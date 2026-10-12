<?php
session_start();

// ===== MODO PRUEBA: login desactivado temporalmente para navegar sin iniciar sesión.
// Para reactivar la seguridad: borrar este bloque y descomentar el de abajo.
$_SESSION['rol'] = 'usuario';
$_SESSION['usuario'] = 'prueba@mail.com';
$_SESSION['nombre'] = 'prueba@mail.com';
$_SESSION['no_cuenta'] = '1001';
// ===== FIN MODO PRUEBA

/*
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'usuario') {
    header('Location: login_usuario.php');
    exit;
}
*/
