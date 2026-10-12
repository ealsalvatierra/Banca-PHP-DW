<?php
// Shell de la aplicación (barra superior).
// Cada página define antes de incluir este archivo: $titulo y $activo.

function icono($nombre) {
    $svg = [
        'grid'     => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'users'    => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'activity' => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
        'wallet'   => '<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/>',
        'down'     => '<circle cx="12" cy="12" r="9"/><path d="M12 8v8"/><path d="m8 12 4 4 4-4"/>',
        'up'       => '<circle cx="12" cy="12" r="9"/><path d="M12 16V8"/><path d="m8 12 4-4 4 4"/>',
        'transfer' => '<path d="m17 2 4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="m7 22-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>',
        'file'     => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h8"/>',
    ];
    $p = $svg[$nombre] ?? '';
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">' . $p . '</svg>';
}

$menus = [
    'admin' => [
        'rol' => 'Administrador',
        'items' => [
            ['admin_panel.php', 'Resumen'],
            ['admin_cajeros.php', 'Cajeros'],
            ['admin_monitor.php', 'Monitor'],
        ],
    ],
    'cajero' => [
        'rol' => 'Cajero',
        'items' => [
            ['cajero_panel.php', 'Resumen'],
            ['cajero_crear_cuenta.php', 'Crear cuenta'],
            ['cajero_deposito.php', 'Depósito'],
            ['cajero_retiro.php', 'Retiro'],
        ],
    ],
    'usuario' => [
        'rol' => 'Cliente',
        'items' => [
            ['usuario_panel.php', 'Resumen'],
            ['usuario_terceros.php', 'Cuentas de terceros'],
            ['usuario_transferencia.php', 'Transferencia'],
            ['usuario_estado.php', 'Estado de cuenta'],
        ],
    ],
];

$rol = $_SESSION['rol'];
$menu = $menus[$rol];
$nombre = $_SESSION['nombre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banco UMG - <?= htmlspecialchars($titulo) ?></title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
<header class="topnav">
    <div class="topnav-inner">
        <a class="brand" href="<?= htmlspecialchars($menu['items'][0][0]) ?>">
            <span class="brand-mark">U</span>
            <span class="brand-name">Banco UMG</span>
        </a>
        <nav class="topnav-links">
            <?php foreach ($menu['items'] as $item): ?>
                <a href="<?= $item[0] ?>" class="<?= $activo === $item[0] ? 'active' : '' ?>"><?= $item[1] ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="topnav-right">
            <span class="topnav-user"><?= htmlspecialchars($nombre) ?></span>
            <a href="logout.php">Salir</a>
        </div>
    </div>
</header>

<main class="main">
    <div class="page-head">
        <p class="eyebrow"><?= $menu['rol'] ?></p>
        <h1><?= htmlspecialchars($titulo) ?></h1>
    </div>
