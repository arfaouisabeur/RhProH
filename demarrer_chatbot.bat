@echo off
echo ========================================
echo   DEMARRER CHATBOT ML (Flask API)
echo ========================================
echo.
echo Ce script demarre l'API Flask du chatbot sur le port 5001
echo.
echo URL API: http://127.0.0.1:5001/chatbot
echo URL Sante: http://127.0.0.1:5001/sante
echo.
echo IMPORTANT: Gardez cette fenetre ouverte!
echo           Appuyez sur Ctrl+C pour arreter le serveur.
echo.
echo Demarrage du serveur chatbot...
echo.
cd chatbot_ml
python api_chatbot.py
