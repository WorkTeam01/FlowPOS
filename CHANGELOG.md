# Changelog

Todos los cambios importantes de este proyecto se documentan en este archivo.

Este formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/)
y el versionado sigue [Semantic Versioning](https://semver.org/lang/es/).

## [1.2.2] - 2026-09-08

### Fixed

- **Auditoría impeccable del módulo roles (13/20 → 18/20)** sobre `views/roles/index.php` y `views/roles/permisos.php`. Tras los cambios, axe-core reporta 0 violaciones WCAG 2 A/AA en el contenido del módulo:
  - Doble escape eliminado en `nombre` / `descripcion` / `data-*` / `aria-label`: `Rol::sanitizarDatos()` ya escapa al guardar (patrón "escape-at-storage"). Se conserva `htmlspecialchars()` sobre los nombres del catálogo `permiso` y las categorías, que no pasan por ese modelo.
  - Matriz de permisos: primera columna, cabecera de roles y etiqueta de categoría con `position: sticky` para no perder el contexto de fila al hacer scroll horizontal en móvil; descripción de cada permiso vía `AuthorizationService::descripcionPermiso()`; celda de checkbox clicable en todo su relleno para alcanzar el área táctil de 44px (WCAG 2.5.5); aviso de "cambios sin guardar" por columna con `beforeunload`; `caption`, `scope` y `aria-label` en la tabla.
  - `views/roles/index.php`: el botón "Desactivar" se oculta cuando el rol sería el último administrador activo (misma barrera que ya aplicaba el endpoint en servidor).
  - `aria-label` en el botón de colapso del panel (`.btn-tool[data-card-widget="collapse"]`) de ambas vistas.
- **Contraste de controles sólidos** (`public/css/core/common.css`, global a todo el proyecto):
  - `.btn-primary` (`#007bff` → `#0056b3`, ~7:1) y `.btn-info` (`#17a2b8` → `#117a8b`, ~5:1) oscurecidos: los fondos sólidos de AdminLTE con texto blanco fallaban WCAG AA 1.4.3. Estados `:disabled` exentos.
  - `.btn:focus-visible` restituye un contorno sólido visible por teclado (WCAG 2.4.11); AdminLTE lo reducía a una sombra tenue.
  - `.badge-success` / `.badge-info` / `.badge-primary` oscurecidos sobre los defaults de Bootstrap/AdminLTE, que con texto blanco al tamaño de badge fallaban WCAG AA. Sin dark mode → sin contraparte.
- **`APP_VERSION` subido a `1.2.2`**: `common.css` y los assets de módulo se sirven con `?v=<APP_VERSION>`. Sin el bump, el navegador sigue sirviendo la copia cacheada de `common.css` y los fixes de contraste anteriores no llegan al usuario.

### Note

- `CLAUDE.md` actualizado: sección de autorización, flujo de request y tablas de BD alineados con RBAC (se eliminaron referencias residuales a `cargo`, `permisousuario` y `requireRole()`); nuevas utilidades de `common.css` documentadas; lección nueva sobre el bump obligatorio de `APP_VERSION` al tocar assets versionados. Misma regla añadida a `PROMPTS.md` y `CONTRIBUTING.md`.

## [1.2.1] - 2026-08-30

### Added

- **Restricción de anulación de ventas al rol supervisor/administrador**: el rol `vendedor` ya no puede anular ventas. Resuelto con un rol-check puntual (`strtolower($_SESSION['usuario_rol']) === 'vendedor'`) en `controllers/ventas/anular_venta.php` como barrera real — un POST directo evadiendo el HTML es rechazado con mensaje flash y redirect al listado. El botón "Anular" se oculta además en `views/ventas/index.php` y `views/ventas/show.php` para ese rol (solo limpieza de UI). Decisión deliberada de no introducir un permiso de acción granular (`ventas.anular`) por un solo caso; el catálogo `permiso` sigue siendo de granularidad por módulo. Verificado en Playwright con las 3 cuentas demo: vendedor bloqueado (botón oculto + POST rechazado), supervisor y administrador anulan sin cambios.

### Fixed

- **Auditoría impeccable del módulo permisos (14/20 → 18/20)** sobre `views/permisos/index.php` y `public/js/modules/permisos/index-permisos.js`:
  - Aviso `alert alert-info` (fondo sólido de AdminLTE, 3.04:1, falla WCAG AA) migrado a `alert alert-default-info` (~7:1). AdminLTE sobrescribe todas las variantes `.alert-*` de Bootstrap con fondo oscuro; hay que usar las variantes `alert-default-*`.
  - El aviso de "solo lectura" ahora es visible también para no-administradores (antes envuelto en `if esAdministrador`), con copy adaptado por rol: el no-admin ve el texto sin el enlace a la matriz de permisos.
  - Columna "Estado" eliminada de la tabla (siempre "Activo", sin UI para cambiarla; `badge-success` fallaba contraste). Tabla de 4 → 3 columnas; `exportOptions.columns` ajustado a `[0,1,2]` en copy/pdf/excel/csv/print, quitado el formateo de la columna Estado en el PDF, y `format.body` de Excel/print aplana los badges de roles a una lista separada por comas.
  - `aria-label` agregado al botón de colapso del panel y al `<table>`.
  - Línea muerta `$('[data-toggle="tooltip"]').tooltip();` eliminada del JS (la vista no tiene tooltips).
- **Barrido de accesibilidad de contraste fuera del módulo permisos**:
  - `public/css/core/common.css`: nueva regla `.dataTables_wrapper .pagination .page-item:not(.active):not(.disabled) .page-link { color: #0056b3 }` — oscurece el `#007bff` (3.98:1) heredado de AdminLTE en la paginación de todos los DataTables del proyecto a ~5.9:1. El `:not()` deja intactas la página activa y los botones deshabilitados.
  - `alert alert-info` → `alert-default-info` en `views/usuarios/perfil.php`, `views/usuarios/show.php`, `views/usuarios/update.php` y `views/productos/show.php` (mismo override sólido de AdminLTE).
  - Cierre del barrido: migradas también las `alert-danger` / `alert-warning` / `alert-success` restantes a variantes `alert-default-*` en `views/productos/show.php` (bloques de estado de stock y de margen), `views/ventas/show.php` y `views/compras/show.php` (aviso de la sección de observaciones). Ya no quedan `.alert-*` sólidas de AdminLTE en `views/` fuera del patrón `alert-default-*`; estas variantes conservan el mismo aspecto en dark-mode, sin contraparte `body.dark-mode`.
  - `common.css`: área táctil de 44px (WCAG 2.5.5) extendida a los botones de reporte de DataTables (`.dt-buttons > .btn`), que no forman un `.btn-group` y por eso no los cubría la regla `@media (pointer: coarse)` existente.

## [1.2.0] - 2026-08-26

### Fixed

- **Re-auditoría de código sobre productos+clientes (2026-08-26)**: segunda pasada de `/code-review` tras cerrar la ronda del 2026-08-18, para confirmar que el diff commiteado no introdujo regresiones. Encontró y corrigió 7 hallazgos:
  - `views/productos/create.php`/`update.php` — `<div class="container-fluid">` sin su `</div>` de cierre tras el refactor a cards, rompiendo el layout/footer de AdminLTE en toda la página.
  - `views/clientes/show.php` — el stat box "Última Compra" seguía leyendo `$compras[0]` (sin filtrar por estado) en vez de `$comprasValidas[0]`, mostrando una venta anulada como la más reciente.
  - `views/productos/update.php` — doble escape en la Vista Previa: `htmlspecialchars()` aplicado sobre campos que `Producto::sanitizarDatos()` ya escapa al guardar (ver patrón "escape-at-storage" documentado en `CLAUDE.md`).
  - `views/productos/update.php` — el refactor a cards eliminó la card "Acciones Rápidas" (cambiar estado desde edición) sin reemplazo; agregada una card "Acciones Adicionales" equivalente a la de `clientes/update.php`.
  - `public/js/modules/productos/vista-previa-producto.js` — formateaba el precio inline en vez de usar el helper compartido `formatCurrency()` de `common-utils.js`.
  - `views/clientes/index.php` — variables PHP calculadas por fila (`$titulo_alerta`, etc.) nunca usadas, ya que el diálogo de confirmación es 100% client-side vía `confirmarCambioEstado()`. Eliminadas.
- **Auditoría impeccable de categorías (18/20→20/20)**: `initializeTooltips()` sin `drawCallback` (no sobrevivían a la paginación de DataTables); `exportOptions.columns` de los botones de reporte incluía la columna "Acciones" (solo tiene botones, sin texto exportable); botones de ícono sin `aria-label` explícito.
- **Auditoría impeccable de empresa (17/20)**: `EmpresaController::actualizarAjax()` no validaba sucursales antes de permitir `estado=0` desde el modal de edición — el botón dedicado "cambiar estado" sí validaba, pero el modal lo bypaseaba al incluir su propio `<select>` de Estado. Agregada la misma validación de negocio en `actualizarAjax()`. También corregidos `table-responsive` redundante con DataTables y tooltips sin `drawCallback`.
- **Auditoría impeccable de sucursales (16/20→18/20)**: `exportOptions.columns` incluía la columna Acciones (mismo bug que categorías); tooltips inicializados directo con `.tooltip()` en vez del helper `initializeTooltips()` (sin `placement` fijo, sin `drawCallback`); `table-responsive` redundante con DataTables.
- **`views/usuarios/show.php` mostraba "usuario no tiene permisos específicos asignados" para todo usuario no-admin**: la pestaña Permisos seguía llamando a `AuthorizationService::obtenerPermisosAsignados()`, que lee de `permisousuario` — tabla eliminada por la migración a RBAC. La consulta fallaba silenciosamente (catch + `error_log`) y devolvía `[]` siempre, dando la falsa impresión de que solo los usuarios "viejos" carecían de permisos. Corregido a `obtenerPermisosUsuario()`, que resuelve los permisos por el rol del usuario vía `rolpermiso`; el mensaje pasa a indicar "permisos heredados del rol X".
- **`views/usuarios/index.php` usaba `badge-pill` en los badges de estado (Activo/Inactivo)**, único módulo del proyecto con ese estilo — clientes y productos usan `badge` plano. Quitado `badge-pill` para consistencia visual con el resto de listados.

### Changed

- **Estandarización de Select2 en todo el proyecto**: se centralizó el fix de altura/responsive de Select2 (bootstrap4) en `public/css/core/common.css` (estaba duplicado en `public/css/modules/dashboard/dashboard.css`); `dashboard.js`, `dashboard_supervisor.js`, `dashboard_vendedor.js` y `sucursales/index-sucursales.js` reemplazan `$('.select2').select2({...})` inline por el helper `initializeSelect2()` de `common-utils.js`. Corregido `views/categorias/index.php` y `views/empresa/index.php`, que tenían `$skip_select2 = true` sin darse cuenta de que ya usaban selects sin estilizar; agregada la clase `select2` a los `<select>` de Estado en `sucursales/index.php` y `empresa/index.php` que no la tenían, con inicialización correcta dentro de `shown.bs.modal` (`dropdownParent`) para los que viven en modales.

### Added

- **Sistema de permisos migrado de asignación individual por usuario a control de acceso basado en roles (RBAC)**: se agregan las tablas `rol` (con los 3 roles del sistema: administrador, supervisor, vendedor) y `rolpermiso` (pivot rol↔permiso); `usuarios.idrol` reemplaza a la antigua columna `cargo`, y `permisousuario` (asignación granular por usuario) se elimina por completo — los permisos ahora se administran por rol, no por cuenta individual.
  - Nuevo módulo `views/roles/` (`models/Rol.php`, `controllers/rol/RolController.php`) para crear, renombrar, activar/desactivar y eliminar roles. Un rol de sistema no puede renombrarse ni cambiar su dashboard, no puede eliminarse, y no se puede desactivar el último rol administrador activo (evita lockout) — reglas validadas en el servidor, no solo ocultando opciones en el HTML.
  - Nueva pantalla `views/roles/permisos.php`: matriz rol×permiso con un checkbox por celda, agrupada por categoría; un botón "Guardar" por columna persiste esa columna completa en una transacción. Nadie puede editar los permisos de su propio rol. `views/permisos/index.php` pasa a ser un catálogo de solo lectura con la matriz inversa (qué roles tiene cada permiso). Eliminado `controllers/permisos/cambiar_estado.php` (endpoint huérfano, código muerto).
  - `AuthorizationService` resuelve permisos y estado de administrador contra `rol`/`rolpermiso` en vez de `usuarios.cargo`/`permisousuario`, con memo-cache por request.
  - `views/usuarios/create.php`/`update.php` seleccionan el rol del usuario con un Select2 (en vez de un `<select>` de 3 opciones hardcodeadas) y ya no muestran un checklist de permisos individual — los permisos se heredan del rol. Las validaciones anti-escalada de privilegios ahora se basan en `rol.es_admin`, validando tanto el rol solicitado como el rol actual del usuario objetivo.
  - `index.php` despacha el dashboard según el rol del usuario (resuelto siempre contra la base de datos, nunca contra el dato de sesión) en vez de un `switch` sobre el cargo; toda la lectura de rol en dashboards, sesión, login y listados de usuario se migró de la columna `cargo` a la relación con `rol`.
  - Migración de base de datos aditiva con backfill de `idrol` por nombre de rol y unión de las asignaciones individuales existentes hacia `rolpermiso`, sin pérdida de acceso para ningún usuario, seguida de la limpieza final: `usuarios.cargo` eliminada, `usuarios.idrol` pasa a `NOT NULL`, y `permisousuario` eliminada (con respaldo previo). `schema.sql` actualizado para instalaciones nuevas.
  - Verificado en Playwright con las 3 cuentas demo (administrador, supervisor, vendedor): menús, accesos, dashboards y formularios de usuario se comportan igual que antes de la migración, y los intentos de escalada de privilegios por POST directo (evadiendo el HTML) siguen siendo rechazados.

## [1.1.7] - 2026-08-18

### Added

- **Preselección de cliente al crear una venta desde su ficha**: el botón "Nueva Venta" en `views/clientes/show.php` enlaza a `views/ventas/create.php?cliente=<idcliente>`; `create-venta.js` lee ese parámetro y preselecciona el cliente correspondiente en el formulario si existe entre los disponibles.

### Changed

- **`views/productos/update.php` migrado al formato de cards por sección** usado en `create.php` (1.1.6): reemplaza el formulario monolítico anterior por secciones agrupadas (datos generales, precios/stock, imagen, estado), con la misma card "Vista Previa" dinámica en el sidebar.
- **`public/js/modules/productos/vista-previa-producto.js` (nuevo)**: lógica de la card "Vista Previa" extraída a un archivo compartido entre `create-producto.js` y `update-productos.js` (ambos formularios usan los mismos IDs de campo), evitando duplicar el listener de `input`/`change`.
- **`views/clientes/{create,update,show}.php` migrados al mismo formato de cards por sección** que productos (sin card de Vista Previa — decisión explícita, clientes no tiene un preview visual equivalente al de producto). `views/clientes/index.php` ajustado a la misma línea visual.
- **`views/productos/partials/vista_previa.php` (nuevo)**: markup de la card "Vista Previa" extraído a un partial incluido por `create.php` y `update.php`, mismo criterio de no duplicar HTML entre ambos formularios.
- **`ClienteController::getHistorialCompras($idcliente)` (nuevo)**: delega en `Venta::getPorCliente()`; alimenta la pestaña de historial de compras real en `views/clientes/show.php` (antes placeholder).
- **`confirmarCambioEstado()` (nuevo, `public/js/core/common-utils.js`)**: helper compartido que reemplaza el bloque de confirmación SweetAlert2 + `submitCsrfForm` duplicado en `show-producto.js` e `index-productos.js`/`index-clientes.js` para activar/desactivar registros. Sigue la misma convención de `UsuarioController::cambiarEstadoUsuario()`: el endpoint espera el estado **actual**, no el deseado.
- `ClienteController`: eliminada la dependencia de `ImagenService` (no usada — clientes no maneja subida de imágenes), detectada como código muerto en la auditoría de seguridad de clientes (2026-08-18).

## [1.1.6] - 2026-08-11

### Seguridad

- **Escalada de privilegios en `UsuarioController::guardar()`/`actualizar()`**: el campo `cargo` se tomaba crudo de `$_POST` sin whitelist ni chequeo de rol, permitiendo que cualquier usuario con el permiso granular `usuarios` (no solo `administrador`) se auto-asignara o asignara a otro `cargo=Administrador`. Ahora se valida contra `AuthorizationService::esAdministrador()`. `create.php`/`update.php` deshabilitan además la opción "Administrador" del `<select>` para quien no es admin (defensa en profundidad, no reemplaza la validación server-side).
- Corolario del fix anterior: la validación original solo cubría el cargo _nuevo_ solicitado, dejando sin protección la modificación de una cuenta que _ya_ era Administrador (un no-admin podía degradarla enviando `cargo=Vendedor`, cambiarle la contraseña, o desactivarla vía `cambiarEstadoUsuario()`, evadiendo el check porque el valor nuevo ya no era "Administrador"). Ahora se valida **ambos lados** — cargo nuevo solicitado y cargo actual del registro objetivo — antes de permitir cualquier modificación en `actualizar()` y `cambiarEstadoUsuario()`.

### Fixed

- **Doble-toggle de estado en `views/usuarios/show.php`**: el botón "Activar/Desactivar" enviaba el estado ya invertido (`estadoActual == 1 ? 0 : 1`), pero `UsuarioController::cambiarEstadoUsuario()` espera el estado _actual_ y hace el toggle internamente. El resultado era que el backend volvía a invertirlo y el estado en BD quedaba sin cambios, aunque el mensaje flash indicara lo contrario. Corregido `show-usuario.js` para enviar el estado actual, igual que `index-usuarios.js`.
- Doble escape (`htmlspecialchars()` sobre datos ya escapados por `sanitizarDatos()` al guardar) en `views/usuarios/perfil.php` y `views/usuarios/update.php` — mismo patrón "escape-at-storage" ya documentado para ventas/compras.
- `views/usuarios/{create,update}.php` redirigían con `header('Location: index.php')` (ruta relativa dependiente del directorio actual) en vez de `$URL`, igual que el resto del proyecto.
- Tooltips con `placement: 'auto'` en tablas de acciones (`btn-group` de la última columna) eran inconsistentes cerca de los bordes de la card. `initializeTooltips()` ahora usa `placement: 'top'` fijo.
- Tooltips de `usuarios/index.php`, `ventas/index.php` y `compras/index.php` dejaban de inicializarse tras la primera página del paginador de DataTables; agregado `drawCallback` que reinvoca `initializeTooltips()` en cada redibujado.
- `.btn-group>.btn-sm+.btn-sm` en `common.css` usaba `margin-left: 2px`, rompiendo el empalme sin costuras entre botones que Bootstrap/AdminLTE logra con `-1px`.

### Changed

- Eliminado `UsuarioController::actualizarClavePerfilAjax()`, endpoint de cambio de contraseña de perfil no referenciado (código muerto duplicando el flujo real de `perfil-usuario.js`).
- `views/usuarios/perfil.php`: agregados atributos ARIA (`role="tab"`/`role="tabpanel"`, `aria-controls`, `aria-selected`) a las pestañas de Bootstrap y botón de mostrar/ocultar contraseña en los campos de cambio de clave.
- Eliminada la regla de touch targets duplicada en `public/css/modules/ventas/ventas.css` (`@media (hover: none) and (pointer: coarse)`); ya cubierta globalmente por `common.css` para cualquier `.btn-group>.btn-sm`.

## [1.1.5] - 2026-08-09

### Seguridad

- Eliminado `controllers/compras/actualizar_compra.php`: llamaba a `CompraController::completar()` con permiso y CSRF verificados pero sin el ownership check (comparar `idusuario`) que sí tiene `cambiar_estado_compra.php` — mismo IDOR ya corregido en ventas (1.1.4) y en el resto de compras. Confirmado endpoint huérfano (sin referencias en ningún JS ni vista) antes de eliminarlo.
- `controllers/compras/cambiar_estado_compra.php` ahora verifica que la compra pertenezca al usuario (o rol administrador) antes de completar/cancelar, mismo criterio ya aplicado en ventas.

### Fixed

- Doble escape en `views/compras/show.php` (`usuario_nombre`, `observaciones`, `producto_nombre`, `producto_codigo`): estos campos ya se escapan al guardarse (`sanitizarDatos()`), volver a aplicar `htmlspecialchars()` en la vista producía entidades duplicadas (`&amp;lt;` en vez de `&lt;`).
- `data-productos` en `views/compras/create.php` exponía el array completo de productos (incluyendo inactivos) tal como lo devuelve el modelo; ahora se filtra a productos activos y se proyectan solo los campos que usa el JS del carrito.
- Reemplazado `FILTER_SANITIZE_STRING` (deprecado desde PHP 8.1) por una validación directa en `controllers/compras/cambiar_estado_compra.php`.
- `views/compras/show.php` incluía `header.php` (que ya emite HTML) antes del ownership check; al no ser administrador el `header('Location: index.php')` fallaba silenciosamente (headers ya enviados) y dejaba una página en blanco en vez de redirigir con el mensaje flash. Reordenado para obtener la compra y validar propiedad antes de incluir `header.php`, mismo orden que `views/ventas/show.php`.

### Changed

- `CompraController`: agregados los métodos de cálculo puro `calcularDetalleLinea()`, `calcularDesgloseDetalles()`, `calcularTotales()` (con guarda contra división por cero) y `obtenerInfoEstado()`. `views/compras/show.php` e `views/compras/index.php` ya no calculan subtotales ni el mapeo estado→badge inline; consumen estos métodos.
- `views/compras/create.php` / `create-compra.js`: la tabla de productos ahora clona una fila plantilla oculta (`#fila-base`) en vez de construir el HTML por concatenación de strings, mismo patrón que `views/ventas/create.php`. Quitados los inputs ocultos `totalcompra` y `estado` (ignorados server-side; el total se recalcula siempre en `CompraController::guardar()` y toda compra nueva nace en estado 1).
- `public/css/modules/compras/compras.css`: quitadas las reglas `.d-none`, `.is-invalid`, `.invalid-feedback` e `input[readonly]` por duplicar estilos ya provistos por Bootstrap.
- Corregido el docblock de `CompraController::completar()` para reflejar que es un no-op sin caso de uso actual (ningún flujo del sistema lo invoca).

## [1.1.4] - 2026-07-28

### Fixed

- **IDOR en `controllers/ventas/anular_venta.php`**: solo verificaba el permiso de módulo `'ventas'`, sin comprobar que la venta perteneciera al usuario — un vendedor podía anular la venta de otro cambiando el `id` en el POST. Ahora valida propiedad (o rol administrador) antes de anular, mismo criterio que `views/ventas/show.php`. Agregado `VentaController::obtenerPorId()` para esta verificación.
- **Autoría de venta falsificable**: `VentaController::prepararDatosVenta()` tomaba `idusuario` de `$_POST`, permitiendo atribuir una venta a otro vendedor editando el DOM. Ahora se usa siempre `$_SESSION['usuario_id']`; retirado el input oculto correspondiente en `views/ventas/create.php`.
- **`views/ventas/recibo.php` sin verificación de propiedad**: cualquier usuario con permiso `'ventas'` podía generar el PDF de la venta de otro vendedor cambiando `?id=`. Agregada la misma validación de propiedad que `show.php`.
- **Self-XSS en el modal de confirmación de `create-venta.js`**: el campo "Observaciones" se interpolaba sin escapar en el `html` de SweetAlert2 antes de enviar el formulario. Escapado en el cliente antes de interpolarse.
- Eliminado un `error_log()` sin límite en `views/ventas/index.php` que registraba cada método de pago no reconocido, vector de flood del log del servidor.
- Corregida precedencia rota de `??` en `views/ventas/recibo.php` (`usuario_registro`), que impedía que el fallback se aplicara realmente.

### Changed

- `views/ventas/show.php`: rediseño de layout (columna izquierda = resumen/acciones tipo `sidebar-sticky`, derecha = detalle), siguiendo el mismo patrón ya usado en `usuarios/show.php` y `productos/show.php`. La información general pasó de `form-group` a `list-group-unbordered`, con ícono y total destacado antes de la lista, y los botones Volver/Anular movidos a la misma card.
- Tema visual unificado a `card-info` en las cards de `views/ventas/show.php` (Información General, Método de Pago, Productos Vendidos, Información Adicional).
- Accesibilidad: agregado `aria-live="polite"` a los mensajes dinámicos de `views/ventas/create.php` (`#diferencia-pago`, `#cliente-feedback`) y `aria-label` a los botones de acción ícono-only del historial en `views/ventas/index.php` (ver, anular, imprimir).
- Movida lógica de negocio fuera de las vistas de ventas hacia `VentaController`/`Venta`: `views/ventas/index.php` calculaba el texto/clase de badge del método de pago con un `switch` inline y hacía dos consultas por fila (`tienePagosMixtos()` + `obtenerMetodosPago()`, patrón N+1); `views/ventas/show.php` calculaba subtotal/descuento/total pagado y el mapeo ícono/badge por método de pago directamente en la vista. Ahora `VentaController::index()` adjunta pagos e información de método de pago ya calculada a cada venta (una sola consulta batch vía el nuevo `Venta::getMetodosPagoPorVentas()`), y `VentaController::calcularTotales()`/`obtenerIconoMetodoPago()` encapsulan el resto; las vistas solo consumen los datos ya resueltos.
- `views/ventas/recibo.php` (generación de PDF) calculaba subtotal/descuento/neto por línea de producto, acumulaba subtotal/descuento general, sumaba recibido/cambio de los pagos, y determinaba "pago mixto" con un criterio (`count($pagos) > 1`) distinto y menos correcto que el ya centralizado (contaba pagos, no métodos distintos). Agregados `VentaController::calcularDesgloseDetalles()`, `calcularResumenPagos()` y `esPagoMixto()` (reutilizado también por `calcularInfoMetodoPago()`); verificado que el PDF generado es byte-idéntico en contenido antes/después del refactor.

### Note

- Documentado en `CLAUDE.md` el patrón "escape-at-storage" (`sanitizarDatos()` en los modelos escapa con `htmlspecialchars()` al guardar, no al mostrar) como deuda técnica conocida: las vistas no deben re-escapar esos campos al mostrarlos (produce doble escape), pero cualquier endpoint nuevo que escriba en esas tablas debe pasar por el `sanitizarDatos()` correspondiente para no dejar una vía de XSS almacenado.

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

[1.2.2]: https://github.com/WorkTeam01/FlowPOS/compare/1.2.1...1.2.2
[1.2.1]: https://github.com/WorkTeam01/FlowPOS/compare/1.2.0...1.2.1
[1.2.0]: https://github.com/WorkTeam01/FlowPOS/compare/1.1.7...1.2.0
[1.1.7]: https://github.com/WorkTeam01/FlowPOS/compare/1.1.6...1.1.7
[1.1.6]: https://github.com/WorkTeam01/FlowPOS/compare/1.1.5...1.1.6
[1.1.5]: https://github.com/WorkTeam01/FlowPOS/compare/1.1.4...1.1.5
[1.1.4]: https://github.com/WorkTeam01/FlowPOS/compare/1.1.3...1.1.4
[1.1.3]: https://github.com/WorkTeam01/FlowPOS/compare/1.1.2...1.1.3
[1.1.2]: https://github.com/WorkTeam01/FlowPOS/compare/1.1.1...1.1.2
[1.1.1]: https://github.com/WorkTeam01/FlowPOS/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/WorkTeam01/FlowPOS/compare/1.0.1...1.1.0
[1.0.1]: https://github.com/WorkTeam01/FlowPOS/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/WorkTeam01/FlowPOS/releases/tag/1.0.0
