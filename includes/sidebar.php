<?php
// ...existing code...
?>
<nav id="sidebar" class="sidebar bg-dark text-white vh-100 d-flex flex-column">
    <div class="sidebar-header py-4 px-3 bg-primary text-white">
        <h3 class="mb-0">La Repostería Sahagún</h3>
    </div>
    <ul class="list-unstyled components mt-4 flex-grow-1">
        <li class="mb-2">
            <a href="<?php echo rtrim(env('BASE_PATH', '/reposteria_sah2'), '/'); ?>/admin/index.php" class="d-flex align-items-center px-3 py-2 rounded
                <?php
                // Resalta Dashboard con bg-info text-dark solo si es index.php en /admin/
                echo (basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false)
                    ? 'bg-info text-dark'
                    : 'text-white bg-dark';
                ?>">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
        </li>
        <li class="mb-2">
            <a href="<?php echo rtrim(env('BASE_PATH', '/reposteria_sah2'), '/'); ?>/admin/productos/index.php"
                class="d-flex align-items-center text-white px-3 py-2 rounded <?php echo strpos($_SERVER['PHP_SELF'], 'productos') !== false ? 'bg-primary' : 'bg-dark'; ?>">
                <i class="fas fa-birthday-cake me-2"></i> Productos
            </a>
        </li>
        <li class="mb-2">
            <a href="<?php echo rtrim(env('BASE_PATH', '/reposteria_sah2'), '/'); ?>/admin/pedidos/index.php"
                class="d-flex align-items-center text-white px-3 py-2 rounded <?php echo strpos($_SERVER['PHP_SELF'], 'pedidos') !== false ? 'bg-primary' : 'bg-dark'; ?>">
                <i class="fas fa-shopping-cart me-2"></i> Pedidos
            </a>
        </li>
        <li class="mb-2">
            <a href="<?php echo rtrim(env('BASE_PATH', '/reposteria_sah2'), '/'); ?>/admin/clientes/index.php"
                class="d-flex align-items-center text-white px-3 py-2 rounded <?php echo strpos($_SERVER['PHP_SELF'], 'clientes') !== false ? 'bg-primary' : 'bg-dark'; ?>">
                <i class="fas fa-users me-2"></i> Clientes
            </a>
        </li>
        <li class="mb-2">
            <a href="<?php echo rtrim(env('BASE_PATH', '/reposteria_sah2'), '/'); ?>/index.php"
                class="d-flex align-items-center text-white px-3 py-2 rounded bg-dark mt-4">
                <i class="fas fa-home me-2"></i> Ir al sitio principal
            </a>
        </li>
    </ul>
    <div class="mt-auto mb-3">
        <a href="<?php echo rtrim(env('BASE_PATH', '/reposteria_sah2'), '/'); ?>/admin/logout.php"
            class="d-flex align-items-center text-white px-3 py-2 rounded bg-dark">
            <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
        </a>
    </div>
</nav>
<!-- ...existing code... -->