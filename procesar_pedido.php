<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar si hay productos en el carrito
if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
    $_SESSION['alert_message'] = "No hay productos en tu carrito. Por favor, agrega productos antes de continuar.";
    $_SESSION['alert_type'] = "warning";
    header("Location: pedido.php");
    exit;
}

// Verificar si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: pedido.php");
    exit;
}

// Validar campos requeridos
$campos_requeridos = ['nombre', 'telefono', 'correo', 'direccion', 'fecha_entrega', 'hora_entrega', 'metodo_pago', 'terminos'];
foreach ($campos_requeridos as $campo) {
    if (!isset($_POST[$campo]) || empty($_POST[$campo])) {
        $_SESSION['alert_message'] = "Por favor, completa todos los campos requeridos.";
        $_SESSION['alert_type'] = "danger";
        header("Location: pedido.php?paso=2");
        exit;
    }
}

// Limpiar y validar datos
$nombre = cleanInput($_POST['nombre']);
$telefono = cleanInput($_POST['telefono']);
$correo = cleanInput($_POST['correo']);
$direccion = cleanInput($_POST['direccion']);
$fecha_entrega = cleanInput($_POST['fecha_entrega']);
$hora_entrega = cleanInput($_POST['hora_entrega']);
$metodo_pago = cleanInput($_POST['metodo_pago']);
$comentarios = isset($_POST['comentarios']) ? cleanInput($_POST['comentarios']) : '';

// Validar correo electrónico
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['alert_message'] = "Por favor, ingresa un correo electrónico válido.";
    $_SESSION['alert_type'] = "danger";
    header("Location: pedido.php?paso=2");
    exit;
}

// Validar fecha de entrega (al menos un día después)
$fecha_actual = date('Y-m-d');
if ($fecha_entrega <= $fecha_actual) {
    $_SESSION['alert_message'] = "La fecha de entrega debe ser al menos un día después de hoy.";
    $_SESSION['alert_type'] = "danger";
    header("Location: pedido.php?paso=2");
    exit;
}

// Calcular total del carrito
$carrito = $_SESSION['carrito'];
$subtotal = 0;
foreach ($carrito as $item) {
    $subtotal += $item['precio'] * $item['cantidad'];
}

// Agregar costo de envío
$costo_envio = 50.00;
$total = $subtotal + $costo_envio;

// Iniciar transacción
mysqli_begin_transaction($conn);

