# Sistema de Simulación de Radio - Cruz Roja

Este proyecto es una plataforma web interactiva para la instrucción de telemática y comunicaciones en la Cruz Roja. Permite la simulación de radios Motorola DEP450 y DEM500 en un entorno 3D con comunicación de voz en tiempo real por canales.

## Características

*   **Roles:** Administrador, Estudiante, Operador de Central.
*   **Gestión:** Creación de usuarios, escenarios y grupos de trabajo.
*   **Simulación 3D:** Modelos interactivos de radios DEP450 (Portátil) y DEM500 (Base).
*   **Audio PTT:** Comunicación "Push-to-Talk" con lógica de canales.
*   **Tecnología:** PHP (Backend), MySQL (Base de datos), Three.js (3D), WebRTC (Audio), Node.js (Signaling).

## Requisitos del Servidor

*   **PHP:** 7.4 o superior.
*   **MySQL:** 5.7 o superior.
*   **Node.js:** 14.x o superior (Para el servidor de señalización).
*   **SSL (HTTPS):** Obligatorio para el acceso al micrófono (WebRTC).

## Instrucciones de Despliegue en cPanel

### 1. Base de Datos
1.  Ingrese a **cPanel > Bases de Datos MySQL**.
2.  Cree una nueva base de datos (ej: `cruzroja_radio`).
3.  Cree un usuario y asigne todos los privilegios a esa base de datos.
4.  Ingrese a **phpMyAdmin**, seleccione la base de datos e importe el archivo `database.sql` ubicado en la raíz del proyecto.

### 2. Archivos Web (Backend PHP)
1.  Suba todos los archivos de la carpeta `public`, `src`, `config` y `vendor` (si aplica) a `public_html` (o a un subdominio).
2.  Edite el archivo `config/db.php` con las credenciales de su base de datos:
    ```php
    $host = 'localhost';
    $db   = 'cruzroja_radio';
    $user = 'usuario_db';
    $pass = 'contraseña_db';
    ```

### 3. Servidor de Señalización (Node.js)
El audio en tiempo real requiere un pequeño servidor de "señalización" para conectar a los usuarios.

**Opción A: cPanel "Setup Node.js App" (Recomendado)**
1.  En cPanel, busque **Setup Node.js App**.
2.  Cree una nueva aplicación.
    *   **Application Root:** `server` (Suba la carpeta `server` a la raíz de su hosting, fuera de `public_html` si es posible para seguridad, o dentro).
    *   **Application URL:** `radio-signal` (o similar).
    *   **Startup File:** `index.js`.
3.  Haga clic en **Run NPM Install**.
4.  Inicie la aplicación.
5.  Copie la URL de la aplicación Node.js.
6.  Edite el archivo `public/simulation.php` (o `public/js/radio.js` si prefiere) y actualice la línea:
    ```javascript
    window.SIGNALING_SERVER = 'https://su-dominio.com/radio-signal';
    // O la URL que le dio cPanel (a veces es puerto específico si no usa Proxy Pass)
    ```

**Opción B: Servidor Externo / VPS**
Si su cPanel no soporta Node.js, puede alojar la carpeta `server` en un VPS gratuito (ej: Glitch, Heroku, Railway) y apuntar `window.SIGNALING_SERVER` a esa URL.

## Uso Local (Desarrollo)

1.  Instale dependencias de Node:
    ```bash
    cd server
    npm install
    node index.js
    ```
2.  Configure `config/db.php` (o use SQLite para pruebas rápidas).
3.  Inicie un servidor PHP en la carpeta `public`:
    ```bash
    php -S localhost:8000 -t public
    ```
4.  Abra `http://localhost:8000`.
5.  **Usuario Admin por defecto:**
    *   Usuario: `admin`
    *   Contraseña: `admin123`

## Licencia
Código abierto para uso educativo en Cruz Roja.
