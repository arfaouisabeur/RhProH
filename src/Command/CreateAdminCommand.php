<?php

namespace App\Command;

use App\Entity\RH;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create an admin user for production',
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
        $existingAdmin = $this->entityManager->getRepository(RH::class)->findOneBy(['email' => 'admin@rhpro.com']);
        
        if ($existingAdmin) {
            $io->warning('Admin user already exists!');
            return Command::SUCCESS;
        }

        // Create admin user
        $admin = new RH();
        $admin->setEmail('admin@rhpro.com');
        $admin->setNom('Admin');
        $admin->setPrenom('RHPro');
        $admin->setTelephone('+21600000000');
        $admin->setAdresse('Tunis, Tunisia');
        $admin->setRoles(['ROLE_RH', 'ROLE_ADMIN']);
        
        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'admin123');
        $admin->setPassword($hashedPassword);

        // Save to database
        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success('Admin user created successfully!');
        $io->info('Email: admin@rhpro.com');
        $io->info('Password: admin123');
        $io->warning('Please change the password after first login!');

        return Command::SUCCESS;
    }
}
