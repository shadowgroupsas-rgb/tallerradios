<?php
require_once __DIR__ . '/auth.php';

// Set JSON header
header('Content-Type: application/json');

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    // Admin Actions
    if ($action === 'get_roles' && $method === 'GET') {
        require_role('admin');
        $stmt = $pdo->query("SELECT * FROM roles");
        echo json_encode($stmt->fetchAll());
        exit;
    }

    if ($action === 'get_users' && $method === 'GET') {
        require_role('admin');
        $stmt = $pdo->query("SELECT u.id, u.username, u.email, u.radio_code, u.radio_model, r.name as role FROM users u LEFT JOIN roles r ON u.role_id = r.id ORDER BY u.id DESC");
        echo json_encode($stmt->fetchAll());
        exit;
    }

    if ($action === 'create_user' && $method === 'POST') {
        require_role('admin');
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['username']) || empty($data['password']) || empty($data['role'])) {
            throw new Exception("Missing required fields");
        }

        // Get Role ID
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
        $stmt->execute([$data['role']]);
        $role_id = $stmt->fetchColumn();

        if (!$role_id) throw new Exception("Invalid role");

        $hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $radio_code = $data['radio_code'] ?? null;
        $radio_model = $data['radio_model'] ?? 'DEP450'; // Default

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role_id, radio_code, radio_model) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$data['username'], $data['email'], $hash, $role_id, $radio_code, $radio_model]);

        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        exit;
    }

    if ($action === 'delete_user' && $method === 'POST') {
        require_role('admin');
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$data['id']]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'get_scenarios' && $method === 'GET') {
        require_role(['admin', 'student', 'operator']);
        $stmt = $pdo->query("SELECT * FROM scenarios ORDER BY id DESC");
        echo json_encode($stmt->fetchAll());
        exit;
    }

    if ($action === 'create_scenario' && $method === 'POST') {
        require_role('admin');
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("INSERT INTO scenarios (name, description) VALUES (?, ?)");
        $stmt->execute([$data['name'], $data['description']]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        exit;
    }

    if ($action === 'get_groups' && $method === 'GET') {
        require_role(['admin', 'student', 'operator']);
        // If scenario_id is provided, filter
        $scenario_id = $_GET['scenario_id'] ?? null;
        if ($scenario_id) {
            $stmt = $pdo->prepare("SELECT * FROM groups WHERE scenario_id = ?");
            $stmt->execute([$scenario_id]);
        } else {
            $stmt = $pdo->query("SELECT * FROM groups");
        }
        echo json_encode($stmt->fetchAll());
        exit;
    }

    if ($action === 'create_group' && $method === 'POST') {
        require_role('admin');
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("INSERT INTO groups (name, scenario_id) VALUES (?, ?)");
        $stmt->execute([$data['name'], $data['scenario_id']]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        exit;
    }

    if ($action === 'get_group_members' && $method === 'GET') {
        require_role(['admin', 'student', 'operator']);
        $group_id = $_GET['group_id'] ?? null;
        if (!$group_id) throw new Exception("Group ID required");

        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.radio_code, u.radio_model
            FROM group_members gm
            JOIN users u ON gm.user_id = u.id
            WHERE gm.group_id = ?
        ");
        $stmt->execute([$group_id]);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    if ($action === 'assign_user' && $method === 'POST') {
        require_role('admin');
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("INSERT INTO group_members (user_id, group_id) VALUES (?, ?)");
        try {
            $stmt->execute([$data['user_id'], $data['group_id']]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            // Likely duplicate entry
            echo json_encode(['error' => 'User already in group']);
        }
        exit;
    }

    // User Specific Actions
    if ($action === 'my_assignment' && $method === 'GET') {
        require_role(['student', 'operator']);
        $user_id = $_SESSION['user_id'];

        // Get the most recent group assignment
        $stmt = $pdo->prepare("
            SELECT g.id as group_id, g.name as group_name, s.id as scenario_id, s.name as scenario_name, s.description
            FROM group_members gm
            JOIN groups g ON gm.group_id = g.id
            JOIN scenarios s ON g.scenario_id = s.id
            WHERE gm.user_id = ?
            ORDER BY gm.assigned_at DESC LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $assignment = $stmt->fetch();

        echo json_encode($assignment ?: null);
        exit;
    }

    throw new Exception("Invalid action or method");

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
