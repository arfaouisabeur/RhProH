@echo off
REM Script de sauvegarde de la base de données

echo ==========================================
echo   SAUVEGARDE DE LA BASE DE DONNEES
echo ==========================================
echo.

REM Créer le dossier backups s'il n'existe pas
if not exist "backups" mkdir backups

REM Générer un nom de fichier avec la date et l'heure
set datetime=%date:~-4%%date:~3,2%%date:~0,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set datetime=%datetime: =0%
set filename=backups\pidevf_backup_%datetime%.sql

echo Sauvegarde en cours vers : %filename%
echo.

REM Exécuter mysqldump
"C:\xampp\mysql\bin\mysqldump.exe" -h localhost -P 3307 -u root pidevf > %filename%

if %errorlevel% equ 0 (
    echo.
    echo [OK] Sauvegarde reussie !
    echo Fichier : %filename%
) else (
    echo.
    echo [ERREUR] La sauvegarde a echoue !
)

echo.
pause
