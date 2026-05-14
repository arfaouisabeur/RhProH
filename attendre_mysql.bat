@echo off
echo ========================================
echo En attente du demarrage de MySQL...
echo ========================================
echo.
echo Veuillez demarrer MySQL dans XAMPP Control Panel
echo (Cliquez sur Start a cote de MySQL)
echo.
echo Verification en cours...
echo.

:check
timeout /t 2 /nobreak >nul
netstat -ano | findstr :3306 >nul
if errorlevel 1 (
    echo MySQL pas encore demarre... verification dans 2 secondes
    goto check
)

echo.
echo ========================================
echo ✅ MySQL est maintenant demarre!
echo ========================================
echo.

REM Test de connexion
echo Test de connexion a la base de donnees...
php -r "try { new PDO('mysql:host=127.0.0.1:3306', 'pidevf_app', 'PidevfApp2025Secure'); echo 'Connexion reussie!\n'; } catch(Exception $e) { echo 'Erreur: ' . $e->getMessage() . '\n'; }"
echo.

echo Vous pouvez maintenant executer: demarrer_application.bat
pause
