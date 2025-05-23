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

// Obtener información del cliente
$sql = "SELECT * FROM clientes WHERE id_cliente = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 1) {
            $cliente = mysqli_fetch_assoc($result);
        } else {
            showAlert("No se encontró el cliente especificado.", "danger");
            header("Location: index.php");
            exit;
        }
    } else {
        showAlert("Error al obtener información del cliente.", "danger");
        header("Location: index.php");
        exit;
    }
    
    mysqli_stmt_close($stmt);
} else {
    showAlert("Error en la consulta.", "danger");
    header("Location: index.php");
    exit;
}

// Obtener pedidos del cliente
$sql_pedidos = "SELECT p.*, COUNT(dp.id_detalle) as total_productos 
                FROM pedidos p 
                LEFT JOIN detalle_pedidos dp ON p.id_pedido = dp.id_pedido 
                WHERE p.id_cliente = ? 
                GROUP BY p.id_pedido 
                ORDER BY p.fecha_pedido DESC";

if ($stmt_pedidos = mysqli_prepare($conn, $sql_pedidos)) {
    mysqli_stmt_bind_param($stmt_pedidos, "i", $id);
    
    if (mysqli_stmt_execute($stmt_pedidos)) {
        $result_pedidos = mysqli_stmt_get_result($stmt_pedidos);
    } else {
        showAlert("Error al obtener pedidos del cliente.", "danger");
    }
    
    mysqli_stmt_close($stmt_pedidos);
}

// Obtener estadísticas del cliente
$sql_stats = "SELECT 
                COUNT(p.id_pedido) as total_pedidos,
                SUM(p.total) as total_gastado,
                MAX(p.fecha_pedido) as ultimo_pedido,
                AVG(p.total) as promedio_pedido
              FROM pedidos p 
              WHERE p.id_cliente = ?";

if ($stmt_stats = mysqli_prepare($conn, $sql_stats)) {
    mysqli_stmt_bind_param($stmt_stats, "i", $id);
    
    if (mysqli_stmt_execute($stmt_stats)) {
        $result_stats = mysqli_stmt_get_result($stmt_stats);
        $stats = mysqli_fetch_assoc($result_stats);
    } else {
        showAlert("Error al obtener estadísticas del cliente.", "danger");
    }
    
    mysqli_stmt_close($stmt_stats);
}

// Incluir el header
include_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Perfil del Cliente</h1>
    <div>
        <a href="index.php" class="btn btn-secondary me-2">
            <i class="fas fa-arrow-left me-2"></i> Volver
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editClientModal">
            <i class="fas fa-edit me-2"></i> Editar Cliente
        </button>
    </div>
</div>

<?php displayAlert(); ?>

<div class="row">
    <!-- Información del Cliente -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Información del Cliente</h6>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="display-1 text-primary mb-3">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h4><?php echo $cliente['nombre']; ?></h4>
                    <p class="text-muted">Cliente desde <?php echo date('d/m/Y', strtotime($cliente['fecha_registro'])); ?></p>
                </div>
                
                <div class="mb-3">
                    <strong><i class="fas fa-phone me-2"></i> Teléfono:</strong>
                    <div class="d-flex align-items-center mt-1">
                        <span><?php echo $cliente['telefono']; ?></span>
                        <a href="tel:<?php echo $cliente['telefono']; ?>" class="btn btn-sm btn-outline-primary ms-2">
                            <i class="fas fa-phone"></i> Llamar
                        </a>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $cliente['telefono']); ?>" target="_blank" class="btn btn-sm btn-outline-success ms-2">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                    </div>
                </div>
                
                <div class="mb-3">
                    <strong><i class="fas fa-envelope me-2"></i> Correo:</strong>
                    <div class="d-flex align-items-center mt-1">
                        <span><?php echo $cliente['correo']; ?></span>
                        <a href="mailto:<?php echo $cliente['correo']; ?>" class="btn btn-sm btn-outline-primary ms-2">
                            <i class="fas fa-envelope"></i> Enviar Correo
                        </a>
                    </div>
                </div>
                
                <div class="mb-3">
                    <strong><i class="fas fa-map-marker-alt me-2"></i> Dirección:</strong>
                    <p class="mt-1"><?php echo nl2br($cliente['direccion']); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Estadísticas del Cliente -->
    <div class="col-lg-8 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Estadísticas del Cliente</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Pedidos</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_pedidos'] ?? 0; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Total Gastado</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($stats['total_gastado'] ?? 0, 2); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Promedio por Pedido</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($stats['promedio_pedido'] ?? 0, 2); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calculator fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Último Pedido</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $stats['ultimo_pedido'] ? date('d/m/Y', strtotime($stats['ultimo_pedido'])) : 'N/A'; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h6 class="font-weight-bold">Acciones Rápidas</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newOrderModal">
                            <i class="fas fa-plus me-2"></i> Nuevo Pedido
                        </button>
                        <a href="#" class="btn btn-success">
                            <i class="fas fa-envelope me-2"></i> Enviar Promoción
                        </a>
                        <a href="#" class="btn btn-info">
                            <i class="fas fa-file-alt me-2"></i> Generar Reporte
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Historial de Pedidos -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Historial de Pedidos</h6>
                <a href="../pedidos/index.php?cliente=<?php echo $id; ?>" class="btn btn-sm btn-primary">
                    Ver Todos
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Productos</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result_pedidos) > 0): ?>
                                <?php while ($pedido = mysqli_fetch_assoc($result_pedidos)): ?>
                                    <tr>
                                        <td>#<?php echo $pedido['id_pedido']; ?></td>
                                        <td><?php echo formatDate($pedido['fecha_pedido']); ?></td>
                                        <td><?php echo $pedido['total_productos']; ?> productos</td>
                                        <td>$<?php echo number_format($pedido['total'], 2); ?></td>
                                        <td><?php echo getOrderStatusBadge($pedido['estado']); ?></td>
                                        <td>
                                            <a href="../pedidos/ver.php?id=<?php echo $pedido['id_pedido']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> Ver
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">Este cliente no tiene pedidos registrados</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar cliente -->
<div class="modal fade" id="editClientModal" tabindex="-1" aria-labelledby="editClientModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editClientModalLabel">Editar Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="editar.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="id_cliente" value="<?php echo $cliente['id_cliente']; ?>">
                    <input type="hidden" name="redirect" value="ver.php?id=<?php echo $cliente['id_cliente']; ?>">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo $cliente['nombre']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="telefono" name="telefono" value="<?php echo $cliente['telefono']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="correo" name="correo" value="<?php echo $cliente['correo']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="direccion" class="form-label">Dirección</label>
                        <textarea class="form-control" id="direccion" name="direccion" rows="3" required><?php echo $cliente['direccion']; ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para nuevo pedido -->
