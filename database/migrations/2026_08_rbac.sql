-- Migración RBAC (roles y permisos por rol) — release 1.2.0
-- Idempotente: puede correrse N veces sin error ni duplicar datos.
--
-- FASE 1 (este bloque): tablas `rol` y `rolpermiso` + seed de los 3 roles base.
-- Aditivo puro — no toca `usuarios`, `permiso` ni `permisousuario`, no cambia
-- el comportamiento de la app todavía. Ver docs/plan-rbac-permisos.md.

CREATE TABLE IF NOT EXISTS rol (
  idrol int PRIMARY KEY AUTO_INCREMENT,
  nombre varchar(50) NOT NULL,
  descripcion varchar(255) DEFAULT NULL,
  es_sistema tinyint(1) NOT NULL DEFAULT 0,
  es_admin tinyint(1) NOT NULL DEFAULT 0,
  dashboard varchar(64) NOT NULL DEFAULT 'dashboard_general.php',
  fechacreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  fechaactualizacion DATETIME ON UPDATE CURRENT_TIMESTAMP,
  estado tinyint(1) DEFAULT 1,
  UNIQUE KEY uk_rol_nombre (nombre)
);

CREATE TABLE IF NOT EXISTS rolpermiso (
  idrolpermiso int PRIMARY KEY AUTO_INCREMENT,
  idrol int NOT NULL,
  idpermiso int NOT NULL,
  fechacreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  fechaactualizacion DATETIME ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (idrol) REFERENCES rol(idrol) ON DELETE CASCADE,
  FOREIGN KEY (idpermiso) REFERENCES permiso(idpermiso) ON DELETE CASCADE,
  UNIQUE KEY uk_rol_permiso (idrol, idpermiso)
);

-- Seed con IDs fijos y predecibles: 1=administrador, 2=supervisor, 3=vendedor.
INSERT INTO rol (idrol, nombre, descripcion, es_sistema, es_admin, dashboard, estado) VALUES
 (1, 'administrador', 'Acceso total al sistema', 1, 1, 'dashboard.php', 1),
 (2, 'supervisor', 'Supervisa vendedores y operaciones', 1, 0, 'dashboard_supervisor.php', 1),
 (3, 'vendedor', 'Registra ventas y clientes', 1, 0, 'dashboard_vendedor.php', 1)
ON DUPLICATE KEY UPDATE
  descripcion = VALUES(descripcion),
  es_sistema = 1;

-- FASE 2: backfill de `usuarios.idrol` desde `cargo` (modo espejo — ambas
-- columnas coexisten) + migración de asignaciones individuales de
-- `permisousuario` a `rolpermiso` (unión: nadie pierde acceso) + limpieza de
-- permisos legacy sin uso. Todavía no cambia runtime: la app sigue leyendo
-- `cargo` y `permisousuario` hasta la fase 5 (corte de lectura).

-- Respaldo de permisousuario antes de tocar nada (por si hay que reconstruir).
CREATE TABLE IF NOT EXISTS permisousuario_backup_1_2_0 AS SELECT * FROM permisousuario;

-- Columna aditiva, NULLable — no rompe INSERTs existentes que no la mencionan.
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS idrol int NULL AFTER cargo;

-- Guard: la FK no acepta "IF NOT EXISTS" en MariaDB 10.4, así que se agrega
-- solo si todavía no existe (buscándola por nombre en information_schema).
SET @fk_exists = (
  SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'usuarios'
    AND COLUMN_NAME = 'idrol'
    AND REFERENCED_TABLE_NAME = 'rol'
);
SET @sql_fk = IF(@fk_exists = 0,
  'ALTER TABLE usuarios ADD CONSTRAINT fk_usuarios_idrol FOREIGN KEY (idrol) REFERENCES rol(idrol)',
  'SELECT "FK fk_usuarios_idrol ya existe, se omite"'
);
PREPARE stmt_fk FROM @sql_fk;
EXECUTE stmt_fk;
DEALLOCATE PREPARE stmt_fk;

