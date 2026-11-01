<?php
require_once 'seguridad.php';
require_once 'conexion.php';
$mensaje = '';
$usuario = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $usuario = entrada_texto('usuario');
    $clave = entrada_texto('clave');
    $fila = autenticar($mysqli, 'admin', $usuario, $clave);
    if ($fila) {
        session_regenerate_id(true);
        $_SESSION = [];
        $_SESSION['rol'] = 'admin';
        $_SESSION['usuario'] = $usuario;
        $_SESSION['nombre'] = $fila['nombre_completo'];
        $_SESSION['autenticada'] = true;
        $_SESSION['ultima_actividad'] = time();
        header('Location: admin_panel.php');
        exit;
    }
    $mensaje = 'Usuario o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banco UMG - Acceso administrador</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
<div class="auth">
    <div class="auth-card">
        <a class="brand" href="index.php">
            <span class="brand-mark">U</span>
            <span class="brand-name">Banco UMG</span>
        </a>
        <h1>Acceso de administrador</h1>
        <p class="sub">Ingrese sus credenciales para continuar.</p>

        <?php if ($mensaje): ?>
            <p class="mensaje-error"><?= $mensaje ?></p>
        <?php endif; ?>

        <form action="login_admin.php" method="POST">
        <?= csrf_campo() ?>
            <div class="field">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario" maxlength="50" required value="<?= htmlspecialchars($usuario) ?>">
            </div>
            <div class="field">
                <label for="clave">Contraseña</label>
                <input type="password" id="clave" name="clave" maxlength="72" required>
            </div>
            <input type="submit" class="full" value="Ingresar">
        </form>

        <p class="alt"><a href="index.php">Volver al inicio</a></p>
    </div>
</div>
</body>
</html>
