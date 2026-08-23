CREATE DATABASE IF NOT EXISTS tecnostock;
USE tecnostock;

-- 1. Tabla Usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) UNIQUE NOT NULL,
    clave VARCHAR(255) NOT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo'
);

-- Insertar usuario inicial de prueba (clave: 123456)
INSERT INTO usuarios (nombre, correo, clave) VALUES 
('Encargado Inventario', 'admin@tecnostock.cl', '$2y$10$w3J2eYkI9Y6k13Jb/1qJjeBvB4Y/YhKq7jLq1r3Jq.x3X0k1O8C3G');

-- 2. Tabla Categorías
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo'
);

-- Categorías base
INSERT INTO categorias (nombre, descripcion) VALUES 
('Periféricos', 'Teclados, mouses y audífonos'),
('Almacenamiento', 'Discos duros y SSDs');

-- 3. Tabla Productos
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio INT NOT NULL CHECK (precio >= 0),
    stock_actual INT NOT NULL DEFAULT 0 CHECK (stock_actual >= 0),
    stock_minimo INT NOT NULL DEFAULT 5 CHECK (stock_minimo >= 0),
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    categoria_id INT,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
);

-- 4. Tabla Movimientos
CREATE TABLE IF NOT EXISTS movimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    tipo ENUM('entrada', 'salida') NOT NULL,
    cantidad INT NOT NULL CHECK (cantidad > 0),
    observacion VARCHAR(255),
    producto_id INT NOT NULL,
    usuario_id INT NOT NULL,
    FOREIGN KEY (producto_id) REFERENCES productos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);