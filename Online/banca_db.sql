-- ============================================================================
-- SCRIPT DE BASE DE DATOS - BANCA EN LÍNEA UMG
-- Optimizado para InfinityFree (Sin Stored Procedures ni Triggers)
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS server_logs;
DROP TABLE IF EXISTS transferencias_terceros;
DROP TABLE IF EXISTS movimientos;
DROP TABLE IF EXISTS cuentas;
DROP TABLE IF EXISTS usuarios;
SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- 1. Tabla: usuarios
-- ----------------------------------------------------------------------------
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'cajero', 'usuario') NOT NULL DEFAULT 'usuario',
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 2. Tabla: cuentas
-- ----------------------------------------------------------------------------
CREATE TABLE cuentas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_cuenta VARCHAR(20) NOT NULL UNIQUE,
    usuario_id INT NOT NULL,
    saldo DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    tipo_cuenta ENUM('AHORRO', 'MONETARIA') NOT NULL DEFAULT 'MONETARIA',
    estado ENUM('ACTIVA', 'INACTIVA', 'BLOQUEADA') NOT NULL DEFAULT 'ACTIVA',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 3. Tabla: movimientos (Depósitos, Retiros y Transferencias)
-- ----------------------------------------------------------------------------
CREATE TABLE movimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cuenta_id INT NOT NULL,
    tipo_mov ENUM('CREDITO', 'DEBITO') NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    concepto VARCHAR(255) NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cuenta_id) REFERENCES cuentas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 4. Tabla: transferencias_terceros (Cuentas asociadas por clientes)
-- ----------------------------------------------------------------------------
CREATE TABLE transferencias_terceros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    cuenta_destino VARCHAR(20) NOT NULL,
    alias VARCHAR(50) NOT NULL,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 5. Tabla: server_logs (Requisito de logs de Apache en tiempo real)
-- ----------------------------------------------------------------------------
CREATE TABLE server_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_origen VARCHAR(45) NOT NULL,
    pagina_solicitada VARCHAR(255) NOT NULL,
    metodo VARCHAR(10) NOT NULL,
    codigo_respuesta INT NOT NULL DEFAULT 200,
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- DATOS INICIALES DE PRUEBA / ADMINISTRADOR POR DEFECTO
-- ============================================================================

-- Usuario Administrador (Contraseña: admin123 -> Hash de prueba o texto según tu login)
INSERT INTO usuarios (nombre, email, password, rol) VALUES 
('Administrador UMG', 'admin@bancaweb.com', '$2y$10$e.g.hash.de.ejemplo.admin123', 'admin'),
('Cajero 1', 'cajero1@bancaweb.com', '$2y$10$e.g.hash.de.ejemplo.cajero123', 'cajero'),
('Cliente Ejemplo', 'cliente@bancaweb.com', '$2y$10$e.g.hash.de.ejemplo.cliente123', 'usuario');

-- Cuentas de ejemplo
INSERT INTO cuentas (numero_cuenta, usuario_id, saldo, tipo_cuenta) VALUES 
('3001234567', 3, 1500.00, 'MONETARIA'),
('3009876543', 3, 500.00, 'AHORRO');

-- Movimientos iniciales para generar gráficos de prueba en admin_monitor.php
INSERT INTO movimientos (cuenta_id, tipo_mov, monto, concepto) VALUES 
(1, 'CREDITO', 2000.00, 'Apertura de cuenta'),
(1, 'DEBITO', 500.00, 'Retiro en cajero');
