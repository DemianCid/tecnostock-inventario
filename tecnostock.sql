CREATE DATABASE IF NOT EXISTS tecnostock
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE tecnostock;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    clave VARCHAR(255) NOT NULL,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categorias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS productos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    categoria_id INT UNSIGNED NOT NULL,
    precio DECIMAL(10, 2) NOT NULL,
    stock_actual INT UNSIGNED NOT NULL DEFAULT 0,
    stock_minimo INT UNSIGNED NOT NULL DEFAULT 5,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    CONSTRAINT chk_productos_precio CHECK (precio >= 0),
    CONSTRAINT fk_productos_categoria
        FOREIGN KEY (categoria_id) REFERENCES categorias(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS movimientos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    producto_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    tipo ENUM('entrada', 'salida') NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observacion VARCHAR(255),
    precio_unitario DECIMAL(10, 2) DEFAULT NULL,
    CONSTRAINT chk_movimientos_cantidad CHECK (cantidad > 0),
    CONSTRAINT fk_movimientos_producto
        FOREIGN KEY (producto_id) REFERENCES productos(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_movimientos_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT INTO usuarios (nombre, correo, clave, estado)
VALUES (
    'Administrador',
    'admin@tecnostock.cl',
    '$2y$10$Rq6C0rsLHZhAjM7Qsnx5meNN/slh2G7hyPBAz9e1rPu5o/o85ijE6',
    'activo'
)
ON DUPLICATE KEY UPDATE correo = VALUES(correo);

INSERT INTO categorias (nombre, descripcion, estado)
VALUES
    ('Periféricos', 'Teclados, mouses y audífonos', 'activo'),
    ('Almacenamiento', 'Discos duros y unidades SSD', 'activo')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

INSERT INTO productos
    (codigo, nombre, descripcion, categoria_id, precio, stock_actual, stock_minimo, estado)
VALUES
    (
        'PER-001',
        'Mouse inalámbrico',
        'Mouse USB para oficina',
        (SELECT id FROM categorias WHERE nombre = 'Periféricos'),
        12990.00,
        2,
        5,
        'activo'
    ),
    (
        'ALM-001',
        'SSD 1 TB',
        'Unidad de estado sólido SATA',
        (SELECT id FROM categorias WHERE nombre = 'Almacenamiento'),
        59990.00,
        20,
        5,
        'activo'
    )
ON DUPLICATE KEY UPDATE codigo = VALUES(codigo);
