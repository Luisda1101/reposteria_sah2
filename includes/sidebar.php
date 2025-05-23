<nav id="sidebar" class="bg-dark">
    <div class="sidebar-header">
        <h3 class="text-light">La Repostería Sahagún</h3>
    </div>

    <ul class="list-unstyled components ">
        <li class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/index.php') !== false) ? 'active' : ''; ?>">
            <a href="/reposteria_sah2/admin/index.php">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
        </li>
        <?php if (isAdmin()): ?>
            <li class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/productos') !== false) ? 'active' : ''; ?>">
                <a href="/reposteria_sah2/admin/productos/index.php">
                    <i class="fas fa-birthday-cake me-2"></i> Productos
                </a>
            </li>
            <li class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/pedidos') !== false) ? 'active' : ''; ?>">
                <a href="/reposteria_sah2/admin/pedidos/index.php">
                    <i class="fas fa-shopping-cart me-2"></i> Pedidos
                </a>
            </li>
            <li class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/clientes') !== false) ? 'active' : ''; ?>">
                <a href="/reposteria_sah2/admin/clientes/index.php">
                    <i class="fas fa-users me-2"></i> Clientes
                </a>
            </li>
            <li class="<?php echo (strpos($_SERVER['REQUEST_URI'], '/index.php') !== false) ? 'active' : ''; ?>">
                <a href="/reposteria_sah2/index.php">
                    <i class="fas fa-users me-2"></i> Inicio
                </a>
            </li>
        <?php endif; ?>
    </ul>
</nav>