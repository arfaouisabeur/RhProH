@echo off
chcp 65001 >nul
echo ========================================
echo DIAGNOSTIC XAMPP ET SYMFONY
echo ========================================
echo.

echo [1] Vérification de PHP...
php -v
if errorlevel 1 (
    echo ❌ PHP n'est pas installé ou pas dans le PATH
) else (
    echo ✅ PHP OK
)
echo.

echo [2] Vérification de Composer...
composer --version
if errorlevel 1 (
    echo ❌ Composer n'est pas installé
) else (
    echo ✅ Composer OK
)
echo.

echo [3] Vérification des ports...
echo.
echo Port 80 (Apache):
netstat -ano | findstr :80
if errorlevel 1 (
    echo ✅ Port 80 libre
) else (
    echo ⚠️ Port 80 occupé
)
echo.

echo Port 3306 (MySQL):
netstat -ano | findstr :3306
if errorlevel 1 (
    echo ❌ Port 3306 libre - MySQL n'est pas démarré!
) else (
    echo ✅ Port 3306 occupé - MySQL semble démarré
)
echo.

echo [4] Test de connexion MySQL...
php -r "try { new PDO('mysql:host=127.0.0.1:3306', 'pidevf_app', 'PidevfApp2025Secure'); echo '✅ Connexion MySQL OK\n'; } catch(Exception $e) { echo '❌ Erreur MySQL: ' . $e->getMessage() . '\n'; }"
echo.

echo [5] Vérification du fichier .env...
if exist .env (
    echo ✅ Fichier .env trouvé
    findstr "DATABASE_URL" .env
) else (
    echo ❌ Fichier .env manquant
)
echo.

echo [6] Vérification du dossier vendor...
if exist vendor (
    echo ✅ Dépendances installées
) else (
    echo ❌ Dépendances manquantes - Exécutez: composer install
)
echo.

echo ========================================
echo RÉSUMÉ
echo ========================================
echo.
echo Si MySQL n'est pas démarré:
echo   1. Ouvrez XAMPP Control Panel
echo   2. Cliquez sur "Start" pour Apache
echo   3. Cliquez sur "Start" pour MySQL
echo.
echo Si les ports sont occupés:
echo   - Exécutez: verifier_ports.bat
echo   - Arrêtez les applications qui utilisent ces ports
echo.
echo Pour démarrer l'application:
echo   - Exécutez: demarrer_application.bat
echo.
echo ========================================
pause
