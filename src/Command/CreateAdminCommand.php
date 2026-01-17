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
    name: 'app:create-admin',
    description: 'Creates the default admin user',
)]
class CreateAdminCommand extends Command
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

        // Check if admin already exists
        $userRepo = $this->entityManager->getRepository(User::class);
        $existingAdmin = $userRepo->findOneBy(['email' => 'admin@restaurant.local']);

        if ($existingAdmin) {
            $io->warning('Admin user already exists!');
            $io->info('Email: admin@restaurant.local');
            return Command::SUCCESS;
        }

        // Create admin user
        $admin = new User();
        $admin->setEmail('admin@restaurant.local');
        $admin->setNom('Admin');
        $admin->setPrenom('System');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setAccountType('staff');
        $admin->setIsActive(true);
        
        // Hash password: admin123
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'admin123');
        $admin->setPassword($hashedPassword);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success('Admin user created successfully!');
        $io->table(
            ['Field', 'Value'],
            [
                ['Email', 'admin@restaurant.local'],
                ['Password', 'admin123'],
                ['Name', 'System Admin'],
                ['Role', 'ROLE_ADMIN'],
            ]
        );
        
        $io->warning('Please change the admin password after first login!');

        return Command::SUCCESS;
    }
}
