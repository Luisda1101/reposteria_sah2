<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>La Repostería Sahagún - Sistema de Gestión</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link href="<?php echo rtrim(env('BASE_PATH', '/reposteria_sah2'), '/'); ?>/assets/css/styles.css" rel="stylesheet">
</head>

<body>
    <div class="wrapper">
        <?php if (isLoggedIn()): ?>
            <?php include_once __DIR__ . '/sidebar.php'; ?>
        <?php endif; ?>
        <div class="content">

            <div class="container-fluid p-4">
                <?php displayAlert(); ?>
                <?php if (isLoggedIn()): ?>
                    <div class="alert alert-info text-center mb-0 rounded-0">
                        Sesión iniciada como administrador
                    </div>
                <?php endif; ?>