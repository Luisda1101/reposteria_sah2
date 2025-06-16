<?php
session_start();

if (!isset($_GET['pedido'])) {
    header("Location: index.php");
    exit;
}

$id_pedido = intval($_GET['pedido']);

// Recuperar datos del pedido de la sesión (guardados al finalizar el pedido)
$pedido_confirmado = isset($_SESSION['pedido_confirmado']) ? $_SESSION['pedido_confirmado'] : null;

if (!$pedido_confirmado || $pedido_confirmado['id_pedido'] != $id_pedido) {
    // Si no hay datos en sesión, muestra mensaje básico
    $mensaje = "¡Tu pedido ha sido registrado exitosamente!";
    $total = '';
    $whatsapp_url = "https://wa.me/573016179642?text=Hola,%20acabo%20de%20realizar%20un%20pedido%20en%20La%20Repostería%20Sahagún.%20Mi%20número%20de%20pedido%20es%20{$id_pedido}.";
} else {
    $mensaje = "¡Tu pedido ha sido registrado exitosamente!";
    $total = number_format($pedido_confirmado['total'], 2);
    // Mensaje preformateado para WhatsApp
    $mensaje_whatsapp = isset($pedido_confirmado['mensaje_whatsapp']) ? $pedido_confirmado['mensaje_whatsapp'] : '';
    // Número de WhatsApp del administrador (ajusta el número real)
    $whatsapp_url = "https://wa.me/573016179642?text={$mensaje_whatsapp}";
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Pedido Confirmado</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
        }

        .card {
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .btn-whatsapp {
            background-color: #25d366;
            color: #fff;
            border: none;
        }

        .btn-whatsapp:hover {
            background-color: #1ebe57;
            color: #fff;
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card p-4 text-center">
                    <div class="mb-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h2 class="fw-bold mb-2">¡Pedido confirmado!</h2>
                        <p class="lead"><?php echo $mensaje; ?></p>
                    </div>
                    <div class="mb-4">
                        <h5 class="mb-1">Número de pedido:</h5>
                        <span
                            class="badge bg-primary fs-5"><?php echo str_pad($id_pedido, 6, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <?php if ($total): ?>
                        <div class="mb-4">
                            <h5 class="mb-1">Total a pagar:</h5>
                            <span class="fw-bold fs-4 text-success">$<?php echo $total; ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="mb-4">
                        <p class="mb-2">Para acordar el método de pago y confirmar tu pedido, haz clic en el botón y
                            envía tu pedido por WhatsApp al administrador.</p>
                        <a href="<?php echo $whatsapp_url; ?>" target="_blank" class="btn btn-whatsapp btn-lg">
                            <i class="fab fa-whatsapp me-2"></i>Enviar pedido por WhatsApp
                        </a>
                    </div>
                    <a href="index.php" class="btn btn-outline-secondary mt-3">
                        <i class="fas fa-home me-1"></i> Volver al inicio
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>