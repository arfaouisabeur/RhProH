<?php
require __DIR__.'/vendor/autoload.php';

use Doctrine\DBAL\DriverManager;

$connectionParams = [
    'dbname' => 'pidevf',
    'user' => 'root',
    'password' => '',
    'host' => '127.0.0.1',
    'driver' => 'pdo_mysql',
];

$conn = DriverManager::getConnection($connectionParams);

echo "Applying fixes...\n";

try {
    // Drop foreign key
    $conn->executeStatement('ALTER TABLE demande_service DROP FOREIGN KEY FK_D16A217DC54C8C93');
    echo "✓ Dropped foreign key\n";
} catch (\Exception $e) {
    echo "⚠ Foreign key might not exist: " . $e->getMessage() . "\n";
}

try {
    // Modify column
    $conn->executeStatement('ALTER TABLE demande_service CHANGE type_id type_id BIGINT NOT NULL');
    echo "✓ Modified type_id column\n";
} catch (\Exception $e) {
    echo "✗ Error modifying column: " . $e->getMessage() . "\n";
}

try {
    // Re-add foreign key
    $conn->executeStatement('ALTER TABLE demande_service ADD CONSTRAINT FK_D16A217DC54C8C93 FOREIGN KEY (type_id) REFERENCES type_service(id) ON DELETE CASCADE');
    echo "✓ Re-added foreign key\n";
} catch (\Exception $e) {
    echo "⚠ Foreign key might already exist: " . $e->getMessage() . "\n";
}

try {
    // Add service_reaction fields
    $conn->executeStatement('ALTER TABLE service_reaction ADD updated_at DATETIME DEFAULT NULL');
    echo "✓ Added updated_at\n";
} catch (\Exception $e) {
    echo "⚠ updated_at might already exist\n";
}

try {
    $conn->executeStatement('ALTER TABLE service_reaction ADD created_by BIGINT DEFAULT NULL');
    echo "✓ Added created_by\n";
} catch (\Exception $e) {
    echo "⚠ created_by might already exist\n";
}

try {
    $conn->executeStatement('ALTER TABLE service_reaction ADD updated_by BIGINT DEFAULT NULL');
    echo "✓ Added updated_by\n";
} catch (\Exception $e) {
    echo "⚠ updated_by might already exist\n";
}

try {
    $conn->executeStatement('ALTER TABLE service_reaction ADD CONSTRAINT FK_5DA15CCADE12AB56 FOREIGN KEY (created_by) REFERENCES users (id)');
    echo "✓ Added created_by foreign key\n";
} catch (\Exception $e) {
    echo "⚠ created_by FK might already exist\n";
}

try {
    $conn->executeStatement('ALTER TABLE service_reaction ADD CONSTRAINT FK_5DA15CCA16FE72E1 FOREIGN KEY (updated_by) REFERENCES users (id)');
    echo "✓ Added updated_by foreign key\n";
} catch (\Exception $e) {
    echo "⚠ updated_by FK might already exist\n";
}

try {
    $conn->executeStatement('CREATE INDEX IDX_5DA15CCADE12AB56 ON service_reaction (created_by)');
    echo "✓ Created created_by index\n";
} catch (\Exception $e) {
    echo "⚠ created_by index might already exist\n";
}

try {
    $conn->executeStatement('CREATE INDEX IDX_5DA15CCA16FE72E1 ON service_reaction (updated_by)');
    echo "✓ Created updated_by index\n";
} catch (\Exception $e) {
    echo "⚠ updated_by index might already exist\n";
}

echo "\nDone! All fixes applied.\n";
