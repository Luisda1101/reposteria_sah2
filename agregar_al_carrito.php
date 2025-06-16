<?php
session_start();
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$id = $data['id'];
$nombre = $data['nombre'];
$precio = floatval($data['precio']);
$foto = $data['foto'];

// Si el producto ya está en el carrito, suma cantidad
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}
$encontrado = false;
foreach ($_SESSION['carrito'] as &$item) {
    if ($item['id_producto'] == $id) {
        $item['cantidad'] += 1;
        $encontrado = true;
        break;
    }
}
unset($item);

if (!$encontrado) {
    $_SESSION['carrito'][] = [
        'id_producto' => $id,
        'nombre' => $nombre,
        'precio' => $precio,
        'foto' => $foto,
        'cantidad' => 1
    ];
}

echo json_encode([
    'success' => true,
    'message' => 'Producto agregado con éxito',
    'carrito_count' => count($_SESSION['carrito'])
]);
