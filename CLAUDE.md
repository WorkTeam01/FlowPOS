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
mysql -u root flowpos < database/schema.sql
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
APP_VERSION=1.2.6
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

1. **`index.php`** — punto de entrada autenticado; resuelve el rol del usuario contra la base de datos (`rol.dashboard`, nunca contra el dato de sesión) y despacha a la vista de dashboard correspondiente
2. **`views/layouts/session.php`** — incluido en prácticamente todas las páginas; inicia la sesión PHP, carga `.env`, define `$URL` y provee las funciones de autenticación (`isAuthenticated()`, `requireLogin()`, `getCurrentUser()`) y CSRF (`generateCSRFToken()`, `verifyCSRFToken()`, `csrfField()`, `csrfMetaTag()`, `getRequestCSRFToken()`, `requireCSRF()`, `getSafeRedirectBack()`). `requireRole()` fue código muerto y se eliminó en la migración a RBAC — no reintroducirlo (ver "Errores conocidos")
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

El control de acceso real de cada vista y cada endpoint de acción es **`AuthorizationService::tienePermisoNombre($idusuario, '<modulo>')`** (permiso granular por módulo) combinado, donde aplica, con **`AuthorizationService::esAdministrador($idusuario)`**. `AuthorizationService` resuelve permisos y estado de administrador contra las tablas `rol` / `rolpermiso` (con memo-cache por request); los administradores evitan todas las verificaciones. `requireRole()` es código muerto eliminado — no reintroducirlo.

Restricciones a nivel de **una sola acción** dentro de un módulo (no de todo el módulo): rol-check puntual en el controlador con `strtolower($_SESSION['usuario_rol']) === '<rol>'` como barrera real (el POST directo evade ocultar el botón en la vista), no ampliando el catálogo `permiso`. Ver "Errores conocidos".

Roles de usuario (nombres en minúsculas): `administrador`, `supervisor`, `vendedor`. Cada usuario tiene un `idrol`; los permisos se administran por rol en `views/roles/permisos.php` (matriz rol×permiso), no por cuenta individual.

### CSRF

Todo endpoint de escritura bajo `controllers/*/` que reciba `POST` debe invocar `requireCSRF();` (definido en `views/layouts/session.php`) como primera línea tras los `require_once`. Corta la ejecución con 403 JSON (AJAX) o redirect + mensaje flash (form tradicional) si el token falta o es inválido; no destruye la sesión activa.

- **Formularios tradicionales**: incluir `<?= csrfField() ?>` dentro de cada `<form method="post">`.
- **AJAX jQuery**: el token viaja automático vía header `X-CSRF-Token` gracias al `$.ajaxSetup` global en `public/js/core/common-utils.js`, que lo lee del meta tag `<?= csrfMetaTag() ?>` emitido en `views/layouts/header.php`.
- **AJAX con FormData** (uploads): `formData.append('csrf_token', csrfToken)` explícito, leyendo el token del meta tag.
- **Acciones que mutan estado invocadas antes por GET** (desactivar, cambiar estado, anular, cerrar sesión): usar `submitCsrfForm(action, fields)` de `common-utils.js`, que construye y envía un formulario oculto por POST con el token incluido — nunca `window.location.href` a un endpoint de escritura.

### Tablas Principales de la Base de Datos

- `empresa`, `sucursal` — estructura empresa/sucursal (preparada para uso multi-tenant futuro)
- `usuarios` — usuarios con `idrol` (FK a `rol`) e `idsucursal`
- `rol` — roles del sistema (`administrador`/`supervisor`/`vendedor`), con `es_admin`, `es_sistema`, `dashboard`, `estado`
- `permiso` — catálogo de permisos por módulo (contrato con el código, de solo lectura por UI)
- `rolpermiso` — pivot rol↔permiso: la asignación editable de permisos
- `producto`, `categoria` — catálogo de productos con control de stock
- `cliente` — registro de clientes
- `venta`, `detalleventa`, `pagoventa` — ventas con líneas de detalle y métodos de pago mixtos (efectivo, tarjeta, QR, transferencia)
- `compra`, `detallecompra` — ingreso de mercadería/inventario
- `sesionusuario` — registro de auditoría de sesiones (incluye `token_hash` = SHA-256 del token de revocación y `motivo_cierre`; ver la lección sobre revocación en "Errores conocidos")
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

