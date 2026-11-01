<?php
require 'verificar_usuario.php';
require_once 'conexion.php';

$correo = $_SESSION['usuario'];

$mensaje = '';
$tipoMensaje = '';
$no_cuenta = $monto_maximo = $max_transacciones = $alias = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $no_cuenta = entrada_texto('no_cuenta');
    $monto_maximo = entrada_texto('monto_maximo');
    $max_transacciones = entrada_texto('max_transacciones');
    $alias = entrada_texto('alias');

    $errores = [];

    if ($no_cuenta === '' || strlen($no_cuenta) > 20) {
        $errores[] = 'El número de cuenta es obligatorio y debe tener máximo 20 caracteres.';
    }
    if (!monto_valido($monto_maximo)) {
        $errores[] = 'El monto máximo debe ser un número mayor a cero.';
    }
    if ($max_transacciones === '' || !ctype_digit($max_transacciones) || ($max_transacciones <= 0 || $max_transacciones > 2147483647)) {
        $errores[] = 'La cantidad máxima de transacciones diarias debe ser un número entero mayor a cero.';
    }
    if ($alias === '' || strlen($alias) > 40) {
        $errores[] = 'El alias es obligatorio y debe tener máximo 40 caracteres.';
    }

    if (empty($errores)) {
        $stmt = $mysqli->prepare('CALL sp_cuenta_existe(?)');
        $stmt->bind_param('s', $no_cuenta);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $fila = $resultado->fetch_assoc();
        $stmt->close();
        limpiar_resultados($mysqli);

        if ($no_cuenta === $_SESSION['no_cuenta']) {
            $errores[] = 'No puede agregar su propia cuenta como tercero.';
        } elseif ($fila['total'] == 0) {
            $errores[] = 'La cuenta bancaria no existe.';
        }
    }

    if (empty($errores)) {
        $monto_num = $monto_maximo;
        $max_num = (int)$max_transacciones;
        $stmt = $mysqli->prepare('CALL sp_tercero_insertar(?, ?, ?, ?, ?)');
        $stmt->bind_param('sssis', $correo, $no_cuenta, $monto_num, $max_num, $alias);

        if ($stmt->execute()) {
            $mensaje = 'Cuenta de tercero agregada correctamente.';
            $tipoMensaje = 'mensaje-exito';
            $no_cuenta = $monto_maximo = $max_transacciones = $alias = '';
        } else {
            $mensaje = 'Error al agregar la cuenta de tercero: ' . error_operacion($stmt);
            $tipoMensaje = 'mensaje-error';
        }
        $stmt->close();
        limpiar_resultados($mysqli);
    } else {
        $mensaje = implode('<br>', $errores);
        $tipoMensaje = 'mensaje-error';
    }
}

$stmt = $mysqli->prepare('CALL sp_terceros_listar(?)');
$stmt->bind_param('s', $correo);
$stmt->execute();
$resultado = $stmt->get_result();

$titulo = 'Cuentas de terceros';
$activo = 'usuario_terceros.php';
require 'cabecera.php';
?>
<?php if ($mensaje): ?>
    <p class="<?= $tipoMensaje ?>"><?= $mensaje ?></p>
<?php endif; ?>

<div class="card">
    <h2>Mis cuentas de terceros</h2>
    <?php if ($resultado && $resultado->num_rows > 0): ?>
    <table>
        <tr>
            <th>Alias</th>
            <th>No. de cuenta</th>
            <th>Monto máximo</th>
            <th>Máx. transacciones diarias</th>
        </tr>
        <?php while ($fila = $resultado->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($fila['alias']) ?></td>
            <td><?= htmlspecialchars($fila['no_cuenta']) ?></td>
            <td>Q<?= number_format($fila['monto_maximo'], 2) ?></td>
            <td><?= $fila['max_transacciones'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?>
        <p class="lead">No tiene cuentas de terceros registradas.</p>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Agregar cuenta de tercero</h2>
    <form action="usuario_terceros.php" method="POST">
        <?= csrf_campo() ?>
        <div class="field">
            <label for="no_cuenta">No. de cuenta</label>
            <input type="text" id="no_cuenta" name="no_cuenta" maxlength="20" required value="<?= htmlspecialchars($no_cuenta) ?>">
        </div>
        <div class="field">
            <label for="monto_maximo">Monto máximo</label>
            <input type="number" id="monto_maximo" name="monto_maximo" step="0.01" min="0" required value="<?= htmlspecialchars($monto_maximo) ?>">
        </div>
        <div class="field">
            <label for="max_transacciones">Máx. transacciones diarias</label>
            <input type="number" id="max_transacciones" name="max_transacciones" min="1" step="1" required value="<?= htmlspecialchars($max_transacciones) ?>">
        </div>
        <div class="field">
            <label for="alias">Alias</label>
            <input type="text" id="alias" name="alias" maxlength="40" required value="<?= htmlspecialchars($alias) ?>">
        </div>

        <input type="submit" value="Agregar cuenta">
    </form>
</div>
<?php require 'pie.php'; ?>
