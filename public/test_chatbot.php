<?php
// Test direct - accessible sur http://127.0.0.1:8000/test_chatbot.php
$ch = curl_init('http://127.0.0.1:5001/chatbot');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'message' => 'bonjour',
    'contexte' => new stdClass()
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

header('Content-Type: text/plain');
echo "Python API direct:\n";
echo "HTTP: $code\n";
echo "Body: $result\n";
echo "\n---\n";

// Maintenant via le kernel Symfony
require_once __DIR__ . '/../vendor/autoload.php';
echo "Done";
