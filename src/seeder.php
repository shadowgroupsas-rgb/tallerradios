<?php
require_once __DIR__ . '/../config/db.php';

if (php_sapi_name() !== 'cli' && !isset($_GET['run'])) {
    die("This script should be run from CLI or with ?run=1");
}

echo "Running Seeder...<br>\n";

try {
    // Check for admin
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pass = password_hash('admin123', PASSWORD_DEFAULT);

        // Get Admin Role ID
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'admin'");
        $stmt->execute();
        $role_id = $stmt->fetchColumn();

        if ($role_id) {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role_id) VALUES (?, ?, ?, ?)");
            $stmt->execute(['admin', 'admin@cruzroja.org', $pass, $role_id]);
            echo "Default admin created. User: admin, Pass: admin123<br>\n";
        } else {
            echo "Error: Admin role not found. Run database.sql first.<br>\n";
        }
    } else {
        echo "Admin already exists.<br>\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
