<?php

namespace App\Security;

use App\Entity\Client;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private RouterInterface $router;
    private ClientRepository $clientRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        RouterInterface $router,
        ClientRepository $clientRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->router = $router;
        $this->clientRepository = $clientRepository;
        $this->entityManager = $entityManager;
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

        // For clients, ensure they have a Client record
        $this->ensureClientRecord($user, $request);

        // Clients go to menu
        return new RedirectResponse($this->router->generate('app_customer_menu'));
    }

    private function ensureClientRecord($user, Request $request): void
    {
        // Check if client record exists for this user
        $client = $this->clientRepository->findOneBy(['user' => $user]);

        if (!$client) {
            // Check if walk-in customer
            $isWalkIn = $user->getEmail() === 'walkin@restaurant.local';

            if ($isWalkIn) {
                // For walk-in, create new session-based client record
                $sessionId = session_id() ?: uniqid('walkin_', true);
                $request->getSession()->set('walkin_session_id', $sessionId);

                $client = new Client();
                $client->setNom('Walk-in');
                $client->setPrenom('Customer');
                $client->setEmail($user->getEmail());
                $client->setUser($user);
                $client->setSessionId($sessionId);
            } else {
                // For regular client, create permanent client record
                $client = new Client();
                $client->setNom($user->getNom());
                $client->setPrenom($user->getPrenom());
                $client->setEmail($user->getEmail());
                $client->setUser($user);
            }

            $this->entityManager->persist($client);
            $this->entityManager->flush();
        } elseif ($client->getUser() && $client->getUser()->getEmail() === 'walkin@restaurant.local') {
            // Walk-in customer logging in again - create new session
            $sessionId = session_id() ?: uniqid('walkin_', true);
            $request->getSession()->set('walkin_session_id', $sessionId);
            $client->setSessionId($sessionId);
            $this->entityManager->flush();
        }
    }
}
