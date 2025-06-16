<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = cleanInput($_POST['nombre']);
    $telefono = cleanInput($_POST['telefono']);
    $correo = cleanInput($_POST['correo']);
    $direccion = cleanInput($_POST['direccion']);

    // Guardar cliente en la base de datos si lo deseas aquí, o solo en localStorage
    // Si quieres guardar en BD aquí, puedes hacerlo, pero normalmente solo se guarda al crear el pedido

    // Guardar en localStorage y redirigir a datos_pedido.php
    echo "<script>
        localStorage.setItem('clienteDatos', JSON.stringify({
            nombre: '" . addslashes($nombre) . "',
            telefono: '" . addslashes($telefono) . "',
            correo: '" . addslashes($correo) . "',
            direccion: '" . addslashes($direccion) . "'
        }));
        window.location.href = 'datos_pedido.php';
    </script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Datos del Cliente</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card p-4">
                    <h2 class="fw-bold mb-4 text-center">Datos del Cliente</h2>
                    <form method="post" id="clienteForm" autocomplete="off">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre completo</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono" required>
                        </div>
                        <div class="mb-3">
                            <label for="correo" class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control" id="correo" name="correo" required>
                        </div>
                        <div class="mb-3">
                            <label for="direccion" class="form-label">Dirección</label>
                            <input type="text" class="form-control" id="direccion" name="direccion" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                Guardar y continuar
                            </button>
                        </div>
                    </form>
                </div>
                <div class="text-center mt-4">
                    <a href="carrito.php" class="btn btn-link text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i> Volver al carrito
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Precargar datos si existen en localStorage
        document.addEventListener('DOMContentLoaded', function () {
            const datos = JSON.parse(localStorage.getItem('clienteDatos'));
            if (datos) {
                document.getElementById('nombre').value = datos.nombre || '';
                document.getElementById('telefono').value = datos.telefono || '';
                document.getElementById('correo').value = datos.correo || '';
                document.getElementById('direccion').value = datos.direccion || '';
            }
        });
    </script>
</body>

</html>