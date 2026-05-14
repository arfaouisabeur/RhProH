<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\RH;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create an admin user for production',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Check if admin already exists
        $existingAdmin = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@rhpro.com']);
        
        if ($existingAdmin) {
            $io->warning('Admin user already exists!');
            return Command::SUCCESS;
        }

        // Create admin user (password stored in plain text for compatibility)
        $user = new User();
        $user->setEmail('admin@rhpro.com');
        $user->setNom('Admin');
        $user->setPrenom('RHPro');
        $user->setTelephone('+21600000000');
        $user->setAdresse('Tunis, Tunisia');
        $user->setRole('RH');
        $user->setMotDePasse('admin123'); // Plain text password for Java compatibility
        $user->setStatut('actif');

        // Save user first to get ID
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Create RH profile linked to user
        $rh = new RH();
        $rh->setUser($user);

        // Save RH profile
        $this->entityManager->persist($rh);
        $this->entityManager->flush();

        $io->success('Admin user created successfully!');
        $io->info('Email: admin@rhpro.com');
        $io->info('Password: admin123');
        $io->warning('Please change the password after first login!');

        return Command::SUCCESS;
    }
}
