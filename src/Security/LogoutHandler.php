<?php

namespace App\Security;

use App\Repository\ClientRepository;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutHandler
{
    private ClientRepository $clientRepository;
    private CommandeRepository $commandeRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        ClientRepository $clientRepository,
        CommandeRepository $commandeRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->clientRepository = $clientRepository;
        $this->commandeRepository = $commandeRepository;
        $this->entityManager = $entityManager;
    }

    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();
        
        if (!$token) {
            return;
        }

        $user = $token->getUser();
        
        if (!$user) {
            return;
        }

        // Check if this is a walk-in customer
        if ($user->getEmail() === 'walkin@restaurant.local') {
            $request = $event->getRequest();
            $sessionId = $request->getSession()->get('walkin_session_id');
            
            if ($sessionId) {
                // Find client by session ID
                $client = $this->clientRepository->findOneBy(['sessionId' => $sessionId]);
                
                if ($client) {
                    // Delete all orders for this walk-in session
                    $commandes = $this->commandeRepository->findBy(['client' => $client]);
                    
                    foreach ($commandes as $commande) {
                        $this->entityManager->remove($commande);
                    }
                    
                    // Remove the client record
                    $this->entityManager->remove($client);
                    $this->entityManager->flush();
                }
            }
        }
    }
}
