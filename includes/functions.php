<?php
// Cargar variables de entorno desde .env
function loadEnv($envPath = null)
{
    static $envLoaded = false;
    if ($envLoaded)
        return;
    $envLoaded = true;
    if ($envPath === null) {
        $envPath = dirname(__DIR__) . '/.env';
    }
    if (!file_exists($envPath))
        return;
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0)
            continue;
        list($name, $value) = array_map('trim', explode('=', $line, 2));
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}

// Obtener variable de entorno
function env($key, $default = null)
{
    loadEnv();
    return isset($_ENV[$key]) ? $_ENV[$key] : $default;
}

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Función para verificar si el usuario está logueado
function isLoggedIn()
{
    return isset($_SESSION["user_id"]) && !empty($_SESSION["user_id"]);
}

// Nueva función para limpiar cookies de sesión
function clearSessionCookies()
{
    $params = session_get_cookie_params();
    setcookie("user_id", '', time() - 3600, $params["path"]);
    setcookie("user_name", '', time() - 3600, $params["path"]);
    setcookie("user_role", '', time() - 3600, $params["path"]);
    setcookie("login_time", '', time() - 3600, $params["path"]);
}

// Función para cerrar sesión completamente
function logoutUser()
{
    // Destruir todas las variables de sesión
    $_SESSION = array();

    // Si se desea destruir la sesión completamente, también hay que borrar la cookie de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Destruir la sesión
    session_destroy();
}

// Función para verificar si el usuario es administrador
function isAdmin()
{
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}

// Función para redirigir si no está logueado
function requireLogin()
{
    $basePath = env('BASE_PATH', '/');
    if (!isLoggedIn()) {
        header("Location: {$basePath}/login.php");
        exit;
    }
}

// Función para redirigir si no es administrador
function requireAdmin()
{
    requireLogin();
    $basePath = env('BASE_PATH', '/reposteria_sah2');
    if (!isAdmin()) {
        header("Location: {$basePath}/index.php");
        showAlert("Acceso denegado. Solo los administradores pueden acceder a esta sección.", "danger");
        exit;
    }
}

// Función para verificar y renovar sesión en cada página
function checkSessionValidity()
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Si hay sesión pero no hay last_activity, agregarla
    if (isset($_SESSION['user_id']) && !isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
    }

    // Verificar validez de la sesión
    if (!isLoggedIn()) {
        // Redirigir a login si la sesión no es válida
        header("Location: /reposteria_sah2/login.php");
        exit;
    }
}

// Función para limpiar datos de entrada
function cleanInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Función para mostrar mensajes de alerta
function showAlert($message, $type = 'success')
{
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

// Función para mostrar la alerta si existe y luego eliminarla
function displayAlert()
{
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
function uploadImage($file, $targetDir = null)
{
    if ($targetDir === null) {
        $targetDir = env('PRODUCT_IMG_PATH', 'assets/img/productos/');
    }
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
    if ($check === false) {
        return ["success" => false, "message" => "El archivo no es una imagen."];
    }

    // Verificar el tamaño del archivo
    if ($file["size"] > 5000000) { // 5MB
        return ["success" => false, "message" => "El archivo es demasiado grande."];
    }

    // Permitir ciertos formatos de archivo
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
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
function formatDate($date, $format = 'd/m/Y')
{
    return date($format, strtotime($date));
}

// Función para obtener el estado del pedido formateado
function getOrderStatusBadge($status)
{
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
function sendNotificationEmail($to, $subject, $message)
{
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: La Repostería Sahagún <info@reposteriasahagun.com>' . "\r\n";

    return mail($to, $subject, $message);
}

// Función para generar slug
function generateSlug($text)
{
    // Reemplazar caracteres especiales
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Transliterar
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    // Eliminar caracteres no deseados
    $text = preg_replace('~[^-\w]+~', '', $text);
    // Trim
    $text = trim($text, '-');
    // Eliminar duplicados
    $text = preg_replace('~-+~', '-', $text);
    // Convertir a minúsculas
    $text = strtolower($text);

    if (empty($text)) {
        return 'n-a';
    }

    return $text;
}

// Función para obtener estado del pedido formateado
function getEstadoPedido($estado)
{
    $estados = [
        'pendiente' => ['texto' => 'Pendiente', 'clase' => 'warning'],
        'confirmado' => ['texto' => 'Confirmado', 'clase' => 'info'],
        'en_preparacion' => ['texto' => 'En Preparación', 'clase' => 'primary'],
        'listo' => ['texto' => 'Listo', 'clase' => 'success'],
        'entregado' => ['texto' => 'Entregado', 'clase' => 'success'],
        'cancelado' => ['texto' => 'Cancelado', 'clase' => 'danger']
    ];

    if (isset($estados[$estado])) {
        return $estados[$estado];
    }

    return ['texto' => 'Desconocido', 'clase' => 'secondary'];
}

// Función para obtener método de pago formateado
function getMetodoPago($metodo)
{
    $metodos = [
        'efectivo' => 'Efectivo',
        'transferencia' => 'Transferencia Bancaria',
        'nequi' => 'Nequi',
        'daviplata' => 'Daviplata',
        'deposito' => 'Depósito Bancario'
    ];

    if (isset($metodos[$metodo])) {
        return $metodos[$metodo];
    }

    return 'Otro';
}

// Función para obtener tipo de entrega formateado
function getTipoEntrega($tipo)
{
    $tipos = [
        'recoger' => 'Recoger en tienda',
        'domicilio' => 'Entrega a domicilio'
    ];

    if (isset($tipos[$tipo])) {
        return $tipos[$tipo];
    }

    return 'Otro';
}
?>