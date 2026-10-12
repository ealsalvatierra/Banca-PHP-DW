<?php
require 'verificar_admin.php';
require_once 'conexion.php';

$resultado = $mysqli->query('CALL sp_monitor()');
$datos = $resultado->fetch_assoc();

$monto_depositos = (float)$datos['monto_depositos'];
$monto_retiros = (float)$datos['monto_retiros'];

$maximo = max($monto_depositos, $monto_retiros);
$ancho_depositos = $maximo > 0 ? ($monto_depositos / $maximo) * 100 : 0;
$ancho_retiros = $maximo > 0 ? ($monto_retiros / $maximo) * 100 : 0;

$titulo = 'Monitor de transferencias';
$activo = 'admin_monitor.php';
require 'cabecera.php';
?>
<div class="card">
    <h2>Resumen</h2>
    <div class="stats">
        <a class="stat" href="admin_detalle.php?tipo=cuentas">
            <p class="k">Cuentas creadas hoy</p>
            <p class="v tabular"><?= $datos['cuentas_dia'] ?></p>
            <span class="more link-arrow">Ver</span>
        </a>
        <a class="stat" href="admin_detalle.php?tipo=usuarios">
            <p class="k">Usuarios cliente hoy</p>
            <p class="v tabular"><?= $datos['usuarios_dia'] ?></p>
            <span class="more link-arrow">Ver</span>
        </a>
        <a class="stat" href="admin_detalle.php?tipo=transacciones">
            <p class="k">Transacciones</p>
            <p class="v tabular"><?= $datos['transacciones'] ?></p>
            <span class="more link-arrow">Ver</span>
        </a>
        <a class="stat" href="admin_detalle.php?tipo=depositos">
            <p class="k">Depósitos</p>
            <p class="v tabular"><?= $datos['depositos'] ?></p>
            <span class="more link-arrow">Ver</span>
        </a>
        <a class="stat" href="admin_detalle.php?tipo=retiros">
            <p class="k">Retiros</p>
            <p class="v tabular"><?= $datos['retiros'] ?></p>
            <span class="more link-arrow">Ver</span>
        </a>
    </div>
</div>

<div class="card">
    <h2>Depósitos vs retiros</h2>
    <div class="barra-row">
        <span class="cap">Depósitos</span>
        <span class="barra-track">
            <span class="barra barra-deposito" style="width: <?= $ancho_depositos ?>%;">Q<?= number_format($monto_depositos, 2) ?></span>
        </span>
    </div>
    <div class="barra-row">
        <span class="cap">Retiros</span>
        <span class="barra-track">
            <span class="barra barra-retiro" style="width: <?= $ancho_retiros ?>%;">Q<?= number_format($monto_retiros, 2) ?></span>
        </span>
    </div>
</div>
<?php require 'pie.php'; ?>
