# Contributing Guide

¡Gracias por tu interés en contribuir a este proyecto!

## Cómo empezar

1. Haz un fork del repositorio.
2. Crea una rama para tu cambio:

```bash
git checkout -b feat/mi-mejora
```

3. Realiza cambios pequeños y enfocados.
4. Prueba tu cambio en entorno local (XAMPP/LAMP) antes de enviar PR.
5. Actualiza documentación relacionada cuando aplique (`README.md`, `CLAUDE.md`, `PROMPTS.md`, etc.).
6. Si tu cambio impacta comportamiento funcional, registra el cambio en `CHANGELOG.md`.
7. Abre un Pull Request con contexto claro.

## Entorno local

Este proyecto usa PHP + MariaDB sin build step ni gestor de paquetes.

Pasos recomendados:

```bash
cp .env.example .env
mysql -u root -e "CREATE DATABASE flowpos CHARACTER SET utf8mb4;"
mysql -u root flowpos < schema.sql
mysql -u root flowpos < seed.sql
sudo /opt/lampp/lampp start
```

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
- [ ] Documentación actualizada (si aplica).
- [ ] `CHANGELOG.md` actualizado (si aplica).

## Reportar issues

Al crear un issue, incluye:

- Comportamiento esperado
- Comportamiento actual
- Pasos para reproducir
- Entorno (PHP, MariaDB, SO, navegador)

## Código de Conducta

`CODE_OF_CONDUCT.md` es un archivo opcional en open source que define reglas de convivencia para la comunidad (respeto, colaboración y manejo de conflictos).  
No es obligatorio para contribuir aquí por ahora, pero puede añadirse más adelante si se desea formalizar ese aspecto.
