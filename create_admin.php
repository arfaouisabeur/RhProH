<?php
// Direct SQL admin creation - bypasses Doctrine completely

$databaseUrl = getenv('DATABASE_URL');
if (!$databaseUrl) {
    echo "ERROR: DATABASE_URL not set\n";
    exit(1);
}

// Parse DATABASE_URL to get connection details
// Format: postgresql://user:password@host:port/database
preg_match('/postgresql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/(.+)/', $databaseUrl, $matches);

if (count($matches) < 6) {
    echo "ERROR: Invalid DATABASE_URL format\n";
    exit(1);
}

$user = $matches[1];
$password = $matches[2];
$host = $matches[3];
$port = $matches[4];
$database = $matches[5];

try {
    // Use pg_connect instead of PDO
    $conn = pg_connect("host=$host port=$port dbname=$database user=$user password=$password");
    
    if (!$conn) {
        echo "ERROR: Could not connect to database\n";
        exit(1);
    }
    
    // Check if admin already exists
    $result = pg_query_params($conn, 'SELECT id FROM "user" WHERE email = $1', ['admin@rhpro.com']);
    
    if (pg_num_rows($result) > 0) {
        echo "✓ Admin user already exists\n";
        pg_close($conn);
        exit(0);
    }
    
    // Insert admin user
    $result = pg_query_params($conn, '
        INSERT INTO "user" (nom, prenom, email, mot_de_passe, telephone, adresse, role, statut)
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
        RETURNING id
    ', [
        'Admin',
        'RHPro',
        'admin@rhpro.com',
        'admin123',  // Plain text for Java compatibility
        '+21600000000',
        'Tunis, Tunisia',
        'RH',
        'actif'
    ]);
    
    if (!$result) {
        echo "ERROR: Failed to insert user: " . pg_last_error($conn) . "\n";
        pg_close($conn);
        exit(1);
    }
    
    $row = pg_fetch_assoc($result);
    $userId = $row['id'];
    
    // Insert RH profile
    $result = pg_query_params($conn, 'INSERT INTO rh (user_id) VALUES ($1)', [$userId]);
    
    if (!$result) {
        echo "ERROR: Failed to insert RH profile: " . pg_last_error($conn) . "\n";
        pg_close($conn);
        exit(1);
    }
    
    echo "✓ Admin user created successfully!\n";
    echo "  Email: admin@rhpro.com\n";
    echo "  Password: admin123\n";
    
    pg_close($conn);
    exit(0);
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

