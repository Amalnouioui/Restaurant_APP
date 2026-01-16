<?php

namespace App\Controller\Customer;

use App\Entity\Client;
use App\Entity\Commande;
use App\Entity\LigneCommande;
use App\Entity\Plat;
use App\Repository\ClientRepository;
use App\Repository\CommandeRepository;
use App\Repository\LigneCommandeRepository;
use App\Repository\PlatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/customer')]
final class OrderController extends AbstractController
{
    public function __construct(
        private PlatRepository $platRepository,
        private CommandeRepository $commandeRepository,
        private LigneCommandeRepository $ligneCommandeRepository,
        private ClientRepository $clientRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    #[Route('/menu', name: 'app_customer_menu')]
    public function menu(): Response
    {
        $plats = $this->platRepository->findAll();
        
        return $this->render('customer/menu/index.html.twig', [
            'plats' => $plats,
        ]);
    }

    #[Route('/add-to-cart', name: 'app_customer_add_to_cart', methods: ['POST'])]
    public function addToCart(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $platId = $data['platId'] ?? null;
        $quantity = $data['quantity'] ?? 1;

        if (!$platId) {
            return $this->json(['error' => 'Invalid plat ID'], 400);
        }

        $plat = $this->platRepository->find($platId);
        if (!$plat) {
            return $this->json(['error' => 'Plat not found'], 404);
        }

        // Get or create cart in session
        $cart = $request->getSession()->get('cart', []);
        
        if (isset($cart[$platId])) {
            $cart[$platId]['quantity'] += $quantity;
        } else {
            $cart[$platId] = [
                'id' => $plat->getId(),
                'name' => $plat->getNomPlat(),
                'price' => $plat->getPrix(),
                'quantity' => $quantity,
            ];
        }

        $request->getSession()->set('cart', $cart);

        return $this->json([
            'success' => true,
            'message' => 'Dish added to cart',
            'cart' => $cart,
        ]);
    }

    #[Route('/cart', name: 'app_customer_cart')]
    public function viewCart(Request $request): Response
    {
        $cart = $request->getSession()->get('cart', []);
        
        return $this->render('customer/cart/index.html.twig', [
            'cart' => $cart,
        ]);
    }

    #[Route('/checkout', name: 'app_customer_checkout', methods: ['POST'])]
    public function checkout(Request $request): Response
    {
        $cart = $request->getSession()->get('cart', []);

        if (empty($cart)) {
            return $this->json(['error' => 'Cart is empty'], 400);
        }

        // Get client from request or use the first available client
        $data = json_decode($request->getContent(), true);
        $clientId = $data['clientId'] ?? null;

        if ($clientId) {
            $client = $this->clientRepository->find($clientId);
            if (!$client) {
                return $this->json(['error' => 'Client not found'], 404);
            }
        } else {
            // Use first available client for testing
            $clients = $this->clientRepository->findAll();
            if (empty($clients)) {
                return $this->json(['error' => 'No client available'], 400);
            }
            $client = $clients[0];
        }

        try {
            // Create Commande
            $commande = new Commande();
            $commande->setClient($client);
            $commande->setDateHeure(new \DateTime());
            $commande->setStatut('pending');
            $commande->setTotal(0);

            $total = 0;

            // Create LigneCommande entries
            foreach ($cart as $item) {
                $plat = $this->platRepository->find($item['id']);
                if (!$plat) {
                    continue;
                }

                $ligneCommande = new LigneCommande();
                $ligneCommande->setCommande($commande);
                $ligneCommande->setPlat($plat);
                $ligneCommande->setQuantite($item['quantity']);

                $commande->addLigneCommande($ligneCommande);
                $total += $plat->getPrix() * $item['quantity'];

                $this->entityManager->persist($ligneCommande);
            }

            $commande->setTotal($total);
            $this->entityManager->persist($commande);
            $this->entityManager->flush();

            // Clear cart from session
            $request->getSession()->remove('cart');

            return $this->json([
                'success' => true,
                'message' => 'Order created successfully',
                'commandeId' => $commande->getId(),
                'total' => $total,
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error creating order: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/order', name: 'app_customer_order_list')]
    public function list(): Response
    {
        return $this->render('customer/order/list.html.twig', [
            'controller_name' => 'OrderController',
        ]);
    }

    #[Route('/order/{id}', name: 'app_customer_order_show')]
    public function show(int $id): Response
    {
        return $this->render('customer/order/show.html.twig', [
            'order_id' => $id,
        ]);
    }
}
