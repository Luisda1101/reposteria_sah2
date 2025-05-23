<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Verificar si el usuario está logueado y es administrador
requireAdmin();

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = cleanInput($_GET['id']);

// Obtener información del pedido
$sql = "SELECT p.*, c.nombre as cliente, c.telefono, c.correo, c.direccion 
        FROM pedidos p 
        JOIN clientes c ON p.id_cliente = c.id_cliente 
        WHERE p.id_pedido = ?";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 1) {
            $pedido = mysqli_fetch_assoc($result);
        } else {
            showAlert("No se encontró el pedido especificado.", "danger");
            header("Location: index.php");
            exit;
        }
    } else {
        showAlert("Error al obtener información del pedido.", "danger");
        header("Location: index.php");
        exit;
    }
    
    mysqli_stmt_close($stmt);
} else {
    showAlert("Error en la consulta.", "danger");
    header("Location: index.php");
    exit;
}

// Obtener detalles del pedido
$sql_detalles = "SELECT d.*, p.nombre, p.foto 
                FROM detalle_pedidos d 
                JOIN productos p ON d.id_producto = p.id_producto 
                WHERE d.id_pedido = ?";

if ($stmt_detalles = mysqli_prepare($conn, $sql_detalles)) {
    mysqli_stmt_bind_param($stmt_detalles, "i", $id);
    
    if (mysqli_stmt_execute($stmt_detalles)) {
        $result_detalles = mysqli_stmt_get_result($stmt_detalles);
    } else {
        showAlert("Error al obtener detalles del pedido.", "danger");
    }
    
    mysqli_stmt_close($stmt_detalles);
}

// Incluir el header
include_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Detalles del Pedido #<?php echo $id; ?></h1>
    <div>
        <a href="index.php" class="btn btn-secondary me-2">
            <i class="fas fa-arrow-left me-2"></i> Volver
        </a>
        <a href="#" class="btn btn-primary" onclick="window.print();">
            <i class="fas fa-print me-2"></i> Imprimir
        </a>
    </div>
</div>

<?php displayAlert(); ?>

