<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

checkSessionValidity();
requireAdmin();

// Verificar si el usuario está logueado y es administrador
requireAdmin();

// Filtros
$estado_filtro = isset($_GET['estado']) ? cleanInput($_GET['estado']) : '';
$fecha_desde = isset($_GET['fecha_desde']) ? cleanInput($_GET['fecha_desde']) : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? cleanInput($_GET['fecha_hasta']) : '';
$busqueda = isset($_GET['busqueda']) ? cleanInput($_GET['busqueda']) : '';

// Construir la consulta SQL con filtros
$sql = "SELECT p.id_pedido, p.fecha_pedido, p.estado, p.total, 
               c.nombre AS nombre_cliente, c.telefono AS telefono_cliente 
        FROM pedidos p 
        LEFT JOIN clientes c ON p.id_cliente = c.id_cliente
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($estado_filtro)) {
    $sql .= " AND p.estado = ?";
    $params[] = $estado_filtro;
    $types .= "s";
}

if (!empty($fecha_desde)) {
    $sql .= " AND p.fecha_pedido >= ?";
    $params[] = $fecha_desde . " 00:00:00";
    $types .= "s";
}

if (!empty($fecha_hasta)) {
    $sql .= " AND p.fecha_pedido <= ?";
    $params[] = $fecha_hasta . " 23:59:59";
    $types .= "s";
}

if (!empty($busqueda)) {
    $sql .= " AND (c.nombre LIKE ? OR c.telefono LIKE ? OR p.id_pedido LIKE ?)";
    $busqueda_param = "%" . $busqueda . "%";
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $types .= "sss";
}

$sql .= " ORDER BY p.fecha_pedido DESC";

