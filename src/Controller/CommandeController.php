<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Form\CommandeType;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/commande')]
class CommandeController extends AbstractController
{
    #[Route('/', name: 'app_commande_index', methods: ['GET'])]
    public function index(Request $request, CommandeRepository $commandeRepository): Response
    {
        $status = $request->query->get('status');
        $orderNumber = $request->query->get('order_number');
        $date = $request->query->get('date');
        
        if ($status) {
            $commandes = $commandeRepository->findBy(['statut' => $status], ['dateHeure' => 'DESC']);
        } elseif ($orderNumber) {
            $commandes = $commandeRepository->findBy(['id' => $orderNumber]);
        } elseif ($date) {
            $startDate = new \DateTime($date . ' 00:00:00');
            $endDate = new \DateTime($date . ' 23:59:59');
            $commandes = $commandeRepository->createQueryBuilder('c')
                ->where('c.dateHeure BETWEEN :start AND :end')
                ->setParameter('start', $startDate)
                ->setParameter('end', $endDate)
                ->orderBy('c.dateHeure', 'DESC')
                ->getQuery()
                ->getResult();
        } else {
            // Default: show all orders
            $commandes = $commandeRepository->findBy([], ['dateHeure' => 'DESC']);
        }
        
        return $this->render('commande/index.html.twig', [
            'commandes' => $commandes,
            'current_status' => $status,
            'current_order_number' => $orderNumber,
            'current_date' => $date,
        ]);
    }

    #[Route('/new', name: 'app_commande_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CommandeRepository $commandeRepository): Response
    {
        $commande = new Commande();
        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Auto-calculate estimated time if not manually set
            if ($commande->getEstimatedTime() === null) {
                $commande->setEstimatedTime($this->calculateEstimatedTime($commandeRepository));
            }
            
            $entityManager->persist($commande);
            $entityManager->flush();
            
            $this->addFlash('success', 'Order #' . $commande->getId() . ' created successfully');

            return $this->redirectToRoute('app_commande_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('commande/new.html.twig', [
            'commande' => $commande,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_commande_show', methods: ['GET'])]
    public function show(Commande $commande): Response
    {
        return $this->render('commande/show.html.twig', [
            'commande' => $commande,
        ]);
    }

    #[Route('/{id}', name: 'app_commande_delete', methods: ['POST'])]
    public function delete(Request $request, Commande $commande, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$commande->getId(), $request->getPayload()->get('_token'))) {
            $orderId = $commande->getId();
            $entityManager->remove($commande);
            $entityManager->flush();
            
            $this->addFlash('success', 'Order #' . $orderId . ' has been successfully deleted');
        } else {
            $this->addFlash('error', 'Invalid security token. Please try again.');
        }

        return $this->redirectToRoute('app_commande_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/status', name: 'app_commande_update_status', methods: ['POST'])]
    public function updateStatus(Request $request, Commande $commande, EntityManagerInterface $entityManager, CommandeRepository $commandeRepository): Response
    {
        $newStatus = $request->request->get('status');
        $oldStatus = $commande->getStatut();
        
        $validStatuses = ['PENDING', 'CONFIRMED', 'IN_PROGRESS', 'READY', 'SERVED', 'CANCELLED'];
        
        if (in_array($newStatus, $validStatuses)) {
            $commande->setStatut($newStatus);
            
            // When order moves to IN_PROGRESS or READY, update pending orders' estimated time
            if (in_array($newStatus, ['IN_PROGRESS', 'READY']) && $oldStatus !== $newStatus) {
                $this->updatePendingOrdersEstimatedTime($commandeRepository, $entityManager);
            }
            
            $entityManager->flush();
            
            $this->addFlash('success', 'Order status updated to ' . $newStatus);
        } else {
            $this->addFlash('error', 'Invalid status');
        }

        return $this->redirectToRoute('app_commande_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/estimated-time', name: 'app_commande_update_estimated_time', methods: ['POST'])]
    public function updateEstimatedTime(Request $request, Commande $commande, EntityManagerInterface $entityManager): Response
    {
        $estimatedTime = (int) $request->request->get('estimated_time');
        
        if ($estimatedTime > 0) {
            $commande->setEstimatedTime($estimatedTime);
            $entityManager->flush();
            
            $this->addFlash('success', 'Estimated time updated to ' . $estimatedTime . ' minutes');
        } else {
            $this->addFlash('error', 'Invalid estimated time');
        }

        return $this->redirectToRoute('app_commande_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Calculate estimated time based on paid orders in queue
     * Each order takes 10 minutes: Order 0 = 10min, Order 1 = 20min, etc.
     */
    private function calculateEstimatedTime(CommandeRepository $commandeRepository): int
    {
        // Count paid orders that are pending/confirmed/in-progress (not yet ready/served)
        $qb = $commandeRepository->createQueryBuilder('c')
            ->where('c.paymentStatus = :paid')
            ->andWhere('c.statut IN (:statuses)')
            ->setParameter('paid', 'PAID')
            ->setParameter('statuses', ['PENDING', 'CONFIRMED', 'IN_PROGRESS'])
            ->getQuery();
        
        $paidOrdersInQueue = $qb->getResult();
        
        // Each order adds 10 minutes
        return 10 * (count($paidOrdersInQueue) + 1);
    }

    /**
     * Update estimated time for pending/confirmed orders based on paid orders in queue
     */
    private function updatePendingOrdersEstimatedTime(CommandeRepository $commandeRepository, EntityManagerInterface $entityManager): void
    {
        // Get all paid orders in queue
        $qb = $commandeRepository->createQueryBuilder('c')
            ->where('c.paymentStatus = :paid')
            ->andWhere('c.statut IN (:statuses)')
            ->setParameter('paid', 'PAID')
            ->setParameter('statuses', ['PENDING', 'CONFIRMED', 'IN_PROGRESS'])
            ->orderBy('c.dateHeure', 'ASC')
            ->getQuery();
        
        $paidOrdersInQueue = $qb->getResult();
        
        // Update estimated time for each order based on its position
        $position = 1;
        foreach ($paidOrdersInQueue as $order) {
            $order->setEstimatedTime(10 * $position);
            $position++;
        }
        
        $entityManager->flush();
    }
}
