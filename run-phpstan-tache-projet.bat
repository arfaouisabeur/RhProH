@echo off
echo ========================================
echo PHPStan - Analyse Statique
echo Gestion Tache et Projet
echo ========================================
echo.

echo Nettoyage du cache PHPStan...
if exist var\cache\phpstan rmdir /s /q var\cache\phpstan
echo Cache nettoye!
echo.

echo Lancement de l'analyse PHPStan (Niveau 8)...
echo Fichiers analyses:
echo   - src/Entity/Projet.php
echo   - src/Entity/Tache.php
echo   - src/Controller/ProjetController.php
echo   - src/Controller/TacheController.php
echo   - src/Repository/ProjetRepository.php
echo   - src/Repository/TacheRepository.php
echo.

php -d memory_limit=512M vendor/bin/phpstan analyse --configuration=phpstan-tache-projet.neon --error-format=table

echo.
echo ========================================
echo Analyse terminee!
echo ========================================
pause
