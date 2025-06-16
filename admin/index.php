<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

checkSessionValidity();
requireAdmin();

// Verificar si el usuario está logueado
requireLogin();

// Obtener estadísticas
// Total de productos
$sql_productos = "SELECT COUNT(*) as total FROM productos WHERE disponible = 1";
$result_productos = mysqli_query($conn, $sql_productos);
$row_productos = mysqli_fetch_assoc($result_productos);
$total_productos = $row_productos['total'];

// Total de pedidos
$sql_pedidos = "SELECT COUNT(*) as total FROM pedidos";
$result_pedidos = mysqli_query($conn, $sql_pedidos);
$row_pedidos = mysqli_fetch_assoc($result_pedidos);
$total_pedidos = $row_pedidos['total'];

// Total de clientes (contando clientes únicos por teléfono en la tabla pedidos)
$sql_clientes = "SELECT COUNT(*) as total FROM clientes";
$result_clientes = mysqli_query($conn, $sql_clientes);
$row_clientes = mysqli_fetch_assoc($result_clientes);
$total_clientes = $row_clientes['total'];

// Total de ventas
$sql_ventas = "SELECT SUM(total) as total FROM pedidos WHERE estado != 'cancelado'";
$result_ventas = mysqli_query($conn, $sql_ventas);
$row_ventas = mysqli_fetch_assoc($result_ventas);
$total_ventas = $row_ventas['total'] ? $row_ventas['total'] : 0;

// Pedidos recientes
$sql_pedidos_recientes = "SELECT p.id_pedido, p.fecha_pedido, p.estado, p.total, c.nombre 
                          FROM pedidos p 
                          JOIN clientes c ON p.id_cliente = c.id_cliente
                          ORDER BY p.fecha_pedido DESC LIMIT 5";
$result_pedidos_recientes = mysqli_query($conn, $sql_pedidos_recientes);

// Productos más vendidos
$sql_productos_populares = "SELECT p.id_producto, p.nombre, p.precio, COUNT(dp.id_producto) as ventas, SUM(dp.cantidad) as cantidad_total 
                            FROM productos p 
                            JOIN detalle_pedido dp ON p.id_producto = dp.id_producto 
                            JOIN pedidos pe ON dp.id_pedido = pe.id_pedido 
                            WHERE pe.estado != 'cancelado' 
                            GROUP BY p.id_producto 
                            ORDER BY ventas DESC LIMIT 5";
$result_productos_populares = mysqli_query($conn, $sql_productos_populares);

// Incluir el header
include_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Dashboard</h1>
    <div>
        <span class="text-muted">Hoy: <?php echo date('d/m/Y'); ?></span>
    </div>
</div>

<!-- Tarjetas de estadísticas -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2 dashboard-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Productos</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_productos; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-birthday-cake fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2 dashboard-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Ventas</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            $<?php echo number_format($total_ventas, 2); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2 dashboard-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Pedidos</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_pedidos; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2 dashboard-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Clientes</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_clientes; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contenido principal -->
<div class="row">
    <!-- Pedidos recientes -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Pedidos Recientes</h6>
                <a href="pedidos/index.php" class="btn btn-sm btn-primary">Ver Todos</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result_pedidos_recientes) > 0): ?>
                                <?php while ($pedido = mysqli_fetch_assoc($result_pedidos_recientes)): ?>
                                    <tr>
                                        <td><a
                                                href="pedidos/ver.php?id=<?php echo $pedido['id_pedido']; ?>">#<?php echo $pedido['id_pedido']; ?></a>
                                        </td>
                                        <td><?php echo $pedido['nombre']; ?></td>
                                        <td><?php echo formatDate($pedido['fecha_pedido']); ?></td>
                                        <td><?php echo getOrderStatusBadge($pedido['estado']); ?></td>
                                        <td>$<?php echo number_format($pedido['total'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No hay pedidos recientes</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Productos más vendidos -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Productos Más Vendidos</h6>
                <a href="productos/index.php" class="btn btn-sm btn-primary">Ver Todos</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Precio</th>
                                <th>Ventas</th>
                                <th>Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result_productos_populares) > 0): ?>
                                <?php while ($producto = mysqli_fetch_assoc($result_productos_populares)): ?>
                                    <tr>
                                        <td><a
                                                href="productos/editar.php?id=<?php echo $producto['id_producto']; ?>"><?php echo $producto['nombre']; ?></a>
                                        </td>
                                        <td>$<?php echo number_format($producto['precio'], 2); ?></td>
                                        <td><?php echo $producto['ventas']; ?></td>
                                        <td><?php echo $producto['cantidad_total']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No hay datos de ventas disponibles</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>