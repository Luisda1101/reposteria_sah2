<footer class="py-5 bg-dark text-white">
    <div class="container">
        <!-- ...existing code... -->
        <div class="row">
            <div class="col-md-6 mb-3 mb-md-0">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> La Repostería Sahagún. Todos los derechos reservados.
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="<?php echo rtrim(env('BASE_PATH', '/reposteria_sah2'), '/'); ?>/login.php"
                    class="text-white text-decoration-none">Acceso Administrador</a>
            </div>
        </div>
    </div>
</footer>
<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Custom JS -->
<script src="/assets/js/scripts.js"></script>
</body>

</html>