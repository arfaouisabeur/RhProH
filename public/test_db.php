<?php
// Database connection test
// Access via: https://rhproh.onrender.com/test_db.php

echo "<h1>Database Connection Test</h1>";
echo "<hr>";

// Test 1: Check DATABASE_URL
echo "<h2>Test 1: Environment Variables</h2>";
$databaseUrl = getenv('DATABASE_URL');
if ($databaseUrl) {
    echo "✅ DATABASE_URL is set<br>";
    $dbParts = parse_url($databaseUrl);
    echo "Host: " . ($dbParts['host'] ?? 'NOT SET') . "<br>";
    echo "Port: " . ($dbParts['port'] ?? 5432) . "<br>";
    echo "Database: " . ltrim($dbParts['path'] ?? '', '/') . "<br>";
    echo "User: " . ($dbParts['user'] ?? 'NOT SET') . "<br>";
} else {
    echo "❌ DATABASE_URL is NOT set<br>";
    die();
}

echo "<hr>";

// Test 2: Check PHP extensions
echo "<h2>Test 2: PHP Extensions</h2>";
echo "PDO: " . (extension_loaded('pdo') ? '✅' : '❌') . "<br>";
echo "PDO_PGSQL: " . (extension_loaded('pdo_pgsql') ? '✅' : '❌') . "<br>";
echo "PGSQL: " . (extension_loaded('pgsql') ? '✅' : '❌') . "<br>";

echo "<hr>";

// Test 3: Connect to database
echo "<h2>Test 3: Database Connection</h2>";
$connString = sprintf(
    "host=%s port=%d dbname=%s user=%s password=%s",
    $dbParts['host'],
    $dbParts['port'] ?? 5432,
    ltrim($dbParts['path'], '/'),
    $dbParts['user'],
    $dbParts['pass']
);

$conn = @pg_connect($connString);
if ($conn) {
    echo "✅ Connected to PostgreSQL<br>";
} else {
    echo "❌ Failed to connect: " . pg_last_error() . "<br>";
    die();
}

echo "<hr>";

// Test 4: Check if user table exists
echo "<h2>Test 4: User Table</h2>";
$result = pg_query($conn, "SELECT COUNT(*) as count FROM \"user\"");
if ($result) {
    $row = pg_fetch_assoc($result);
    echo "✅ User table exists<br>";
    echo "Total users: " . $row['count'] . "<br>";
} else {
    echo "❌ User table error: " . pg_last_error($conn) . "<br>";
}

echo "<hr>";

// Test 5: Check admin user
echo "<h2>Test 5: Admin User</h2>";
$result = pg_query($conn, "SELECT id, nom, prenom, email, role, statut, LENGTH(mot_de_passe) as pwd_length FROM \"user\" WHERE email = 'admin@rhpro.com'");
if ($result && pg_num_rows($result) > 0) {
    $admin = pg_fetch_assoc($result);
    echo "✅ Admin user found<br>";
    echo "ID: " . $admin['id'] . "<br>";
    echo "Name: " . $admin['prenom'] . " " . $admin['nom'] . "<br>";
    echo "Email: " . $admin['email'] . "<br>";
    echo "Role: " . $admin['role'] . "<br>";
    echo "Status: " . $admin['statut'] . "<br>";
    echo "Password length: " . $admin['pwd_length'] . " chars<br>";
    
    // Check if password is hashed
    if ($admin['pwd_length'] > 50) {
        echo "✅ Password appears to be hashed<br>";
    } else {
        echo "⚠️ Password might be plain text (length: " . $admin['pwd_length'] . ")<br>";
    }
} else {
    echo "❌ Admin user NOT found<br>";
}

echo "<hr>";

// Test 6: Check RH table
echo "<h2>Test 6: RH Role</h2>";
$result = pg_query($conn, "SELECT user_id FROM rh WHERE user_id = (SELECT id FROM \"user\" WHERE email = 'admin@rhpro.com')");
if ($result && pg_num_rows($result) > 0) {
    echo "✅ Admin has RH role in rh table<br>";
} else {
    echo "❌ Admin NOT in rh table<br>";
}

echo "<hr>";

// Test 7: Test password verification
echo "<h2>Test 7: Password Verification</h2>";
$result = pg_query($conn, "SELECT mot_de_passe FROM \"user\" WHERE email = 'admin@rhpro.com'");
if ($result && pg_num_rows($result) > 0) {
    $row = pg_fetch_assoc($result);
    $storedPassword = $row['mot_de_passe'];
    
    if (password_verify('admin123', $storedPassword)) {
        echo "✅ Password 'admin123' verifies correctly<br>";
    } else {
        echo "❌ Password 'admin123' does NOT verify<br>";
        echo "Stored password starts with: " . substr($storedPassword, 0, 10) . "...<br>";
    }
}

echo "<hr>";

// Test 8: Check Symfony files
echo "<h2>Test 8: Symfony Files</h2>";
$files = [
    '/app/config/packages/security.yaml',
    '/app/src/Entity/User.php',
    '/app/src/Security/LoginFormAuthenticator.php',
    '/app/var/cache',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file NOT found<br>";
    }
}

echo "<hr>";

// Test 9: Check cache permissions
echo "<h2>Test 9: Cache Directory</h2>";
$cacheDir = '/app/var/cache';
if (is_dir($cacheDir)) {
    echo "✅ Cache directory exists<br>";
    if (is_writable($cacheDir)) {
        echo "✅ Cache directory is writable<br>";
    } else {
        echo "❌ Cache directory is NOT writable<br>";
    }
} else {
    echo "❌ Cache directory does NOT exist<br>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "If all tests pass, the database is working correctly.<br>";
echo "The 500 error might be a Symfony configuration issue, not a database issue.<br>";

pg_close($conn);
?>
