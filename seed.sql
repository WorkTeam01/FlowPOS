-- Datos de demostración para el boilerplate SistemaVentas
-- Ejecutar después de schema.sql:
--   mysql -u root flowpos < seed.sql
--
-- Credenciales de acceso (clave: admin123):
--   admin@demo.com      → administrador
--   supervisor@demo.com → supervisor
--   vendedor@demo.com   → vendedor

SET FOREIGN_KEY_CHECKS = 0;

-- ─── Empresa ────────────────────────────────────────────────────────────────
INSERT INTO empresa (nombre, nit, direccion, telefono, email, imagen) VALUES
('Mi Empresa Demo', '1000000000', 'Av. Principal 100', '70000000', 'contacto@demo.com', 'default.png');

-- ─── Sucursales ─────────────────────────────────────────────────────────────
INSERT INTO sucursal (nombre, idempresa) VALUES
('Central', 1),
('Norte',   1);

-- ─── Categorías ─────────────────────────────────────────────────────────────
INSERT INTO categoria (nombre) VALUES
('Electrónica'),
('Hogar'),
('Ropa'),
('Alimentos'),
('Herramientas');

-- ─── Permisos (schema.sql ya los inserta; IGNORE por si se ejecuta solo) ───
INSERT IGNORE INTO permiso (nombre) VALUES
('perfil'), ('ventas'), ('nueva_venta'), ('clientes'), ('productos'),
('categorias'), ('compras'), ('nueva_compra'), ('empresa'), ('sucursales'),
('usuarios'), ('permisos'), ('sesiones');

-- ─── Usuarios ───────────────────────────────────────────────────────────────
-- Hash generado con: password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO usuarios (nombre, apellidopaterno, apellidomaterno, tipodocumento, numdocumento, direccion, telefono, correo, cargo, clave, idsucursal) VALUES
('Admin',   'Sistema', NULL,     'CI', '00000001', 'Oficina Central', '70000001', 'admin@demo.com',      'Administrador', '$2y$10$vvu6YNNSLFNljDT.JMn.ee2x.RErV0P7OnnoKtAeif8MF1H6abU8W', 1),
('Carlos',  'Pérez',   'Ruiz',   'CI', '10000001', 'Zona Sur',        '70000002', 'supervisor@demo.com', 'Supervisor',    '$2y$10$vvu6YNNSLFNljDT.JMn.ee2x.RErV0P7OnnoKtAeif8MF1H6abU8W', 1),
('María',   'López',   'Vargas', 'CI', '10000002', 'Zona Norte',      '70000003', 'vendedor@demo.com',   'Vendedor',      '$2y$10$vvu6YNNSLFNljDT.JMn.ee2x.RErV0P7OnnoKtAeif8MF1H6abU8W', 2);

-- ─── Permisos del supervisor ─────────────────────────────────────────────────
INSERT INTO permisousuario (idpermiso, idusuario)
SELECT idpermiso, 2 FROM permiso
WHERE nombre IN ('perfil','ventas','nueva_venta','clientes','productos','categorias','compras','nueva_compra','sesiones');

-- ─── Permisos del vendedor ───────────────────────────────────────────────────
INSERT INTO permisousuario (idpermiso, idusuario)
SELECT idpermiso, 3 FROM permiso
WHERE nombre IN ('perfil','nueva_venta','clientes');

-- ─── Clientes ────────────────────────────────────────────────────────────────
INSERT INTO cliente (nombres, apellidopaterno, apellidomaterno, tipodocumento, numdocumento, genero, direccion, celular, email) VALUES
('Juan',     'García',   'Mendoza', 'CI', '20000001', 'Masculino', 'Zona Central', '71000001', 'juan.garcia@email.com'),
('Ana',      'Torres',   'Lima',    'CI', '20000002', 'Femenino',  'Zona Sur',     '71000002', 'ana.torres@email.com'),
('Pedro',    'Mamani',   NULL,      'CI', '20000003', 'Masculino', NULL,            '71000003', NULL),
('Sofía',    'Quispe',   'Arce',   'CI', '20000004', 'Femenino',  'Zona Norte',   '71000004', 'sofia.quispe@email.com'),
('Roberto',  'Flores',   'Condori', 'CI', '20000005', 'Masculino', 'Zona Este',    '71000005', NULL),
('Lucía',    'Chávez',   'Ramos',   'CI', '20000006', 'Femenino',  'Centro',       '71000006', 'lucia.chavez@email.com'),
('Miguel',   'Soria',    NULL,      'CI', '20000007', 'Masculino', 'Zona Oeste',   '71000007', NULL),
('Valentina','Apaza',    'Cruz',    'CI', '20000008', 'Femenino',  'Zona Sur',     '71000008', 'valentina.apaza@email.com');

