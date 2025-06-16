<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Iniciar sesión
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$carrito = isset($_SESSION['carrito']) ? $_SESSION['carrito'] : [];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras - La Repostería Sahagún</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="productos.php">Productos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="carrito.php">
                            <i class="fas fa-shopping-cart me-2"></i> Carrito
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <h2 class="mb-4">Mi carrito de compras</h2>
        <?php if (empty($carrito)): ?>
            <div class="alert alert-info">Tu carrito está vacío.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio unitario</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $total = 0; ?>
                        <?php foreach ($carrito as $item): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($item['foto'])): ?>
                                        <img src="assets/img/productos/<?php echo htmlspecialchars($item['foto']); ?>"
                                            alt="<?php echo htmlspecialchars($item['nombre']); ?>" width="60">
                                    <?php else: ?>
                                        <img src="assets/img/no-image.png" alt="Sin imagen" width="60">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                                <td><?php echo $item['cantidad']; ?></td>
                                <td>$<?php echo number_format($item['precio'], 2); ?></td>
                                <td>
                                    $<?php
                                    $subtotal = $item['precio'] * $item['cantidad'];
                                    echo number_format($subtotal, 2);
                                    $total += $subtotal;
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="4" class="text-end fw-bold">Total</td>
                            <td class="fw-bold">$<?php echo number_format($total, 2); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                <a href="productos.php" class="btn btn-secondary">Seguir comprando</a>
                <button class="btn btn-primary" id="finalizarPedidoBtn">Finalizar pedido</button>
            </div>
            <script>
                document.getElementById('finalizarPedidoBtn').addEventListener('click', function () {
                    const datos = localStorage.getItem('clienteDatos');
                    if (datos) {
                        window.location.href = 'datos_pedido.php';
                    } else {
                        window.location.href = 'crear_cliente.php';
                    }
                });
            </script>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="py-5 bg-dark text-white mt-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5 class="mb-3">La Repostería Sahagún</h5>
                    <p>Endulzando momentos especiales desde 2015.</p>
                </div>
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5 class="mb-3">Contacto</h5>
                    <p><i class="fas fa-phone me-2"></i> (123) 456-7890</p>
                    <p><i class="fas fa-envelope me-2"></i> info@reposteriasahagun.com</p>
                </div>
                <div class="col-lg-4">
                    <h5 class="mb-3">Horario</h5>
                    <p>Lunes a Viernes: 9:00 AM - 7:00 PM</p>
                    <p>Sábado: 9:00 AM - 5:00 PM</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/carrito.js"></script>
</body>

</html>