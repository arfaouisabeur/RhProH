<?php

namespace App\Service;

/**
 * Règles métier des congés + calendrier des jours fériés tunisiens.
 * - Limites légales par type de congé (droit tunisien)
 * - Calcul des jours ouvrables (hors weekends + fériés)
 * - Détection des jours fériés dans une période
 * - Validation complète d'une demande
 *
 * ✅ Jours fériés islamiques récupérés DYNAMIQUEMENT via l'API Aladhan
 *    (https://aladhan.com/islamic-calendar-api) — gratuite, sans clé API.
 */
class CongeRegleService
{
    // ══════════════════════════════════════════════
    //  1. LIMITES LÉGALES PAR TYPE (jours calendaires)
    // ══════════════════════════════════════════════

    /** @var array<string, array{min: int, max: int, document: bool, description: string, couleur: string, icone: string}> */
    private const REGLES = [
        'Congé annuel' => [
            'min' => 1, 'max' => 30, 'document' => false,
            'description' => 'Max 30 jours ouvrables par an (Code du travail tunisien Art. 107)',
            'couleur' => '#0369a1', 'icone' => '🏖',
        ],
        'Congé maladie' => [
            'min' => 1, 'max' => 180, 'document' => true,
            'description' => 'Certificat médical obligatoire. Max 6 mois (180 j) avec justificatif',
            'couleur' => '#059669', 'icone' => '🏥',
        ],
        'Congé maternité' => [
            'min' => 30, 'max' => 112, 'document' => false,
            'description' => 'Entre 30 et 112 jours (16 semaines). Protégé par la loi n°2002-32',
            'couleur' => '#ec4899', 'icone' => '👶',
        ],
        'Congé professionnel' => [
            'min' => 1, 'max' => 10, 'document' => false,
            'description' => 'Max 10 jours par an pour formation ou mission professionnelle',
            'couleur' => '#6d2269', 'icone' => '💼',
        ],
        'Congé sabbatique' => [
            'min' => 30, 'max' => 365, 'document' => false,
            'description' => 'Entre 30 jours et 1 an. Accord préalable de l\'employeur requis',
            'couleur' => '#d97706', 'icone' => '🌍',
        ],
        'Télétravail' => [
            'min' => 1, 'max' => 30, 'document' => false,
            'description' => 'Télétravail exceptionnel, max 30 jours consécutifs',
            'couleur' => '#7c3aed', 'icone' => '💻',
        ],
        'Autre' => [
            'min' => 1, 'max' => 30, 'document' => false,
            'description' => 'Congé exceptionnel, max 30 jours. Justificatif recommandé',
            'couleur' => '#6b7280', 'icone' => '📋',
        ],
    ];

    /**
     * @return array{min: int, max: int, document: bool, description: string, couleur: string, icone: string}|null
     */
    public function getRegle(string $typeConge): ?array
    {
        return self::REGLES[$typeConge] ?? null;
    }

    /**
     * @return array<string, array{min: int, max: int, document: bool, description: string, couleur: string, icone: string}>
     */
    public function getToutesLesRegles(): array
    {
        return self::REGLES;
    }

    // ══════════════════════════════════════════════
    //  2. CACHE DES JOURS FÉRIÉS
    // ══════════════════════════════════════════════

    /** @var array<int, array<string, array{nom: string, type: string, emoji: string}>> */
    private static array $cacheFeries = [];

    // ══════════════════════════════════════════════
    //  3. JOURS FÉRIÉS FIXES TUNISIENS
    // ══════════════════════════════════════════════

