<?php

/**
 * PHPStan Issues Auto-Fix Script
 * Fixes the most common type conversion issues found in the analysis
 */

echo "🔧 PHPStan Auto-Fix Script for Salaire, Prime & Contract Modules\n";
echo "================================================================\n\n";

$fixes = [
    // ContractController.php fixes
    'src/Controller/ContractController.php' => [
        [
            'search' => '$contract->setSalaireBase($display);',
            'replace' => '$contract->setSalaireBase((string) $display);',
            'line' => 109,
            'description' => 'Fix float to string conversion for setSalaireBase'
        ],
        [
            'search' => '$contract->setSalaireBase($display);',
            'replace' => '$contract->setSalaireBase((string) $display);',
            'line' => 138,
            'description' => 'Fix float to string conversion for setSalaireBase'
        ],
        [
            'search' => '$contract->setSalaireBase($display);',
            'replace' => '$contract->setSalaireBase((string) $display);',
            'line' => 153,
            'description' => 'Fix float to string conversion for setSalaireBase'
        ],
        [
            'search' => '$this->isCsrfTokenValid(\'delete\', $request->request->get(\'_token\'))',
            'replace' => '$this->isCsrfTokenValid(\'delete\', (string) $request->request->get(\'_token\'))',
            'line' => 174,
            'description' => 'Fix CSRF token type conversion'
        ]
    ],
    
    // RhPrimeController.php fixes
    'src/Controller/RhPrimeController.php' => [
        [
            'search' => '$prime->setMontant($displayMontant);',
            'replace' => '$prime->setMontant((string) $displayMontant);',
            'line' => 94,
            'description' => 'Fix float to string conversion for setMontant'
        ],
        [
            'search' => '$data = json_decode($request->request->get(\'data\'));',
            'replace' => '$jsonData = $request->request->get(\'data\'); $data = is_string($jsonData) ? json_decode($jsonData) : null;',
            'line' => 100,
            'description' => 'Fix JSON decode parameter type'
        ],
        [
            'search' => '$prime->setMontant($displayMontant);',
            'replace' => '$prime->setMontant((string) $displayMontant);',
            'line' => 135,
            'description' => 'Fix float to string conversion for setMontant'
        ],
        [
            'search' => '$prime->setMontant($display);',
            'replace' => '$prime->setMontant((string) $display);',
            'line' => 144,
            'description' => 'Fix float to string conversion for setMontant'
        ],
        [
            'search' => '$this->isCsrfTokenValid(\'delete\', $request->request->get(\'_token\'))',
            'replace' => '$this->isCsrfTokenValid(\'delete\', (string) $request->request->get(\'_token\'))',
            'line' => 161,
            'description' => 'Fix CSRF token type conversion'
        ]
    ],
    
    // RhSalaireController.php fixes
    'src/Controller/RhSalaireController.php' => [
        [
            'search' => '$salaire->setMontant($displayMontant);',
            'replace' => '$salaire->setMontant((string) $displayMontant);',
            'line' => 107,
            'description' => 'Fix float to string conversion for setMontant'
        ],
        [
            'search' => '$salaire->setMontant($displayMontant);',
            'replace' => '$salaire->setMontant((string) $displayMontant);',
            'line' => 130,
            'description' => 'Fix float to string conversion for setMontant'
        ],
        [
            'search' => '$salaire->setMontant($display);',
            'replace' => '$salaire->setMontant((string) $display);',
            'line' => 142,
            'description' => 'Fix float to string conversion for setMontant'
        ],
        [
            'search' => '$this->isCsrfTokenValid(\'delete\', $request->request->get(\'_token\'))',
            'replace' => '$this->isCsrfTokenValid(\'delete\', (string) $request->request->get(\'_token\'))',
            'line' => 159,
            'description' => 'Fix CSRF token type conversion'
        ]
    ],
    
    // Form fixes
    'src/Form/ContractType.php' => [
        [
            'search' => 'class ContractType extends AbstractType',
            'replace' => "/**\n * @extends AbstractType<Contract>\n */\nclass ContractType extends AbstractType",
            'line' => 18,
            'description' => 'Add generic type annotation'
        ]
    ],
    
    'src/Form/PrimeType.php' => [
        [
            'search' => 'class PrimeType extends AbstractType',
            'replace' => "/**\n * @extends AbstractType<Prime>\n */\nclass PrimeType extends AbstractType",
            'line' => 16,
            'description' => 'Add generic type annotation'
        ]
    ],
    
    'src/Form/SalaireType.php' => [
        [
            'search' => 'class SalaireType extends AbstractType',
            'replace' => "/**\n * @extends AbstractType<Salaire>\n */\nclass SalaireType extends AbstractType",
            'line' => 17,
            'description' => 'Add generic type annotation'
        ]
    ]
];

$totalFixes = 0;
$successfulFixes = 0;

foreach ($fixes as $file => $fileFixes) {
    echo "📁 Processing: $file\n";
    
    if (!file_exists($file)) {
        echo "   ❌ File not found: $file\n";
        continue;
    }
    
    $content = file_get_contents($file);
    $originalContent = $content;
    
    foreach ($fileFixes as $fix) {
        $totalFixes++;
        echo "   🔧 Line {$fix['line']}: {$fix['description']}\n";
        
        if (strpos($content, $fix['search']) !== false) {
            $content = str_replace($fix['search'], $fix['replace'], $content);
            $successfulFixes++;
            echo "      ✅ Applied\n";
        } else {
            echo "      ⚠️  Pattern not found (may already be fixed)\n";
        }
    }
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "   💾 File updated\n";
    }
    
    echo "\n";
}

echo "📊 Summary:\n";
echo "   Total fixes attempted: $totalFixes\n";
echo "   Successful fixes: $successfulFixes\n";
echo "   Success rate: " . round(($successfulFixes / $totalFixes) * 100, 1) . "%\n\n";

echo "🎯 Next Steps:\n";
echo "1. Run PHPStan again: vendor/bin/phpstan analyse --configuration=phpstan-modules.neon\n";
echo "2. Run unit tests: php bin/phpunit tests/Entity\n";
echo "3. Test the application manually\n";
echo "4. Commit the changes if everything works\n\n";

echo "✅ Auto-fix script completed!\n";