try {
    // Verificar si el cliente ya existe
    $sql_cliente = "SELECT id_cliente FROM clientes WHERE correo = ? OR telefono = ?";
    $stmt_cliente = mysqli_prepare($conn, $sql_cliente);
    mysqli_stmt_bind_param($stmt_cliente, "ss", $correo, $telefono);
    mysqli_stmt_execute($stmt_cliente);
    $result_cliente = mysqli_stmt_get_result($stmt_cliente);
    
    if (mysqli_num_rows($result_cliente) > 0) {
        // Cliente existente
        $cliente = mysqli_fetch_assoc($result_cliente);
        $id_cliente = $cliente['id_cliente'];
        
        // Actualizar datos del cliente
        $sql_update = "UPDATE clientes SET nombre = ?, telefono = ?, direccion = ? WHERE id_cliente = ?";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "sssi", $nombre, $telefono, $direccion, $id_cliente);
        mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);
    } else {
        // Nuevo cliente
        $sql_insert = "INSERT INTO clientes (nombre, telefono, correo, direccion) VALUES (?, ?, ?, ?)";
        $stmt_insert = mysqli_prepare($conn, $sql_insert);
        mysqli_stmt_bind_param($stmt_insert, "ssss", $nombre, $telefono, $correo, $direccion);
        mysqli_stmt_execute($stmt_insert);
        $id_cliente = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt_insert);
    }
    
    mysqli_stmt_close($stmt_cliente);
    
    // Crear pedido
    $fecha_pedido = date('Y-m-d H:i:s');
    $estado = 'pendiente';
    $fecha_hora_entrega = $fecha_entrega . ' ' . $hora_entrega . ':00';
    
    $sql_pedido = "INSERT INTO pedidos (id_cliente, fecha_pedido, fecha_entrega, total, estado, metodo_pago, comentarios) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_pedido = mysqli_prepare($conn, $sql_pedido);
    mysqli_stmt_bind_param($stmt_pedido, "issdss", $id_cliente, $fecha_pedido, $fecha_hora_entrega, $total, $estado, $metodo_pago, $comentarios);
    mysqli_stmt_execute($stmt_pedido);
    $id_pedido = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt_pedido);
    
    // Insertar detalles del pedido
    foreach ($carrito as $item) {
        $id_producto = $item['id_producto'];
        $cantidad = $item['cantidad'];
        $precio = $item['precio'];
        $subtotal_item = $precio * $cantidad;
        
        $sql_detalle = "INSERT INTO detalle_pedidos (id_pedido, id_producto, cantidad, precio, subtotal) 
                       VALUES (?, ?, ?, ?, ?)";
        $stmt_detalle = mysqli_prepare($conn, $sql_detalle);
        mysqli_stmt_bind_param($stmt_detalle, "iidd", $id_pedido, $id_producto, $cantidad, $precio, $subtotal_item);
        mysqli_stmt_execute($stmt_detalle);
        mysqli_stmt_close($stmt_detalle);
    }
    
    // Confirmar transacción
    mysqli_commit($conn);
    
    // Enviar correo de confirmación
    $asunto = "Confirmación de Pedido #" . $id_pedido . " - La Repostería Sahagún";
    
    $mensaje = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #e84393; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
            th { background-color: #f8f9fa; }
            .total { font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Confirmación de Pedido</h1>
                <p>Pedido #$id_pedido</p>
            </div>
            <div class='content'>
                <p>Hola $nombre,</p>
                <p>¡Gracias por tu pedido! Hemos recibido tu solicitud y estamos procesándola.</p>
                
                <h3>Detalles del Pedido:</h3>
                <table>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio</th>
                        <th>Subtotal</th>
                    </tr>";
    
    foreach ($carrito as $item) {
        $mensaje .= "
                    <tr>
                        <td>{$item['nombre']}</td>
                        <td>{$item['cantidad']}</td>
                        <td>$" . number_format($item['precio'], 2) . "</td>
                        <td>$" . number_format($item['precio'] * $item['cantidad'], 2) . "</td>
                    </tr>";
    }
    
    $mensaje .= "
                    <tr>
                        <td colspan='3' class='total'>Subtotal</td>
                        <td>$" . number_format($subtotal, 2) . "</td>
                    </tr>
                    <tr>
                        <td colspan='3' class='total'>Envío</td>
                        <td>$" . number_format($costo_envio, 2) . "</td>
                    </tr>
                    <tr>
                        <td colspan='3' class='total'>Total</td>
                        <td>$" . number_format($total, 2) . "</td>
                    </tr>
                </table>
                
                <h3>Información de Entrega:</h3>
                <p>
                    <strong>Fecha de Entrega:</strong> " . date('d/m/Y', strtotime($fecha_entrega)) . "<br>
                    <strong>Hora de Entrega:</strong> $hora_entrega<br>
                    <strong>Dirección:</strong> $direccion<br>
                    <strong>Método de Pago:</strong> " . ($metodo_pago == 'efectivo' ? 'Efectivo al momento de la entrega' : ($metodo_pago == 'transferencia' ? 'Transferencia bancaria' : 'Depósito bancario')) . "
                </p>";
    
    if ($metodo_pago == 'transferencia' || $metodo_pago == 'deposito') {
        $mensaje .= "
                <h3>Información Bancaria:</h3>
                <p>
                    <strong>Banco:</strong> Banco Ejemplo<br>
                    <strong>Titular:</strong> La Repostería Sahagún S.A. de C.V.<br>
                    <strong>Cuenta:</strong> 1234 5678 9012 3456<br>
                    <strong>CLABE:</strong> 123456789012345678
                </p>
                <p>Por favor, envía el comprobante de pago a info@reposteriasahagun.com o a través de WhatsApp al (123) 456-7890.</p>";
    }
    
    $mensaje .= "
                <p>Si tienes alguna pregunta o necesitas hacer cambios en tu pedido, por favor contáctanos al (123) 456-7890 o responde a este correo.</p>
                
                <p>¡Gracias por elegir La Repostería Sahagún!</p>
            </div>
            <div class='footer'>
                <p>© " . date('Y') . " La Repostería Sahagún. Todos los derechos reservados.</p>
                <p>Calle Principal #123, Colonia Centro</p>
            </div>
        </div>
    </body>
    </html>";
    
    // Enviar correo
    sendNotificationEmail($correo, $asunto, $mensaje);
    
    // Enviar notificación al administrador
    $asunto_admin = "Nuevo Pedido #" . $id_pedido . " - La Repostería Sahagún";
    $mensaje_admin = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #e84393; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
            th { background-color: #f8f9fa; }
            .total { font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Nuevo Pedido</h1>
                <p>Pedido #$id_pedido</p>
            </div>
            <div class='content'>
                <p>Se ha recibido un nuevo pedido con los siguientes detalles:</p>
                
                <h3>Información del Cliente:</h3>
                <p>
                    <strong>Nombre:</strong> $nombre<br>
                    <strong>Teléfono:</strong> $telefono<br>
                    <strong>Correo:</strong> $correo<br>
                    <strong>Dirección:</strong> $direccion
                </p>
                
                <h3>Detalles del Pedido:</h3>
                <table>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio</th>
                        <th>Subtotal</th>
                    </tr>";
    
    foreach ($carrito as $item) {
        $mensaje_admin .= "
                    <tr>
                        <td>{$item['nombre']}</td>
                        <td>{$item['cantidad']}</td>
                        <td>$" . number_format($item['precio'], 2) . "</td>
                        <td>$" . number_format($item['precio'] * $item['cantidad'], 2) . "</td>
                    </tr>";
    }
    
    $mensaje_admin .= "
                    <tr>
                        <td colspan='3' class='total'>Subtotal</td>
                        <td>$" . number_format($subtotal, 2) . "</td>
                    </tr>
                    <tr>
                        <td colspan='3' class='total'>Envío</td>
                        <td>$" . number_format($costo_envio, 2) . "</td>
                    </tr>
                    <tr>
                        <td colspan='3' class='total'>Total</td>
                        <td>$" . number_format($total, 2) . "</td>
                    </tr>
                </table>
                
                <h3>Información de Entrega:</h3>
                <p>
                    <strong>Fecha de Entrega:</strong> " . date('d/m/Y', strtotime($fecha_entrega)) . "<br>
                    <strong>Hora de Entrega:</strong> $hora_entrega<br>
                    <strong>Método de Pago:</strong> " . ($metodo_pago == 'efectivo' ? 'Efectivo al momento de la entrega' : ($metodo_pago == 'transferencia' ? 'Transferencia bancaria' : 'Depósito bancario')) . "<br>
                    <strong>Comentarios:</strong> " . ($comentarios ? $comentarios : 'Ninguno') . "
                </p>
                
                <p>Puedes ver los detalles completos del pedido en el <a href='http://localhost/admin/pedidos/ver.php?id=$id_pedido'>panel de administración</a>.</p>
            </div>
            <div class='footer'>
                <p>© " . date('Y') . " La Repostería Sahagún. Todos los derechos reservados.</p>
            </div>
        </div>
    </body>
    </html>";
    
    // Enviar correo al administrador
    sendNotificationEmail('info@reposteriasahagun.com', $asunto_admin, $mensaje_admin);
    
    // Limpiar carrito
    unset($_SESSION['carrito']);
    
    // Redirigir a la página de confirmación
    header("Location: pedido.php?paso=3&pedido=" . $id_pedido);
    exit;
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    mysqli_rollback($conn);
    
    $_SESSION['alert_message'] = "Error al procesar el pedido: " . $e->getMessage();
    $_SESSION['alert_type'] = "danger";
    header("Location: pedido.php?paso=2");
    exit;
}