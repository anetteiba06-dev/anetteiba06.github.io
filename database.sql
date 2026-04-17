-- ============================================================
--  VINILOS STORE — Base de datos completa
--  Importar en phpMyAdmin o ejecutar en MySQL/XAMPP
-- ============================================================

CREATE DATABASE IF NOT EXISTS vinilos_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vinilos_store;

-- ─── TABLA: usuarios ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS usuarios (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,          -- bcrypt hash
    rol         ENUM('admin','cliente') DEFAULT 'cliente',
    activo      TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── TABLA: artistas ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS artistas (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    color       VARCHAR(7)   NOT NULL,   -- color hex de la marca
    descripcion TEXT,
    imagen_url  VARCHAR(255)
) ENGINE=InnoDB;

-- ─── TABLA: productos ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS productos (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    artista_id  INT NOT NULL,
    titulo      VARCHAR(200) NOT NULL,
    album       VARCHAR(200) NOT NULL,
    anio        YEAR,
    precio      DECIMAL(8,2) NOT NULL,
    stock       INT          DEFAULT 0,
    imagen_url  VARCHAR(255),
    descripcion TEXT,
    edicion     VARCHAR(100),             -- ej. "Edición Limitada", "Estándar"
    color_vinilo VARCHAR(50),             -- ej. "Negro", "Transparente", "Splatter"
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (artista_id) REFERENCES artistas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─── TABLA: pedidos ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS pedidos (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT NOT NULL,
    total       DECIMAL(10,2) NOT NULL,
    estado      ENUM('pendiente','pagado','enviado','cancelado') DEFAULT 'pendiente',
    direccion   VARCHAR(300),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- ─── TABLA: detalle_pedidos ────────────────────────────────
CREATE TABLE IF NOT EXISTS detalle_pedidos (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id   INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad    INT NOT NULL DEFAULT 1,
    precio_unit DECIMAL(8,2) NOT NULL,
    FOREIGN KEY (pedido_id)   REFERENCES pedidos(id)  ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB;

-- ─── TABLA: carrito ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS carrito (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad    INT NOT NULL DEFAULT 1,
    added_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_product (usuario_id, producto_id),
    FOREIGN KEY (usuario_id)  REFERENCES usuarios(id)  ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
--  DATOS INICIALES
-- ============================================================

-- Artistas
INSERT INTO artistas (nombre, color, descripcion, imagen_url) VALUES
('Gorillaz',  '#CDFF00', 'Banda virtual británica creada por Damon Albarn y Jamie Hewlett. Pioneros del género art rock y trip hop.', 'gorillaz.jpg'),
('Joji',      '#FF6B35', 'Artista japonés-australiano conocido por su música indie/R&B melancólica y sus letras introspectivas.', 'joji.jpg'),
('TWICE',     '#FF2D78', 'Girl group surcoreano de JYP Entertainment. Icono del K-Pop desde 2015 con un sonido pop brillante y energético.', 'twice.jpg');

-- Productos — Gorillaz
INSERT INTO productos (artista_id, titulo, album, anio, precio, stock, descripcion, edicion, color_vinilo) VALUES
(1, 'Gorillaz',           'Gorillaz',           2001, 45.99, 15, 'Álbum debut homónimo. Incluye éxitos como Clint Eastwood y Feel Good Inc.', 'Reedición 2022', 'Negro'),
(1, 'Demon Days',         'Demon Days',         2005, 52.99, 10, 'Obra maestra del trip hop. DARE, Feel Good Inc. y Dirty Harry en vinilo doble.', 'Edición Deluxe 2LP', 'Negro'),
(1, 'Plastic Beach',      'Plastic Beach',      2010, 49.99,  8, 'Concepto concept album ambiental. On Melancholy Hill y Stylo en formato vinilo.', 'Estándar', 'Azul Océano'),
(1, 'Humanz',             'Humanz',             2017, 55.99,  6, 'Vinilo doble con colaboraciones de Popcaan, De La Soul y Vince Staples.', 'Edición Limitada 2LP', 'Splatter Verde'),
(1, 'Song Machine Season One', 'Song Machine', 2020, 48.99, 12, 'Formato episódico. The Valley of The Pagans y Simplicity son destacados.', 'Edición Especial', 'Transparente');

-- Productos — Joji
INSERT INTO productos (artista_id, titulo, album, anio, precio, stock, descripcion, edicion, color_vinilo) VALUES
(2, 'In Tongues',         'In Tongues',         2017, 39.99, 20, 'EP debut oficial. Slow Dancing in the Dark en su versión más íntima.', 'Estándar', 'Negro'),
(2, 'Ballads 1',          'Ballads 1',          2018, 44.99, 14, 'Álbum debut. Slow Dancing in the Dark, Yeah Right y Will He.', 'Edición Limitada', 'Rojo Sangre'),
(2, 'Nectar',             'Nectar',             2020, 49.99,  9, 'Álbum doble producido con Clams Casino. Sanctuary y Gimme Love destacan.', 'Deluxe 2LP', 'Dorado'),
(2, 'Smithereens',        'Smithereens',        2022, 42.99, 11, 'Exploración de sonidos orquestales y melancolía profunda. Album íntimo.', 'Estándar', 'Negro'),
(2, 'Glimpse of Us',      'Single',             2022, 24.99, 18, 'Single éxito global. Vinilo 7" con cara B exclusiva.', '7" Single Edición Limitada', 'Crema');

-- Productos — TWICE
INSERT INTO productos (artista_id, titulo, album, anio, precio, stock, descripcion, edicion, color_vinilo) VALUES
(3, 'The Story Begins',   'The Story Begins',   2015, 38.99, 16, 'Mini álbum debut. OOH-AHH como el mv debut de sus 9 integrantes.', 'Reedición Vinilo', 'Rosa'),
(3, 'Page Two',           'Page Two',           2016, 38.99, 13, 'Contiene Cheer Up, uno de los éxitos más grandes de K-Pop de 2016.', 'Estándar', 'Rosa Claro'),
(3, 'Fancy You',          'Fancy You',          2019, 44.99, 10, 'Fancy y Stuck in My Head. El inicio de su era más madura y sofisticada.', 'Edición Especial', 'Morado Pastel'),
(3, 'Formula of Love',    'Formula of Love',    2021, 52.99,  7, 'Álbum de concepto científico. Doughnut y SCIENTIST son los highlights.', 'Edición Limitada 2LP', 'Holográfico'),
(3, 'Ready to Be',        'Ready to Be',        2023, 46.99,  9, 'Set Me Free Pt.2 y MOONLIGHT. La era más oscura y electrónica del grupo.', 'Deluxe Edition', 'Negro Mate');

-- Usuario admin (contraseña: admin123)
INSERT INTO usuarios (username, email, password, rol) VALUES
('admin', 'admin@vinilosstore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Usuario de prueba (contraseña: cliente123)
INSERT INTO usuarios (username, email, password, rol) VALUES
('juan_cliente', 'juan@example.com', '$2y$10$TKh8H1.PpmAalXn7cTfZ4.4IaEBKdAkJhOkVaLkH68.fuJnk8Dm', 'cliente');
