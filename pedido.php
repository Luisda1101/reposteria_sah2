<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Inicializar sesión
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar que se haya enviado el formulario
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    // Cambia la redirección para evitar bucle infinito
    header("Location: carrito.php");
    exit;
}

// Verificar que hay productos en el carrito
if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
    $_SESSION['alert_message'] = "Tu carrito está vacío.";
    $_SESSION['alert_type'] = "warning";
    header("Location: pedido.php");
    exit;
}

// Obtener datos del formulario
$nombre = cleanInput($_POST['nombre']);
$telefono = cleanInput($_POST['telefono']);
$correo = cleanInput($_POST['correo']);
$direccion = cleanInput($_POST['direccion']);
$fecha_entrega = cleanInput($_POST['fecha_entrega']);
$hora_entrega = cleanInput($_POST['hora_entrega']);
$metodo_pago = cleanInput($_POST['metodo_pago']);
$comentarios = isset($_POST['comentarios']) ? cleanInput($_POST['comentarios']) : '';

// Validaciones básicas
$errores = [];

if (empty($nombre)) {
    $errores[] = "El nombre es requerido.";
}

if (empty($telefono)) {
    $errores[] = "El teléfono es requerido.";
}

if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $errores[] = "El correo electrónico es requerido y debe ser válido.";
}

if (empty($direccion)) {
    $errores[] = "La dirección es requerida.";
}

if (empty($fecha_entrega)) {
    $errores[] = "La fecha de entrega es requerida.";
}

if (empty($hora_entrega)) {
    $errores[] = "La hora de entrega es requerida.";
}

if (empty($metodo_pago)) {
    $errores[] = "El método de pago es requerido.";
}

// Validar fecha de entrega (debe ser al menos mañana)
$fecha_minima = date('Y-m-d', strtotime('+1 day'));
if ($fecha_entrega < $fecha_minima) {
    $errores[] = "La fecha de entrega debe ser al menos un día después de hoy.";
}

// Si hay errores, regresar al formulario
if (!empty($errores)) {
    $_SESSION['alert_message'] = implode('<br>', $errores);
    $_SESSION['alert_type'] = "danger";
    header("Location: pedido.php?paso=2");
    exit;
}

// Calcular totales
$subtotal = 0;
foreach ($_SESSION['carrito'] as $item) {
    $subtotal += $item['precio'] * $item['cantidad'];
}

$costo_envio = 50.00;
$total = $subtotal + $costo_envio;

// Iniciar transacción
mysqli_begin_transaction($conn);

try {
    // Obtener id_cliente de la sesión
    $id_cliente = isset($_SESSION['id_cliente']) ? intval($_SESSION['id_cliente']) : null;

    // Insertar el pedido principal
    $sql_pedido = "INSERT INTO pedidos (id_cliente, nombre_cliente, telefono, email, direccion, fecha_entrega, hora_entrega, tipo_entrega, metodo_pago, observaciones, total, estado, fecha_pedido) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, 'domicilio', ?, ?, ?, 'pendiente', NOW())";

    if ($stmt_pedido = mysqli_prepare($conn, $sql_pedido)) {
        mysqli_stmt_bind_param(
            $stmt_pedido,
            "issssssssd",
            $id_cliente,
            $nombre,
            $telefono,
            $correo,
            $direccion,
            $fecha_entrega,
            $hora_entrega,
            $metodo_pago,
            $comentarios,
            $total
        );

        if (mysqli_stmt_execute($stmt_pedido)) {
            $id_pedido = mysqli_insert_id($conn);

            // Insertar los detalles del pedido
            $sql_detalle = "INSERT INTO detalle_pedidos (id_pedido, id_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)";

            if ($stmt_detalle = mysqli_prepare($conn, $sql_detalle)) {
                foreach ($_SESSION['carrito'] as $item) {
                    $subtotal_item = $item['precio'] * $item['cantidad'];

                    mysqli_stmt_bind_param(
                        $stmt_detalle,
                        "iiidd",
                        $id_pedido,
                        $item['id_producto'],
                        $item['cantidad'],
                        $item['precio'],
                        $subtotal_item
                    );

                    if (!mysqli_stmt_execute($stmt_detalle)) {
                        throw new Exception("Error al insertar detalle del pedido: " . mysqli_error($conn));
                    }
                }
                mysqli_stmt_close($stmt_detalle);
            } else {
                throw new Exception("Error al preparar consulta de detalle: " . mysqli_error($conn));
            }

            // Confirmar transacción
            mysqli_commit($conn);

            // Generar mensaje para WhatsApp
            $mensaje_whatsapp = generarMensajeWhatsApp($id_pedido, $_SESSION['carrito'], $nombre, $telefono, $direccion, $fecha_entrega, $hora_entrega, $metodo_pago, $total, $comentarios);

            // Enviar correo electrónico de confirmación
            enviarCorreoConfirmacion($correo, $id_pedido, $_SESSION['carrito'], $nombre, $telefono, $direccion, $fecha_entrega, $hora_entrega, $metodo_pago, $subtotal, $costo_envio, $total, $comentarios);

            // Limpiar carrito
            unset($_SESSION['carrito']);

            // Guardar datos para la página de confirmación
            $_SESSION['pedido_confirmado'] = [
                'id_pedido' => $id_pedido,
                'mensaje_whatsapp' => $mensaje_whatsapp,
                'total' => $total,
                'nombre' => $nombre,
                'correo' => $correo
            ];

            // Redirigir a página de confirmación
            header("Location: pedido.php?paso=3&pedido=" . $id_pedido);
            exit;

        } else {
            throw new Exception("Error al insertar pedido: " . mysqli_error($conn));
        }

    } else {
        throw new Exception("Error al preparar consulta: " . mysqli_error($conn));
    }

} catch (Exception $e) {
    // Revertir transacción en caso de error
    mysqli_rollback($conn);

    $_SESSION['alert_message'] = "Error al procesar el pedido: " . $e->getMessage();
    $_SESSION['alert_type'] = "danger";
    header("Location: pedido.php?paso=2");
    exit;
}

