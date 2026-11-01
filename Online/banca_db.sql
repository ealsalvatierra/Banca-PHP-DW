-- ============================================================================
-- BANCO UMG - Script de base de datos para el hosting (InfinityFree)
-- Mismas siete tablas que Local/creacion_y_migracion.sql + server_logs.
-- InfinityFree no permite CREATE PROCEDURE: los procedimientos se implementan
-- en procedimientos.php con los mismos nombres, parámetros y validaciones.
-- Ejecutar en phpMyAdmin con la base del hosting seleccionada.
-- ATENCIÓN: elimina y vuelve a crear las tablas (deja la BD en blanco).
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS server_logs;
DROP TABLE IF EXISTS transferencias;
DROP TABLE IF EXISTS movimientos;
DROP TABLE IF EXISTS cuentas_terceros;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS cuentas;
DROP TABLE IF EXISTS cajeros;
DROP TABLE IF EXISTS administradores;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE administradores (
    usuario VARCHAR(50) NOT NULL PRIMARY KEY,
    clave VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(60) NOT NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO administradores (usuario, clave, nombre_completo) VALUES
('admin', '$2y$10$2S93nf/nHqwrDFalnwk1iOXUJOp6IKsDN3Uv.nweg0PJr2M/fsVLq', 'Administrador del Sistema');

CREATE TABLE cajeros (
    usuario VARCHAR(50) NOT NULL PRIMARY KEY,
    clave VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(60) NOT NULL,
    activo INT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE cuentas (
    no_cuenta VARCHAR(20) NOT NULL PRIMARY KEY,
    nombre VARCHAR(60) NOT NULL,
    dpi VARCHAR(13) NOT NULL,
    saldo DECIMAL(12,2) NOT NULL DEFAULT 0,
    fecha_creacion DATETIME NOT NULL
) ENGINE=InnoDB;

CREATE TABLE usuarios (
    correo VARCHAR(50) NOT NULL PRIMARY KEY,
    clave VARCHAR(255) NOT NULL,
    no_cuenta VARCHAR(20) NOT NULL,
    dpi VARCHAR(13) NOT NULL,
    fecha_registro DATETIME NOT NULL,
    UNIQUE KEY uq_usuarios_cuenta (no_cuenta)
) ENGINE=InnoDB;

CREATE TABLE cuentas_terceros (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    correo_usuario VARCHAR(50) NOT NULL,
    no_cuenta VARCHAR(20) NOT NULL,
    monto_maximo DECIMAL(12,2) NOT NULL,
    max_transacciones INT NOT NULL,
    alias VARCHAR(40) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE movimientos (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    no_cuenta VARCHAR(20) NOT NULL,
    tipo VARCHAR(20) NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    tipo_mov VARCHAR(10) NOT NULL,
    descripcion VARCHAR(100) NOT NULL,
    fecha DATETIME NOT NULL
) ENGINE=InnoDB;

CREATE TABLE transferencias (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    cuenta_origen VARCHAR(20) NOT NULL,
    cuenta_destino VARCHAR(20) NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    fecha DATETIME NOT NULL,
    KEY ix_transferencias_diarias (cuenta_origen, cuenta_destino, fecha)
) ENGINE=InnoDB;

CREATE TABLE server_logs (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ip_origen VARCHAR(45) NOT NULL,
    pagina_solicitada VARCHAR(255) NOT NULL,
    metodo VARCHAR(10) NOT NULL,
    codigo_respuesta INT NOT NULL DEFAULT 200,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
