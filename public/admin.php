<?php
// public/admin.php

// Load Auth (Support Flat/Nested)
if (file_exists(__DIR__ . '/src/auth.php')) {
    require_once __DIR__ . '/src/auth.php';
} else {
    require_once __DIR__ . '/../src/auth.php';
}

require_role('admin');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Cruz Roja</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>

<nav class="navbar">
    <div style="font-family: 'Orbitron'; color: var(--primary); font-size: 1.5rem;">
        COMANDOS :: ADMIN
    </div>
    <div>
        <a href="#" class="nav-link" onclick="showTab('users')">Usuarios</a>
        <a href="#" class="nav-link" onclick="showTab('scenarios')">Escenarios</a>
        <a href="logout.php" class="nav-link" style="color: var(--danger);">Salir</a>
    </div>
</nav>

<div class="container mt-4">

    <!-- USERS TAB -->
    <div id="users" class="tab-content active">
        <div class="glass-panel">
            <h3>Gestión de Personal</h3>
            <div style="display: flex; gap: 20px;">
                <!-- List -->
                <div style="flex: 2;">
                    <table>
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Radio Model</th>
                                <th>Radio Code</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="user-list">
                            <!-- JS populates -->
                        </tbody>
                    </table>
                </div>

                <!-- Create Form -->
                <div style="flex: 1; border-left: 1px solid var(--primary); padding-left: 20px;">
                    <h4>Nuevo Usuario</h4>
                    <form id="create-user-form">
                        <input type="text" name="username" placeholder="Nombre Usuario" required>
                        <input type="email" name="email" placeholder="Correo" required>
                        <input type="password" name="password" placeholder="Contraseña" required>
                        <select name="role" required>
                            <option value="student">Estudiante</option>
                            <option value="operator">Operador (Central)</option>
                            <option value="admin">Administrador</option>
                        </select>
                        <input type="text" name="radio_code" placeholder="Código Radio (ej: 6301)">
                        <select name="radio_model">
                            <option value="DEP450">Motorola DEP450 (Portátil)</option>
                            <option value="DEM500">Motorola DEM500 (Base)</option>
                        </select>
                        <button type="submit">Crear Usuario</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- SCENARIOS TAB -->
    <div id="scenarios" class="tab-content">
        <div class="glass-panel">
            <h3>Escenarios y Grupos</h3>

            <div style="display: flex; gap: 20px;">
                <!-- Create Scenario -->
                <div style="flex: 1;">
                    <h4>Crear Escenario</h4>
                    <form id="create-scenario-form">
                        <input type="text" name="name" placeholder="Nombre Escenario" required>
                        <textarea name="description" placeholder="Descripción de la misión..." rows="4" style="width: 100%;"></textarea>
                        <button type="submit">Crear Escenario</button>
                    </form>

                    <h4 class="mt-4">Escenarios Existentes</h4>
                    <ul id="scenario-list" style="list-style: none; padding: 0;"></ul>
                </div>

                <!-- Manage Groups -->
                <div style="flex: 2; border-left: 1px solid var(--primary); padding-left: 20px;">
                    <h4>Gestión de Grupos</h4>
                    <select id="scenario-select" onchange="loadGroups()">
                        <option value="">Seleccione Escenario...</option>
                    </select>

                    <div id="group-management" class="hidden">
                        <div style="margin-top: 10px; border: 1px solid rgba(255,255,255,0.1); padding: 10px;">
                            <h5>Nuevo Grupo</h5>
                            <form id="create-group-form" style="display: flex; gap: 10px;">
                                <input type="text" name="name" placeholder="Nombre Grupo (ej: Alpha)" required>
                                <button type="submit">Crear</button>
                            </form>
                        </div>

                        <h5 class="mt-4">Grupos en este Escenario</h5>
                        <div id="groups-list"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal for Assigning User (Simple implementation) -->
<div id="assign-modal" class="glass-panel hidden" style="position: fixed; top: 20%; left: 30%; width: 40%; z-index: 100;">
    <h4>Asignar Miembro al Grupo</h4>
    <select id="assign-user-select"></select>
    <input type="hidden" id="assign-group-id">
    <div class="mt-4 text-right">
        <button onclick="closeAssignModal()" class="danger">Cancelar</button>
        <button onclick="submitAssignment()">Asignar</button>
    </div>
</div>

<script>
// Determine API Path (Flat vs Nested)
// In flat structure (public_html), api.php is in src/api.php
// But we are at index.php (root).
// Wait, client side JS needs correct URL.
// If index.php is at root, and api.php is in src/api.php, then URL is src/api.php.
// If index.php is at public/index.php and api.php is src/api.php (sibling of public), we can't access it via HTTP unless src is public?
// This is why standard Laravel/etc put index.php in public and everything else outside.
// But cPanel simple deploy puts everything in public_html.
// So src/api.php IS accessible via public_html/src/api.php.
// BUT, if we use Dev mode (public/index.php), src is ../src/api.php which is NOT accessible via HTTP typically unless VHOST points to root.
// Assuming "Flat Deployment" puts everything in public_html:
const API = 'src/api.php';
// If this fails in Dev mode (where public is root), we might need a router or just accept dev mode requires different URL.
// Let's try to detect? No, client side can't detect easily.
// We can inject it via PHP.
</script>
<script>
    const API_URL = '<?= file_exists(__DIR__ . "/src/api.php") ? "src/api.php" : "../src/api.php" ?>';
    // Wait, if it's ../src/api.php, the browser can't request "../src/api.php".
    // It implies the web server root is the parent.
    // If the web server root is public/, then src/ is not accessible.
    // This is a known issue with the dual structure.
    // However, for the "Autoinstaller" goal (cPanel), we assume Flat structure.
    // So src/api.php is correct.
