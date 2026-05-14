#!/bin/bash

# Script pour exécuter les tests unitaires et l'analyse statique

echo "=========================================="
echo "  EXÉCUTION DES TESTS UNITAIRES"
echo "=========================================="
echo ""

# Exécuter tous les tests
echo "→ Exécution de tous les tests..."
php vendor/bin/phpunit

echo ""
echo "=========================================="
echo "  TESTS DES ENTITÉS"
echo "=========================================="
echo ""

# Tests des entités
echo "→ Tests de l'entité Candidature..."
php vendor/bin/phpunit tests/Entity/CandidatureTest.php

echo ""
echo "→ Tests de l'entité OffreEmploi..."
php vendor/bin/phpunit tests/Entity/OffreEmploiTest.php

echo ""
echo "=========================================="
echo "  TESTS DES CONTRÔLEURS"
echo "=========================================="
echo ""

# Tests des contrôleurs
echo "→ Tests du contrôleur Candidature..."
php vendor/bin/phpunit tests/Controller/CandidatureControllerTest.php

echo ""
echo "→ Tests du contrôleur OffreEmploi..."
php vendor/bin/phpunit tests/Controller/OffreEmploiControllerTest.php

echo ""
echo "=========================================="
echo "  ANALYSE STATIQUE AVEC PHPSTAN"
echo "=========================================="
echo ""

# Exécuter PHPStan
echo "→ Analyse statique du code..."
php vendor/bin/phpstan analyse

echo ""
echo "=========================================="
echo "  RÉSUMÉ"
echo "=========================================="
echo ""
echo "✓ Tests unitaires terminés"
echo "✓ Analyse statique terminée"
echo ""
