<?php
require_once 'verificar_admin.php';
require_once 'conexion.php';

$titulo = 'Logs del Servidor';
$activo = 'logs.php';
require 'cabecera.php';

// Consultas de logs a la base de datos MySQL
$resTotal = $mysqli->query("SELECT COUNT(*) as total FROM server_logs");
$totalRequests = $resTotal ? ($resTotal->fetch_assoc()['total'] ?? 0) : 0;

$resStatus = $mysqli->query("SELECT codigo_respuesta, COUNT(*) as cantidad FROM server_logs GROUP BY codigo_respuesta");
$statusCodes = [];
if ($resStatus) {
    while ($row = $resStatus->fetch_assoc()) {
        $statusCodes[$row['codigo_respuesta']] = $row['cantidad'];
    }
}

$resPages = $mysqli->query("SELECT pagina_solicitada, COUNT(*) as cantidad FROM server_logs GROUP BY pagina_solicitada ORDER BY cantidad DESC LIMIT 15");
?>

<style>
    .badge {
        display: inline-block;
        padding: 4px 10px;
        font-size: 0.85rem;
        font-weight: 600;
        border-radius: 6px;
    }
    .badge-ok {
        background-color: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }
    .badge-error {
        background-color: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }
</style>

<div class="card">
    <h2>Tráfico en Tiempo Real</h2>
    <p><strong>Total de Solicitudes Registradas:</strong> <?= $totalRequests ?></p>
</div>

<div class="card">
    <h2>1. Solicitudes por Código de Estado HTTP</h2>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Estado</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($statusCodes as $code => $count): ?>
            <tr>
                <td><strong><?= $code ?></strong></td>
                <td>
                    <span class="badge <?= (int)$code === 200 ? 'badge-ok' : 'badge-error' ?>">
                        <?= (int)$code === 200 ? '200 OK (Éxito)' : $code . ' Error / Redirección' ?>
                    </span>
                </td>
                <td><?= $count ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h2>2. Páginas y Recursos Más Visitados</h2>
    <table>
        <thead>
            <tr>
                <th>Página / Recurso</th>
                <th>Visitas</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($resPages): ?>
                <?php while ($row = $resPages->fetch_assoc()): ?>
                <tr>
                    <td><code><?= htmlspecialchars($row['pagina_solicitada']) ?></code></td>
                    <td><?= $row['cantidad'] ?></td>
                </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require 'pie.php'; ?>