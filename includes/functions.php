<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Función para verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Función para verificar si el usuario es administrador
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'administrador';
}

// Función para redirigir si no está logueado
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /reposteria_sah2/login.php");
        exit;
    }
}

// Función para redirigir si no es administrador
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: /reposteria_sah2/index.php");
        showAlert("Acceso denegado. Solo los administradores pueden acceder a esta sección.", "danger");
        exit;
    }
}

// Función para limpiar datos de entrada
function cleanInput($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    if ($conn) {
        $data = mysqli_real_escape_string($conn, $data);
    }
    return $data;
}

// Función para mostrar mensajes de alerta
function showAlert($message, $type = 'success') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

// Función para mostrar la alerta si existe y luego eliminarla
function displayAlert() {
    if (isset($_SESSION['alert'])) {
        $alertType = $_SESSION['alert']['type'];
        $alertMessage = $_SESSION['alert']['message'];
        echo "<div class='alert alert-{$alertType} alert-dismissible fade show' role='alert'>
                {$alertMessage}
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
        unset($_SESSION['alert']);
    }
}

// Función para subir imágenes
function uploadImage($file, $targetDir = "../assets/img/productos/") {
    // Verificar si el directorio existe, si no, crearlo
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $targetFile = $targetDir . basename($file["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    $newFileName = uniqid() . '.' . $imageFileType;
    $targetFile = $targetDir . $newFileName;
    
    // Verificar si el archivo es una imagen real
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        return ["success" => false, "message" => "El archivo no es una imagen."];
    }
    
    // Verificar el tamaño del archivo
    if ($file["size"] > 5000000) { // 5MB
        return ["success" => false, "message" => "El archivo es demasiado grande."];
    }
    
    // Permitir ciertos formatos de archivo
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
        return ["success" => false, "message" => "Solo se permiten archivos JPG, JPEG, PNG y GIF."];
    }
    
    // Intentar subir el archivo
    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return ["success" => true, "file_name" => $newFileName];
    } else {
        return ["success" => false, "message" => "Hubo un error al subir el archivo."];
    }
}

// Función para formatear la fecha
function formatDate($date) {
    return date("d/m/Y H:i", strtotime($date));
}

// Función para obtener el estado del pedido formateado
function getOrderStatusBadge($status) {
    switch ($status) {
        case 'pendiente':
            return '<span class="badge bg-warning text-dark">Pendiente</span>';
        case 'en_proceso':
            return '<span class="badge bg-info text-dark">En Proceso</span>';
        case 'completado':
            return '<span class="badge bg-primary">Completado</span>';
        case 'entregado':
            return '<span class="badge bg-success">Entregado</span>';
        case 'cancelado':
            return '<span class="badge bg-danger">Cancelado</span>';
        default:
            return '<span class="badge bg-secondary">Desconocido</span>';
    }
}

// Función para enviar correo de notificación
function sendNotificationEmail($to, $subject, $message) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: La Repostería Sahagún <info@reposteriasahagun.com>' . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}
?>