<div class="modal fade" id="newOrderModal" tabindex="-1" aria-labelledby="newOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newOrderModalLabel">Crear Nuevo Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="../pedidos/crear.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="id_cliente" value="<?php echo $cliente['id_cliente']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Cliente</label>
                        <input type="text" class="form-control" value="<?php echo $cliente['nombre']; ?>" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="productos" class="form-label">Seleccionar Productos</label>
                        <div id="productos-container">
                            <div class="row producto-row mb-3">
                                <div class="col-md-5">
                                    <select class="form-select producto-select" name="productos[]" required>
                                        <option value="">Seleccionar producto</option>
                                        <?php
                                        // Obtener todos los productos disponibles
                                        $sql_productos = "SELECT id_producto, nombre, precio FROM productos WHERE disponible = 1 ORDER BY nombre";
                                        $result_productos = mysqli_query($conn, $sql_productos);
                                        while ($producto = mysqli_fetch_assoc($result_productos)) {
                                            echo "<option value='" . $producto['id_producto'] . "' data-precio='" . $producto['precio'] . "'>" . $producto['nombre'] . " - $" . number_format($producto['precio'], 2) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" class="form-control cantidad-input" name="cantidades[]" placeholder="Cantidad" min="1" value="1" required>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control precio-input" name="precios[]" placeholder="Precio" step="0.01" min="0" readonly>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control subtotal-input" name="subtotales[]" placeholder="Subtotal" step="0.01" min="0" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary" id="add-producto">
                            <i class="fas fa-plus me-2"></i> Agregar Producto
                        </button>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="notas" class="form-label">Notas</label>
                            <textarea class="form-control" id="notas" name="notas" rows="3"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label for="total" class="form-label">Total</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="total" name="total" step="0.01" min="0" readonly>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Pedido</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Función para actualizar precios y subtotales
        function updatePrices() {
            const productoSelects = document.querySelectorAll('.producto-select');
            const cantidadInputs = document.querySelectorAll('.cantidad-input');
            const precioInputs = document.querySelectorAll('.precio-input');
            const subtotalInputs = document.querySelectorAll('.subtotal-input');
            let total = 0;
            
            productoSelects.forEach((select, index) => {
                const selectedOption = select.options[select.selectedIndex];
                const precio = selectedOption.dataset.precio || 0;
                const cantidad = cantidadInputs[index].value || 0;
                
                precioInputs[index].value = precio;
                const subtotal = precio * cantidad;
                subtotalInputs[index].value = subtotal.toFixed(2);
                
                total += subtotal;
            });
            
            document.getElementById('total').value = total.toFixed(2);
        }
        
        // Agregar producto
        document.getElementById('add-producto').addEventListener('click', function() {
            const container = document.getElementById('productos-container');
            const productoRow = document.querySelector('.producto-row').cloneNode(true);
            
            // Limpiar valores
            productoRow.querySelector('.producto-select').selectedIndex = 0;
            productoRow.querySelector('.cantidad-input').value = 1;
            productoRow.querySelector('.precio-input').value = '';
            productoRow.querySelector('.subtotal-input').value = '';
            
            // Agregar botón de eliminar
            const deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.className = 'btn btn-sm btn-danger ms-2';
            deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
            deleteBtn.addEventListener('click', function() {
                productoRow.remove();
                updatePrices();
            });
            
            productoRow.querySelector('.col-md-2').appendChild(deleteBtn);
            
            // Agregar eventos
            productoRow.querySelector('.producto-select').addEventListener('change', updatePrices);
            productoRow.querySelector('.cantidad-input').addEventListener('input', updatePrices);
            
            container.appendChild(productoRow);
        });
        
        // Eventos iniciales
        document.querySelectorAll('.producto-select').forEach(select => {
            select.addEventListener('change', updatePrices);
        });
        
        document.querySelectorAll('.cantidad-input').forEach(input => {
            input.addEventListener('input', updatePrices);
        });
        
        // Inicializar precios
        updatePrices();
    });
</script>

<?php
// Incluir el footer
include_once '../../includes/footer.php';
?>