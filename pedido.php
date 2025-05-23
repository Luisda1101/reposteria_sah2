<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Inicializar variables
$paso = isset($_GET['paso']) ? (int)$_GET['paso'] : 1;
$id_producto = isset($_GET['producto']) ? (int)$_GET['producto'] : 0;
$carrito = isset($_SESSION['carrito']) ? $_SESSION['carrito'] : [];

// Si se recibe un producto específico, agregarlo al carrito
if ($id_producto > 0) {
    $sql = "SELECT id_producto, nombre, precio, foto FROM productos WHERE id_producto = ? AND disponible = 1";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id_producto);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($producto = mysqli_fetch_assoc($result)) {
            // Verificar si el producto ya está en el carrito
            $encontrado = false;
            foreach ($carrito as $key => $item) {
                if ($item['id_producto'] == $id_producto) {
                    $carrito[$key]['cantidad']++;
                    $encontrado = true;
                    break;
                }
            }
            
            // Si no está en el carrito, agregarlo
            if (!$encontrado) {
                $carrito[] = [
                    'id_producto' => $producto['id_producto'],
                    'nombre' => $producto['nombre'],
                    'precio' => $producto['precio'],
                    'foto' => $producto['foto'],
                    'cantidad' => 1
                ];
            }
            
            $_SESSION['carrito'] = $carrito;
            
            // Mostrar mensaje de éxito
            $_SESSION['alert_message'] = "¡Producto agregado al carrito!";
            $_SESSION['alert_type'] = "success";
        }
        
        mysqli_stmt_close($stmt);
    }
    
    // Redirigir para evitar reenvío del formulario
    header("Location: pedido.php");
    exit;
}

// Procesar acciones del carrito
if (isset($_POST['accion'])) {
    $accion = $_POST['accion'];
    
    if ($accion == 'actualizar') {
        foreach ($_POST['cantidad'] as $id => $cantidad) {
            $cantidad = (int)$cantidad;
            if ($cantidad <= 0) {
                // Eliminar producto si la cantidad es 0 o negativa
                foreach ($carrito as $key => $item) {
                    if ($item['id_producto'] == $id) {
                        unset($carrito[$key]);
                        break;
                    }
                }
            } else {
                // Actualizar cantidad
                foreach ($carrito as $key => $item) {
                    if ($item['id_producto'] == $id) {
                        $carrito[$key]['cantidad'] = $cantidad;
                        break;
                    }
                }
            }
        }
        
        // Reindexar el array
        $carrito = array_values($carrito);
        $_SESSION['carrito'] = $carrito;
        
        // Mostrar mensaje de éxito
        $_SESSION['alert_message'] = "¡Carrito actualizado correctamente!";
        $_SESSION['alert_type'] = "success";
        
        // Redirigir para evitar reenvío del formulario
        header("Location: pedido.php");
        exit;
    } elseif ($accion == 'eliminar' && isset($_POST['id_producto'])) {
        $id_eliminar = (int)$_POST['id_producto'];
        
        foreach ($carrito as $key => $item) {
            if ($item['id_producto'] == $id_eliminar) {
                unset($carrito[$key]);
                break;
            }
        }
        
        // Reindexar el array
        $carrito = array_values($carrito);
        $_SESSION['carrito'] = $carrito;
        
        // Mostrar mensaje de éxito
        $_SESSION['alert_message'] = "¡Producto eliminado del carrito!";
        $_SESSION['alert_type'] = "success";
        
        // Redirigir para evitar reenvío del formulario
        header("Location: pedido.php");
        exit;
    } elseif ($accion == 'vaciar') {
        // Vaciar carrito
        $carrito = [];
        $_SESSION['carrito'] = $carrito;
        
        // Mostrar mensaje de éxito
        $_SESSION['alert_message'] = "¡Carrito vaciado correctamente!";
        $_SESSION['alert_type'] = "success";
        
        // Redirigir para evitar reenvío del formulario
        header("Location: pedido.php");
        exit;
    }
}

// Obtener productos para mostrar
$sql_productos = "SELECT id_producto, nombre, descripcion, precio, foto, ocasion 
                 FROM productos 
                 WHERE disponible = 1 
                 ORDER BY nombre ASC";
$result_productos = mysqli_query($conn, $sql_productos);

// Calcular total del carrito
$total_carrito = 0;
foreach ($carrito as $item) {
    $total_carrito += $item['precio'] * $item['cantidad'];
}

