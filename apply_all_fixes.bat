@echo off
echo ==========================================
echo Doctrine Doctor - Application Complete des Corrections
echo ==========================================
echo.
echo Ce script va:
echo 1. Vider le cache Symfony
echo 2. Appliquer les corrections SQL
echo 3. Mettre a jour le schema Doctrine
echo 4. Verifier les corrections
echo.
echo ATTENTION: Assurez-vous d'avoir une sauvegarde de votre base de donnees!
echo.
pause

echo.
echo ==========================================
echo Etape 1/5: Vidage du cache
echo ==========================================
php bin/console cache:clear
if %errorlevel% neq 0 (
    echo [ERREUR] Echec du vidage du cache
    pause
    exit /b 1
)
echo [OK] Cache vide avec succes
echo.

echo ==========================================
echo Etape 2/5: Application des corrections SQL
echo ==========================================
echo Connexion a MySQL...
echo Entrez le mot de passe MySQL quand demande:
mysql -u root -p pidevf < fix_doctrine_database_issues.sql
if %errorlevel% neq 0 (
    echo [ERREUR] Echec de l'application du script SQL
    echo Verifiez vos identifiants MySQL et que la base 'pidevf' existe
    pause
    exit /b 1
)
echo [OK] Script SQL applique avec succes
echo.

echo ==========================================
echo Etape 3/5: Verification du schema
echo ==========================================
php bin/console doctrine:schema:update --dump-sql
echo.
echo Voulez-vous appliquer ces changements? (O/N)
set /p APPLY_SCHEMA="Reponse: "
if /i "%APPLY_SCHEMA%"=="O" (
    php bin/console doctrine:schema:update --force
    echo [OK] Schema mis a jour
) else (
    echo [INFO] Schema non mis a jour
)
echo.

echo ==========================================
echo Etape 4/5: Validation du schema
echo ==========================================
php bin/console doctrine:schema:validate
echo.

echo ==========================================
echo Etape 5/5: Execution de Doctrine Doctor
echo ==========================================
php bin/console doctrine:doctor
echo.

echo ==========================================
echo TERMINE!
echo ==========================================
echo.
echo Resultats:
echo - Cache: Vide
echo - SQL: Applique
echo - Schema: Verifie
echo.
echo Prochaines etapes:
echo 1. Verifiez les resultats de doctrine:doctor ci-dessus
echo 2. Testez votre application
echo 3. Si des erreurs persistent, consultez TROUBLESHOOTING.md
echo.
echo IMPORTANT - SECURITE:
echo N'oubliez pas de definir un mot de passe pour votre base de donnees!
echo Voir QUICK_FIX_GUIDE.md section "CRITICAL SECURITY FIXES"
echo.

pause