Cualquier `<select>` nuevo en una vista debe llevar la clase `select2` y su inicialización debe pasar siempre por `initializeSelect2()` (`public/js/core/common-utils.js`), nunca reimplementando `$(...).select2({...})` inline — el fix de altura/responsive de Select2 vive centralizado en `public/css/core/common.css` (no duplicarlo en CSS de módulo). Si el select vive dentro de un modal Bootstrap, inicializar en el evento `shown.bs.modal` con `{ dropdownParent: $('#modalId') }` (si no, el dropdown se renderiza mal/cortado). Antes de cerrar cualquier módulo nuevo o auditado, correr `grep -rn "<select" views/ | grep -v select2` — mismo tipo de checklist que el `drawCallback` de tooltips. Bug real encontrado 2026-08-26: `views/categorias/index.php` y `views/empresa/index.php` tenían `$skip_select2 = true` sin darse cuenta de que ya usaban `<select>` en sus modales (quedaban sin estilizar); y `sucursales/index.php`/`empresa/index.php` tenían un `<select>` de Estado sin la clase `select2` en absoluto.

### Utilidades CSS Compartidas (`public/css/core/common.css`)

- `.sidebar-sticky` — columna lateral fija al hacer scroll (`position: sticky`, `static` bajo 767.98px).
- `.card-outline-tabs .nav-link:not(.active)` — color info oscurecido (`#117a8b`, 5.02:1; hover `#0e6c7a`) en pestañas inactivas de cualquier card con tabs (vistas "show"), sin CSS por módulo. El `#17a2b8` info original de AdminLTE daba 3.04:1 sobre blanco (falla WCAG AA 1.4.3).
- `.main-header .navbar-nav .nav-link` — enlaces e íconos del navbar oscurecidos a `#495057 !important` (7.76:1): AdminLTE los deja en `rgba(0,0,0,.5)` (computed `#7c7d7d`, 3.91:1). `!important` requerido porque AdminLTE define ese color con `!important` propio.
- `.nav-pills[role="tablist"] .nav-link.active` — pills usados como tablist (ej. pestañas de `perfil.php`) con la pestaña activa oscurecida a `#0056b3` (7.04:1): Bootstrap/AdminLTE dejan `#007bff` (3.97:1). El `nav-sidebar` no lleva `role="tablist"` y conserva sus propios estados de color.
- `.dataTables_wrapper .pagination .page-item:not(.active):not(.disabled) .page-link` — oscurece el `#007bff` de la paginación de DataTables a `#0056b3` (contraste WCAG AA); `:not()` deja intactas la página activa (blanco sobre primary) y Anterior/Siguiente deshabilitados. Global para todos los DataTables.
- `.badge-success` / `.badge-info` / `.badge-primary` — fondos oscurecidos (`#1e7e34` / `#117a8b` / `#0056b3`) sobre los defaults de Bootstrap/AdminLTE, que con texto blanco al tamaño de badge (~12px) fallaban WCAG AA (3.0–4.0:1). Global para todos los badges de estado/etiqueta del proyecto. `badge-danger` (4.5:1) y `badge-warning` (texto oscuro) ya cumplían. Sin dark mode → sin contraparte.
- `.btn-primary` / `.btn-info` (+ hover/focus/active) — fondos oscurecidos (`#0056b3` / `#117a8b`) sobre los sólidos de AdminLTE (`#007bff` ~4.0:1, `#17a2b8` ~2.8:1 con texto blanco); mismo criterio de contraste WCAG AA que sus badges y la paginación. Global. Estados `:disabled` exentos (WCAG 1.4.3). Sin dark mode → sin contraparte.
- `.btn:focus-visible` — contorno sólido (`2px solid #1f2d3d`, offset 2px) para foco de teclado; AdminLTE lo dejaba en una sombra tenue. Solo `:focus-visible`, así el clic de mouse no lo dispara. Global.
- `.breadcrumb-item a` / `.breadcrumb-item.active` / `.breadcrumb-item + .breadcrumb-item::before` — enlaces oscurecidos a `#0056b3` (~7:1), activo y separador a `#495057` (~8:1:1): AdminLTE deja los enlaces en `#007bff` (3.98:1) y el separador en `#6c757d` (4.69:1). Aplica al breadcrumb del `content-header` (layout).
- `.main-footer .footer-content` / `*` / `a` — color base del bloque oscurecido a `#495057` (~8:1) y enlace a `#0056b3` (~7:1): AdminLTE deja `.main-footer` en `#869099` (3.25:1). Sin dark mode → sin contraparte.
- `common.css` y `module_styles` versionados por query string (`?v=<?= $appVersion ?>`) igual que `module_scripts`, para evitar caché obsoleta.

