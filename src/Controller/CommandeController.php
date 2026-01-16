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
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $commande = new Commande();
        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($commande);
            $entityManager->flush();

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

    #[Route('/{id}/edit', name: 'app_commande_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Commande $commande, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_commande_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('commande/edit.html.twig', [
            'commande' => $commande,
            'form' => $form,
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
    public function updateStatus(Request $request, Commande $commande, EntityManagerInterface $entityManager): Response
    {
        $newStatus = $request->request->get('status');
        
        $validStatuses = ['PENDING', 'CONFIRMED', 'IN_PROGRESS', 'READY', 'SERVED', 'CANCELLED'];
        
        if (in_array($newStatus, $validStatuses)) {
            $commande->setStatut($newStatus);
            $entityManager->flush();
            
            $this->addFlash('success', 'Order status updated to ' . $newStatus);
        } else {
            $this->addFlash('error', 'Invalid status');
        }

        return $this->redirectToRoute('app_commande_index', [], Response::HTTP_SEE_OTHER);
    }
}