// Función para generar mensaje de WhatsApp
function generarMensajeWhatsApp($id_pedido, $carrito, $nombre, $telefono, $direccion, $fecha_entrega, $hora_entrega, $metodo_pago, $total, $comentarios)
{
    $mensaje = "🍰 *NUEVO PEDIDO - La Repostería Sahagún* 🍰\n\n";
    $mensaje .= "📋 *Pedido #" . str_pad($id_pedido, 6, '0', STR_PAD_LEFT) . "*\n\n";

    $mensaje .= "👤 *DATOS DEL CLIENTE:*\n";
    $mensaje .= "• Nombre: " . $nombre . "\n";
    $mensaje .= "• Teléfono: " . $telefono . "\n";
    $mensaje .= "• Dirección: " . $direccion . "\n\n";

    $mensaje .= "📅 *ENTREGA:*\n";
    $mensaje .= "• Fecha: " . date('d/m/Y', strtotime($fecha_entrega)) . "\n";
    $mensaje .= "• Hora: " . $hora_entrega . "\n\n";

    $mensaje .= "🛒 *PRODUCTOS:*\n";
    foreach ($carrito as $item) {
        $mensaje .= "• " . $item['cantidad'] . "x " . $item['nombre'] . " - $" . number_format($item['precio'] * $item['cantidad'], 2) . "\n";
    }

    $mensaje .= "\n💰 *RESUMEN:*\n";
    $mensaje .= "• Subtotal: $" . number_format($total - 50, 2) . "\n";
    $mensaje .= "• Envío: $50.00\n";
    $mensaje .= "• *Total: $" . number_format($total, 2) . "*\n\n";

    $mensaje .= "💳 *Método de pago:* " . ucfirst(str_replace('_', ' ', $metodo_pago)) . "\n\n";

    if (!empty($comentarios)) {
        $mensaje .= "📝 *Comentarios:* " . $comentarios . "\n\n";
    }

    $mensaje .= "✅ *Por favor confirma la recepción de este pedido.*";

    return urlencode($mensaje);
}

