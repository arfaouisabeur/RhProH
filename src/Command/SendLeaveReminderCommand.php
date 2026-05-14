<?php

namespace App\Command;

use App\Entity\CongeTt;
use App\Service\SmsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Commande pour envoyer des SMS de rappel aux employés
 * dont le congé commence dans 2 jours.
 * 
 * Exécution : php bin/console app:send-leave-reminder
 * 
 * Pour automatiser : ajouter dans crontab (Linux) ou Task Scheduler (Windows)
 * Exemple cron : 0 9 * * * cd /path/to/project && php bin/console app:send-leave-reminder
 */
#[AsCommand(
    name: 'app:send-leave-reminder',
    description: 'Envoie des SMS de rappel pour les congés qui commencent dans 2 jours'
)]
class SendLeaveReminderCommand extends Command
{
    private EntityManagerInterface $em;
    private SmsService $smsService;

    public function __construct(EntityManagerInterface $em, SmsService $smsService)
    {
        parent::__construct();
        $this->em = $em;
        $this->smsService = $smsService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🔔 Envoi des rappels de congé');

        // Date dans 2 jours
        $dateDebut = new \DateTime('+2 days');
        $dateDebut->setTime(0, 0, 0);
        
        $dateFin = clone $dateDebut;
        $dateFin->setTime(23, 59, 59);

        $io->info(sprintf('Recherche des congés acceptés qui commencent le %s', $dateDebut->format('d/m/Y')));

        // Récupérer tous les congés acceptés qui commencent dans 2 jours
        $conges = $this->em->getRepository(CongeTt::class)
            ->createQueryBuilder('c')
            ->where('c.statut = :statut')
            ->andWhere('c.date_debut >= :debut')
            ->andWhere('c.date_debut <= :fin')
            ->setParameter('statut', 'Accepté')
            ->setParameter('debut', $dateDebut)
            ->setParameter('fin', $dateFin)
            ->getQuery()
            ->getResult();

        if (empty($conges)) {
            $io->success('Aucun congé à rappeler pour cette date.');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Trouvé %d congé(s) à rappeler', count($conges)));

        $sent = 0;
        $errors = 0;

        foreach ($conges as $conge) {
            try {
                $employe = $conge->getEmploye();
                if (!$employe) {
                    $io->warning(sprintf('Congé #%d : employé introuvable', $conge->getId()));
                    $errors++;
                    continue;
                }

                $user = $employe->getUser();
                if (!$user) {
                    $io->warning(sprintf('Congé #%d : utilisateur introuvable pour l\'employé', $conge->getId()));
                    $errors++;
                    continue;
                }

                $telephone = method_exists($user, 'getTelephone') ? $user->getTelephone() : null;
                if (!$telephone) {
                    $io->warning(sprintf('Congé #%d : numéro de téléphone introuvable pour %s', 
                        $conge->getId(), 
                        $user->getNom() ?? 'employé'
                    ));
                    $errors++;
                    continue;
                }

                // Formater le téléphone
                $telephone = $this->formatTelephone($telephone);

                // Créer le message de rappel
                $message = sprintf(
                    "RAPPEL RH\nVotre conge (%s) commence dans 2 jours le %s. Bonne preparation !",
                    $conge->getTypeConge() ?? 'congé',
                    $conge->getDateDebut()?->format('d/m/Y') ?? '-'
                );

                // Envoyer le SMS
                $this->smsService->sendSms($telephone, $message);

                $io->text(sprintf('✅ SMS envoyé à %s (%s)', 
                    $user->getNom() ?? 'employé', 
                    $telephone
                ));
                $sent++;

            } catch (\Exception $e) {
                $io->error(sprintf('Erreur pour le congé #%d : %s', 
                    $conge->getId(), 
                    $e->getMessage()
                ));
                $errors++;
            }
        }

        $io->newLine();
        $io->success(sprintf('Rappels envoyés : %d / %d', $sent, count($conges)));
        
        if ($errors > 0) {
            $io->warning(sprintf('%d erreur(s) rencontrée(s)', $errors));
        }

        return Command::SUCCESS;
    }

    /**
     * Formate un numéro tunisien : si pas de +, ajoute +216.
     */
    private function formatTelephone(string $phone): string
    {
        $phone = preg_replace('/\s+/', '', $phone); // retirer espaces
        if (!str_starts_with($phone, '+')) {
            $phone = '+216' . ltrim($phone, '0');
        }
        return $phone;
    }
}
