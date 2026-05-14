<?php

namespace App\EventSubscriber;

use App\Repository\EventParticipationRepository;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\SetDataEvent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CalendarSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EventParticipationRepository $participationRepo,
        private UrlGeneratorInterface        $router,
        private Security                     $security
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            SetDataEvent::class => 'onCalendarSetData',
        ];
    }

    public function onCalendarSetData(SetDataEvent $setDataEvent): void
    {
        $user = $this->security->getUser();
        $employe = $user?->getEmploye();

        if (!$employe) {
            return;
        }

        // Récupérer les participations de l'employé
        $participations = $this->participationRepo->findBy(['employe' => $employe]);

        foreach ($participations as $p) {
            $ev = $p->getEvenement();
            if (!$ev) continue;
            
            if ($p->getStatut() === 'refuse') continue;

            // Conversion des dates (string -> DateTime)
            try {
                $startStr = $ev->getDateDebut();
                $endStr   = $ev->getDateFin() ?: $startStr;
                
                $start = new \DateTime($startStr);
                $end   = new \DateTime($endStr);
                
                if ($start == $end) {
                    $end->modify('+1 day');
                }
            } catch (\Exception $e) {
                continue;
            }

            // Créer l'objet Event du bundle
            $calendarEvent = new Event(
                $ev->getTitre(),
                $start,
                $end
            );

            // Déterminer la couleur
            $color = ($p->getStatut() === 'accepte') ? '#6d28d9' : '#f59e0b';
            
            // Configuration correcte pour le bundle
            $calendarEvent->setAllDay(true);
            $calendarEvent->setOptions([
                'id'              => $ev->getId(),
                'backgroundColor' => $color,
                'borderColor'     => $color,
                'textColor'       => '#ffffff',
                'url'             => $this->router->generate('app_employe_evenement_show', [
                    'id' => $ev->getId(),
                ]),
            ]);

            $setDataEvent->addEvent($calendarEvent);
        }
    }
}
