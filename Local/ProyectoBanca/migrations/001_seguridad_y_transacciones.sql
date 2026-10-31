USE banca_db;
ALTER TABLE administradores ENGINE=InnoDB;
ALTER TABLE cajeros ENGINE=InnoDB;
ALTER TABLE cuentas ENGINE=InnoDB;
ALTER TABLE usuarios ENGINE=InnoDB;
ALTER TABLE cuentas_terceros ENGINE=InnoDB;
ALTER TABLE movimientos ENGINE=InnoDB;
ALTER TABLE transferencias ENGINE=InnoDB;
ALTER TABLE administradores MODIFY clave VARCHAR(255) NOT NULL;
ALTER TABLE cajeros MODIFY clave VARCHAR(255) NOT NULL;
ALTER TABLE usuarios MODIFY clave VARCHAR(255) NOT NULL;
SET @indice = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND INDEX_NAME = 'uq_usuarios_cuenta');
SET @ddl = IF(@indice = 0, 'ALTER TABLE usuarios ADD UNIQUE KEY uq_usuarios_cuenta (no_cuenta)', 'SELECT 1');
PREPARE migracion_indice FROM @ddl;
EXECUTE migracion_indice;
DEALLOCATE PREPARE migracion_indice;
SET @indice = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transferencias' AND INDEX_NAME = 'ix_transferencias_diarias');
SET @ddl = IF(@indice = 0, 'ALTER TABLE transferencias ADD KEY ix_transferencias_diarias (cuenta_origen, cuenta_destino, fecha)', 'SELECT 1');
PREPARE migracion_indice FROM @ddl;
EXECUTE migracion_indice;
DEALLOCATE PREPARE migracion_indice;
DELIMITER $
DROP PROCEDURE IF EXISTS sp_deposito$$
CREATE PROCEDURE sp_deposito(IN p_no_cuenta VARCHAR(20), IN p_monto DECIMAL(12,2))
BEGIN
    DECLARE v_saldo DECIMAL(12,2) DEFAULT NULL;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    START TRANSACTION;
    IF p_monto IS NULL OR p_monto <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Monto inválido.';
    END IF;
    SELECT saldo INTO v_saldo FROM cuentas WHERE no_cuenta = p_no_cuenta FOR UPDATE;
    IF v_saldo IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La cuenta no existe.';
    END IF;
    IF v_saldo + p_monto > 9999999999.99 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El saldo excede el máximo permitido.';
    END IF;
    UPDATE cuentas SET saldo = saldo + p_monto WHERE no_cuenta = p_no_cuenta;
    INSERT INTO movimientos (no_cuenta, tipo, monto, tipo_mov, descripcion, fecha)
    VALUES (p_no_cuenta, 'DEPOSITO', p_monto, 'CREDITO', 'Deposito monetario', NOW());
    COMMIT;
END$$
DROP PROCEDURE IF EXISTS sp_retiro$$
CREATE PROCEDURE sp_retiro(IN p_no_cuenta VARCHAR(20), IN p_monto DECIMAL(12,2))
BEGIN
    DECLARE v_saldo DECIMAL(12,2) DEFAULT NULL;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    START TRANSACTION;
    IF p_monto IS NULL OR p_monto <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Monto inválido.';
    END IF;
    SELECT saldo INTO v_saldo FROM cuentas WHERE no_cuenta = p_no_cuenta FOR UPDATE;
    IF v_saldo IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La cuenta no existe.';
    END IF;
    IF v_saldo < p_monto THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Saldo insuficiente.';
    END IF;
    UPDATE cuentas SET saldo = saldo - p_monto WHERE no_cuenta = p_no_cuenta;
    INSERT INTO movimientos (no_cuenta, tipo, monto, tipo_mov, descripcion, fecha)
    VALUES (p_no_cuenta, 'RETIRO', p_monto, 'DEBITO', 'Retiro monetario', NOW());
    COMMIT;