### Callouts / Alerts inline

AdminLTE **sobrescribe** todas las variantes `.alert-*` de Bootstrap con fondo sólido oscuro y texto blanco (ej. `.alert-info` = `#17a2b8` con `#fff` → 3.04:1, falla WCAG AA en texto de cuerpo; `.alert-secondary` = `#6c757d`). Para un callout informativo legible usar las variantes `.alert-default-info` / `.alert-default-warning` / etc. de AdminLTE, que conservan el look claro original de Bootstrap (`#d1ecf1` con `#0c5460` → ~7:1). No pelear con overrides de color sobre `.alert-*` ni agregar `border-left` grueso (baja calidad). Encontrado 2026-08-30 en `views/permisos/index.php`; barrido posterior migró todos los `alert-info` de `views/usuarios/*` y `views/productos/show.php` a `alert-default-info`, y luego (2026-08-30) los `alert-warning`/`alert-success`/`alert-danger` de `productos/show.php`, `ventas/show.php` y `compras/show.php` a sus variantes `alert-default-*`. No quedan `.alert-*` sólidas de AdminLTE en `views/` fuera de este patrón.

### Mensajes Flash

Se asignan en `$_SESSION['mensaje']` (cadena de texto) y `$_SESSION['icono']` (ícono de SweetAlert2: `success`, `error`, `warning`, `info`). Los renderiza `views/layouts/mensajes.php`.

### Generación de PDFs

Los recibos se generan con TCPDF desde `libs/TCPDF-main/`. Los PDFs se emiten marcados como "sin valor fiscal".

### Errores conocidos y lecciones aprendidas

