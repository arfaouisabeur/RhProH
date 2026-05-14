<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * SERVICE MÉTIER — Détection de bad words et blocage automatique.
 *
 * Contraintes respectées :
 *  - Aucune modification de la base de données
 *  - Aucune API externe
 *  - Compteur de tentatives stocké en SESSION PHP
 *  - Seul user->setStatut('bloque') est utilisé (champ déjà existant)
 */
class BadWordService
{
    private const SESSION_KEY  = 'badword_attempts';
    private const MAX_ATTEMPTS = 3;

    private const BAD_WORDS = [
        // Français
        'merde', 'putain', 'connard', 'connasse', 'salope', 'encule', 'enculé',
        'batard', 'batarde', 'idiot', 'idiote', 'imbecile', 'cretin', 'cretine',
        'abruti', 'abrutie', 'debile', 'fdp', 'ordure', 'salopard',
        'degueulasse', 'pourri', 'ta gueule', 'ferme la', 'fils de pute',
        // Anglais
        'fuck', 'shit', 'asshole', 'bitch', 'bastard', 'crap',
        'damn', 'stupid', 'moron', 'loser', 'wtf', 'piss',
        // Arabe translittéré
        'kalb', 'kahba', 'zebi', 'kess', 'hmar', 'hmara',
        'sharmouta', 'weld kalb', 'bouzbal', 'manyak', 'charmuta',
    ];

    private const LEET_MAP = [
        '0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a',
        '5' => 's', '7' => 't', '@' => 'a', '$' => 's',
        '!' => 'i', '+' => 't', '(' => 'c', '|' => 'l',
    ];

    private const ACCENT_MAP = [
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
        'ÿ' => 'y', 'ñ' => 'n', 'ç' => 'c',
    ];

    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack           $requestStack,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // MÉTHODE PRINCIPALE
    // ─────────────────────────────────────────────────────────────────────────

    public function check(string $commentaire, User $user): array
    {
        // Cas 1 : compte déjà bloqué → refus immédiat
        if ($user->getStatut() === 'bloque') {
            return [
                'blocked'         => true,
                'account_blocked' => true,
                'words_found'     => [],
                'attempts'        => self::MAX_ATTEMPTS,
                'attempts_left'   => 0,
                'message'         => "Vous n'avez pas le droit de faire un commentaire. Vous êtes bloqué à cause de l'utilisation répétée de langage inapproprié.",
            ];
        }

        // Cas 2 : analyser le commentaire
        $normalised = $this->normalise($commentaire);
        $wordsFound = $this->detectBadWords($normalised);

        // Commentaire propre
        if (empty($wordsFound)) {
            return [
                'blocked'         => false,
                'account_blocked' => false,
                'words_found'     => [],
                'attempts'        => $this->getAttempts($user),
                'attempts_left'   => self::MAX_ATTEMPTS - $this->getAttempts($user),
                'message'         => '',
            ];
        }

        // Cas 3 : bad word → incrémenter session
        $newAttempts = $this->incrementAttempts($user);
        $left        = self::MAX_ATTEMPTS - $newAttempts;

        // Cas 4 : seuil atteint → bloquer le compte
        if ($newAttempts >= self::MAX_ATTEMPTS) {
            $user->setStatut('bloque');
            $this->em->flush();
            $this->resetAttempts($user);

            return [
                'blocked'         => true,
                'account_blocked' => true,
                'words_found'     => $wordsFound,
                'attempts'        => $newAttempts,
                'attempts_left'   => 0,
                'message'         => "Vous n'avez pas le droit de faire un commentaire. Vous êtes bloqué à cause de l'utilisation répétée de langage inapproprié.",
            ];
        }

        // Cas 5 : avertissement
        return [
            'blocked'         => true,
            'account_blocked' => false,
            'words_found'     => $wordsFound,
            'attempts'        => $newAttempts,
            'attempts_left'   => $left,
            'message'         => sprintf(
                'Commentaire refusé : langage inapproprié. Avertissement %d/%d — encore %d infraction%s avant blocage.',
                $newAttempts,
                self::MAX_ATTEMPTS,
                $left,
                $left > 1 ? 's' : ''
            ),
        ];
    }

    /**
     * Débloquer un utilisateur — appelé par le RH depuis la page participations.
     */
    public function unblock(User $user): void
    {
        $user->setStatut('actif');
        $this->em->flush();
        $this->resetAttempts($user);
    }

    public function getAttempts(User $user): int
    {
        $all = $this->requestStack->getSession()->get(self::SESSION_KEY, []);
        return $all[$user->getId()] ?? 0;
    }

    public function getMaxAttempts(): int
    {
        return self::MAX_ATTEMPTS;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SESSION
    // ─────────────────────────────────────────────────────────────────────────

    private function incrementAttempts(User $user): int
    {
        $session = $this->requestStack->getSession();
        $all     = $session->get(self::SESSION_KEY, []);
        $all[$user->getId()] = ($all[$user->getId()] ?? 0) + 1;
        $session->set(self::SESSION_KEY, $all);
        return $all[$user->getId()];
    }

    private function resetAttempts(User $user): void
    {
        $session = $this->requestStack->getSession();
        $all     = $session->get(self::SESSION_KEY, []);
        unset($all[$user->getId()]);
        $session->set(self::SESSION_KEY, $all);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DÉTECTION
    // ─────────────────────────────────────────────────────────────────────────

    // ─────────────────────────────────────────────────────────────────────────
    // DÉTECTION AVANCÉE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Transforme un texte en une version "canonique" :
     * minuscule, sans accents, sans caractères spéciaux, leetspeak décodé, compressé.
     */
    private function canonicalize(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = strtr($text, self::ACCENT_MAP);
        $text = strtr($text, self::LEET_MAP);

        // Supprimer tout ce qui n'est pas alphanumérique
        $text = preg_replace('/[^a-z0-0]/u', '', $text) ?? $text;

        // Compresser les répétitions (aaaa -> a)
        $text = preg_replace('/(.)\1+/u', '$1', $text) ?? $text;

        return $text;
    }

    private function detectBadWords(string $commentaire): array
    {
        $found = [];
        $canonicalComment = $this->canonicalize($commentaire);

        foreach (self::BAD_WORDS as $word) {
            $canonicalWord = $this->canonicalize($word);

            // Si le mot est très court (ex: 'fdp'), on cherche une correspondance exacte
            // ou on garde une règle de longueur pour éviter les faux positifs (ex: 'con' dans 'concept')
            if (mb_strlen($canonicalWord) < 3) {
                 // Pour les mots courts, on utilise le texte original normalisé mais avec protection \b
                 $normalised = mb_strtolower($commentaire, 'UTF-8');
                 $w = mb_strtolower($word, 'UTF-8');
                 if (preg_match('/\b' . preg_quote($w, '/') . '\b/u', $normalised)) {
                     $found[] = $word;
                 }
                 continue;
            }

            // Pour les mots longs, on cherche dans le flux canonique (plus agressif)
            if (str_contains($canonicalComment, $canonicalWord)) {
                $found[] = $word;
            }
        }
        return array_unique($found);
    }

    /**
     * Obsolète mais gardée pour compatibilité si nécessaire (non utilisée en interne désormais)
     */
    private function normalise(string $text): string
    {
        return $this->canonicalize($text);
    }
}