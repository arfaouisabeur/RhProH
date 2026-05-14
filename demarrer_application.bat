@echo off
echo ========================================
echo Demarrage de l'application Symfony
echo ========================================
echo.

REM Vérifier que les services XAMPP sont démarrés
echo [1/5] Verification des services...
netstat -ano | findstr :3306 >nul
if errorlevel 1 (
    echo ERREUR: MySQL n'est pas demarre dans XAMPP!
    echo Veuillez demarrer MySQL dans le panneau de controle XAMPP
    pause
    exit /b 1
)

netstat -ano | findstr :80 >nul
if errorlevel 1 (
    echo ERREUR: Apache n'est pas demarre dans XAMPP!
    echo Veuillez demarrer Apache dans le panneau de controle XAMPP
    pause
    exit /b 1
)

echo Services XAMPP OK!
echo.

REM Installer les dépendances si nécessaire
echo [2/5] Verification des dependances...
if not exist vendor (
    echo Installation des dependances Composer...
    composer install
)
echo.

REM Créer la base de données si elle n'existe pas
echo [3/5] Verification de la base de donnees...
php bin/console doctrine:database:create --if-not-exists
echo.

REM Exécuter les migrations
echo [4/5] Execution des migrations...
php bin/console doctrine:migrations:migrate --no-interaction
echo.

REM Démarrer le serveur Symfony
echo [5/5] Demarrage du serveur Symfony...
echo.
echo ========================================
echo Application disponible sur: http://127.0.0.1:8000
echo Appuyez sur Ctrl+C pour arreter
echo ========================================
echo.
php -S 127.0.0.1:8000 -t public
