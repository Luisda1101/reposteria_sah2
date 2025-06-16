<?php

require_once 'includes/functions.php';

// Iniciar sesión
session_start();

logoutUser();

// Redirigir a la página de login
header("location: login.php");
exit;
?>