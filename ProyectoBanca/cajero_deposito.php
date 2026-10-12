<?php
require 'verificar_cajero.php';
require 'conexion.php';

$mensaje = '';
$tipoMensaje = '';
$no_cuenta = $monto = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_cuenta = trim($_POST['no_cuenta'] ?? '');
    $monto = trim($_POST['monto'] ?? '');

    $errores = [];

    if ($no_cuenta === '') {
        $errores[] = 'El número de cuenta es obligatorio.';
    }
    if ($monto === '' || !is_numeric($monto) || $monto <= 0) {
        $errores[] = 'La cantidad a depositar debe ser un número mayor a cero.';
    }

    if (empty($errores)) {
        $stmt = $mysqli->prepare('CALL sp_cuenta_existe(?)');
        $stmt->bind_param('s', $no_cuenta);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $fila = $resultado->fetch_assoc();
        $stmt->close();

        if ($fila['total'] == 0) {
            $errores[] = 'La cuenta no existe.';
        }
    }

    if (empty($errores)) {
        $monto_num = (float)$monto;
        $stmt = $mysqli->prepare('CALL sp_deposito(?, ?)');
        $stmt->bind_param('sd', $no_cuenta, $monto_num);

        if ($stmt->execute()) {
            $mensaje = 'Depósito realizado correctamente.';
            $tipoMensaje = 'mensaje-exito';
            $no_cuenta = $monto = '';
        } else {
            $mensaje = 'Error al realizar el depósito: ' . $stmt->error;
            $tipoMensaje = 'mensaje-error';
        }
        $stmt->close();
    } else {
        $mensaje = implode('<br>', $errores);
        $tipoMensaje = 'mensaje-error';
    }
}

$titulo = 'Depósito';
$activo = 'cajero_deposito.php';
require 'cabecera.php';
?>
<?php if ($mensaje): ?>
    <p class="<?= $tipoMensaje ?>"><?= $mensaje ?></p>
<?php endif; ?>

<div class="card">
    <h2>Depósito monetario</h2>
    <form action="cajero_deposito.php" method="POST">
        <div class="field">
            <label for="no_cuenta">No. de cuenta</label>
            <input type="text" id="no_cuenta" name="no_cuenta" maxlength="20" required value="<?= htmlspecialchars($no_cuenta) ?>">
        </div>
        <div class="field">
            <label for="monto">Cantidad a depositar</label>
            <input type="number" id="monto" name="monto" step="0.01" min="0" required value="<?= htmlspecialchars($monto) ?>">
        </div>

        <input type="submit" value="Depositar">
    </form>
</div>
<?php require 'pie.php'; ?>
