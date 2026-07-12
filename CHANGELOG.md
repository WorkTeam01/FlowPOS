# Changelog

Todos los cambios importantes de este proyecto se documentan en este archivo.

Este formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/)
y el versionado sigue [Semantic Versioning](https://semver.org/lang/es/).

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