    /**
     * Retourne tous les jours fériés tunisiens pour une année donnée.
     * Les fêtes islamiques sont récupérées dynamiquement via l'API Aladhan.
     * En cas d'échec réseau, un fallback statique est utilisé.
     *
     * @return array<string, array{nom: string, type: string, emoji: string}>  clé = 'YYYY-MM-DD'
     */
    public function getJoursFeries(int $annee): array
    {
        if (isset(self::$cacheFeries[$annee])) {
            return self::$cacheFeries[$annee];
        }

        $feries = [];

        // ── Jours fériés FIXES ────────────────────────────────────────────
        $fixes = [
            sprintf('%d-01-01', $annee) => ['nom' => 'Nouvel An',                    'type' => 'fixe', 'emoji' => '🎆'],
            sprintf('%d-03-20', $annee) => ['nom' => "Fête de l'Indépendance",       'type' => 'fixe', 'emoji' => '🇹🇳'],
            sprintf('%d-04-09', $annee) => ['nom' => 'Journée des Martyrs',          'type' => 'fixe', 'emoji' => '🕊'],
            sprintf('%d-05-01', $annee) => ['nom' => 'Fête du Travail',              'type' => 'fixe', 'emoji' => '👷'],
            sprintf('%d-07-25', $annee) => ['nom' => 'Fête de la République',        'type' => 'fixe', 'emoji' => '🏛'],
            sprintf('%d-08-13', $annee) => ['nom' => 'Journée de la Femme',          'type' => 'fixe', 'emoji' => '👩'],
            sprintf('%d-10-15', $annee) => ['nom' => "Fête de l'Évacuation",         'type' => 'fixe', 'emoji' => '🏳'],
        ];

        foreach ($fixes as $date => $info) {
            $feries[$date] = $info;
        }

        // ── Jours fériés ISLAMIQUES (API Aladhan + fallback) ──────────────
        try {
            $islamiques = $this->recupererFeriesIslamiques($annee);
            if (!empty($islamiques)) {
                $feries = array_merge($feries, $islamiques);
            } else {
                throw new \RuntimeException('API retourne vide');
            }
        } catch (\Throwable $e) {
            error_log('[CongeRegleService] ⚠ API indisponible pour ' . $annee . ' — fallback statique. ' . $e->getMessage());
            $feries = array_merge($feries, $this->getFeriesIslamiquesFallback($annee));
        }

        ksort($feries);
        self::$cacheFeries[$annee] = $feries;
        return $feries;
    }

    public function viderCache(): void
    {
        self::$cacheFeries = [];
    }

    // ══════════════════════════════════════════════
    //  4. API ALADHAN — Récupération dynamique
    // ══════════════════════════════════════════════

