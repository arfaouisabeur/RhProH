@echo off
chcp 65001 >nul
color 0A
echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║     DEMARRAGE AUTOMATIQUE DE L'APPLICATION SYMFONY         ║
echo ╚════════════════════════════════════════════════════════════╝
echo.

REM ============================================================
REM ÉTAPE 1 : Vérifier que XAMPP est installé
REM ============================================================
echo [1/6] Vérification de XAMPP...

if exist "C:\xampp\xampp-control.exe" (
    echo ✅ XAMPP trouvé
    set XAMPP_PATH=C:\xampp
) else if exist "C:\xampp\xampp_control.exe" (
    echo ✅ XAMPP trouvé
    set XAMPP_PATH=C:\xampp
) else (
    echo ❌ XAMPP n'est pas installé dans C:\xampp
    echo.
    echo Veuillez installer XAMPP depuis: https://www.apachefriends.org
    pause
    exit /b 1
)
echo.

REM ============================================================
REM ÉTAPE 2 : Démarrer Apache
REM ============================================================
echo [2/6] Démarrage d'Apache...

netstat -ano | findstr ":80 " | findstr "LISTENING" >nul
if errorlevel 1 (
    echo Apache n'est pas démarré. Démarrage en cours...
    start "" "%XAMPP_PATH%\apache_start.bat"
    timeout /t 3 /nobreak >nul
    echo ✅ Apache démarré
) else (
    echo ✅ Apache est déjà démarré
)
echo.

REM ============================================================
REM ÉTAPE 3 : Démarrer MySQL
REM ============================================================
echo [3/6] Démarrage de MySQL...

netstat -ano | findstr ":3306 " | findstr "LISTENING" >nul
if errorlevel 1 (
    echo MySQL n'est pas démarré. Démarrage en cours...
    start "" "%XAMPP_PATH%\mysql_start.bat"
    
    echo Attente du démarrage de MySQL...
    set /a count=0
    :wait_mysql
    timeout /t 2 /nobreak >nul
    netstat -ano | findstr ":3306 " | findstr "LISTENING" >nul
    if errorlevel 1 (
        set /a count+=1
        if %count% LSS 15 (
            echo Tentative %count%/15...
            goto wait_mysql
        ) else (
            echo.
            echo ❌ MySQL n'a pas démarré après 30 secondes
            echo.
            echo SOLUTION MANUELLE:
            echo 1. Ouvrez XAMPP Control Panel
            echo 2. Cliquez sur "Start" à côté de MySQL
            echo 3. Relancez ce script
            echo.
            pause
            exit /b 1
        )
    )
    echo ✅ MySQL démarré
) else (
    echo ✅ MySQL est déjà démarré
)
echo.

REM ============================================================
REM ÉTAPE 4 : Installer les dépendances
REM ============================================================
echo [4/6] Vérification des dépendances...

if not exist "vendor" (
    echo Installation des dépendances Composer...
    composer install --no-interaction
    if errorlevel 1 (
        echo ❌ Erreur lors de l'installation des dépendances
        pause
        exit /b 1
    )
    echo ✅ Dépendances installées
) else (
    echo ✅ Dépendances déjà installées
)
echo.

REM ============================================================
REM ÉTAPE 5 : Configurer la base de données
REM ============================================================
echo [5/6] Configuration de la base de données...

REM Créer la base de données
php bin/console doctrine:database:create --if-not-exists --no-interaction 2>nul
if errorlevel 1 (
    echo ⚠️ La base de données existe déjà ou erreur de connexion
) else (
    echo ✅ Base de données créée
)

REM Exécuter les migrations
echo Exécution des migrations...
php bin/console doctrine:migrations:migrate --no-interaction
if errorlevel 1 (
    echo ⚠️ Erreur lors des migrations (peut-être déjà appliquées)
) else (
    echo ✅ Migrations appliquées
)
echo.

REM ============================================================
REM ÉTAPE 6 : Démarrer le serveur Symfony
REM ============================================================
echo [6/6] Démarrage du serveur Symfony...
echo.
echo ╔════════════════════════════════════════════════════════════╗
echo ║                    APPLICATION PRÊTE                       ║
echo ╠════════════════════════════════════════════════════════════╣
echo ║                                                            ║
echo ║  🌐 Ouvrez votre navigateur et allez sur:                 ║
echo ║                                                            ║
echo ║     http://127.0.0.1:8000                                 ║
echo ║                                                            ║
echo ║  📌 Pour arrêter le serveur: Appuyez sur Ctrl+C           ║
echo ║                                                            ║
echo ╚════════════════════════════════════════════════════════════╝
echo.
echo Démarrage dans 3 secondes...
timeout /t 3 /nobreak >nul

REM Ouvrir automatiquement le navigateur
start http://127.0.0.1:8000

REM Démarrer le serveur
php -S 127.0.0.1:8000 -t public
