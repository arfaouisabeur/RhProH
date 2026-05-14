@echo off
REM Script pour exécuter les tests d'intégration avec la base de données réelle

echo ==========================================
echo   TESTS D'INTEGRATION - BASE REELLE
echo ==========================================
echo.
echo ATTENTION : Ces tests utilisent la base de donnees REELLE !
echo.
echo Assurez-vous d'avoir fait une sauvegarde avant de continuer.
echo.
echo Voulez-vous continuer ? (O/N)
set /p confirm=

if /i "%confirm%" neq "O" (
    echo.
    echo Tests annules.
    pause
    exit /b 0
)

echo.
echo ==========================================
echo   EXECUTION DES TESTS D'INTEGRATION
echo ==========================================
echo.

REM Exécuter les tests d'intégration
php vendor/bin/phpunit tests/Integration/ --testdox

echo.
echo ==========================================
echo   TESTS TERMINES
echo ==========================================
echo.
pause
