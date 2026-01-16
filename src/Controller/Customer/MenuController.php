<?php

namespace App\Controller\Customer;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/customer/menu')]
final class MenuController extends AbstractController
{
    #[Route('/', name: 'app_customer_menu')]
    public function index(): Response
    {
        return $this->render('customer/menu/index.html.twig', [
            'controller_name' => 'MenuController',
        ]);
    }
}