-- ─── Productos ───────────────────────────────────────────────────────────────
INSERT INTO producto (codigo, nombre, descripcion, stock, stockminimo, stockmaximo, preciocompra, precioventa, idcategoria) VALUES
('ELEC-001', 'Audífonos Bluetooth',    'Inalámbricos, batería 20h',      30, 5,  80, 120.00, 220.00, 1),
('ELEC-002', 'Cable USB-C 1m',         'Carga rápida 3A',                50, 10, 100, 15.00,  35.00, 1),
('ELEC-003', 'Cargador de Pared 20W',  'Doble puerto USB',               25, 5,  60, 40.00,  80.00, 1),
('ELEC-004', 'Mouse Inalámbrico',      '1600 DPI, receptor nano',        20, 5,  50, 60.00, 110.00, 1),
('HOG-001',  'Sartén Antiadherente',   '24cm, apto inducción',           15, 3,  30, 80.00, 150.00, 2),
('HOG-002',  'Juego de Toallas x3',   '100% algodón, varios colores',   20, 5,  40, 55.00, 100.00, 2),
('HOG-003',  'Lámpara de Escritorio', 'LED, 3 niveles de brillo',       12, 3,  25, 90.00, 170.00, 2),
('ROP-001',  'Camiseta Básica',        'Algodón 100%, tallas S-XL',     40, 8,  80, 30.00,  65.00, 3),
('ROP-002',  'Pantalón Casual',        'Slim fit, varios colores',       25, 5,  50, 75.00, 140.00, 3),
('ALI-001',  'Café Molido 250g',       'Tostado medio, origen único',   35, 10, 70, 25.00,  50.00, 4),
('ALI-002',  'Aceite de Oliva 500ml', 'Extra virgen, primera presión',  20, 5,  40, 35.00,  70.00, 4),
('HER-001',  'Destornillador Set x6', 'Punta plana y estrella',         18, 5,  35, 45.00,  85.00, 5),
('HER-002',  'Cinta Métrica 5m',      'Autoretráctil, gancho magnético', 30, 8, 60, 20.00,  40.00, 5);

-- ─── Compras (reposición de stock) ───────────────────────────────────────────
INSERT INTO compra (idusuario, fechacompra, observaciones, totalcompra) VALUES
(1, DATE_SUB(NOW(), INTERVAL 25 DAY), 'Reposición inicial electrónica', 4800.00),
(2, DATE_SUB(NOW(), INTERVAL 18 DAY), 'Reposición hogar y ropa',        3050.00),
(1, DATE_SUB(NOW(), INTERVAL 10 DAY), 'Reposición alimentos y herramientas', 1800.00);

INSERT INTO detallecompra (idcompra, idproducto, cantidad, preciocompra) VALUES
-- Compra 1
(1, 1, 20, 120.00),  -- Audífonos
(1, 2, 40, 15.00),   -- Cable USB-C
(1, 3, 20, 40.00),   -- Cargador
(1, 4, 15, 60.00),   -- Mouse
-- Compra 2
(2, 5, 12, 80.00),   -- Sartén
(2, 6, 15, 55.00),   -- Toallas
(2, 7, 10, 90.00),   -- Lámpara
(2, 8, 30, 30.00),   -- Camiseta
(2, 9, 20, 75.00),   -- Pantalón
-- Compra 3
(3, 10, 30, 25.00),  -- Café
(3, 11, 15, 35.00),  -- Aceite
(3, 12, 15, 45.00),  -- Destornilladores
(3, 13, 25, 20.00);  -- Cinta métrica

