-- 1) Base de datos
CREATE DATABASE esteroides;

-- 2) Tabla clientes

select * from clientes
CREATE TABLE clientes (
    id_cliente   SERIAL PRIMARY KEY,
    nombres      VARCHAR(80)  NOT NULL,
    apellidos    VARCHAR(80)  NOT NULL,
    telefono     INTEGER,
    correo       VARCHAR(100),
    direccion    VARCHAR(200),
    situacion    SMALLINT     DEFAULT 1
);

-- 3) Tabla categorías
select *from categorias

CREATE TABLE categorias (
    id_categoria SERIAL PRIMARY KEY,
    nombre       VARCHAR(30)  NOT NULL,
    situacion    SMALLINT     DEFAULT 1
);

-- 4) Tabla prioridades
select* from prioridades

CREATE TABLE prioridades (
    id_prioridad SERIAL PRIMARY KEY,
    nombre       VARCHAR(100) NOT NULL,
    situacion    SMALLINT     DEFAULT 1
);

-- 5) Tabla usuarios
CREATE TABLE usuarios (
    id_usuario   SERIAL PRIMARY KEY,
    usuario      VARCHAR(50)  NOT NULL UNIQUE,
    contrasena   VARCHAR(100) NOT NULL,
    nombre       VARCHAR(100) NOT NULL,
    rol          VARCHAR(20)  DEFAULT 'vendedor',
    situacion    SMALLINT     DEFAULT 1
);

-- 6) Tabla productos
--    * Sin id_cliente: la relación cliente–producto se resuelve en ventas
CREATE TABLE productos (
    id_producto   SERIAL PRIMARY KEY,
    nombre        VARCHAR(100) NOT NULL,
    cantidad      INTEGER      NOT NULL,
    id_categoria  INTEGER      NOT NULL,
    id_prioridad  INTEGER      NOT NULL,
    comprado      SMALLINT     DEFAULT 0,
    situacion     SMALLINT     DEFAULT 1
);

ALTER TABLE productos ADD CONSTRAINT FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria);
ALTER TABLE productos ADD CONSTRAINT FOREIGN KEY (id_prioridad) REFERENCES prioridades(id_prioridad);

-- 7) Tabla ventas
CREATE TABLE ventas (
    id_venta     SERIAL PRIMARY KEY,
    fecha        DATETIME YEAR TO SECOND DEFAULT CURRENT YEAR TO SECOND,
    id_cliente   INTEGER    NOT NULL,
    total        DECIMAL(10,2),
    id_usuario   INTEGER,
    situacion    SMALLINT    DEFAULT 1
);

ALTER TABLE ventas ADD CONSTRAINT FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente);

ALTER TABLE ventas ADD CONSTRAINT FOREIGN KEY (id_usuario)  REFERENCES usuarios(id_usuario);

-- 8) Tabla detalle_ventas (ya estaba terminada)
CREATE TABLE detalle_ventas (
    id_detalle      SERIAL PRIMARY KEY,
    id_venta        INTEGER       NOT NULL,
    id_producto     INTEGER       NOT NULL,
    cantidad        INTEGER       NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal        DECIMAL(10,2),
    fecha_detalle DATETIME YEAR TO SECOND,
    UNIQUE (id_venta, id_producto)
);

ALTER TABLE detalle_ventas
    ADD CONSTRAINT FOREIGN KEY (id_venta)    REFERENCES ventas(id_venta);

ALTER TABLE detalle_ventas
    ADD CONSTRAINT  FOREIGN KEY (id_producto) REFERENCES productos(id_producto);

