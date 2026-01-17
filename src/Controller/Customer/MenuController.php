<?php

namespace App\Controller\Customer;

use App\Repository\PlatRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/customer/menu')]
final class MenuController extends AbstractController
{
    #[Route('/', name: 'app_customer_menu')]
    public function index(PlatRepository $platRepository, SessionInterface $session): Response
    {
        $plats = $platRepository->findAll();
        $cart = $session->get('cart', []);
        $cartCount = array_sum($cart);
        
        return $this->render('customer/menu/index.html.twig', [
            'plats' => $plats,
            'cartCount' => $cartCount,
        ]);
    }
}