// Título de la página según el paso
$titulo_pagina = "Hacer Pedido";
if ($paso == 2) {
    $titulo_pagina = "Datos de Contacto";
} elseif ($paso == 3) {
    $titulo_pagina = "Confirmar Pedido";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina; ?> - La Repostería Sahagún</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link href="assets/css/frontend.css" rel="stylesheet">
    <style>
        .product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .cart-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .cart-item-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        .quantity-control {
            display: flex;
            align-items: center;
        }
        .quantity-btn {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            cursor: pointer;
        }
        .quantity-input {
            width: 50px;
            text-align: center;
            margin: 0 5px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 4px;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .step {
            flex: 1;
            text-align: center;
            padding: 10px;
            position: relative;
        }
        .step:not(:last-child):after {
            content: '';
            position: absolute;
            top: 50%;
            right: -10px;
            width: 20px;
            height: 2px;
            background-color: #dee2e6;
            transform: translateY(-50%);
        }
        .step.active {
            font-weight: bold;
            color: var(--primary-color);
        }
        .step.completed {
            color: var(--success-color);
        }
        .step-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            border-radius: 50%;
            background-color: #f8f9fa;
            margin-bottom: 5px;
        }
        .step.active .step-number {
            background-color: var(--primary-color);
            color: white;
        }
        .step.completed .step-number {
            background-color: var(--success-color);
            color: white;
        }
    </style>
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <i class="fas fa-birthday-cake text-primary me-2"></i>
            <span>La Repostería Sahagún</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
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
                    <a class="nav-link" href="nosotros.php">Nosotros</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="contacto.php">Contacto</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active btn btn-primary text-white ms-lg-3 px-3" href="pedido.php">
                        <i class="fas fa-shopping-cart me-2"></i> Hacer Pedido
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Page Content -->
<div class="container py-5">
    <h1 class="mb-4 text-center"><?php echo $titulo_pagina; ?></h1>
    
    <!-- Indicador de pasos -->
    <div class="step-indicator mb-5">
        <div class="step <?php echo $paso >= 1 ? 'active' : ''; ?> <?php echo $paso > 1 ? 'completed' : ''; ?>">
            <div class="step-number">1</div>
            <div class="step-title">Seleccionar Productos</div>
        </div>
        <div class="step <?php echo $paso >= 2 ? 'active' : ''; ?> <?php echo $paso > 2 ? 'completed' : ''; ?>">
            <div class="step-number">2</div>
            <div class="step-title">Datos de Contacto</div>
        </div>
        <div class="step <?php echo $paso >= 3 ? 'active' : ''; ?>">
            <div class="step-number">3</div>
            <div class="step-title">Confirmar Pedido</div>
        </div>
    </div>
    
    <?php if (isset($_SESSION['alert_message']) && isset($_SESSION['alert_type'])): ?>
        <div class="alert alert-<?php echo $_SESSION['alert_type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['alert_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['alert_message'], $_SESSION['alert_type']); ?>
    <?php endif; ?>
    
    <?php if ($paso == 1): ?>
        <!-- Paso 1: Seleccionar Productos -->
        <div class="row">
            <!-- Lista de Productos -->
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Selecciona tus productos</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php if (mysqli_num_rows($result_productos) > 0): ?>
                                <?php while ($producto = mysqli_fetch_assoc($result_productos)): ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card h-100 product-card">
                                            <div class="product-img-container">
                                                <?php if (!empty($producto['foto'])): ?>
                                                    <img src="assets/img/productos/<?php echo $producto['foto']; ?>" class="card-img-top" alt="<?php echo $producto['nombre']; ?>" style="height: 180px; object-fit: cover;">
                                                <?php else: ?>
                                                    <img src="assets/img/no-image.png" class="card-img-top" alt="Sin imagen" style="height: 180px; object-fit: cover;">
                                                <?php endif; ?>
                                            </div>
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo $producto['nombre']; ?></h5>
                                                <p class="card-text text-primary fw-bold">$<?php echo number_format($producto['precio'], 2); ?></p>
                                                <?php if (!empty($producto['ocasion'])): ?>
                                                    <span class="badge bg-secondary"><?php echo ucfirst($producto['ocasion']); ?></span>
                                                <?php endif; ?>
                                                <p class="card-text small text-muted mt-2"><?php echo truncateText($producto['descripcion'], 60); ?></p>
                                            </div>
                                            <div class="card-footer bg-white border-top-0">
                                                <a href="pedido.php?producto=<?php echo $producto['id_producto']; ?>" class="btn btn-outline-primary w-100">
                                                    <i class="fas fa-cart-plus me-2"></i> Agregar
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="col-12 text-center">
                                    <p>No hay productos disponibles en este momento.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Carrito -->
            <div class="col-lg-4">
                <div class="card shadow-sm sticky-top" style="top: 20px; z-index: 100;">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-shopping-cart me-2"></i> Tu Pedido
                            <span class="badge bg-primary rounded-pill ms-2"><?php echo count($carrito); ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($carrito)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                <p>Tu carrito está vacío</p>
                                <p class="small text-muted">Agrega productos para continuar</p>
                            </div>
                        <?php else: ?>
                            <form action="pedido.php" method="post">
                                <input type="hidden" name="accion" value="actualizar">
                                
                                <?php foreach ($carrito as $item): ?>
                                    <div class="cart-item">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0">
                                                <?php if (!empty($item['foto'])): ?>
                                                    <img src="assets/img/productos/<?php echo $item['foto']; ?>" class="cart-item-img" alt="<?php echo $item['nombre']; ?>">
                                                <?php else: ?>
                                                    <img src="assets/img/no-image.png" class="cart-item-img" alt="Sin imagen">
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-1"><?php echo $item['nombre']; ?></h6>
                                                <p class="text-primary mb-2">$<?php echo number_format($item['precio'], 2); ?></p>
                                                
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="quantity-control">
                                                        <button type="button" class="quantity-btn minus" data-id="<?php echo $item['id_producto']; ?>">-</button>
                                                        <input type="number" name="cantidad[<?php echo $item['id_producto']; ?>]" value="<?php echo $item['cantidad']; ?>" min="1" class="quantity-input" required>
                                                        <button type="button" class="quantity-btn plus" data-id="<?php echo $item['id_producto']; ?>">+</button>
                                                    </div>
                                                    
                                                    <button type="button" class="btn btn-sm btn-outline-danger remove-item" data-id="<?php echo $item['id_producto']; ?>">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                                
                                                <p class="text-end mb-0 mt-2">
                                                    <small class="text-muted">Subtotal: $<?php echo number_format($item['precio'] * $item['cantidad'], 2); ?></small>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-sync-alt me-2"></i> Actualizar
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" id="vaciarCarrito">
                                        <i class="fas fa-trash me-2"></i> Vaciar
                                    </button>
                                </div>
                            </form>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span>$<?php echo number_format($total_carrito, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Envío:</span>
                                <span>Calculado en el siguiente paso</span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Total:</span>
                                <span>$<?php echo number_format($total_carrito, 2); ?></span>
                            </div>
                            
                            <div class="d-grid gap-2 mt-4">
                                <a href="pedido.php?paso=2" class="btn btn-primary">
                                    Continuar <i class="fas fa-arrow-right ms-2"></i>
                                </a>
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i> Seguir Comprando
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($paso == 2): ?>
        <!-- Paso 2: Datos de Contacto -->
        <?php if (empty($carrito)): ?>
            <div class="alert alert-warning" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> Tu carrito está vacío. Por favor, agrega productos antes de continuar.
            </div>
            <div class="text-center mt-4">
                <a href="pedido.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i> Volver a Productos
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Información de Contacto</h5>
                        </div>
                        <div class="card-body">
                            <form action="procesar_pedido.php" method="post" id="formContacto">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nombre" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="telefono" class="form-label">Teléfono <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" id="telefono" name="telefono" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="correo" class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="correo" name="correo" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="direccion" class="form-label">Dirección de Entrega <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="direccion" name="direccion" rows="3" required></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="fecha_entrega" class="form-label">Fecha de Entrega <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="fecha_entrega" name="fecha_entrega" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                    <small class="text-muted">La fecha de entrega debe ser al menos un día después de hoy.</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="hora_entrega" class="form-label">Hora de Entrega <span class="text-danger">*</span></label>
                                    <select class="form-select" id="hora_entrega" name="hora_entrega" required>
                                        <option value="">Seleccionar hora</option>
                                        <?php
                                        // Generar opciones de hora de 9 AM a 7 PM
                                        for ($hora = 9; $hora <= 19; $hora++) {
                                            $hora_formato = sprintf("%02d:00", $hora);
                                            echo "<option value=\"$hora_formato\">$hora_formato</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="metodo_pago" class="form-label">Método de Pago <span class="text-danger">*</span></label>
                                    <select class="form-select" id="metodo_pago" name="metodo_pago" required>
                                        <option value="">Seleccionar método de pago</option>
                                        <option value="efectivo">Efectivo al momento de la entrega</option>
                                        <option value="transferencia">Transferencia bancaria</option>
                                        <option value="deposito">Depósito bancario</option>
                                    </select>
                                </div>
                                
                                <div id="infoTransferencia" class="alert alert-info mt-3" style="display: none;">
                                    <h6 class="alert-heading">Información para Transferencia/Depósito</h6>
                                    <p class="mb-0">
                                        <strong>Banco:</strong> Banco Ejemplo<br>
                                        <strong>Titular:</strong> La Repostería Sahagún S.A. de C.V.<br>
                                        <strong>Cuenta:</strong> 1234 5678 9012 3456<br>
                                        <strong>CLABE:</strong> 123456789012345678
                                    </p>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="comentarios" class="form-label">Comentarios o Instrucciones Especiales</label>
                                    <textarea class="form-control" id="comentarios" name="comentarios" rows="3"></textarea>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="terminos" name="terminos" required>
                                    <label class="form-check-label" for="terminos">
                                        Acepto los <a href="#" data-bs-toggle="modal" data-bs-target="#terminosModal">términos y condiciones</a> <span class="text-danger">*</span>
                                    </label>
                                </div>
                                
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="pedido.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i> Volver
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        Continuar <i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card shadow-sm sticky-top" style="top: 20px; z-index: 100;">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-shopping-cart me-2"></i> Resumen del Pedido
                                <span class="badge bg-primary rounded-pill ms-2"><?php echo count($carrito); ?></span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($carrito as $item): ?>
                                <div class="d-flex mb-3">
                                    <div class="flex-shrink-0">
                                        <?php if (!empty($item['foto'])): ?>
                                            <img src="assets/img/productos/<?php echo $item['foto']; ?>" alt="<?php echo $item['nombre']; ?>" class="rounded" width="50" height="50" style="object-fit: cover;">
                                        <?php else: ?>
                                            <img src="assets/img/no-image.png" alt="Sin imagen" class="rounded" width="50" height="50" style="object-fit: cover;">
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-0"><?php echo $item['nombre']; ?></h6>
                                        <small class="text-muted">
                                            <?php echo $item['cantidad']; ?> x $<?php echo number_format($item['precio'], 2); ?>
                                        </small>
                                        <p class="mb-0 text-end">
                                            <span class="fw-bold">$<?php echo number_format($item['precio'] * $item['cantidad'], 2); ?></span>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span>$<?php echo number_format($total_carrito, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Envío:</span>
                                <span>$50.00</span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Total:</span>
                                <span>$<?php echo number_format($total_carrito + 50, 2); ?></span>
                            </div>
                            
                            <div class="alert alert-warning mt-3">
                                <small>
                                    <i class="fas fa-info-circle me-2"></i> Los pedidos se deben realizar con al menos 24 horas de anticipación.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php elseif ($paso == 3): ?>
        <!-- Paso 3: Confirmar Pedido -->
        <div class="text-center">
            <i class="fas fa-check-circle fa-5x text-success mb-4"></i>
            <h2 class="mb-4">¡Gracias por tu pedido!</h2>
            <p class="lead">Tu pedido ha sido recibido y está siendo procesado.</p>
            <p>Hemos enviado un correo electrónico con los detalles de tu pedido a la dirección proporcionada.</p>
            <p>Número de pedido: <strong>#<?php echo isset($_GET['pedido']) ? $_GET['pedido'] : '000000'; ?></strong></p>
            
            <div class="row justify-content-center mt-5">
                <div class="col-md-6">
                    <div class="d-grid gap-3">
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i> Volver a la página principal
                        </a>
                        <a href="contacto.php" class="btn btn-outline-primary">
                            <i class="fas fa-envelope me-2"></i> Contactar con nosotros
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de Términos y Condiciones -->
<div class="modal fade" id="terminosModal" tabindex="-1" aria-labelledby="terminosModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="terminosModalLabel">Términos y Condiciones</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>1. Pedidos</h6>
                <p>Los pedidos deben realizarse con al menos 24 horas de anticipación. La confirmación del pedido está sujeta a disponibilidad.</p>
                
                <h6>2. Pagos</h6>
                <p>El pago puede realizarse en efectivo al momento de la entrega, mediante transferencia bancaria o depósito. En caso de transferencia o depósito, se requiere el comprobante de pago antes de la entrega.</p>
                
                <h6>3. Entregas</h6>
                <p>Las entregas se realizan en la dirección proporcionada por el cliente. El costo de envío es de $50.00 dentro de la zona metropolitana. Para otras zonas, el costo puede variar.</p>
                
                <h6>4. Cancelaciones</h6>
                <p>Las cancelaciones deben realizarse con al menos 12 horas de anticipación. En caso contrario, se cobrará el 50% del valor del pedido.</p>
                
                <h6>5. Devoluciones</h6>
                <p>Por la naturaleza de nuestros productos, no se aceptan devoluciones. Si existe algún problema con el pedido, por favor contáctenos inmediatamente.</p>
                
                <h6>6. Privacidad</h6>
                <p>La información proporcionada por el cliente será utilizada únicamente para procesar el pedido y no será compartida con terceros.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para eliminar producto -->
<div class="modal fade" id="eliminarProductoModal" tabindex="-1" aria-labelledby="eliminarProductoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eliminarProductoModalLabel">Eliminar Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Estás seguro de que deseas eliminar este producto de tu pedido?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form action="pedido.php" method="post" id="formEliminar">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id_producto" id="id_producto_eliminar" value="">
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para vaciar carrito -->
<div class="modal fade" id="vaciarCarritoModal" tabindex="-1" aria-labelledby="vaciarCarritoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="vaciarCarritoModalLabel">Vaciar Carrito</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Estás seguro de que deseas vaciar tu carrito? Esta acción no se puede deshacer.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form action="pedido.php" method="post">
                    <input type="hidden" name="accion" value="vaciar">
                    <button type="submit" class="btn btn-danger">Vaciar Carrito</button>
                </form>
            </div>
        </div>
    </div>
</div>

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
                <a href="login.php" class="text-white text-decoration-none">Acceso Administrador</a>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Controles de cantidad
        const minusButtons = document.querySelectorAll('.quantity-btn.minus');
        const plusButtons = document.querySelectorAll('.quantity-btn.plus');
        
        minusButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const input = document.querySelector(`input[name="cantidad[${id}]"]`);
                let value = parseInt(input.value);
                if (value > 1) {
                    input.value = value - 1;
                }
            });
        });
        
        plusButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const input = document.querySelector(`input[name="cantidad[${id}]"]`);
                let value = parseInt(input.value);
                input.value = value + 1;
            });
        });
        
        // Eliminar producto
        const removeButtons = document.querySelectorAll('.remove-item');
        removeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                document.getElementById('id_producto_eliminar').value = id;
                const modal = new bootstrap.Modal(document.getElementById('eliminarProductoModal'));
                modal.show();
            });
        });
        
        // Vaciar carrito
        const vaciarCarritoBtn = document.getElementById('vaciarCarrito');
        if (vaciarCarritoBtn) {
            vaciarCarritoBtn.addEventListener('click', function() {
                const modal = new bootstrap.Modal(document.getElementById('vaciarCarritoModal'));
                modal.show();
            });
        }
        
        // Mostrar información de transferencia
        const metodoPago = document.getElementById('metodo_pago');
        const infoTransferencia = document.getElementById('infoTransferencia');
        
        if (metodoPago && infoTransferencia) {
            metodoPago.addEventListener('change', function() {
                if (this.value === 'transferencia' || this.value === 'deposito') {
                    infoTransferencia.style.display = 'block';
                } else {
                    infoTransferencia.style.display = 'none';
                }
            });
        }
        
        // Validación del formulario de contacto
        const formContacto = document.getElementById('formContacto');
        if (formContacto) {
            formContacto.addEventListener('submit', function(event) {
                const telefono = document.getElementById('telefono').value;
                const fechaEntrega = new Date(document.getElementById('fecha_entrega').value);
                const hoy = new Date();
                
                // Validar teléfono (solo números y al menos 10 dígitos)
                if (!/^\d{10,}$/.test(telefono.replace(/\D/g, ''))) {
                    alert('Por favor, ingresa un número de teléfono válido (al menos 10 dígitos).');
                    event.preventDefault();
                    return;
                }
                
                // Validar fecha de entrega (al menos un día después)
                hoy.setHours(0, 0, 0, 0);
                const manana = new Date(hoy);
                manana.setDate(manana.getDate() + 1);
                
                if (fechaEntrega < manana) {
                    alert('La fecha de entrega debe ser al menos un día después de hoy.');
                    event.preventDefault();
                    return;
                }
            });
        }
    });
