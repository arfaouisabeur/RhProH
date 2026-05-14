<?php
namespace App\Service;

use App\Entity\Tache;
use App\Entity\Projet;
use App\Entity\Employe;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ChatbotService
{
    public function __construct(
        private HttpClientInterface    $httpClient,
        private EntityManagerInterface $em
    ) {}

    public function poserQuestion(string $message): array
    {
        $contexte = $this->construireContexte();

        $response = $this->httpClient->request('POST',
            'http://127.0.0.1:5001/chatbot',
            [
                'json'        => ['message' => $message, 'contexte' => (object)$contexte],
                'timeout'     => 5,
                'verify_peer' => false,
                'verify_host' => false,
            ]
        );

        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            throw new \RuntimeException("API Python erreur HTTP $statusCode");
        }

        return $response->toArray();
    }

    private function construireContexte(): array
    {
        $taches  = $this->em->getRepository(Tache::class)->findAll();
        $projets = $this->em->getRepository(Projet::class)->findAll();
        $employes = $this->em->getRepository(Employe::class)->findAll();
        $now     = new \DateTime();

        // ── Statistiques tâches ──
        $enCours = $terminees = $bloquees = $urgentes = $retard = $bassePriorite = $nonAssignees = $aFaire = 0;

        foreach ($taches as $t) {
            $statut = strtolower(trim($t->getStatut() ?? ''));
            $level  = strtolower(trim($t->getLevel() ?? ''));

            if (in_array($statut, ['en_cours', 'en cours', 'encours'])) $enCours++;
            if (in_array($statut, ['terminee', 'terminée', 'termine', 'terminé', 'done', 'completed'])) $terminees++;
            if (in_array($statut, ['bloquee', 'bloquée', 'bloque', 'bloqué', 'blocked'])) $bloquees++;
            if (in_array($statut, ['a_faire', 'à faire', 'a faire', 'todo', 'to do', 'todo'])) $aFaire++;
            if (in_array($level, ['haute', 'urgente', 'high', 'urgent', 'critical'])) $urgentes++;
            if (in_array($level, ['faible', 'basse', 'low', 'bas'])) $bassePriorite++;
            if ($t->getDateFin() !== null && $t->getDateFin() < $now && !in_array($statut, ['terminee', 'terminée', 'termine', 'terminé'])) $retard++;
            if ($t->getEmploye() === null) $nonAssignees++;
        }

        // ── Statistiques projets ──
        $projetsActifs = $projetsTermines = $projetsRetard = $projetsSansTaches = 0;
        $meilleurProjet = 'Aucun';
        $meilleurAvancement = 0;
        $totalAvancement = 0;
        $projetsAvecTaches = 0;
        $detailsProjets = [];

        foreach ($projets as $p) {
            $statut = $p->getStatut();
            if ($statut === 'en_cours') $projetsActifs++;
            if ($statut === 'termine')  $projetsTermines++;
            if ($p->getDateFin() !== null && $p->getDateFin() < $now && $statut !== 'termine') $projetsRetard++;

            $total    = $this->em->getRepository(Tache::class)->count(['projet' => $p]);
            $termine  = $this->em->getRepository(Tache::class)->count(['projet' => $p, 'statut' => 'terminee']);
            $enCoursPrj = $this->em->getRepository(Tache::class)->count(['projet' => $p, 'statut' => 'en_cours'])
                        + $this->em->getRepository(Tache::class)->count(['projet' => $p, 'statut' => 'a_faire']);
            $bloquePrj  = $this->em->getRepository(Tache::class)->count(['projet' => $p, 'statut' => 'bloquee']);

            $avancement = $total > 0 ? (int)round(($termine / $total) * 100) : 0;

            $detailsProjets[] = [
                'nom'        => $p->getTitre(),
                'statut'     => $statut,
                'total'      => $total,
                'terminees'  => $termine,
                'en_cours'   => $enCoursPrj,
                'bloquees'   => $bloquePrj,
                'avancement' => $avancement,
            ];

            if ($total === 0) {
                $projetsSansTaches++;
            } else {
                $totalAvancement += $avancement;
                $projetsAvecTaches++;
                if ($avancement > $meilleurAvancement) {
                    $meilleurAvancement = $avancement;
                    $meilleurProjet     = $p->getTitre();
                }
            }
        }

        $avancementMoyen = $projetsAvecTaches > 0 ? (int)round($totalAvancement / $projetsAvecTaches) : 0;

        // ── Statistiques employés ──
        $statsEmployes = [];
        foreach ($employes as $e) {
            $nom = trim(($e->getUser()?->getPrenom() ?? '') . ' ' . ($e->getUser()?->getNom() ?? ''));
            $statsEmployes[$e->getUserId()] = [
                'nom'      => $nom ?: 'Employé #' . $e->getUserId(),
                'total'    => 0,
                'terminees'=> 0
            ];
        }

        foreach ($taches as $t) {
            $e = $t->getEmploye();
            if ($e && isset($statsEmployes[$e->getUserId()])) {
                $statsEmployes[$e->getUserId()]['total']++;
                $statut = strtolower(trim($t->getStatut() ?? ''));
                if (in_array($statut, ['terminee', 'terminée', 'termine', 'terminé', 'done', 'completed'])) {
                    $statsEmployes[$e->getUserId()]['terminees']++;
                }
            }
        }

        $surchargeNom = 'Aucun'; $surchargeNb = -1;
        $dispoNom = 'Aucun';    $dispoNb = 99999;
        $meilleurEmploye = 'Aucun'; $meilleurTaux = -1;
        $nbEmployes = count($employes);

        foreach ($statsEmployes as $s) {
            $nbActives = $s['total'];
            $nbTermine = $s['terminees'];
            $taux = $nbActives > 0 ? round(($nbTermine / $nbActives) * 100) : 0;
            $nom  = $s['nom'];

            if ($nbActives > $surchargeNb) { $surchargeNb = $nbActives; $surchargeNom = $nom; }
            if ($nbActives <= $dispoNb)    { $dispoNb = $nbActives;     $dispoNom = $nom; }
            if ($taux > $meilleurTaux)     { $meilleurTaux = $taux;     $meilleurEmploye = $nom; }
        }

        if ($surchargeNb === -1) $surchargeNb = 0;
        if ($dispoNb === 99999) $dispoNb = 0;
        if ($meilleurTaux === -1) $meilleurTaux = 0;

        $chargeMoyenne = $nbEmployes > 0 ? round(count($taches) / $nbEmployes, 1) : 0;

        return [
            // Tâches
            'taches_en_cours'      => $enCours,
            'taches_terminees'     => $terminees,
            'taches_bloquees'      => $bloquees,
            'taches_urgentes'      => $urgentes,
            'taches_retard'        => $retard,
            'taches_total'         => count($taches),
            'taches_basse_priorite'=> $bassePriorite,
            'taches_non_assignees' => $nonAssignees,
            // Projets
            'projets_actifs'       => $projetsActifs,
            'projets_termines'     => $projetsTermines,
            'projets_retard'       => $projetsRetard,
            'projets_sans_taches'  => $projetsSansTaches,
            'meilleur_projet'      => $meilleurProjet,
            'meilleur_avancement'  => $meilleurAvancement,
            'avancement_moyen'     => $avancementMoyen,
            'details_projets'      => $detailsProjets,
            // Employés
            'employe_surcharge'    => $surchargeNom,
            'nb_taches_surcharge'  => $surchargeNb,
            'employe_disponible'   => $dispoNom,
            'nb_taches_dispo'      => $dispoNb,
            'meilleur_employe'     => $meilleurEmploye,
            'taux_completion'      => $meilleurTaux,
            'nb_employes'          => $nbEmployes,
            'charge_moyenne'       => $chargeMoyenne,
            // Global
            'progression'          => count($taches) > 0
                ? round(($terminees / count($taches)) * 100) : 0,
        ];
    }
}
