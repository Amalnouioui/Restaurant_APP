<?php

namespace App\Controller\Customer;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/customer/order')]
final class OrderController extends AbstractController
{
    #[Route('/', name: 'app_customer_order_list')]
    public function list(): Response
    {
        return $this->render('customer/order/list.html.twig', [
            'controller_name' => 'OrderController',
        ]);
    }

    #[Route('/{id}', name: 'app_customer_order_show')]
    public function show(int $id): Response
    {
        return $this->render('customer/order/show.html.twig', [
            'order_id' => $id,
        ]);
    }
}
