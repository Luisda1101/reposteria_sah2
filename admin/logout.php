<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

// Limpiar todas las variables de sesión
$_SESSION = array();

// Si se desea destruir la sesión completamente, también se debe borrar la cookie de sesión.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}

// Limpiar cookies de sesión personalizadas
clearSessionCookies();

// Finalmente, destruir la sesión.
session_destroy();

$basePath = env('BASE_PATH', '/reposteria_sah2');
header("Location: {$basePath}/login.php");
exit;
