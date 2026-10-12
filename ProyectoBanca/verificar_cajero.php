<?php
require_once 'seguridad.php';
exigir_rol('cajero', 'login_cajero.php');
require_once 'conexion.php';
$stmt = $mysqli->prepare('SELECT activo FROM cajeros WHERE usuario = ?');
$stmt->bind_param('s', $_SESSION['usuario']);
$stmt->execute();
$resultado = $stmt->get_result();
$cajero = $resultado ? $resultado->fetch_assoc() : null;
$stmt->close();
if (!$cajero || (int)$cajero['activo'] !== 1) {
    $_SESSION = [];
    session_regenerate_id(true);
    header('Location: login_cajero.php');
    exit;
}
