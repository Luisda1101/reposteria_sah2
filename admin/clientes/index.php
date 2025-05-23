<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Verificar si el usuario está logueado y es administrador
requireAdmin();

// Filtros
$busqueda = isset($_GET['busqueda']) ? cleanInput($_GET['busqueda']) : '';

// Construir la consulta SQL con filtros
$sql = "SELECT c.*, COUNT(p.id_pedido) as total_pedidos, SUM(p.total) as total_gastado 
        FROM clientes c 
        LEFT JOIN pedidos p ON c.id_cliente = p.id_cliente 
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($busqueda)) {
    $sql .= " AND (c.nombre LIKE ? OR c.telefono LIKE ? OR c.correo LIKE ?)";
    $busqueda_param = "%" . $busqueda . "%";
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $types .= "sss";
}

$sql .= " GROUP BY c.id_cliente ORDER BY c.nombre ASC";

// Preparar y ejecutar la consulta
$stmt = mysqli_prepare($conn, $sql);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Incluir el header
include_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Gestión de Clientes</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClientModal">
        <i class="fas fa-plus me-2"></i> Nuevo Cliente
    </button>
</div>

<?php displayAlert(); ?>

<!-- Filtros -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Buscar Clientes</h6>
    </div>
    <div class="card-body">
        <form action="" method="get" class="row g-3">
            <div class="col-md-10">
                <input type="text" class="form-control" id="busqueda" name="busqueda" placeholder="Buscar por nombre, teléfono o correo..." value="<?php echo $busqueda; ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Buscar</button>
            </div>
        </form>
    </div>
</div>

<!-- Lista de Clientes -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Lista de Clientes</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Pedidos</th>
                        <th>Total Gastado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $row['id_cliente']; ?></td>
                                <td><?php echo $row['nombre']; ?></td>
                                <td>
                                    <?php echo $row['telefono']; ?>
                                    <a href="tel:<?php echo $row['telefono']; ?>" class="btn btn-sm btn-outline-primary ms-2">
                                        <i class="fas fa-phone"></i>
                                    </a>
                                </td>
                                <td>
                                    <?php echo $row['correo']; ?>
                                    <a href="mailto:<?php echo $row['correo']; ?>" class="btn btn-sm btn-outline-primary ms-2">
                                        <i class="fas fa-envelope"></i>
                                    </a>
                                </td>
                                <td><?php echo $row['total_pedidos']; ?></td>
                                <td>$<?php echo number_format($row['total_gastado'] ?? 0, 2); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="ver.php?id=<?php echo $row['id_cliente']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Ver Detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editClientModal<?php echo $row['id_cliente']; ?>" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- Modal para editar cliente -->
                                    <div class="modal fade" id="editClientModal<?php echo $row['id_cliente']; ?>" tabindex="-1" aria-labelledby="editClientModalLabel<?php echo $row['id_cliente']; ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editClientModalLabel<?php echo $row['id_cliente']; ?>">Editar Cliente</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="editar.php" method="post">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="id_cliente" value="<?php echo $row['id_cliente']; ?>">
                                                        <div class="mb-3">
                                                            <label for="nombre<?php echo $row['id_cliente']; ?>" class="form-label">Nombre</label>
                                                            <input type="text" class="form-control" id="nombre<?php echo $row['id_cliente']; ?>" name="nombre" value="<?php echo $row['nombre']; ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="telefono<?php echo $row['id_cliente']; ?>" class="form-label">Teléfono</label>
                                                            <input type="text" class="form-control" id="telefono<?php echo $row['id_cliente']; ?>" name="telefono" value="<?php echo $row['telefono']; ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="correo<?php echo $row['id_cliente']; ?>" class="form-label">Correo Electrónico</label>
                                                            <input type="email" class="form-control" id="correo<?php echo $row['id_cliente']; ?>" name="correo" value="<?php echo $row['correo']; ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="direccion<?php echo $row['id_cliente']; ?>" class="form-label">Dirección</label>
                                                            <textarea class="form-control" id="direccion<?php echo $row['id_cliente']; ?>" name="direccion" rows="3" required><?php echo $row['direccion']; ?></textarea>
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
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No hay clientes que coincidan con la búsqueda</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para agregar cliente -->
<div class="modal fade" id="addClientModal" tabindex="-1" aria-labelledby="addClientModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addClientModalLabel">Agregar Nuevo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="agregar.php" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="telefono" name="telefono" required>
                    </div>
                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="correo" name="correo" required>
                    </div>
                    <div class="mb-3">
                        <label for="direccion" class="form-label">Dirección</label>
                        <textarea class="form-control" id="direccion" name="direccion" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Incluir el footer
include_once '../../includes/footer.php';
?>