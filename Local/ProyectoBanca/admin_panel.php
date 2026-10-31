<?php
require 'verificar_admin.php';

$titulo = 'Resumen';
$activo = 'admin_panel.php';
require 'cabecera.php';
?>
<div class="card">
    <p class="eyebrow">Bienvenido</p>
    <h2>Panel de administración</h2>
    <p class="lead">Gestione los usuarios de cajeros y supervise la actividad del banco.</p>
</div>

<div class="access-grid">
    <a class="access-card" href="admin_cajeros.php">
        <span class="chip chip-indigo"><?= icono('users') ?></span>
        <span class="texts">
            <span class="title">Gestión de cajeros</span>
            <span class="desc">Agregue cajeros y bloquee o desbloquee su acceso.</span>
        </span>
    </a>
    <a class="access-card" href="admin_monitor.php">
        <span class="chip chip-green"><?= icono('activity') ?></span>
        <span class="texts">
            <span class="title">Monitor de transferencias</span>
            <span class="desc">Vea cuentas, transacciones y depósitos vs retiros.</span>
        </span>
    </a>
</div>
<?php require 'pie.php'; ?>
