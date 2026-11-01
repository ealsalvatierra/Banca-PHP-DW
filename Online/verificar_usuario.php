<?php
require_once 'seguridad.php';
exigir_rol('usuario', 'login_usuario.php');
require_once 'conexion.php';
$stmt = $mysqli->prepare('SELECT no_cuenta FROM usuarios WHERE correo = ?');
$stmt->bind_param('s', $_SESSION['usuario']);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado ? $resultado->fetch_assoc() : null;
$stmt->close();
if (!$usuario) {
    $_SESSION = [];
    session_regenerate_id(true);
    header('Location: login_usuario.php');
    exit;
}
$_SESSION['no_cuenta'] = $usuario['no_cuenta'];
