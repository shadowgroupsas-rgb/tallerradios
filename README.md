# Sistema de Simulación de Radio - Cruz Roja

Plataforma web para entrenamiento de telemática, diseñada para un despliegue sencillo en cPanel.

## Características

*   **Autoinstalador Web:** Configuración guiada de base de datos y usuario administrador.
*   **Roles:** Administrador, Estudiante, Operador de Central.
*   **Simulación 3D:** Modelos DEP450 y DEM500.
*   **Audio PTT:** WebRTC en tiempo real.

## Instrucciones de Instalación en cPanel

### 1. Preparar Base de Datos
1.  En cPanel, vaya a **Bases de Datos MySQL**.
2.  Cree una nueva base de datos (ej: `cruzroja_radio`).
3.  Cree un usuario MySQL y asígnele permisos completos sobre la base de datos creada.
4.  **No necesita importar SQL manualmente**, el instalador lo hará por usted.

### 2. Subir Archivos
1.  Suba el contenido de este repositorio a `public_html` (o subdominio).
2.  Asegúrese de que la carpeta `public` sea el directorio raíz web (Document Root), o mueva el contenido de `public` a la raíz y las carpetas `src`, `config`, `server` al nivel superior para mayor seguridad.
    *   *Nota para Hosting Compartido:* Si no puede cambiar el Document Root, puede subir todo y acceder a `su-dominio.com/public/`.

### 3. Ejecutar Instalador
1.  Abra su navegador y vaya a la URL de su sitio (ej: `https://su-dominio.com/public/`).
2.  Será redirigido automáticamente a la pantalla de instalación.
3.  Ingrese los datos de la base de datos (Host, Nombre, Usuario, Contraseña) que creó en el paso 1.
4.  Defina su usuario y contraseña de Administrador.
5.  (Opcional) Ingrese la URL de su servidor de señalización Node.js (ver abajo).
6.  Haga clic en **Instalar**.
7.  Al finalizar, elimine la carpeta `public/install` por seguridad.

### 4. Servidor de Señalización (Node.js)
El audio requiere un servidor Node.js.
1.  En cPanel -> **Setup Node.js App**.
2.  Cree una app apuntando a la carpeta `server`.
3.  Instale dependencias (`npm install`).
4.  Copie la URL de la app y péguela en el instalador web (Paso 3) o edite `public/js/config.js` posteriormente.

## Requisitos
*   PHP 7.4+
*   MySQL 5.7+
*   Node.js 14+ (Para audio)
*   HTTPS (Requerido para micrófono)
