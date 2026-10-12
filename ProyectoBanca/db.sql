CREATE DATABASE IF NOT EXISTS banca_db CHARACTER SET utf8mb4;
USE banca_db;

CREATE TABLE IF NOT EXISTS administradores (
    usuario VARCHAR(50) NOT NULL PRIMARY KEY,
    clave VARCHAR(20) NOT NULL,
    nombre_completo VARCHAR(60) NOT NULL
);

INSERT IGNORE INTO administradores (usuario, clave, nombre_completo) VALUES
('admin', 'admin123', 'Administrador del Sistema');

CREATE TABLE IF NOT EXISTS cajeros (
    usuario VARCHAR(50) NOT NULL PRIMARY KEY,
    clave VARCHAR(20) NOT NULL,
    nombre_completo VARCHAR(60) NOT NULL,
    activo INT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS cuentas (
    no_cuenta VARCHAR(20) NOT NULL PRIMARY KEY,
    nombre VARCHAR(60) NOT NULL,
    dpi VARCHAR(13) NOT NULL,
    saldo DECIMAL(12,2) NOT NULL DEFAULT 0,
    fecha_creacion DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS usuarios (
    correo VARCHAR(50) NOT NULL PRIMARY KEY,
    clave VARCHAR(20) NOT NULL,
    no_cuenta VARCHAR(20) NOT NULL,
    dpi VARCHAR(13) NOT NULL,
    fecha_registro DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS cuentas_terceros (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    correo_usuario VARCHAR(50) NOT NULL,
    no_cuenta VARCHAR(20) NOT NULL,
    monto_maximo DECIMAL(12,2) NOT NULL,
    max_transacciones INT NOT NULL,
    alias VARCHAR(40) NOT NULL
);

CREATE TABLE IF NOT EXISTS movimientos (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    no_cuenta VARCHAR(20) NOT NULL,
    tipo VARCHAR(20) NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    tipo_mov VARCHAR(10) NOT NULL,
    descripcion VARCHAR(100) NOT NULL,
    fecha DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS transferencias (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    cuenta_origen VARCHAR(20) NOT NULL,
    cuenta_destino VARCHAR(20) NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    fecha DATETIME NOT NULL
);

DROP PROCEDURE IF EXISTS sp_login_admin;
DROP PROCEDURE IF EXISTS sp_login_cajero;
DROP PROCEDURE IF EXISTS sp_login_usuario;
DROP PROCEDURE IF EXISTS sp_cajeros_listar;
DROP PROCEDURE IF EXISTS sp_cajero_existe;
DROP PROCEDURE IF EXISTS sp_cajero_insertar;
DROP PROCEDURE IF EXISTS sp_cajero_cambiar_estado;
DROP PROCEDURE IF EXISTS sp_monitor;
DROP PROCEDURE IF EXISTS sp_cuenta_existe;
DROP PROCEDURE IF EXISTS sp_cuenta_obtener;
DROP PROCEDURE IF EXISTS sp_cuenta_crear;
DROP PROCEDURE IF EXISTS sp_deposito;
DROP PROCEDURE IF EXISTS sp_retiro;
DROP PROCEDURE IF EXISTS sp_usuario_existe_correo;
DROP PROCEDURE IF EXISTS sp_usuario_existe_cuenta;
DROP PROCEDURE IF EXISTS sp_usuario_insertar;
DROP PROCEDURE IF EXISTS sp_tercero_insertar;
DROP PROCEDURE IF EXISTS sp_terceros_listar;
DROP PROCEDURE IF EXISTS sp_tercero_obtener;
DROP PROCEDURE IF EXISTS sp_transferencias_dia;
DROP PROCEDURE IF EXISTS sp_transferir;
DROP PROCEDURE IF EXISTS sp_estado_cuenta;
DROP PROCEDURE IF EXISTS sp_detalle_cuentas_dia;
DROP PROCEDURE IF EXISTS sp_detalle_usuarios_dia;
DROP PROCEDURE IF EXISTS sp_detalle_transacciones;
DROP PROCEDURE IF EXISTS sp_detalle_depositos;
DROP PROCEDURE IF EXISTS sp_detalle_retiros;

DELIMITER $$

CREATE PROCEDURE sp_login_admin(IN p_usuario VARCHAR(50), IN p_clave VARCHAR(20))
BEGIN
    SELECT nombre_completo
    FROM administradores
    WHERE usuario = p_usuario AND clave = p_clave;
END$$

CREATE PROCEDURE sp_login_cajero(IN p_usuario VARCHAR(50), IN p_clave VARCHAR(20))
BEGIN
    SELECT nombre_completo
    FROM cajeros
    WHERE usuario = p_usuario AND clave = p_clave AND activo = 1;
END$$

CREATE PROCEDURE sp_login_usuario(IN p_correo VARCHAR(50), IN p_clave VARCHAR(20))
BEGIN
    SELECT no_cuenta
    FROM usuarios
    WHERE correo = p_correo AND clave = p_clave;
END$$

CREATE PROCEDURE sp_cajeros_listar()
BEGIN
    SELECT usuario, nombre_completo, activo
    FROM cajeros
    ORDER BY nombre_completo;
END$$

CREATE PROCEDURE sp_cajero_existe(IN p_usuario VARCHAR(50))
BEGIN
    SELECT COUNT(*) AS total
    FROM cajeros
    WHERE usuario = p_usuario;
END$$

CREATE PROCEDURE sp_cajero_insertar(
    IN p_usuario VARCHAR(50),
    IN p_clave VARCHAR(20),
    IN p_nombre VARCHAR(60)
)
BEGIN
    INSERT INTO cajeros (usuario, clave, nombre_completo, activo)
    VALUES (p_usuario, p_clave, p_nombre, 1);
END$$

CREATE PROCEDURE sp_cajero_cambiar_estado(IN p_usuario VARCHAR(50), IN p_activo INT)
BEGIN
    UPDATE cajeros SET activo = p_activo WHERE usuario = p_usuario;
END$$

CREATE PROCEDURE sp_monitor()
BEGIN
    SELECT
        (SELECT COUNT(*) FROM cuentas WHERE DATE(fecha_creacion) = CURDATE()) AS cuentas_dia,
        (SELECT COUNT(*) FROM usuarios WHERE DATE(fecha_registro) = CURDATE()) AS usuarios_dia,
        (SELECT COUNT(*) FROM movimientos) AS transacciones,
        (SELECT COUNT(*) FROM movimientos WHERE tipo = 'DEPOSITO') AS depositos,
        (SELECT COUNT(*) FROM movimientos WHERE tipo = 'RETIRO') AS retiros,
        IFNULL((SELECT SUM(monto) FROM movimientos WHERE tipo = 'DEPOSITO'), 0) AS monto_depositos,
        IFNULL((SELECT SUM(monto) FROM movimientos WHERE tipo = 'RETIRO'), 0) AS monto_retiros;
END$$

CREATE PROCEDURE sp_cuenta_existe(IN p_no_cuenta VARCHAR(20))
BEGIN
    SELECT COUNT(*) AS total
    FROM cuentas
    WHERE no_cuenta = p_no_cuenta;
END$$

CREATE PROCEDURE sp_cuenta_obtener(IN p_no_cuenta VARCHAR(20))
BEGIN
    SELECT no_cuenta, nombre, dpi, saldo
    FROM cuentas
    WHERE no_cuenta = p_no_cuenta;
END$$

CREATE PROCEDURE sp_cuenta_crear(
    IN p_no_cuenta VARCHAR(20),
    IN p_nombre VARCHAR(60),
    IN p_dpi VARCHAR(13),
    IN p_monto DECIMAL(12,2)
)
BEGIN
    INSERT INTO cuentas (no_cuenta, nombre, dpi, saldo, fecha_creacion)
    VALUES (p_no_cuenta, p_nombre, p_dpi, p_monto, NOW());
END$$

CREATE PROCEDURE sp_deposito(IN p_no_cuenta VARCHAR(20), IN p_monto DECIMAL(12,2))
BEGIN
    UPDATE cuentas SET saldo = saldo + p_monto WHERE no_cuenta = p_no_cuenta;

    INSERT INTO movimientos (no_cuenta, tipo, monto, tipo_mov, descripcion, fecha)
    VALUES (p_no_cuenta, 'DEPOSITO', p_monto, 'CREDITO', 'Deposito monetario', NOW());
END$$

CREATE PROCEDURE sp_retiro(IN p_no_cuenta VARCHAR(20), IN p_monto DECIMAL(12,2))
BEGIN
    UPDATE cuentas SET saldo = saldo - p_monto WHERE no_cuenta = p_no_cuenta;

    INSERT INTO movimientos (no_cuenta, tipo, monto, tipo_mov, descripcion, fecha)
    VALUES (p_no_cuenta, 'RETIRO', p_monto, 'DEBITO', 'Retiro monetario', NOW());
END$$

CREATE PROCEDURE sp_usuario_existe_correo(IN p_correo VARCHAR(50))
BEGIN
    SELECT COUNT(*) AS total
    FROM usuarios
    WHERE correo = p_correo;
END$$

CREATE PROCEDURE sp_usuario_existe_cuenta(IN p_no_cuenta VARCHAR(20))
BEGIN
    SELECT COUNT(*) AS total
    FROM usuarios
    WHERE no_cuenta = p_no_cuenta;
END$$

CREATE PROCEDURE sp_usuario_insertar(
    IN p_correo VARCHAR(50),
    IN p_clave VARCHAR(20),
    IN p_no_cuenta VARCHAR(20),
    IN p_dpi VARCHAR(13)
)
BEGIN
    INSERT INTO usuarios (correo, clave, no_cuenta, dpi, fecha_registro)
    VALUES (p_correo, p_clave, p_no_cuenta, p_dpi, NOW());
END$$

CREATE PROCEDURE sp_tercero_insertar(
    IN p_correo VARCHAR(50),
    IN p_no_cuenta VARCHAR(20),
    IN p_monto_maximo DECIMAL(12,2),
    IN p_max_transacciones INT,
    IN p_alias VARCHAR(40)
)
BEGIN
    INSERT INTO cuentas_terceros (correo_usuario, no_cuenta, monto_maximo, max_transacciones, alias)
    VALUES (p_correo, p_no_cuenta, p_monto_maximo, p_max_transacciones, p_alias);
END$$

CREATE PROCEDURE sp_terceros_listar(IN p_correo VARCHAR(50))
BEGIN
    SELECT id, no_cuenta, monto_maximo, max_transacciones, alias
    FROM cuentas_terceros
    WHERE correo_usuario = p_correo
    ORDER BY alias;
END$$

CREATE PROCEDURE sp_tercero_obtener(IN p_id INT, IN p_correo VARCHAR(50))
BEGIN
    SELECT id, no_cuenta, monto_maximo, max_transacciones, alias
    FROM cuentas_terceros
    WHERE id = p_id AND correo_usuario = p_correo;
END$$

CREATE PROCEDURE sp_transferencias_dia(IN p_origen VARCHAR(20), IN p_destino VARCHAR(20))
BEGIN
    SELECT COUNT(*) AS total
    FROM transferencias
    WHERE cuenta_origen = p_origen AND cuenta_destino = p_destino
      AND DATE(fecha) = CURDATE();
END$$

CREATE PROCEDURE sp_transferir(
    IN p_origen VARCHAR(20),
    IN p_destino VARCHAR(20),
    IN p_monto DECIMAL(12,2)
)
BEGIN
    DECLARE v_saldo DECIMAL(12,2);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SELECT saldo INTO v_saldo FROM cuentas WHERE no_cuenta = p_origen FOR UPDATE;

    IF v_saldo < p_monto THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Saldo insuficiente.';
    END IF;

    UPDATE cuentas SET saldo = saldo - p_monto WHERE no_cuenta = p_origen;
    UPDATE cuentas SET saldo = saldo + p_monto WHERE no_cuenta = p_destino;

    INSERT INTO movimientos (no_cuenta, tipo, monto, tipo_mov, descripcion, fecha)
    VALUES (p_origen, 'TRANSFERENCIA', p_monto, 'DEBITO', CONCAT('Transferencia a cuenta ', p_destino), NOW());

    INSERT INTO movimientos (no_cuenta, tipo, monto, tipo_mov, descripcion, fecha)
    VALUES (p_destino, 'TRANSFERENCIA', p_monto, 'CREDITO', CONCAT('Transferencia de cuenta ', p_origen), NOW());

    INSERT INTO transferencias (cuenta_origen, cuenta_destino, monto, fecha)
    VALUES (p_origen, p_destino, p_monto, NOW());

    COMMIT;
END$$

CREATE PROCEDURE sp_estado_cuenta(IN p_no_cuenta VARCHAR(20))
BEGIN
    SELECT fecha, tipo, descripcion, tipo_mov, monto
    FROM movimientos
    WHERE no_cuenta = p_no_cuenta
    ORDER BY fecha;
END$$

CREATE PROCEDURE sp_detalle_cuentas_dia()
BEGIN
    SELECT no_cuenta, nombre, dpi, saldo, fecha_creacion
    FROM cuentas
    WHERE DATE(fecha_creacion) = CURDATE()
    ORDER BY fecha_creacion;
END$$

CREATE PROCEDURE sp_detalle_usuarios_dia()
BEGIN
    SELECT correo, no_cuenta, fecha_registro
    FROM usuarios
    WHERE DATE(fecha_registro) = CURDATE()
    ORDER BY fecha_registro;
END$$

CREATE PROCEDURE sp_detalle_transacciones()
BEGIN
    SELECT fecha, no_cuenta, tipo, tipo_mov, monto, descripcion
    FROM movimientos
    ORDER BY fecha;
END$$

CREATE PROCEDURE sp_detalle_depositos()
BEGIN
    SELECT fecha, no_cuenta, monto, descripcion
    FROM movimientos
    WHERE tipo = 'DEPOSITO'
    ORDER BY fecha;
END$$

CREATE PROCEDURE sp_detalle_retiros()
BEGIN
    SELECT fecha, no_cuenta, monto, descripcion
    FROM movimientos
    WHERE tipo = 'RETIRO'
    ORDER BY fecha;
END$$

DELIMITER ;
