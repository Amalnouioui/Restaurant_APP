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
        $commandes = $client ? $this->commandeRepository->findBy(['client' => $client]) : [];
        
        return $this->render('customer/cart/view.html.twig', [
            'cart' => $sessionCart,
            'commandes' => $commandes,
            'client' => $client,
        ]);
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

