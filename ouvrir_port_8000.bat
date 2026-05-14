@echo off
echo ========================================
echo   OUVRIR LE PORT 8000 POUR SYMFONY
echo ========================================
echo.
echo Ce script va autoriser le port 8000 dans le pare-feu Windows
echo pour permettre l'acces depuis votre telephone.
echo.
echo IMPORTANT: Clic droit sur ce fichier et choisir "Executer en tant qu'administrateur"
echo.
pause

echo.
echo Ajout de la regle de pare-feu...
powershell -Command "New-NetFirewallRule -DisplayName 'Symfony Dev Server' -Direction Inbound -LocalPort 8000 -Protocol TCP -Action Allow -Profile Any"

if %errorlevel% equ 0 (
    echo.
    echo ========================================
    echo   SUCCES! Port 8000 ouvert
    echo ========================================
    echo.
    echo Le port 8000 est maintenant accessible depuis votre telephone.
    echo.
    echo Testez avec votre telephone:
    echo   http://192.168.55.208:8000
    echo.
) else (
    echo.
    echo ========================================
    echo   ERREUR!
    echo ========================================
    echo.
    echo Le script doit etre execute EN TANT QU'ADMINISTRATEUR
    echo.
    echo 1. Clic droit sur "ouvrir_port_8000.bat"
    echo 2. Choisir "Executer en tant qu'administrateur"
    echo.
)

echo.
pause
