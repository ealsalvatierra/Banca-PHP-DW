<?php
session_start();

// ===== MODO PRUEBA: login desactivado temporalmente para navegar sin iniciar sesión.
// Para reactivar la seguridad: borrar este bloque y descomentar el de abajo.
$_SESSION['rol'] = 'cajero';
$_SESSION['usuario'] = 'cajero_prueba';
$_SESSION['nombre'] = 'Cajero de Prueba';
// ===== FIN MODO PRUEBA

/*
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'cajero') {
    header('Location: login_cajero.php');
    exit;
}
*/
