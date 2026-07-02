# Changelog

Todos los cambios importantes de este proyecto se documentan en este archivo.

Este formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/)
y el versionado sigue [Semantic Versioning](https://semver.org/lang/es/).

## [Unreleased]

### Added
- Se añadió `APP_VERSION` en `.env` y `.env.example` para versionado explícito de la app.

### Changed
- La app ahora usa `APP_VERSION` desde `.env` en tiempo de ejecución (footer, login y `window.APP.version`).
- Se añadió `version` en la configuración global `config/config.php` (`app.version`).

## [1.0.0] - 2026-07-01

### Added

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
- Capa de autorización por permisos con `AuthorizationService` para validaciones por ID o nombre de permiso.
- Reglas de integridad en base de datos (claves foráneas, `UNIQUE` y `CHECK`) para reducir inconsistencias de datos.

### Technical

- Arquitectura MVC clásica en PHP sin framework y sin paso de build.
- Flujo de páginas con includes PHP (sin router central/front controller).
- Configuración por variables de entorno (`.env`) para URL, timezone, debug y conexión a base de datos.
- Compatibilidad con PHP 7.4+, MariaDB/MySQL y frontend basado en AdminLTE/Bootstrap.
