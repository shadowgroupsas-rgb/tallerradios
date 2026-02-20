# Sistema de Simulación de Radio - Cruz Roja (cPanel)

## Características

*   **Roles:** Administrador, Estudiante, Operador de Central.
*   **Simulación 3D:** Modelos DEP450 y DEM500.
*   **Audio PTT:** WebRTC en tiempo real.
*   **Autoinstalador Web:** Configuración rápida.

## Instrucciones de Instalación en cPanel

### 1. Preparación de Archivos
1.  Descargue el archivo `cruzroja_radio_deploy.zip`.
2.  Extraiga este archivo en su computadora local. Verá dos carpetas principales: `public_html` y `server`.

### 2. Configurar la Aplicación Node.js (Audio)
**IMPORTANTE:** Este paso es crítico para que el audio funcione.

1.  En cPanel, vaya al Administrador de Archivos.
2.  Navegue a su carpeta raíz (generalmente `/home/su_usuario/`).
3.  Cree una carpeta llamada `server`.
4.  Suba el contenido de la carpeta `server` extraída (archivos `index.js`, `package.json`, `package-lock.json`) dentro de esta carpeta `/home/su_usuario/server`.
    *   **Verifique:** Asegúrese de que `package.json` esté visible en esa carpeta.
5.  Vuelva al panel principal de cPanel y busque **Setup Node.js App**.
6.  Haga clic en **Create Application**.
    *   **Node.js Version:** 14.x o superior.
    *   **Application mode:** Production.
    *   **Application root:** `server` (esto apunta a la carpeta que acaba de crear).
    *   **Application URL:** `radio-signal` (o cualquier nombre, ej: `app`).
    *   **Application startup file:** `index.js`.
7.  Haga clic en **Create**.
8.  Una vez creada, si ve un botón **Run NPM Install**, haga clic en él. Esto instalará las dependencias necesarias.
    *   *Si el botón está deshabilitado o muestra advertencia, verifique que `package.json` esté en la carpeta `server`.*
9.  Copie la URL completa de su aplicación (ej: `https://sudominio.com/radio-signal`) para usarla en el paso 4.

### 3. Subir Archivos Web (PHP)
1.  En el Administrador de Archivos, vaya a `public_html`.
2.  Suba todo el contenido de la carpeta `public_html` extraída del zip (`index.php`, `admin.php`, carpetas `src`, `config`, etc.).
3.  Asegúrese de que `index.php` esté en la raíz de `public_html` (o en la subcarpeta donde quiera instalar el sistema).

### 4. Ejecutar el Instalador
1.  Abra su navegador y visite su sitio web (ej: `https://sudominio.com`).
2.  Será redirigido al instalador.
3.  Ingrese los datos de su base de datos MySQL (créela en cPanel > Bases de Datos MySQL si aún no lo ha hecho).
4.  Ingrese la URL de la aplicación Node.js que copió en el paso 2 (ej: `https://sudominio.com/radio-signal`).
5.  Complete la instalación.

## Solución de Problemas
*   **Error "package.json missing":** Asegúrese de subir la carpeta `server` FUERA de `public_html` y que contenga el archivo `package.json`.
*   **Error de conexión DB:** Verifique usuario y contraseña en cPanel.