// Preparar y ejecutar la consulta
$stmt = mysqli_prepare($conn, $sql);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Procesar actualización de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_estado'], $_POST['id_pedido'], $_POST['nuevo_estado'])) {
    $id_pedido = intval($_POST['id_pedido']);
    $nuevo_estado = $_POST['nuevo_estado'];
    $estados_validos = ['aceptado', 'en_proceso', 'enviado', 'finalizado'];
    if (in_array($nuevo_estado, $estados_validos)) {
        $sql = "UPDATE pedidos SET estado = ? WHERE id_pedido = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $nuevo_estado, $id_pedido);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        // Mensaje de éxito opcional
        echo '<div class="alert alert-success">Estado actualizado correctamente.</div>';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_estado'], $_POST['id_pedido'], $_POST['nuevo_estado'])) {
    require_once '../../config/database.php';
    $id_pedido = intval($_POST['id_pedido']);
    $nuevo_estado = $_POST['nuevo_estado'];
    $estados_validos = ['aceptado', 'en_proceso', 'enviado', 'finalizado'];
    if (in_array($nuevo_estado, $estados_validos)) {
        $sql = "UPDATE pedidos SET estado = ? WHERE id_pedido = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $nuevo_estado, $id_pedido);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['success' => $ok]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

// Incluir el header
include_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Gestión de Pedidos</h1>
    <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exportModal">
        <i class="fas fa-file-export me-2"></i> Exportar
    </a>
</div>

<?php displayAlert(); ?>

<!-- Filtros -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Filtros de Búsqueda</h6>
    </div>
    <div class="card-body">
        <form action="" method="get" class="row g-3">
            <div class="col-md-3">
                <label for="estado" class="form-label">Estado</label>
                <select class="form-select" id="estado" name="estado">
                    <option value="">Todos</option>
                    <option value="pendiente" <?php echo $estado_filtro == 'pendiente' ? 'selected' : ''; ?>>Pendiente
                    </option>
                    <option value="en_proceso" <?php echo $estado_filtro == 'en_proceso' ? 'selected' : ''; ?>>En Proceso
                    </option>
                    <option value="completado" <?php echo $estado_filtro == 'completado' ? 'selected' : ''; ?>>Completado
                    </option>
                    <option value="entregado" <?php echo $estado_filtro == 'entregado' ? 'selected' : ''; ?>>Entregado
                    </option>
                    <option value="cancelado" <?php echo $estado_filtro == 'cancelado' ? 'selected' : ''; ?>>Cancelado
                    </option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="fecha_desde" class="form-label">Fecha Desde</label>
                <input type="date" class="form-control" id="fecha_desde" name="fecha_desde"
                    value="<?php echo $fecha_desde; ?>">
            </div>
            <div class="col-md-3">
                <label for="fecha_hasta" class="form-label">Fecha Hasta</label>
                <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta"
                    value="<?php echo $fecha_hasta; ?>">
            </div>
            <div class="col-md-3">
                <label for="busqueda" class="form-label">Buscar</label>
                <input type="text" class="form-control" id="busqueda" name="busqueda"
                    placeholder="Cliente, teléfono, ID..." value="<?php echo $busqueda; ?>">
            </div>
            <div class="col-12 text-end">
                <a href="index.php" class="btn btn-secondary me-2">Limpiar</a>
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </div>
        </form>
    </div>
</div>

<!-- Lista de Pedidos -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Lista de Pedidos</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>#<?php echo $row['id_pedido']; ?></td>
                                <td><?php echo !empty($row['nombre_cliente']) ? $row['nombre_cliente'] : 'Desconocido'; ?></td>
                                <td><?php echo !empty($row['telefono_cliente']) ? $row['telefono_cliente'] : 'Desconocido'; ?>
                                </td>
                                <td><?php echo !empty($row['fecha_pedido']) ? formatDate($row['fecha_pedido']) : 'Desconocido'; ?>
                                </td>
                                <td>$<?php echo isset($row['total']) ? number_format($row['total'], 2) : '0.00'; ?></td>
                                <td><?php echo isset($row['estado']) ? $row['estado'] : 'Desconocido'; ?></td>
                                <td>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="id_pedido" value="<?php echo $row['id_pedido']; ?>">
                                        <select name="nuevo_estado" class="form-select form-select-sm d-inline w-auto" required>
                                            <option value="aceptado" <?php if ($row['estado'] == 'aceptado')
                                                echo 'selected'; ?>>
                                                Aceptado</option>
                                            <option value="en_proceso" <?php if ($row['estado'] == 'en_proceso')
                                                echo 'selected'; ?>>En Proceso</option>
                                            <option value="enviado" <?php if ($row['estado'] == 'enviado')
                                                echo 'selected'; ?>>
                                                Enviado</option>
                                            <option value="finalizado" <?php if ($row['estado'] == 'finalizado')
                                                echo 'selected'; ?>>Finalizado</option>
                                        </select>
                                        <button type="submit" name="actualizar_estado"
                                            class="btn btn-sm btn-primary ms-1">Actualizar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No hay pedidos que coincidan con los filtros</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para exportar -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalLabel">Exportar Pedidos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="exportar.php" method="post">
                    <div class="mb-3">
                        <label for="export_format" class="form-label">Formato</label>
                        <select class="form-select" id="export_format" name="format" required>
                            <option value="excel">Excel (.xlsx)</option>
                            <option value="csv">CSV (.csv)</option>
                            <option value="pdf">PDF (.pdf)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="export_date_range" class="form-label">Rango de Fechas</label>
                        <select class="form-select" id="export_date_range" name="date_range">
                            <option value="all">Todos los pedidos</option>
                            <option value="today">Hoy</option>
                            <option value="week">Esta semana</option>
                            <option value="month">Este mes</option>
                            <option value="custom">Personalizado</option>
                        </select>
                    </div>
                    <div class="row mb-3 date-range-custom" style="display: none;">
                        <div class="col-md-6">
                            <label for="export_date_from" class="form-label">Desde</label>
                            <input type="date" class="form-control" id="export_date_from" name="date_from">
                        </div>
                        <div class="col-md-6">
                            <label for="export_date_to" class="form-label">Hasta</label>
                            <input type="date" class="form-control" id="export_date_to" name="date_to">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="export_status" class="form-label">Estado</label>
                        <select class="form-select" id="export_status" name="status">
                            <option value="all">Todos los estados</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="en_proceso">En Proceso</option>
                            <option value="completado">Completado</option>
                            <option value="entregado">Entregado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary">Exportar</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Mostrar/ocultar campos de fecha personalizada
        const dateRangeSelect = document.getElementById('export_date_range');
        const dateRangeCustom = document.querySelector('.date-range-custom');

        dateRangeSelect.addEventListener('change', function () {
            if (this.value === 'custom') {
                dateRangeCustom.style.display = 'flex';
            } else {
                dateRangeCustom.style.display = 'none';
            }
        });

        // Actualización de estado en tiempo real (y recarga forzosa al actualizar)
        document.querySelectorAll('form .btn[name="actualizar_estado"]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                setTimeout(function () {
                    window.location.reload(true); // recarga forzosa desde el servidor
                }, 1000);
            });
        });
    });
</script>