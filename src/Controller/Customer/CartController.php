<?php

namespace App\Controller\Customer;

use App\Repository\ClientRepository;
use App\Repository\CommandeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/customer/cart')]
final class CartController extends AbstractController
{
    public function __construct(
        private ClientRepository $clientRepository,
        private CommandeRepository $commandeRepository,
    ) {}

    #[Route('/', name: 'app_customer_cart_view')]
    public function view(Request $request): Response
    {
        // Get session cart for pending items
        $sessionCart = $request->getSession()->get('cart', []);
        
        // Get the first client (or you could implement user authentication)
        $clients = $this->clientRepository->findAll();
        $client = !empty($clients) ? $clients[0] : null;
        
        // Get all commands for this client from database
        $commandes = $client ? $this->commandeRepository->findBy(['client' => $client], ['dateHeure' => 'DESC']) : [];
        
        return $this->render('customer/cart/view.html.twig', [
            'cart' => $sessionCart,
            'commandes' => $commandes,
            'client' => $client,
        ]);
    }

    #[Route('/api/orders', name: 'app_customer_api_orders', methods: ['GET'])]
    public function getOrders(): Response
    {
        // Get the first client
        $clients = $this->clientRepository->findAll();
        $client = !empty($clients) ? $clients[0] : null;
        
        if (!$client) {
            return $this->json(['orders' => []]);
        }
        
        // Get all commands for this client from database
        $commandes = $this->commandeRepository->findBy(['client' => $client], ['dateHeure' => 'DESC']);
        
        $ordersData = [];
        foreach ($commandes as $commande) {
            $items = [];
            foreach ($commande->getLigneCommandes() as $ligneCommande) {
                $items[] = [
                    'dishName' => $ligneCommande->getPlat()->getNomPlat(),
                    'price' => $ligneCommande->getPlat()->getPrix(),
                    'quantity' => $ligneCommande->getQuantite(),
                    'subtotal' => $ligneCommande->getPlat()->getPrix() * $ligneCommande->getQuantite(),
                ];
            }
            
            $ordersData[] = [
                'id' => $commande->getId(),
                'date' => $commande->getDateHeure()->format('d/m/Y H:i'),
                'status' => $commande->getStatut(),
                'total' => $commande->getTotal(),
                'items' => $items,
            ];
        }
        
        return $this->json(['orders' => $ordersData]);
    }

    #[Route('/add/{platId}', name: 'app_customer_cart_add')]
    public function add(int $platId): Response
    {
        return $this->redirectToRoute('app_customer_cart_view');
    }

    #[Route('/checkout', name: 'app_customer_cart_checkout')]
    public function checkout(): Response
    {
        return $this->render('customer/cart/checkout.html.twig');
    }
}


