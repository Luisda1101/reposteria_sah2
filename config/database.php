<?php
// Cargar variables de entorno
require_once __DIR__ . '/../includes/functions.php';
loadEnv();

// Configuración de la base de datos
$host = env('DB_HOST', 'localhost');
$user = env('DB_USER', 'root');
$pass = env('DB_PASS', '');
$db = env('DB_NAME', 'reposteria');

// Intentar conectar a la base de datos MySQL
$conn = mysqli_connect($host, $user, $pass, $db);

// Verificar la conexión
if (!$conn) {
    die("ERROR: No se pudo conectar a la base de datos. " . mysqli_connect_error());
}

// Establecer el conjunto de caracteres a utf8
mysqli_set_charset($conn, "utf8");
?>