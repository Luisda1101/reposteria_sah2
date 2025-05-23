<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Verificar si el usuario está logueado y es administrador
requireAdmin();

// Verificar si se recibieron los datos necesarios
if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['nombre']) || !isset($_POST['telefono']) || !isset($_POST['correo']) || !isset($_POST['direccion'])) {
    showAlert("Datos incompletos para agregar el cliente.", "danger");
    header("Location: index.php");
    exit;
}

// Obtener y limpiar los datos del formulario
$nombre = cleanInput($_POST['nombre']);
$telefono = cleanInput($_POST['telefono']);
$correo = cleanInput($_POST['correo']);
$direccion = cleanInput($_POST['direccion']);

// Verificar si el correo ya existe
$sql_check = "SELECT id_cliente FROM clientes WHERE correo = ?";
if ($stmt_check = mysqli_prepare($conn, $sql_check)) {
    mysqli_stmt_bind_param($stmt_check, "s", $correo);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);
    
    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        showAlert("Ya existe un cliente con ese correo electrónico.", "danger");
        header("Location: index.php");
        exit;
    }
    
    mysqli_stmt_close($stmt_check);
}

// Insertar el nuevo cliente
$sql = "INSERT INTO clientes (nombre, telefono, correo, direccion) VALUES (?, ?, ?, ?)";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ssss", $nombre, $telefono, $correo, $direccion);
    
    if (mysqli_stmt_execute($stmt)) {
        $id_cliente = mysqli_insert_id($conn);
        showAlert("Cliente agregado correctamente.", "success");
        
        // Redirigir a la página de detalles del cliente
        header("Location: ver.php?id=" . $id_cliente);
        exit;
    } else {
        showAlert("Error al agregar el cliente: " . mysqli_error($conn), "danger");
    }
    
    mysqli_stmt_close($stmt);
} else {
    showAlert("Error en la consulta.", "danger");
}

// Redirigir a la lista de clientes
header("Location: index.php");
exit;
?>