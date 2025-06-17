<?php
// Cargar variables de entorno
require_once __DIR__ . '/../includes/functions.php';
loadEnv();

// Configuración de la base de datos
$host = env('DB_HOST', 'interchange.proxy.rlwy.net');
$user = env('DB_USER', 'root');
$pass = env('DB_PASS', 'SHjMiPcaBGZsDvvnHsjWZZaMXgzHJJkn');
$db = env('DB_NAME', 'railway');
$port = env('DB_PORT', 55626);

// Intentar conectar a la base de datos MySQL
$conn = mysqli_connect($host, $user, $pass, $db, $port);

// Verificar la conexión
if (!$conn) {
    die("ERROR: No se pudo conectar a la base de datos. " . mysqli_connect_error());
}

// Establecer el conjunto de caracteres a utf8
mysqli_set_charset($conn, "utf8");
?>