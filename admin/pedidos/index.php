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
$sql = "SELECT p.id_pedido, p.fecha_pedido, p.estado, p.total, c.nombre AS nombre_cliente, c.telefono 
        FROM pedidos p 
        JOIN clientes c ON p.id_cliente = c.id_cliente
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
                                <td><?php echo $row['nombre_cliente']; ?></td>
                                <td><?php echo $row['telefono']; ?></td>
                                <td><?php echo formatDate($row['fecha_pedido']); ?></td>
                                <td>$<?php echo number_format($row['total'], 2); ?></td>
                                <td><?php echo getOrderStatusBadge($row['estado']); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="ver.php?id=<?php echo $row['id_pedido']; ?>" class="btn btn-sm btn-primary"
                                            data-bs-toggle="tooltip" title="Ver Detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal"
                                            data-bs-target="#statusModal<?php echo $row['id_pedido']; ?>"
                                            title="Cambiar Estado">
                                            <i class="fas fa-exchange-alt"></i>
                                        </button>
                                    </div>

                                    <!-- Modal para cambiar estado -->
                                    <div class="modal fade" id="statusModal<?php echo $row['id_pedido']; ?>" tabindex="-1"
                                        aria-labelledby="statusModalLabel<?php echo $row['id_pedido']; ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title"
                                                        id="statusModalLabel<?php echo $row['id_pedido']; ?>">Cambiar Estado del
                                                        Pedido #<?php echo $row['id_pedido']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <form action="actualizar_estado.php" method="post">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="id_pedido"
                                                            value="<?php echo $row['id_pedido']; ?>">
                                                        <div class="mb-3">
                                                            <label for="estado<?php echo $row['id_pedido']; ?>"
                                                                class="form-label">Estado</label>
                                                            <select class="form-select"
                                                                id="estado<?php echo $row['id_pedido']; ?>" name="estado"
                                                                required>
                                                                <option value="pendiente" <?php echo $row['estado'] == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                                                <option value="en_proceso" <?php echo $row['estado'] == 'en_proceso' ? 'selected' : ''; ?>>En
                                                                    Proceso</option>
                                                                <option value="completado" <?php echo $row['estado'] == 'completado' ? 'selected' : ''; ?>>
                                                                    Completado</option>
                                                                <option value="entregado" <?php echo $row['estado'] == 'entregado' ? 'selected' : ''; ?>>Entregado</option>
                                                                <option value="cancelado" <?php echo $row['estado'] == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="notificar<?php echo $row['id_pedido']; ?>"
                                                                class="form-label">Notificar al cliente</label>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox"
                                                                    id="notificar<?php echo $row['id_pedido']; ?>"
                                                                    name="notificar" checked>
                                                                <label class="form-check-label"
                                                                    for="notificar<?php echo $row['id_pedido']; ?>">
                                                                    Enviar correo electrónico de notificación
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="notas<?php echo $row['id_pedido']; ?>"
                                                                class="form-label">Notas adicionales</label>
                                                            <textarea class="form-control"
                                                                id="notas<?php echo $row['id_pedido']; ?>" name="notas"
                                                                rows="3"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary"
                                                            data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-primary">Actualizar Estado</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
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
    });
</script>