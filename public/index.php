<?php
require_once __DIR__ . '/../src/auth.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin.php");
    } else {
        header("Location: student.php");
    }
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (login($username, $password)) {
        if ($_SESSION['role'] === 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: student.php");
        }
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Comunicaciones - Cruz Roja</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { font-family: 'Orbitron', sans-serif; }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-box glass-panel">
        <h2>Acceso al Sistema</h2>
        <p>Departamento de Telemática</p>

        <?php if ($error): ?>
            <div style="color: var(--danger); margin-bottom: 10px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="username" placeholder="Usuario / Correo" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit" style="width: 100%;">Iniciar Sesión</button>
        </form>

        <div style="margin-top: 20px; font-size: 0.8em; color: gray;">
            Simulación de Radio de Emergencias
        </div>
    </div>
</div>

</body>
</html>
