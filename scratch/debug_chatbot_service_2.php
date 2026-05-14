<?php
require_once 'vendor/autoload.php';
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv('.env');
$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();
$httpClient = $container->get('http_client');

$chatbotService = new \App\Service\ChatbotService($httpClient, $em);

try {
    echo "Testing construireContexte()...\n";
    $reflection = new \ReflectionClass($chatbotService);
    $method = $reflection->getMethod('construireContexte');
    $method->setAccessible(true);
    $contexte = $method->invoke($chatbotService);
    echo "Contexte OK! Total tâches: " . count($contexte['taches_total'] ?? []) . "\n";
    
    echo "Testing poserQuestion()...\n";
    $result = $chatbotService->poserQuestion('hello');
    print_r($result);
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " (Line " . $e->getLine() . ")\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
