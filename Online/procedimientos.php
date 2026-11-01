<?php
// El plan gratuito de InfinityFree no permite CREATE PROCEDURE. Este archivo implementa en PHP
// los mismos procedimientos de Local/creacion_y_migracion.sql: mismos nombres, parámetros,
// validaciones, transacciones y mensajes de error. Las páginas siguen invocando
// CALL sp_...(?) y BancaDB dirige cada llamada a la función equivalente de esta clase.

class ErrorProcedimiento extends Exception {}

class BancaDB extends mysqli {
    private const PROCEDIMIENTOS = [
        'sp_cajeros_listar' => 0, 'sp_cajero_existe' => 1, 'sp_cajero_insertar' => 3,
        'sp_cajero_cambiar_estado' => 2, 'sp_monitor' => 0, 'sp_cuenta_existe' => 1,
        'sp_cuenta_obtener' => 1, 'sp_cuenta_crear' => 4, 'sp_deposito' => 2, 'sp_retiro' => 2,
        'sp_usuario_existe_correo' => 1, 'sp_usuario_existe_cuenta' => 1, 'sp_usuario_insertar' => 4,
        'sp_tercero_insertar' => 5, 'sp_terceros_listar' => 1, 'sp_tercero_obtener' => 2,
        'sp_transferencias_dia' => 2, 'sp_transferir' => 3, 'sp_estado_cuenta' => 1,
        'sp_detalle_cuentas_dia' => 0, 'sp_detalle_usuarios_dia' => 0, 'sp_detalle_transacciones' => 0,
        'sp_detalle_depositos' => 0, 'sp_detalle_retiros' => 0,
    ];
    private const SALDO_MAXIMO = 999999999999; // Q9,999,999,999.99 expresado en centavos.

    #[\ReturnTypeWillChange]
    public function prepare($query) {
        $llamada = $this->llamada($query);
        return $llamada ?: parent::prepare($query);
    }

    #[\ReturnTypeWillChange]
    public function query($query, $result_mode = MYSQLI_STORE_RESULT) {
        $llamada = $this->llamada($query);
        if (!$llamada) {
            return parent::query($query, $result_mode);
        }
        return $llamada->execute() ? ($llamada->get_result() ?: true) : false;
    }

    private function llamada($sql) {
        if (!preg_match('/\A\s*CALL\s+(sp_\w+)\s*\(([^)]*)\)\s*;?\s*\z/i', $sql, $m)) {
            return null;
        }
        $nombre = strtolower($m[1]);
        $parametros = substr_count($m[2], '?');
        if (!isset(self::PROCEDIMIENTOS[$nombre]) || self::PROCEDIMIENTOS[$nombre] !== $parametros) {
            return null;
        }
        return new LlamadaProcedimiento($this, $nombre);
    }

    public function ejecutar_procedimiento($nombre, array $args) {
        if (!isset(self::PROCEDIMIENTOS[$nombre]) || self::PROCEDIMIENTOS[$nombre] !== count($args)) {
            throw new ErrorProcedimiento('Procedimiento no reconocido.');
        }
        return $this->$nombre(...$args);
    }

