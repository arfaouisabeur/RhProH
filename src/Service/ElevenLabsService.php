<?php

namespace App\Service;

/**
 * ElevenLabsService — Text-to-Speech via l'API ElevenLabs.
 * Port PHP du service Java fourni.
 *
 * Clé gratuite : https://elevenlabs.io (10 000 caractères/mois offerts)
 * Voice ID "Rachel" = 21m00Tcm4TlvDq8ikWAM (voix féminine professionnelle)
 */
class ElevenLabsService
{
    private const VOICE_ID = '21m00Tcm4TlvDq8ikWAM'; // Rachel — voix pro
    private const API_URL  = 'https://api.elevenlabs.io/v1/text-to-speech/' . self::VOICE_ID;
    private const MODEL_ID = 'eleven_multilingual_v2';

    public function __construct(private readonly string $apiKey)
    {
    }

    // ══════════════════════════════════════════════════════════════
    //  Construire le texte du résumé RH (équivalent construireResume Java)
    // ══════════════════════════════════════════════════════════════

    /**
     * Construit un texte naturel en français pour le rapport vocal RH.
     *
     * @param int    $total       Nombre total de demandes
     * @param float  $tauxRes     Taux de résolution en %
     * @param int    $slaDepasses Nombre de demandes dépassant le SLA
     * @param int    $nbUrgentes  Nombre de demandes urgentes
     * @param array  $statuts     ['En attente' => N, 'Accepté' => N, 'Refusé' => N]
     * @param array  $topTypes    [['nom' => '...', 'count' => N], ...]
     * @param int    $totalLikes  Total likes sur les types de service
     * @param int    $totalDislikes Total dislikes sur les types de service
     */
    public function construireResume(
        int   $total,
        float $tauxRes,
        int   $slaDepasses,
        int   $nbUrgentes,
        array $statuts,
        array $topTypes = [],
        int   $totalLikes = 0,
        int   $totalDislikes = 0
    ): string {
        $moisFr  = $this->traduireMois((new \DateTime())->format('F Y'));
        $jour    = (new \DateTime())->format('d');
        $heure   = (int)(new \DateTime())->format('H');

        // Salutation selon l'heure
        if ($heure < 12) {
            $salut = 'Bonjour';
        } elseif ($heure < 18) {
            $salut = 'Bon après-midi';
        } else {
            $salut = 'Bonsoir';
        }

        $accepted  = $statuts['Accepté']    ?? 0;
        $rejected  = $statuts['Refusé']     ?? 0;
        $pending   = $statuts['En attente'] ?? 0;
        $resolved  = $accepted + $rejected;
        $tx        = round($tauxRes);

        // ── Introduction ────────────────────────────────────────────────────
        $t  = "{$salut}. Vous écoutez le rapport analytique des demandes de service RH, ";
        $t .= "généré automatiquement le {$jour} {$moisFr}. ";
        $t .= "Ce rapport couvre l'ensemble de l'activité enregistrée dans le système. ";

        // ── Volume global ────────────────────────────────────────────────────
        $t .= "Commençons par le volume global. ";
        if ($total === 0) {
            $t .= "Aucune demande n'a été enregistrée pour la période analysée. ";
        } elseif ($total === 1) {
            $t .= "Une seule demande a été enregistrée au total. ";
        } else {
            $t .= "Au total, {$total} demandes ont été enregistrées dans le système. ";
        }

        // ── Répartition par statut ───────────────────────────────────────────
        $t .= "En ce qui concerne la répartition par statut : ";
        $t .= "{$accepted} demande" . ($accepted > 1 ? 's ont été acceptées' : ' a été acceptée') . ", ";
        $t .= "{$rejected} demande" . ($rejected > 1 ? 's ont été refusées' : ' a été refusée') . ", ";
        $t .= "et {$pending} demande" . ($pending > 1 ? 's sont' : ' est') . " encore en attente de traitement. ";

        // ── Taux de résolution ───────────────────────────────────────────────
        $t .= "Passons maintenant au taux de résolution. ";
        if ($total > 0) {
            $t .= "Sur {$total} demandes, {$resolved} ont été traitées, ";
            $t .= "ce qui représente un taux de résolution de {$tx} pourcent. ";
            if ($tx >= 80) {
                $t .= "C'est un excellent résultat qui témoigne d'une gestion efficace des demandes. ";
            } elseif ($tx >= 50) {
                $t .= "Ce résultat est satisfaisant, mais des efforts supplémentaires permettraient d'améliorer ce taux. ";
            } elseif ($tx > 0) {
                $t .= "Ce taux est insuffisant. Il est recommandé de prioriser le traitement des demandes en attente. ";
            } else {
                $t .= "Aucune demande n'a encore été traitée. Une action immédiate est recommandée. ";
            }
        }

        // ── SLA ──────────────────────────────────────────────────────────────
        $t .= "Concernant le respect des délais SLA : ";
        if ($slaDepasses === 0) {
            $t .= "excellente nouvelle, aucune demande ne dépasse le délai réglementaire de sept jours. ";
            $t .= "L'équipe RH respecte parfaitement les engagements de service. ";
        } elseif ($slaDepasses === 1) {
            $t .= "attention, une demande dépasse le délai SLA de sept jours. ";
            $t .= "Une intervention rapide est nécessaire pour régulariser cette situation. ";
        } else {
            $t .= "alerte importante : {$slaDepasses} demandes dépassent le délai SLA de sept jours. ";
            $t .= "Ces demandes nécessitent une prise en charge prioritaire et immédiate. ";
            $t .= "Un retard prolongé peut impacter la satisfaction des employés et la réputation du service RH. ";
        }

        // ── Demandes urgentes ────────────────────────────────────────────────
        if ($nbUrgentes > 0) {
            $t .= "Par ailleurs, {$nbUrgentes} demande" . ($nbUrgentes > 1 ? 's urgentes sont' : ' urgente est');
            $t .= " en attente de traitement. Ces demandes doivent être traitées en priorité absolue. ";
        }

        // ── Top types de service ─────────────────────────────────────────────
        if (!empty($topTypes)) {
            $t .= "Analysons maintenant les types de service les plus sollicités. ";
            $nbTypes = count($topTypes);
            if ($nbTypes === 1) {
                $nom   = $topTypes[0]['nom'];
                $count = $topTypes[0]['count'];
                $t .= "Le seul type de service enregistré est \"{$nom}\" avec {$count} demande" . ($count > 1 ? 's' : '') . ". ";
            } else {
                $t .= "Le type de service le plus demandé est \"{$topTypes[0]['nom']}\" ";
                $t .= "avec {$topTypes[0]['count']} demande" . ($topTypes[0]['count'] > 1 ? 's' : '') . ". ";
                if ($nbTypes >= 2) {
                    $t .= "En deuxième position, on trouve \"{$topTypes[1]['nom']}\" ";
                    $t .= "avec {$topTypes[1]['count']} demande" . ($topTypes[1]['count'] > 1 ? 's' : '') . ". ";
                }
                if ($nbTypes >= 3) {
                    $t .= "Et en troisième position, \"{$topTypes[2]['nom']}\" ";
                    $t .= "avec {$topTypes[2]['count']} demande" . ($topTypes[2]['count'] > 1 ? 's' : '') . ". ";
                }
                $t .= "Ces données permettent d'identifier les besoins récurrents des employés ";
                $t .= "et d'anticiper les ressources nécessaires pour y répondre efficacement. ";
            }
        }

        // ── Satisfaction ─────────────────────────────────────────────────────
        $totalReactions = $totalLikes + $totalDislikes;
        if ($totalReactions > 0) {
            $pctSat = round($totalLikes / $totalReactions * 100);
            $t .= "Concernant la satisfaction des employés vis-à-vis des types de service proposés : ";
            $t .= "le système a enregistré {$totalReactions} réaction" . ($totalReactions > 1 ? 's' : '') . " au total, ";
            $t .= "dont {$totalLikes} positive" . ($totalLikes > 1 ? 's' : '') . " et {$totalDislikes} négative" . ($totalDislikes > 1 ? 's' : '') . ". ";
            $t .= "Cela représente un taux de satisfaction de {$pctSat} pourcent. ";
            if ($pctSat >= 75) {
                $t .= "Les employés sont globalement satisfaits des services proposés. ";
            } elseif ($pctSat >= 50) {
                $t .= "La satisfaction est mitigée. Des améliorations sont à envisager sur certains services. ";
            } else {
                $t .= "Le taux de satisfaction est préoccupant. Une révision des services proposés est fortement recommandée. ";
            }
        } else {
            $t .= "Aucune réaction n'a encore été enregistrée sur les types de service. ";
            $t .= "Encouragez les employés à donner leur avis pour améliorer la qualité des services. ";
        }

        // ── Recommandations ──────────────────────────────────────────────────
        $t .= "Pour conclure, voici les recommandations prioritaires pour l'équipe RH. ";
        $recommandations = [];
        if ($slaDepasses > 0) {
            $recommandations[] = "traiter en urgence les {$slaDepasses} demande" . ($slaDepasses > 1 ? 's' : '') . " en retard SLA";
        }
        if ($pending > 0) {
            $recommandations[] = "réduire le nombre de demandes en attente, actuellement au nombre de {$pending}";
        }
        if ($tx < 50 && $total > 0) {
            $recommandations[] = "améliorer le taux de résolution qui est actuellement insuffisant";
        }
        if ($totalReactions > 0 && ($totalLikes / $totalReactions * 100) < 50) {
            $recommandations[] = "revoir les services les moins appréciés pour améliorer la satisfaction";
        }

        if (!empty($recommandations)) {
            $t .= "Premièrement, " . implode(". Deuxièmement, ", $recommandations) . ". ";
        } else {
            $t .= "La situation est globalement satisfaisante. Continuez sur cette lancée. ";
        }

        $t .= "Ce rapport a été généré automatiquement par le système RH Pro. ";
        $t .= "Merci de votre attention. Bonne journée à toute l'équipe.";

        return $t;
    }

