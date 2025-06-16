<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirigir si ya está logueado
if (isLoggedIn()) {
    header("Location: admin/index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = cleanInput($_POST["username"]);
    $name = cleanInput($_POST["name"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $role = cleanInput($_POST["role"]);

    if (empty($username) || empty($name) || empty($password) || empty($confirm_password) || empty($role)) {
        showAlert("Todos los campos son obligatorios.", "danger");
    } elseif ($password !== $confirm_password) {
        showAlert("Las contraseñas no coinciden.", "danger");
    } else {
        // Verificar si el usuario ya existe
        $check_sql = "SELECT id_usuario FROM usuario WHERE usuario = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "s", $username);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);

        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            showAlert("El nombre de usuario ya está en uso.", "danger");
        } else {
            // Registrar el usuario
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $insert_sql = "INSERT INTO usuario (usuario, contrasena, nombre, rol, estado) VALUES (?, ?, ?, ?, 1)";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "ssss", $username, $hashed_password, $name, $role);

            if (mysqli_stmt_execute($insert_stmt)) {
                // Redirigir al login después de registrar correctamente
                header("Location: login.php?registered=1");
                exit;
            } else {
                showAlert("Error al registrar el usuario. Intente más tarde.", "danger");
            }

            mysqli_stmt_close($insert_stmt);
        }

        mysqli_stmt_close($check_stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Registro - La Repostería Sahagún</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container">
        <div class="login-container">
            <div class="login-logo">
                <i class="fas fa-user-plus fa-4x text-success"></i>
                <h2 class="mt-3">Registro de Usuario</h2>
                <p class="text-muted">Cree una nueva cuenta</p>
            </div>

            <?php displayAlert(); ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="mb-3">
                    <label for="username" class="form-label">Usuario</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="name" class="form-label">Nombre completo</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">Rol</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="">Seleccione un rol</option>
                        <option value="admin">Administrador</option>
                        <option value="usuario">Usuario</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-success btn-lg">Registrar</button>
                </div>
                <div class="mt-3 text-center">
                    <a href="login.php" class="text-muted">¿Ya tienes cuenta? Inicia sesión</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>