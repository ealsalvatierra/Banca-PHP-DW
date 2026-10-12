<?php
require 'verificar_cajero.php';
require 'conexion.php';

$mensaje = '';
$tipoMensaje = '';
$nombre = $no_cuenta = $dpi = $monto = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $no_cuenta = trim($_POST['no_cuenta'] ?? '');
    $dpi = trim($_POST['dpi'] ?? '');
    $monto = trim($_POST['monto'] ?? '');

    $errores = [];

    if ($nombre === '' || strlen($nombre) > 60) {
        $errores[] = 'El nombre de la cuenta es obligatorio y debe tener máximo 60 caracteres.';
    }
    if ($no_cuenta === '' || strlen($no_cuenta) > 20) {
        $errores[] = 'El número de cuenta es obligatorio y debe tener máximo 20 caracteres.';
    }
    if ($dpi === '' || !ctype_digit($dpi) || strlen($dpi) != 13) {
        $errores[] = 'El DPI es obligatorio y debe tener 13 dígitos.';
    }
    if ($monto === '' || !is_numeric($monto) || $monto < 0) {
        $errores[] = 'El monto inicial es obligatorio y debe ser un número mayor o igual a cero.';
    }

    if (empty($errores)) {
        $stmt = $mysqli->prepare('CALL sp_cuenta_existe(?)');
        $stmt->bind_param('s', $no_cuenta);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $fila = $resultado->fetch_assoc();
        $stmt->close();

        if ($fila['total'] > 0) {
            $errores[] = 'Ya existe una cuenta con ese número.';
        }
    }

    if (empty($errores)) {
        $monto_num = (float)$monto;
        $stmt = $mysqli->prepare('CALL sp_cuenta_crear(?, ?, ?, ?)');
        $stmt->bind_param('sssd', $no_cuenta, $nombre, $dpi, $monto_num);

        if ($stmt->execute()) {
            $mensaje = 'Cuenta creada correctamente.';
            $tipoMensaje = 'mensaje-exito';
            $nombre = $no_cuenta = $dpi = $monto = '';
        } else {
            $mensaje = 'Error al crear la cuenta: ' . $stmt->error;
            $tipoMensaje = 'mensaje-error';
        }
        $stmt->close();
    } else {
        $mensaje = implode('<br>', $errores);
        $tipoMensaje = 'mensaje-error';
    }
}

$titulo = 'Crear cuenta';
$activo = 'cajero_crear_cuenta.php';
require 'cabecera.php';
?>
<?php if ($mensaje): ?>
    <p class="<?= $tipoMensaje ?>"><?= $mensaje ?></p>
<?php endif; ?>

<div class="card">
    <h2>Nueva cuenta monetaria</h2>
    <form action="cajero_crear_cuenta.php" method="POST">
        <div class="field">
            <label for="nombre">Nombre de la cuenta</label>
            <input type="text" id="nombre" name="nombre" maxlength="60" required value="<?= htmlspecialchars($nombre) ?>">
        </div>
        <div class="field">
            <label for="no_cuenta">No. de cuenta</label>
            <input type="text" id="no_cuenta" name="no_cuenta" maxlength="20" required value="<?= htmlspecialchars($no_cuenta) ?>">
        </div>
        <div class="field">
            <label for="dpi">Identificación (DPI)</label>
            <input type="text" id="dpi" name="dpi" maxlength="13" pattern="[0-9]{13}" title="13 dígitos" required value="<?= htmlspecialchars($dpi) ?>">
        </div>
        <div class="field">
            <label for="monto">Monto inicial</label>
            <input type="number" id="monto" name="monto" step="0.01" min="0" required value="<?= htmlspecialchars($monto) ?>">
        </div>

        <input type="submit" value="Crear cuenta">
    </form>
</div>
<?php require 'pie.php'; ?>
