<?php
require_once 'seguridad.php';
mysqli_report(MYSQLI_REPORT_OFF);

$host     = getenv('BANCA_DB_HOST')     ?: 'sql200.infinityfree.com';
$user     = getenv('BANCA_DB_USER')     ?: 'if0_43265572';
$password = getenv('BANCA_DB_PASSWORD') ?: 'TuContraseñaInfinity';
$database = getenv('BANCA_DB_NAME')     ?: 'if0_43265572_bancadw';
$port     = (int)(getenv('BANCA_DB_PORT') ?: 3306);

$mysqli = new mysqli($host, $user, $password, $database, $port);

if ($mysqli->connect_error || !$mysqli->set_charset('utf8mb4')) {
    http_response_code(503);
    exit('El servicio no está disponible temporalmente.');
}

// ============================================================================
// REGISTRADOR AUTOMÁTICO DE TRÁFICO EN TIEMPO REAL
// ============================================================================
$ip     = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$pagina = $_SERVER['REQUEST_URI'] ?? '/';
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$codigo = http_response_code() ?: 200;

// Registrar solo si no es la propia página de logs
if (strpos($pagina, 'logs.php') === false) {
    $stmtLog = $mysqli->prepare("INSERT INTO server_logs (ip_origen, pagina_solicitada, metodo, codigo_respuesta) VALUES (?, ?, ?, ?)");
    if ($stmtLog) {
        $stmtLog->bind_param("sssi", $ip, $pagina, $metodo, $codigo);
        $stmtLog->execute();
        $stmtLog->close();
    }
}