</script>
<script>
const API = 'src/api.php'; // Default to flat structure for production

function showTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    if(tabId === 'users') loadUsers();
    if(tabId === 'scenarios') loadScenarios();
}

// USERS
async function loadUsers() {
    const res = await fetch(`${API}?action=get_users`);
    const users = await res.json();
    const tbody = document.getElementById('user-list');
    tbody.innerHTML = '';
    users.forEach(u => {
        tbody.innerHTML += `
            <tr>
                <td>${u.username}<br><small style="color:gray">${u.email}</small></td>
                <td>${u.role}</td>
                <td>${u.radio_model || '-'}</td>
                <td>${u.radio_code || '-'}</td>
                <td><button class="danger" onclick="deleteUser(${u.id})">X</button></td>
            </tr>
        `;
    });
}

document.getElementById('create-user-form').onsubmit = async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const res = await fetch(`${API}?action=create_user`, {
        method: 'POST', body: JSON.stringify(data)
    });
    if(res.ok) {
        e.target.reset();
        loadUsers();
    } else {
        alert("Error creating user");
    }
};

async function deleteUser(id) {
    if(!confirm('Seguro?')) return;
    await fetch(`${API}?action=delete_user`, {
        method: 'POST', body: JSON.stringify({id})
    });
    loadUsers();
}

// SCENARIOS
async function loadScenarios() {
    const res = await fetch(`${API}?action=get_scenarios`);
    const scenarios = await res.json();

    // List
    const ul = document.getElementById('scenario-list');
    ul.innerHTML = '';
    scenarios.forEach(s => {
        ul.innerHTML += `<li style="padding: 10px; border-bottom: 1px solid #333; cursor: pointer" onclick="selectScenario(${s.id})">
            <strong>${s.name}</strong><br><small>${s.description.substring(0,50)}...</small>
        </li>`;
    });

    // Select
    const sel = document.getElementById('scenario-select');
    sel.innerHTML = '<option value="">Seleccione Escenario...</option>';
    scenarios.forEach(s => {
        sel.innerHTML += `<option value="${s.id}">${s.name}</option>`;
    });
}

document.getElementById('create-scenario-form').onsubmit = async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const res = await fetch(`${API}?action=create_scenario`, {
        method: 'POST', body: JSON.stringify(data)
    });
    loadScenarios();
    e.target.reset();
};

function selectScenario(id) {
    document.getElementById('scenario-select').value = id;
    loadGroups();
}

// GROUPS
let currentScenarioId = null;

async function loadGroups() {
    const id = document.getElementById('scenario-select').value;
    currentScenarioId = id;
    const div = document.getElementById('group-management');
    if(!id) {
        div.classList.add('hidden');
        return;
    }
    div.classList.remove('hidden');

    const res = await fetch(`${API}?action=get_groups&scenario_id=${id}`);
    const groups = await res.json();
    const list = document.getElementById('groups-list');
    list.innerHTML = '';

    for (const g of groups) {
        // Fetch members
        const mRes = await fetch(`${API}?action=get_group_members&group_id=${g.id}`);
        const members = await mRes.json();
        const memberNames = members.map(m => `<span style="background: var(--primary); color: black; padding: 2px 5px; border-radius: 4px; font-size: 0.8em; margin-right: 5px;">${m.username} (${m.radio_code})</span>`).join('');

        list.innerHTML += `
            <div style="background: #f9f9f9; border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-left: 3px solid var(--secondary);">
                <div style="display: flex; justify-content: space-between;">
                    <strong>${g.name}</strong>
                    <button onclick="openAssignModal(${g.id})" style="font-size: 0.8em; padding: 5px;">+ Miembro</button>
                </div>
                <div style="margin-top: 5px;">${memberNames || '<em style="color:#666">Sin miembros</em>'}</div>
            </div>
        `;
    }
}

document.getElementById('create-group-form').onsubmit = async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    data.scenario_id = currentScenarioId;
    await fetch(`${API}?action=create_group`, {
        method: 'POST', body: JSON.stringify(data)
    });
    loadGroups();
    e.target.reset();
};

// ASSIGNMENT
async function openAssignModal(groupId) {
    document.getElementById('assign-group-id').value = groupId;
    document.getElementById('assign-modal').classList.remove('hidden');

    // Load users
    const res = await fetch(`${API}?action=get_users`);
    const users = await res.json();
    const sel = document.getElementById('assign-user-select');
    sel.innerHTML = '';
    users.forEach(u => {
        sel.innerHTML += `<option value="${u.id}">${u.username} (${u.role}) - ${u.radio_code}</option>`;
    });
}

function closeAssignModal() {
    document.getElementById('assign-modal').classList.add('hidden');
}

async function submitAssignment() {
    const userId = document.getElementById('assign-user-select').value;
    const groupId = document.getElementById('assign-group-id').value;

    await fetch(`${API}?action=assign_user`, {
        method: 'POST',
        body: JSON.stringify({user_id: userId, group_id: groupId})
    });

    closeAssignModal();
    loadGroups();
}

// Init
showTab('users');
</script>

</body>
</html>
