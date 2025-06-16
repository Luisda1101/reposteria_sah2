// Gestión del carrito de compras con localStorage
class CarritoManager {
    constructor() {
        this.carrito = this.obtenerCarrito();
        this.init();
    }

    init() {
        this.actualizarContador();
        this.configurarEventos();
        
        // Si estamos en la página del carrito, mostrar contenido
        if (window.location.pathname.includes('carrito.php')) {
            this.mostrarCarrito();
        }
    }

    // Obtener carrito del localStorage
    obtenerCarrito() {
        const carritoGuardado = localStorage.getItem('carrito_reposteria');
        return carritoGuardado ? JSON.parse(carritoGuardado) : [];
    }

    // Guardar carrito en localStorage
    guardarCarrito() {
        localStorage.setItem('carrito_reposteria', JSON.stringify(this.carrito));
        this.actualizarContador();
    }

    // Agregar producto al carrito
    agregarProducto(producto) {
        const productoExistente = this.carrito.find(item => item.id === producto.id);
        
        if (productoExistente) {
            productoExistente.cantidad += 1;
        } else {
            this.carrito.push({
                id: producto.id,
                nombre: producto.nombre,
                precio: parseFloat(producto.precio),
                foto: producto.foto,
                cantidad: 1
            });
        }
        
        this.guardarCarrito();
        this.mostrarNotificacion(`${producto.nombre} agregado al carrito`);
        
        // Si estamos en la página del carrito, actualizar vista
        if (window.location.pathname.includes('carrito.php')) {
            this.mostrarCarrito();
        }
    }

    // Eliminar producto del carrito
    eliminarProducto(id) {
        this.carrito = this.carrito.filter(item => item.id !== id);
        this.guardarCarrito();
        
        if (window.location.pathname.includes('carrito.php')) {
            this.mostrarCarrito();
        }
    }

    // Actualizar cantidad de producto
    actualizarCantidad(id, nuevaCantidad) {
        const producto = this.carrito.find(item => item.id === id);
        if (producto) {
            if (nuevaCantidad <= 0) {
                this.eliminarProducto(id);
            } else {
                producto.cantidad = parseInt(nuevaCantidad);
                this.guardarCarrito();
                
                if (window.location.pathname.includes('carrito.php')) {
                    this.mostrarCarrito();
                }
            }
        }
    }

    // Actualizar contador en el navbar
    actualizarContador() {
        const contador = document.getElementById('carrito-contador');
        const totalProductos = this.carrito.reduce((total, item) => total + item.cantidad, 0);
        
        if (contador) {
            if (totalProductos > 0) {
                contador.textContent = totalProductos;
                contador.style.display = 'inline';
            } else {
                contador.style.display = 'none';
            }
        }
    }

    // Mostrar carrito en carrito.php
    mostrarCarrito() {
        const contenidoCarrito = document.getElementById('carrito-contenido');
        const carritoVacio = document.getElementById('carrito-vacio');
        const totalProductosSpan = document.getElementById('total-productos');
        const subtotalSpan = document.getElementById('subtotal');
        const totalFinalSpan = document.getElementById('total-final');
        const finalizarBtn = document.getElementById('finalizar-pedido');

        if (this.carrito.length === 0) {
            carritoVacio.style.display = 'block';
            if (totalProductosSpan) totalProductosSpan.textContent = '0';
            if (subtotalSpan) subtotalSpan.textContent = '$0';
            if (totalFinalSpan) totalFinalSpan.textContent = '$0';
            if (finalizarBtn) finalizarBtn.disabled = true;
            return;
        }

        carritoVacio.style.display = 'none';

        let html = '<div class="table-responsive"><table class="table">';
        html += '<thead><tr><th>Producto</th><th>Precio</th><th>Cantidad</th><th>Subtotal</th><th>Acciones</th></tr></thead>';
        html += '<tbody>';

        let total = 0;
        let totalProductos = 0;

        this.carrito.forEach(item => {
            const subtotal = item.precio * item.cantidad;
            total += subtotal;
            totalProductos += item.cantidad;

            html += `
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="assets/img/productos/${item.foto || 'no-image.png'}" 
                                 alt="${item.nombre}" class="me-3" style="width: 60px; height: 60px; object-fit: cover;">
                            <div>
                                <h6 class="mb-0">${item.nombre}</h6>
                            </div>
                        </div>
                    </td>
                    <td>$${this.formatearPrecio(item.precio)}</td>
                    <td>
                        <div class="input-group" style="width: 120px;">
                            <button class="btn btn-outline-secondary btn-sm" type="button" 
                                    onclick="carrito.actualizarCantidad(${item.id}, ${item.cantidad - 1})">-</button>
                            <input type="number" class="form-control form-control-sm text-center" 
                                   value="${item.cantidad}" min="1" 
                                   onchange="carrito.actualizarCantidad(${item.id}, this.value)">
                            <button class="btn btn-outline-secondary btn-sm" type="button" 
                                    onclick="carrito.actualizarCantidad(${item.id}, ${item.cantidad + 1})">+</button>
                        </div>
                    </td>
                    <td>$${this.formatearPrecio(subtotal)}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-danger" 
                                onclick="carrito.eliminarProducto(${item.id})" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table></div>';
        contenidoCarrito.innerHTML = html;

        // Actualizar resumen
        if (totalProductosSpan) totalProductosSpan.textContent = totalProductos;
        if (subtotalSpan) subtotalSpan.textContent = '$' + this.formatearPrecio(total);
        if (totalFinalSpan) totalFinalSpan.textContent = '$' + this.formatearPrecio(total);
        if (finalizarBtn) {
            finalizarBtn.disabled = false;
            finalizarBtn.onclick = () => this.finalizarPedido();
        }
    }

    // Finalizar pedido - transferir carrito a sesión PHP
    finalizarPedido() {
        if (this.carrito.length === 0) {
            alert('Tu carrito está vacío');
            return;
        }

        // Enviar carrito al servidor para guardarlo en sesión
        fetch('procesar_carrito.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(this.carrito)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirigir a pedido.php
                window.location.href = 'pedido.php';
            } else {
                alert('Error al procesar el carrito');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar el carrito');
        });
    }

    // Limpiar carrito
    limpiarCarrito() {
        this.carrito = [];
        this.guardarCarrito();
        
        if (window.location.pathname.includes('carrito.php')) {
            this.mostrarCarrito();
        }
    }

    // Configurar eventos
    configurarEventos() {
        // Botones "Agregar al carrito"
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('agregar-carrito') || 
                e.target.closest('.agregar-carrito')) {
                
                const btn = e.target.classList.contains('agregar-carrito') ? 
                           e.target : e.target.closest('.agregar-carrito');
                
                const producto = {
                    id: parseInt(btn.dataset.id),
                    nombre: btn.dataset.nombre,
                    precio: btn.dataset.precio,
                    foto: btn.dataset.foto
                };
                
                this.agregarProducto(producto);
            }
        });
    }

    // Mostrar notificación toast
    mostrarNotificacion(mensaje) {
        const toastBody = document.getElementById('carritoToastBody');
        const toast = document.getElementById('carritoToast');
        
        if (toastBody && toast) {
            toastBody.textContent = mensaje;
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
        }
    }

    // Formatear precio
    formatearPrecio(precio) {
        return new Intl.NumberFormat('es-CO', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(precio);
    }
}

// Inicializar carrito cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    window.carrito = new CarritoManager();
});