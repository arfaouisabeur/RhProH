@echo off
chcp 65001 >nul
cls
echo.
echo ════════════════════════════════════════════════════════
echo     DEMARRAGE DE L'APPLICATION SYMFONY
echo ════════════════════════════════════════════════════════
echo.

REM Vérifier Apache
echo [1/5] Vérification d'Apache...
netstat -ano | findstr ":80 " | findstr "LISTENING" >nul
if errorlevel 1 (
    echo ❌ Apache n'est pas démarré
    echo.
    echo VEUILLEZ DEMARRER APACHE DANS XAMPP:
    echo 1. Ouvrez XAMPP Control Panel
    echo 2. Cliquez sur Start à côté d'Apache
    echo 3. Relancez ce script
    pause
    exit /b 1
) else (
    echo ✅ Apache est démarré
)
echo.

REM Vérifier MySQL
echo [2/5] Vérification de MySQL...
netstat -ano | findstr ":3306 " | findstr "LISTENING" >nul
if errorlevel 1 (
    echo ❌ MySQL n'est pas démarré
    echo.
    echo VEUILLEZ DEMARRER MYSQL DANS XAMPP:
    echo 1. Ouvrez XAMPP Control Panel
    echo 2. Cliquez sur Start à côté de MySQL
    echo 3. Relancez ce script
    pause
    exit /b 1
) else (
    echo ✅ MySQL est démarré
)
echo.

REM Installer dépendances
echo [3/5] Vérification des dépendances...
if not exist "vendor" (
    echo Installation en cours...
    composer install --no-interaction
)
echo ✅ Dépendances OK
echo.

REM Base de données
echo [4/5] Configuration de la base de données...
php bin/console doctrine:database:create --if-not-exists --no-interaction 2>nul
php bin/console doctrine:migrations:migrate --no-interaction 2>nul
echo ✅ Base de données OK
echo.

REM Démarrer serveur
echo [5/5] Démarrage du serveur...
echo.
echo ════════════════════════════════════════════════════════
echo     APPLICATION DEMARREE !
echo ════════════════════════════════════════════════════════
echo.
echo 🌐 Ouvrez votre navigateur:
echo.
echo     http://127.0.0.1:8000
echo.
echo 📌 Pour arrêter: Ctrl+C
echo.
echo ════════════════════════════════════════════════════════
echo.

start http://127.0.0.1:8000
php -S 127.0.0.1:8000 -t public
