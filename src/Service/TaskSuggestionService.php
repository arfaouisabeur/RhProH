<?php

namespace App\Service;

use App\Entity\Projet;
use App\Entity\Tache;

class TaskSuggestionService
{
    private array $patterns = [
        'web_dev' => [
            ['title' => 'Audit technique SEO', 'desc' => 'Analyse des performances et de l\'indexation.'],
            ['title' => 'Configuration de l\'infrastructure Cloud', 'desc' => 'Mise en place de l\'hébergement et du déploiement continu.'],
            ['title' => 'Développement des API REST', 'desc' => 'Création des endpoints pour la communication inter-services.'],
            ['title' => 'Tests de pénétration (Sécurité)', 'desc' => 'Vérification des vulnérabilités critiques.'],
            ['title' => 'Optimisation des performances Front-end', 'desc' => 'Amélioration du score Core Web Vitals.'],
            ['title' => 'Intégration d\'un système de paiement', 'desc' => 'Mise en place de Stripe ou PayPal.'],
            ['title' => 'Mise en place de la recherche ElasticSearch', 'desc' => 'Indexation des données pour une recherche rapide.'],
            ['title' => 'Refonte de la base de données', 'desc' => 'Migration et optimisation des schémas SQL/NoSQL.'],
        ],
        'ux_design' => [
            ['title' => 'Réalisation de User Personas', 'desc' => 'Définition des cibles et de leurs besoins.'],
            ['title' => 'Création d\'un Design System', 'desc' => 'Standardisation des composants graphiques.'],
            ['title' => 'Tests utilisateurs (A/B testing)', 'desc' => 'Comparaison de variantes pour maximiser la conversion.'],
            ['title' => 'Wireframing Low-Fidelity', 'desc' => 'Structure de base des écrans sans graphismes.'],
            ['title' => 'Prototypage interactif High-Fidelity', 'desc' => 'Simulation réaliste de l\'application finale.'],
        ],
        'marketing_digital' => [
            ['title' => 'Lancement de campagnes Google Ads', 'desc' => 'Configuration du SEA et des mots-clés.'],
            ['title' => 'Stratégie de Content Marketing', 'desc' => 'Planning éditorial et création de blogs.'],
            ['title' => 'Gestion des Newsletters', 'desc' => 'Automatisation et segmentation des envois e-mails.'],
            ['title' => 'Optimisation du tunnel de conversion', 'desc' => 'Analyse du parcours client abandoniste.'],
        ],
        'rh_admin' => [
            ['title' => 'Mise à jour du livret d\'accueil', 'desc' => 'Rédaction des nouvelles procédures internes.'],
            ['title' => 'Audit de conformité légale', 'desc' => 'Vérification des contrats face aux nouvelles lois.'],
            ['title' => 'Organisation de Team Building', 'desc' => 'Renforcement de la cohésion d\'équipe.'],
            ['title' => 'Mise en place du plan de formation', 'desc' => 'Identification des montées en compétences.'],
        ]
    ];

    public function suggestTasks(Projet $projet): array
    {
        $text = strtolower($projet->getTitre() . ' ' . $projet->getDescription());
        
        // Accumuler tous les patterns correspondants
        $pool = [];
        
        if ($this->matches($text, ['web', 'site', 'app', 'code', 'logiciel', 'dev'])) {
            $pool = array_merge($pool, $this->patterns['web_dev']);
        }
        if ($this->matches($text, ['design', 'ux', 'ui', 'graphisme', 'maquette', 'visuel'])) {
            $pool = array_merge($pool, $this->patterns['ux_design']);
        }
        if ($this->matches($text, ['marketing', 'pub', 'vente', 'commerce', 'seo', 'ads', 'reseaux'])) {
            $pool = array_merge($pool, $this->patterns['marketing_digital']);
        }
        if ($this->matches($text, ['rh', 'humain', 'recrutement', 'formation', 'equipe', 'personnel'])) {
            $pool = array_merge($pool, $this->patterns['rh_admin']);
        }

        // Si vide, utiliser un pool générique enrichi
        if (empty($pool)) {
            $pool = [
                ['title' => 'Cadrage stratégique', 'desc' => 'Mise à jour des objectifs du projet.'],
                ['title' => 'Plan de communication interne', 'desc' => 'Informer les parties prenantes.'],
                ['title' => 'Gestion des risques', 'desc' => 'Identification des obstacles potentiels.'],
                ['title' => 'Revue de mi-parcours', 'desc' => 'Ajustement des ressources et des délais.'],
                ['title' => 'Documentation technique', 'desc' => 'Archivage des connaissances produites.'],
            ];
        }

        // Shuffle et sélection aléatoire pour éviter la répétition
        shuffle($pool);
        $selection = array_slice($pool, 0, 5); // On prend 5 tâches aléatoires du pool pertinent

        $suggestedTasks = [];
        $projStart = $projet->getDateDebut() ?? new \DateTime();
        $projEnd = $projet->getDateFin() ?? (clone $projStart)->modify('+1 month');
        $totalDuration = $projEnd->getTimestamp() - $projStart->getTimestamp();
        $durationPerTask = $totalDuration / count($selection);

        foreach ($selection as $index => $def) {
            $tache = new Tache();
            $tache->setTitre($def['title']);
            $tache->setDescription($def['desc']);
            $tache->setStatut('a_faire');
            $tache->setLevel('moyenne');
            $tache->setProjet($projet);
            
            $startOffset = $index * $durationPerTask;
            $endOffset = ($index + 1) * $durationPerTask;
            $tache->setDateDebut((clone $projStart)->setTimestamp($projStart->getTimestamp() + $startOffset));
            $tache->setDateFin((clone $projStart)->setTimestamp($projStart->getTimestamp() + $endOffset));
            
            if ($projet->getResponsableEmploye()) {
                $tache->setEmploye($projet->getResponsableEmploye());
            }
            $suggestedTasks[] = $tache;
        }

        return $suggestedTasks;
    }

    private function matches(string $text, array $keywords): bool
    {
        foreach ($keywords as $word) {
            if (str_contains($text, $word)) return true;
        }
        return false;
    }
}
