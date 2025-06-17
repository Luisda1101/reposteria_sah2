<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Repostería Sahagún - Sistema de Gestión</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link href="<?php echo rtrim(env('BASE_PATH', '/reposteria_sah2'), '/'); ?>/assets/css/styles.css" rel="stylesheet">
</head>

<body>
    <div class="wrapper">
        <?php if (isLoggedIn()): ?>
            <?php include_once __DIR__ . '/sidebar.php'; ?>
        <?php endif; ?>
        <div class="content">
            <?php if (isLoggedIn()): ?>
                <nav class="navbar navbar-expand-lg navbar-light bg-light">
                    <div class="container-fluid">
                        <!-- Botón hamburguesa para sidebar -->
                        <button type="button" id="sidebarCollapse" class="btn btn-primary me-2" aria-label="Mostrar menú">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="ms-auto">
                            <!-- Menú desplegable de usuario -->
                            <div class="dropdown">
                                <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle me-1"></i>
                                    <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                                    <li>
                                        <a class="dropdown-item"
                                            href="<?php echo rtrim(env('BASE_PATH', '/reposteria_sah2'), '/'); ?>/admin/logout.php">
                                            Cerrar Sesión
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>
                <script>
                    // Script para mostrar/ocultar el menú lateral (hamburguesa)
                    document.addEventListener('DOMContentLoaded', function () {
                        var sidebarCollapse = document.getElementById('sidebarCollapse');
                        var wrapper = document.querySelector('.wrapper');
                        if (sidebarCollapse && wrapper) {
                            sidebarCollapse.addEventListener('click', function () {
                                wrapper.classList.toggle('active');
                            });
                        }
                    });
                </script>
            <?php endif; ?>

            <div class="container-fluid p-4">
                <?php displayAlert(); ?>