</script>
</body>
</html><?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Inicializar variables
$paso = isset($_GET['paso']) ? (int)$_GET['paso'] : 1;
$id_producto = isset($_GET['producto']) ? (int)$_GET['producto'] : 0;
$carrito = isset($_SESSION['carrito']) ? $_SESSION['carrito'] : [];

// Si se recibe un producto específico, agregarlo al carrito
if ($id_producto > 0) {
    $sql = "SELECT id_producto, nombre, precio, foto FROM productos WHERE id_producto = ? AND disponible = 1";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id_producto);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($producto = mysqli_fetch_assoc($result)) {
            // Verificar si el producto ya está en el carrito
            $encontrado = false;
            foreach ($carrito as $key => $item) {
                if ($item['id_producto'] == $id_producto) {
                    $carrito[$key]['cantidad']++;
                    $encontrado = true;
                    break;
                }
            }
            
            // Si no está en el carrito, agregarlo
            if (!$encontrado) {
                $carrito[] = [
                    'id_producto' => $producto['id_producto'],
                    'nombre' => $producto['nombre'],
                    'precio' => $producto['precio'],
                    'foto' => $producto['foto'],
                    'cantidad' => 1
                ];
            }
            
            $_SESSION['carrito'] = $carrito;
            
            // Mostrar mensaje de éxito
            $_SESSION['alert_message'] = "¡Producto agregado al carrito!";
            $_SESSION['alert_type'] = "success";
        }
        
        mysqli_stmt_close($stmt);
    }
    
    // Redirigir para evitar reenvío del formulario
    header("Location: pedido.php");
    exit;
}

