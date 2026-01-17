<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Redirect if already logged in
        if ($this->getUser()) {
            return $this->redirectToRoute('app_commande_index');
        }
        
        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        
        return $this->render('auth/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request, 
        EntityManagerInterface $em, 
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        // Redirect if already logged in
        if ($this->getUser()) {
            return $this->redirectToRoute('app_commande_index');
        }
        
        if ($request->isMethod('POST')) {
            $accountType = $request->request->get('account_type');
            $nom = $request->request->get('nom');
            $prenom = $request->request->get('prenom');
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');
            
            // Validation
            if (empty($nom) || empty($prenom) || empty($email) || empty($password) || empty($accountType)) {
                $this->addFlash('error', 'All fields are required');
                return $this->render('auth/register.html.twig', [
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => $email,
                ]);
            }
            
            if ($password !== $confirmPassword) {
                $this->addFlash('error', 'Passwords do not match');
                return $this->render('auth/register.html.twig', [
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => $email,
                ]);
            }
            
            if (strlen($password) < 6) {
                $this->addFlash('error', 'Password must be at least 6 characters');
                return $this->render('auth/register.html.twig', [
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => $email,
                ]);
            }
            
            // Check if email already exists
            $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                $this->addFlash('error', 'Email already registered');
                return $this->render('auth/register.html.twig', [
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => $email,
                ]);
            }
            
            // Create new user
            $user = new User();
            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setEmail($email);
            $user->setAccountType($accountType);
            
            // Set role and active status based on account type
            if ($accountType === 'client') {
                $user->setRoles(['ROLE_CLIENT']);
                $user->setIsActive(true);
                $message = 'Registration successful! You can now log in.';
            } else {
                // Staff - no specific role yet, inactive until admin approves
                $user->setRoles(['ROLE_CLIENT']); // Temporary default
                $user->setIsActive(false);
                $message = 'Staff account created! Please wait for admin to assign your role before logging in.';
            }
            
            // Hash password
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);
            
            $em->persist($user);
            $em->flush();
            
            $this->addFlash('success', $message);
            return $this->redirectToRoute('app_login');
        }
        
        return $this->render('auth/register.html.twig');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // This method can be blank - Symfony handles logout automatically
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
