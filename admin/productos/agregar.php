<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';

checkSessionValidity();
requireAdmin();

// Verificar si el usuario está logueado y es administrador
requireAdmin();

// Procesar el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener datos del formulario
    $nombre = cleanInput($_POST['nombre']);
    $precio = cleanInput($_POST['precio']);
    $peso = cleanInput($_POST['peso']);
    $descripcion = cleanInput($_POST['descripcion']);
    $foto = $_FILES['foto'];
    $categoria = cleanInput($_POST['categoria']);
    $disponible = isset($_POST['disponible']) ? 1 : 0;
    
    // Validar datos
    if (empty($nombre) || empty($precio )) {
        showAlert("Por favor, complete los campos obligatorios.", "danger");
    } else {
        // Procesar la imagen si se ha subido
        $foto = "";
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $upload = uploadImage($_FILES['foto'], "../../assets/img/productos/");
            if ($upload['success']) {
                $foto = $upload['file_name'];
            } else {
                showAlert($upload['message'], "danger");
            }
        }
        
        // Insertar en la base de datos
        $sql = "INSERT INTO productos (nombre, precio, peso, foto, descripcion, categoria, disponible) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sdssssi", $nombre, $precio, $peso, $foto, $descripcion, $categoria, $disponible);
            
            if (mysqli_stmt_execute($stmt)) {
                showAlert("Producto agregado correctamente.", "success");
                header("Location: index.php");
                exit;
            } else {
                showAlert("Error al agregar el producto: " . mysqli_error($conn), "danger");
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}

// Incluir el header
include_once '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Agregar Nuevo Producto</h1>
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
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nombre" class="form-label required-field">Nombre</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="precio" class="form-label required-field">Precio</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="precio" name="precio" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="peso" class="form-label">Peso (kg)</label>
                    <input type="number" class="form-control" id="peso" name="peso" step="0.01" min="0">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="foto" class="form-label">Foto</label>
                    <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                    <div class="mt-2">
                        <img id="imagePreview" src="#" alt="Vista previa" class="product-img-preview" style="display: none; max-height: 200px;">
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="categoria" class="form-label">Categoríaa</label>
                    <select class="form-select" id="categoria" name="categoria">
                        <option value="">General</option>
                        <option value="tortas">Tortas</option>
                        <option value="cupcakes">Cupcakes</option>
                        <option value="postres">Postres</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="4"></textarea>
            </div>
            
            <div class="mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="disponible" name="disponible" checked>
                    <label class="form-check-label" for="disponible">
                        Disponible para la venta
                    </label>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="index.php" class="btn btn-secondary me-md-2">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar Producto</button>
            </div>
        </form>
    </div>
</div>

<?php
// Incluir el footer
include_once '../../includes/footer.php';
?>