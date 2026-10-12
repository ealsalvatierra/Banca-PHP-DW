<?php
require 'verificar_usuario.php';
require 'conexion.php';

$correo = $_SESSION['usuario'];
$origen = $_SESSION['no_cuenta'];

$mensaje = '';
$tipoMensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_tercero = trim($_POST['id_tercero'] ?? '');
    $monto = trim($_POST['monto'] ?? '');

    $errores = [];

    if ($id_tercero === '' || !ctype_digit($id_tercero)) {
        $errores[] = 'Debe seleccionar una cuenta de tercero.';
    }
    if ($monto === '' || !is_numeric($monto) || $monto <= 0) {
        $errores[] = 'La cantidad a transferir debe ser un número mayor a cero.';
    }

    $tercero = null;
    if (empty($errores)) {
        $id_num = (int)$id_tercero;
        $stmt = $mysqli->prepare('CALL sp_tercero_obtener(?, ?)');
        $stmt->bind_param('is', $id_num, $correo);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $tercero = $resultado->fetch_assoc();
        $stmt->close();

        if (!$tercero) {
            $errores[] = 'La cuenta de tercero seleccionada no es válida.';
        }
    }

    if (empty($errores) && $monto > $tercero['monto_maximo']) {
        $errores[] = 'El monto supera el máximo permitido para esta cuenta (Q' . number_format($tercero['monto_maximo'], 2) . ').';
    }

    if (empty($errores)) {
        $stmt = $mysqli->prepare('CALL sp_transferencias_dia(?, ?)');
        $stmt->bind_param('ss', $origen, $tercero['no_cuenta']);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $fila = $resultado->fetch_assoc();
        $stmt->close();

        if ($fila['total'] >= $tercero['max_transacciones']) {
            $errores[] = 'Ya alcanzó la cantidad máxima de transferencias diarias para esta cuenta.';
        }
    }

    if (empty($errores)) {
        $stmt = $mysqli->prepare('CALL sp_cuenta_obtener(?)');
        $stmt->bind_param('s', $origen);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $cuenta = $resultado->fetch_assoc();
        $stmt->close();

        if (!$cuenta || $cuenta['saldo'] < $monto) {
            $errores[] = 'Saldo insuficiente para realizar la transferencia.';
        }
    }

    if (empty($errores)) {
        $monto_num = (float)$monto;
        $destino = $tercero['no_cuenta'];
        $stmt = $mysqli->prepare('CALL sp_transferir(?, ?, ?)');
        $stmt->bind_param('ssd', $origen, $destino, $monto_num);

        if ($stmt->execute()) {
            $mensaje = 'Transferencia realizada correctamente.';
            $tipoMensaje = 'mensaje-exito';
        } else {
            $mensaje = 'Error al realizar la transferencia: ' . $stmt->error;
            $tipoMensaje = 'mensaje-error';
        }
        $stmt->close();
    } else {
        $mensaje = implode('<br>', $errores);
        $tipoMensaje = 'mensaje-error';
    }
}

$stmt = $mysqli->prepare('CALL sp_terceros_listar(?)');
$stmt->bind_param('s', $correo);
$stmt->execute();
$resultado = $stmt->get_result();

$titulo = 'Transferencia';
$activo = 'usuario_transferencia.php';
require 'cabecera.php';
?>
<?php if ($mensaje): ?>
    <p class="<?= $tipoMensaje ?>"><?= $mensaje ?></p>
<?php endif; ?>

<div class="card">
    <h2>Transferir a cuenta de tercero</h2>
    <?php if ($resultado && $resultado->num_rows > 0): ?>
    <form action="usuario_transferencia.php" method="POST">
        <div class="field">
            <label for="id_tercero">Cuenta de tercero</label>
            <select id="id_tercero" name="id_tercero" required>
                <option value="">Seleccione una cuenta</option>
                <?php while ($fila = $resultado->fetch_assoc()): ?>
                <option value="<?= $fila['id'] ?>"><?= htmlspecialchars($fila['alias']) ?> (<?= htmlspecialchars($fila['no_cuenta']) ?>)</option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="field">
            <label for="monto">Cantidad a transferir</label>
            <input type="number" id="monto" name="monto" step="0.01" min="0" required>
        </div>

        <input type="submit" value="Transferir">
    </form>
    <?php else: ?>
        <p class="lead">No tiene cuentas de terceros registradas. Agregue una antes de transferir.</p>
    <?php endif; ?>
</div>
<?php require 'pie.php'; ?>
