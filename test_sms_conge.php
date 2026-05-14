<?php

require __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

// Créer le kernel Symfony
$kernel = new Kernel($_ENV['APP_ENV'] ?? 'dev', (bool) ($_ENV['APP_DEBUG'] ?? true));
$kernel->boot();
$container = $kernel->getContainer();

// Récupérer les services
$em = $container->get('doctrine')->getManager();
$smsService = $container->get('App\Service\SmsService');

echo "=== TEST SMS CONGÉ ===" . PHP_EOL . PHP_EOL;

// Récupérer un congé de test
$congeRepo = $em->getRepository('App\Entity\CongeTt');
$conge = $congeRepo->findOneBy(['statut' => 'Accepté']);

if (!$conge) {
    echo "❌ Aucun congé accepté trouvé en base." . PHP_EOL;
    echo "Créez d'abord un congé via l'interface." . PHP_EOL;
    exit(1);
}

echo "✅ Congé trouvé : #" . $conge->getId() . PHP_EOL;
echo "   Type : " . $conge->getTypeConge() . PHP_EOL;
echo "   Date début : " . $conge->getDateDebut()->format('d/m/Y') . PHP_EOL;
echo "   Employé : " . ($conge->getEmploye()?->getUser()?->getNom() ?? 'N/A') . PHP_EOL;
echo PHP_EOL;

// Test 1 : SMS d'acceptation
echo "📤 Test 1 : Envoi SMS d'acceptation..." . PHP_EOL;
try {
    $smsService->envoyerAlerteConge($conge, 'approuvé');
    echo "✅ SMS d'acceptation envoyé avec succès !" . PHP_EOL;
} catch (\Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;

// Test 2 : SMS de refus
echo "📤 Test 2 : Envoi SMS de refus..." . PHP_EOL;
try {
    $smsService->envoyerAlerteConge($conge, 'refusé');
    echo "✅ SMS de refus envoyé avec succès !" . PHP_EOL;
} catch (\Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL;
echo "=== FIN DES TESTS ===" . PHP_EOL;
