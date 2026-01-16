<?php

namespace App\Controller\Customer;

use App\Entity\Client;
use App\Entity\Commande;
use App\Entity\LigneCommande;
use App\Repository\PlatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/customer/cart')]
final class CartController extends AbstractController
{
    #[Route('/', name: 'app_customer_cart_view')]
    public function view(SessionInterface $session, PlatRepository $platRepository): Response
    {
        $cart = $session->get('cart', []);
        $cartItems = [];
        $total = 0;

        foreach ($cart as $platId => $quantity) {
            $plat = $platRepository->find($platId);
            if ($plat) {
                $subtotal = $plat->getPrix() * $quantity;
                $cartItems[] = [
                    'plat' => $plat,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal
                ];
                $total += $subtotal;
            }
        }

        return $this->render('customer/cart/view.html.twig', [
            'cartItems' => $cartItems,
            'total' => $total
        ]);
    }

    #[Route('/add/{platId}', name: 'app_customer_cart_add', methods: ['POST'])]
    public function add(int $platId, Request $request, SessionInterface $session, PlatRepository $platRepository): Response
    {
        $plat = $platRepository->find($platId);
        
        if (!$plat) {
            $this->addFlash('error', 'Dish not found');
            return $this->redirectToRoute('app_customer_menu');
        }

        $quantity = (int) $request->request->get('quantity', 1);
        
        if ($quantity < 1) {
            $quantity = 1;
        }

        $cart = $session->get('cart', []);
        
        if (isset($cart[$platId])) {
            $cart[$platId] += $quantity;
        } else {
            $cart[$platId] = $quantity;
        }

        $session->set('cart', $cart);
        
        $this->addFlash('success', $plat->getNomPlat() . ' added to cart!');
        
        return $this->redirectToRoute('app_customer_menu');
    }

    #[Route('/remove/{platId}', name: 'app_customer_cart_remove')]
    public function remove(int $platId, SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);
        
        if (isset($cart[$platId])) {
            unset($cart[$platId]);
            $session->set('cart', $cart);
            $this->addFlash('success', 'Item removed from cart');
        }
        
