<?php
require 'verificar_admin.php';
require 'conexion.php';

$tipos = [
    'cuentas'       => ['Cuentas creadas hoy', 'sp_detalle_cuentas_dia'],
    'usuarios'      => ['Usuarios cliente registrados hoy', 'sp_detalle_usuarios_dia'],
    'transacciones' => ['Todas las transacciones', 'sp_detalle_transacciones'],
    'depositos'     => ['Depósitos', 'sp_detalle_depositos'],
    'retiros'       => ['Retiros', 'sp_detalle_retiros'],
];

$tipo = $_GET['tipo'] ?? '';
if (!isset($tipos[$tipo])) {
    header('Location: admin_monitor.php');
    exit;
}

$resultado = $mysqli->query('CALL ' . $tipos[$tipo][1] . '()');
$campos = $resultado->fetch_fields();

$etiquetas = [
    'no_cuenta' => 'No. cuenta',
    'nombre' => 'Nombre',
    'dpi' => 'DPI',
    'saldo' => 'Saldo',
    'fecha_creacion' => 'Fecha de creación',
    'correo' => 'Correo',
    'fecha_registro' => 'Fecha de registro',
    'fecha' => 'Fecha',
    'tipo' => 'Tipo',
    'tipo_mov' => 'Movimiento',
    'monto' => 'Monto',
    'descripcion' => 'Descripción',
];

$titulo = $tipos[$tipo][0];
$activo = 'admin_monitor.php';
require 'cabecera.php';
?>
<p class="eyebrow"><a href="admin_monitor.php">&lsaquo; Volver al monitor</a></p>

<div class="card">
    <?php if ($resultado && $resultado->num_rows > 0): ?>
    <table>
        <tr>
            <?php foreach ($campos as $campo): ?>
                <th><?= $etiquetas[$campo->name] ?? htmlspecialchars($campo->name) ?></th>
            <?php endforeach; ?>
        </tr>
        <?php while ($fila = $resultado->fetch_assoc()): ?>
        <tr>
            <?php foreach ($fila as $nombre => $valor): ?>
                <td>
                    <?php if ($nombre === 'monto' || $nombre === 'saldo'): ?>
                        Q<?= number_format($valor, 2) ?>
                    <?php else: ?>
                        <?= htmlspecialchars($valor) ?>
                    <?php endif; ?>
                </td>
            <?php endforeach; ?>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?>
        <p class="lead">No hay registros para mostrar.</p>
    <?php endif; ?>
</div>
<?php require 'pie.php'; ?>
