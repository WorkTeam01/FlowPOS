document.addEventListener('DOMContentLoaded', function () {
    const datosCompra = document.getElementById('datos-compra');
    const productosDisponibles = datosCompra ? ($(datosCompra).data('productos') || []) : [];

    // ---- Modal de productos ----
    const modalProductos = $('#modal-productos');
    const modalBuscarProducto = document.getElementById('modal-buscar-producto');
    const listaProductosModal = document.getElementById('lista-productos-modal');
    const sinResultadosProductos = document.getElementById('sin-resultados-productos');

    modalProductos.on('shown.bs.modal', function () {
        modalBuscarProducto.value = '';
        renderizarListaProductos(productosDisponibles);
        modalBuscarProducto.focus();
    });

    modalBuscarProducto.addEventListener('input', function () {
        const termino = this.value.trim().toLowerCase();
        const filtrados = termino.length === 0
            ? productosDisponibles
            : productosDisponibles.filter(p =>
                (p.nombre && p.nombre.toLowerCase().includes(termino)) ||
                (p.codigo && p.codigo.toLowerCase().includes(termino))
            );
        renderizarListaProductos(filtrados);
    });

    function renderizarListaProductos(productos) {
        listaProductosModal.innerHTML = '';
        sinResultadosProductos.style.display = productos.length === 0 ? 'block' : 'none';

        productos.forEach(producto => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';

            const infoSpan = document.createElement('span');
            const nombreStrong = document.createElement('strong');
            nombreStrong.textContent = producto.nombre;
            const detalleSmall = document.createElement('small');
            detalleSmall.className = 'text-muted d-block';
            detalleSmall.textContent = `Código: ${producto.codigo || 'N/A'}`;
            infoSpan.appendChild(nombreStrong);
            infoSpan.appendChild(detalleSmall);

            const precioBadge = document.createElement('span');
            precioBadge.className = 'badge badge-primary badge-pill';
            precioBadge.textContent = parseFloat(producto.preciocompra || 0).toFixed(2);

            item.appendChild(infoSpan);
            item.appendChild(precioBadge);

            item.addEventListener('click', () => {
                agregarProductoAlDetalle(producto);
                modalProductos.modal('hide');
            });
            listaProductosModal.appendChild(item);
        });
    }

    /**
     * Agregar producto al detalle (o incrementar cantidad si ya está agregado)
     */
    function agregarProductoAlDetalle(producto) {
        const filasExistentes = document.querySelectorAll('tr.fila-producto');
        for (const fila of filasExistentes) {
            if (fila.querySelector('.idproducto').value == producto.idproducto) {
                const inputCantidad = fila.querySelector('.cantidad');
                inputCantidad.value = parseInt(inputCantidad.value) + 1;
                calcularSubtotal(fila);
                calcularTotal();
                actualizarVisibilidadCarritoVacio();
                return;
            }
        }

        const filaBase = document.getElementById('fila-base');
        const nuevaFila = filaBase.cloneNode(true);
        nuevaFila.removeAttribute('id');
        nuevaFila.style.display = '';
        nuevaFila.classList.add('fila-producto');

        nuevaFila.querySelector('.idproducto').value = producto.idproducto;
        nuevaFila.querySelector('.nombre-producto').textContent = producto.nombre;
        nuevaFila.querySelector('.codigo-producto').textContent = producto.codigo || 'S/COD';
        nuevaFila.querySelector('.cantidad').value = 1;
        nuevaFila.querySelector('.precio').value = parseFloat(producto.preciocompra || 0).toFixed(2);
        nuevaFila.querySelector('.subtotal').textContent = parseFloat(producto.preciocompra || 0).toFixed(2);

        // Vincular cada input con su mensaje de error (aria-describedby) para que
        // los lectores de pantalla anuncien la invalidez, no solo la clase visual is-invalid
        const cantidadInput = nuevaFila.querySelector('.cantidad');
        const precioInput = nuevaFila.querySelector('.precio');
        const cantidadFeedback = cantidadInput.nextElementSibling;
        const precioInputGroup = precioInput.closest('.input-group');
        const precioFeedback = precioInputGroup ? precioInputGroup.querySelector('.invalid-feedback') : null;

        cantidadFeedback.id = `feedback-cantidad-${producto.idproducto}`;
        cantidadInput.setAttribute('aria-describedby', cantidadFeedback.id);
        cantidadInput.setAttribute('aria-invalid', 'false');

        if (precioFeedback) {
            precioFeedback.id = `feedback-precio-${producto.idproducto}`;
            precioInput.setAttribute('aria-describedby', precioFeedback.id);
        }
        precioInput.setAttribute('aria-invalid', 'false');

        agregarEventosCalculo(nuevaFila);

        nuevaFila.querySelector('.btn-eliminar-fila').addEventListener('click', function () {
            this.closest('tr').remove();
            calcularTotal();
            actualizarVisibilidadCarritoVacio();
        });

        document.querySelector('#tabla-productos tbody').appendChild(nuevaFila);

        actualizarVisibilidadCarritoVacio();
        calcularTotal();
    }

    /**
     * Muestra/oculta el mensaje de carrito vacío según haya o no productos agregados
     */
    function actualizarVisibilidadCarritoVacio() {
        const hayProductos = document.querySelectorAll('tr.fila-producto').length > 0;
        document.getElementById('carrito-vacio').style.display = hayProductos ? 'none' : 'block';
        document.getElementById('tabla-productos').closest('.table-responsive').style.display = hayProductos ? '' : 'none';
    }

    function agregarEventosCalculo(fila) {
        const cantidad = fila.querySelector('.cantidad');
        const precio = fila.querySelector('.precio');

        cantidad.addEventListener('input', function () {
            calcularSubtotal(fila);
            calcularTotal();
        });

        precio.addEventListener('input', function () {
            calcularSubtotal(fila);
            calcularTotal();
        });
    }

    function calcularSubtotal(fila) {
        const cantidad = parseFloat(fila.querySelector('.cantidad').value) || 0;
        const precio = parseFloat(fila.querySelector('.precio').value) || 0;
        const subtotal = cantidad * precio;
        fila.querySelector('.subtotal').textContent = subtotal.toFixed(2);
    }

    function calcularTotal() {
        let total = 0;
        document.querySelectorAll('.fila-producto').forEach(fila => {
            const subtotal = parseFloat(fila.querySelector('.subtotal').textContent) || 0;
            total += subtotal;
        });
        document.getElementById('total-compra').textContent = total.toFixed(2);
        return total;
    }

    /**
     * Valida cantidad/precio de cada fila de producto y marca los campos inválidos
     */
    function validarFilasProductos() {
        const filas = document.querySelectorAll('tr.fila-producto');
        let esValido = filas.length > 0;

        filas.forEach(fila => {
            const cantidad = fila.querySelector('.cantidad');
            const precio = fila.querySelector('.precio');

            [cantidad, precio].forEach(input => {
                if (!input.checkValidity()) {
                    input.classList.add('is-invalid');
                    input.setAttribute('aria-invalid', 'true');
                    esValido = false;
                } else {
                    input.classList.remove('is-invalid');
                    input.setAttribute('aria-invalid', 'false');
                }
            });
        });

        return esValido;
    }

    // Validar formulario antes de enviar
    document.getElementById('form-compra').addEventListener('submit', function (e) {
        e.preventDefault();

        document.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
            el.setAttribute('aria-invalid', 'false');
        });

        const fechaCompra = document.getElementById('fechacompra');
        let isValid = true;
        if (!fechaCompra.value) {
            fechaCompra.classList.add('is-invalid');
            fechaCompra.setAttribute('aria-invalid', 'true');
            isValid = false;
        } else {
            fechaCompra.setAttribute('aria-invalid', 'false');
        }

        if (document.querySelectorAll('.fila-producto').length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe agregar al menos un producto',
            });
            return;
        }

        if (!validarFilasProductos()) {
            isValid = false;
        }

        if (!isValid) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Por favor complete todos los campos requeridos correctamente',
            });
            return;
        }

        const total = calcularTotal();
        if (total <= 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'El total de la compra debe ser mayor que cero',
            });
            return;
        }

        Swal.fire({
            title: '¿Confirmar compra?',
            text: '¿Está seguro de registrar esta compra?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, registrar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Procesando compra...',
                    html: 'Por favor espere mientras se registra la compra',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                this.submit();
            }
        });
    });

    actualizarVisibilidadCarritoVacio();
});