<div class="row">
    <!-- Información del Pedido -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Información del Pedido</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Estado:</strong> <?php echo getOrderStatusBadge($pedido['estado']); ?>
                </div>
                <div class="mb-3">
                    <strong>Fecha del Pedido:</strong> <?php echo formatDate($pedido['fecha_pedido']); ?>
                </div>
                <div class="mb-3">
                    <strong>Total:</strong> $<?php echo number_format($pedido['total'], 2); ?>
                </div>
                <?php if (!empty($pedido['notas'])): ?>
                <div class="mb-3">
                    <strong>Notas:</strong>
                    <p class="mt-1"><?php echo nl2br($pedido['notas']); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="mt-4">
                    <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#statusModal">
                        <i class="fas fa-exchange-alt me-2"></i> Cambiar Estado
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Información del Cliente -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Información del Cliente</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Nombre:</strong> <?php echo $pedido['cliente']; ?>
                </div>
                <div class="mb-3">
                    <strong>Teléfono:</strong> <?php echo $pedido['telefono']; ?>
                    <a href="tel:<?php echo $pedido['telefono']; ?>" class="btn btn-sm btn-outline-primary ms-2">
                        <i class="fas fa-phone"></i>
                    </a>
                </div>
                <div class="mb-3">
                    <strong>Correo:</strong> <?php echo $pedido['correo']; ?>
                    <a href="mailto:<?php echo $pedido['correo']; ?>" class="btn btn-sm btn-outline-primary ms-2">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
                <div class="mb-3">
                    <strong>Dirección:</strong>
                    <p class="mt-1"><?php echo nl2br($pedido['direccion']); ?></p>
                </div>
                
                <div class="mt-4">
                    <a href="../clientes/ver.php?id=<?php echo $pedido['id_cliente']; ?>" class="btn btn-primary w-100">
                        <i class="fas fa-user me-2"></i> Ver Perfil del Cliente
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Historial de Estados -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Historial de Estados</h6>
            </div>
            <div class="card-body">
                <ul class="timeline">
                    <li class="timeline-item">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <h3 class="timeline-title">Pedido Creado</h3>
                            <p class="timeline-date"><?php echo formatDate($pedido['fecha_pedido']); ?></p>
                        </div>
                    </li>
                    <!-- Aquí se mostrarían los cambios de estado del pedido -->
                    <!-- En una implementación real, estos datos vendrían de una tabla de historial -->
                    <li class="timeline-item">
                        <div class="timeline-marker bg-primary"></div>
                        <div class="timeline-content">
                            <h3 class="timeline-title">Estado Actual: <?php echo ucfirst(str_replace('_', ' ', $pedido['estado'])); ?></h3>
                            <p class="timeline-date">Actualizado: <?php echo formatDate($pedido['fecha_pedido']); ?></p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Detalles de Productos -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Productos del Pedido</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Precio Unitario</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result_detalles) > 0): ?>
                        <?php while ($detalle = mysqli_fetch_assoc($result_detalles)): ?>
                            <tr>
                                <td class="d-flex align-items-center">
                                    <?php if (!empty($detalle['foto'])): ?>
                                        <img src="../../assets/img/productos/<?php echo $detalle['foto']; ?>" alt="<?php echo $detalle['nombre']; ?>" class="product-img-thumbnail me-3">
                                    <?php else: ?>
                                        <img src="../../assets/img/no-image.png" alt="Sin imagen" class="product-img-thumbnail me-3">
                                    <?php endif; ?>
                                    <div>
                                        <h6 class="mb-0"><?php echo $detalle['nombre']; ?></h6>
                                        <a href="../productos/editar.php?id=<?php echo $detalle['id_producto']; ?>" class="text-primary">Ver producto</a>
                                    </div>
                                </td>
                                <td>$<?php echo number_format($detalle['precio_unitario'], 2); ?></td>
                                <td><?php echo $detalle['cantidad']; ?></td>
                                <td>$<?php echo number_format($detalle['subtotal'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">No hay productos en este pedido</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                        <td><strong>$<?php echo number_format($pedido['total'], 2); ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Modal para cambiar estado -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusModalLabel">Cambiar Estado del Pedido #<?php echo $id; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="actualizar_estado.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="id_pedido" value="<?php echo $id; ?>">
                    <input type="hidden" name="redirect" value="ver.php?id=<?php echo $id; ?>">
                    <div class="mb-3">
                        <label for="estado" class="form-label">Estado</label>
                        <select class="form-select" id="estado" name="estado" required>
                            <option value="pendiente" <?php echo $pedido['estado'] == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="en_proceso" <?php echo $pedido['estado'] == 'en_proceso' ? 'selected' : ''; ?>>En Proceso</option>
                            <option value="completado" <?php echo $pedido['estado'] == 'completado' ? 'selected' : ''; ?>>Completado</option>
                            <option value="entregado" <?php echo $pedido['estado'] == 'entregado' ? 'selected' : ''; ?>>Entregado</option>
                            <option value="cancelado" <?php echo $pedido['estado'] == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="notificar" class="form-label">Notificar al cliente</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="notificar" name="notificar" checked>
                            <label class="form-check-label" for="notificar">
                                Enviar correo electrónico de notificación
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="notas" class="form-label">Notas adicionales</label>
                        <textarea class="form-control" id="notas" name="notas" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar Estado</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Estilos para la línea de tiempo */
.timeline {
    list-style: none;
    padding: 0;
    position: relative;
}

.timeline:before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #ddd;
    left: 12px;
    margin-left: -1px;
}

.timeline-item {
    position: relative;
    padding-left: 40px;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    left: 0;
    top: 0;
}

.timeline-title {
    font-size: 1rem;
    margin: 0;
}

.timeline-date {
    font-size: 0.8rem;
    color: #6c757d;
    margin: 0;
}

/* Estilos para impresión */
@media print {
    .sidebar, .navbar, .btn, .modal, footer {
        display: none !important;
    }
    
    .content {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    
    .card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }
    
    .card-header {
        background-color: #f8f9fa !important;
        color: #000 !important;
    }
}
</style>

<?php
// Incluir el footer
include_once '../../includes/footer.php';
?>