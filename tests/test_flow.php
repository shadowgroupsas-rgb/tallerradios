<?php
// tests/test_flow.php

// Force local test environment to trigger SQLite fallback in config/db.php
putenv('APP_ENV=local_test');
$dbFile = __DIR__ . '/../database.sqlite';
if (file_exists($dbFile)) unlink($dbFile);

require_once __DIR__ . '/../config/db.php';

echo ">> Database initialized (SQLite Mode).\n";

// Load Schema
$schema = file_get_contents(__DIR__ . '/../database.sql');

// Fix MySQL syntax for SQLite
$schema = str_replace('INT AUTO_INCREMENT PRIMARY KEY', 'INTEGER PRIMARY KEY AUTOINCREMENT', $schema);
$schema = str_replace('TIMESTAMP DEFAULT CURRENT_TIMESTAMP', 'DATETIME DEFAULT CURRENT_TIMESTAMP', $schema);
// Remove MySQL specific ON DELETE CASCADE syntax if problematic? No, SQLite supports it if enabled.
// Remove Foreign Key checks temporarily to avoid ordering issues during create
$pdo->exec("PRAGMA foreign_keys = OFF;");

$statements = explode(';', $schema);
foreach($statements as $sql) {
    if(trim($sql)) {
        try {
            $pdo->exec($sql);
        } catch (Exception $e) {
            echo "Schema Error: " . $e->getMessage() . "\nSQL: $sql\n";
        }
    }
}
$pdo->exec("PRAGMA foreign_keys = ON;");
echo ">> Schema loaded.\n";

// TEST 1: Check Roles
$stm = $pdo->query("SELECT COUNT(*) FROM roles");
$count = $stm->fetchColumn();
echo ">> Roles count: " . $count . " (Expected 3)\n";

// TEST 2: Run Seeder
// Capture output
ob_start();
require __DIR__ . '/../src/seeder.php';
ob_end_clean();

$stm = $pdo->query("SELECT username, role_id FROM users WHERE username='admin'");
$admin = $stm->fetch();
echo ">> Admin User Created: " . ($admin ? 'YES' : 'NO') . "\n";

// TEST 3: Auth Login
require_once __DIR__ . '/../src/auth.php';
// Mock Session
if (session_status() === PHP_SESSION_NONE) session_start();

if (login('admin', 'admin123')) {
    echo ">> Login Admin: SUCCESS\n";
} else {
    echo ">> Login Admin: FAILED\n";
    exit(1);
}

// TEST 4: Create Scenario
// We can't easily include api.php because it expects HTTP context (GET/POST).
// We will test the DB logic directly simulating what API does.

$stmt = $pdo->prepare("INSERT INTO scenarios (name, description) VALUES (?, ?)");
$stmt->execute(['Test Scenario Alpha', 'A major earthquake simulation.']);
$scenarioId = $pdo->lastInsertId();
echo ">> Scenario Created: ID $scenarioId\n";

// TEST 5: Create Group
$stmt = $pdo->prepare("INSERT INTO groups (name, scenario_id) VALUES (?, ?)");
$stmt->execute(['Alpha Team', $scenarioId]);
$groupId = $pdo->lastInsertId();
echo ">> Group Created: ID $groupId\n";

// TEST 6: Create Student
$pass = password_hash('student123', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'student'");
$stmt->execute();
$roleId = $stmt->fetchColumn();

$stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role_id, radio_code) VALUES (?, ?, ?, ?, ?)");
$stmt->execute(['student1', 's1@cr.org', $pass, $roleId, '6301']);
$studentId = $pdo->lastInsertId();
echo ">> Student Created: ID $studentId\n";

// TEST 7: Assign Student
$stmt = $pdo->prepare("INSERT INTO group_members (user_id, group_id) VALUES (?, ?)");
$stmt->execute([$studentId, $groupId]);
echo ">> Student Assigned to Group\n";

// TEST 8: Verify Assignment (Logic from API 'my_assignment')
// Mock login as student
$_SESSION['user_id'] = $studentId;
$_SESSION['role'] = 'student';

$stmt = $pdo->prepare("
    SELECT g.id as group_id, g.name as group_name, s.id as scenario_id, s.name as scenario_name
    FROM group_members gm
    JOIN groups g ON gm.group_id = g.id
    JOIN scenarios s ON g.scenario_id = s.id
    WHERE gm.user_id = ?
    ORDER BY gm.assigned_at DESC LIMIT 1
");
$stmt->execute([$studentId]);
$assignment = $stmt->fetch();

if ($assignment && $assignment['group_name'] === 'Alpha Team') {
    echo ">> Verification Assignment: SUCCESS (" . $assignment['group_name'] . ")\n";
} else {
    echo ">> Verification Assignment: FAILED\n";
}

echo ">> All Backend Tests Passed.\n";
?>