// Función para enviar correo electrónico de confirmación
function enviarCorreoConfirmacion($correo, $id_pedido, $carrito, $nombre, $telefono, $direccion, $fecha_entrega, $hora_entrega, $metodo_pago, $subtotal, $costo_envio, $total, $comentarios)
{
    // Formato del número de pedido
    $numero_pedido = str_pad($id_pedido, 6, '0', STR_PAD_LEFT);

    // Asunto del correo
    $asunto = "Confirmación de Pedido #" . $numero_pedido . " - La Repostería Sahagún";

    // Cabeceras del correo
    $cabeceras = "MIME-Version: 1.0" . "\r\n";
    $cabeceras .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $cabeceras .= "From: La Repostería Sahagún <pedidos@reposteriasahagun.com>" . "\r\n";

    // Contenido HTML del correo
    $mensaje_html = '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Confirmación de Pedido</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
            }
            .header {
                background-color: #f8a5c2;
                padding: 20px;
                text-align: center;
                color: white;
                border-radius: 5px 5px 0 0;
            }
            .content {
                padding: 20px;
                border: 1px solid #ddd;
                border-top: none;
                border-radius: 0 0 5px 5px;
            }
            .footer {
                text-align: center;
                margin-top: 20px;
                font-size: 12px;
                color: #777;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }
            th, td {
                padding: 10px;
                border-bottom: 1px solid #ddd;
                text-align: left;
            }
            th {
                background-color: #f2f2f2;
            }
            .total-row {
                font-weight: bold;
                background-color: #f9f9f9;
            }
            .info-section {
                margin-bottom: 20px;
                padding-bottom: 20px;
                border-bottom: 1px solid #eee;
            }
            .info-title {
                font-weight: bold;
                color: #f8a5c2;
            }
            .button {
                display: inline-block;
                padding: 10px 20px;
                background-color: #f8a5c2;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin-top: 20px;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>¡Gracias por tu pedido!</h1>
            <p>Pedido #' . $numero_pedido . '</p>
        </div>
        <div class="content">
            <p>Hola <strong>' . $nombre . '</strong>,</p>
            <p>Hemos recibido tu pedido correctamente. A continuación, encontrarás los detalles:</p>
            
            <div class="info-section">
                <p class="info-title">Datos de Entrega:</p>
                <p><strong>Nombre:</strong> ' . $nombre . '<br>
                <strong>Teléfono:</strong> ' . $telefono . '<br>
                <strong>Dirección:</strong> ' . $direccion . '<br>
                <strong>Fecha de entrega:</strong> ' . date('d/m/Y', strtotime($fecha_entrega)) . '<br>
                <strong>Hora de entrega:</strong> ' . $hora_entrega . '<br>
                <strong>Método de pago:</strong> ' . ucfirst(str_replace('_', ' ', $metodo_pago)) . '</p>
            </div>
            
            <p class="info-title">Productos:</p>
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>';

    foreach ($carrito as $item) {
        $mensaje_html .= '
                    <tr>
                        <td>' . $item['nombre'] . '</td>
                        <td>' . $item['cantidad'] . '</td>
                        <td>$' . number_format($item['precio'], 2) . '</td>
                        <td>$' . number_format($item['precio'] * $item['cantidad'], 2) . '</td>
                    </tr>';
    }

    $mensaje_html .= '
                    <tr>
                        <td colspan="3" style="text-align: right;"><strong>Subtotal:</strong></td>
                        <td>$' . number_format($subtotal, 2) . '</td>
                    </tr>
                    <tr>
                        <td colspan="3" style="text-align: right;"><strong>Envío:</strong></td>
                        <td>$' . number_format($costo_envio, 2) . '</td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                        <td>$' . number_format($total, 2) . '</td>
                    </tr>
                </tbody>
            </table>';

    if (!empty($comentarios)) {
        $mensaje_html .= '
            <div class="info-section">
                <p class="info-title">Comentarios:</p>
                <p>' . nl2br($comentarios) . '</p>
            </div>';
    }

    $mensaje_html .= '
            <p>Nos pondremos en contacto contigo para confirmar tu pedido. Si tienes alguna pregunta, no dudes en contactarnos.</p>
            
            <p>¡Gracias por elegir La Repostería Sahagún!</p>
            
            <a href="https://reposteriasahagun.com/contacto.php" class="button">Contactar con nosotros</a>
        </div>
        <div class="footer">
            <p>© ' . date('Y') . ' La Repostería Sahagún. Todos los derechos reservados.</p>
            <p>Calle Principal #123, Colonia Centro, Sahagún, Colombia</p>
            <p>Tel: (123) 456-7890 | Email: info@reposteriasahagun.com</p>
        </div>
    </body>
    </html>';

    // Enviar el correo
    return mail($correo, $asunto, $mensaje_html, $cabeceras);
}
?>