- **`json_encode(array_filter(...))` sin reindexar produce un objeto JS, no un array**: `array_filter()` preserva las claves originales del array; si estas dejan de ser secuenciales desde 0, `json_encode()` serializa el resultado como objeto (`{"2":...,"5":...}`) en vez de array (`[...]`). Cualquier JS que reciba ese dato vía `data-*`/`.data()` y llame `.forEach()`/`.map()` sobre él falla con `TypeError: x.forEach is not a function`. Envolver siempre con `array_values()` antes de `json_encode()` cuando el array pasó por `array_filter()` (ver `views/ventas/create.php`, `data-productos`).
- **Tablas anchas (5+ columnas) en vistas con inputs por celda no deben depender solo de `.table-responsive`**: el scroll horizontal oculta columnas fuera de la vista sin indicarlo, mala UX en móvil. Para tablas editables tipo carrito (`views/ventas/create.php`, `#tabla-productos`), usar un breakpoint (`@media (max-width: 767.98px)`) que convierta `<tr>`/`<td>` a `display: block` con `data-label` en cada `<td>` para mostrar la etiqueta de columna como pseudo-elemento `::before`, formando tarjetas apiladas en vez de forzar scroll lateral.
- **Ownership check debe ir antes de `include_once header.php`**: `header.php` ya emite HTML (sidebar, navbar) antes de retornar. Si un check de propiedad (`idusuario` de la sesión vs. dueño del registro) se coloca después de incluir `header.php` y falla, el `header('Location: ...')` correspondiente no aborta la ejecución con un redirect real — PHP no puede enviar cabeceras HTTP una vez que ya se envió cuerpo de respuesta — y el resultado es una página en blanco en vez de la redirección con mensaje flash esperada (bug detectado en `views/compras/show.php`, corregido en 1.1.5 moviendo la obtención del registro y el ownership check antes del `include_once`, igual que en `views/ventas/show.php`). El acceso queda bloqueado igualmente (no hay fuga de datos), pero es una regresión de UX que debe evitarse: todo `header('Location: ...')` condicionado a una verificación (permiso, existencia, propiedad) debe ejecutarse **antes** de incluir `header.php`.
- **Patrón "escape-at-storage" (deuda técnica conocida)**: `sanitizarDatos()` en los modelos (`Venta`, `Cliente`, `Producto`, `Usuario`, `Empresa`, `Sucursal`, `Rol`) aplica `htmlspecialchars()` a todos los strings **al guardar**, no al mostrar. Por eso las vistas (`show.php`, `recibo.php` de ventas, etc.) deben interpolar esos campos **sin volver a escaparlos** — hacerlo produce doble escape visible (`&lt;b&gt;` literal en vez de texto legible). Verificado en `views/ventas/show.php` y `views/ventas/recibo.php` con `php -l` y prueba manual en navegador (payload `<script>alert(1)</script>` en observación: se muestra como texto plano, sin duplicar entidades). Riesgo conocido: si algún endpoint futuro escribe en estas tablas sin pasar por `sanitizarDatos()`, ese dato quedaría sin escapar y se renderizaría crudo → XSS almacenado. Mientras este patrón siga vigente, cualquier INSERT/UPDATE nuevo sobre estas tablas debe pasar por el `sanitizarDatos()` del modelo correspondiente; no agregar `htmlspecialchars()` en las vistas como "defensa extra" sin antes confirmar si el dato ya viene escapado. Migrar todo el proyecto a "escapar al mostrar" (quitar el escape de `sanitizarDatos()` y agregarlo en cada punto de salida) es la solución de fondo, pero es un refactor transversal pendiente de decisión, no parte de ninguna auditoría de módulo individual.
- **`cargo` de usuario nunca debe aceptarse crudo del `$_POST` sin verificar quién lo envía**: `UsuarioController::guardar()` y `actualizar()` tomaban `cargo` del formulario sin whitelist ni chequeo de rol, permitiendo que cualquier usuario con el permiso granular `usuarios` (no solo `administrador`) se auto-asignara o asignara a otro `cargo=Administrador` — escalada de privilegios (encontrado y corregido 2026-08-11 en el módulo `usuarios`). Regla general: cualquier campo que determine el nivel de acceso de una cuenta (rol, cargo, permisos) debe validarse contra `AuthorizationService::esAdministrador($_SESSION['usuario_id'])` (o el chequeo de jerarquía equivalente) en el controlador, nunca confiar en que el `<select>` de la vista oculte la opción — el POST directo la evade igual. **Corolario descubierto en la auditoría de 2026-08-11**: el fix original solo validaba el cargo _nuevo_ enviado en el POST, dejando sin protección la modificación de una cuenta que _ya_ era Administrador (un no-admin podía enviar `cargo=Vendedor` para degradarla, o cambiarle la contraseña, evadiendo el check porque el valor nuevo ya no era "Administrador"; mismo hueco en `cambiarEstadoUsuario()` para desactivar cuentas admin). Regla completa: validar **ambos lados** — el cargo nuevo solicitado Y el cargo actual del registro objetivo — antes de permitir cualquier modificación (`UsuarioController::actualizar()` y `cambiarEstadoUsuario()`).
- **`UsuarioController::cambiarEstadoUsuario($id, $estado_actual)` espera el estado _actual_ (antes del cambio), no el nuevo**: internamente calcula `$nuevo_estado = $estado_actual == 1 ? 0 : 1`, es decir, hace el toggle él mismo. `public/js/modules/usuarios/index-usuarios.js` lo invoca correctamente (`estado: estadoActual`), pero `public/js/modules/usuarios/show-usuario.js` (botón "Activar/Desactivar" del detalle de usuario) enviaba el estado ya invertido en el cliente (`estado: estadoActual == 1 ? 0 : 1`), causando un doble-toggle: el backend volvía a invertirlo y el estado en BD quedaba sin cambios, aunque el mensaje flash indicaba lo contrario ("Usuario desactivado correctamente" sin que `estado` cambiara). Verificado con consulta directa a `usuarios.estado` antes/después del clic (bug encontrado y corregido 2026-08-11 durante prueba end-to-end del módulo usuarios). Regla general: cualquier endpoint que "invierte" un estado debe documentar explícitamente si espera el valor actual o el deseado, y todos los llamadores JS deben usar la misma convención — verificar ambos emisores (`index-usuarios.js` y `show-usuario.js`) cuando se toque este endpoint de nuevo.
- **Touch targets accesibles (WCAG 2.5.5) para `.btn-group>.btn-sm` ya están resueltos globalmente en `public/css/core/common.css` (`@media (pointer: coarse)`), no crear una regla duplicada por módulo**: al auditar un módulo con la skill `impeccable` y encontrar botones de acción agrupados en tabla por debajo de 44x44px, verificar primero si `common.css` ya cubre el caso antes de agregar CSS al módulo — evita duplicación y reglas que se pisan entre sí. Ojo con `@media (pointer: coarse)` (dispositivo con puntero táctil) vs `@media (max-width: ...)` (ancho de viewport): son condiciones distintas — Playwright con `page.setViewportSize()` NO activa `pointer: coarse` (sigue siendo puntero "fino"), por lo que probar solo con resize de viewport no revela bugs de esta regla; hay que emular puntero táctil (DevTools "device toolbar" o contexto Playwright con `hasTouch`/`isMobile`) para verla en acción. Bug real encontrado y corregido 2026-08-11: la regla usaba `margin-left: 2px` entre botones adyacentes del grupo, rompiendo visualmente el empalme sin costuras que Bootstrap/AdminLTE ya logra con `margin-left: -1px` (ver `.btn-group>.btn:not(:first-child)` en `adminlte.min.css`) — corregido a `-1px` en `common.css`. Desde 2026-09-11 el bloque cubre también `.btn-sm` aislados (fuera de `.btn-group`/`.dt-buttons`, ej. "Nueva Compra", "Agregar Producto", "Eliminar producto") con un `::before` centrado `width/height: max(100%, 44px)` que expande el área de toque sin agrandar la caja visible — si un `.btn-sm` nuevo queda por debajo del mínimo en táctil, verificar que este bloque ya lo cubre antes de agregar CSS de módulo. Para verificar el `::before` en navegador: `elementFromPoint()` sobre un punto fuera del box visible pero dentro de los 44px debe devolver el botón; en puntero fino esa misma regla no debe estar activa.
- **Confirmación de cambio de estado (activar/desactivar) estandarizada en `confirmarCambioEstado()` (`public/js/core/common-utils.js`)**: reemplaza el bloque repetido de `Swal.fire({...}).then(...) => submitCsrfForm(...)` que existía por separado en cada módulo (`show-producto.js`, `index-productos.js`, `index-clientes.js`). Al agregar un botón nuevo de activar/desactivar en cualquier módulo, usar este helper en vez de copiar el bloque de SweetAlert2 — recibe `{ id, estadoActual, titulo, texto, actionUrl }` y sigue la misma convención de `UsuarioController::cambiarEstadoUsuario()`: el endpoint destino espera el estado **actual**, no el deseado (hace el toggle internamente).
- **Card "Vista Previa" del formulario (create/update) es un patrón reutilizable, no exclusivo de productos**: el markup vive en un partial (`views/productos/partials/vista_previa.php`) y la lógica JS en un archivo compartido (`public/js/modules/productos/vista-previa-producto.js`) porque `create.php`/`update.php` usan los mismos IDs de campo — evita duplicar HTML/JS entre ambos formularios. Clientes usa el mismo formato de cards por sección pero **sin** esta card (decisión explícita: no hay un preview visual equivalente al de producto). Si un módulo nuevo necesita una Vista Previa similar, seguir este mismo patrón de partial + JS compartido entre create/update en vez de duplicar entre ambas vistas.
- **`initializeTooltips()` (`public/js/core/common-utils.js`) con `placement: 'auto'` no es confiable en tablas de acciones (`btn-group` en la última columna de un `#tabla*`)**: Popper compara espacio disponible en las 4 direcciones y, en filas cercanas al borde superior/derecho de la tarjeta, puede elegir `left` en vez de `top` de forma inconsistente entre filas — reproducido en `views/usuarios/index.php` incluso sin `.table-responsive` de por medio (descarta que sea un problema de `scrollParent`). Fix aplicado 2026-08-11: `initializeTooltips()` ahora usa `placement: 'top'` fijo (igual que el patrón ya usado en `ventas/index-ventas.js` y `compras/index-compras.js`, que llaman `.tooltip()` sin opciones y por eso nunca sufrieron este bug). Si se reutiliza `initializeTooltips()` en una tabla nueva con DataTables, agregar también un `drawCallback: function() { initializeTooltips(); }` en las opciones del `DataTable()` — sin esto, los tooltips solo funcionan en la página inicial del paginador, porque DataTables re-renderiza las filas en cada `draw` y los `data-toggle="tooltip"` nuevos quedan sin inicializar (ver `usuarios/index-usuarios.js`). **Cuidado con dónde se agrega**: `drawCallback` debe ir como propiedad hermana a nivel raíz del objeto de opciones de `DataTable({...})`, **no** anidada dentro de `"language": {...}` — DataTables ignora silenciosamente claves desconocidas dentro de `language`, así que quedaría definida pero nunca se ejecutaría (bug real cometido y corregido en `ventas/index-ventas.js` el 2026-08-11: el `drawCallback` quedó pegado justo antes del `}` de cierre de `language` en vez de después). Al verificar en navegador tras tocar estos archivos `.js` versionados por `?v=<?= $appVersion ?>`, forzar bypass de caché (hard reload / DevTools "Disable cache") — el query string no cambia entre ediciones dentro de la misma versión, así que el navegador puede seguir sirviendo la copia vieja en caché y dar un falso negativo.
- **`exportOptions.columns` de los botones de reporte de DataTables (copy/pdf/excel/csv/print) nunca debe incluir la columna "Acciones"**: esa columna solo contiene botones (Editar, Activar/Desactivar, etc.), sin texto exportable — incluirla deja una columna vacía en el PDF/Excel/CSV/impresión. Regla: `columns` debe listar únicamente los índices de columnas con datos reales (ej. `[0, 1, 2, 3]` si la tabla tiene 5 columnas y la última es Acciones), nunca el rango completo por copy-paste de otro módulo con distinto número de columnas (bug encontrado en `categorias/index-categorias.js` 2026-08-26, copiado de un módulo con 6 columnas cuando la tabla real tenía 5). **Tampoco deben incluirse columnas de imágenes** (nada que exportar) ni carecer de columnas de texto sí exportables (Cargo/Estado): `usuarios/index-usuarios.js` tenía `[0,1,2,3,4,5,6,7]` incluyendo la columna Imágen (5) y omitiendo Cargo/Estado, corregido a `[0,1,2,3,4,6,7]` en 1.2.4 — además se eliminó el `format.body` que formateaba la columna Imágen en Excel/print (código muerto, la columna ya no se exportaba).
- **Previews de imagen con `<img>` sin `src` en el markup (nunca `src="#"`) y alts descriptivos en español**: en los formularios con card "Vista Previa" (`create.php`/`update.php`/`perfil.php` de usuarios, productos, etc.) el `src` se asigna por JS recién al seleccionar el archivo y el contenedor vive `display:none` hasta ese momento. Poner `src="#"` inserta una imagen rota (el detector `broken-image` de la skill `impeccable` la flaggea) y fuerza una petición HTTP a `#`. El `alt` debe describir el contenido ("Foto de <Nombre> <Apellido>", "Foto por defecto"), no el nombre del atributo, y en español (encontrado `alt="Imagen"` repetido y `alt="User profile picture"` en `perfil.php`, corregido 2026-09-15). Falso positivo conocido: el detector flaggea como `broken-image` todo `<img>` sin `src` aunque jamás se renderice — si el src lo asigna JS sobre un contenedor oculto, es esperado.
- **Tablists de Bootstrap con `<a role="tab">` envueltos en `<li>`: `role="presentation"` en el `<li>` wrapper**: axe flaggea `aria-required-children`/`listitem` cuando el hijo directo del `role="tablist"` no es el `role="tab"` sino un `<li>`. Al crear un tablist/pill-tablist nuevo en una vista "show", el `<ul>` lleva `role="tablist"` y cada `<li>` que envuelve un `<a role="tab">` lleva `role="presentation"` (mismo criterio que el `role="menu"` eliminado del sidebar en 1.2.3). La sincronización de `aria-selected` de tabs/pills está **centralizada** en `common-utils.js` (handler `shown.bs.tab` sobre `[data-toggle="tab"],[data-toggle="pill"]`, ampliado a pills en 1.2.4) — no reimplementar el sync inline en el JS de módulo.
- **Una tabla que usa DataTables no necesita el wrapper `<div class="table-responsive">`**: DataTables con `"responsive": true` ya maneja el overflow/colapso en viewports angostos por su cuenta; envolverla en `.table-responsive` es redundante (duplica el manejo de scroll horizontal) y no aporta nada. Usar `.table-responsive` solo en tablas estáticas sin DataTables.
- **El control de acceso real de FlowPOS es `tienePermisoNombre() && esAdministrador()` en cada vista, no `requireRole()`**: `requireRole()` (`views/layouts/session.php`) llevaba tiempo sin ningún call site real — quedó como código muerto desde que el proyecto migró a permisos granulares, y se eliminó durante la migración a RBAC por rol. Antes de reutilizar o "arreglar" una función de auth que parece obsoleta, `grep` sus call sites reales — puede llevar tiempo sin usarse sin que nadie lo haya notado.
- **Restricciones a nivel de acción (no de módulo) se resuelven con un rol-check puntual en el controlador, no ampliando el catálogo `permiso`**: el catálogo es de granularidad por módulo a propósito (ver nota siguiente). Cuando una sola acción dentro de un módulo debe restringirse a ciertos roles (ej. anular ventas: supervisor/admin sí, vendedor no — `controllers/ventas/anular_venta.php`, 1.2.1), gatear con `strtolower($_SESSION['usuario_rol']) === '<rol>'` en el controlador como barrera real (el POST directo evade el ocultar del botón en la vista) y ocultar el botón en la(s) vista(s) solo como limpieza de UI. Nombres de rol en minúsculas: `administrador`, `supervisor`, `vendedor` (`database/migrations/2026_08_rbac.sql`). Migrar a permisos de acción tipo `ventas.anular` solo si aparecen 3-4 reglas de este tipo; por un caso aislado no amortiza.
- **El catálogo `permiso` es un contrato con el código, no dato editable por UI**: cada `nombre` está hardcodeado en ~40 llamadas `tienePermisoNombre($id, 'ventas')` repartidas por el proyecto. Cualquier módulo que ofrezca CRUD sobre esta tabla (crear/renombrar/eliminar permisos desde la UI) introduce un kill-switch de acceso global si alguien edita o borra un nombre que el código espera literal. `views/permisos/index.php` es intencionalmente de solo lectura por esta razón — la gestión editable vive en `rolpermiso` (asignación por rol), no en el catálogo mismo.
- **Antes de ejecutar un paso destructivo de una migración incremental (DROP COLUMN, DROP TABLE), re-`grep` el código en busca de lecturas que sigan dependiendo de lo que se va a eliminar — no confiar en que un corte de lectura previo "ya quedó completo" solo porque así lo dice la documentación/memoria de una sesión anterior**: en la migración a RBAC por rol de FlowPOS, el corte de lectura de dashboards/sesión (`cargo` → `idrol`/`rol`) se había dado por cerrado, pero `DashboardSupervisorController` (5 queries SQL crudas con `LOWER(u.cargo)='vendedor'`), los guards de `get_vendedor_dashboard_data.php`/`get_supervisor_dashboard_data.php`, `session.php::getCurrentUser()`, `AuthController` y `models/Sesion.php` seguían leyendo la columna en vivo. Ejecutar el `DROP COLUMN cargo` sin detectar esto habría roto el acceso a los dashboards de supervisor/vendedor en producción. Regla general: antes de un `DROP`/`ALTER` irreversible sobre una columna/tabla que el código leía, correr `grep -rn "<columna>"` sobre todo el árbol (no solo los archivos que la sesión actual recuerda haber tocado) y confirmar que cada resultado restante es intencional (mirror-write a retirar en el mismo paso, o alias de presentación ya migrado a otra fuente). **Aun así, un lector huérfano se coló tras el `DROP TABLE permisousuario`**: `views/usuarios/show.php` seguía llamando `AuthorizationService::obtenerPermisosAsignados()` (que leía esa tabla) porque el `grep` de cierre buscó la columna/tabla en el código de _escritura y lectura de autorización_, pero no se re-verificó cada vista de presentación que consumía esos métodos indirectamente — la llamada fallaba silenciosamente (catch + `error_log`) devolviendo `[]`, sin error visible, mostrando un mensaje incorrecto ("sin permisos asignados") en vez de romper obviamente. Corolario: tras un `DROP`, además del `grep` de la columna/tabla, revisar también los métodos de servicio que quedaron `@deprecated`/rotos por el DROP y `grep` sus _call sites_ — un método que sigue siendo código válido pero apunta a una tabla inexistente falla en runtime sin que `php -l` lo detecte.
- **Todo cambio en `public/css/core/common.css` (o cualquier asset servido con `?v=<?= $appVersion ?>`) debe ir acompañado del bump de `APP_VERSION` en `.env` en el mismo commit**: el query string solo cambia al subir la versión, así que sin el bump el navegador de un usuario existente sigue sirviendo la copia cacheada y el fix no llega — solo lo ven quienes hacen hard-reload (los devs). Confirmado 2026-09-08: el fix de contraste de `.badge-*` de la primera pasada de la auditoría de roles era invisible en `?v=1.2.1`; recién al subir a `1.2.2` los badges tomaron el color nuevo. Verificar en navegador con la URL del asset (`.../common.css?v=<versión nueva>`) y `getComputedStyle`, no solo con hard-reload manual que enmascara el problema.
- **Tokens de colores de UI compartidos entre módulos (ej. SweetAlert2 buttons) van en `common.css :root`, no en CSS de módulo**: `--swal-confirm`, `--swal-cancel`, `--swal-danger` originalmente estaban en `ventas/ventas.css` pero se usaban en 10+ módulos. `common.css` es el lugar correcto para tokens globales. En JS, usar `getComputedStyle(document.documentElement).getPropertyValue('--token').trim()` con fallback hardcodeado por si la variable no carga. No cachear valores de `getComputedStyle` en variables al inicio del archivo si solo se usan en `Swal.fire()` ocasionales — el overhead de `getComputedStyle` es despreciable en un contexto de click del usuario, no en un loop.
- **`(int)` sobre string vacío produce `0`, no `null` — peligroso en FK nullable**: `VentaController::prepararDatosVenta()` usaba `isset($post_data['idcliente']) ? (int)$post_data['idcliente'] : null`. Un string vacío del input hidden pasaba `isset()` y `(int)''` = `0`, violando la FK constraint `cliente(idcliente)` (no existe cliente con id 0). Fix: usar `!empty()` en vez de `isset()` para columnas nullable con cast a `(int)`. Aplica a cualquier campo FK que pueda venir vacío desde un formulario HTML.
- **Lazy render en modales con listas grandes**: cuando un modal carga una lista de 100+ items (productos, clientes), renderizar todos de una vez genera cientos de nodos DOM y retrasa el paint. Patrón: renderizar en páginas de 20 con un botón "Cargar más", y si hay búsqueda, debounce 200ms. Ejemplo en `create-venta.js` con `renderizarSiguientePagina()`. No aplicar lazy render si la lista típica es <30 items (ej. categorías, sucursales) — el overhead del botón no justifica la complejidad.
- **`LEFT JOIN` + `GROUP BY` en queries de estadísticas puede devolver filas con valores NULL que parecen "truthy"**: el query de "Cliente Top" usa `LEFT JOIN cliente` para incluir ventas sin cliente; si todas las ventas del día son "Consumidor Final" (`idcliente = NULL`), el `GROUP BY v.idcliente` agrupa esas ventas y devuelve una fila con `nombre_cliente = NULL`. En PHP, el array asociativo es truthy (no es `false`), así que `? htmlspecialchars($row['nombre_cliente']) : 'N/A'` renderiza string vacío en vez de "N/A". Fix: usar `!empty($row['nombre_cliente'])` en vez de solo verificar truthiness del array. Aplica a cualquier query con `LEFT JOIN` que alimente un display condicional.
- **`Cliente::getAll()` retorna todos los clientes incluyendo inactivos**: a diferencia de productos (filtrados por `estado == 1 && stock > 0` en `create.php`), el modal de selección de clientes en `create-venta.js` no filtra por estado. Clientes desactivados aparecen en la búsqueda. Decisión de diseño: el vendedor debe poder ver historial de ventas a clientes inactivos, pero si se quiere filtrar, agregar `WHERE estado = 1` en `Cliente::getAll()` o filtrar en `create.php` antes de `json_encode()` (como se hace con productos).
- **Revocación de sesiones: token en dos partes (crudo en `$_SESSION`, SHA-256 en BD) y validación fail closed por request**: cerrar la sesión de otro usuario desde el panel admin exige que la sesión del navegador objetivo muere en su siguiente request. Patrón (servicio `services/SesionTokenService.php`): al hacer login se genera `bin2hex(random_bytes(32))`, se guarda crudo en `$_SESSION['sesion_revocation_token']` y su SHA-256 en `sesionusuario.token_hash`; `isAuthenticated()` (`views/layouts/session.php`) revalida contra la fila de BD en cada request y, ante cualquier duda (fila cerrada, token ausente en sesiones legadas, error de lectura), devuelve no autenticado — **fail closed** — y `requireLogin()` cierra la sesión con flash diferenciado ("Su sesión fue finalizada. Inicie sesión nuevamente.") en vez del mensaje genérico de expiración. Reglas: (1) `token_hash` jamás en HTML/JS/exportaciones — `Sesion::COLUMNAS_SESION` lo excluye de los SELECT del modelo; (2) todo cierre lleva `motivo_cierre` (`logout`, `timeout`, `security`, `admin_row`, `admin_user`, `migration`) y los UPDATE de cierre son idempotentes (`WHERE ... AND estado=1`, gana el primer motivo en escribirse); (3) cerrar sesiones de otros usuarios es **solo administrador**: el gate vive en `controllers/sesiones/cerrar_sesion.php` y `cerrar_sesiones_usuario.php` con `AuthorizationService::esAdministrador()` — no en la vista, porque el POST directo evadió el candado del UI en la prueba real (supervisor con permiso `sesiones`); (4) `checkSessionSecurity()` compara IP y User-Agent **exactos**: una sesión creada por curl/script no sirve desde otro cliente si difiere el UA, y `logout.php` por GET ya no destruye la sesión (patrón `submitCsrfForm`) — al automatizar pruebas en navegador, loguear desde el propio navegador y limpiar las filas de prueba por preview + marker propio, nunca por corte de ID.
