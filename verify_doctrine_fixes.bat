@echo off
echo ==========================================
echo Doctrine Doctor Fixes - Verification
echo ==========================================
echo.

REM Step 1: Clear cache
echo Step 1: Clearing Symfony cache...
php bin/console cache:clear
if %errorlevel% neq 0 (
    echo [ERROR] Failed to clear cache
    exit /b 1
)
echo [OK] Cache cleared successfully
echo.

REM Step 2: Validate schema
echo Step 2: Validating Doctrine schema...
php bin/console doctrine:schema:validate
set SCHEMA_RESULT=%errorlevel%
echo.

REM Step 3: Run Doctrine Doctor
echo Step 3: Running Doctrine Doctor...
php bin/console doctrine:doctor
set DOCTOR_RESULT=%errorlevel%
echo.

REM Step 4: Check mapping info
echo Step 4: Checking Doctrine mapping info...
php bin/console doctrine:mapping:info
echo.

REM Summary
echo ==========================================
echo Verification Summary
echo ==========================================

if %SCHEMA_RESULT% equ 0 (
    echo [OK] Schema validation: PASSED
) else (
    echo [WARNING] Schema validation: WARNINGS ^(check output above^)
)

if %DOCTOR_RESULT% equ 0 (
    echo [OK] Doctrine Doctor: PASSED
) else (
    echo [WARNING] Doctrine Doctor: WARNINGS ^(check output above^)
)

echo.
echo ==========================================
echo Next Steps
echo ==========================================
echo 1. Review any warnings above
echo 2. If database changes needed, run:
echo    mysql -u root -p pidevf ^< fix_doctrine_database_issues.sql
echo 3. If schema updates needed, run:
echo    php bin/console doctrine:schema:update --dump-sql
echo    php bin/console doctrine:schema:update --force
echo.

pause
