# CLAUDE.md

Este archivo proporciona orientación a Claude Code (claude.ai/code) cuando trabaja con el código en este repositorio.

## Entorno

Es una aplicación web PHP/MariaDB que corre sobre XAMPP (Apache + MariaDB). No tiene paso de compilación ni gestor de paquetes — todas las dependencias están incluidas en `libs/` (TCPDF) y `public/` (AdminLTE, Bootstrap, jQuery, etc.).

**Stack:** PHP 7.4+, MariaDB/MySQL, Apache con mod_rewrite, JavaScript vanilla (ES6+), Bootstrap 4 / AdminLTE 3.

## Ejecutar la Aplicación

Asegurarse de que XAMPP esté corriendo con Apache y MariaDB, luego navegar a:

```
http://localhost/FlowPOS/
```

Iniciar/detener los servicios de XAMPP:

```bash
sudo /opt/lampp/lampp start
sudo /opt/lampp/lampp stop
```

## Configuración de la Base de Datos

Importar el esquema (solo la primera vez o al resetear):

```bash
mysql -u root flowpos < schema.sql
```

El nombre de la base de datos es `flowpos` según lo configurado en `.env`.

Establecer permisos en los directorios de subida:

```bash
chmod 755 public/uploads/productos/ public/uploads/clientes/ public/uploads/usuarios/
```

## Configuración de Entorno

Ajustar los valores en `.env` — `config/env.php` lo carga automáticamente:

```
APP_NAME=FlowPOS
APP_VERSION=1.0.0
APP_CURRENCY=Bs
DB_HOST=localhost
DB_NAME=flowpos
DB_USER=root
DB_PASS=
APP_URL=http://localhost/FlowPOS/
TIMEZONE=America/La_Paz
DEBUG=true
```

`APP_URL` debe terminar con `/` y coincidir con la ruta URL real. Se usa en toda la app para redirecciones y enlaces a assets mediante la variable global `$URL`.

`APP_VERSION` define la versión funcional vigente del proyecto y debe mantenerse alineada con `CHANGELOG.md`.

## Versionado y Changelog

El proyecto usa versionado semántico (`MAJOR.MINOR.PATCH`) y documenta cambios en `CHANGELOG.md`.

- Mantener `APP_VERSION` sincronizado con la última versión publicada.
- Registrar cambios relevantes en la sección `Unreleased` y versionarlos al publicar.

## Arquitectura

La aplicación sigue un patrón MVC personalizado **sin router**. No hay un front controller que despache rutas — las vistas y controladores se incluyen directamente mediante `require`/`include` de PHP.

### Flujo de una Solicitud

1. **`index.php`** — punto de entrada autenticado; redirige a la vista de dashboard específica según el rol en `$_SESSION['usuario_cargo']`
2. **`views/layouts/session.php`** — incluido en prácticamente todas las páginas; inicia la sesión PHP, carga `.env`, define `$URL` y provee las funciones de autenticación (`isAuthenticated()`, `requireLogin()`, `requireRole()`, `getCurrentUser()`) y CSRF (`generateCSRFToken()`, `verifyCSRFToken()`, `csrfField()`, `csrfMetaTag()`, `getRequestCSRFToken()`, `requireCSRF()`, `getSafeRedirectBack()`)
3. **`views/layouts/header.php`** — incluido después de session; llama a `requireLogin()`, instancia `AuthorizationService`, renderiza el `<head>` y navbar de AdminLTE
4. **`views/layouts/footer.php`** y **`views/layouts/mensajes.php`** — cierran el HTML y renderizan mensajes flash de `$_SESSION['mensaje']` con SweetAlert2

### Rol de los Directorios

| Directorio                       | Propósito                                                                                                                                                                                                                                  |
| -------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `config/`                        | `env.php` (carga .env), `config.php` (retorna array de config), `conexion.php` (wrapper PDO Singleton)                                                                                                                                     |
| `models/`                        | Clases de acceso a datos (una por entidad); cada una instancia `Conexion::getInstance()`                                                                                                                                                   |
| `controllers/`                   | Clases controlador y scripts de acción por módulo (ej. `controllers/ventas/VentaController.php`, `controllers/ventas/crear_venta.php`)                                                                                                     |
| `views/`                         | Archivos de vista PHP organizados por módulo; incluyen controladores y modelos directamente según necesiten                                                                                                                                |
| `services/`                      | `AuthorizationService.php` (verificación de permisos por nombre/ID), `ImagenService.php` (subida y eliminación de imágenes), `RateLimiterService.php` (rate limiting de login por cuenta/IP), `literal.php` (conversión número a palabras) |
| `libs/`                          | Librerías de terceros empaquetadas — solo TCPDF para generación de PDFs                                                                                                                                                                    |
| `public/js/modules/`             | Archivos JavaScript por módulo (un subdirectorio por módulo)                                                                                                                                                                               |
| `public/js/core/common-utils.js` | Utilidades JS compartidas                                                                                                                                                                                                                  |
| `public/uploads/`                | Imágenes subidas por usuarios (productos/, clientes/, usuarios/)                                                                                                                                                                           |

