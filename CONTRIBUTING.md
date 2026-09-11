# Contributing Guide

¡Gracias por tu interés en contribuir a este proyecto!

## Cómo empezar

1. Haz un fork del repositorio.
2. Crea una rama para tu cambio:

```bash
git checkout -b feat/mi-mejora
```

3. Realiza cambios pequeños y enfocados.
4. Prueba tu cambio en entorno local (XAMPP/LAMP) antes de enviar PR. Los cambios que afecten permisos o flujos por rol deben probarse con las tres cuentas demo (administrador, supervisor, vendedor). Si el cambio toca UI o accesibilidad, valida también con axe-core (WCAG 2.1 A/AA) los estados condicionales (modal abierto, carrito con datos, etc.), no solo el DOM inicial.
5. Actualiza documentación relacionada cuando aplique (`README.md`, `CLAUDE.md`, `PROMPTS.md`, `CHANGELOG.md`).
6. Si tu cambio impacta comportamiento funcional, regístralo en `CHANGELOG.md` (sección `Unreleased` o la versión en curso) y mantén `APP_VERSION` en `.env` sincronizado con la última versión publicada.
   - Si modificaste `public/css/core/common.css` o cualquier asset servido con `?v=<APP_VERSION>` (CSS/JS de módulo), **sube `APP_VERSION`** aunque el cambio parezca menor: el query string solo cambia con la versión, y sin el bump los usuarios existentes siguen recibiendo la copia cacheada.
7. Abre un Pull Request con contexto claro.

## Versionado

El proyecto sigue versionado semántico (`MAJOR.MINOR.PATCH`). El registro de cambios vive en `CHANGELOG.md` y la versión funcional vigente en `APP_VERSION` (`.env`); ambos deben quedar alineados en el mismo PR que introduce el cambio. Los tags de git se nombran sin prefijo (`1.2.1`, no `v1.2.1`).

## Entorno local

Este proyecto usa PHP + MariaDB sin build step ni gestor de paquetes.

Pasos recomendados:

```bash
cp .env.example .env   # ajusta DB_USER / DB_PASS según tu instalación
mysql -u root -e "CREATE DATABASE flowpos CHARACTER SET utf8mb4;"
mysql -u root flowpos < database/schema.sql
mysql -u root flowpos < database/seed.sql
sudo /opt/lampp/lampp start
```

`database/schema.sql` ya incluye el esquema RBAC; sobre una base preexistente aplica en cambio las migraciones de `database/migrations/` en orden.

Luego abre `http://localhost/FlowPOS/`.

## Alcance de contribuciones

Se aceptan contribuciones en:

- Corrección de bugs
- Mejoras de seguridad
- Mejoras de UX/UI
- Refactors sin romper comportamiento
- Documentación y ejemplos

## Lineamientos de código

- Mantén consistencia con el estilo y patrones existentes.
- Evita cambios masivos no relacionados con el objetivo del PR.
- No incluyas credenciales, tokens ni datos sensibles.
- Mantén compatibilidad con el stack actual del proyecto.
- Mantén accesibilidad WCAG AA: `aria-label` en botones/íconos sin texto visible y en el botón de colapso de panel; mensajes de error enlazados a su campo (`aria-describedby` + `aria-invalid` + `invalid-feedback`); usa los overrides de contraste y de touch targets táctiles (44px) ya centralizados en `common.css` — no dupliques reglas por módulo.

## Pull Requests

Incluye en la descripción:

- **Qué cambia**
- **Por qué cambia**
- **Cómo probarlo**
- **Capturas** (si afecta UI)

Checklist mínima:

- [ ] El cambio está acotado al objetivo.
- [ ] Probado localmente.
- [ ] No rompe flujos existentes.
- [ ] Accesibilidad WCAG AA verificada si el cambio toca UI (contraste, `aria-label`, estados de error accesibles).
- [ ] Documentación actualizada (si aplica): `README.md`, `CLAUDE.md`, `PROMPTS.md`.
- [ ] `CHANGELOG.md` actualizado y `APP_VERSION` sincronizado (si aplica; obligatorio si tocaste `common.css` o assets versionados).
- [ ] Cambios de permisos/rol probados con las tres cuentas demo.

## Reportar issues

Al crear un issue, incluye:

- Comportamiento esperado
- Comportamiento actual
- Pasos para reproducir
- Entorno (PHP, MariaDB, SO, navegador)

## Código de Conducta

`CODE_OF_CONDUCT.md` es un archivo opcional en open source que define reglas de convivencia para la comunidad (respeto, colaboración y manejo de conflictos).  
No es obligatorio para contribuir aquí por ahora, pero puede añadirse más adelante si se desea formalizar ese aspecto.
