<?php

namespace App\Controller\Customer;

use App\Repository\CommandeRepository;
use App\Repository\ClientRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/customer/order')]
final class OrderController extends AbstractController
{
    #[Route('/', name: 'app_customer_order_list')]
    public function list(CommandeRepository $commandeRepository, ClientRepository $clientRepository): Response
    {
        // For walk-in customers, show all orders
        // In a real app, you'd filter by session or authenticated user
        $client = $clientRepository->findOneBy(['email' => 'walkin@restaurant.local']);
        
        $commandes = [];
        if ($client) {
            $commandes = $commandeRepository->findBy(
                ['client' => $client->getId()],
                ['dateHeure' => 'DESC']
            );
        }

        return $this->render('customer/order/list.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    #[Route('/show/{id}', name: 'app_customer_order_show')]
    public function show(int $id, CommandeRepository $commandeRepository): Response
    {
        $commande = $commandeRepository->find($id);
        
        if (!$commande) {
            throw $this->createNotFoundException('Order not found');
        }

        return $this->render('customer/order/show.html.twig', [
            'commande' => $commande,
        ]);
    }

    #[Route('/success/{id}', name: 'app_customer_order_success')]
    public function success(int $id, CommandeRepository $commandeRepository): Response
    {
        $commande = $commandeRepository->find($id);
        
        if (!$commande) {
            throw $this->createNotFoundException('Order not found');
        }

        return $this->render('customer/order/success.html.twig', [
            'commande' => $commande,
        ]);
    }

    #[Route('/ticket/{id}', name: 'app_customer_order_ticket')]
    public function ticket(int $id, CommandeRepository $commandeRepository): Response
    {
        $commande = $commandeRepository->find($id);
        
        if (!$commande) {
            throw $this->createNotFoundException('Order not found');
        }

        return $this->render('customer/order/ticket.html.twig', [
            'commande' => $commande,
        ]);
    }

    #[Route('/ticket/{id}/download', name: 'app_customer_order_ticket_download')]
    public function ticketDownload(int $id, CommandeRepository $commandeRepository): Response
    {
        $commande = $commandeRepository->find($id);
        
        if (!$commande) {
            throw $this->createNotFoundException('Order not found');
        }

        // Render the HTML template
        $html = $this->renderView('customer/order/ticket_pdf.html.twig', [
            'commande' => $commande,
        ]);

        // Configure Dompdf
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        
        // Load HTML
        $dompdf->loadHtml($html);
        
        // Set paper size (80mm thermal printer width)
        $dompdf->setPaper([0, 0, 226.77, 841.89], 'portrait'); // 80mm x 297mm (A4 height)
        
        // Render PDF
        $dompdf->render();
        
        // Output PDF for download
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="receipt-order-' . $commande->getId() . '.pdf"',
            ]
        );
    }
}