### Conexión a la Base de Datos

`Conexion` en `config/conexion.php` es un Singleton que lee desde `config/config.php` (que a su vez lee `.env`). Se accede en cualquier lugar mediante:

```php
require_once __DIR__ . '/../../config/conexion.php';
$pdo = Conexion::getInstance()->getConnection();
```

### Autorización

Dos niveles de control de acceso:

1. **Por rol** (`requireRole(['administrador', 'supervisor'])`) — aplicado a nivel de página vía sesión
2. **Por permiso** (`AuthorizationService::tienePermiso()` / `tienePermisoNombre()`) — verificaciones granulares por acción, almacenadas en las tablas `permiso` y `permisousuario`. Los administradores evitan todas las verificaciones de permisos.

Roles de usuario: `administrador`, `supervisor`, `vendedor`.

### CSRF

Todo endpoint de escritura bajo `controllers/*/` que reciba `POST` debe invocar `requireCSRF();` (definido en `views/layouts/session.php`) como primera línea tras los `require_once`. Corta la ejecución con 403 JSON (AJAX) o redirect + mensaje flash (form tradicional) si el token falta o es inválido; no destruye la sesión activa.

- **Formularios tradicionales**: incluir `<?= csrfField() ?>` dentro de cada `<form method="post">`.
- **AJAX jQuery**: el token viaja automático vía header `X-CSRF-Token` gracias al `$.ajaxSetup` global en `public/js/core/common-utils.js`, que lo lee del meta tag `<?= csrfMetaTag() ?>` emitido en `views/layouts/header.php`.
- **AJAX con FormData** (uploads): `formData.append('csrf_token', csrfToken)` explícito, leyendo el token del meta tag.
- **Acciones que mutan estado invocadas antes por GET** (desactivar, cambiar estado, anular, cerrar sesión): usar `submitCsrfForm(action, fields)` de `common-utils.js`, que construye y envía un formulario oculto por POST con el token incluido — nunca `window.location.href` a un endpoint de escritura.

### Tablas Principales de la Base de Datos

- `empresa`, `sucursal` — estructura empresa/sucursal (preparada para uso multi-tenant futuro)
- `usuarios` — usuarios con `cargo` (rol) e `idsucursal`
- `permiso`, `permisousuario` — sistema de permisos granular
- `producto`, `categoria` — catálogo de productos con control de stock
- `cliente` — registro de clientes
- `venta`, `detalleventa`, `pagoventa` — ventas con líneas de detalle y métodos de pago mixtos (efectivo, tarjeta, QR, transferencia)
- `compra`, `detallecompra` — ingreso de mercadería/inventario
- `sesionusuario` — registro de auditoría de sesiones
- `intento_login` — registro de intentos de login (éxito/fallo) para rate limiting por cuenta e IP

### Datos de Dashboard vía AJAX

Los dashboards por rol (`views/dashboard/dashboard*.php`) no reciben datos precargados desde el controlador de vista — el JS del módulo hace `fetch()` a un endpoint dedicado en `controllers/dashboard/` (ej. `get_general_dashboard_data.php`) que devuelve JSON. Cada sección del payload se condiciona con `AuthorizationService::tienePermisoNombre()`: si el usuario no tiene el permiso, la clave se omite del JSON (no se envía vacía ni con datos parciales) y el frontend muestra "Sin acceso a esta información" en su lugar. Al agregar una sección nueva a un dashboard, seguir este mismo patrón de permiso-por-sección en el endpoint, no en la vista.

CSS compartido entre los 3 dashboards por rol vive en `public/css/modules/dashboard/dashboard.css` — evitar duplicar `<style>` inline por vista; agregar ahí lo que aplique a más de un rol.

