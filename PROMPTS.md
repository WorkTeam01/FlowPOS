# PROMPTS.md — Sistema de Ventas

> Plantillas de prompts para trabajar con este proyecto.
> Úsalas como base — adapta los bloques `[Tarea]` y `[Contexto]` a lo que necesites en cada sesión.
> El `CLAUDE.md` siempre debe estar disponible para el agente como contexto base.

---

## Cómo usar este archivo

Cada plantilla sigue la estructura de 5 ejes del prompt profesional:

| Eje                   | Pregunta          | Para qué sirve                                    |
| --------------------- | ----------------- | ------------------------------------------------- |
| **Rol**               | ¿Quién eres?      | Define el nivel y especialidad que asume la IA    |
| **Contexto**          | ¿Dónde estamos?   | El proyecto, stack y módulo activo                |
| **Tarea exacta**      | ¿Qué necesitas?   | Concreto y específico — nunca genérico            |
| **Restricciones**     | ¿Qué límites hay? | Convenciones del proyecto que NO se pueden romper |
| **Formato de salida** | ¿Cómo lo quieres? | Estructura del output esperado                    |

> **Regla de oro:** Cuanto más específico sea el bloque `[Tarea]`,
> menos correcciones necesitarás después.

**Reglas de uso:**

- **Siempre carga el CLAUDE.md** al inicio de la sesión si la herramienta no lo carga automáticamente.
- **Un prompt por subtarea.** Pedir "el módulo completo" en un solo prompt produce resultados genéricos.
- **Si el output no encaja**, no corrijas manualmente primero — ajusta `[Restricciones]` y repite.
- **El spec antes que el código.** Define qué debe hacer antes de pedir que lo implemente.
- **Si hay cambios funcionales,** pide también actualización de `CHANGELOG.md`.
- **Guarda los prompts que funcionen bien** en este archivo como nuevas plantillas.

---

## Plantilla base (copia esto y rellena)

```
[Rol]
Actúa como desarrollador PHP Senior especializado en arquitectura MVC
sin framework y desarrollo web con PHP puro.

[Contexto]
Proyecto: Sistema de Ventas — PHP MVC personalizado sin router ni Composer.
Stack: PHP 7.4+, MariaDB/MySQL, Apache mod_rewrite, JavaScript vanilla ES6+,
       Bootstrap 4 / AdminLTE 3, jQuery, DataTables, SweetAlert2, Select2, TCPDF.
Sin framework, sin Composer — dependencias incluidas en libs/ y public/.
Versionado: `APP_VERSION` en `.env` + registro de cambios en `CHANGELOG.md`.
Módulo activo: _______________

[Tarea]
_______________

[Restricciones]
- MVC sin router: las vistas incluyen controladores directamente con require/include
- Todos los archivos de vista incluyen views/layouts/session.php primero, luego header.php
- CSRF obligatorio en formularios POST: generateCSRFToken() en vista, verifyCSRFToken() en controlador
- Conexión a BD: Conexion::getInstance()->getConnection() — nunca instanciar PDO directamente
- Mensajes flash: $_SESSION['mensaje'] + $_SESSION['icono'] (success/error/warning/info)
- Subida de imágenes: siempre a través de ImagenService
- DataTables para listados; SweetAlert2 para confirmaciones y notificaciones
- No introducir dependencias externas sin evaluar el impacto
- Si se modifica comportamiento funcional: actualizar `CHANGELOG.md`

[Formato de salida]
_______________
```

---

## Plantilla 1 — Generar código nuevo (feature)

Usar cuando: implementar un requerimiento nuevo.

