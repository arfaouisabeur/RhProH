@echo off
echo ========================================
echo Demarrage du Serveur de Reconnaissance Faciale
echo ========================================
echo.

cd "Projet final sans user\face_ai_project"

echo Verification de Python...
python --version
if errorlevel 1 (
    echo ERREUR: Python n'est pas installe ou pas dans le PATH
    pause
    exit /b 1
)

echo.
echo Demarrage du serveur Flask sur le port 5001...
echo.
echo IMPORTANT: Gardez cette fenetre ouverte!
echo Pour arreter le serveur, appuyez sur Ctrl+C
echo.

python recognize.py

pause
