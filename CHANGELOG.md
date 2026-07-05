# Changelog

Todos los cambios importantes de este proyecto se documentan en este archivo.

Este formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/)
y el versionado sigue [Semantic Versioning](https://semver.org/lang/es/).

## [1.0.1] - 2026-07-05

### Security

- Protección CSRF aplicada de forma consistente en todos los formularios y endpoints de escritura del proyecto (antes solo existía en `views/login/login.php`). Se agregó un helper centralizado `requireCSRF()` en `views/layouts/session.php`, junto con `csrfField()`, `csrfMetaTag()` y `getRequestCSRFToken()` (lee el token desde `$_POST['csrf_token']` o el header `X-CSRF-Token`), y se invoca en los ~28 controladores de acción de `controllers/{clientes,productos,compras,ventas,usuarios,categoria,empresa,sucursal,permisos,sesiones}/`. `verifyCSRFToken()` ahora compara con `hash_equals()` para evitar timing attacks. Las vistas con formularios tradicionales incluyen `<?= csrfField() ?>`; los módulos AJAX puros (categorías, sucursales, empresa) envían el token vía header `X-CSRF-Token` mediante `$.ajaxSetup` global en `public/js/core/common-utils.js`; la subida de imagen de perfil lo envía dentro del `FormData`.
- Corrección de broken access control: se agregó `requireLogin()` a los ~20 scripts de acción de `controllers/*/` que carecían de verificación de sesión.
- Corrección de permisos granulares: los mismos scripts de acción ahora también verifican `AuthorizationService::tienePermisoNombre()` (antes el enforcement por permiso solo existía en las vistas, no en los endpoints).
- Corrección de manipulación de totales de venta: `VentaController::prepararDatosVenta()` obtiene `precioventa` desde la base de datos (`Producto::getById()`) y recalcula `totalventa` en servidor, en vez de confiar en los valores enviados por el cliente.
- Corrección de upload inseguro: `ImagenService::procesarImagen()` valida el tipo MIME real del contenido del archivo (`finfo`/`getimagesize`) en vez del `type` reportado por el cliente, y deriva la extensión de guardado del tipo detectado.
- Corrección de XSS reflejado en `views/layouts/header.php`: `nombre` y `cargo` del usuario actual se escapan con `htmlspecialchars`.
- Corrección de acciones de escritura ejecutadas vía GET (no protegibles con CSRF): `desactivar_cliente`, `desactivar_producto`, `desactivar_usuario`, `cambiar_estado_compra`, `anular_venta`, `cerrar_sesion` y `cerrar_sesiones_usuario` ahora se invocan por POST con el token CSRF incluido, usando el nuevo helper `submitCsrfForm()` en `public/js/core/common-utils.js` (construye y envía un formulario oculto en vez de navegar a la URL de acción).

### Fixed

- El recibo de una venta anulada (`views/ventas/recibo.php`) ya no corta la ejecución con un `die()` plano; ahora muestra una página de aviso legible y responde `409`.
- `views/ventas/index.php` deshabilita el botón de imprimir comprobante para ventas anuladas en vez de enlazar a un recibo que fallaría.

### Technical

- Los scripts `common-utils.js` y los módulos por vista se cargan con `?v=<?= $appVersion ?>` en `views/layouts/footer.php` para evitar que el navegador sirva versiones cacheadas tras un despliegue.

## [1.0.0] - 2026-07-01

### Added

- Se añadió `APP_VERSION` en `.env` y `.env.example` para versionado explícito de la app.
- Lanzamiento funcional base del FlowPOS open source.
- Módulo completo de **ventas** con detalle por ítems (`venta`, `detalleventa`) y registro de pagos mixtos (`pagoventa`: efectivo, tarjeta, QR y transferencia).
- Módulo de **compras** para ingreso de mercadería (`compra`, `detallecompra`) con impacto en inventario.
- Gestión de **inventario y catálogo**: productos, categorías, stock mínimo/máximo, precios de compra/venta y validaciones de consistencia.
- Gestión de **clientes** y mantenimiento de datos principales para flujo comercial.
- Gestión de **usuarios**, roles y estructura organizacional básica (empresa/sucursal).
- Sistema de **permisos granulares por usuario** además del control por rol.
- Dashboards y vistas operativas por módulo (ventas, compras, productos, clientes, usuarios, permisos, sesiones, sucursales y empresa).
- Generación de comprobantes **PDF** mediante TCPDF (marcados como "sin valor fiscal").
- Registro de **sesiones de usuario** para trazabilidad básica (`sesionusuario`).

### Security

- Protección de vistas autenticadas con `requireLogin()` y control por roles con `requireRole()`.
- Protección CSRF con generación y verificación de tokens (`generateCSRFToken()` / `verifyCSRFToken()`).
- Capa de autorización por permisos con `AuthorizationService` para validaciones por ID o nombre de permiso, aplicada también en los scripts de acción de `controllers/` (no solo en las vistas).
- Reglas de integridad en base de datos (claves foráneas, `UNIQUE` y `CHECK`) para reducir inconsistencias de datos.

### Technical

- Arquitectura MVC clásica en PHP sin framework y sin paso de build.
- Flujo de páginas con includes PHP (sin router central/front controller).
- Configuración por variables de entorno (`.env`) para URL, timezone, debug y conexión a base de datos.
- Compatibilidad con PHP 7.4+, MariaDB/MySQL y frontend basado en AdminLTE/Bootstrap.
- La app usa `APP_VERSION` desde `.env` en tiempo de ejecución (footer, login y `window.APP.version`), disponible también en `config/config.php` (`app.version`).
