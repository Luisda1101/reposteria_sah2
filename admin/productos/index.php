<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Verificar si el usuario está logueado y es administrador
requireAdmin();

// Obtener todos los productos
$sql = "SELECT * FROM productos ORDER BY id_producto DESC";
$result = mysqli_query($conn, $sql);

// Incluir el header
include_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Gestión de Productos</h1>
    <a href="agregar.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i> Nuevo Producto
    </a>
</div>

<?php displayAlert(); ?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Lista de Productos</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
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
                                        <img src="../../assets/img/productos/<?php echo $row['foto']; ?>" alt="<?php echo $row['nombre']; ?>" class="product-img-thumbnail" style="width: 100px; height: auto;">
                                    <?php else: ?>
                                        <img src="../../assets/img/no-image.png" alt="Sin imagen" class="product-img-thumbnail">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $row['nombre']; ?></td>
                                <td>$<?php echo number_format($row['precio'], 2); ?></td>
                                <td><?php echo $row['peso'] ? $row['peso'] . ' kg' : 'N/A'; ?></td>
                                <td><?php echo $row['ocasion'] ? ucfirst($row['ocasion']) : 'General'; ?></td>
                                <td>
                                    <?php if ($row['disponible']): ?>
                                        <span class="badge bg-success">Disponible</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">No Disponible</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="editar.php?id=<?php echo $row['id_producto']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="eliminar.php?id=<?php echo $row['id_producto']; ?>" class="btn btn-sm btn-danger btn-delete" data-bs-toggle="tooltip" title="Eliminar">
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
        </div>
    </div>
</div>

<?php
// Incluir el footer
include_once '../../includes/footer.php';
?>