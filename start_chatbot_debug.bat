@echo off
echo ========================================
echo   CHATBOT DEBUG STARTUP
echo ========================================
echo.

echo 1. Checking Python installation...
python --version
if errorlevel 1 (
    echo ❌ Python not found! Install Python first.
    pause
    exit /b 1
)

echo.
echo 2. Checking if port 5000 is free...
netstat -an | findstr :5000
if not errorlevel 1 (
    echo ⚠️  Port 5000 is already in use!
    echo    Kill the process or use a different port.
    pause
)

echo.
echo 3. Checking chatbot files...
if not exist "chatbot_ml\api_chatbot.py" (
    echo ❌ Chatbot file not found!
    echo    Make sure you're in the right directory.
    pause
    exit /b 1
)

if not exist "chatbot_ml\modele_chatbot_rhpro.pkl" (
    echo ❌ Chatbot model not found!
    echo    The ML model file is missing.
    pause
    exit /b 1
)

echo ✅ All checks passed!
echo.
echo 4. Starting chatbot server...
echo    Keep this window open!
echo    Press Ctrl+C to stop the server.
echo.

cd chatbot_ml
python api_chatbot.py

echo.
echo Chatbot server stopped.
pause