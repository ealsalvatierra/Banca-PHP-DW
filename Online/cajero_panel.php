<?php
require 'verificar_cajero.php';

$titulo = 'Resumen';
$activo = 'cajero_panel.php';
require 'cabecera.php';
?>
<div class="card">
    <p class="eyebrow">Bienvenido</p>
    <h2>Panel de cajero</h2>
    <p class="lead">Abra cuentas monetarias y registre depósitos y retiros de los clientes.</p>
</div>

<div class="access-grid">
    <a class="access-card" href="cajero_crear_cuenta.php">
        <span class="chip chip-indigo"><?= icono('wallet') ?></span>
        <span class="texts">
            <span class="title">Crear cuenta</span>
            <span class="desc">Registre una nueva cuenta monetaria con su monto inicial.</span>
        </span>
    </a>
    <a class="access-card" href="cajero_deposito.php">
        <span class="chip chip-green"><?= icono('down') ?></span>
        <span class="texts">
            <span class="title">Depósito</span>
            <span class="desc">Acredite dinero a una cuenta existente.</span>
        </span>
    </a>
    <a class="access-card" href="cajero_retiro.php">
        <span class="chip chip-amber"><?= icono('up') ?></span>
        <span class="texts">
            <span class="title">Retiro</span>
            <span class="desc">Debite dinero de una cuenta existente.</span>
        </span>
    </a>
</div>
<?php require 'pie.php'; ?>
