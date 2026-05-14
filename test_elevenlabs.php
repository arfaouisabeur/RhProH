<?php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

$apiKey = $_ENV['ELEVENLABS_API_KEY'] ?? '';

echo "=== TEST ELEVENLABS API ===" . PHP_EOL . PHP_EOL;
echo "API Key: " . substr($apiKey, 0, 10) . "..." . PHP_EOL;
echo PHP_EOL;

// Test 1 : Vérifier la clé API
echo "📋 Test 1 : Vérification de la clé API..." . PHP_EOL;

$ch = curl_init('https://api.elevenlabs.io/v1/user');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'xi-api-key: ' . $apiKey,
    ],
    CURLOPT_CAINFO         => __DIR__ . '/cacert.pem',
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_TIMEOUT        => 10,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Erreur cURL: " . $error . PHP_EOL;
    exit(1);
}

echo "HTTP Code: " . $httpCode . PHP_EOL;

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "✅ Clé API valide !" . PHP_EOL;
    echo "Subscription: " . ($data['subscription']['tier'] ?? 'N/A') . PHP_EOL;
    
    if (isset($data['subscription']['character_count'], $data['subscription']['character_limit'])) {
        $used = $data['subscription']['character_count'];
        $limit = $data['subscription']['character_limit'];
        $remaining = $limit - $used;
        echo "Caractères utilisés: " . $used . " / " . $limit . PHP_EOL;
        echo "Caractères restants: " . $remaining . PHP_EOL;
        
        if ($remaining <= 0) {
            echo "⚠️  QUOTA DÉPASSÉ ! Vous avez atteint la limite mensuelle." . PHP_EOL;
        }
    }
} elseif ($httpCode === 401) {
    echo "❌ Clé API invalide ou expirée !" . PHP_EOL;
    echo "Réponse: " . $response . PHP_EOL;
    exit(1);
} else {
    echo "❌ Erreur HTTP " . $httpCode . PHP_EOL;
    echo "Réponse: " . substr($response, 0, 500) . PHP_EOL;
    exit(1);
}

echo PHP_EOL;

// Test 2 : Générer un court audio
echo "📋 Test 2 : Génération d'un court audio..." . PHP_EOL;

$voiceId = '21m00Tcm4TlvDq8ikWAM'; // Rachel
$apiUrl = 'https://api.elevenlabs.io/v1/text-to-speech/' . $voiceId;

$body = json_encode([
    'text' => 'Bonjour, ceci est un test du système de synthèse vocale.',
    'model_id' => 'eleven_multilingual_v2',
    'voice_settings' => [
        'stability' => 0.5,
        'similarity_boost' => 0.8,
        'style' => 0.2,
        'use_speaker_boost' => true,
    ],
]);

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_HTTPHEADER     => [
        'xi-api-key: ' . $apiKey,
        'Content-Type: application/json',
        'Accept: audio/mpeg',
    ],
    CURLOPT_CAINFO         => __DIR__ . '/cacert.pem',
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);

$audioData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Erreur cURL: " . $error . PHP_EOL;
    exit(1);
}

echo "HTTP Code: " . $httpCode . PHP_EOL;

if ($httpCode === 200) {
    $audioSize = strlen($audioData);
    echo "✅ Audio généré avec succès !" . PHP_EOL;
    echo "Taille: " . number_format($audioSize) . " bytes" . PHP_EOL;
    
    // Sauvegarder le fichier
    $filename = 'test_audio_' . time() . '.mp3';
    file_put_contents($filename, $audioData);
    echo "📁 Fichier sauvegardé: " . $filename . PHP_EOL;
    
} elseif ($httpCode === 401) {
    echo "❌ Clé API invalide !" . PHP_EOL;
    echo "Réponse: " . substr($audioData, 0, 500) . PHP_EOL;
} elseif ($httpCode === 422) {
    echo "❌ Erreur de validation (synthesis-failed) !" . PHP_EOL;
    $errorData = json_decode($audioData, true);
    echo "Détails: " . json_encode($errorData, JSON_PRETTY_PRINT) . PHP_EOL;
    
    if (isset($errorData['detail']['status'])) {
        echo PHP_EOL;
        echo "⚠️  Cause possible:" . PHP_EOL;
        if (strpos($errorData['detail']['status'], 'quota') !== false) {
            echo "   - Quota mensuel dépassé" . PHP_EOL;
        } elseif (strpos($errorData['detail']['status'], 'voice') !== false) {
            echo "   - Voice ID invalide ou inaccessible" . PHP_EOL;
        } else {
            echo "   - " . $errorData['detail']['status'] . PHP_EOL;
        }
    }
} else {
    echo "❌ Erreur HTTP " . $httpCode . PHP_EOL;
    echo "Réponse: " . substr($audioData, 0, 500) . PHP_EOL;
}

echo PHP_EOL;
echo "=== FIN DES TESTS ===" . PHP_EOL;
