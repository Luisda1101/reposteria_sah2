<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Iniciar sesión
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar si ya está logueado
if (isLoggedIn()) {
    header("Location: admin/index.php");
    exit;
}

// Procesar el formulario de login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = cleanInput($_POST["username"]);
    $password = $_POST["password"];
    
    // Validar campos
    if (empty($username) || empty($password)) {
        showAlert("Por favor, complete todos los campos.", "danger");
    } else {
        // Consultar la base de datos
        $sql = "SELECT id_usuario, usuario, password, nombre, rol FROM usuarios WHERE usuario = ? AND activo = 1";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                // Verificar si el usuario existe
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $name, $role);
                    
                    if (mysqli_stmt_fetch($stmt)) {
                        // Verificar la contraseña
                        echo "HASH EN BD: " . $hashed_password;
                        if (password_verify($password, $hashed_password)) {
                            // Contraseña correcta, iniciar sesión
                            $_SESSION["user_id"] = $id;
                            $_SESSION["user_name"] = $name;
                            $_SESSION["user_role"] = $role;
                            
                            // Redirigir según el rol
                            header("Location: admin/index.php");
                            exit;
                        } else {
                            showAlert("La contraseña ingresada no es válida.", "danger");
                        }
                    }
                } else {
                    showAlert("No se encontró una cuenta con ese nombre de usuario.", "danger");
                }
            } else {
                showAlert("Oops! Algo salió mal. Por favor, inténtelo de nuevo más tarde.", "danger");
            }
            
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - La Repostería Sahagún</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="login-container">
            <div class="login-logo">
                <i class="fas fa-birthday-cake fa-4x text-primary"></i>
                <h2 class="mt-3">La Repostería Sahagún</h2>
                <p class="text-muted">Sistema de Gestión</p>
            </div>
            
            <?php displayAlert(); ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="mb-3">
                    <label for="username" class="form-label">Usuario</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">Iniciar Sesión</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>