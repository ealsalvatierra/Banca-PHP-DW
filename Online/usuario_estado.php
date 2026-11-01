<?php
require 'verificar_usuario.php';
require_once 'conexion.php';

$no_cuenta = $_SESSION['no_cuenta'];

$stmt = $mysqli->prepare('CALL sp_estado_cuenta(?)');
$stmt->bind_param('s', $no_cuenta);
$stmt->execute();
$resultado = $stmt->get_result();

$titulo = 'Estado de cuenta';
$activo = 'usuario_estado.php';
require 'cabecera.php';
?>
<div class="card">
    <h2>Movimientos de la cuenta <?= htmlspecialchars($no_cuenta) ?></h2>
    <?php if ($resultado && $resultado->num_rows > 0): ?>
    <table>
        <tr>
            <th>Fecha</th>
            <th>Tipo</th>
            <th>Descripción</th>
            <th>Débito</th>
            <th>Crédito</th>
        </tr>
        <?php while ($fila = $resultado->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($fila['fecha']) ?></td>
            <td><?= htmlspecialchars($fila['tipo']) ?></td>
            <td><?= htmlspecialchars($fila['descripcion']) ?></td>
            <td class="amt-db"><?= $fila['tipo_mov'] === 'DEBITO' ? 'Q' . number_format($fila['monto'], 2) : '' ?></td>
            <td class="amt-cr"><?= $fila['tipo_mov'] === 'CREDITO' ? 'Q' . number_format($fila['monto'], 2) : '' ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?>
        <p class="lead">No hay operaciones registradas en la cuenta.</p>
    <?php endif; ?>
</div>
<?php require 'pie.php'; ?>