        return $this->redirectToRoute('app_customer_cart_view');
    }

    #[Route('/update/{platId}', name: 'app_customer_cart_update', methods: ['POST'])]
    public function update(int $platId, Request $request, SessionInterface $session): Response
    {
        $quantity = (int) $request->request->get('quantity', 1);
        
        if ($quantity < 1) {
            return $this->redirectToRoute('app_customer_cart_remove', ['platId' => $platId]);
        }

        $cart = $session->get('cart', []);
        
        if (isset($cart[$platId])) {
            $cart[$platId] = $quantity;
            $session->set('cart', $cart);
        }
        
        return $this->redirectToRoute('app_customer_cart_view');
    }

    #[Route('/checkout', name: 'app_customer_cart_checkout')]
    public function checkout(SessionInterface $session, PlatRepository $platRepository): Response
    {
        $cart = $session->get('cart', []);
        
        if (empty($cart)) {
            $this->addFlash('error', 'Your cart is empty');
            return $this->redirectToRoute('app_customer_menu');
        }

        $cartItems = [];
        $subtotal = 0;

        foreach ($cart as $platId => $quantity) {
            $plat = $platRepository->find($platId);
            if ($plat) {
                $itemSubtotal = $plat->getPrix() * $quantity;
                $cartItems[] = [
                    'plat' => $plat,
                    'quantity' => $quantity,
                    'subtotal' => $itemSubtotal
                ];
                $subtotal += $itemSubtotal;
            }
        }

        $tax = $subtotal * 0.10;
        $delivery = 5.00;
        $total = $subtotal + $tax + $delivery;

        return $this->render('customer/cart/checkout.html.twig', [
            'cartItems' => $cartItems,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'delivery' => $delivery,
            'total' => $total
        ]);
    }

    #[Route('/checkout/process', name: 'app_customer_cart_process', methods: ['POST'])]
    public function processCheckout(
        Request $request,
        SessionInterface $session,
        PlatRepository $platRepository,
        EntityManagerInterface $em
    ): Response {
        $cart = $session->get('cart', []);
        
        if (empty($cart)) {
            $this->addFlash('error', 'Your cart is empty');
            return $this->redirectToRoute('app_customer_menu');
        }

        $paymentMethod = $request->request->get('payment_method');
        
        if (!in_array($paymentMethod, ['CASH', 'CARD'])) {
            $this->addFlash('error', 'Invalid payment method');
            return $this->redirectToRoute('app_customer_cart_checkout');
        }

        // Validate card payment
        if ($paymentMethod === 'CARD') {
            $cardholderName = $request->request->get('cardholder_name');
            $cardNumber = str_replace(' ', '', $request->request->get('card_number'));
            $expiryDate = $request->request->get('expiry_date');
            $cvv = $request->request->get('cvv');
            
            // Validate cardholder name
            if (empty($cardholderName) || !preg_match('/^[A-Za-z\s]+$/', $cardholderName)) {
                $this->addFlash('error', '❌ Invalid cardholder name');
                return $this->redirectToRoute('app_customer_cart_checkout');
            }
            
            // Validate card number (16 digits)
            if (!preg_match('/^\d{16}$/', $cardNumber)) {
                $this->addFlash('error', '❌ Invalid card number - must be 16 digits');
                return $this->redirectToRoute('app_customer_cart_checkout');
            }
            
            // Validate expiry date
            if (!preg_match('/^(\d{2})\s\/\s(\d{2})$/', $expiryDate, $matches)) {
                $this->addFlash('error', '❌ Invalid expiry date format (use MM / YY)');
                return $this->redirectToRoute('app_customer_cart_checkout');
            }
            
            $month = (int)$matches[1];
            $year = (int)('20' . $matches[2]);
            
            if ($month < 1 || $month > 12) {
                $this->addFlash('error', '❌ Invalid expiry month (must be 01-12)');
                return $this->redirectToRoute('app_customer_cart_checkout');
            }
            
            $now = new \DateTime();
            $expiryDateTime = new \DateTime("$year-$month-01");
            if ($expiryDateTime < $now) {
                $this->addFlash('error', '❌ Card has expired');
                return $this->redirectToRoute('app_customer_cart_checkout');
            }
            
            // Validate CVV
            if (!preg_match('/^\d{3}$/', $cvv)) {
                $this->addFlash('error', '❌ Invalid CVV (must be 3 digits)');
                return $this->redirectToRoute('app_customer_cart_checkout');
            }
            
            // Simulate payment processing (90% success rate for demo)
            $paymentSuccess = rand(1, 10) > 1;
            
            if (!$paymentSuccess) {
                $this->addFlash('error', '❌ Payment declined. Please try again or use a different card.');
                return $this->redirectToRoute('app_customer_cart_checkout');
            }
        }

        // Calculate total
        $subtotal = 0;
        foreach ($cart as $platId => $quantity) {
            $plat = $platRepository->find($platId);
            if ($plat) {
                $subtotal += $plat->getPrix() * $quantity;
            }
        }
        
        $tax = $subtotal * 0.10;
        $delivery = 5.00;
        $total = $subtotal + $tax + $delivery;

        // Create or get walk-in client
        $clientRepo = $em->getRepository(Client::class);
        $client = $clientRepo->findOneBy(['email' => 'walkin@restaurant.local']);
        
        if (!$client) {
            $client = new Client();
            $client->setNom('Walk-In');
            $client->setPrenom('Customer');
            $client->setEmail('walkin@restaurant.local');
            $em->persist($client);
        }

        // Create order
        $commande = new Commande();
        $commande->setClient($client);
        $commande->setDateHeure(new \DateTime());
        $commande->setTotal($total);
        $commande->setPaymentMethod($paymentMethod);
        
        if ($paymentMethod === 'CASH') {
            $commande->setStatut('AWAITING_PAYMENT');
            $commande->setPaymentStatus('AWAITING_CASH');
        } else {
            // For CARD, simulate payment gateway
            $commande->setStatut('CONFIRMED');
            $commande->setPaymentStatus('PAID');
        }

        // Add order lines
        foreach ($cart as $platId => $quantity) {
            $plat = $platRepository->find($platId);
            if ($plat) {
                $ligne = new LigneCommande();
                $ligne->setCommande($commande);
                $ligne->setPlat($plat);
                $ligne->setQuantite($quantity);
                $commande->addLigneCommande($ligne);
                $em->persist($ligne);
            }
        }

        $em->persist($commande);
        $em->flush();

        // Clear cart
        $session->remove('cart');

        return $this->redirectToRoute('app_customer_order_success', ['id' => $commande->getId()]);
    }
}
