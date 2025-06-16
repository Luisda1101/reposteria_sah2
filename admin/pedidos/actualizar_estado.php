<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

checkSessionValidity();
requireAdmin();

// Verificar si el usuario está logueado y es administrador
requireAdmin();

// Verificar si se recibieron los datos necesarios
if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['id_pedido']) || !isset($_POST['estado'])) {
    showAlert("Datos incompletos para actualizar el estado.", "danger");
    header("Location: index.php");
    exit;
}

$id_pedido = cleanInput($_POST['id_pedido']);
$estado = cleanInput($_POST['estado']);
$notas = isset($_POST['notas']) ? cleanInput($_POST['notas']) : '';
$notificar = isset($_POST['notificar']) ? true : false;
$redirect = isset($_POST['redirect']) ? $_POST['redirect'] : 'index.php';

// Obtener información del pedido actual
$sql_pedido = "SELECT p.*, c.nombre as cliente, c.correo 
               FROM pedidos p 
               JOIN clientes c ON p.id_cliente = c.id_cliente 
               WHERE p.id_pedido = ?";

if ($stmt_pedido = mysqli_prepare($conn, $sql_pedido)) {
    mysqli_stmt_bind_param($stmt_pedido, "i", $id_pedido);
    
    if (mysqli_stmt_execute($stmt_pedido)) {
        $result_pedido = mysqli_stmt_get_result($stmt_pedido);
        
        if (mysqli_num_rows($result_pedido) == 1) {
            $pedido = mysqli_fetch_assoc($result_pedido);
        } else {
            showAlert("No se encontró el pedido especificado.", "danger");
            header("Location: index.php");
            exit;
        }
    } else {
        showAlert("Error al obtener información del pedido.", "danger");
        header("Location: index.php");
        exit;
    }
    
    mysqli_stmt_close($stmt_pedido);
} else {
    showAlert("Error en la consulta.", "danger");
    header("Location: index.php");
    exit;
}

// Actualizar el estado del pedido
$sql = "UPDATE pedidos SET estado = ?, notas = CONCAT(IFNULL(notas, ''), ?) WHERE id_pedido = ?";

if ($stmt = mysqli_prepare($conn, $sql)) {
    // Preparar notas con formato
    $notas_formateadas = '';
    if (!empty($notas)) {
        $notas_formateadas = "\n\n[" . date('d/m/Y H:i') . " - " . $_SESSION['user_name'] . "] Cambio de estado a '" . ucfirst(str_replace('_', ' ', $estado)) . "':\n" . $notas;
    }
    
    mysqli_stmt_bind_param($stmt, "ssi", $estado, $notas_formateadas, $id_pedido);
    
    if (mysqli_stmt_execute($stmt)) {
        // Notificar al cliente si se solicitó
        if ($notificar) {
            // Preparar el mensaje de correo
            $subject = "Actualización de su pedido #" . $id_pedido . " - La Repostería Sahagún";
            
            $message = "<html><body>";
            $message .= "<h2>Actualización de su pedido #" . $id_pedido . "</h2>";
            $message .= "<p>Estimado/a " . $pedido['cliente'] . ",</p>";
            $message .= "<p>Le informamos que el estado de su pedido ha sido actualizado a: <strong>" . ucfirst(str_replace('_', ' ', $estado)) . "</strong>.</p>";
            
            if (!empty($notas)) {
                $message .= "<p><strong>Notas adicionales:</strong><br>" . nl2br($notas) . "</p>";
            }
            
            $message .= "<p>Para más detalles, puede contactarnos al teléfono o responder a este correo.</p>";
            $message .= "<p>Gracias por su preferencia.</p>";
            $message .= "<p><strong>La Repostería Sahagún</strong></p>";
            $message .= "</body></html>";
            
            // Enviar el correo
            if (sendNotificationEmail($pedido['correo'], $subject, $message)) {
                showAlert("Estado actualizado y notificación enviada correctamente.", "success");
            } else {
                showAlert("Estado actualizado, pero hubo un problema al enviar la notificación.", "warning");
            }
        } else {
            showAlert("Estado actualizado correctamente.", "success");
        }
    } else {
        showAlert("Error al actualizar el estado: " . mysqli_error($conn), "danger");
    }
    
    mysqli_stmt_close($stmt);
} else {
    showAlert("Error en la consulta de actualización.", "danger");
}

// Redirigir a la página correspondiente
header("Location: " . $redirect);
exit;
?>