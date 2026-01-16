<?php

namespace App\Controller\Customer;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/customer/cart')]
final class CartController extends AbstractController
{
    #[Route('/', name: 'app_customer_cart_view')]
    public function view(): Response
    {
        return $this->render('customer/cart/view.html.twig');
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
