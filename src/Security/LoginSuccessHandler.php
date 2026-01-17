<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $user = $token->getUser();
        $roles = $user->getRoles();

        // Admin goes to user management
        if (in_array('ROLE_ADMIN', $roles)) {
            return new RedirectResponse($this->router->generate('app_user_index'));
        }

        // Staff members go to orders page
        if (in_array('ROLE_CASHIER', $roles) || 
            in_array('ROLE_CHEF', $roles) || 
            in_array('ROLE_WAITER', $roles)) {
            return new RedirectResponse($this->router->generate('app_commande_index'));
        }

        // Clients go to menu
        return new RedirectResponse($this->router->generate('app_customer_menu'));
    }
}
