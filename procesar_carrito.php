<?php
require_once __DIR__ . '/includes/functions.php';

// Iniciar sesión
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Obtener datos del carrito desde JavaScript
$input = file_get_contents('php://input');
$carrito = json_decode($input, true);

if (is_array($carrito) && !empty($carrito)) {
    // Guardar carrito en sesión PHP
    $_SESSION['carrito'] = [];

    foreach ($carrito as $item) {
        if (isset($item['id']) && isset($item['cantidad']) && is_numeric($item['id']) && is_numeric($item['cantidad'])) {
            $_SESSION['carrito'][(int) $item['id']] = (int) $item['cantidad'];
        }
    }

    // Respuesta exitosa
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    // Error en los datos
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
}
?>