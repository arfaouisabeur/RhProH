@echo off
echo ========================================
echo   DEMARRER SERVEUR POUR TELEPHONE
echo ========================================
echo.
echo Ce script demarre le serveur PHP sur 0.0.0.0:8000
echo pour permettre l'acces depuis votre telephone.
echo.
echo Votre IP WiFi: 172.20.10.13
echo URL pour telephone: http://172.20.10.13:8000
echo.
echo IMPORTANT: Gardez cette fenetre ouverte!
echo           Appuyez sur Ctrl+C pour arreter le serveur.
echo.
echo Demarrage du serveur...
echo.
php -S 0.0.0.0:8000 -t public public/index.php
