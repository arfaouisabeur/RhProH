@echo off
REM Script pour exécuter les tests unitaires et l'analyse statique (Windows)

echo ==========================================
echo   EXECUTION DES TESTS UNITAIRES
echo ==========================================
echo.

REM Exécuter tous les tests
echo -^> Execution de tous les tests...
php vendor/bin/phpunit

echo.
echo ==========================================
echo   TESTS DES ENTITES
echo ==========================================
echo.

REM Tests des entités
echo -^> Tests de l'entite Candidature...
php vendor/bin/phpunit tests/Entity/CandidatureTest.php

echo.
echo -^> Tests de l'entite OffreEmploi...
php vendor/bin/phpunit tests/Entity/OffreEmploiTest.php

echo.
echo ==========================================
echo   TESTS DES CONTROLEURS
echo ==========================================
echo.

REM Tests des contrôleurs
echo -^> Tests du controleur Candidature...
php vendor/bin/phpunit tests/Controller/CandidatureControllerTest.php

echo.
echo -^> Tests du controleur OffreEmploi...
php vendor/bin/phpunit tests/Controller/OffreEmploiControllerTest.php

echo.
echo ==========================================
echo   ANALYSE STATIQUE AVEC PHPSTAN
echo ==========================================
echo.

REM Exécuter PHPStan
echo -^> Analyse statique du code...
php vendor/bin/phpstan analyse

echo.
echo ==========================================
echo   RESUME
echo ==========================================
echo.
echo [OK] Tests unitaires termines
echo [OK] Analyse statique terminee
echo.
pause
