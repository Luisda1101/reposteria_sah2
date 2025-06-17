<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

checkSessionValidity();
requireAdmin();

// Configuración de paginación
$registros_por_pagina = 5; // Puedes ajustar este número según tus preferencias
$pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Obtener el total de productos para la paginación
$sql_total = "SELECT COUNT(*) as total FROM productos";
$result_total = mysqli_query($conn, $sql_total);
$row_total = mysqli_fetch_assoc($result_total);
$total_registros = $row_total['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Obtener productos con paginación
$sql = "SELECT * FROM productos ORDER BY id_producto DESC LIMIT $offset, $registros_por_pagina";
$result = mysqli_query($conn, $sql);

// Incluir el header
include_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Gestión de Productos</h1>
    <a href="agregar.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i> Nuevo Producto
    </a>
</div>

<?php displayAlert(); ?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Lista de Productos</h6>
        <span class="text-muted">Mostrando <?php echo mysqli_num_rows($result); ?> de <?php echo $total_registros; ?>
            productos</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Imagen</th>
                        <th>Nombre</th>
                        <th>Precio</th>
                        <th>Peso</th>
                        <th>Ocasión</th>
                        <th>Disponible</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $row['id_producto']; ?></td>
                                <td>
                                    <?php if (!empty($row['foto'])): ?>
                                        <img src="../../assets/img/productos/<?php echo $row['foto']; ?>"
                                            alt="<?php echo $row['nombre']; ?>" class="product-img-thumbnail"
                                            style="width: 100px; height: auto;">
                                    <?php else: ?>
                                        <img src="../../assets/img/no-image.png" alt="Sin imagen" class="product-img-thumbnail">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $row['nombre']; ?></td>
                                <td>$<?php echo number_format($row['precio'], 2); ?></td>
                                <td><?php echo $row['peso'] ? $row['peso'] . ' kg' : 'N/A'; ?></td>
                                <td><?php echo $row['categoria'] ? ucfirst($row['categoria']) : 'General'; ?></td>
                                <td>
                                    <?php if ($row['disponible']): ?>
                                        <span class="badge bg-success">Disponible</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">No Disponible</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="editar.php?id=<?php echo $row['id_producto']; ?>"
                                            class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="eliminar.php?id=<?php echo $row['id_producto']; ?>"
                                            class="btn btn-sm btn-danger btn-delete" data-bs-toggle="tooltip" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No hay productos registrados</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Paginación con Bootstrap -->
            <?php if ($total_paginas > 1): ?>
                <nav aria-label="Navegación de productos">
                    <ul class="pagination justify-content-center mt-4">
                        <!-- Botón Anterior -->
                        <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?>" aria-label="Anterior">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>

                        <!-- Primera página -->
                        <?php if ($pagina_actual > 3): ?>
                            <li class="page-item">
                                <a class="page-link" href="?pagina=1">1</a>
                            </li>
                            <?php if ($pagina_actual > 4): ?>
                                <li class="page-item disabled">
                                    <a class="page-link" href="#">...</a>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- Rango de páginas -->
                        <?php
                        $rango_inicio = max(1, $pagina_actual - 2);
                        $rango_fin = min($total_paginas, $pagina_actual + 2);

                        for ($i = $rango_inicio; $i <= $rango_fin; $i++):
                            ?>
                            <li class="page-item <?php echo ($pagina_actual == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <!-- Última página -->
                        <?php if ($pagina_actual < $total_paginas - 2): ?>
                            <?php if ($pagina_actual < $total_paginas - 3): ?>
                                <li class="page-item disabled">
                                    <a class="page-link" href="#">...</a>
                                </li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link"
                                    href="?pagina=<?php echo $total_paginas; ?>"><?php echo $total_paginas; ?></a>
                            </li>
                        <?php endif; ?>

                        <!-- Botón Siguiente -->
                        <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?>" aria-label="Siguiente">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>