<?php

$files = [
    __DIR__ . '/templates/auth/register_candidat.html.twig',
    __DIR__ . '/templates/auth/register_employe.html.twig'
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);

    // Fix bug: adresse is a textarea, not an input!
    $content = str_replace(
        "const addressInput = document.querySelector('input[name=\"registration_candidat[adresse]\"]') || document.querySelector('input[name=\"registration_employe[adresse]\"]');",
        "const addressInput = document.querySelector('textarea[name=\"registration_candidat[adresse]\"]') || document.querySelector('textarea[name=\"registration_employe[adresse]\"]');",
        $content
    );

    file_put_contents($file, $content);
    echo "Fixed selector for: " . basename($file) . "\n";
}
