@echo off
echo Demarrage de l'application...
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction
echo.
echo Application disponible sur: http://127.0.0.1:8000
echo.
start http://127.0.0.1:8000
php -S 127.0.0.1:8000 -t public
