<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Verificar si el usuario está logueado y es administrador
requireAdmin();

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = cleanInput($_GET['id']);

// Obtener información del producto
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

// Procesar el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener datos del formulario
    $nombre = cleanInput($_POST['nombre']);
    $precio = cleanInput($_POST['precio']);
    $peso = cleanInput($_POST['peso']);
    $descripcion = cleanInput($_POST['descripcion']);
    $ocasion = cleanInput($_POST['ocasion']);
    $disponible = isset($_POST['disponible']) ? 1 : 0;
    
    // Validar datos
    if (empty($nombre) || empty($precio)) {
        showAlert("Por favor, complete los campos obligatorios.", "danger");
    } else {
        // Procesar la imagen si se ha subido una nueva
        $foto = $producto['foto']; // Mantener la foto actual por defecto
        
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $upload = uploadImage($_FILES['foto'], "../../assets/img/productos/");
            if ($upload['success']) {
                // Si hay una foto anterior, eliminarla
                if (!empty($producto['foto']) && file_exists("../../assets/img/productos/" . $producto['foto'])) {
                    unlink("../../assets/img/productos/" . $producto['foto']);
                }
                $foto = $upload['file_name'];
            } else {
                showAlert($upload['message'], "danger");
            }
        }
        
        // Actualizar en la base de datos
        $sql = "UPDATE productos SET nombre = ?, precio = ?, peso = ?, foto = ?, descripcion = ?, ocasion = ?, disponible = ? WHERE id_producto = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sdssssii", $nombre, $precio, $peso, $foto, $descripcion, $ocasion, $disponible, $id);
            
            if (mysqli_stmt_execute($stmt)) {
                showAlert("Producto actualizado correctamente.", "success");
                header("Location: index.php");
                exit;
            } else {
                showAlert("Error al actualizar el producto: " . mysqli_error($conn), "danger");
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}

// Incluir el header
include_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Editar Producto</h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i> Volver
    </a>
</div>

<?php displayAlert(); ?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Información del Producto</h6>
    </div>
    <div class="card-body">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $id); ?>" method="post" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nombre" class="form-label required-field">Nombre</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo $producto['nombre']; ?>" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="precio" class="form-label required-field">Precio</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="precio" name="precio" step="0.01" min="0" value="<?php echo $producto['precio']; ?>" required>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="peso" class="form-label">Peso (kg)</label>
                    <input type="number" class="form-control" id="peso" name="peso" step="0.01" min="0" value="<?php echo $producto['peso']; ?>">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="foto" class="form-label">Foto</label>
                    <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                    <div class="mt-2">
                        <?php if (!empty($producto['foto'])): ?>
                            <img id="imagePreview" src="../../assets/img/productos/<?php echo $producto['foto']; ?>" alt="<?php echo $producto['nombre']; ?>" class="product-img-preview" style="max-height: 200px;">
                        <?php else: ?>
                            <img id="imagePreview" src="#" alt="Vista previa" class="product-img-preview" style="display: none; max-height: 200px;">
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="ocasion" class="form-label">Ocasión</label>
                    <select class="form-select" id="ocasion" name="ocasion">
                        <option value="" <?php echo empty($producto['ocasion']) ? 'selected' : ''; ?>>General</option>
                        <option value="cumpleanos" <?php echo $producto['ocasion'] == 'cumpleanos' ? 'selected' : ''; ?>>Cumpleaños</option>
                        <option value="boda" <?php echo $producto['ocasion'] == 'boda' ? 'selected' : ''; ?>>Boda</option>
                        <option value="aniversario" <?php echo $producto['ocasion'] == 'aniversario' ? 'selected' : ''; ?>>Aniversario</option>
                        <option value="graduacion" <?php echo $producto['ocasion'] == 'graduacion' ? 'selected' : ''; ?>>Graduación</option>
                        <option value="baby_shower" <?php echo $producto['ocasion'] == 'baby_shower' ? 'selected' : ''; ?>>Baby Shower</option>
                        <option value="navidad" <?php echo $producto['ocasion'] == 'navidad' ? 'selected' : ''; ?>>Navidad</option>
                        <option value="san_valentin" <?php echo $producto['ocasion'] == 'san_valentin' ? 'selected' : ''; ?>>San Valentín</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="4"><?php echo $producto['descripcion']; ?></textarea>
            </div>
            
            <div class="mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="disponible" name="disponible" <?php echo $producto['disponible'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="disponible">
                        Disponible para la venta
                    </label>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="index.php" class="btn btn-secondary me-md-2">Cancelar</a>
                <button type="submit" class="btn btn-primary">Actualizar Producto</button>
            </div>
        </form>
    </div>
</div>

<?php
// Incluir el footer
include_once '../../includes/footer.php';
?>