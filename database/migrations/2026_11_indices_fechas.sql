-- Migración: índices por fecha para los filtros de rango de dashboards y listados.
-- Idempotente: puede correrse N veces sin error.
--
-- Las consultas filtran por `estado` y un rango de fecha (`col >= ini AND col < fin + 1 día`),
-- así que el índice lleva `estado` primero y la fecha después.

CREATE INDEX IF NOT EXISTS idx_venta_estado_fecha ON venta (estado, fechacreacion);
CREATE INDEX IF NOT EXISTS idx_compra_estado_fecha ON compra (estado, fechacompra);
CREATE INDEX IF NOT EXISTS idx_sesionusuario_horaingreso ON sesionusuario (horaingreso);
