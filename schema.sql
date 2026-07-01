-- Primero creamos las tablas que no tienen dependencias
CREATE TABLE empresa (
  idempresa int PRIMARY KEY AUTO_INCREMENT,
  nombre varchar(255) NOT NULL,
  nit varchar(20) NOT NULL,
  direccion varchar(255) NOT NULL,
  telefono varchar(20) NOT NULL,
  email varchar(255) NOT NULL CHECK (email LIKE '%@%.%'),
  imagen varchar(255) NOT NULL,
  fechacreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  fechaactualizacion DATETIME ON UPDATE CURRENT_TIMESTAMP,
  estado tinyint(1) DEFAULT 1
);

CREATE TABLE sucursal (
  idsucursal int PRIMARY KEY AUTO_INCREMENT,
  nombre varchar(255) NOT NULL,
  idempresa int DEFAULT NULL,
  fechacreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  fechaactualizacion DATETIME ON UPDATE CURRENT_TIMESTAMP,
  estado tinyint(1) DEFAULT 1,
  FOREIGN KEY (idempresa) REFERENCES empresa(idempresa)
);

CREATE TABLE categoria (
  idcategoria int PRIMARY KEY AUTO_INCREMENT,
  nombre varchar(255) NOT NULL,
  fechacreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  fechaactualizacion DATETIME ON UPDATE CURRENT_TIMESTAMP,
  estado tinyint(1) DEFAULT 1
);

CREATE TABLE permiso (
  idpermiso int PRIMARY KEY AUTO_INCREMENT,
  nombre varchar(255) NOT NULL,
  fechacreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  fechaactualizacion DATETIME ON UPDATE CURRENT_TIMESTAMP,
  estado tinyint(1) DEFAULT 1
);

CREATE TABLE cliente (
  idcliente int PRIMARY KEY AUTO_INCREMENT,
  nombres varchar(255) NOT NULL,
  apellidopaterno varchar(255) NOT NULL,
  apellidomaterno varchar(255),
  tipodocumento varchar(20) NOT NULL,
  numdocumento varchar(20) NOT NULL,
  genero varchar(20) NOT NULL,
  direccion varchar(255) DEFAULT NULL,
  celular varchar(15) DEFAULT NULL,
  email varchar(255) DEFAULT NULL CHECK (email IS NULL OR email LIKE '%@%.%'),
  fechacreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  fechaactualizacion DATETIME ON UPDATE CURRENT_TIMESTAMP,
  estado tinyint(1) DEFAULT 1,
  UNIQUE KEY (tipodocumento, numdocumento)
);

CREATE TABLE producto (
  idproducto int PRIMARY KEY AUTO_INCREMENT,
  codigo varchar(255) NOT NULL,
  imagen varchar(255),
  nombre varchar(255) NOT NULL,
  descripcion varchar(255),
  stock int NOT NULL DEFAULT 0,
  stockminimo int NOT NULL,
  stockmaximo int NOT NULL,
  preciocompra decimal(10,2) NOT NULL,
  precioventa decimal(10,2) NOT NULL,
  idcategoria int DEFAULT NULL,
  fechacreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  fechaactualizacion DATETIME ON UPDATE CURRENT_TIMESTAMP,
  estado tinyint(1) DEFAULT 1,
  FOREIGN KEY (idcategoria) REFERENCES categoria(idcategoria),
  UNIQUE KEY (codigo),
  CHECK (stock >= 0),
  CHECK (stockminimo >= 0),
  CHECK (stockmaximo >= stockminimo),
  CHECK (precioventa >= preciocompra)
);

CREATE TABLE usuarios (
  idusuario int PRIMARY KEY AUTO_INCREMENT,
  nombre varchar(255) NOT NULL,
  apellidopaterno varchar(255) NOT NULL,
  apellidomaterno varchar(255) DEFAULT NULL,
  tipodocumento varchar(20) NOT NULL,
  numdocumento varchar(20) NOT NULL,
  direccion varchar(255) DEFAULT NULL,
  telefono varchar(15) DEFAULT NULL,
  correo varchar(255) DEFAULT NULL,
  cargo varchar(255) DEFAULT NULL,
  clave varchar(255) NOT NULL,
  imagen varchar(255),
  fechacreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  fechaactualizacion DATETIME ON UPDATE CURRENT_TIMESTAMP,
  estado tinyint(1) DEFAULT 1,
  idsucursal int,
  FOREIGN KEY (idsucursal) REFERENCES sucursal(idsucursal),
  UNIQUE KEY (correo),
  UNIQUE KEY (tipodocumento, numdocumento)
);