```
[Rol]
Actúa como desarrollador PHP Senior especializado en arquitectura MVC
sin framework y desarrollo web con PHP puro.

[Contexto]
Proyecto: Sistema de Ventas — PHP MVC personalizado sin router ni Composer.
Stack: PHP 7.4+, MariaDB/MySQL, Apache mod_rewrite, JavaScript vanilla ES6+,
       Bootstrap 4 / AdminLTE 3, jQuery, DataTables, SweetAlert2, Select2, TCPDF.
Versionado: `APP_VERSION` en `.env` + `CHANGELOG.md`.

Estructura de archivos relevante:
- controllers/[modulo]/[Modulo]Controller.php  — clase con lógica de negocio
- controllers/[modulo]/[accion]_[modulo].php   — scripts de acción invocados por vistas
- models/[Modulo].php                          — acceso a datos con PDO
- views/[modulo]/[vista].php                   — vista PHP que incluye layouts y controlador
- public/js/modules/[modulo]/index-[modulo].js — JS del módulo
- views/layouts/session.php                    — siempre el primer include en cada vista
- views/layouts/header.php                     — incluido después de session
- views/layouts/footer.php + mensajes.php      — cierre HTML y mensajes flash

BD relevante:
- empresa, sucursal — estructura multi-sucursal
- usuarios (cargo: administrador/supervisor/vendedor, idsucursal)
- permiso, permisousuario — permisos granulares por usuario
- producto, categoria — catálogo con stock
- cliente — registro de clientes
- venta, detalleventa, pagoventa — ventas con pagos mixtos
- compra, detallecompra — ingreso de inventario
- sesionusuario — auditoría de sesiones

[Tarea]
Implementar [nombre exacto del requerimiento].

Descripción: [criterios de aceptación]

[Restricciones]
- Seguir el patrón MVC del módulo de ventas como referencia
- CSRF en todos los formularios POST: generateCSRFToken() en vista, verifyCSRFToken() en controlador
- Sanitización con htmlspecialchars() en vistas — nunca en modelos ni controladores
- Queries con PDO preparado — nunca concatenar variables en SQL
- Mensajes flash: $_SESSION['mensaje'] + $_SESSION['icono'] antes de cualquier redirect
- Subida de archivos: siempre ImagenService — nunca move_uploaded_file() directo
- Control de acceso: requireRole() para páginas, AuthorizationService para acciones
- DataTables para listados; SweetAlert2 para confirmaciones — nunca alert()/confirm() nativo
- Select2 para dropdowns; con dropdownParent si está dentro de un modal
- AJAX: devolver JSON con header('Content-Type: application/json') + json_encode()
- No introducir librerías externas nuevas
- Si cambia funcionalidad visible: actualizar `CHANGELOG.md` en `Unreleased`

[Formato de salida]
Devuelve en este orden:
1. Lista de archivos que se crean o modifican
2. SQL si hay cambios en BD (ALTER TABLE / INSERT de permisos)
3. Código de cada archivo
4. Checklist de testing manual (casos exitosos + edge cases)
```

---

## Plantilla 2 — Debuggear un error

Usar cuando: algo no funciona y no está claro por qué.

```
[Rol]
Actúa como desarrollador PHP Senior especializado en debugging
de aplicaciones MVC y MySQL/MariaDB.

[Contexto]
Proyecto: Sistema de Ventas — PHP MVC personalizado sin router ni Composer.
Stack: PHP 7.4+, PDO/MariaDB, jQuery, AdminLTE 3, JavaScript vanilla.
Archivo donde ocurre el error: [ruta completa]
Función/sección afectada: [nombre o descripción]

[Tarea]
Tengo este error:
[pega el mensaje de error exacto o el comportamiento inesperado]

Código actual:
[pega el bloque de código relevante — no todo el archivo]

Lo que debería hacer:
[describe el comportamiento esperado]

Lo que intenté que no funciona:
[describe lo que ya probaste]

[Restricciones]
- No cambiar la arquitectura del archivo — solo corregir el problema específico
- Mantener las convenciones de naming del proyecto (español para variables de negocio)
- Si el fix requiere cambiar más de un archivo, indicarlo antes de proponer código

[Formato de salida]
1. Diagnóstico: causa raíz del error en 2-3 líneas
2. Fix: código corregido con comentario explicando el cambio
3. Por qué pasó: explicación breve para no repetirlo
```

---

## Plantilla 3 — Code review antes del merge

Usar cuando: antes de hacer merge de una rama, o cuando el código funciona
pero algo "huele mal".

```
[Rol]
Actúa como Tech Lead PHP con experiencia en code review de sistemas MVC,
seguridad web y buenas prácticas en PHP sin framework.

[Contexto]
Proyecto: Sistema de Ventas — PHP MVC personalizado sin router ni Composer.
Cambio implementado: [descripción breve]
Versionado: `APP_VERSION` en `.env` + `CHANGELOG.md`.

[Tarea]
Revisa el siguiente código antes del merge.

[pega el código o el diff]

[Restricciones]
Evalúa específicamente:
- Seguridad: SQL injection (PDO preparado), XSS (htmlspecialchars en vistas),
  CSRF (generateCSRFToken + verifyCSRFToken), sesiones mal validadas
- Conexión: Conexion::getInstance() usado correctamente — nunca PDO directo
- Autorización: requireRole() en páginas, AuthorizationService en acciones
- Uploads: ImagenService — nunca move_uploaded_file() directo
- Flash messages: $_SESSION['mensaje'] + $_SESSION['icono'] antes de redirect
- AJAX: JSON válido con header correcto
- Casos edge que podrían fallar en producción

[Formato de salida]
OK  - Lo que está bien (al menos 2 cosas)
OBS - Observaciones (mejoras no críticas, con sugerencia)
FIX - Problemas a corregir antes del merge (con código corregido)
```

---

## Plantilla 4 — Consulta de arquitectura

Usar cuando: hay una decisión técnica importante antes de implementar,
o cuando no está claro cómo integrar algo nuevo.

