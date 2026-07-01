/**
 * create-venta.js - Scripts para el formulario de ventas con pago mixto o único
 * 
 * Este archivo contiene las funciones y eventos para el funcionamiento
 * del formulario de creación de ventas, incluyendo la gestión de pagos mixtos y únicos.
 * 
 * @version 1.0
 */

document.addEventListener('DOMContentLoaded', function () {
    // Referencias a elementos del DOM
    // Elementos relacionados con productos
    const buscarProductoInput = document.getElementById('buscar-producto');
    const btnBuscarProducto = document.getElementById('btn-buscar-producto');
    const totalVentaSpan = document.getElementById('total-venta');
    const totalVentaDisplay = document.getElementById('total-venta-display');
    const totalVentaHidden = document.getElementById('totalventa-hidden');

    // Elementos relacionados con pagos
    const btnAgregarPago = document.getElementById('btn-agregar-pago');
    const contenedorPagos = document.getElementById('contenedor-pagos');
    const templateMetodoPago = document.getElementById('template-metodo-pago').innerHTML;
    const totalPagadoDisplay = document.getElementById('total-pagado-display');
    const diferenciaPagoElement = document.getElementById('diferencia-pago');

    // Elementos para pago único/mixto
    const btnPagoUnico = document.getElementById('btn-pago-unico');
    const btnPagoMixto = document.getElementById('btn-pago-mixto');
    const seccionPagoUnico = document.getElementById('seccion-pago-unico');
    const seccionPagoMixto = document.getElementById('seccion-pago-mixto');
    const metodoPagoUnico = document.getElementById('metodopago-unico');
    const montoUnico = document.getElementById('monto-unico');
    const pagoRecibidoUnico = document.getElementById('pago-recibido-unico');
    const cambioUnico = document.getElementById('cambio-unico');
    const cambioUnicoHidden = document.getElementById('cambio-unico-hidden');
    const divPagoRecibidoUnico = document.getElementById('div-pago-recibido-unico');
    const divCambioUnico = document.getElementById('div-cambio-unico');

    // Variables globales
    let totalVentaActual = 0;
    let totalPagado = 0;

    // Inicializar Select2
    initializeSelect2();

    // Inicializar modo de pago único
    initPagoUnico();

    // Evento para cambiar entre pago único y mixto
    btnPagoUnico.addEventListener('click', function () {
        seccionPagoUnico.style.display = 'block';
        seccionPagoMixto.style.display = 'none';
        calcularTotalPagado(); // Cambiado de actualizarTotalPagado a calcularTotalPagado
    });

    btnPagoMixto.addEventListener('click', function () {
        seccionPagoUnico.style.display = 'none';
        seccionPagoMixto.style.display = 'block';
        // Si no hay métodos de pago añadidos, agregar uno
        if (document.querySelectorAll('.metodo-pago-item').length === 0) {
            agregarMetodoPago();
        }
        calcularTotalPagado(); // Cambiado de actualizarTotalPagado a calcularTotalPagado
    });

    // Configurar eventos para pago único
    metodoPagoUnico.addEventListener('change', function () {
        if (this.value === 'efectivo') {
            divPagoRecibidoUnico.style.display = 'block';
            calcularCambioUnico();
        } else {
            divPagoRecibidoUnico.style.display = 'none';
            divCambioUnico.style.display = 'none';
            pagoRecibidoUnico.value = montoUnico.value;
        }
        actualizarEstadoPagoUnico();
    });

    pagoRecibidoUnico.addEventListener('input', function () {
        if (metodoPagoUnico.value === 'efectivo') {
            calcularCambioUnico();
        }
        actualizarEstadoPagoUnico();
    });

    // Añadir primer método de pago para el modo mixto
    btnAgregarPago.addEventListener('click', agregarMetodoPago);

    // Buscar producto
    btnBuscarProducto.addEventListener('click', function () {
        const codigo = buscarProductoInput.value.trim();
        if (!codigo) {
            Swal.fire('Error', 'Ingrese un código de producto', 'error');
            return;
        }

        const producto = buscarProductoPorCodigo(codigo);
        if (producto) {
            agregarProductoAlDetalle(producto);
        } else {
            Swal.fire('Producto no encontrado', 'No se encontró un producto con ese código', 'error');
        }
    });

    // También buscar al presionar Enter
    buscarProductoInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            btnBuscarProducto.click();
        }
    });

    // Evento para buscar cliente
    document.getElementById('buscar-cliente').addEventListener('input', function () {
        if (this.value.length >= 2) {
            const resultados = buscarClientes(this.value);
            mostrarSugerencias(resultados);
        } else {
            document.getElementById('sugerencias-clientes').style.display = 'none';
        }
    });

    // Evento para quitar cliente seleccionado
    document.getElementById('quitar-cliente').addEventListener('click', function () {
        document.getElementById('idcliente').value = '';
        document.getElementById('buscar-cliente').value = '';
        document.getElementById('info-cliente-seleccionado').style.display = 'none';
        document.getElementById('buscar-cliente').focus();
    });

    // Ocultar sugerencias al hacer clic fuera
    document.addEventListener('click', function (e) {
        if (!e.target.closest('#buscar-cliente') && !e.target.closest('#sugerencias-clientes')) {
            document.getElementById('sugerencias-clientes').style.display = 'none';
        }
    });

    // Añadir la confirmación de formulario con SweetAlert2
    document.getElementById('form-venta').addEventListener('submit', function (e) {
        e.preventDefault();

        // Verificar específicamente si se seleccionó un cliente
        const idCliente = document.getElementById('idcliente').value;
        if (!idCliente) {
            // Si no hay cliente seleccionado, mostrar mensaje amigable
            Swal.fire({
                title: 'Cliente requerido',
                text: 'Por favor, seleccione un cliente antes de continuar',
                icon: 'warning',
                timer: 3000,
                showConfirmButton: false,
                position: 'top-end',
                toast: true
            });

            // Hacer focus en el campo de búsqueda de cliente
            document.getElementById('buscar-cliente').focus();

            return false; // Detener el envío del formulario
        }

        // Obtener el tipo de pago
        const tipoPago = document.querySelector('input[name="tipo_pago"]:checked').value;

        // Obtener el valor de la observación
        const observacion = document.getElementById('observacion').value.trim();
        const observacionHtml = observacion ?
            `<p><strong>Observación:</strong> ${observacion.length > 50 ? observacion.substring(0, 50) + '...' : observacion}</p>` : '';

        // Confirmación final
        Swal.fire({
            title: '¿Confirmar Venta?',
            html: `<div class="text-left">
                <p><strong>Total:</strong> ${document.getElementById('total-venta').textContent} Bs.</p>
                <p><strong>Tipo de pago:</strong> ${tipoPago === 'unico' ? 'Pago único' : 'Pago mixto'}</p>
                ${observacionHtml}
                <p>¿Está seguro de registrar esta venta?</p>
              </div>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, registrar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Preparar campos adicionales para el formulario
                prepareDynamicFields(this, tipoPago);
                this.submit();
            }
        });
    });

    /**
     * Prepara campos dinámicos antes de enviar el formulario
     */
    function prepareDynamicFields(form, tipoPago) {
        // Crear un campo oculto para el tipo de pago
        const inputTipoPago = document.createElement('input');
        inputTipoPago.type = 'hidden';
        inputTipoPago.name = 'tipo_pago';
        inputTipoPago.value = tipoPago;
        form.appendChild(inputTipoPago);

        if (tipoPago === 'unico') {
            // Crear campos ocultos para incluir el método de pago único
            const metodoPago = document.createElement('input');
            metodoPago.type = 'hidden';
            metodoPago.name = 'metodopago[]';
            metodoPago.value = metodoPagoUnico.value;
            form.appendChild(metodoPago);

            const montoPago = document.createElement('input');
            montoPago.type = 'hidden';
            montoPago.name = 'montopago[]';
            montoPago.value = montoUnico.value;
            form.appendChild(montoPago);

            const pagoRecibido = document.createElement('input');
            pagoRecibido.type = 'hidden';
            pagoRecibido.name = 'pagorecibido[]';
            pagoRecibido.value = pagoRecibidoUnico.value;
            form.appendChild(pagoRecibido);

            const cambio = document.createElement('input');
            cambio.type = 'hidden';
            cambio.name = 'cambio[]';
            cambio.value = cambioUnicoHidden.value;
            form.appendChild(cambio);
        }
    }

    /**
     * Inicializa los selectores mejorados
     */
    function initializeSelect2() {
        if ($.fn.select2) {
            $('.select2').select2({
                theme: 'bootstrap4',
                width: '100%'
            });
        }
    }

    /**
     * Función para inicializar el modo de pago único
     */
    function initPagoUnico() {
        // Actualizar el monto único con el total de la venta
        montoUnico.value = totalVentaActual.toFixed(2);
        pagoRecibidoUnico.value = totalVentaActual.toFixed(2);
    }

    /**
     * Función para calcular el cambio en pago único
     */
    function calcularCambioUnico() {
        const monto = parseFloat(montoUnico.value) || 0;
        const pagoRecibido = parseFloat(pagoRecibidoUnico.value) || 0;

        if (pagoRecibido >= monto) {
            const cambio = pagoRecibido - monto;
            cambioUnico.value = cambio.toFixed(2);
            cambioUnicoHidden.value = cambio.toFixed(2);
            divCambioUnico.style.display = 'block';
            pagoRecibidoUnico.classList.remove('is-invalid');
        } else {
            cambioUnico.value = '0.00';
            cambioUnicoHidden.value = '0.00';
            divCambioUnico.style.display = 'block';
            pagoRecibidoUnico.classList.add('is-invalid');
        }
    }

    /**
     * Actualiza visualmente el estado del pago único
     */
    function actualizarEstadoPagoUnico() {
        const metodo = metodoPagoUnico.value;
        const monto = parseFloat(montoUnico.value) || 0;
        const pagoRecibido = parseFloat(pagoRecibidoUnico.value) || 0;

        seccionPagoUnico.classList.remove('pago-correcto', 'pago-incorrecto');

        if (!metodo || monto <= 0) {
            seccionPagoUnico.classList.add('pago-incorrecto');
            return;
        }

        if (metodo === 'efectivo' && pagoRecibido < monto) {
            seccionPagoUnico.classList.add('pago-incorrecto');
            return;
        }

        seccionPagoUnico.classList.add('pago-correcto');
    }

    /**
     * Función para agregar un nuevo método de pago
     */
    function agregarMetodoPago() {
        // Crear elemento a partir del template
        const div = document.createElement('div');
        div.innerHTML = templateMetodoPago;
        const nuevoMetodoPago = div.firstElementChild;

        // Agregar al contenedor
        contenedorPagos.appendChild(nuevoMetodoPago);

        // Configurar eventos
        const selectMetodo = nuevoMetodoPago.querySelector('.select-metodo-pago');
        const inputMonto = nuevoMetodoPago.querySelector('.monto-pago');
        const inputPagoRecibido = nuevoMetodoPago.querySelector('.pago-recibido');
        const btnEliminar = nuevoMetodoPago.querySelector('.btn-eliminar-pago');
        const divPagoRecibido = nuevoMetodoPago.querySelector('.div-pago-recibido');
        const divCambio = nuevoMetodoPago.querySelector('.div-cambio');

        selectMetodo.addEventListener('change', function () {
            const metodo = this.value;
            if (metodo === 'efectivo') {
                divPagoRecibido.style.display = 'block';
                inputPagoRecibido.required = true;
                calcularCambio(nuevoMetodoPago);
            } else {
                divPagoRecibido.style.display = 'none';
                divCambio.style.display = 'none';
                inputPagoRecibido.required = false;
                inputPagoRecibido.value = inputMonto.value;
            }

            // Actualizar clases visuales
            actualizarEstadoPago(nuevoMetodoPago);
        });

        inputMonto.addEventListener('input', function () {
            if (selectMetodo.value !== 'efectivo') {
                inputPagoRecibido.value = this.value;
            } else {
                calcularCambio(nuevoMetodoPago);
            }
            calcularTotalPagado();
            actualizarEstadoPago(nuevoMetodoPago);
        });

        inputPagoRecibido.addEventListener('input', function () {
            if (selectMetodo.value === 'efectivo') {
                calcularCambio(nuevoMetodoPago);
            }
            actualizarEstadoPago(nuevoMetodoPago);
        });

        btnEliminar.addEventListener('click', function () {
            if (document.querySelectorAll('.metodo-pago-item').length > 1) {
                nuevoMetodoPago.remove();
                calcularTotalPagado();
            } else {
                Swal.fire('Atención', 'Debe haber al menos un método de pago', 'warning');
            }
        });

        // Inicializar valores
        if (totalVentaActual > 0) {
            // Si ya hay otros métodos de pago, sugerir el saldo pendiente
            const totalPendiente = totalVentaActual - totalPagado;
            if (totalPendiente > 0) {
                inputMonto.value = totalPendiente.toFixed(2);
            } else {
                inputMonto.value = "0.00";
            }

            if (selectMetodo.value !== 'efectivo') {
                inputPagoRecibido.value = inputMonto.value;
            }
        } else {
            // Si no hay total de venta aún, inicializar con 0
            inputMonto.value = "0.00";
            inputPagoRecibido.value = "0.00";
        }

        calcularTotalPagado();
        actualizarEstadoPago(nuevoMetodoPago);
    }

    /**
     * Calcular cambio para un método de pago específico
     */
    function calcularCambio(metodoPagoElement) {
        const monto = parseFloat(metodoPagoElement.querySelector('.monto-pago').value) || 0;
        const pagoRecibido = parseFloat(metodoPagoElement.querySelector('.pago-recibido').value) || 0;
        const cambioPago = metodoPagoElement.querySelector('.cambio-pago');
        const cambioHidden = metodoPagoElement.querySelector('.cambio-hidden');
        const divCambio = metodoPagoElement.querySelector('.div-cambio');

        if (pagoRecibido >= monto) {
            const cambio = pagoRecibido - monto;
            cambioPago.value = cambio.toFixed(2);
            cambioHidden.value = cambio.toFixed(2);
            divCambio.style.display = 'block';
            metodoPagoElement.querySelector('.pago-recibido').classList.remove('is-invalid');
        } else {
            cambioPago.value = '0.00';
            cambioHidden.value = '0.00';
            divCambio.style.display = 'block';
            metodoPagoElement.querySelector('.pago-recibido').classList.add('is-invalid');
        }
    }

    /**
     * Actualiza visualmente el estado de un método de pago
     */
    function actualizarEstadoPago(metodoPagoElement) {
        const selectMetodo = metodoPagoElement.querySelector('.select-metodo-pago');
        const inputMonto = metodoPagoElement.querySelector('.monto-pago');
        const inputPagoRecibido = metodoPagoElement.querySelector('.pago-recibido');

        const monto = parseFloat(inputMonto.value) || 0;
        const pagoRecibido = parseFloat(inputPagoRecibido.value) || 0;

        metodoPagoElement.classList.remove('pago-correcto', 'pago-incorrecto');

        if (monto <= 0) {
            metodoPagoElement.classList.add('pago-incorrecto');
            return;
        }

        if (selectMetodo.value === 'efectivo' && pagoRecibido < monto) {
            metodoPagoElement.classList.add('pago-incorrecto');
            return;
        }

        metodoPagoElement.classList.add('pago-correcto');
    }

    /**
     * Calcular total pagado y validar
     */
    function calcularTotalPagado() {
        let total = 0;

        // Verificar qué tipo de pago está activo
        const tipoPago = document.querySelector('input[name="tipo_pago"]:checked').value;

        if (tipoPago === 'unico') {
            // Calcular total de pago único
            total = parseFloat(montoUnico.value) || 0;
        } else {
            // Calcular total de pagos mixtos
            const metodosItems = document.querySelectorAll('.metodo-pago-item');
            metodosItems.forEach(item => {
                total += parseFloat(item.querySelector('.monto-pago').value) || 0;
            });
        }

        totalPagado = total;
        totalPagadoDisplay.textContent = total.toFixed(2) + ' Bs.';

        // Validar diferencia con el total de la venta
        const totalVenta = parseFloat(totalVentaSpan.textContent) || 0;
        const diferencia = totalVenta - total;

        if (Math.abs(diferencia) > 0.01) {
            if (diferencia > 0) {
                diferenciaPagoElement.textContent = `Falta por pagar: ${diferencia.toFixed(2)} Bs.`;
                diferenciaPagoElement.classList.add('text-danger');
                diferenciaPagoElement.classList.remove('text-success', 'text-warning');
            } else {
                diferenciaPagoElement.textContent = `Sobrepago: ${Math.abs(diferencia).toFixed(2)} Bs.`;
                diferenciaPagoElement.classList.remove('text-danger', 'text-success');
                diferenciaPagoElement.classList.add('text-warning');
            }
        } else {
            diferenciaPagoElement.textContent = 'Pago completo';
            diferenciaPagoElement.classList.remove('text-danger', 'text-warning');
            diferenciaPagoElement.classList.add('text-success');
        }
    }

    /**
     * Buscar producto por código
     */
    function buscarProductoPorCodigo(codigo) {
        return productosDisponibles.find(producto =>
            producto.codigo && producto.codigo.toLowerCase() === codigo.toLowerCase()
        );
    }

    /**
     * Agregar producto al detalle
     */
    function agregarProductoAlDetalle(producto) {
        // Verificar si el producto ya está en el detalle
        const productosEnDetalle = document.querySelectorAll('.idproducto');
        for (let i = 0; i < productosEnDetalle.length; i++) {
            if (productosEnDetalle[i].value == producto.idproducto) {
                Swal.fire('Producto ya agregado', 'Este producto ya está en el detalle de la venta', 'info');
                return;
            }
        }

        const filaBase = document.getElementById('fila-base');
        const nuevaFila = filaBase.cloneNode(true);

        nuevaFila.style.display = '';
        nuevaFila.classList.add('fila-producto'); // Añadir clase para identificarla

        // Llenar datos del producto
        nuevaFila.querySelector('.idproducto').value = producto.idproducto;
        nuevaFila.querySelector('.nombre-producto').value = producto.nombre;
        nuevaFila.querySelector('.codigo-producto').value = producto.codigo;
        nuevaFila.querySelector('.cantidad').value = 1;
        nuevaFila.querySelector('.cantidad').max = producto.stock;
        nuevaFila.querySelector('.precio').value = parseFloat(producto.precioventa).toFixed(2);
        nuevaFila.querySelector('.descuento').value = '0.00';
        nuevaFila.querySelector('.subtotal').textContent = parseFloat(producto.precioventa).toFixed(2);
        nuevaFila.querySelector('.stock-disponible').textContent = `Disponible: ${producto.stock}`;

        // Agregar eventos
        nuevaFila.querySelector('.btn-eliminar-fila').addEventListener('click', eliminarFila);
        agregarEventosCalculo(nuevaFila);

        document.querySelector('#tabla-productos tbody').appendChild(nuevaFila);

        // Calcular totales
        calcularTotalesVenta();

        // Limpiar campo de búsqueda
        buscarProductoInput.value = '';
        buscarProductoInput.focus();
    }

    /**
     * Eliminar fila de producto
     */
    function eliminarFila() {
        if (document.querySelectorAll('tr.fila-producto').length > 1) {
            this.closest('tr').remove();
            calcularTotalesVenta();
        } else {
            Swal.fire('Atención', 'Debe haber al menos un producto en la venta', 'warning');
        }
    }

    /**
     * Agregar eventos de cálculo a fila de producto
     */
    function agregarEventosCalculo(fila) {
        const cantidad = fila.querySelector('.cantidad');
        const precio = fila.querySelector('.precio');
        const descuento = fila.querySelector('.descuento');

        cantidad.addEventListener('input', function () {
            const maxStock = parseInt(this.max) || 0;
            if (parseInt(this.value) > maxStock) {
                this.value = maxStock;
                Swal.fire('Stock insuficiente', `Máximo disponible: ${maxStock}`, 'warning');
            }
            calcularSubtotal(fila);
            calcularTotalesVenta();
        });

        precio.addEventListener('input', () => {
            calcularSubtotal(fila);
            calcularTotalesVenta();
        });

        descuento.addEventListener('input', () => {
            calcularSubtotal(fila);
            calcularTotalesVenta();
        });
    }

    /**
     * Calcular subtotal de un producto (sin cambios)
     */
    function calcularSubtotal(fila) {
        const cantidad = parseFloat(fila.querySelector('.cantidad').value) || 0;
        const precio = parseFloat(fila.querySelector('.precio').value) || 0;
        const descuentoUnitario = parseFloat(fila.querySelector('.descuento').value) || 0;

        // El subtotal sigue siendo cantidad * precio (sin restar descuento)
        const subtotal = cantidad * precio;

        // Actualizar el campo visual de subtotal
        fila.querySelector('.subtotal').textContent = subtotal.toFixed(2);

        // Guardamos el descuento total para este producto en un atributo data
        // para usarlo al calcular el total general
        fila.setAttribute('data-descuento-total', (cantidad * descuentoUnitario).toFixed(2));
    }

    /**
     * Calcular totales de la venta
     */
    function calcularTotalesVenta() {
        let subtotal = 0,
            descuentoTotal = 0,
            total = 0;

        document.querySelectorAll('tr.fila-producto').forEach(fila => {
            const cantidad = parseFloat(fila.querySelector('.cantidad').value) || 0;
            const precio = parseFloat(fila.querySelector('.precio').value) || 0;
            const descuentoUnitario = parseFloat(fila.querySelector('.descuento').value) || 0;

            // Calcular el subtotal por producto (cantidad * precio)
            const subtotalProducto = cantidad * precio;
            subtotal += subtotalProducto;

            // Calcular el descuento total por producto (cantidad * descuentoUnitario)
            const descuentoProducto = cantidad * descuentoUnitario;
            descuentoTotal += descuentoProducto;
        });

        // El total es el subtotal menos el descuento total
        total = subtotal - descuentoTotal;

        document.getElementById('subtotal-venta').textContent = subtotal.toFixed(2);
        document.getElementById('descuento-total').textContent = descuentoTotal.toFixed(2);
        document.getElementById('total-venta').textContent = total.toFixed(2);
        document.getElementById('totalventa-hidden').value = total.toFixed(2);
        document.getElementById('total-venta-display').textContent = total.toFixed(2) + ' Bs.';

        // Actualizar variable global
        totalVentaActual = total;

        // Actualizar el campo de monto único
        montoUnico.value = totalVentaActual.toFixed(2);
        if (metodoPagoUnico.value !== 'efectivo') {
            pagoRecibidoUnico.value = totalVentaActual.toFixed(2);
        } else {
            calcularCambioUnico();
        }

        // Actualizar estado visual del pago único
        actualizarEstadoPagoUnico();

        // Actualizar montos de los métodos de pago en modo mixto
        const tipoPago = document.querySelector('input[name="tipo_pago"]:checked').value;
        if (tipoPago === 'mixto') {
            const metodosItems = document.querySelectorAll('.metodo-pago-item');

            // Si solo hay un método de pago, actualizar automáticamente su monto
            if (metodosItems.length === 1) {
                const item = metodosItems[0];
                const selectMetodo = item.querySelector('.select-metodo-pago');
                const inputMonto = item.querySelector('.monto-pago');
                const inputPagoRecibido = item.querySelector('.pago-recibido');

                // Solo actualizar si hay un método seleccionado
                if (selectMetodo.value) {
                    inputMonto.value = total.toFixed(2);

                    // Actualizar pago recibido según el método
                    if (selectMetodo.value === 'efectivo') {
                        // Solo actualizar si el pago recibido es menor que el nuevo total
                        if (parseFloat(inputPagoRecibido.value) < total) {
                            inputPagoRecibido.value = total.toFixed(2);
                        }
                        calcularCambio(item);
                    } else {
                        inputPagoRecibido.value = total.toFixed(2);
                    }

                    actualizarEstadoPago(item);
                }
            }
        }

        // Recalcular el total pagado y verificar diferencia
        calcularTotalPagado();
    }

    /**
     * Función para buscar clientes
     */
    function buscarClientes(termino) {
        termino = termino.toLowerCase();
        return clientesDisponibles.filter(cliente =>
            (cliente.numdocumento && cliente.numdocumento.toLowerCase().includes(termino)) ||
            (cliente.nombres && cliente.nombres.toLowerCase().includes(termino)) ||
            (cliente.apellidopaterno && cliente.apellidopaterno.toLowerCase().includes(termino)) ||
            (cliente.apellidomaterno && cliente.apellidomaterno && cliente.apellidomaterno.toLowerCase().includes(termino))
        );
    }

    /**
     * Mostrar sugerencias de clientes
     */
    function mostrarSugerencias(clientes) {
        const contenedor = document.getElementById('sugerencias-clientes');
        contenedor.innerHTML = '';

        if (clientes.length === 0) {
            contenedor.style.display = 'none';
            return;
        }

        clientes.forEach(cliente => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'list-group-item list-group-item-action';
            item.innerHTML = `
                <strong>${cliente.numdocumento}</strong> - 
                ${cliente.nombres} ${cliente.apellidopaterno} ${cliente.apellidomaterno || ''}
            `;
            item.addEventListener('click', () => {
                seleccionarCliente(cliente);
            });
            contenedor.appendChild(item);
        });

        contenedor.style.display = 'block';
    }

    /**
     * Seleccionar cliente
     */
    function seleccionarCliente(cliente) {
        document.getElementById('idcliente').value = cliente.idcliente;
        document.getElementById('buscar-cliente').value = cliente.numdocumento;
        document.getElementById('nombre-cliente').textContent =
            `${cliente.nombres} ${cliente.apellidopaterno} ${cliente.apellidomaterno || ''}`;
        document.getElementById('info-cliente-seleccionado').style.display = 'block';
        document.getElementById('sugerencias-clientes').style.display = 'none';
    }
});