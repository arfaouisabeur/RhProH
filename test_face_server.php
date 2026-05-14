<?php
// Test de connexion au serveur de reconnaissance faciale

$ch = curl_init('http://localhost:5001/recognize');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['image' => 'test']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "=== Test de Connexion au Serveur de Reconnaissance Faciale ===\n\n";

if ($error) {
    echo "❌ ERREUR: Le serveur Flask n'est PAS accessible\n";
    echo "Détails: $error\n\n";
    echo "SOLUTION:\n";
    echo "1. Ouvrir un terminal\n";
    echo "2. cd \"Projet final sans user/face_ai_project\"\n";
    echo "3. python recognize.py\n";
} else {
    echo "✅ Le serveur Flask est accessible!\n";
    echo "Code HTTP: $httpCode\n";
    echo "Réponse: $response\n";
}