    /**
     * Récupère les fêtes islamiques d'une année grégorienne via l'API Aladhan.
     *
     * Fêtes récupérées :
     *  - Aïd el-Fitr      : 1 Shawwal  (mois 10)
     *  - Aïd el-Adha      : 10 Dhu al-Hijjah (mois 12)
     *  - Ras el-Am Hijri  : 1 Muharram (mois 1)
     *  - Mouled           : 12 Rabi' al-Awwal (mois 3)
     *  - Début Ramadan    : 1 Ramadan  (mois 9)
     *
     * @return array<string, array{nom: string, type: string, emoji: string}>
     */
    private function recupererFeriesIslamiques(int $annee): array
    {
        $islamiques = [];

        // {mois hégiren, jour hégiren, nom, emoji, ajouter J+1}
        $fetes = [
            [1,  1,  'Ras el-Am el-Hijri (Nouvel An islamique)', '☪',  false],
            [3,  12, 'Mouled (Naissance du Prophète)',            '☪',  false],
            [9,  1,  'Début Ramadan',                             '🌙', false],
            [10, 1,  'Aïd el-Fitr (Aïd Seghir)',                 '🌙', true],
            [12, 10, 'Aïd el-Adha (Aïd Kebir)',                  '🐑', true],
        ];

        // Une année grégorienne couvre ~1.03 années hégiennes
        // Approximation : année grégorienne - 579 ≈ année hégirienne
        $hijriBase = $annee - 579;

        for ($decalage = -1; $decalage <= 1; $decalage++) {
            $ha = $hijriBase + $decalage;
            if ($ha < 1) continue;

            foreach ($fetes as [$moisH, $jourH, $nom, $emoji, $ajouterJ1]) {
                $dateH = sprintf('%02d-%02d-%04d', $jourH, $moisH, $ha);
                $url   = 'https://api.aladhan.com/v1/hToG?date=' . $dateH;

                $dateGreg = null;

                // Essai 1 : API Aladhan v1
                $response = $this->httpGet($url, 6);
                if ($response !== null) {
                    $dateGreg = $this->parseAladhanResponse($response);
                }

                // Essai 2 : endpoint alternatif
                if ($dateGreg === null) {
                    $url2     = 'https://api.aladhan.com/v1/hToG/' . $dateH;
                    $response2 = $this->httpGet($url2, 6);
                    if ($response2 !== null) {
                        $dateGreg = $this->parseAladhanResponse($response2);
                    }
                }

                if ($dateGreg === null) continue;

                // Garder uniquement les dates de l'année demandée
                if ((int) date('Y', strtotime($dateGreg)) !== $annee) continue;

                // Éviter les doublons
                if (isset($islamiques[$dateGreg])) continue;

                $islamiques[$dateGreg] = ['nom' => $nom, 'type' => 'islamique', 'emoji' => $emoji];

                // Ajouter J+1 pour Aïd el-Fitr et Aïd el-Adha
                if ($ajouterJ1) {
                    $lendemain = date('Y-m-d', strtotime($dateGreg . ' +1 day'));
                    if ((int) date('Y', strtotime($lendemain)) === $annee && !isset($islamiques[$lendemain])) {
                        $nomJ1 = str_replace(['(Aïd Seghir)', '(Aïd Kebir)'], '(J+1)', $nom);
                        $islamiques[$lendemain] = ['nom' => $nomJ1, 'type' => 'islamique', 'emoji' => $emoji];
                    }
                }
            }
        }

        if (empty($islamiques)) {
            throw new \RuntimeException('Aucune fête islamique retournée par l\'API pour ' . $annee);
        }

        return $islamiques;
    }