```
[Rol]
Actúa como arquitecto de software PHP con experiencia en sistemas MVC
custom sin framework, diseño de base de datos y patrones de diseño.

[Contexto]
Proyecto: Sistema de Ventas — PHP MVC personalizado sin router ni Composer.
Stack actual: PHP 7.4+, MariaDB, PDO Singleton (Conexion::getInstance()),
              session.php como hub de autenticación/autorización,
              AuthorizationService para permisos granulares,
              ImagenService para uploads, TCPDF para PDFs.
Versionado: `APP_VERSION` en `.env` + `CHANGELOG.md`.
Módulos existentes: auth, dashboard (admin/supervisor/vendedor),
                    ventas, compras, productos, clientes, usuarios,
                    empresa, sucursal, permisos, sesiones.
Patrón: MVC sin router — vistas incluyen controladores directamente.

[Tarea]
Necesito decidir: [describe la decisión técnica]

Opciones que estoy considerando:
- Opción A: [describe]
- Opción B: [describe]

[Restricciones]
- No introducir frameworks (ni Laravel, ni Symfony, ni Slim)
- No introducir Composer ni dependencias externas sin evaluar impacto
- Mantener el patrón de includes directos — sin router
- La solución debe poder mantenerse sin herramientas de build
- Nuevos servicios van en services/ (ImagenService y AuthorizationService como referencia)

[Formato de salida]
1. Recomendación directa (cuál opción y por qué en 3 líneas)
2. Trade-offs de cada opción (tabla si aplica)
3. Impacto en el resto del sistema
4. Primeros pasos concretos para implementar la opción recomendada
```

---

## Plantilla 5 — Nuevo módulo completo (spec-first)

Usar cuando: se va a implementar un módulo nuevo de principio a fin.
Completar el spec antes de pedir código.

```
[Rol]
Actúa como desarrollador PHP Senior especializado en arquitectura MVC
sin framework, diseño de base de datos y seguridad web.

[Contexto]
Proyecto: Sistema de Ventas — PHP MVC personalizado sin router ni Composer.
Stack: PHP 7.4+, MariaDB/MySQL, Apache mod_rewrite, JavaScript vanilla ES6+,
       Bootstrap 4 / AdminLTE 3, jQuery, DataTables, SweetAlert2, Select2, TCPDF.
Versionado: `APP_VERSION` en `.env` + `CHANGELOG.md`.

BD existente relevante:
- empresa (id, nombre, nit, direccion, telefono, logo, ...)
- sucursal (id, idempresa FK, nombre, direccion, ...)
- usuarios (id, nombre, apellido, email, password, cargo, idsucursal FK, foto, estado)
- permiso (id, nombre, descripcion) — permisos del sistema
- permisousuario (idusuario, idpermiso) — asignación de permisos por usuario
- producto (id, nombre, idcategoria FK, precio, stock, foto, estado)
- categoria (id, nombre, descripcion, estado)
- cliente (id, nombre, apellido, ci, telefono, email, foto, estado)
- venta (id, idusuario FK, idcliente FK, fecha, total, estado)
- detalleventa (id, idventa FK, idproducto FK, cantidad, preciounitario, subtotal)
- pagoventa (id, idventa FK, monto, metodopago: efectivo/tarjeta/qr/transferencia)
- compra (id, idusuario FK, fecha, total, observacion)
- detallecompra (id, idcompra FK, idproducto FK, cantidad, preciounitario, subtotal)
- sesionusuario (id, idusuario FK, fechainicio, fechafin, ip)

Módulo de referencia para patrones: ventas.

[Tarea]
Implementar el módulo [nombre] con las siguientes funcionalidades:
[lista de operaciones: CRUD, reportes, AJAX, etc.]

Criterios de aceptación:
[pega los criterios]

[Restricciones]
- Seguir el patrón MVC del módulo ventas como referencia exacta
- CSRF en todos los formularios POST y endpoints AJAX
- PDO preparado en todos los queries — sin concatenación de variables
- htmlspecialchars() en vistas para todo output de usuario
- Mensajes flash: $_SESSION['mensaje'] + $_SESSION['icono'] antes de redirect
- DataTables para listados; SweetAlert2 para confirmaciones y notificaciones
- Select2 para dropdowns; con dropdownParent si está dentro de un modal
- Autorización: requireRole() en la vista, AuthorizationService en acciones
- Registrar permisos nuevos en schema.sql (INSERT en tabla permiso)
- Uploads: ImagenService — nunca move_uploaded_file() directo
- No introducir librerías externas nuevas
- Si se agregan cambios funcionales: documentarlos en `CHANGELOG.md` (`Unreleased`)

[Formato de salida]
Devuelve en este orden:
1. SQL: ALTER/CREATE TABLE + INSERT de permisos en schema.sql
2. models/[Modulo].php
3. controllers/[modulo]/[Modulo]Controller.php
4. controllers/[modulo]/[acciones].php (scripts de acción POST/AJAX)
5. views/[modulo]/index.php
6. views/[modulo]/crear.php (si aplica)
7. views/[modulo]/editar.php (si aplica)
8. public/js/modules/[modulo]/index-[modulo].js
9. Checklist de testing manual
```

---

_Última actualización: 2026-07-01_
_Mantener sincronizado con CLAUDE.md al hacer cambios de arquitectura._
