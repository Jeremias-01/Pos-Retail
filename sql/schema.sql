CREATE DATABASE IF NOT EXISTS pos_retail
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE pos_retail;

CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL UNIQUE,
  descripcion VARCHAR(150),
  estado TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  rol_id INT NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  correo VARCHAR(120) NOT NULL UNIQUE,
  usuario VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  intentos_fallidos INT NOT NULL DEFAULT 0,
  bloqueado TINYINT(1) NOT NULL DEFAULT 0,
  estado TINYINT(1) NOT NULL DEFAULT 1,
  fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_usuarios_roles
    FOREIGN KEY (rol_id) REFERENCES roles(id)
);

CREATE TABLE productos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(30) NOT NULL UNIQUE,
  nombre VARCHAR(120) NOT NULL,
  descripcion VARCHAR(255),
  precio_compra DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  precio_venta DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  stock_actual INT NOT NULL DEFAULT 0,
  stock_minimo INT NOT NULL DEFAULT 0,
  estado TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE proveedores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  nit VARCHAR(30),
  telefono VARCHAR(30),
  correo VARCHAR(120),
  direccion VARCHAR(255),
  estado TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  nit VARCHAR(30),
  telefono VARCHAR(30),
  correo VARCHAR(120),
  direccion VARCHAR(255),
  estado TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE compras (
  id INT AUTO_INCREMENT PRIMARY KEY,
  proveedor_id INT NOT NULL,
  usuario_id INT NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  tipo_compra ENUM('contado', 'credito') NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  estado VARCHAR(20) NOT NULL DEFAULT 'registrada',
  CONSTRAINT fk_compras_proveedores
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id),
  CONSTRAINT fk_compras_usuarios
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE detalle_compras (
  id INT AUTO_INCREMENT PRIMARY KEY,
  compra_id INT NOT NULL,
  producto_id INT NOT NULL,
  cantidad INT NOT NULL,
  costo_unitario DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_detalle_compras_compras
    FOREIGN KEY (compra_id) REFERENCES compras(id),
  CONSTRAINT fk_detalle_compras_productos
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);

CREATE TABLE ventas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  usuario_id INT NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  tipo_venta ENUM('contado', 'credito') NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  estado VARCHAR(20) NOT NULL DEFAULT 'registrada',
  CONSTRAINT fk_ventas_clientes
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
  CONSTRAINT fk_ventas_usuarios
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE detalle_ventas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  venta_id INT NOT NULL,
  producto_id INT NOT NULL,
  cantidad INT NOT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_detalle_ventas_ventas
    FOREIGN KEY (venta_id) REFERENCES ventas(id),
  CONSTRAINT fk_detalle_ventas_productos
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);

CREATE TABLE pagos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  venta_id INT NOT NULL,
  usuario_id INT NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  monto DECIMAL(10,2) NOT NULL,
  metodo_pago VARCHAR(50) NOT NULL,
  numero_comprobante VARCHAR(50) NOT NULL UNIQUE,
  estado VARCHAR(20) NOT NULL DEFAULT 'registrado',
  CONSTRAINT fk_pagos_ventas
    FOREIGN KEY (venta_id) REFERENCES ventas(id),
  CONSTRAINT fk_pagos_usuarios
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE kardex (
  id INT AUTO_INCREMENT PRIMARY KEY,
  producto_id INT NOT NULL,
  usuario_id INT NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  tipo_movimiento ENUM('entrada', 'salida') NOT NULL,
  origen VARCHAR(30) NOT NULL,
  referencia_id INT NOT NULL,
  cantidad INT NOT NULL,
  stock_anterior INT NOT NULL,
  stock_nuevo INT NOT NULL,
  costo_unitario DECIMAL(10,2),
  observacion VARCHAR(255),
  CONSTRAINT fk_kardex_productos
    FOREIGN KEY (producto_id) REFERENCES productos(id),
  CONSTRAINT fk_kardex_usuarios
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE cuentas_por_pagar (
  id INT AUTO_INCREMENT PRIMARY KEY,
  compra_id INT NOT NULL UNIQUE,
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  monto_total DECIMAL(10,2) NOT NULL,
  saldo_pendiente DECIMAL(10,2) NOT NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'pendiente',
  CONSTRAINT fk_cuentas_por_pagar_compras
    FOREIGN KEY (compra_id) REFERENCES compras(id)
);

CREATE TABLE cuentas_por_cobrar (
  id INT AUTO_INCREMENT PRIMARY KEY,
  venta_id INT NOT NULL UNIQUE,
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  monto_total DECIMAL(10,2) NOT NULL,
  saldo_pendiente DECIMAL(10,2) NOT NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'pendiente',
  CONSTRAINT fk_cuentas_por_cobrar_ventas
    FOREIGN KEY (venta_id) REFERENCES ventas(id)
);

CREATE TABLE devoluciones_proveedor (
  id INT AUTO_INCREMENT PRIMARY KEY,
  proveedor_id INT NOT NULL,
  usuario_id INT NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  motivo VARCHAR(255) NOT NULL,
  total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  estado VARCHAR(20) NOT NULL DEFAULT 'registrada',
  CONSTRAINT fk_devoluciones_proveedor_proveedores
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id),
  CONSTRAINT fk_devoluciones_proveedor_usuarios
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE detalle_devoluciones_proveedor (
  id INT AUTO_INCREMENT PRIMARY KEY,
  devolucion_id INT NOT NULL,
  producto_id INT NOT NULL,
  cantidad INT NOT NULL,
  costo_unitario DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_detalle_devoluciones_devoluciones
    FOREIGN KEY (devolucion_id) REFERENCES devoluciones_proveedor(id),
  CONSTRAINT fk_detalle_devoluciones_productos
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);

INSERT INTO roles (nombre, descripcion) VALUES
('Administrador', 'Acceso general al sistema'),
('Cajero', 'Registro de ventas y pagos'),
('Compras', 'Registro de compras y devoluciones');
