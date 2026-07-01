# CLAUDE.md

Este archivo proporciona orientación a Claude Code (claude.ai/code) cuando trabaja con el código en este repositorio.

## Entorno

Es una aplicación web PHP/MariaDB que corre sobre XAMPP (Apache + MariaDB). No tiene paso de compilación ni gestor de paquetes — todas las dependencias están incluidas en `libs/` (TCPDF) y `public/` (AdminLTE, Bootstrap, jQuery, etc.).

**Stack:** PHP 7.4+, MariaDB/MySQL, Apache con mod_rewrite, JavaScript vanilla (ES6+), Bootstrap 4 / AdminLTE 3.

## Ejecutar la Aplicación

Asegurarse de que XAMPP esté corriendo con Apache y MariaDB, luego navegar a:

```
http://localhost/sistema-ventas/
```

Iniciar/detener los servicios de XAMPP:

```bash
sudo /opt/lampp/lampp start
sudo /opt/lampp/lampp stop
```

## Configuración de la Base de Datos

Importar el esquema (solo la primera vez o al resetear):

```bash
mysql -u root sistema_ventas < schema.sql
```

El nombre de la base de datos es `sistema_ventas` según lo configurado en `.env`.

Establecer permisos en los directorios de subida:

```bash
chmod 755 public/uploads/productos/ public/uploads/clientes/ public/uploads/usuarios/
```

## Configuración de Entorno

Ajustar los valores en `.env` — `config/env.php` lo carga automáticamente:

```
APP_NAME=Mi Sistema de Ventas
APP_VERSION=1.0.0
APP_CURRENCY=Bs
DB_HOST=localhost
DB_NAME=sistema_ventas
DB_USER=root
DB_PASS=
APP_URL=http://localhost/sistema-ventas/
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
2. **`views/layouts/session.php`** — incluido en prácticamente todas las páginas; inicia la sesión PHP, carga `.env`, define `$URL` y provee las funciones de autenticación (`isAuthenticated()`, `requireLogin()`, `requireRole()`, `getCurrentUser()`, `generateCSRFToken()`, `verifyCSRFToken()`)
3. **`views/layouts/header.php`** — incluido después de session; llama a `requireLogin()`, instancia `AuthorizationService`, renderiza el `<head>` y navbar de AdminLTE
4. **`views/layouts/footer.php`** y **`views/layouts/mensajes.php`** — cierran el HTML y renderizan mensajes flash de `$_SESSION['mensaje']` con SweetAlert2

### Rol de los Directorios

| Directorio                       | Propósito                                                                                                                                                                 |
| -------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `config/`                        | `env.php` (carga .env), `config.php` (retorna array de config), `conexion.php` (wrapper PDO Singleton)                                                                    |
| `models/`                        | Clases de acceso a datos (una por entidad); cada una instancia `Conexion::getInstance()`                                                                                  |
| `controllers/`                   | Clases controlador y scripts de acción por módulo (ej. `controllers/ventas/VentaController.php`, `controllers/ventas/crear_venta.php`)                                    |
| `views/`                         | Archivos de vista PHP organizados por módulo; incluyen controladores y modelos directamente según necesiten                                                               |
| `services/`                      | `AuthorizationService.php` (verificación de permisos por nombre/ID), `ImagenService.php` (subida y eliminación de imágenes), `literal.php` (conversión número a palabras) |
| `libs/`                          | Librerías de terceros empaquetadas — solo TCPDF para generación de PDFs                                                                                                   |
| `public/js/modules/`             | Archivos JavaScript por módulo (un subdirectorio por módulo)                                                                                                              |
| `public/js/core/common-utils.js` | Utilidades JS compartidas                                                                                                                                                 |
| `public/uploads/`                | Imágenes subidas por usuarios (productos/, clientes/, usuarios/)                                                                                                          |

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

### Tablas Principales de la Base de Datos

- `empresa`, `sucursal` — estructura empresa/sucursal (preparada para uso multi-tenant futuro)
- `usuarios` — usuarios con `cargo` (rol) e `idsucursal`
- `permiso`, `permisousuario` — sistema de permisos granular
- `producto`, `categoria` — catálogo de productos con control de stock
- `cliente` — registro de clientes
- `venta`, `detalleventa`, `pagoventa` — ventas con líneas de detalle y métodos de pago mixtos (efectivo, tarjeta, QR, transferencia)
- `compra`, `detallecompra` — ingreso de mercadería/inventario
- `sesionusuario` — registro de auditoría de sesiones

### Mensajes Flash

Se asignan en `$_SESSION['mensaje']` (cadena de texto) y `$_SESSION['icono']` (ícono de SweetAlert2: `success`, `error`, `warning`, `info`). Los renderiza `views/layouts/mensajes.php`.

### Generación de PDFs

Los recibos se generan con TCPDF desde `libs/TCPDF-main/`. Los PDFs se emiten marcados como "sin valor fiscal".