END$$
DROP PROCEDURE IF EXISTS sp_transferir$$
CREATE PROCEDURE sp_transferir(IN p_correo VARCHAR(50), IN p_tercero INT, IN p_monto DECIMAL(12,2))
BEGIN
    DECLARE v_origen VARCHAR(20) DEFAULT NULL;
    DECLARE v_destino VARCHAR(20) DEFAULT NULL;
    DECLARE v_saldo DECIMAL(12,2) DEFAULT NULL;
    DECLARE v_saldo_destino DECIMAL(12,2) DEFAULT NULL;
    DECLARE v_maximo DECIMAL(12,2);
    DECLARE v_limite INT;
    DECLARE v_total INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    START TRANSACTION;
    IF p_monto IS NULL OR p_monto <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Monto inválido.';
    END IF;
    SELECT no_cuenta INTO v_origen FROM usuarios WHERE correo = p_correo FOR UPDATE;
    SELECT no_cuenta, monto_maximo, max_transacciones INTO v_destino, v_maximo, v_limite
    FROM cuentas_terceros WHERE id = p_tercero AND correo_usuario = p_correo FOR UPDATE;
    IF v_origen IS NULL OR v_destino IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La cuenta de tercero no es válida.';
    END IF;
    IF v_origen = v_destino THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No puede transferir a su propia cuenta.';
    END IF;
    -- Orden consistente de bloqueo para transferencias en direcciones opuestas.
    IF v_origen < v_destino THEN
        SELECT saldo INTO v_saldo FROM cuentas WHERE no_cuenta = v_origen FOR UPDATE;
        SELECT saldo INTO v_saldo_destino FROM cuentas WHERE no_cuenta = v_destino FOR UPDATE;
    ELSE
        SELECT saldo INTO v_saldo_destino FROM cuentas WHERE no_cuenta = v_destino FOR UPDATE;
        SELECT saldo INTO v_saldo FROM cuentas WHERE no_cuenta = v_origen FOR UPDATE;
    END IF;
    IF v_saldo IS NULL OR v_saldo_destino IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La cuenta no existe.';
    END IF;
    IF p_monto > v_maximo THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El monto supera el máximo permitido.';
    END IF;
    SELECT COUNT(*) INTO v_total FROM transferencias
    WHERE cuenta_origen = v_origen AND cuenta_destino = v_destino
      AND fecha >= CURRENT_DATE() AND fecha < CURRENT_DATE() + INTERVAL 1 DAY FOR UPDATE;
    IF v_total >= v_limite THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ya alcanzó el máximo de transferencias diarias.';
    END IF;
    IF v_saldo < p_monto THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Saldo insuficiente.';
    END IF;
    IF v_saldo_destino + p_monto > 9999999999.99 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El saldo excede el máximo permitido.';
    END IF;
    UPDATE cuentas SET saldo = saldo - p_monto WHERE no_cuenta = v_origen;
    UPDATE cuentas SET saldo = saldo + p_monto WHERE no_cuenta = v_destino;
    INSERT INTO movimientos (no_cuenta, tipo, monto, tipo_mov, descripcion, fecha) VALUES
    (v_origen, 'TRANSFERENCIA', p_monto, 'DEBITO', CONCAT('Transferencia a cuenta ', v_destino), NOW()),
    (v_destino, 'TRANSFERENCIA', p_monto, 'CREDITO', CONCAT('Transferencia de cuenta ', v_origen), NOW());
    INSERT INTO transferencias (cuenta_origen, cuenta_destino, monto, fecha)
    VALUES (v_origen, v_destino, p_monto, NOW());
    COMMIT;
END$$
DROP PROCEDURE IF EXISTS sp_cajero_insertar$$
CREATE PROCEDURE sp_cajero_insertar(
    IN p_usuario VARCHAR(50),
    IN p_clave VARCHAR(255),
    IN p_nombre VARCHAR(60)
)
BEGIN
    INSERT INTO cajeros (usuario, clave, nombre_completo, activo)
    VALUES (p_usuario, p_clave, p_nombre, 1);
END$$
DROP PROCEDURE IF EXISTS sp_usuario_insertar$$
CREATE PROCEDURE sp_usuario_insertar(
    IN p_correo VARCHAR(50),
    IN p_clave VARCHAR(255),
    IN p_no_cuenta VARCHAR(20),
    IN p_dpi VARCHAR(13)
)
BEGIN
    INSERT INTO usuarios (correo, clave, no_cuenta, dpi, fecha_registro)
    VALUES (p_correo, p_clave, p_no_cuenta, p_dpi, NOW());
END$$
DELIMITER ;
