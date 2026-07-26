# Changelog

Todos los cambios importantes de este proyecto se documentan en este archivo.

Este formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/)
y el versionado sigue [Semantic Versioning](https://semver.org/lang/es/).

## [Unreleased]

## [1.1.3] - 2026-07-26

### Changed

- Extraído el JS inline más grande de 8 vistas a sus propios archivos bajo `public/js/modules/`, siguiendo el patrón `create-[modulo].js`/`update-[modulo].js`/`show-[modulo].js`: `compras/create.php`, `compras/ingresar.php`, `productos/create.php`, `productos/update.php`, `productos/show.php`, `usuarios/show.php`, `dashboard/dashboard_general.php`, `sesiones/index.php`. Los datos que antes se interpolaban con PHP inline ahora viajan por `data-*` attributes o variables `window.*` (`window.productosDisponibles`, `window.APP.currency`, `baseUrl`).
- `create-producto.js` y `update-productos.js` estaban obsoletos (lógica antigua no referenciada por las vistas); reemplazados por la lógica real actualmente en uso.
- Completada la extracción de JS inline en el resto de vistas del proyecto (no quedan bloques `<script>` embebidos en ningún archivo bajo `views/`): índices con acción "cambiar estado" (productos, usuarios, compras, ventas, clientes), vistas "show"/"update" de ventas/compras/clientes, `login/login.php`, y los mensajes trivial de configuración (sucursales, empresa, categorías). Los datos que antes viajaban en variables PHP inline (`window.productosDisponibles`, arrays de clientes/productos) ahora se exponen vía atributos `data-*` en elementos ocultos, leídos con `.data()` de jQuery.
- `views/layouts/header.php`: el script inline que definía `baseUrl`/`window.APP` se reemplazó por meta tags (`app-name`, `app-version`, `app-currency`) y un nuevo archivo `public/js/core/app-globals.js` que las lee al cargar la página.
- `views/layouts/mensajes.php`: el toast de SweetAlert2 para mensajes flash ya no se genera con un `<script>` inline; ahora emite un `<div id="flash-mensaje" data-mensaje="..." data-icono="...">` oculto, leído por un listener agregado a `common-utils.js` (y a `login.js` para la pantalla de login, que no carga `common-utils.js`).
- Estandarizado el mecanismo de carga de JS por módulo: todas las vistas (excepto `login/login.php`, que no usa el layout `header.php`/`footer.php`) declaran `$module_scripts = ['modulo/archivo'];` antes de incluir `header.php`, en vez de mezclar eso con `<script src="...">` manuales al final de la vista. Eliminados comentarios redundantes que solo repetían lo que la línea siguiente ya decía (p. ej. "// Incluir el encabezado...").

- Eliminadas las vistas duplicadas `views/ventas/nueva.php` y `views/compras/ingresar.php` (y sus controladores `nueva_venta.php`/`ingresar_compra.php`), que habían divergido del formulario canónico `create.php` de cada módulo. Todos los enlaces (sidebar, dashboards por rol, `clientes/show.php`, `clientes/update.php`) apuntan ahora a `create.php`.
- El historial de ventas/compras (`views/ventas/index.php`, `views/compras/index.php`) ahora limita el listado a las propias transacciones del usuario cuando no es administrador (`VentaController::index()`/`CompraController::index()` aceptan un `$idusuario` opcional y usan `getPorUsuario()`).
- `views/ventas/show.php` y `views/compras/show.php` verificaban solo el permiso de módulo, no la propiedad del registro: un usuario no administrador podía ver el detalle de la venta/compra de otro usuario adivinando el `?id=` en la URL (IDOR). Ahora se valida que `idusuario` coincida con la sesión salvo que sea administrador.
- Retirados los permisos `nueva_venta`/`nueva_compra`: con el historial ya filtrado por usuario, el permiso `ventas`/`compras` por sí solo cubre tanto crear como ver el propio historial, dejando la separación anterior sin propósito. Migrado en BD el único usuario que dependía solo de `nueva_venta` (ahora tiene `ventas`); los permisos viejos quedaron desactivados (`estado = 0`, no eliminados físicamente) para no romper claves foráneas ni perder el historial de asignación. Simplificados los checks de permiso en `views/ventas/create.php`, `views/compras/create.php`, `views/layouts/header.php`, `views/dashboard/dashboard_general.php`, `views/permisos/index.php` y `AuthorizationService::categoriasPermisos()`.
- Corregido `views/empresa/index.php`: exigía el permiso `'empresas'` (plural) mientras el resto de la app (sidebar, dashboard) chequea `'empresa'` (singular) para mostrar el enlace — un mismatch de nombres que podía dejar a un usuario con el permiso visible en el menú pero sin acceso real a la página. Unificado a `'empresa'`; verificado contra la BD que no existía ninguna fila `permiso` llamada `'empresas'`, así que no hubo asignaciones huérfanas que migrar.

### Fixed

- `views/ventas/create.php`: el formulario usa `novalidate` pero solo validaba en cliente la selección de cliente antes de enviar; cantidad/precio de los productos no tenían ninguna validación real pese a marcar `.invalid-feedback` en el markup. Agregada validación real en `create-venta.js` (`validarFilasProductos()`), que marca `is-invalid` en cantidad/precio inválidos y bloquea el envío con un aviso, en vez de dejar que el usuario se entere recién tras el POST al servidor.
- El buscador de cliente en `views/ventas/create.php` (`#buscar-cliente`/`#sugerencias-clientes`) era un dropdown de sugerencias sólo usable con mouse. Convertido al patrón combobox ARIA (`role="combobox"`/`role="listbox"`/`role="option"`, `aria-expanded`, `aria-activedescendant`) con navegación por teclado (flechas, Enter, Escape) en `create-venta.js`. De paso, el render de cada sugerencia usaba `innerHTML` con datos de cliente (editables en BD) sin escapar — cambiado a construcción de nodos DOM (`textContent`) para evitar XSS almacenado.
- `views/ventas/index.php` y `views/ventas/show.php` mostraban el símbolo de moneda hardcodeado como `"Bs."` en vez de usar `$appCurrency`, a diferencia de `create.php` que ya lo hacía bien — quedaban desactualizados si `APP_CURRENCY` cambiaba en `.env`. Unificado a `$appCurrency` en ambas vistas.
- Los selects de método de pago en `views/ventas/create.php` (`#metodopago-unico` y `.select-metodo-pago` del pago mixto) usaban `<select class="form-control">` plano. Ahora usan Select2 (clase `select2` + `initializeSelect2()` de `common-utils.js`, la utilidad compartida del resto de la app) para consistencia visual con los demás formularios; el `$skip_select2` que se había agregado en un pase anterior de esta misma sesión quedó revertido porque ahora sí hay `<select class="select2">` reales en la vista. Los selects agregados dinámicamente en modo pago mixto (`agregarMetodoPago()`) se inicializan individualmente al insertarse.
- `public/js/modules/ventas/index-ventas.js` tenía handlers (`.btn-imprimir-ticket`, `#btn-filtrar-fechas`, `#fecha_inicio`, `#fecha_fin`, `#btn-restablecer-filtros`) apuntando a elementos que no existen en `views/ventas/index.php` — código muerto que nunca se ejecutaba. Eliminado junto con una inicialización duplicada de tooltips.
- `views/ventas/index.php` tenía un `<style>` inline con `.badge-purple` hardcodeado (color plano, sin equivalente en la paleta de AdminLTE) — extraído a `public/css/modules/ventas/ventas.css`, cargado vía `$module_styles` (mismo mecanismo que `dashboard.css`), consistente con que el resto del proyecto ya no tiene bloques `<style>` embebidos en las vistas de este módulo.
- Los botones de acción (ver/anular/imprimir) de la tabla de historial en `views/ventas/index.php` usan `btn-sm`, por debajo del mínimo de 44x44px recomendado por WCAG 2.5.5 (Target Size) para uso táctil. Agregada una regla en `ventas.css` que amplía el área táctil solo bajo `@media (hover: none) and (pointer: coarse)`, sin afectar la densidad de la tabla en desktop.
- La validación de cantidad/precio de `views/ventas/create.php` marcaba `is-invalid` visualmente pero no lo comunicaba a lectores de pantalla. Agregado `aria-invalid`/`aria-describedby` (vinculado al `.invalid-feedback` de cada fila con un id único por producto) en `create-venta.js`, sincronizado tanto en la validación de envío (`validarFilasProductos()`) como en los listeners `input` de cada fila.
- `apellidomaterno` (usuarios y clientes) se guardaba como cadena vacía `''` en vez de `NULL` cuando el campo se dejaba en blanco, pese a que la columna es nullable. Corregido en `ClienteController`, `UsuarioController` y el binding PDO de `models/Usuario.php` (antes bindeaba siempre como `PDO::PARAM_STR`).

## [1.1.2] - 2026-07-22

### Changed

- Vistas de creación/edición de usuarios: cada sección del formulario pasa a tener su propia tarjeta independiente en vez de un único card con `fieldset` anidados.
- Vista de detalle de usuario rediseñada al estilo de perfil de AdminLTE, con el contenido organizado en pestañas.
- Columna de guía/perfil de las vistas de usuarios ahora permanece visible al hacer scroll (`.sidebar-sticky` en `common.css`).

### Added

- Regla global en `common.css` para que las pestañas inactivas de cualquier card con tabs (patrón de vistas "show") usen el color info en vez del primary por defecto de AdminLTE.

### Fixed

- Caché obsoleta de `common.css` y de los CSS por módulo: ahora versionados por query string igual que el resto de assets.

### Performance

- Carga de DataTables/Select2 (`$skip_datatables`/`$skip_select2`) ajustada en todas las vistas de todos los módulos según lo que cada una realmente usa.

## [1.1.1] - 2026-07-19

### Fixed

- Dashboard general: reemplazados los datos de ejemplo hardcodeados por datos reales vía el endpoint `get_general_dashboard_data.php` (inventario, actividad reciente, clientes recientes), respetando los permisos granulares por sección.
- Accesibilidad del login: labels asociados a los campos, botón de mostrar/ocultar contraseña convertido a `<button>` real, mensajes de error inline, landmark `<main>`, sin demoras artificiales antes de enviar el formulario.
- Accesibilidad del dashboard (los 4 roles): `aria-live` en KPIs y secciones cargadas por AJAX, `aria-hidden` en íconos decorativos, `aria-label` en botones de colapsar panel y en gráficos (`<canvas>`), jerarquía de encabezados corregida.
- XSS en el dashboard general: escape de datos de clientes antes de insertarlos en el DOM.
- Landmark `<main>` agregado al layout compartido (`header.php`/`footer.php`), beneficiando a todas las vistas autenticadas.

### Changed

- CSS de los 3 dashboards por rol (administrador, supervisor, vendedor) consolidado en una hoja compartida (`dashboard.css`), eliminando duplicación y unificando breakpoints responsive y estilos de barras de progreso.
- Toggles de visibilidad (fechas personalizadas, detalle de inventario) migrados de `style="display"` inline a la clase utilitaria `.d-none`.
- Estructura de encabezado de página estandarizada (`<section class="content-header">`) en los 4 dashboards.

### Performance

- Chart.js ya no se carga globalmente en el layout: solo se incluye en los 3 dashboards que efectivamente lo usan.

## [1.1.0] - 2026-07-12

### Security

- Rate limiting en el login: bloqueo temporal por cuenta y por IP tras exceder intentos fallidos, para mitigar fuerza bruta y credential stuffing.

## [1.0.1] - 2026-07-05

### Security

- Protección CSRF consistente en todos los formularios y endpoints de escritura.
- Corrección de broken access control en scripts de acción sin verificación de sesión.
- Enforcement de permisos granulares a nivel de endpoint, no solo de vista.
- Corrección de manipulación de totales de venta: el total se recalcula en servidor.
- Validación real del tipo de archivo en la subida de imágenes.
- Corrección de XSS reflejado en el header al mostrar datos del usuario.
- Acciones destructivas (desactivar, anular, cerrar sesión) migradas de GET a POST con CSRF.

### Fixed

- El recibo de una venta anulada ya no corta la ejecución abruptamente; ahora muestra un aviso claro.
- Botón de imprimir comprobante deshabilitado para ventas anuladas.

### Technical

- Assets estáticos versionados por query string para evitar caché obsoleto tras despliegues.

## [1.0.0] - 2026-07-01

### Added

- Lanzamiento funcional base del FlowPOS open source.
- Módulo de **ventas** con detalle por ítems y pagos mixtos.
- Módulo de **compras** con impacto en inventario.
- Gestión de **inventario y catálogo** de productos y categorías.
- Gestión de **clientes** y **usuarios** con roles y estructura empresa/sucursal.
- Sistema de **permisos granulares** por usuario.
- Dashboards y vistas operativas por módulo.
- Generación de comprobantes **PDF** (sin valor fiscal) mediante TCPDF.
- Registro de auditoría de **sesiones de usuario**.

### Security

- Protección de vistas autenticadas por sesión y por rol.
- Protección CSRF con generación y verificación de tokens.
- Capa de autorización por permisos aplicada también en los endpoints de acción.
- Reglas de integridad en base de datos (claves foráneas, `UNIQUE`, `CHECK`).

### Technical

- Arquitectura MVC clásica en PHP sin framework y sin paso de build.
- Configuración por variables de entorno (`.env`).
- Compatibilidad con PHP 7.4+, MariaDB/MySQL y frontend AdminLTE/Bootstrap.

[1.1.0]: https://github.com/WorkTeam01/FlowPOS/compare/1.0.1...1.1.0
[1.0.1]: https://github.com/WorkTeam01/FlowPOS/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/WorkTeam01/FlowPOS/releases/tag/1.0.0