    // ══════════════════════════════════════════════════════════════
    //  Appeler ElevenLabs et retourner le MP3 en bytes
    // ══════════════════════════════════════════════════════════════

    /**
     * @return string Contenu binaire du fichier MP3
     * @throws \RuntimeException Si l'API retourne une erreur
     */
    public function genererAudio(string $texte): string
    {
        $body = json_encode([
            'text'           => $texte,
            'model_id'       => self::MODEL_ID,
            'voice_settings' => [
                'stability'         => 0.5,
                'similarity_boost'  => 0.8,
                'style'             => 0.2,
                'use_speaker_boost' => true,
            ],
        ]);

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_HTTPHEADER     => [
                'xi-api-key: ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: audio/mpeg',
            ],
            // Fix SSL Windows — cacert.pem à la racine du projet
            CURLOPT_CAINFO         => __DIR__ . '/../../cacert.pem',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $raw   = curl_exec($ch);
        $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $raw === false) {
            throw new \RuntimeException('ElevenLabs cURL error: ' . $error);
        }

        if ($code !== 200) {
            error_log('[ElevenLabs] HTTP ' . $code . ': ' . substr((string)$raw, 0, 500));
            throw new \RuntimeException('ElevenLabs erreur HTTP ' . $code);
        }

        return (string) $raw;
    }

    // ══════════════════════════════════════════════════════════════
    //  Helper — traduction mois anglais → français
    // ══════════════════════════════════════════════════════════════

    private function traduireMois(string $moisEn): string
    {
        $map = [
            'January'   => 'janvier',   'February'  => 'février',
            'March'     => 'mars',      'April'     => 'avril',
            'May'       => 'mai',       'June'      => 'juin',
            'July'      => 'juillet',   'August'    => 'août',
            'September' => 'septembre', 'October'   => 'octobre',
            'November'  => 'novembre',  'December'  => 'décembre',
        ];

        foreach ($map as $en => $fr) {
            $moisEn = str_replace($en, $fr, $moisEn);
        }

        return $moisEn;
    }
}
