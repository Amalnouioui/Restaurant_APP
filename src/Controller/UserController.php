<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        // Get clients (active clients)
        $clients = $userRepository->findBy([
            'accountType' => 'client',
            'isActive' => true
        ]);
        
        // Get pending staff (inactive staff waiting for role assignment)
        $pendingStaff = $userRepository->createQueryBuilder('u')
            ->where('u.accountType = :staff')
            ->andWhere('u.isActive = :inactive')
            ->setParameter('staff', 'staff')
            ->setParameter('inactive', false)
            ->getQuery()
            ->getResult();
        
        // Get active staff (staff with roles assigned)
        $activeStaff = $userRepository->createQueryBuilder('u')
            ->where('u.accountType = :staff')
            ->andWhere('u.isActive = :active')
            ->setParameter('staff', 'staff')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
        
        return $this->render('user/index.html.twig', [
            'clients' => $clients,
            'pending_staff' => $pendingStaff,
            'active_staff' => $activeStaff,
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password
            $plainPassword = $form->get('password')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
            
            // Get the selected role from the form
            $selectedRole = $form->get('role')->getData();
            $user->setRoles([$selectedRole]);
            
            $entityManager->persist($user);
            $entityManager->flush();
            
            $this->addFlash('success', 'User "' . $user->getFullName() . '" created successfully with role: ' . $selectedRole);

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Only update password if a new one was provided
            $plainPassword = $form->get('password')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }
            
            // Update the role
            $selectedRole = $form->get('role')->getData();
            $user->setRoles([$selectedRole]);
            
            // If assigning role to staff and they're inactive, activate them
            if ($user->getAccountType() === 'staff' && !$user->getIsActive() && $selectedRole !== 'ROLE_CLIENT') {
                $user->setIsActive(true);
                $this->addFlash('success', 'Staff member "' . $user->getFullName() . '" has been activated with role: ' . $selectedRole);
            } else {
                $this->addFlash('success', 'User "' . $user->getFullName() . '" updated successfully');
            }
            
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->get('_token'))) {
            $userName = $user->getFullName();
            $entityManager->remove($user);
            $entityManager->flush();
            
            $this->addFlash('success', 'User "' . $userName . '" has been deleted');
        } else {
            $this->addFlash('error', 'Invalid security token. Please try again.');
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
