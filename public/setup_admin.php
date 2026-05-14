<?php
// Emergency admin creation script
// Access via: https://rhproh.onrender.com/setup_admin.php

// Get database URL from environment
$databaseUrl = getenv('DATABASE_URL');

if (!$databaseUrl) {
    die('ERROR: DATABASE_URL not set');
}

// Parse the database URL
$dbParts = parse_url($databaseUrl);

$host = $dbParts['host'];
$port = $dbParts['port'] ?? 5432;
$dbname = ltrim($dbParts['path'], '/');
$user = $dbParts['user'];
$password = $dbParts['pass'];

// Connect to PostgreSQL
$connString = "host=$host port=$port dbname=$dbname user=$user password=$password";
$conn = pg_connect($connString);

if (!$conn) {
    die('ERROR: Could not connect to database');
}

// Check if admin already exists
$checkQuery = "SELECT id, email FROM \"user\" WHERE email = 'admin@rhpro.com'";
$result = pg_query($conn, $checkQuery);

if (pg_num_rows($result) > 0) {
    $admin = pg_fetch_assoc($result);
    echo "✅ Admin user already exists!<br>";
    echo "ID: " . $admin['id'] . "<br>";
    echo "Email: " . $admin['email'] . "<br><br>";
    echo "You can now login at: <a href='/login'>https://rhproh.onrender.com/login</a><br>";
    echo "Email: admin@rhpro.com<br>";
    echo "Password: admin123";
} else {
    // Hash the password using bcrypt (Symfony's default)
    $hashedPassword = password_hash('admin123', PASSWORD_BCRYPT);
    
    // Create admin user with correct column names
    $insertQuery = "INSERT INTO \"user\" (nom, prenom, email, mot_de_passe, role, statut) 
                    VALUES ('Admin', 'RHPro', 'admin@rhpro.com', '$hashedPassword', 'RH', 'actif')
                    RETURNING id, email";
    
    $result = pg_query($conn, $insertQuery);
    
    if ($result) {
        $admin = pg_fetch_assoc($result);
        echo "✅ SUCCESS! Admin user created!<br><br>";
        echo "ID: " . $admin['id'] . "<br>";
        echo "Email: " . $admin['email'] . "<br><br>";
        
        // Also create RH entry for admin
        $rhQuery = "INSERT INTO rh (user_id) VALUES (" . $admin['id'] . ")";
        pg_query($conn, $rhQuery);
        echo "✅ RH role assigned!<br><br>";
        
        echo "You can now login at: <a href='/login'>https://rhproh.onrender.com/login</a><br>";
        echo "Email: admin@rhpro.com<br>";
        echo "Password: admin123<br><br>";
        echo "<strong>IMPORTANT: Delete this file (setup_admin.php) after logging in!</strong>";
    } else {
        echo "ERROR: Could not create admin user<br>";
        echo "Error: " . pg_last_error($conn);
    }
}

pg_close($conn);
?>
