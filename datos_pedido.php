<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recuperar datos del cliente desde localStorage (enviado por campos ocultos o por JS)
    $nombre = cleanInput($_POST['nombre']);
    $telefono = cleanInput($_POST['telefono']);
    $correo = cleanInput($_POST['correo']);
    $direccion = cleanInput($_POST['direccion']);
    // Datos pedido
    $fecha_entrega = cleanInput($_POST['fecha_entrega']);
    $hora_entrega = cleanInput($_POST['hora_entrega']);
    $metodo_pago = cleanInput($_POST['metodo_pago']);
    $comentarios = isset($_POST['comentarios']) ? cleanInput($_POST['comentarios']) : '';

    // Validaciones
    if (empty($fecha_entrega) || empty($hora_entrega) || empty($metodo_pago)) {
        $errores[] = "Todos los campos del pedido son obligatorios.";
    }
    $fecha_minima = date('Y-m-d', strtotime('+3 days'));
    if ($fecha_entrega < $fecha_minima) {
        $errores[] = "La fecha de entrega debe ser al menos 3 días después de hoy.";
    }

    if (empty($errores)) {
        // Guardar cliente en la base de datos (si no existe, puedes buscar por correo o teléfono para evitar duplicados)
        $sql = "SELECT id_cliente FROM clientes WHERE correo = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $correo);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $id_cliente);
        if (mysqli_stmt_fetch($stmt)) {
            // Cliente ya existe
            mysqli_stmt_close($stmt);
        } else {
            mysqli_stmt_close($stmt);
            $sql = "INSERT INTO clientes (nombre, telefono, correo, direccion) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssss", $nombre, $telefono, $correo, $direccion);
            mysqli_stmt_execute($stmt);
            $id_cliente = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
        }

        // Guardar pedido
        $subtotal = 0;
        foreach ($_SESSION['carrito'] as $item) {
            $subtotal += $item['precio'] * $item['cantidad'];
        }
        $costo_envio = 50.00;
        $total = $subtotal + $costo_envio;

        mysqli_begin_transaction($conn);
        try {
            $sql_pedido = "INSERT INTO pedidos (id_cliente, nombre_cliente, telefono, email, direccion, fecha_entrega, hora_entrega, tipo_entrega, metodo_pago, observaciones, total, estado, fecha_pedido) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'domicilio', ?, ?, ?, 'pendiente', NOW())";
            $stmt_pedido = mysqli_prepare($conn, $sql_pedido);
            mysqli_stmt_bind_param(
                $stmt_pedido,
                "issssssssd",
                $id_cliente,
                $nombre,
                $telefono,
                $correo,
                $direccion,
                $fecha_entrega,
                $hora_entrega,
                $metodo_pago,
                $comentarios,
                $total
            );
            mysqli_stmt_execute($stmt_pedido);
            $id_pedido = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt_pedido);

            // Insertar detalles del pedido
            $sql_detalle = "INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, subtotal) VALUES (?, ?, ?, ?)";
            $stmt_detalle = mysqli_prepare($conn, $sql_detalle);
            foreach ($_SESSION['carrito'] as $item) {
                $subtotal_item = $item['precio'] * $item['cantidad'];
                // Deben ser 5 variables: int, int, int, double, double
                mysqli_stmt_bind_param(
                    $stmt_detalle,
                    "iiid", // 2 int, 1 int, 2 double
                    $id_pedido,
                    $item['id_producto'],
                    $item['cantidad'],
                    $subtotal_item
                );
                mysqli_stmt_execute($stmt_detalle);
            }
            mysqli_stmt_close($stmt_detalle);

            mysqli_commit($conn);

            // Limpiar carrito
            unset($_SESSION['carrito']);

            // Redirigir a confirmación
            header("Location: pedido_confirmado.php?pedido={$id_pedido}");
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errores[] = "Error al guardar el pedido: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Datos del Pedido</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card p-4">
                    <h2 class="fw-bold mb-4 text-center">Datos del Pedido</h2>
                    <?php if (!empty($errores)): ?>
                        <div class="alert alert-danger"><?php echo implode('<br>', $errores); ?></div>
                    <?php endif; ?>
                    <form method="post" id="pedidoForm" autocomplete="off">
                        <!-- Campos ocultos para datos del cliente -->
                        <input type="hidden" id="nombre" name="nombre">
                        <input type="hidden" id="telefono" name="telefono">
                        <input type="hidden" id="correo" name="correo">
                        <input type="hidden" id="direccion" name="direccion">
                        <div class="mb-3">
                            <label for="fecha_entrega" class="form-label">Fecha de entrega</label>
                            <input type="date" class="form-control" id="fecha_entrega" name="fecha_entrega" required
                                min="<?php echo date('Y-m-d', strtotime('+3 days')); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="hora_entrega" class="form-label">Hora de entrega</label>
                            <input type="time" class="form-control" id="hora_entrega" name="hora_entrega" required>
                        </div>
                        <div class="mb-3">
                            <label for="metodo_pago" class="form-label">Método de pago</label>
                            <select class="form-select" id="metodo_pago" name="metodo_pago" required>
                                <option value="">Selecciona...</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="transferencia">Transferencia</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="comentarios" class="form-label">Comentarios</label>
                            <textarea class="form-control" id="comentarios" name="comentarios" rows="2"></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                Finalizar pedido
                            </button>
                        </div>
                    </form>
                </div>
                <div class="text-center mt-4">
                    <a href="carrito.php" class="btn btn-link text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i> Volver al carrito
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Precargar datos del cliente en campos ocultos desde localStorage
        document.addEventListener('DOMContentLoaded', function () {
            const datos = JSON.parse(localStorage.getItem('clienteDatos'));
            if (datos) {
                document.getElementById('nombre').value = datos.nombre || '';
                document.getElementById('telefono').value = datos.telefono || '';
                document.getElementById('correo').value = datos.correo || '';
                document.getElementById('direccion').value = datos.direccion || '';
            }
        });
    </script>
</body>

</html>