// Procesar acciones del carrito
if (isset($_POST['accion'])) {
    $accion = $_POST['accion'];
    
    if ($accion == 'actualizar') {
        foreach ($_POST['cantidad'] as $id => $cantidad) {
            $cantidad = (int)$cantidad;
            if ($cantidad <= 0) {
                // Eliminar producto si la cantidad es 0 o negativa
                foreach ($carrito as $key => $item) {
                    if ($item['id_producto'] == $id) {
                        unset($carrito[$key]);
                        break;
                    }
                }
            } else {
                // Actualizar cantidad
                foreach ($carrito as $key => $item) {
                    if ($item['id_producto'] == $id) {
                        $carrito[$key]['cantidad'] = $cantidad;
                        break;
                    }
                }
            }
        }
        
        // Reindexar el array
        $carrito = array_values($carrito);
        $_SESSION['carrito'] = $carrito;
        
        // Mostrar mensaje de éxito
        $_SESSION['alert_message'] = "¡Carrito actualizado correctamente!";
        $_SESSION['alert_type'] = "success";
        
        // Redirigir para evitar reenvío del formulario
        header("Location: pedido.php");
        exit;
    } elseif ($accion == 'eliminar' && isset($_POST['id_producto'])) {
        $id_eliminar = (int)$_POST['id_producto'];
        
        foreach ($carrito as $key => $item) {
            if ($item['id_producto'] == $id_eliminar) {
                unset($carrito[$key]);
                break;
            }
        }
        
        // Reindexar el array
        $carrito = array_values($carrito);
        $_SESSION['carrito'] = $carrito;
        
        // Mostrar mensaje de éxito
        $_SESSION['alert_message'] = "¡Producto eliminado del carrito!";
        $_SESSION['alert_type'] = "success";
        
        // Redirigir para evitar reenvío del formulario
        header("Location: pedido.php");
        exit;
    } elseif ($accion == 'vaciar') {
        // Vaciar carrito
        $carrito = [];
        $_SESSION['carrito'] = $carrito;
        
        // Mostrar mensaje de éxito
        $_SESSION['alert_message'] = "¡Carrito vaciado correctamente!";
        $_SESSION['alert_type'] = "success";
        
        // Redirigir para evitar reenvío del formulario
        header("Location: pedido.php");
        exit;
    }
}

