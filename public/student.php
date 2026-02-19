<?php
require_once __DIR__ . '/../src/auth.php';
require_login();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Estudiante - Cruz Roja</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="navbar">
    <div style="font-family: 'Orbitron'; color: var(--primary); font-size: 1.5rem;">
        OPERATIVO :: <?= htmlspecialchars($_SESSION['username']) ?>
    </div>
    <div>
        <span style="color: gray; margin-right: 15px;">Radio: <?= htmlspecialchars($_SESSION['radio_code']) ?> (<?= htmlspecialchars($_SESSION['radio_model']) ?>)</span>
        <a href="logout.php" class="nav-link" style="color: var(--danger);">Salir</a>
    </div>
</nav>

<div class="container mt-4">
    <div class="glass-panel" id="loading">
        Cargando asignación...
    </div>

    <div id="assignment-content" class="hidden">
        <div class="glass-panel" style="border-left: 5px solid var(--secondary);">
            <h2 id="scenario-name">Escenario</h2>
            <p id="scenario-desc" style="font-size: 1.1em; color: #555;"></p>
        </div>

        <div style="display: flex; gap: 20px;">
            <div class="glass-panel" style="flex: 1;">
                <h3>Su Grupo</h3>
                <h1 id="group-name" style="color: var(--primary); font-size: 3em;">-</h1>
                <p>Usted está asignado a este grupo operativo.</p>
            </div>

            <div class="glass-panel" style="flex: 1; text-align: center; display: flex; flex-direction: column; justify-content: center;">
                <h3>Sala de Simulación</h3>
                <p>Acceda al radio virtual y canal de comunicación.</p>
                <a id="sim-link" href="#" target="_blank">
                    <button style="font-size: 1.5em; padding: 20px 40px; margin-top: 20px;">
                        INGRESAR A SALA
                    </button>
                </a>
            </div>
        </div>

        <div class="glass-panel">
             <h3>Miembros del Grupo</h3>
             <div id="group-members"></div>
        </div>
    </div>

    <div id="no-assignment" class="glass-panel hidden" style="text-align: center; color: var(--danger);">
        <h2>Sin Asignación Activa</h2>
        <p>Contacte a su instructor para ser asignado a un escenario y grupo.</p>
    </div>
</div>

<script>
const API = '../src/api.php';

async function loadAssignment() {
    try {
        const res = await fetch(`${API}?action=my_assignment`);
        const data = await res.json();

        document.getElementById('loading').classList.add('hidden');

        if (data && data.scenario_id) {
            document.getElementById('assignment-content').classList.remove('hidden');
            document.getElementById('scenario-name').innerText = data.scenario_name;
            document.getElementById('scenario-desc').innerText = data.description;
            document.getElementById('group-name').innerText = data.group_name;

            // Link to simulation: pass scenario ID and Group ID?
            // Actually, audio routing depends on Channel (Frequency), not strict Group ID,
            // but we can pass Group ID to default them to a channel or visualize teammates.
            // Let's pass scenario_id and group_id.
            document.getElementById('sim-link').href = `simulation.php?scenario=${data.scenario_id}&group=${data.group_id}`;

            // Load members
            const mRes = await fetch(`${API}?action=get_group_members&group_id=${data.group_id}`);
            const members = await mRes.json();
            const memDiv = document.getElementById('group-members');
            members.forEach(m => {
                memDiv.innerHTML += `
                    <div style="display: inline-block; background: #fff; padding: 10px; margin: 5px; border: 1px solid var(--primary); box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                        <strong>${m.username}</strong><br>
                        <small>Radio: ${m.radio_code}</small>
                    </div>
                `;
            });

        } else {
            document.getElementById('no-assignment').classList.remove('hidden');
        }
    } catch (e) {
        console.error(e);
        document.getElementById('loading').innerText = "Error cargando datos.";
    }
}

loadAssignment();
</script>

</body>
</html>
