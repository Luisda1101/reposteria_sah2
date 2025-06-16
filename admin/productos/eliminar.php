<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

checkSessionValidity();
requireAdmin();

// Verificar si el usuario está logueado y es administrador
requireAdmin();

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = cleanInput($_GET['id']);

// Verificar si el producto existe
$sql = "SELECT * FROM productos WHERE id_producto = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 1) {
            $producto = mysqli_fetch_assoc($result);
        } else {
            showAlert("No se encontró el producto especificado.", "danger");
            header("Location: index.php");
            exit;
        }
    } else {
        showAlert("Error al obtener información del producto.", "danger");
        header("Location: index.php");
        exit;
    }
    
    mysqli_stmt_close($stmt);
} else {
    showAlert("Error en la consulta.", "danger");
    header("Location: index.php");
    exit;
}

// Verificar si el producto está en algún pedido
$sql_check = "SELECT COUNT(*) as count FROM detalle_pedido WHERE id_producto = ?";
if ($stmt_check = mysqli_prepare($conn, $sql_check)) {
    mysqli_stmt_bind_param($stmt_check, "i", $id);
    
    if (mysqli_stmt_execute($stmt_check)) {
        $result_check = mysqli_stmt_get_result($stmt_check);
        $row_check = mysqli_fetch_assoc($result_check);
        
        if ($row_check['count'] > 0) {
            showAlert("No se puede eliminar el producto porque está asociado a uno o más pedidos.", "danger");
            header("Location: index.php");
            exit;
        }
    }
    
    mysqli_stmt_close($stmt_check);
}

// Procesar la eliminación
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    // Eliminar el producto de la base de datos
    $sql_delete = "DELETE FROM productos WHERE id_producto = ?";
    
    if ($stmt_delete = mysqli_prepare($conn, $sql_delete)) {
        mysqli_stmt_bind_param($stmt_delete, "i", $id);
        
        if (mysqli_stmt_execute($stmt_delete)) {
            // Si hay una foto, eliminarla
            if (!empty($producto['foto']) && file_exists("../../assets/img/productos/" . $producto['foto'])) {
                unlink("../../assets/img/productos/" . $producto['foto']);
            }
            
            showAlert("Producto eliminado correctamente.", "success");
        } else {
            showAlert("Error al eliminar el producto: " . mysqli_error($conn), "danger");
        }
        
        mysqli_stmt_close($stmt_delete);
    }
}

// Redirigir a la lista de productos
header("Location: index.php");
exit;
?>