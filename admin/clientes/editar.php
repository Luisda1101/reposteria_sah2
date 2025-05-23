<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Verificar si el usuario está logueado y es administrador
requireAdmin();

// Verificar si se recibieron los datos necesarios
if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['id_cliente']) || !isset($_POST['nombre']) || !isset($_POST['telefono']) || !isset($_POST['correo']) || !isset($_POST['direccion'])) {
    showAlert("Datos incompletos para editar el cliente.", "danger");
    header("Location: index.php");
    exit;
}

// Obtener y limpiar los datos del formulario
$id_cliente = cleanInput($_POST['id_cliente']);
$nombre = cleanInput($_POST['nombre']);
$telefono = cleanInput($_POST['telefono']);
$correo = cleanInput($_POST['correo']);
$direccion = cleanInput($_POST['direccion']);
$redirect = isset($_POST['redirect']) ? $_POST['redirect'] : 'index.php';

// Verificar si el correo ya existe (excluyendo el cliente actual)
$sql_check = "SELECT id_cliente FROM clientes WHERE correo = ? AND id_cliente != ?";
if ($stmt_check = mysqli_prepare($conn, $sql_check)) {
    mysqli_stmt_bind_param($stmt_check, "si", $correo, $id_cliente);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);
    
    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        showAlert("Ya existe otro cliente con ese correo electrónico.", "danger");
        header("Location: " . $redirect);
        exit;
    }
    
    mysqli_stmt_close($stmt_check);
}

// Actualizar el cliente
$sql = "UPDATE clientes SET nombre = ?, telefono = ?, correo = ?, direccion = ? WHERE id_cliente = ?";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ssssi", $nombre, $telefono, $correo, $direccion, $id_cliente);
    
    if (mysqli_stmt_execute($stmt)) {
        showAlert("Cliente actualizado correctamente.", "success");
    } else {
        showAlert("Error al actualizar el cliente: " . mysqli_error($conn), "danger");
    }
    
    mysqli_stmt_close($stmt);
} else {
    showAlert("Error en la consulta.", "danger");
}

// Redirigir a la página correspondiente
header("Location: " . $redirect);
exit;
?>