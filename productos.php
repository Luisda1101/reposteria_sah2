<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Consulta productos por categoría
function obtenerProductosPorCategoria($conn, $categoria)
{
    $sql = "SELECT * FROM productos WHERE categoria = ? AND disponible = 1 ORDER BY nombre ASC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $categoria);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $productos = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $productos[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $productos;
}

$tortas = obtenerProductosPorCategoria($conn, 'tortas');
$cupcakes = obtenerProductosPorCategoria($conn, 'cupcakes');
$postres = obtenerProductosPorCategoria($conn, 'postres');
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - La Repostería Sahagún</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="assets/css/frontend.css" rel="stylesheet">
</head>

<body>
    <!-- Navbar (copiado de index.php) -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="fas fa-birthday-cake text-primary me-2"></i>
                <span>La Repostería Sahagún</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="productos.php">Productos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="nosotros.php">Nosotros</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contacto.php">Contacto</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary text-white ms-lg-3 px-3" href="carrito.php" id="carritoBtn">
                            <i class="fas fa-shopping-cart me-2"></i>
                            Carrito
                            <span class="badge bg-light text-primary ms-1"
                                id="carritoCount"><?php echo isset($_SESSION['carrito']) ? count($_SESSION['carrito']) : 0; ?></span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <h1 class="mb-5 text-center fw-bold">Nuestros Productos</h1>

        <!-- Sección Tortas -->
        <section class="mb-4">
            <h2 class="mb-3 text-primary"><i class="fas fa-birthday-cake me-2"></i>Tortas</h2>
            <div class="row row-cols-2 row-cols-md-4 g-2">
                <?php if (count($tortas) > 0): ?>
                    <?php foreach ($tortas as $producto): ?>
                        <div class="col">
                            <div class="card h-100" style="max-width: 220px; margin: 0 auto;">
                                <?php if (!empty($producto['foto'])): ?>
                                    <img src="assets/img/productos/<?php echo $producto['foto']; ?>" class="card-img-top"
                                        style="width:100%; height:140px; object-fit:cover;" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                                <?php else: ?>
                                    <img src="assets/img/no-image.png" class="card-img-top"
                                        style="width:100%; height:140px; object-fit:cover;" alt="Sin imagen">
                                <?php endif; ?>
                                <div class="card-body p-2">
                                    <h5 class="card-title fs-6 mb-1"><?php echo htmlspecialchars($producto['nombre']); ?></h5>
                                    <p class="card-text small mb-1"><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                                    <p class="fw-bold text-primary mb-1">$<?php echo number_format($producto['precio'], 2); ?></p>
                                </div>
                                <div class="card-footer bg-white border-top-0 p-2">
                                    <a href="#" class="btn btn-outline-primary w-100 btn-sm agregar-carrito"
                                        data-id="<?php echo $producto['id_producto']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                        data-precio="<?php echo $producto['precio']; ?>"
                                        data-foto="<?php echo $producto['foto']; ?>">
                                        <i class="fas fa-shopping-cart me-2"></i> Agregar al Carrito
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info">No hay tortas disponibles.</div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Sección Cupcakes -->
        <section class="mb-4">
            <h2 class="mb-3 text-success"><i class="fas fa-cookie-bite me-2"></i>Cupcakes</h2>
            <div class="row row-cols-2 row-cols-md-4 g-2">
                <?php if (count($cupcakes) > 0): ?>
                    <?php foreach ($cupcakes as $producto): ?>
                        <div class="col">
                            <div class="card h-100" style="max-width: 220px; margin: 0 auto;">
                                <?php if (!empty($producto['foto'])): ?>
                                    <img src="assets/img/productos/<?php echo $producto['foto']; ?>" class="card-img-top"
                                        style="width:100%; height:140px; object-fit:cover;" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                                <?php else: ?>
                                    <img src="assets/img/no-image.png" class="card-img-top"
                                        style="width:100%; height:140px; object-fit:cover;" alt="Sin imagen">
                                <?php endif; ?>
                                <div class="card-body p-2">
                                    <h5 class="card-title fs-6 mb-1"><?php echo htmlspecialchars($producto['nombre']); ?></h5>
                                    <p class="card-text small mb-1"><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                                    <p class="fw-bold text-success mb-1">$<?php echo number_format($producto['precio'], 2); ?></p>
                                </div>
                                <div class="card-footer bg-white border-top-0 p-2">
                                    <a href="#" class="btn btn-outline-success w-100 btn-sm agregar-carrito"
                                        data-id="<?php echo $producto['id_producto']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                        data-precio="<?php echo $producto['precio']; ?>"
                                        data-foto="<?php echo $producto['foto']; ?>">
                                        <i class="fas fa-shopping-cart me-2"></i> Agregar al Carrito
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info">No hay cupcakes disponibles.</div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Sección Postres -->
        <section>
            <h2 class="mb-3 text-warning"><i class="fas fa-ice-cream me-2"></i>Postres</h2>
            <div class="row row-cols-2 row-cols-md-4 g-2">
                <?php if (count($postres) > 0): ?>
                    <?php foreach ($postres as $producto): ?>
                        <div class="col">
                            <div class="card h-100" style="max-width: 220px; margin: 0 auto;">
                                <?php if (!empty($producto['foto'])): ?>
                                    <img src="assets/img/productos/<?php echo $producto['foto']; ?>" class="card-img-top"
                                        style="width:100%; height:140px; object-fit:cover;" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                                <?php else: ?>
                                    <img src="assets/img/no-image.png" class="card-img-top"
                                        style="width:100%; height:140px; object-fit:cover;" alt="Sin imagen">
                                <?php endif; ?>
                                <div class="card-body p-2">
                                    <h5 class="card-title fs-6 mb-1"><?php echo htmlspecialchars($producto['nombre']); ?></h5>
                                    <p class="card-text small mb-1"><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                                    <p class="fw-bold text-warning mb-1">$<?php echo number_format($producto['precio'], 2); ?></p>
                                </div>
                                <div class="card-footer bg-white border-top-0 p-2">
                                    <a href="#" class="btn btn-outline-warning w-100 btn-sm agregar-carrito"
                                        data-id="<?php echo $producto['id_producto']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                        data-precio="<?php echo $producto['precio']; ?>"
                                        data-foto="<?php echo $producto['foto']; ?>">
                                        <i class="fas fa-shopping-cart me-2"></i> Agregar al Carrito
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info">No hay postres disponibles.</div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <!-- Toast para feedback del carrito (opcional, igual que en index.php) -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
        <div id="carritoToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas fa-shopping-cart text-primary me-2"></i>
                <strong class="me-auto">Carrito</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="carritoToastBody">
                Producto agregado al carrito
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.agregar-carrito').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    const id = this.dataset.id;
                    const nombre = this.dataset.nombre;
                    const precio = this.dataset.precio;
                    const foto = this.dataset.foto;

                    fetch('agregar_al_carrito.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id, nombre, precio, foto })
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                // Actualizar contador si existe en la página
                                if (document.getElementById('carritoCount')) {
                                    document.getElementById('carritoCount').textContent = data.carrito_count;
                                }
                                // Mostrar toast
                                document.getElementById('carritoToastBody').textContent = data.message;
                                const toast = new bootstrap.Toast(document.getElementById('carritoToast'));
                                toast.show();
                            }
                        });
                });
            });
        });
    </script>

    <!-- Footer (copiado de index.php) -->
    <footer class="py-5 bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5 class="mb-3">La Repostería Sahagún</h5>
                    <p>Endulzando momentos especiales desde 2015.</p>
                    <div class="social-icons">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5 class="mb-3">Contacto</h5>
                    <p><i class="fas fa-map-marker-alt me-2"></i> Calle Principal #123, Colonia Centro</p>
                    <p><i class="fas fa-phone me-2"></i> (123) 456-7890</p>
                    <p><i class="fas fa-envelope me-2"></i> info@reposteriasahagun.com</p>
                </div>
                <div class="col-lg-4">
                    <h5 class="mb-3">Horario</h5>
                    <p>Lunes a Viernes: 9:00 AM - 7:00 PM</p>
                    <p>Sábado: 9:00 AM - 5:00 PM</p>
                    <p>Domingo: 10:00 AM - 2:00 PM</p>
                </div>
            </div>
            <hr class="my-4">
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> La Repostería Sahagún. Todos los derechos reservados.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="<?php echo rtrim(env('BASE_PATH', '/reposteria_sah2'), '/'); ?>/login.php"
                        class="text-white text-decoration-none">Acceso Administrador</a>
                </div>
            </div>
        </div>
    </footer>
</body>

</html>