<?php
session_start();

// ===== MODO PRUEBA: login desactivado temporalmente para navegar sin iniciar sesión.
// Para reactivar la seguridad: borrar este bloque y descomentar el de abajo.
$_SESSION['rol'] = 'admin';
$_SESSION['usuario'] = 'admin';
$_SESSION['nombre'] = 'Administrador del Sistema';
// ===== FIN MODO PRUEBA

/*
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login_admin.php');
    exit;
}
*/