    /**
     * Appel HTTP GET simple avec timeout.
     */
    private function httpGet(string $url, int $timeoutSeconds): ?string
    {
        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => $timeoutSeconds,
                'header'  => "Accept: application/json\r\n",
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ]);

        $result = @file_get_contents($url, false, $context);
        return $result !== false ? $result : null;
    }

    /**
     * Parse la réponse JSON de l'API Aladhan pour extraire la date grégorienne.
     * Exemple : {"code":200,"data":{"gregorian":{"date":"19-03-2026",...}}}
     */
    private function parseAladhanResponse(string $json): ?string
    {
        $data = json_decode($json, true);
        if (!is_array($data)) return null;

        $dateStr = $data['data']['gregorian']['date'] ?? null;
        if (!is_string($dateStr)) return null;

        // Format reçu : "DD-MM-YYYY"
        $parts = explode('-', $dateStr);
        if (count($parts) !== 3) return null;

        return sprintf('%s-%s-%s', $parts[2], $parts[1], $parts[0]); // YYYY-MM-DD
    }

    // ══════════════════════════════════════════════
    //  5. FALLBACK STATIQUE (si API inaccessible)
    // ══════════════════════════════════════════════

    /**
     * Dates islamiques statiques utilisées uniquement si l'API Aladhan est inaccessible.
     *
     * @return array<string, array{nom: string, type: string, emoji: string}>
     */
    private function getFeriesIslamiquesFallback(int $annee): array
    {
        $f = [];

        $data = [
            2024 => [
                '2024-03-11' => ['Début Ramadan',                            '🌙'],
                '2024-04-10' => ['Aïd el-Fitr (Aïd Seghir)',                '🌙'],
                '2024-04-11' => ['Aïd el-Fitr (J+1)',                       '🌙'],
                '2024-06-16' => ['Aïd el-Adha (Aïd Kebir)',                 '🐑'],
                '2024-06-17' => ['Aïd el-Adha (J+1)',                       '🐑'],
                '2024-07-07' => ['Ras el-Am el-Hijri (Nouvel An islamique)', '☪'],
                '2024-09-15' => ['Mouled (Naissance du Prophète)',           '☪'],
            ],
            2025 => [
                '2025-03-01' => ['Début Ramadan',                            '🌙'],
                '2025-03-30' => ['Aïd el-Fitr (Aïd Seghir)',                '🌙'],
                '2025-03-31' => ['Aïd el-Fitr (J+1)',                       '🌙'],
                '2025-06-06' => ['Aïd el-Adha (Aïd Kebir)',                 '🐑'],
                '2025-06-07' => ['Aïd el-Adha (J+1)',                       '🐑'],
                '2025-06-26' => ['Ras el-Am el-Hijri (Nouvel An islamique)', '☪'],
                '2025-09-04' => ['Mouled (Naissance du Prophète)',           '☪'],
            ],
            2026 => [
                '2026-02-17' => ['Début Ramadan',                            '🌙'],
                '2026-03-19' => ['Aïd el-Fitr (Aïd Seghir)',                '🌙'],
                '2026-03-20' => ['Aïd el-Fitr (J+1)',                       '🌙'],
                '2026-05-27' => ['Aïd el-Adha (Aïd Kebir)',                 '🐑'],
                '2026-05-28' => ['Aïd el-Adha (J+1)',                       '🐑'],
                '2026-06-16' => ['Ras el-Am el-Hijri (Nouvel An islamique)', '☪'],
                '2026-08-25' => ['Mouled (Naissance du Prophète)',           '☪'],
            ],
            2027 => [
                '2027-02-06' => ['Début Ramadan',                            '🌙'],
                '2027-03-08' => ['Aïd el-Fitr (Aïd Seghir)',                '🌙'],
                '2027-03-09' => ['Aïd el-Fitr (J+1)',                       '🌙'],
                '2027-05-16' => ['Aïd el-Adha (Aïd Kebir)',                 '🐑'],
                '2027-05-17' => ['Aïd el-Adha (J+1)',                       '🐑'],
                '2027-06-05' => ['Ras el-Am el-Hijri (Nouvel An islamique)', '☪'],
                '2027-08-14' => ['Mouled (Naissance du Prophète)',           '☪'],
            ],
        ];

        foreach ($data[$annee] ?? [] as $date => [$nom, $emoji]) {
            $f[$date] = ['nom' => $nom, 'type' => 'islamique', 'emoji' => $emoji];
        }

        return $f;
    }

    // ══════════════════════════════════════════════
    //  6. JOURS FÉRIÉS DANS UNE PÉRIODE
    // ══════════════════════════════════════════════

    /**
     * Retourne les jours fériés compris dans une période.
     *
     * @return array<string, array{nom: string, type: string, emoji: string}>  clé = 'YYYY-MM-DD'
     */
    public function getJoursFeriesDansPeriode(\DateTimeInterface $debut, \DateTimeInterface $fin): array
    {
        $result = [];
        $annees = [];

        $current = new \DateTime($debut->format('Y-m-d'));
        $finDt   = new \DateTime($fin->format('Y-m-d'));

        while ($current <= $finDt) {
            $annees[(int) $current->format('Y')] = true;
            $current->modify('+1 month');
        }
        $annees[(int) $finDt->format('Y')] = true;

        foreach (array_keys($annees) as $annee) {
            foreach ($this->getJoursFeries($annee) as $date => $info) {
                if ($date >= $debut->format('Y-m-d') && $date <= $fin->format('Y-m-d')) {
                    $result[$date] = $info;
                }
            }
        }

        ksort($result);
        return $result;
    }

    // ══════════════════════════════════════════════
    //  7. CALCUL DES JOURS OUVRABLES
    // ══════════════════════════════════════════════

    public function calculerJoursOuvrables(\DateTimeInterface $debut, \DateTimeInterface $fin): int
    {
        $feriesSet = [];
        $anneeDebut = (int) $debut->format('Y');
        $anneeFin   = (int) $fin->format('Y');

        for ($a = $anneeDebut; $a <= $anneeFin; $a++) {
            foreach (array_keys($this->getJoursFeries($a)) as $date) {
                $feriesSet[$date] = true;
            }
        }

        $count   = 0;
        $current = new \DateTime($debut->format('Y-m-d'));
        $finDt   = new \DateTime($fin->format('Y-m-d'));

        while ($current <= $finDt) {
            $dow  = (int) $current->format('N'); // 1=lundi, 7=dimanche
            $date = $current->format('Y-m-d');
            if ($dow < 6 && !isset($feriesSet[$date])) {
                $count++;
            }
            $current->modify('+1 day');
        }

        return $count;
    }

    public function calculerJoursCalendaires(\DateTimeInterface $debut, \DateTimeInterface $fin): int
    {
        $d1 = new \DateTime($debut->format('Y-m-d'));
        $d2 = new \DateTime($fin->format('Y-m-d'));
        return (int) $d1->diff($d2)->days + 1;
    }

    // ══════════════════════════════════════════════
    //  8. VALIDATION D'UNE DEMANDE
    // ══════════════════════════════════════════════

    /**
     * @return array{
     *   valide: bool,
     *   erreurs: string[],
     *   avertissements: string[],
     *   infos: string[],
     *   joursOuvrables: int,
     *   joursCalendaires: int,
     *   feriesDansPeriode: array<string, array{nom: string, type: string, emoji: string}>
     * }
     */
    public function valider(
        string $typeConge,
        \DateTimeInterface $debut,
        \DateTimeInterface $fin,
        bool $aDocument = false
    ): array {
        $erreurs        = [];
        $avertissements = [];
        $infos          = [];

        $joursCalendaires = $this->calculerJoursCalendaires($debut, $fin);
        $joursOuvrables   = $this->calculerJoursOuvrables($debut, $fin);
        $feriesDansPeriode = $this->getJoursFeriesDansPeriode($debut, $fin);
        $regle            = $this->getRegle($typeConge);

        if ($regle !== null) {
            if ($joursCalendaires < $regle['min']) {
                $erreurs[] = sprintf(
                    'Durée insuffisante : minimum %d jours pour %s (vous avez %d j)',
                    $regle['min'], $typeConge, $joursCalendaires
                );
            }
            if ($joursCalendaires > $regle['max']) {
                $erreurs[] = sprintf(
                    'Durée dépassée : maximum %d jours pour %s (vous avez %d j)',
                    $regle['max'], $typeConge, $joursCalendaires
                );
            }
            if ($regle['max'] > 0
                && $joursCalendaires > $regle['max'] * 0.85
                && $joursCalendaires <= $regle['max']
            ) {
                $avertissements[] = sprintf(
                    'Attention : vous utilisez %.0f%% de votre quota maximum autorisé',
                    $joursCalendaires * 100.0 / $regle['max']
                );
            }
            if ($regle['document'] && !$aDocument) {
                $avertissements[] = 'Un certificat médical est obligatoire pour ' . $typeConge;
            }
        }

        if ($debut < new \DateTime('today')) {
            $avertissements[] = 'La date de début est dans le passé';
        }

        if (!empty($feriesDansPeriode)) {
            $noms = array_map(
                fn($d, $f) => $f['emoji'] . ' ' . $f['nom'] . ' (' . $d . ')',
                array_keys($feriesDansPeriode),
                array_values($feriesDansPeriode)
            );
            $infos[] = 'Jours fériés inclus : ' . implode(', ', $noms);
        }

        $infos[] = sprintf(
            'Jours ouvrables effectifs : %d (sur %d jours calendaires)',
            $joursOuvrables,
            $joursCalendaires
        );

        return [
            'valide'            => empty($erreurs),
            'erreurs'           => $erreurs,
            'avertissements'    => $avertissements,
            'infos'             => $infos,
            'joursOuvrables'    => $joursOuvrables,
            'joursCalendaires'  => $joursCalendaires,
            'feriesDansPeriode' => $feriesDansPeriode,
        ];
    }
}
