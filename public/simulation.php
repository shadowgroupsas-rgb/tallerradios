<?php
require_once __DIR__ . '/../src/auth.php';
require_login();

$scenario_id = $_GET['scenario'] ?? 0;
$group_id = $_GET['group'] ?? 0;
$user = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'role' => $_SESSION['role'],
    'radio_code' => $_SESSION['radio_code'],
    'radio_model' => $_SESSION['radio_model']
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sala de Simulación - Radio <?= htmlspecialchars($user['radio_model']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { margin: 0; overflow: hidden; background: #f0f0f0; } /* Light background for 3D */
        #simulation-container { width: 100vw; height: 100vh; border: none; }
        #hud {
            position: absolute; top: 20px; left: 20px; pointer-events: none;
            font-family: 'Orbitron', monospace; color: var(--primary);
        }
        /* HUD Panel style overrides style.css for 3D context if needed, but style.css is already good */

        #channel-display { font-size: 2em; color: var(--primary); text-align: center; }

        /* Floating PTT Button */
        #ptt-btn {
            position: absolute; bottom: 50px; right: 50px;
            width: 100px; height: 100px; border-radius: 50%;
            background: #ce1126; /* Red */
            border: 5px solid #fff;
            color: white; font-weight: bold; cursor: pointer;
            z-index: 100;
            display: flex; align-items: center; justify-content: center;
            user-select: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            font-size: 1.2em;
        }
        #ptt-btn:active, #ptt-btn.active {
            background: #a60e1e; /* Darker Red */
            transform: scale(0.95);
        }
        #log-panel {
            position: absolute; bottom: 20px; left: 20px; width: 300px; height: 150px;
            overflow-y: auto; font-size: 0.8em; color: #333;
        }
        /* Operator Controls */
        #operator-controls {
            position: absolute; top: 20px; right: 20px; width: 250px;
        }
    </style>
</head>
<body>

<div id="hud">
    <div class="hud-panel">
        <div>RADIO: <strong><?= htmlspecialchars($user['radio_code']) ?></strong></div>
        <div>MODELO: <?= htmlspecialchars($user['radio_model']) ?></div>
        <hr style="border-color: #333">
        <div style="font-size: 0.8em; color: gray;">CANAL ACTUAL</div>
        <div id="channel-display">CH 01</div>
        <div style="font-size: 0.8em; text-align: center; margin-top: 5px;" id="freq-display">446.000 MHz</div>
    </div>

    <div class="hud-panel" id="status-panel">
        STATUS: <span id="status-text" style="color: #28a745; font-weight: bold;">ONLINE</span>
    </div>
</div>

<div id="log-panel" class="hud-panel">
    <div>SYSTEM LOG:</div>
    <div id="logs"></div>
</div>

<!-- Only show big PTT button for mobile or ease of use, though 3D model has one too -->
<div id="ptt-btn">PTT</div>

<?php if ($user['role'] === 'operator' || $user['role'] === 'admin'): ?>
<div id="operator-controls" class="hud-panel">
    <h4>Central Operator</h4>
    <label>Monitor Channel:</label>
    <select id="monitor-channel" onchange="window.radioLogic.setChannel(this.value)">
        <?php for($i=1; $i<=16; $i++): ?>
            <option value="<?= $i ?>">Channel <?= $i ?></option>
        <?php endfor; ?>
    </select>
    <div style="margin-top: 10px; font-size: 0.8em; color: gray;">
        Como Operador DEM500, usted puede monitorear cualquier canal.
    </div>
</div>
<?php endif; ?>

<div id="simulation-container"></div>

<!-- Data passed to JS -->
<script>
    window.USER_DATA = <?= json_encode($user) ?>;
    window.SCENARIO_ID = <?= json_encode($scenario_id) ?>;
    window.GROUP_ID = <?= json_encode($group_id) ?>;

    // Config for Socket.io (Change this to your Node server URL)
    // For cPanel, this might be the same domain if proxied, or a specific port.
    // We default to port 3000 for this demo.
    window.SIGNALING_SERVER = window.location.protocol + '//' + window.location.hostname + ':3000';
</script>

<!-- Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.socket.io/4.5.0/socket.io.min.js"></script>
<!-- PeerJS for WebRTC audio ease (fallback if we don't build full mesh manually) -->
<!-- We will try to build manual WebRTC logic in radio.js to keep it dependency-light on the server side,
     or use PeerJS cloud. Let's stick to manual WebRTC via Socket.io signaling to fulfill "custom" requirement without external API keys if possible. -->

<script src="js/models.js"></script>
<script src="js/radio.js"></script>

</body>
</html>
