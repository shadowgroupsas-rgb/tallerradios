<?php
// tests/test_install.php

// Force local test environment to trigger SQLite fallback
putenv('APP_ENV=local_test');
$dbFile = __DIR__ . '/../database.sqlite';
$configFile = __DIR__ . '/../config/db.php';
$schemaFile = __DIR__ . '/../database.sql';

// Clean up
if (file_exists($dbFile)) unlink($dbFile);
if (file_exists($configFile)) unlink($configFile);

echo ">> Simulating Installation...\n";

// Use POST request simulation
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['host'] = 'localhost'; // Ignored in local_test mode, uses sqlite
$_POST['name'] = 'test_db';
$_POST['user'] = 'root';
$_POST['pass'] = '';
$_POST['admin_user'] = 'new_admin';
$_POST['admin_pass'] = 'secret';
$_POST['admin_email'] = 'admin@test.com';
$_POST['signal_url'] = 'http://localhost:3000';

// Mock Password Hash for Installer (so we don't need full PHP env constraints if any)
// Actually we need `password_hash` function available.

ob_start();
require __DIR__ . '/../public/install/index.php';
$output = ob_get_clean();

if (strpos($output, 'Installation Successful') !== false) {
    echo ">> Installer Output: SUCCESS\n";
} else {
    echo ">> Installer Output: FAILED\n";
    echo $output;
    exit(1);
}

// Verify config file created
if (file_exists($configFile)) {
    echo ">> Config File Created: YES\n";
} else {
    echo ">> Config File Created: NO\n";
    exit(1);
}

// Verify JS config
if (file_exists(__DIR__ . '/../public/js/config.js')) {
    $jsContent = file_get_contents(__DIR__ . '/../public/js/config.js');
    if (strpos($jsContent, 'http://localhost:3000') !== false) {
        echo ">> JS Config Created: YES\n";
    } else {
        echo ">> JS Config Content: MISMATCH\n";
    }
}

// Verify DB Content (using SQLite direct check)
try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $stmt = $pdo->query("SELECT * FROM users WHERE username = 'new_admin'");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify('secret', $user['password_hash'])) {
        echo ">> Admin User Created: YES\n";
    } else {
        echo ">> Admin User Created: NO\n";
    }
} catch (Exception $e) {
    echo ">> DB Check Failed: " . $e->getMessage() . "\n";
}

// Cleanup
unlink($dbFile);
unlink($configFile);
unlink(__DIR__ . '/../public/js/config.js');
?>
