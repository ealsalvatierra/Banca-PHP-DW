<?php
require_once 'seguridad.php';
require_once 'procedimientos.php';
mysqli_report(MYSQLI_REPORT_OFF);

// Las credenciales del hosting van en config.php (no se versiona). Ver config.example.php.
$config = is_file(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : [];

$host     = $config['host']     ?? (getenv('BANCA_DB_HOST') ?: '127.0.0.1');
$user     = $config['user']     ?? (getenv('BANCA_DB_USER') ?: 'root');
$password = $config['password'] ?? (getenv('BANCA_DB_PASSWORD') ?: '');
$database = $config['database'] ?? (getenv('BANCA_DB_NAME') ?: 'banca_db');
$port     = (int)($config['port'] ?? (getenv('BANCA_DB_PORT') ?: 3306));

$mysqli = new BancaDB($host, $user, $password, $database, $port);

if ($mysqli->connect_error || !$mysqli->set_charset('utf8mb4')) {
    http_response_code(503);
    exit('El servicio no está disponible temporalmente.');
}

// ============================================================================
// REGISTRADOR DE TRÁFICO EN TIEMPO REAL
// Se ejecuta al terminar la petición para guardar el código HTTP final
// (200, 302 de redirección, 403, 500, etc.).
// ============================================================================
register_shutdown_function(function () use ($mysqli) {
    $pagina = $_SERVER['REQUEST_URI'] ?? '/';
    if (strpos($pagina, 'logs.php') !== false) {
        return;
    }
    $ip     = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $error  = error_get_last();
    $codigo = ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR], true))
        ? 500 : (http_response_code() ?: 200);
    $stmt = $mysqli->prepare('INSERT INTO server_logs (ip_origen, pagina_solicitada, metodo, codigo_respuesta) VALUES (?, ?, ?, ?)');
    if ($stmt) {
        $stmt->bind_param('sssi', $ip, $pagina, $metodo, $codigo);
        $stmt->execute();
        $stmt->close();
    }
});
