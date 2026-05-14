<?php
require __DIR__ . '/vendor/autoload.php';

$dotenv = new Symfony\Component\Dotenv\Dotenv();
$dotenv->load(__DIR__ . '/.env');

$sid   = $_ENV['TWILIO_ACCOUNT_SID'] ?? 'your_twilio_account_sid_here';
$token = $_ENV['TWILIO_AUTH_TOKEN']  ?? 'your_twilio_auth_token_here';
$from  = $_ENV['TWILIO_FROM_NUMBER'] ?? 'your_twilio_phone_number_here';
$to    = '+21652175930'; // ← seul numéro vérifié sur ce compte Trial Twilio

echo "=== TEST TWILIO (fix SSL via CurlClient) ===" . PHP_EOL;
echo "SID : " . substr($sid, 0, 6) . "..." . PHP_EOL;
echo "From: $from" . PHP_EOL;
echo "To  : $to"   . PHP_EOL . PHP_EOL;

// --- Fix SSL Windows : CurlClient Twilio + cacert.pem ---
$cacert = __DIR__ . '/cacert.pem';
$httpClient = null;

if (file_exists($cacert)) {
    echo "✅ cacert.pem trouvé." . PHP_EOL;
    $httpClient = new Twilio\Http\CurlClient([
        CURLOPT_CAINFO    => $cacert,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_TIMEOUT   => 30,
    ]);
} else {
    echo "⚠️  cacert.pem introuvable ! SSL sans vérification (dangereux)." . PHP_EOL;
    $httpClient = new Twilio\Http\CurlClient([
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
}

try {
    $client = new Twilio\Rest\Client($sid, $token, null, null, $httpClient);

    // Test connexion compte
    $account = $client->api->v2010->accounts($sid)->fetch();
    echo "✅ Connexion OK !" . PHP_EOL;
    echo "Compte : " . $account->friendlyName . PHP_EOL;
    echo "Type   : " . $account->type   . PHP_EOL;
    echo "Statut : " . $account->status . PHP_EOL . PHP_EOL;

    // Numéros vérifiés (Trial)
    echo "=== NUMÉROS VÉRIFIÉS ===" . PHP_EOL;
    $verified = $client->outgoingCallerIds->read([], 20);
    foreach ($verified as $v) {
        echo " - " . $v->phoneNumber . " (" . $v->friendlyName . ")" . PHP_EOL;
    }
    if (empty($verified)) echo "(aucun numéro vérifié)" . PHP_EOL;

    // Envoi SMS test
    echo PHP_EOL . "=== ENVOI SMS ===" . PHP_EOL;
    $msg = $client->messages->create($to, [
        'from' => $from,
        'body' => 'TEST RHPro : SMS OK !',
    ]);
    echo "✅ SMS envoyé !" . PHP_EOL;
    echo "SID message : " . $msg->sid    . PHP_EOL;
    echo "Statut      : " . $msg->status . PHP_EOL;

} catch (Exception $e) {
    echo "❌ ERREUR : " . $e->getMessage() . PHP_EOL;
    echo "Code       : " . $e->getCode()   . PHP_EOL;
}
