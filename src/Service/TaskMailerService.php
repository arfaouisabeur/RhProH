<?php

namespace App\Service;

use App\Entity\Tache;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class TaskMailerService
{
    private MailerInterface $mailer;
    private string $systemEmail = 'nayssenk@gmail.com';

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendTaskCompletedEmail(Tache $tache): void
    {
        $rhUser = $tache->getProjet()?->getRh()?->getUser();
        if (!$rhUser || !$rhUser->getEmail()) return;

        $employeeName = $tache->getEmploye()?->getUser()?->getFullName() ?? 'Un employé';
        $projetName = $tache->getProjet()?->getTitre() ?? 'Projet';
        
        $message = "Bonjour {$rhUser->getFullName()},\n\n";
        $message .= "✅ Une tâche a été complétée :\n\n";
        $message .= "📋 Tâche : {$tache->getTitre()}\n";
        $message .= "👤 Employé : {$employeeName}\n";
        $message .= "📁 Projet : {$projetName}\n";
        $message .= "📅 Date de fin : " . ($tache->getDateFin() ? $tache->getDateFin()->format('d/m/Y') : 'Non définie') . "\n\n";
        $message .= "Cordialement,\n";
        $message .= "RHPro Notifications";

        $email = (new Email())
            ->from($this->systemEmail)
            ->to($rhUser->getEmail())
            ->subject('✅ Tâche complétée : ' . $tache->getTitre())
            ->text($message);

        $this->mailer->send($email);
    }

    public function sendTaskDeadlineApproachingEmail(Tache $tache, int $daysLeft): void
    {
        $employeUser = $tache->getEmploye()?->getUser();
        if (!$employeUser || !$employeUser->getEmail()) return;

        $projetName = $tache->getProjet()?->getTitre() ?? 'Projet';
        
        $message = "Bonjour {$employeUser->getFullName()},\n\n";
        $message .= "⏳ Rappel : Une de vos tâches approche de sa date limite !\n\n";
        $message .= "📋 Tâche : {$tache->getTitre()}\n";
        $message .= "📁 Projet : {$projetName}\n";
        $message .= "📅 Date limite : " . ($tache->getDateFin() ? $tache->getDateFin()->format('d/m/Y') : 'Non définie') . "\n";
        $message .= "⏰ Temps restant : {$daysLeft} jour(s)\n";
        $message .= "📊 Statut : {$tache->getStatut()}\n\n";
        $message .= "Merci de compléter cette tâche avant la deadline.\n\n";
        $message .= "Cordialement,\n";
        $message .= "RHPro Notifications";

        $email = (new Email())
            ->from($this->systemEmail)
            ->to($employeUser->getEmail())
            ->subject('⏳ Échéance proche : ' . $tache->getTitre())
            ->text($message);

        $this->mailer->send($email);
    }

    public function sendTaskOverdueEmail(Tache $tache): void
    {
        $employeUser = $tache->getEmploye()?->getUser();
        if (!$employeUser || !$employeUser->getEmail()) return;

        $projetName = $tache->getProjet()?->getTitre() ?? 'Projet';
        
        $message = "Bonjour {$employeUser->getFullName()},\n\n";
        $message .= "🚨 URGENT : Une de vos tâches est en retard !\n\n";
        $message .= "📋 Tâche : {$tache->getTitre()}\n";
        $message .= "📁 Projet : {$projetName}\n";
        $message .= "📅 Date limite dépassée : " . ($tache->getDateFin() ? $tache->getDateFin()->format('d/m/Y') : 'Non définie') . "\n";
        $message .= "📊 Statut actuel : {$tache->getStatut()}\n\n";
        $message .= "Merci de traiter cette tâche en priorité.\n\n";
        $message .= "Cordialement,\n";
        $message .= "RHPro Notifications";

        $email = (new Email())
            ->from($this->systemEmail)
            ->to($employeUser->getEmail())
            ->subject('🚨 Tâche en retard : ' . $tache->getTitre())
            ->text($message);

        $this->mailer->send($email);
    }
}
