<?php
// Reset admin user with hashed password
// Access via: https://rhproh.onrender.com/reset_admin.php

$databaseUrl = getenv('DATABASE_URL');
if (!$databaseUrl) {
    die('ERROR: DATABASE_URL not set');
}

$dbParts = parse_url($databaseUrl);
$connString = sprintf(
    "host=%s port=%d dbname=%s user=%s password=%s",
    $dbParts['host'],
    $dbParts['port'] ?? 5432,
    ltrim($dbParts['path'], '/'),
    $dbParts['user'],
    $dbParts['pass']
);

$conn = pg_connect($connString);
if (!$conn) {
    die('ERROR: Could not connect to database');
}

// Delete old admin
pg_query($conn, "DELETE FROM rh WHERE user_id = (SELECT id FROM \"user\" WHERE email = 'admin@rhpro.com')");
pg_query($conn, "DELETE FROM \"user\" WHERE email = 'admin@rhpro.com'");

// Hash the password
$hashedPassword = password_hash('admin123', PASSWORD_BCRYPT);

// Create new admin with hashed password
$insertQuery = "INSERT INTO \"user\" (nom, prenom, email, mot_de_passe, role, statut) 
                VALUES ('Admin', 'RHPro', 'admin@rhpro.com', '$hashedPassword', 'RH', 'actif')
                RETURNING id, email";

$result = pg_query($conn, $insertQuery);

if ($result) {
    $admin = pg_fetch_assoc($result);
    echo "✅ Admin user reset successfully!<br><br>";
    echo "ID: " . $admin['id'] . "<br>";
    echo "Email: " . $admin['email'] . "<br><br>";
    
    // Create RH entry
    $rhQuery = "INSERT INTO rh (user_id) VALUES (" . $admin['id'] . ")";
    pg_query($conn, $rhQuery);
    echo "✅ RH role assigned!<br><br>";
    
    echo "Login at: <a href='/login'>https://rhproh.onrender.com/login</a><br>";
    echo "Email: admin@rhpro.com<br>";
    echo "Password: admin123 (now properly hashed!)<br><br>";
    echo "<strong>DELETE THIS FILE after logging in!</strong>";
} else {
    echo "ERROR: " . pg_last_error($conn);
}

pg_close($conn);
?>
