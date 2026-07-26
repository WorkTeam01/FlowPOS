document.addEventListener('DOMContentLoaded', function() {
    // Mostrar/ocultar mensaje cuando no hay productos
    function actualizarMensajeProductos() {
        const filas = document.querySelectorAll('#tabla-productos tbody tr.fila-producto');
        const mensaje = document.getElementById('mensaje-sin-productos');

        if (filas.length === 0) {
            mensaje.classList.remove('d-none');
        } else {
            mensaje.classList.add('d-none');
        }
    }

    // Función para agregar una nueva fila de producto
    function agregarFilaProducto(producto) {
        const tbody = document.querySelector('#tabla-productos tbody');

        // Verificar si el producto ya está en la tabla
        const filas = tbody.querySelectorAll('tr.fila-producto');
        for (let fila of filas) {
            const idProducto = fila.querySelector('input[name="productos[]"]').value;
            if (idProducto == producto.idproducto) {
                // Incrementar cantidad si el producto ya existe
                const inputCantidad = fila.querySelector('.cantidad');
                inputCantidad.value = parseInt(inputCantidad.value) + 1;
                calcularSubtotal(fila);
                calcularTotal();
                actualizarMensajeProductos();
                return;
            }
        }

        // Crear nueva fila si el producto no existe
        const nuevaFila = document.createElement('tr');
        nuevaFila.classList.add('fila-producto');
        nuevaFila.innerHTML = `
        <td>
            <input type="hidden" name="productos[]" value="${producto.idproducto}">
            <i class="fas fa-box-open mr-2 text-muted"></i>
            ${producto.nombre} <small class="text-muted">(${producto.codigo || 'S/COD'})</small>
        </td>
        <td>
            <div class="input-group">
                <input type="number" class="form-control cantidad" name="cantidades[]"
                    min="1" value="1" required>
                <div class="input-group-append">
                    <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                </div>
            </div>
        </td>
        <td>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-dollar-sign"></i></span>
                </div>
                <input type="number" class="form-control precio" name="precios[]"
                    step="0.01" min="0.01" value="${parseFloat(producto.preciocompra || 0).toFixed(2)}" required>
            </div>
        </td>
        <td class="text-right">
            <span class="subtotal">${parseFloat(producto.preciocompra || 0).toFixed(2)}</span>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-danger btn-sm btn-eliminar-fila">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;

        // Agregar eventos
        agregarEventosCalculo(nuevaFila);

        // Evento para eliminar fila
        nuevaFila.querySelector('.btn-eliminar-fila').addEventListener('click', function() {
            this.closest('tr').remove();
            calcularTotal();
            actualizarMensajeProductos();
        });

        // Insertar en la tabla
        tbody.appendChild(nuevaFila);
        calcularTotal();
        actualizarMensajeProductos();
    }

    // Función para agregar eventos de cálculo a una fila
    function agregarEventosCalculo(fila) {
        const cantidad = fila.querySelector('.cantidad');
        const precio = fila.querySelector('.precio');

        cantidad.addEventListener('input', function() {
            calcularSubtotal(fila);
            calcularTotal();
        });

        precio.addEventListener('input', function() {
            calcularSubtotal(fila);
            calcularTotal();
        });
    }

    // Calcular subtotal para una fila
    function calcularSubtotal(fila) {
        const cantidad = parseFloat(fila.querySelector('.cantidad').value) || 0;
        const precio = parseFloat(fila.querySelector('.precio').value) || 0;
        const subtotal = cantidad * precio;
        fila.querySelector('.subtotal').textContent = subtotal.toFixed(2);
    }

    // Calcular total de la compra
    function calcularTotal() {
        let total = 0;
        document.querySelectorAll('.fila-producto').forEach(fila => {
            const subtotal = parseFloat(fila.querySelector('.subtotal').textContent) || 0;
            total += subtotal;
        });
        document.getElementById('total-compra').textContent = total.toFixed(2);
        document.getElementById('input-total-compra').value = total.toFixed(2);
    }

    // Buscar producto por código
    function buscarProducto() {
        const codigo = document.getElementById('buscar-producto').value.trim();
        if (!codigo) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Por favor ingrese un código de producto',
            });
            return;
        }

        // Buscar en la lista de productos
        const productos = $('#datos-compra').data('productos') || [];
        const productoEncontrado = productos.find(p =>
            p.codigo && p.codigo.toLowerCase() === codigo.toLowerCase() && p.estado == 1
        );

        if (productoEncontrado) {
            agregarFilaProducto(productoEncontrado);
            document.getElementById('buscar-producto').value = '';
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Producto no encontrado',
                text: 'No se encontró un producto activo con ese código',
            });
        }
    }

    // Evento para botón buscar
    document.getElementById('btn-buscar-producto').addEventListener('click', buscarProducto);

    // Permitir buscar con Enter
    document.getElementById('buscar-producto').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            buscarProducto();
        }
    });

    // Validar formulario antes de enviar
    document.getElementById('form-compra').addEventListener('submit', function(e) {
        e.preventDefault();

        // Resetear errores
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        let isValid = true;
        let firstInvalidElement = null;

        // Validar campos principales
        const mainFields = ['fechacompra'];
        mainFields.forEach(field => {
            const element = document.getElementById(field);
            if (!element.value) {
                element.classList.add('is-invalid');
                isValid = false;
                if (!firstInvalidElement) firstInvalidElement = element;
            }
        });

        // Validar filas de productos
        const filas = document.querySelectorAll('.fila-producto');
        if (filas.length === 0) {
            isValid = false;
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe agregar al menos un producto',
            });
            return;
        }

        filas.forEach((fila, index) => {
            const cantidad = fila.querySelector('.cantidad');
            const precio = fila.querySelector('.precio');

            if (!cantidad.value || parseFloat(cantidad.value) <= 0) {
                cantidad.classList.add('is-invalid');
                isValid = false;
                if (!firstInvalidElement) firstInvalidElement = cantidad;
            }

            if (!precio.value || parseFloat(precio.value) <= 0) {
                precio.classList.add('is-invalid');
                isValid = false;
                if (!firstInvalidElement) firstInvalidElement = precio;
            }
        });

        if (!isValid) {
            if (firstInvalidElement) {
                firstInvalidElement.focus();
            }
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Por favor complete todos los campos requeridos correctamente',
            });
            return;
        }

        // Validar total
        const total = parseFloat(document.getElementById('input-total-compra').value);
        if (total <= 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'El total de la compra debe ser mayor que cero',
            });
            return;
        }

        // Confirmación antes de enviar
        Swal.fire({
            title: '¿Confirmar compra?',
            text: "¿Está seguro de registrar esta compra?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, registrar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar mensaje de carga
                Swal.fire({
                    title: 'Procesando compra...',
                    html: 'Por favor espere mientras se registra la compra',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Enviar formulario
                this.submit();
            }
        });
    });

    // Inicializar mensaje de productos
    actualizarMensajeProductos();
});
