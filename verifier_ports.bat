@echo off
echo ========================================
echo Verification des ports utilises
echo ========================================
echo.
echo Port 80 (Apache):
netstat -ano | findstr :80
echo.
echo Port 443 (Apache SSL):
netstat -ano | findstr :443
echo.
echo Port 3306 (MySQL):
netstat -ano | findstr :3306
echo.
echo ========================================
echo Si vous voyez des lignes, ces ports sont occupes
echo ========================================
pause