-- Backfill idempotente: solo toca filas que todavía no tienen idrol asignado.
UPDATE usuarios u
JOIN rol r ON LOWER(TRIM(u.cargo)) = r.nombre
SET u.idrol = r.idrol
WHERE u.idrol IS NULL;

-- GATE MANUAL OBLIGATORIO: si esta consulta devuelve filas, hay usuarios con
-- un `cargo` que no matchea ningún nombre de rol (typo, valor libre, etc.).
-- Resolver a mano (corregir el cargo o asignar idrol directo) antes de seguir
-- con la fase 3+. NUNCA agregar aquí un fallback automático a un rol por defecto.
SELECT idusuario, correo, cargo AS cargo_sin_rol_valido
FROM usuarios
WHERE idrol IS NULL;

-- Migración de permisos individuales -> permisos por rol (estrategia de unión:
-- si cualquier usuario de un rol tenía el permiso, el rol entero lo hereda).
INSERT IGNORE INTO rolpermiso (idrol, idpermiso)
SELECT DISTINCT u.idrol, pu.idpermiso
FROM permisousuario pu
JOIN usuarios u ON u.idusuario = pu.idusuario
WHERE u.idrol IS NOT NULL AND u.estado = 1;

-- Piso mínimo: todo rol con al menos un usuario activo tiene el permiso 'perfil'.
INSERT IGNORE INTO rolpermiso (idrol, idpermiso)
SELECT DISTINCT u.idrol, p.idpermiso
FROM usuarios u
JOIN permiso p ON p.nombre = 'perfil'
WHERE u.idrol IS NOT NULL AND u.estado = 1;

-- El rol admin recibe todos los permisos activos (coherencia visual de la
-- matriz — esAdministrador() ya bypassa el check, esto es solo para que la
-- pantalla de Roles no muestre al admin con permisos "faltantes").
INSERT IGNORE INTO rolpermiso (idrol, idpermiso)
SELECT r.idrol, p.idpermiso
FROM rol r
JOIN permiso p ON p.estado = 1
WHERE r.es_admin = 1;

-- Limpieza del catálogo legacy: 'nueva_compra' y 'nueva_venta' no tienen
-- ninguna referencia en código (confirmado en el diseño de arquitectura).
-- `permisousuario` no tiene ON DELETE CASCADE hacia `permiso`, así que sus
-- asignaciones a estos 2 permisos se limpian primero (ya preservadas intactas
-- en permisousuario_backup_1_2_0). El DELETE en `permiso` sí cascadea a
-- `rolpermiso` por la FK ON DELETE CASCADE.
DELETE pu FROM permisousuario pu
JOIN permiso p ON p.idpermiso = pu.idpermiso
WHERE p.nombre IN ('nueva_compra', 'nueva_venta');

DELETE FROM permiso WHERE nombre IN ('nueva_compra', 'nueva_venta');

-- FASE 8 (release 1.2.1, ejecutada aparte de 1.2.0): limpieza destructiva.
-- Requiere que la fase 7 (lectura sobre idrol/rol.dashboard en dashboards,
-- sesión, AuthController y modelos de presentación) ya esté en producción y
-- estable — de lo contrario esto rompe cualquier código que aún lea `cargo`
-- o `permisousuario`. Irreversible sin restaurar el backup pre-migración.

-- GATE MANUAL OBLIGATORIO: repetir la verificación de la fase 2 — cero filas
-- esperadas. Si devuelve filas, resolver a mano antes de continuar.
SELECT idusuario, correo, cargo AS cargo_sin_rol_valido
FROM usuarios
WHERE idrol IS NULL;

ALTER TABLE usuarios MODIFY idrol int NOT NULL;

SET @cargo_exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'cargo'
);
SET @sql_drop_cargo = IF(@cargo_exists > 0,
  'ALTER TABLE usuarios DROP COLUMN cargo',
  'SELECT "Columna cargo ya no existe, se omite"'
);
PREPARE stmt_drop_cargo FROM @sql_drop_cargo;
EXECUTE stmt_drop_cargo;
DEALLOCATE PREPARE stmt_drop_cargo;

DROP TABLE IF EXISTS permisousuario;
