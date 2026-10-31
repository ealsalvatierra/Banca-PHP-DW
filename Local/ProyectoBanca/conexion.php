<?php
require_once 'seguridad.php';
mysqli_report(MYSQLI_REPORT_OFF);
$host = getenv('BANCA_DB_HOST') ?: '127.0.0.1';
$user = getenv('BANCA_DB_USER') ?: 'root';
$password = getenv('BANCA_DB_PASSWORD') ?: '';
$database = getenv('BANCA_DB_NAME') ?: 'banca_db';
$port = (int)(getenv('BANCA_DB_PORT') ?: 3306);
$mysqli = new mysqli($host, $user, $password, $database, $port);
if ($mysqli->connect_error || !$mysqli->set_charset('utf8mb4')) {
    http_response_code(503);
    exit('El servicio no está disponible temporalmente.');
}
