-- Migración: revocación real de sesiones — release 1.2.7
-- Idempotente: puede correrse N veces sin error ni duplicar datos.
--
-- `token_hash` guarda el SHA-256 del token de revocación; el token en claro
-- vive solo en la sesión PHP del navegador y jamás se persiste en BD.
-- `motivo_cierre` registra por qué se cerró la fila: logout, timeout,
-- security, admin_row, admin_user o migration.
--
-- El corte cierra todas las sesiones abiertas al momento de aplicarse: los
-- usuarios con sesión activa deberán volver a iniciar login.

ALTER TABLE sesionusuario
  ADD COLUMN IF NOT EXISTS token_hash char(64) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS motivo_cierre varchar(20) DEFAULT NULL;

CREATE UNIQUE INDEX IF NOT EXISTS uq_sesionusuario_token ON sesionusuario (token_hash);
CREATE INDEX IF NOT EXISTS idx_sesionusuario_usuario_estado ON sesionusuario (idusuario, estado);

UPDATE sesionusuario
   SET estado = 0,
       horasalida = IFNULL(horasalida, NOW()),
       motivo_cierre = 'migration'
 WHERE estado = 1;
