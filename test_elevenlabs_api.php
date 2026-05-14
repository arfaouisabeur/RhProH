<?php
require __DIR__ . '/vendor/autoload.php';

$dotenv = new Symfony\Component\Dotenv\Dotenv();
$dotenv->load(__DIR__ . '/.env');

// Test direct de l'API ElevenLabs

$apiKey = $_ENV['ELEVENLABS_API_KEY'] ?? 'your_elevenlabs_api_key_here';
$voiceId = '21m00Tcm4TlvDq8ikWAM'; // Rachel
$apiUrl = 'https://api.elevenlabs.io/v1/text-to-speech/' . $voiceId;

$texte = 'Bonjour, ceci est un test de synthèse vocale avec ElevenLabs.';

$body = json_encode([
    'text' => $texte,
    'model_id' => 'eleven_multilingual_v2',
    'voice_settings' => [
        'stability' => 0.5,
        'similarity_boost' => 0.8,
        'style' => 0.2,
        'use_speaker_boost' => true,
    ],
]);

echo "🔧 Test ElevenLabs API\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "📍 URL: $apiUrl\n";
echo "🔑 API Key: " . substr($apiKey, 0, 10) . "...\n";
echo "📝 Texte: $texte\n\n";

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_HTTPHEADER => [
        'xi-api-key: ' . $apiKey,
        'Content-Type: application/json',
        'Accept: audio/mpeg',
    ],
    CURLOPT_CAINFO => __DIR__ . '/cacert.pem',
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_VERBOSE => true,
]);

echo "⏳ Envoi de la requête...\n\n";

$raw = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$info = curl_getinfo($ch);

curl_close($ch);

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 RÉSULTATS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "🔢 HTTP Code: $code\n";
echo "⏱️  Temps total: " . round($info['total_time'], 2) . "s\n";
echo "📦 Taille réponse: " . strlen($raw) . " bytes\n\n";

if ($error) {
    echo "❌ ERREUR cURL: $error\n\n";
} else {
    echo "✅ Pas d'erreur cURL\n\n";
}

if ($code === 200) {
    echo "✅ SUCCÈS! Audio MP3 reçu\n";
    echo "💾 Sauvegarde dans test_audio.mp3...\n";
    file_put_contents('test_audio.mp3', $raw);
    echo "✅ Fichier sauvegardé! Vous pouvez l'écouter.\n";
} else {
    echo "❌ ÉCHEC! Code HTTP: $code\n\n";
    echo "📄 Réponse de l'API:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    // Essayer de décoder JSON
    $json = json_decode($raw, true);
    if ($json) {
        echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo substr($raw, 0, 1000) . "\n";
    }
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
}

echo "\n";
