<?php
require_once 'seguridad.php';
require_once 'conexion.php';
$mensaje = '';
$correo = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $correo = entrada_texto('correo');
    $clave = entrada_texto('clave');
    $fila = autenticar($mysqli, 'usuario', $correo, $clave);
    if ($fila) {
        session_regenerate_id(true);
        $_SESSION = [];
        $_SESSION['rol'] = 'usuario';
        $_SESSION['usuario'] = $correo;
        $_SESSION['nombre'] = $correo;
        $_SESSION['no_cuenta'] = $fila['no_cuenta'];
        $_SESSION['autenticada'] = true;
        $_SESSION['ultima_actividad'] = time();
        header('Location: usuario_panel.php');
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
    <title>Banco UMG - Acceso usuario</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
<div class="auth">
    <div class="auth-card">
        <a class="brand" href="index.php">
            <span class="brand-mark">U</span>
            <span class="brand-name">Banco UMG</span>
        </a>
        <h1>Banca en línea</h1>
        <p class="sub">Ingrese con su correo y contraseña.</p>

        <?php if ($mensaje): ?>
            <p class="mensaje-error"><?= $mensaje ?></p>
        <?php endif; ?>

        <form action="login_usuario.php" method="POST">
        <?= csrf_campo() ?>
            <div class="field">
                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo" maxlength="50" required value="<?= htmlspecialchars($correo) ?>">
            </div>
            <div class="field">
                <label for="clave">Contraseña</label>
                <input type="password" id="clave" name="clave" maxlength="72" required>
            </div>
            <input type="submit" class="full" value="Ingresar">
        </form>

        <p class="alt">¿No tiene usuario? <a href="registro.php">Regístrese</a></p>
    </div>
</div>
</body>
</html>
