@echo off
chcp 65001 >nul
cls
color 0A
echo.
echo ════════════════════════════════════════════════════════
echo          LANCEMENT DE L'APPLICATION
echo ════════════════════════════════════════════════════════
echo.

echo [1/4] Vérification de MySQL (port 3307)...
netstat -ano | findstr ":3307" | findstr "LISTENING" >nul
if errorlevel 1 (
    echo ❌ MySQL n'est pas démarré sur le port 3307
    echo Veuillez démarrer MySQL dans XAMPP Control Panel
    pause
    exit /b 1
)
echo ✅ MySQL est démarré
echo.

echo [2/4] Création de la base de données...
php bin/console doctrine:database:create --if-not-exists --no-interaction
echo ✅ Base de données prête
echo.

echo [3/4] Application des migrations...
php bin/console doctrine:migrations:migrate --no-interaction
echo ✅ Migrations appliquées
echo.

echo [4/4] Démarrage du serveur Symfony...
echo.
echo ════════════════════════════════════════════════════════
echo          🎉 APPLICATION DEMARREE !
echo ════════════════════════════════════════════════════════
echo.
echo 🌐 Votre application est accessible sur:
echo.
echo          http://127.0.0.1:8000
echo.
echo 📌 Pour arrêter le serveur: Appuyez sur Ctrl+C
echo.
echo ════════════════════════════════════════════════════════
echo.

REM Ouvrir le navigateur automatiquement
start http://127.0.0.1:8000

REM Démarrer le serveur PHP
php -S 127.0.0.1:8000 -t public