### Carga Condicional de Librerías Pesadas

`header.php`/`footer.php` cargan DataTables y Select2 por defecto. Si una vista no usa alguna, declarar antes de `include_once 'header.php'`:

```php
$skip_datatables = true; // Sin tabla; evita cargar DataTables/pdfmake/vfs_fonts (~2.8MB)
$skip_select2 = true;    // Sin Select2
```

Antes de marcar `skip_select2`, confirmar que ningún `<select class="select2">` dependa de `initializeSelect2()` — la ausencia de un JS de módulo no es evidencia suficiente. Si `header.php` se incluye antes de saber el contexto (p. ej. `index.php` despachando por rol), declarar los flags ahí mismo.

### Utilidades CSS Compartidas (`public/css/core/common.css`)

- `.sidebar-sticky` — columna lateral fija al hacer scroll (`position: sticky`, `static` bajo 767.98px).
- `.card-outline-tabs .nav-link:not(.active)` — color info en pestañas inactivas de cualquier card con tabs (vistas "show"), sin CSS por módulo.
- `common.css` y `module_styles` versionados por query string (`?v=<?= $appVersion ?>`) igual que `module_scripts`, para evitar caché obsoleta.

### Mensajes Flash

Se asignan en `$_SESSION['mensaje']` (cadena de texto) y `$_SESSION['icono']` (ícono de SweetAlert2: `success`, `error`, `warning`, `info`). Los renderiza `views/layouts/mensajes.php`.

### Generación de PDFs

Los recibos se generan con TCPDF desde `libs/TCPDF-main/`. Los PDFs se emiten marcados como "sin valor fiscal".

### Errores conocidos y lecciones aprendidas

- **`json_encode(array_filter(...))` sin reindexar produce un objeto JS, no un array**: `array_filter()` preserva las claves originales del array; si estas dejan de ser secuenciales desde 0, `json_encode()` serializa el resultado como objeto (`{"2":...,"5":...}`) en vez de array (`[...]`). Cualquier JS que reciba ese dato vía `data-*`/`.data()` y llame `.forEach()`/`.map()` sobre él falla con `TypeError: x.forEach is not a function`. Envolver siempre con `array_values()` antes de `json_encode()` cuando el array pasó por `array_filter()` (ver `views/ventas/create.php`, `data-productos`).
- **Tablas anchas (5+ columnas) en vistas con inputs por celda no deben depender solo de `.table-responsive`**: el scroll horizontal oculta columnas fuera de la vista sin indicarlo, mala UX en móvil. Para tablas editables tipo carrito (`views/ventas/create.php`, `#tabla-productos`), usar un breakpoint (`@media (max-width: 767.98px)`) que convierta `<tr>`/`<td>` a `display: block` con `data-label` en cada `<td>` para mostrar la etiqueta de columna como pseudo-elemento `::before`, formando tarjetas apiladas en vez de forzar scroll lateral.
- **Patrón "escape-at-storage" (deuda técnica conocida)**: `sanitizarDatos()` en los modelos (`Venta`, `Cliente`, `Producto`, `Usuario`, `Empresa`, `Sucursal`) aplica `htmlspecialchars()` a todos los strings **al guardar**, no al mostrar. Por eso las vistas (`show.php`, `recibo.php` de ventas, etc.) deben interpolar esos campos **sin volver a escaparlos** — hacerlo produce doble escape visible (`&lt;b&gt;` literal en vez de texto legible). Verificado en `views/ventas/show.php` y `views/ventas/recibo.php` con `php -l` y prueba manual en navegador (payload `<script>alert(1)</script>` en observación: se muestra como texto plano, sin duplicar entidades). Riesgo conocido: si algún endpoint futuro escribe en estas tablas sin pasar por `sanitizarDatos()`, ese dato quedaría sin escapar y se renderizaría crudo → XSS almacenado. Mientras este patrón siga vigente, cualquier INSERT/UPDATE nuevo sobre estas tablas debe pasar por el `sanitizarDatos()` del modelo correspondiente; no agregar `htmlspecialchars()` en las vistas como "defensa extra" sin antes confirmar si el dato ya viene escapado. Migrar todo el proyecto a "escapar al mostrar" (quitar el escape de `sanitizarDatos()` y agregarlo en cada punto de salida) es la solución de fondo, pero es un refactor transversal pendiente de decisión, no parte de ninguna auditoría de módulo individual.
