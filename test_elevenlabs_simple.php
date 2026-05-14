<?php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

$apiKey = $_ENV['ELEVENLABS_API_KEY'] ?? '';

echo "=== TEST ELEVENLABS TTS ===" . PHP_EOL . PHP_EOL;

$voiceId = '21m00Tcm4TlvDq8ikWAM'; // Rachel
$apiUrl = 'https://api.elevenlabs.io/v1/text-to-speech/' . $voiceId;

$body = json_encode([
    'text' => 'Bonjour, ceci est un test.',
    'model_id' => 'eleven_multilingual_v2',
    'voice_settings' => [
        'stability' => 0.5,
        'similarity_boost' => 0.8,
    ],
]);

echo "🔊 Génération audio..." . PHP_EOL;

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_HTTPHEADER     => [
        'xi-api-key: ' . $apiKey,
        'Content-Type: application/json',
        'Accept: audio/mpeg',
    ],
    CURLOPT_CAINFO         => __DIR__ . '/cacert.pem',
    CURLOPT_SSL_VERIFYPEER => true,
]);

$audioData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: " . $httpCode . PHP_EOL;

if ($error) {
    echo "❌ Erreur cURL: " . $error . PHP_EOL;
} elseif ($httpCode === 200) {
    echo "✅ Audio généré ! Taille: " . strlen($audioData) . " bytes" . PHP_EOL;
    $filename = 'test_' . time() . '.mp3';
    file_put_contents($filename, $audioData);
    echo "📁 Sauvegardé: " . $filename . PHP_EOL;
} else {
    echo "❌ Erreur HTTP " . $httpCode . PHP_EOL;
    $response = json_decode($audioData, true);
    if ($response) {
        echo "Détails: " . json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    } else {
        echo "Réponse brute: " . substr($audioData, 0, 500) . PHP_EOL;
    }
}