// Obtener productos para mostrar
$sql_productos = "SELECT id_producto, nombre, descripcion, precio, foto, ocasion 
                 FROM productos 
                 WHERE disponible = 1 
                 ORDER BY nombre ASC";
$result_productos = mysqli_query($conn, $sql_productos);

// Calcular total del carrito
$total_carrito = 0;
foreach ($carrito as $item) {
    $total_carrito += $item['precio'] * $item['cantidad'];
}

// Título de la página según el paso
$titulo_pagina = "Hacer Pedido";
if ($paso == 2) {
    $titulo_pagina = "Datos de Contacto";
} elseif ($paso == 3) {
    $titulo_pagina = "Confirmar Pedido";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina; ?> - La Repostería Sahagún</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link href="assets/css/frontend.css" rel="stylesheet">
    <style>
        .product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .cart-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .cart-item-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        .quantity-control {
            display: flex;
            align-items: center;
        }
        .quantity-btn {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            cursor: pointer;
        }
        .quantity-input {
            width: 50px;
            text-align: center;
            margin: 0 5px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 4px;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .step {
            flex: 1;
            text-align: center;
            padding: 10px;
            position: relative;
        }
        .step:not(:last-child):after {
            content: '';
            position: absolute;
            top: 50%;
            right: -10px;
            width: 20px;
            height: 2px;
            background-color: #dee2e6;
            transform: translateY(-50%);
        }
        .step.active {
            font-weight: bold;
            color: var(--primary-color);
        }
        .step.completed {
            color: var(--success-color);
        }
        .step-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            border-radius: 50%;
            background-color: #f8f9fa;
            margin-bottom: 5px;
        }
        .step.active .step-number {
            background-color: var(--primary-color);
            color: white;
        }
        .step.completed .step-number {
            background-color: var(--success-color);
            color: white;
        }
    </style>
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <i class="fas fa-birthday-cake text-primary me-2"></i>
            <span>La Repostería Sahagún</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
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
                    <a class="nav-link" href="nosotros.php">Nosotros</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="contacto.php">Contacto</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active btn btn-primary text-white ms-lg-3 px-3" href="pedido.php">
                        <i class="fas fa-shopping-cart me-2"></i> Hacer Pedido
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Page Content -->
<div class="container py-5">
    <h1 class="mb-4 text-center"><?php echo $titulo_pagina; ?></h1>
    
    <!-- Indicador de pasos -->
    <div class="step-indicator mb-5">
        <div class="step <?php echo $paso >= 1 ? 'active' : ''; ?> <?php echo $paso > 1 ? 'completed' : ''; ?>">
            <div class="step-number">1</div>
            <div class="step-title">Seleccionar Productos</div>
        </div>
        <div class="step <?php echo $paso >= 2 ? 'active' : ''; ?> <?php echo $paso > 2 ? 'completed' : ''; ?>">
            <div class="step-number">2</div>
            <div class="step-title">Datos de Contacto</div>
        </div>
        <div class="step <?php echo $paso >= 3 ? 'active' : ''; ?>">
            <div class="step-number">3</div>
            <div class="step-title">Confirmar Pedido</div>
        </div>
    </div>
    
    <?php if (isset($_SESSION['alert_message']) && isset($_SESSION['alert_type'])): ?>
        <div class="alert alert-<?php echo $_SESSION['alert_type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['alert_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['alert_message'], $_SESSION['alert_type']); ?>
    <?php endif; ?>
    
    <?php if ($paso == 1): ?>
        <!-- Paso 1: Seleccionar Productos -->
        <div class="row">
            <!-- Lista de Productos -->
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Selecciona tus productos</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php if (mysqli_num_rows($result_productos) > 0): ?>
                                <?php while ($producto = mysqli_fetch_assoc($result_productos)): ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card h-100 product-card">
                                            <div class="product-img-container">
                                                <?php if (!empty($producto['foto'])): ?>
                                                    <img src="assets/img/productos/<?php echo $producto['foto']; ?>" class="card-img-top" alt="<?php echo $producto['nombre']; ?>" style="height: 180px; object-fit: cover;">
                                                <?php else: ?>
                                                    <img src="assets/img/no-image.png" class="card-img-top" alt="Sin imagen" style="height: 180px; object-fit: cover;">
                                                <?php endif; ?>
                                            </div>
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo $producto['nombre']; ?></h5>
                                                <p class="card-text text-primary fw-bold">$<?php echo number_format($producto['precio'], 2); ?></p>
                                                <?php if (!empty($producto['ocasion'])): ?>
                                                    <span class="badge bg-secondary"><?php echo ucfirst($producto['ocasion']); ?></span>
                                                <?php endif; ?>
                                                <p class="card-text small text-muted mt-2"><?php echo truncateText($producto['descripcion'], 60); ?></p>
                                            </div>
                                            <div class="card-footer bg-white border-top-0">
                                                <a href="pedido.php?producto=<?php echo $producto['id_producto']; ?>" class="btn btn-outline-primary w-100">
                                                    <i class="fas fa-cart-plus me-2"></i> Agregar
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="col-12 text-center">
                                    <p>No hay productos disponibles en este momento.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Carrito -->
            <div class="col-lg-4">
                <div class="card shadow-sm sticky-top" style="top: 20px; z-index: 100;">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-shopping-cart me-2"></i> Tu Pedido
                            <span class="badge bg-primary rounded-pill ms-2"><?php echo count($carrito); ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($carrito)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                <p>Tu carrito está vacío</p>
                                <p class="small text-muted">Agrega productos para continuar</p>
                            </div>
                        <?php else: ?>
                            <form action="pedido.php" method="post">
                                <input type="hidden" name="accion" value="actualizar">
                                
                                <?php foreach ($carrito as $item): ?>
                                    <div class="cart-item">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0">
                                                <?php if (!empty($item['foto'])): ?>
                                                    <img src="assets/img/productos/<?php echo $item['foto']; ?>" class="cart-item-img" alt="<?php echo $item['nombre']; ?>">
                                                <?php else: ?>
                                                    <img src="assets/img/no-image.png" class="cart-item-img" alt="Sin imagen">
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-1"><?php echo $item['nombre']; ?></h6>
                                                <p class="text-primary mb-2">$<?php echo number_format($item['precio'], 2); ?></p>
                                                
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="quantity-control">
                                                        <button type="button" class="quantity-btn minus" data-id="<?php echo $item['id_producto']; ?>">-</button>
                                                        <input type="number" name="cantidad[<?php echo $item['id_producto']; ?>]" value="<?php echo $item['cantidad']; ?>" min="1" class="quantity-input" required>
                                                        <button type="button" class="quantity-btn plus" data-id="<?php echo $item['id_producto']; ?>">+</button>
                                                    </div>
                                                    
                                                    <button type="button" class="btn btn-sm btn-outline-danger remove-item" data-id="<?php echo $item['id_producto']; ?>">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                                
                                                <p class="text-end mb-0 mt-2">
                                                    <small class="text-muted">Subtotal: $<?php echo number_format($item['precio'] * $item['cantidad'], 2); ?></small>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-sync-alt me-2"></i> Actualizar
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" id="vaciarCarrito">
                                        <i class="fas fa-trash me-2"></i> Vaciar
                                    </button>
                                </div>
                            </form>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span>$<?php echo number_format($total_carrito, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Envío:</span>
                                <span>Calculado en el siguiente paso</span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Total:</span>
                                <span>$<?php echo number_format($total_carrito, 2); ?></span>
                            </div>
                            
                            <div class="d-grid gap-2 mt-4">
                                <a href="pedido.php?paso=2" class="btn btn-primary">
                                    Continuar <i class="fas fa-arrow-right ms-2"></i>
                                </a>
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i> Seguir Comprando
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($paso == 2): ?>
        <!-- Paso 2: Datos de Contacto -->
        <?php if (empty($carrito)): ?>
            <div class="alert alert-warning" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> Tu carrito está vacío. Por favor, agrega productos antes de continuar.
            </div>
            <div class="text-center mt-4">
                <a href="pedido.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i> Volver a Productos
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Información de Contacto</h5>
                        </div>
                        <div class="card-body">
                            <form action="procesar_pedido.php" method="post" id="formContacto">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nombre" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="telefono" class="form-label">Teléfono <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" id="telefono" name="telefono" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="correo" class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="correo" name="correo" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="direccion" class="form-label">Dirección de Entrega <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="direccion" name="direccion" rows="3" required></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="fecha_entrega" class="form-label">Fecha de Entrega <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="fecha_entrega" name="fecha_entrega" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                    <small class="text-muted">La fecha de entrega debe ser al menos un día después de hoy.</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="hora_entrega" class="form-label">Hora de Entrega <span class="text-danger">*</span></label>
                                    <select class="form-select" id="hora_entrega" name="hora_entrega" required>
                                        <option value="">Seleccionar hora</option>
                                        <?php
                                        // Generar opciones de hora de 9 AM a 7 PM
                                        for ($hora = 9; $hora <= 19; $hora++) {
                                            $hora_formato = sprintf("%02d:00", $hora);
                                            echo "<option value=\"$hora_formato\">$hora_formato</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="metodo_pago" class="form-label">Método de Pago <span class="text-danger">*</span></label>
                                    <select class="form-select" id="metodo_pago" name="metodo_pago" required>
                                        <option value="">Seleccionar método de pago</option>
                                        <option value="efectivo">Efectivo al momento de la entrega</option>
                                        <option value="transferencia">Transferencia bancaria</option>
                                        <option value="deposito">Depósito bancario</option>
                                    </select>
                                </div>
                                
                                <div id="infoTransferencia" class="alert alert-info mt-3" style="display: none;">
                                    <h6 class="alert-heading">Información para Transferencia/Depósito</h6>
                                    <p class="mb-0">
                                        <strong>Banco:</strong> Banco Ejemplo<br>
                                        <strong>Titular:</strong> La Repostería Sahagún S.A. de C.V.<br>
                                        <strong>Cuenta:</strong> 1234 5678 9012 3456<br>
                                        <strong>CLABE:</strong> 123456789012345678
                                    </p>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="comentarios" class="form-label">Comentarios o Instrucciones Especiales</label>
                                    <textarea class="form-control" id="comentarios" name="comentarios" rows="3"></textarea>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="terminos" name="terminos" required>
                                    <label class="form-check-label" for="terminos">
                                        Acepto los <a href="#" data-bs-toggle="modal" data-bs-target="#terminosModal">términos y condiciones</a> <span class="text-danger">*</span>
                                    </label>
                                </div>
                                
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="pedido.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i> Volver
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        Continuar <i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card shadow-sm sticky-top" style="top: 20px; z-index: 100;">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-shopping-cart me-2"></i> Resumen del Pedido
                                <span class="badge bg-primary rounded-pill ms-2"><?php echo count($carrito); ?></span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($carrito as $item): ?>
                                <div class="d-flex mb-3">
                                    <div class="flex-shrink-0">
                                        <?php if (!empty($item['foto'])): ?>
                                            <img src="assets/img/productos/<?php echo $item['foto']; ?>" alt="<?php echo $item['nombre']; ?>" class="rounded" width="50" height="50" style="object-fit: cover;">
                                        <?php else: ?>
                                            <img src="assets/img/no-image.png" alt="Sin imagen" class="rounded" width="50" height="50" style="object-fit: cover;">
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-0"><?php echo $item['nombre']; ?></h6>
                                        <small class="text-muted">
                                            <?php echo $item['cantidad']; ?> x $<?php echo number_format($item['precio'], 2); ?>
                                        </small>
                                        <p class="mb-0 text-end">
                                            <span class="fw-bold">$<?php echo number_format($item['precio'] * $item['cantidad'], 2); ?></span>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span>$<?php echo number_format($total_carrito, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Envío:</span>
                                <span>$50.00</span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Total:</span>
                                <span>$<?php echo number_format($total_carrito + 50, 2); ?></span>
                            </div>
                            
                            <div class="alert alert-warning mt-3">
                                <small>
                                    <i class="fas fa-info-circle me-2"></i> Los pedidos se deben realizar con al menos 24 horas de anticipación.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php elseif ($paso == 3): ?>
        <!-- Paso 3: Confirmar Pedido -->
        <div class="text-center">
            <i class="fas fa-check-circle fa-5x text-success mb-4"></i>
            <h2 class="mb-4">¡Gracias por tu pedido!</h2>
            <p class="lead">Tu pedido ha sido recibido y está siendo procesado.</p>
            <p>Hemos enviado un correo electrónico con los detalles de tu pedido a la dirección proporcionada.</p>
            <p>Número de pedido: <strong>#<?php echo isset($_GET['pedido']) ? $_GET['pedido'] : '000000'; ?></strong></p>
            
            <div class="row justify-content-center mt-5">
                <div class="col-md-6">
                    <div class="d-grid gap-3">
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i> Volver a la página principal
                        </a>
                        <a href="contacto.php" class="btn btn-outline-primary">
                            <i class="fas fa-envelope me-2"></i> Contactar con nosotros
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de Términos y Condiciones -->
<div class="modal fade" id="terminosModal" tabindex="-1" aria-labelledby="terminosModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="terminosModalLabel">Términos y Condiciones</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>1. Pedidos</h6>
                <p>Los pedidos deben realizarse con al menos 24 horas de anticipación. La confirmación del pedido está sujeta a disponibilidad.</p>
                
                <h6>2. Pagos</h6>
                <p>El pago puede realizarse en efectivo al momento de la entrega, mediante transferencia bancaria o depósito. En caso de transferencia o depósito, se requiere el comprobante de pago antes de la entrega.</p>
                
                <h6>3. Entregas</h6>
                <p>Las entregas se realizan en la dirección proporcionada por el cliente. El costo de envío es de $50.00 dentro de la zona metropolitana. Para otras zonas, el costo puede variar.</p>
                
                <h6>4. Cancelaciones</h6>
                <p>Las cancelaciones deben realizarse con al menos 12 horas de anticipación. En caso contrario, se cobrará el 50% del valor del pedido.</p>
                
                <h6>5. Devoluciones</h6>
                <p>Por la naturaleza de nuestros productos, no se aceptan devoluciones. Si existe algún problema con el pedido, por favor contáctenos inmediatamente.</p>
                
                <h6>6. Privacidad</h6>
                <p>La información proporcionada por el cliente será utilizada únicamente para procesar el pedido y no será compartida con terceros.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para eliminar producto -->
<div class="modal fade" id="eliminarProductoModal" tabindex="-1" aria-labelledby="eliminarProductoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eliminarProductoModalLabel">Eliminar Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Estás seguro de que deseas eliminar este producto de tu pedido?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form action="pedido.php" method="post" id="formEliminar">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id_producto" id="id_producto_eliminar" value="">
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para vaciar carrito -->
<div class="modal fade" id="vaciarCarritoModal" tabindex="-1" aria-labelledby="vaciarCarritoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="vaciarCarritoModalLabel">Vaciar Carrito</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Estás seguro de que deseas vaciar tu carrito? Esta acción no se puede deshacer.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form action="pedido.php" method="post">
                    <input type="hidden" name="accion" value="vaciar">
                    <button type="submit" class="btn btn-danger">Vaciar Carrito</button>
                </form>
            </div>
        </div>
    </div>
</div>

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
                <a href="login.php" class="text-white text-decoration-none">Acceso Administrador</a>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Controles de cantidad
        const minusButtons = document.querySelectorAll('.quantity-btn.minus');
        const plusButtons = document.querySelectorAll('.quantity-btn.plus');
        
        minusButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const input = document.querySelector(`input[name="cantidad[${id}]"]`);
                let value = parseInt(input.value);
                if (value > 1) {
                    input.value = value - 1;
                }
            });
        });
        
        plusButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const input = document.querySelector(`input[name="cantidad[${id}]"]`);
                let value = parseInt(input.value);
                input.value = value + 1;
            });
        });
        
        // Eliminar producto
        const removeButtons = document.querySelectorAll('.remove-item');
        removeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                document.getElementById('id_producto_eliminar').value = id;
                const modal = new bootstrap.Modal(document.getElementById('eliminarProductoModal'));
                modal.show();
            });
        });
        
        // Vaciar carrito
        const vaciarCarritoBtn = document.getElementById('vaciarCarrito');
        if (vaciarCarritoBtn) {
            vaciarCarritoBtn.addEventListener('click', function() {
                const modal = new bootstrap.Modal(document.getElementById('vaciarCarritoModal'));
                modal.show();
            });
        }
        
        // Mostrar información de transferencia
        const metodoPago = document.getElementById('metodo_pago');
        const infoTransferencia = document.getElementById('infoTransferencia');
        
        if (metodoPago && infoTransferencia) {
            metodoPago.addEventListener('change', function() {
                if (this.value === 'transferencia' || this.value === 'deposito') {
                    infoTransferencia.style.display = 'block';
                } else {
                    infoTransferencia.style.display = 'none';
                }
            });
        }
        
        // Validación del formulario de contacto
        const formContacto = document.getElementById('formContacto');
        if (formContacto) {
            formContacto.addEventListener('submit', function(event) {
                const telefono = document.getElementById('telefono').value;
                const fechaEntrega = new Date(document.getElementById('fecha_entrega').value);
                const hoy = new Date();
                
                // Validar teléfono (solo números y al menos 10 dígitos)
                if (!/^\d{10,}$/.test(telefono.replace(/\D/g, ''))) {
                    alert('Por favor, ingresa un número de teléfono válido (al menos 10 dígitos).');
                    event.preventDefault();
                    return;
                }
                
                // Validar fecha de entrega (al menos un día después)
                hoy.setHours(0, 0, 0, 0);
                const manana = new Date(hoy);
                manana.setDate(manana.getDate() + 1);
                
                if (fechaEntrega < manana) {
                    alert('La fecha de entrega debe ser al menos un día después de hoy.');
                    event.preventDefault();
                    return;
                }
            });
        }
    });
</script>
</body>
</html>