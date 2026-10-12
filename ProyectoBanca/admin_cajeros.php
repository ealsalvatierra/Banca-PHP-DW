<?php
require 'verificar_admin.php';
require 'conexion.php';

$mensaje = '';
$tipoMensaje = '';
$nombre = $usuario = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {

    if ($_POST['accion'] === 'estado') {
        $usuarioCajero = trim($_POST['usuario'] ?? '');
        $activo = (int)($_POST['activo'] ?? 0);

        $stmt = $mysqli->prepare('CALL sp_cajero_cambiar_estado(?, ?)');
        $stmt->bind_param('si', $usuarioCajero, $activo);

        if ($stmt->execute()) {
            $mensaje = $activo ? 'Cajero desbloqueado correctamente.' : 'Cajero bloqueado correctamente.';
            $tipoMensaje = 'mensaje-exito';
        } else {
            $mensaje = 'Error al actualizar el cajero: ' . $stmt->error;
            $tipoMensaje = 'mensaje-error';
        }
        $stmt->close();

    } elseif ($_POST['accion'] === 'agregar') {
        $nombre = trim($_POST['nombre'] ?? '');
        $usuario = trim($_POST['usuario'] ?? '');
        $clave = trim($_POST['clave'] ?? '');
        $confirmar = trim($_POST['confirmar'] ?? '');

        $errores = [];

        if ($nombre === '' || strlen($nombre) > 60) {
            $errores[] = 'El nombre completo es obligatorio y debe tener máximo 60 caracteres.';
        }
        if ($usuario === '' || strlen($usuario) > 50) {
            $errores[] = 'El usuario es obligatorio y debe tener máximo 50 caracteres.';
        }
        if ($clave === '' || strlen($clave) > 20) {
            $errores[] = 'La clave es obligatoria y debe tener máximo 20 caracteres.';
        }
        if ($clave !== $confirmar) {
            $errores[] = 'La clave y su confirmación no coinciden.';
        }

        if (empty($errores)) {
            $stmt = $mysqli->prepare('CALL sp_cajero_existe(?)');
            $stmt->bind_param('s', $usuario);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $fila = $resultado->fetch_assoc();
            $stmt->close();

            if ($fila['total'] > 0) {
                $errores[] = 'Ya existe un cajero con ese usuario.';
            }
        }

        if (empty($errores)) {
            $stmt = $mysqli->prepare('CALL sp_cajero_insertar(?, ?, ?)');
            $stmt->bind_param('sss', $usuario, $clave, $nombre);

            if ($stmt->execute()) {
                $mensaje = 'Cajero agregado correctamente.';
                $tipoMensaje = 'mensaje-exito';
                $nombre = $usuario = '';
            } else {
                $mensaje = 'Error al agregar el cajero: ' . $stmt->error;
                $tipoMensaje = 'mensaje-error';
            }
            $stmt->close();
        } else {
            $mensaje = implode('<br>', $errores);
            $tipoMensaje = 'mensaje-error';
        }
    }
}

$resultado = $mysqli->query('CALL sp_cajeros_listar()');

$titulo = 'Cajeros';
$activo = 'admin_cajeros.php';
require 'cabecera.php';
?>
<?php if ($mensaje): ?>
    <p class="<?= $tipoMensaje ?>"><?= $mensaje ?></p>
<?php endif; ?>

<div class="card">
    <h2>Usuarios de cajeros</h2>
    <?php if ($resultado && $resultado->num_rows > 0): ?>
    <table>
        <tr>
            <th>Usuario</th>
            <th>Nombre completo</th>
            <th>Estado</th>
            <th></th>
        </tr>
        <?php while ($fila = $resultado->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($fila['usuario']) ?></td>
            <td><?= htmlspecialchars($fila['nombre_completo']) ?></td>
            <td>
                <span class="pill <?= $fila['activo'] ? 'pill-on' : 'pill-off' ?>">
                    <?= $fila['activo'] ? 'Activo' : 'Bloqueado' ?>
                </span>
            </td>
            <td>
                <form action="admin_cajeros.php" method="POST" style="margin:0;">
                    <input type="hidden" name="accion" value="estado">
                    <input type="hidden" name="usuario" value="<?= htmlspecialchars($fila['usuario']) ?>">
                    <input type="hidden" name="activo" value="<?= $fila['activo'] ? 0 : 1 ?>">
                    <input type="submit" class="small" value="<?= $fila['activo'] ? 'Bloquear' : 'Desbloquear' ?>">
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?>
        <p class="lead">No hay cajeros registrados.</p>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Agregar cajero</h2>
    <form action="admin_cajeros.php" method="POST">
        <input type="hidden" name="accion" value="agregar">

        <div class="field">
            <label for="nombre">Nombre completo</label>
            <input type="text" id="nombre" name="nombre" maxlength="60" required value="<?= htmlspecialchars($nombre) ?>">
        </div>
        <div class="field">
            <label for="usuario">Usuario</label>
            <input type="text" id="usuario" name="usuario" maxlength="50" required value="<?= htmlspecialchars($usuario) ?>">
        </div>
        <div class="field">
            <label for="clave">Clave</label>
            <input type="password" id="clave" name="clave" maxlength="20" required>
        </div>
        <div class="field">
            <label for="confirmar">Confirmación de clave</label>
            <input type="password" id="confirmar" name="confirmar" maxlength="20" required>
        </div>

        <input type="submit" value="Agregar cajero">
    </form>
</div>
<?php require 'pie.php'; ?>
