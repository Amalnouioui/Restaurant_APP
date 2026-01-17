<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-walkin',
    description: 'Creates the walk-in customer account',
)]
class CreateWalkinCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Check if walk-in customer already exists
        $userRepo = $this->entityManager->getRepository(User::class);
        $existingWalkin = $userRepo->findOneBy(['email' => 'walkin@restaurant.local']);

        if ($existingWalkin) {
            $io->warning('Walk-in customer already exists!');
            $io->info('Email: walkin@restaurant.local');
            return Command::SUCCESS;
        }

        // Create walk-in customer
        $walkin = new User();
        $walkin->setEmail('walkin@restaurant.local');
        $walkin->setNom('Customer');
        $walkin->setPrenom('Walk-in');
        $walkin->setRoles(['ROLE_CLIENT']);
        $walkin->setAccountType('client');
        $walkin->setIsActive(true);
        
        // Hash password: walkin123
        $hashedPassword = $this->passwordHasher->hashPassword($walkin, 'walkin123');
        $walkin->setPassword($hashedPassword);

        $this->entityManager->persist($walkin);
        $this->entityManager->flush();

        $io->success('Walk-in customer account created successfully!');
        $io->table(
            ['Field', 'Value'],
            [
                ['Email', 'walkin@restaurant.local'],
                ['Password', 'walkin123'],
                ['Name', 'Walk-in Customer'],
                ['Type', 'Client (No registration needed)'],
            ]
        );

        return Command::SUCCESS;
    }
}
