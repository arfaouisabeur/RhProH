<?php

namespace App\Command;

use App\Repository\CandidatureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:clean-cv-paths',
    description: 'Nettoie les chemins CV en base de données pour ne garder que les noms de fichiers'
)]
class CleanCvPathsCommand extends Command
{
    public function __construct(
        private CandidatureRepository $candidatureRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Nettoyage des chemins CV');

        // Récupérer toutes les candidatures avec un CV
        $candidatures = $this->candidatureRepository->createQueryBuilder('c')
            ->where('c.cvPath IS NOT NULL')
            ->andWhere('c.cvPath != :empty')
            ->setParameter('empty', '')
            ->getQuery()
            ->getResult();

        $updated = 0;
        $io->writeln("Candidatures trouvées: " . count($candidatures));

        foreach ($candidatures as $candidature) {
            // Accéder directement à la propriété privée pour voir la valeur réelle
            $reflection = new \ReflectionClass($candidature);
            $property = $reflection->getProperty('cvPath');
            $property->setAccessible(true);
            $originalPath = $property->getValue($candidature);
            
            $io->writeln("Vérification: ID {$candidature->getId()} - Path: {$originalPath}");
            
            if ($originalPath && (strpos($originalPath, '/') !== false || strpos($originalPath, '\\') !== false)) {
                $cleanPath = basename($originalPath);
                $property->setValue($candidature, $cleanPath);
                
                $io->writeln("✅ Nettoyage: {$originalPath} → {$cleanPath}");
                $updated++;
                
                // Flush immédiatement pour cette candidature
                $this->entityManager->persist($candidature);
                $this->entityManager->flush();
            }
        }

        if ($updated > 0) {
            $io->success("✅ {$updated} chemins CV ont été nettoyés et sauvegardés.");
        } else {
            $io->info('Aucun chemin CV à nettoyer.');
        }

        return Command::SUCCESS;
    }
}