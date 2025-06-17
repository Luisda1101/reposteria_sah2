<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Inicializar sesión para el carrito
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Obtener productos destacados - CONSULTA CORREGIDA
$sql_destacados = "SELECT p.*, COUNT(dp.id_producto) as ventas 
                  FROM productos p 
                  LEFT JOIN detalle_pedido dp ON p.id_producto = dp.id_producto 
                  WHERE p.disponible = 1 
                  GROUP BY p.id_producto 
                  ORDER BY ventas DESC, p.id_producto DESC 
                  LIMIT 4";
$result_destacados = mysqli_query($conn, $sql_destacados);

// Obtener categorías (ocasiones) - Esta consulta está bien
$sql_categorias = "SELECT DISTINCT categoria FROM productos WHERE categoria IS NOT NULL AND categoria != '' AND disponible = 1";
$result_categorias = mysqli_query($conn, $sql_categorias);

// Contar productos en carrito
$carrito_count = isset($_SESSION['carrito']) ? count($_SESSION['carrito']) : 0;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Repostería Sahagún - Dulces Momentos, Dulces Recuerdos</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link href="assets/css/frontend.css" rel="stylesheet">
</head>

<body>
    <!-- Navbar -->
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
                        <a class="nav-link active" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="productos.php">Productos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary text-white ms-lg-3 px-3" href="carrito.php" id="carritoBtn">
                            <i class="fas fa-shopping-cart me-2"></i>
                            Carrito
                            <span class="badge bg-light text-primary ms-1"
                                id="carritoCount"><?php echo $carrito_count; ?></span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Toast Container -->
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Dulces Momentos, Dulces Recuerdos</h1>
                    <p class="lead mb-4">
                        Descubre nuestra exquisita selección de pasteles y postres artesanales para cualquier ocasión.
                        Elaborados con ingredientes de la más alta calidad y con todo el amor que mereces.
                    </p>
                    <div class="d-grid gap-3 d-sm-flex justify-content-sm-start">
                        <a href="productos.php" class="btn btn-primary btn-lg px-4">Ver Catálogo</a>
                        <a href="pedido.php" class="btn btn-outline-primary btn-lg px-4">Hacer Pedido</a>
                    </div>
                </div>
                <div class="col-lg-6 d-flex justify-content-center">
                    <img src="imgs/torta_principal.jpg" alt="Deliciosos pasteles y postres"
                        class="img-fluid rounded-3 shadow w-75">
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row mb-4">
                <div class="col-lg-8">
                    <h2 class="fw-bold">Nuestros Productos Destacados</h2>
                    <p class="text-muted">Los favoritos de nuestros clientes, elaborados con los mejores ingredientes
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="productos.php" class="btn btn-link text-primary text-decoration-none">
                        Ver todos los productos <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
            <div class="row">
                <?php if (mysqli_num_rows($result_destacados) > 0): ?>
                    <?php while ($producto = mysqli_fetch_assoc($result_destacados)): ?>
                        <div class="col-md-6 col-lg-3 mb-4">
                            <div class="card h-100 product-card">
                                <div class="product-img-container">
                                    <?php if (!empty($producto['foto'])): ?>
                                        <img src="assets/img/productos/<?php echo $producto['foto']; ?>" class="card-img-top"
                                            alt="<?php echo $producto['nombre']; ?>">
                                    <?php else: ?>
                                        <img src="assets/img/no-image.png" class="card-img-top" alt="Sin imagen">
                                    <?php endif; ?>
                                    <div class="product-overlay">
                                        <a href="producto.php?id=<?php echo $producto['id_producto']; ?>"
                                            class="btn btn-sm btn-primary">Ver Detalles</a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $producto['nombre']; ?></h5>
                                    <p class="card-text text-primary fw-bold">
                                        $<?php echo number_format($producto['precio'], 2); ?></p>
                                    <?php if (!empty($producto['ocasion'])): ?>
                                        <span class="badge bg-secondary"><?php echo ucfirst($producto['ocasion']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer bg-white border-top-0">
                                    <a href="#" class="btn btn-outline-primary w-100 agregar-carrito"
                                        data-id="<?php echo $producto['id_producto']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                        data-precio="<?php echo $producto['precio']; ?>"
                                        data-foto="<?php echo $producto['foto']; ?>">
                                        <i class="fas fa-shopping-cart me-2"></i> Agregar al Carrito
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <p>No hay productos destacados disponibles en este momento.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Categories -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Categorías</h2>
                <p class="text-muted">Encuentra el pastel perfecto para cada ocasión</p>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-6 col-md-4">
                    <a href="productos.php?categoria=tortas" class="text-decoration-none">
                        <div class="card h-100 text-center category-card">
                            <div class="card-body">
                                <div class="category-icon">
                                    <i class="fas fa-birthday-cake"></i>
                                </div>
                                <h5 class="card-title mt-3">Tortas</h5>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-4">
                    <a href="productos.php?categoria=cupcakes" class="text-decoration-none">
                        <div class="card h-100 text-center category-card">
                            <div class="card-body">
                                <div class="category-icon">
                                    <i class="fas fa-cookie-bite"></i>
                                </div>
                                <h5 class="card-title mt-3">Cupcakes</h5>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-4">
                    <a href="productos.php?categoria=pasteles" class="text-decoration-none">
                        <div class="card h-100 text-center category-card">
                            <div class="card-body">
                                <div class="category-icon">
                                    <i class="fas fa-ice-cream"></i>
                                </div>
                                <h5 class="card-title mt-3">Pasteles</h5>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- About Us -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <img src="assets/img/about-us.png" alt="Nuestro equipo" class="img-fluid rounded-3 shadow">
                </div>
                <div class="col-lg-6">
                    <h2 class="fw-bold mb-4">Sobre La Repostería Sahagún</h2>
                    <p class="mb-4">Desde 2015, La Repostería Sahagún ha estado creando deliciosos pasteles y postres
                        artesanales con las mejores materias primas y recetas tradicionales que han pasado de generación
                        en generación.</p>
                    <p class="mb-4">Nuestro compromiso es ofrecer productos de la más alta calidad, elaborados con amor
                        y dedicación para hacer de cada ocasión un momento especial y memorable.</p>
                    <a href="nosotros.php" class="btn btn-outline-primary">Conoce nuestra historia</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Lo que dicen nuestros clientes</h2>
                <p class="text-muted">Opiniones de quienes han disfrutado nuestros productos</p>
            </div>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card h-100 testimonial-card">
                        <div class="card-body">
                            <div class="testimonial-rating mb-3">
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                            </div>
                            <p class="card-text">"El pastel de cumpleaños que pedí superó todas mis expectativas. No
                                solo era hermoso, sino que también estaba delicioso. ¡Todos mis invitados quedaron
                                encantados!"</p>
                        </div>
                        <div class="card-footer bg-white">
                            <div class="d-flex align-items-center">
                                <div class="testimonial-avatar">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0">María González</h6>
                                    <small class="text-muted">Cliente desde 2020</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 testimonial-card">
                        <div class="card-body">
                            <div class="testimonial-rating mb-3">
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                            </div>
                            <p class="card-text">"He probado muchos pasteles, pero los de La Repostería Sahagún son
                                simplemente los mejores. El sabor es incomparable y el servicio es excelente."</p>
                        </div>
                        <div class="card-footer bg-white">
                            <div class="d-flex align-items-center">
                                <div class="testimonial-avatar">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0">Carlos Ramírez</h6>
                                    <small class="text-muted">Cliente desde 2019</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 testimonial-card">
                        <div class="card-body">
                            <div class="testimonial-rating mb-3">
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star-half-alt text-warning"></i>
                            </div>
                            <p class="card-text">"Pedí cupcakes para el baby shower de mi hermana y fueron un éxito
                                total. La decoración era preciosa y el sabor increíble. Definitivamente volveré a
                                ordenar."</p>
                        </div>
                        <div class="card-footer bg-white">
                            <div class="d-flex align-items-center">
                                <div class="testimonial-avatar">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0">Laura Mendoza</h6>
                                    <small class="text-muted">Cliente desde 2021</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-5 bg-primary text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 mb-4 mb-lg-0">
                    <h2 class="fw-bold">¿Listo para endulzar tu día?</h2>
                    <p class="lead mb-0">Haz tu pedido ahora y disfruta de nuestros deliciosos pasteles y postres
                        artesanales. Entrega a domicilio disponible.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="pedido.php" class="btn btn-light btn-lg">
                        <i class="fas fa-shopping-cart me-2"></i> Hacer Pedido
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
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
                    <p><i class="fas fa-map-marker-alt me-2"></i> Barrio Nueva Granada, Transversal 3A</p>
                    <p><i class="fas fa-phone me-2"></i> 3016179642</p>
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
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> La Repostería Sahagún. Todos los derechos
                        reservados.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="<?php echo rtrim(env('BASE_PATH', '/reposteria_sah2'), '/'); ?>/login.php"
                        class="text-white text-decoration-none">Acceso Administrador</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper (debe estar antes de </body>) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Manejar clic en "Agregar al carrito"
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
                                // Actualizar contador
                                document.getElementById('carritoCount').textContent = data.carrito_count;
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
</body>

</html>