    // Ejecuta una sentencia preparada y devuelve el resultado (SELECT) o true.
    private function sql($sql, $tipos = '', ...$valores) {
        $stmt = parent::prepare($sql);
        if (!$stmt) {
            throw new ErrorProcedimiento($this->error);
        }
        if ($tipos !== '') {
            $stmt->bind_param($tipos, ...$valores);
        }
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new ErrorProcedimiento($error);
        }
        $resultado = $stmt->get_result();
        $stmt->close();
        return $resultado ?: true;
    }

    private function fila($sql, $tipos = '', ...$valores) {
        return $this->sql($sql, $tipos, ...$valores)->fetch_assoc();
    }

    private function transaccion(callable $operacion) {
        $this->begin_transaction();
        try {
            $operacion();
            $this->commit();
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
        return true;
    }

    // Convierte un importe a centavos enteros (redondeado como DECIMAL(12,2)) para comparar
    // sin errores de punto flotante. Hasta Q9,999,999,999.99 el resultado es exacto.
    private static function centavos($monto) {
        return (int)round((float)$monto * 100);
    }

    private static function validar_monto($monto) {
        if ($monto === null || !is_numeric($monto) || self::centavos($monto) <= 0) {
            throw new ErrorProcedimiento('Monto inválido.');
        }
    }

    private function sp_cajeros_listar() {
        return $this->sql('SELECT usuario, nombre_completo, activo FROM cajeros ORDER BY nombre_completo');
    }

    private function sp_cajero_existe($usuario) {
        return $this->sql('SELECT COUNT(*) AS total FROM cajeros WHERE usuario = ?', 's', $usuario);
    }

    private function sp_cajero_insertar($usuario, $clave, $nombre) {
        return $this->sql('INSERT INTO cajeros (usuario, clave, nombre_completo, activo) VALUES (?, ?, ?, 1)',
            'sss', $usuario, $clave, $nombre);
    }

    private function sp_cajero_cambiar_estado($usuario, $activo) {
        return $this->sql('UPDATE cajeros SET activo = ? WHERE usuario = ?', 'is', $activo, $usuario);
    }

    private function sp_monitor() {
        return $this->sql("SELECT
            (SELECT COUNT(*) FROM cuentas WHERE DATE(fecha_creacion) = CURDATE()) AS cuentas_dia,
            (SELECT COUNT(*) FROM usuarios WHERE DATE(fecha_registro) = CURDATE()) AS usuarios_dia,
            (SELECT COUNT(*) FROM movimientos) AS transacciones,
            (SELECT COUNT(*) FROM movimientos WHERE tipo = 'DEPOSITO') AS depositos,
            (SELECT COUNT(*) FROM movimientos WHERE tipo = 'RETIRO') AS retiros,
            IFNULL((SELECT SUM(monto) FROM movimientos WHERE tipo = 'DEPOSITO'), 0) AS monto_depositos,
            IFNULL((SELECT SUM(monto) FROM movimientos WHERE tipo = 'RETIRO'), 0) AS monto_retiros");
    }

    private function sp_cuenta_existe($no_cuenta) {
        return $this->sql('SELECT COUNT(*) AS total FROM cuentas WHERE no_cuenta = ?', 's', $no_cuenta);
    }

    private function sp_cuenta_obtener($no_cuenta) {
        return $this->sql('SELECT no_cuenta, nombre, dpi, saldo FROM cuentas WHERE no_cuenta = ?', 's', $no_cuenta);
    }

    private function sp_cuenta_crear($no_cuenta, $nombre, $dpi, $monto) {
        return $this->sql('INSERT INTO cuentas (no_cuenta, nombre, dpi, saldo, fecha_creacion)
            VALUES (?, ?, ?, CAST(? AS DECIMAL(12,2)), NOW())', 'ssss', $no_cuenta, $nombre, $dpi, $monto);
    }

    private function sp_deposito($no_cuenta, $monto) {
        return $this->transaccion(function () use ($no_cuenta, $monto) {
            self::validar_monto($monto);
            $cuenta = $this->fila('SELECT saldo FROM cuentas WHERE no_cuenta = ? FOR UPDATE', 's', $no_cuenta);
            if (!$cuenta) {
                throw new ErrorProcedimiento('La cuenta no existe.');
            }
            if (self::centavos($cuenta['saldo']) + self::centavos($monto) > self::SALDO_MAXIMO) {
                throw new ErrorProcedimiento('El saldo excede el máximo permitido.');
            }
            $this->sql('UPDATE cuentas SET saldo = saldo + CAST(? AS DECIMAL(12,2)) WHERE no_cuenta = ?',
                'ss', $monto, $no_cuenta);
            $this->sql("INSERT INTO movimientos (no_cuenta, tipo, monto, tipo_mov, descripcion, fecha)
                VALUES (?, 'DEPOSITO', CAST(? AS DECIMAL(12,2)), 'CREDITO', 'Deposito monetario', NOW())",
                'ss', $no_cuenta, $monto);
        });
    }

    private function sp_retiro($no_cuenta, $monto) {
        return $this->transaccion(function () use ($no_cuenta, $monto) {
            self::validar_monto($monto);
            $cuenta = $this->fila('SELECT saldo FROM cuentas WHERE no_cuenta = ? FOR UPDATE', 's', $no_cuenta);
            if (!$cuenta) {
                throw new ErrorProcedimiento('La cuenta no existe.');
            }
            if (self::centavos($cuenta['saldo']) < self::centavos($monto)) {
                throw new ErrorProcedimiento('Saldo insuficiente.');
            }
            $this->sql('UPDATE cuentas SET saldo = saldo - CAST(? AS DECIMAL(12,2)) WHERE no_cuenta = ?',
                'ss', $monto, $no_cuenta);
            $this->sql("INSERT INTO movimientos (no_cuenta, tipo, monto, tipo_mov, descripcion, fecha)
                VALUES (?, 'RETIRO', CAST(? AS DECIMAL(12,2)), 'DEBITO', 'Retiro monetario', NOW())",
                'ss', $no_cuenta, $monto);
        });
    }

    private function sp_usuario_existe_correo($correo) {
        return $this->sql('SELECT COUNT(*) AS total FROM usuarios WHERE correo = ?', 's', $correo);
    }

    private function sp_usuario_existe_cuenta($no_cuenta) {
        return $this->sql('SELECT COUNT(*) AS total FROM usuarios WHERE no_cuenta = ?', 's', $no_cuenta);
    }

    private function sp_usuario_insertar($correo, $clave, $no_cuenta, $dpi) {
        return $this->sql('INSERT INTO usuarios (correo, clave, no_cuenta, dpi, fecha_registro)
            VALUES (?, ?, ?, ?, NOW())', 'ssss', $correo, $clave, $no_cuenta, $dpi);
    }

    private function sp_tercero_insertar($correo, $no_cuenta, $monto_maximo, $max_transacciones, $alias) {
        return $this->sql('INSERT INTO cuentas_terceros (correo_usuario, no_cuenta, monto_maximo, max_transacciones, alias)
            VALUES (?, ?, CAST(? AS DECIMAL(12,2)), ?, ?)',
            'sssis', $correo, $no_cuenta, $monto_maximo, $max_transacciones, $alias);
    }

    private function sp_terceros_listar($correo) {
        return $this->sql('SELECT id, no_cuenta, monto_maximo, max_transacciones, alias
            FROM cuentas_terceros WHERE correo_usuario = ? ORDER BY alias', 's', $correo);
    }

    private function sp_tercero_obtener($id, $correo) {
        return $this->sql('SELECT id, no_cuenta, monto_maximo, max_transacciones, alias
            FROM cuentas_terceros WHERE id = ? AND correo_usuario = ?', 'is', $id, $correo);
    }

    private function sp_transferencias_dia($origen, $destino) {
        return $this->sql('SELECT COUNT(*) AS total FROM transferencias
            WHERE cuenta_origen = ? AND cuenta_destino = ? AND DATE(fecha) = CURDATE()', 'ss', $origen, $destino);
    }

    private function sp_transferir($correo, $tercero, $monto) {
        return $this->transaccion(function () use ($correo, $tercero, $monto) {
            self::validar_monto($monto);
            // Un solo instante para toda la operación, igual que NOW() dentro del CALL original.
            $ahora = $this->fila('SELECT NOW() AS ahora')['ahora'];
            $usuario = $this->fila('SELECT no_cuenta FROM usuarios WHERE correo = ? FOR UPDATE', 's', $correo);
            $destino = $this->fila('SELECT no_cuenta, monto_maximo, max_transacciones FROM cuentas_terceros
                WHERE id = ? AND correo_usuario = ? FOR UPDATE', 'is', $tercero, $correo);
            if (!$usuario || !$destino) {
                throw new ErrorProcedimiento('La cuenta de tercero no es válida.');
            }
            $origen = $usuario['no_cuenta'];
            $cuenta_destino = $destino['no_cuenta'];
            // Se compara en SQL para respetar la colación de las cuentas (ABC = abc).
            if ($this->fila('SELECT ? = ? AS iguales', 'ss', $origen, $cuenta_destino)['iguales']) {
                throw new ErrorProcedimiento('No puede transferir a su propia cuenta.');
            }
            // Orden consistente de bloqueo para transferencias en direcciones opuestas.
            $bloqueo = 'SELECT saldo FROM cuentas WHERE no_cuenta = ? FOR UPDATE';
            if (strcmp($origen, $cuenta_destino) < 0) {
                $saldo = $this->fila($bloqueo, 's', $origen);
                $saldo_destino = $this->fila($bloqueo, 's', $cuenta_destino);
            } else {
                $saldo_destino = $this->fila($bloqueo, 's', $cuenta_destino);
                $saldo = $this->fila($bloqueo, 's', $origen);
            }
            if (!$saldo || !$saldo_destino) {
                throw new ErrorProcedimiento('La cuenta no existe.');
            }
            if (self::centavos($monto) > self::centavos($destino['monto_maximo'])) {
                throw new ErrorProcedimiento('El monto supera el máximo permitido.');
            }
            $hoy = $this->fila('SELECT COUNT(*) AS total FROM transferencias
                WHERE cuenta_origen = ? AND cuenta_destino = ?
                  AND fecha >= DATE(?) AND fecha < DATE(?) + INTERVAL 1 DAY FOR UPDATE',
                'ssss', $origen, $cuenta_destino, $ahora, $ahora);
            if ((int)$hoy['total'] >= (int)$destino['max_transacciones']) {
                throw new ErrorProcedimiento('Ya alcanzó el máximo de transferencias diarias.');
            }
            if (self::centavos($saldo['saldo']) < self::centavos($monto)) {
                throw new ErrorProcedimiento('Saldo insuficiente.');
            }
            if (self::centavos($saldo_destino['saldo']) + self::centavos($monto) > self::SALDO_MAXIMO) {
                throw new ErrorProcedimiento('El saldo excede el máximo permitido.');
            }
            $this->sql('UPDATE cuentas SET saldo = saldo - CAST(? AS DECIMAL(12,2)) WHERE no_cuenta = ?',
                'ss', $monto, $origen);
            $this->sql('UPDATE cuentas SET saldo = saldo + CAST(? AS DECIMAL(12,2)) WHERE no_cuenta = ?',
                'ss', $monto, $cuenta_destino);
            $this->sql("INSERT INTO movimientos (no_cuenta, tipo, monto, tipo_mov, descripcion, fecha) VALUES
                (?, 'TRANSFERENCIA', CAST(? AS DECIMAL(12,2)), 'DEBITO', CONCAT('Transferencia a cuenta ', ?), ?),
                (?, 'TRANSFERENCIA', CAST(? AS DECIMAL(12,2)), 'CREDITO', CONCAT('Transferencia de cuenta ', ?), ?)",
                'ssssssss', $origen, $monto, $cuenta_destino, $ahora, $cuenta_destino, $monto, $origen, $ahora);
            $this->sql('INSERT INTO transferencias (cuenta_origen, cuenta_destino, monto, fecha)
                VALUES (?, ?, CAST(? AS DECIMAL(12,2)), ?)', 'ssss', $origen, $cuenta_destino, $monto, $ahora);
        });
    }

    private function sp_estado_cuenta($no_cuenta) {
        return $this->sql('SELECT fecha, tipo, descripcion, tipo_mov, monto FROM movimientos
            WHERE no_cuenta = ? ORDER BY fecha', 's', $no_cuenta);
    }

    private function sp_detalle_cuentas_dia() {
        return $this->sql('SELECT no_cuenta, nombre, dpi, saldo, fecha_creacion FROM cuentas
            WHERE DATE(fecha_creacion) = CURDATE() ORDER BY fecha_creacion');
    }

    private function sp_detalle_usuarios_dia() {
        return $this->sql('SELECT correo, no_cuenta, fecha_registro FROM usuarios
            WHERE DATE(fecha_registro) = CURDATE() ORDER BY fecha_registro');
    }

    private function sp_detalle_transacciones() {
        return $this->sql('SELECT fecha, no_cuenta, tipo, tipo_mov, monto, descripcion FROM movimientos ORDER BY fecha');
    }

    private function sp_detalle_depositos() {
        return $this->sql("SELECT fecha, no_cuenta, monto, descripcion FROM movimientos
            WHERE tipo = 'DEPOSITO' ORDER BY fecha");
    }

    private function sp_detalle_retiros() {
        return $this->sql("SELECT fecha, no_cuenta, monto, descripcion FROM movimientos
            WHERE tipo = 'RETIRO' ORDER BY fecha");
    }
}

// Sustituto de mysqli_stmt para una llamada CALL: conserva bind_param, execute,
// get_result, error y close, que es lo que usan las páginas.
class LlamadaProcedimiento {
    public $error = '';
    public $errno = 0;
    private $db;
    private $nombre;
    private $valores = [];
    private $resultado = null;

    public function __construct(BancaDB $db, $nombre) {
        $this->db = $db;
        $this->nombre = $nombre;
    }

    public function bind_param($tipos, &...$valores) {
        $this->valores = [];
        foreach ($valores as $i => &$valor) {
            $this->valores[$i] = &$valor;
        }
        return true;
    }

    public function execute() {
        $this->error = '';
        $this->errno = 0;
        $this->resultado = null;
        try {
            $this->resultado = $this->db->ejecutar_procedimiento($this->nombre, array_values($this->valores));
            return true;
        } catch (ErrorProcedimiento $e) {
            $this->error = $e->getMessage();
            $this->errno = 1644;
            return false;
        }
    }

    public function get_result() {
        return $this->resultado instanceof mysqli_result ? $this->resultado : false;
    }

    public function close() {
        return true;
    }
}
