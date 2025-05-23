document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar
    document.getElementById('sidebarCollapse')?.addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });

    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Inicializar popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Previsualización de imagen al subir
    const imageInput = document.getElementById('foto');
    const imagePreview = document.getElementById('imagePreview');
    
    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });
    }

    // Confirmación para eliminar
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('¿Está seguro de que desea eliminar este elemento? Esta acción no se puede deshacer.')) {
                e.preventDefault();
            }
        });
    });

    // Actualización automática del total en formulario de pedidos
    const cantidadInputs = document.querySelectorAll('.cantidad-input');
    const precioInputs = document.querySelectorAll('.precio-input');
    const subtotalInputs = document.querySelectorAll('.subtotal-input');
    const totalInput = document.getElementById('total');
    
    function updateSubtotal(index) {
        const cantidad = parseFloat(cantidadInputs[index].value) || 0;
        const precio = parseFloat(precioInputs[index].value) || 0;
        const subtotal = cantidad * precio;
        subtotalInputs[index].value = subtotal.toFixed(2);
        updateTotal();
    }
    
    function updateTotal() {
        let total = 0;
        subtotalInputs.forEach(input => {
            total += parseFloat(input.value) || 0;
        });
        if (totalInput) {
            totalInput.value = total.toFixed(2);
        }
    }
    
    if (cantidadInputs.length > 0 && precioInputs.length > 0) {
        cantidadInputs.forEach((input, index) => {
            input.addEventListener('input', function() {
                updateSubtotal(index);
            });
        });
        
        precioInputs.forEach((input, index) => {
            input.addEventListener('input', function() {
                updateSubtotal(index);
            });
        });
    }
});