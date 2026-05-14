<?php
// Direct SQL admin creation - bypasses Doctrine completely

$databaseUrl = getenv('DATABASE_URL');
if (!$databaseUrl) {
    echo "ERROR: DATABASE_URL not set\n";
    exit(1);
}

try {
    $pdo = new PDO($databaseUrl);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if admin already exists
    $stmt = $pdo->prepare('SELECT id FROM "user" WHERE email = :email');
    $stmt->execute(['email' => 'admin@rhpro.com']);
    
    if ($stmt->fetch()) {
        echo "✓ Admin user already exists\n";
        exit(0);
    }
    
    // Insert admin user
    $stmt = $pdo->prepare('
        INSERT INTO "user" (nom, prenom, email, mot_de_passe, telephone, adresse, role, statut)
        VALUES (:nom, :prenom, :email, :password, :telephone, :adresse, :role, :statut)
        RETURNING id
    ');
    
    $stmt->execute([
        'nom' => 'Admin',
        'prenom' => 'RHPro',
        'email' => 'admin@rhpro.com',
        'password' => 'admin123',  // Plain text for Java compatibility
        'telephone' => '+21600000000',
        'adresse' => 'Tunis, Tunisia',
        'role' => 'RH',
        'statut' => 'actif'
    ]);
    
    $userId = $pdo->lastInsertId();
    
    // Insert RH profile
    $stmt = $pdo->prepare('INSERT INTO rh (user_id) VALUES (:user_id)');
    $stmt->execute(['user_id' => $userId]);
    
    echo "✓ Admin user created successfully!\n";
    echo "  Email: admin@rhpro.com\n";
    echo "  Password: admin123\n";
    
    exit(0);
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
