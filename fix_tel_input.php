<?php
$files = [
    __DIR__ . '/templates/auth/register_candidat.html.twig',
    __DIR__ . '/templates/auth/register_employe.html.twig'
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    
    // Fix CDN version
    $content = str_replace('17.0.19', '17.0.8', $content);
    
    // Sometimes telephone is not input[type="tel"] but input[type="text"] if rendered differently.
    // Let's make the selector bulletproof by checking both.
    $content = str_replace(
        "const phoneInput = document.querySelector('input[type=\"tel\"]');",
        "const phoneInput = document.querySelector('input[type=\"tel\"]') || document.querySelector('input[name=\"registration_candidat[telephone]\"]') || document.querySelector('input[name=\"registration_employe[telephone]\"]');",
        $content
    );

    file_put_contents($file, $content);
    echo "Fixed CDN and selector for ".basename($file)."\n";
}