-- ─── Ventas ──────────────────────────────────────────────────────────────────
INSERT INTO venta (idcliente, idusuario, totalventa, fechacreacion, observacion) VALUES
(1, 3, 255.00, DATE_SUB(NOW(), INTERVAL 20 DAY), NULL),
(2, 3, 385.00, DATE_SUB(NOW(), INTERVAL 18 DAY), NULL),
(3, 2, 150.00, DATE_SUB(NOW(), INTERVAL 16 DAY), NULL),
(4, 3, 430.00, DATE_SUB(NOW(), INTERVAL 14 DAY), NULL),
(5, 2, 220.00, DATE_SUB(NOW(), INTERVAL 12 DAY), NULL),
(1, 3, 140.00, DATE_SUB(NOW(), INTERVAL 10 DAY), NULL),
(6, 3, 310.00, DATE_SUB(NOW(), INTERVAL 8 DAY),  NULL),
(2, 2, 195.00, DATE_SUB(NOW(), INTERVAL 7 DAY),  NULL),
(7, 3, 510.00, DATE_SUB(NOW(), INTERVAL 5 DAY),  NULL),
(8, 3, 270.00, DATE_SUB(NOW(), INTERVAL 3 DAY),  NULL),
(3, 2, 175.00, DATE_SUB(NOW(), INTERVAL 2 DAY),  NULL),
(4, 3, 340.00, DATE_SUB(NOW(), INTERVAL 1 DAY),  NULL),
(5, 3, 220.00, NOW(),                             NULL);

INSERT INTO detalleventa (idventa, idproducto, cantidad, precioventa, descuento) VALUES
-- Venta 1: Audífonos + Cable
(1, 1, 1, 220.00, 0.00),
(1, 2, 1,  35.00, 0.00),
-- Venta 2: Mouse + Lámpara
(2, 4, 1, 110.00, 0.00),
(2, 7, 1, 170.00, 0.00),
(2, 2, 3,  35.00, 0.00),
-- Venta 3: Sartén
(3, 5, 1, 150.00, 0.00),
-- Venta 4: Audífonos + Pantalón + Café
(4, 1, 1, 220.00, 0.00),
(4, 9, 1, 140.00, 0.00),
(4, 10,1,  50.00, 0.00),  -- desc 20
-- Venta 5: Cargador + Camiseta x2
(5, 3, 1,  80.00, 0.00),
(5, 8, 2,  65.00, 0.00),
-- Venta 6: Cable x2 + Aceite x2
(6, 2, 2,  35.00, 0.00),
(6, 11,1,  70.00, 0.00),
-- Venta 7: Mouse + Toallas + Destornilladores
(7, 4, 1, 110.00, 0.00),
(7, 6, 1, 100.00, 0.00),
(7, 12,1,  85.00, 0.00),  -- desc 15
-- Venta 8: Camiseta x3
(8, 8, 3,  65.00, 0.00),
-- Venta 9: Audífonos x2 + Pantalón
(9, 1, 2, 220.00, 0.00),
(9, 9, 1, 140.00, 0.00),  -- desc 30
-- Venta 10: Lámpara + Café x2 + Cinta
(10, 7, 1, 170.00, 0.00),
(10, 10,2,  50.00, 0.00),
-- Venta 11: Toallas + Aceite
(11, 6, 1, 100.00, 0.00),
(11, 11,1,  70.00, 0.00),  -- desc 5
-- Venta 12: Audífonos + Cargador + Cable x2
(12, 1, 1, 220.00, 0.00),
(12, 3, 1,  80.00, 0.00),
(12, 2, 1,  35.00, 0.00),  -- desc 5
-- Venta 13: Camiseta x2 + Pantalón
(13, 8, 2,  65.00, 0.00),
(13, 9, 1, 140.00, 0.00);  -- desc 50

-- ─── Pagos ───────────────────────────────────────────────────────────────────
INSERT INTO pagoventa (idventa, metodopago, monto, pagorecibido, cambio) VALUES
(1,  'efectivo',      255.00, 300.00, 45.00),
(2,  'tarjeta',       385.00, 385.00,  0.00),
(3,  'qr',            150.00, 150.00,  0.00),
(4,  'efectivo',      430.00, 500.00, 70.00),
(5,  'transferencia', 220.00, 220.00,  0.00),
(6,  'efectivo',      140.00, 150.00, 10.00),
(7,  'tarjeta',       310.00, 310.00,  0.00),
(8,  'efectivo',      195.00, 200.00,  5.00),
(9,  'tarjeta',       510.00, 510.00,  0.00),
(10, 'qr',            270.00, 270.00,  0.00),
(11, 'efectivo',      175.00, 200.00, 25.00),
(12, 'transferencia', 340.00, 340.00,  0.00),
(13, 'efectivo',      220.00, 220.00,  0.00);

SET FOREIGN_KEY_CHECKS = 1;
