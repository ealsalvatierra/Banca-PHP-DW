<?php
require 'verificar_usuario.php';
require_once 'conexion.php';

$no_cuenta = $_SESSION['no_cuenta'];

$stmt = $mysqli->prepare('CALL sp_cuenta_obtener(?)');
$stmt->bind_param('s', $no_cuenta);
$stmt->execute();
$resultado = $stmt->get_result();
$cuenta = $resultado->fetch_assoc();
$stmt->close();
        limpiar_resultados($mysqli);

$saldo = $cuenta ? $cuenta['saldo'] : 0;

$titulo = 'Resumen';
$activo = 'usuario_panel.php';
require 'cabecera.php';
?>
<div class="balance">
    <p class="label">Saldo disponible</p>
    <p class="amount tabular">Q<?= number_format($saldo, 2) ?></p>
    <p class="acct tabular">Cuenta <?= htmlspecialchars($no_cuenta) ?></p>
</div>

<div class="access-grid">
    <a class="access-card" href="usuario_transferencia.php">
        <span class="chip chip-indigo"><?= icono('transfer') ?></span>
        <span class="texts">
            <span class="title">Transferir</span>
            <span class="desc">Envíe dinero a una cuenta de tercero registrada.</span>
        </span>
    </a>
    <a class="access-card" href="usuario_terceros.php">
        <span class="chip chip-blue"><?= icono('users') ?></span>
        <span class="texts">
            <span class="title">Cuentas de terceros</span>
            <span class="desc">Agregue y consulte las cuentas a las que transfiere.</span>
        </span>
    </a>
    <a class="access-card" href="usuario_estado.php">
        <span class="chip chip-green"><?= icono('file') ?></span>
        <span class="texts">
            <span class="title">Estado de cuenta</span>
            <span class="desc">Revise todos sus débitos y créditos.</span>
        </span>
    </a>
</div>
<?php require 'pie.php'; ?>