-- Ahora creamos las tablas que dependen de las anteriores
CREATE TABLE permisousuario (
  idrol int PRIMARY KEY AUTO_INCREMENT,
  idpermiso int DEFAULT NULL,
  idusuario int DEFAULT NULL,
  fechacreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  fechaactualizacion DATETIME ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (idpermiso) REFERENCES permiso(idpermiso),
  FOREIGN KEY (idusuario) REFERENCES usuarios(idusuario)
);

CREATE TABLE compra (
  idcompra int PRIMARY KEY AUTO_INCREMENT,
  idusuario int NOT NULL,
  fechacompra datetime NOT NULL,
  observaciones text DEFAULT NULL,
  totalcompra decimal(11,2) DEFAULT NULL,
  fechacreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  fechaactualizacion DATETIME ON UPDATE CURRENT_TIMESTAMP,
  estado tinyint(1) DEFAULT 1,
  FOREIGN KEY (idusuario) REFERENCES usuarios(idusuario)
);

CREATE TABLE detallecompra (
  iddetallecompra int PRIMARY KEY AUTO_INCREMENT,
  idcompra int DEFAULT NULL,
  idproducto int DEFAULT NULL,
  cantidad int NOT NULL,
  preciocompra decimal(10,2) NOT NULL,
  fechacreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  fechaactualizacion DATETIME ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (idcompra) REFERENCES compra(idcompra),
  FOREIGN KEY (idproducto) REFERENCES producto(idproducto),
  CHECK (cantidad > 0)
);

CREATE TABLE venta (
  idventa int PRIMARY KEY AUTO_INCREMENT,
  idcliente int DEFAULT NULL,
  idusuario int DEFAULT NULL,
  totalventa decimal(11,2) DEFAULT NULL,
  fechacreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  fechaactualizacion DATETIME ON UPDATE CURRENT_TIMESTAMP,
  estado tinyint(1) DEFAULT 1,
  observacion varchar(255) DEFAULT NULL,
  FOREIGN KEY (idcliente) REFERENCES cliente(idcliente),
  FOREIGN KEY (idusuario) REFERENCES usuarios(idusuario)
);

CREATE TABLE detalleventa (
  iddetalleventa int PRIMARY KEY AUTO_INCREMENT,
  idventa int DEFAULT NULL,
  idproducto int DEFAULT NULL,
  cantidad int NOT NULL,
  precioventa decimal(10,2) NOT NULL,
  descuento decimal(10,2) NOT NULL DEFAULT 0.00,
  fechacreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  fechaactualizacion DATETIME ON UPDATE CURRENT_TIMESTAMP,
  estado tinyint(1) DEFAULT 1,
  FOREIGN KEY (idventa) REFERENCES venta(idventa),
  FOREIGN KEY (idproducto) REFERENCES producto(idproducto),
  CHECK (cantidad > 0),
  CHECK (descuento >= 0)
);

CREATE TABLE pagoventa (
  idpagoventa int PRIMARY KEY AUTO_INCREMENT,
  idventa int NOT NULL,
  metodopago VARCHAR(20) NOT NULL CHECK (metodopago IN ('efectivo', 'tarjeta', 'qr', 'transferencia')),
  monto DECIMAL(11,2) NOT NULL,
  pagorecibido DECIMAL(11,2) DEFAULT 0.00,
  cambio DECIMAL(11,2) DEFAULT 0.00,
  fechacreacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  fechaactualizacion DATETIME ON UPDATE CURRENT_TIMESTAMP,
  estado tinyint(1) DEFAULT 1,
  FOREIGN KEY (idventa) REFERENCES venta(idventa),
  CHECK (monto >= 0),
  CHECK (pagorecibido >= 0),
  CHECK (cambio >= 0)
);

CREATE TABLE sesionusuario (
  idsesion int PRIMARY KEY AUTO_INCREMENT,
  idusuario int NOT NULL,
  horaingreso DATETIME DEFAULT CURRENT_TIMESTAMP,
  horasalida DATETIME DEFAULT NULL,
  ipusuario VARCHAR(50) DEFAULT NULL,
  navegador VARCHAR(255) DEFAULT NULL,
  estado tinyint(1) DEFAULT 1, -- 1=Conectado, 0=Desconectado
  FOREIGN KEY (idusuario) REFERENCES usuarios(idusuario)
);