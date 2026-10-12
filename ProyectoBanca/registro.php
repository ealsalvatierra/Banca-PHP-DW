<?php
require 'conexion.php';

$mensaje = '';
$tipoMensaje = '';
$no_cuenta = $correo = $dpi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_cuenta = trim($_POST['no_cuenta'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $dpi = trim($_POST['dpi'] ?? '');
    $clave = trim($_POST['clave'] ?? '');
    $confirmar = trim($_POST['confirmar'] ?? '');

    $errores = [];

    if ($no_cuenta === '' || strlen($no_cuenta) > 20) {
        $errores[] = 'El número de cuenta es obligatorio y debe tener máximo 20 caracteres.';
    }
    if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 50) {
        $errores[] = 'El correo electrónico es obligatorio y debe ser válido.';
    }
    if ($dpi === '' || !ctype_digit($dpi) || strlen($dpi) != 13) {
        $errores[] = 'El DPI es obligatorio y debe tener 13 dígitos.';
    }
    if ($clave === '' || strlen($clave) > 20) {
        $errores[] = 'La contraseña es obligatoria y debe tener máximo 20 caracteres.';
    }
    if ($clave !== $confirmar) {
        $errores[] = 'La contraseña y su confirmación no coinciden.';
    }

    if (empty($errores)) {
        $stmt = $mysqli->prepare('CALL sp_cuenta_obtener(?)');
        $stmt->bind_param('s', $no_cuenta);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $cuenta = $resultado->fetch_assoc();
        $stmt->close();

        if (!$cuenta) {
            $errores[] = 'La cuenta bancaria no existe. Debe crearla primero en el banco.';
        } elseif ($cuenta['dpi'] !== $dpi) {
            $errores[] = 'El DPI no coincide con el registrado en la cuenta bancaria.';
        } else {
            $stmt = $mysqli->prepare('CALL sp_usuario_existe_cuenta(?)');
            $stmt->bind_param('s', $no_cuenta);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $fila = $resultado->fetch_assoc();
            $stmt->close();

            if ($fila['total'] > 0) {
                $errores[] = 'Ya existe un usuario registrado para esa cuenta bancaria.';
            }
        }
    }

    if (empty($errores)) {
        $stmt = $mysqli->prepare('CALL sp_usuario_existe_correo(?)');
        $stmt->bind_param('s', $correo);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $fila = $resultado->fetch_assoc();
        $stmt->close();

        if ($fila['total'] > 0) {
            $errores[] = 'El correo electrónico ya está registrado.';
        }
    }

    if (empty($errores)) {
        $stmt = $mysqli->prepare('CALL sp_usuario_insertar(?, ?, ?, ?)');
        $stmt->bind_param('ssss', $correo, $clave, $no_cuenta, $dpi);

        if ($stmt->execute()) {
            $mensaje = 'Usuario registrado correctamente. Ya puede iniciar sesión.';
            $tipoMensaje = 'mensaje-exito';
            $no_cuenta = $correo = $dpi = '';
        } else {
            $mensaje = 'Error al registrar el usuario: ' . $stmt->error;
            $tipoMensaje = 'mensaje-error';
        }
        $stmt->close();
    } else {
        $mensaje = implode('<br>', $errores);
        $tipoMensaje = 'mensaje-error';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banco UMG - Registro de usuario</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
<div class="auth">
    <div class="auth-card">
        <a class="brand" href="index.php">
            <span class="brand-mark">U</span>
            <span class="brand-name">Banco UMG</span>
        </a>
        <h1>Crear usuario</h1>
        <p class="sub">Registre su acceso a partir de una cuenta ya abierta en el banco.</p>

        <?php if ($mensaje): ?>
            <p class="<?= $tipoMensaje ?>"><?= $mensaje ?></p>
        <?php endif; ?>

        <form action="registro.php" method="POST">
            <div class="field">
                <label for="no_cuenta">No. de cuenta bancaria</label>
                <input type="text" id="no_cuenta" name="no_cuenta" maxlength="20" required value="<?= htmlspecialchars($no_cuenta) ?>">
            </div>
            <div class="field">
                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo" maxlength="50" required value="<?= htmlspecialchars($correo) ?>">
            </div>
            <div class="field">
                <label for="dpi">DPI</label>
                <input type="text" id="dpi" name="dpi" maxlength="13" pattern="[0-9]{13}" title="13 dígitos" required value="<?= htmlspecialchars($dpi) ?>">
            </div>
            <div class="field">
                <label for="clave">Contraseña</label>
                <input type="password" id="clave" name="clave" maxlength="20" required>
            </div>
            <div class="field">
                <label for="confirmar">Confirmar contraseña</label>
                <input type="password" id="confirmar" name="confirmar" maxlength="20" required>
            </div>
            <input type="submit" class="full" value="Registrarme">
        </form>

        <p class="alt">¿Ya tiene usuario? <a href="login_usuario.php">Inicie sesión</a></p>
    </div>
</div>
</body>
</html>
