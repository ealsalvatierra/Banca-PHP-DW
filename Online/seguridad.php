<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('BANCA_SESSION');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Strict',
        'path' => '/',
    ]);
    session_start();
}
header('Cache-Control: no-store');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

function exigir_rol($rol, $login) {
    $expirada = isset($_SESSION['ultima_actividad']) && time() - $_SESSION['ultima_actividad'] > 1800;
    if ($expirada || ($_SESSION['autenticada'] ?? false) !== true
        || !isset($_SESSION['rol'], $_SESSION['usuario']) || $_SESSION['rol'] !== $rol) {
        if ($expirada) {
            $_SESSION = [];
            session_regenerate_id(true);
        }
        header('Location: ' . $login);
        exit;
    }
    $_SESSION['ultima_actividad'] = time();
    header('Cache-Control: no-store');
}

function csrf_campo() {
    $ahora = time();
    $_SESSION['csrf'] = array_filter($_SESSION['csrf'] ?? [], function ($creado) use ($ahora) {
        return $ahora - $creado < 1800;
    });
    while (count($_SESSION['csrf']) >= 64) {
        array_shift($_SESSION['csrf']);
    }
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf'][$token] = $ahora;
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function validar_csrf() {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !preg_match('/\A[0-9a-f]{64}\z/', $token)
        || !isset($_SESSION['csrf'][$token]) || time() - $_SESSION['csrf'][$token] >= 1800) {
        http_response_code(403);
        exit('El formulario expiró o ya fue enviado. Vuelva a la página y reintente.');
    }
    // La sesión permanece bloqueada durante la petición: un token se consume una sola vez.
    unset($_SESSION['csrf'][$token]);
}

function monto_valido($monto, $permite_cero = false) {
    if (!is_string($monto) || !preg_match('/\A[0-9]{1,10}(?:\.[0-9]{1,2})?\z/', $monto)) {
        return false;
    }
    return $permite_cero || (float)$monto > 0;
}

function entrada_texto($nombre) {
    $valor = $_POST[$nombre] ?? '';
    return is_string($valor) ? trim($valor) : '';
}

function limpiar_resultados($mysqli) {
    while ($mysqli->more_results()) {
        if (!$mysqli->next_result()) {
            break;
        }
        $extra = $mysqli->store_result();
        if ($extra) {
            $extra->free();
        }
    }
}

function error_operacion($stmt) {
    $mensajes = [
        'Saldo insuficiente.', 'La cuenta no existe.', 'Monto inválido.',
        'El saldo excede el máximo permitido.', 'La cuenta de tercero no es válida.',
        'No puede transferir a su propia cuenta.', 'El monto supera el máximo permitido.',
        'Ya alcanzó el máximo de transferencias diarias.',
    ];
    return in_array($stmt->error, $mensajes, true)
        ? $stmt->error : 'No se pudo completar la operación. Intente de nuevo.';
}

function autenticar($mysqli, $rol, $identificador, $clave) {
    $config = [
        'admin' => ['administradores', 'usuario', 'nombre_completo'],
        'cajero' => ['cajeros', 'usuario', 'nombre_completo'],
        'usuario' => ['usuarios', 'correo', 'no_cuenta'],
    ];
    if (!isset($config[$rol])) {
        return false;
    }
    list($tabla, $campo, $dato) = $config[$rol];
    $filtro = $rol === 'cajero' ? ' AND activo = 1' : '';
    $stmt = $mysqli->prepare("SELECT clave, $dato FROM $tabla WHERE $campo = ?$filtro");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('s', $identificador);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $fila = $resultado ? $resultado->fetch_assoc() : null;
    $stmt->close();
    if (!$fila) {
        return false;
    }
    $info = password_get_info($fila['clave']);
    $es_hash = $info['algoName'] !== 'unknown';
    $valida = $es_hash ? password_verify($clave, $fila['clave']) : hash_equals($fila['clave'], $clave);
    if (!$valida) {
        return false;
    }
    // Actualización progresiva sin cambiar las claves de las cuentas existentes.
    if (!$es_hash || password_needs_rehash($fila['clave'], PASSWORD_DEFAULT)) {
        $hash = password_hash($clave, PASSWORD_DEFAULT);
        $anterior = $fila['clave'];
        $stmt = $mysqli->prepare("UPDATE $tabla SET clave = ? WHERE $campo = ? AND clave = ?");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('sss', $hash, $identificador, $anterior);
        $actualizada = $stmt->execute();
        $stmt->close();
        if (!$actualizada) {
            return false;
        }
    }
    unset($fila['clave']);
    return $fila;
}
