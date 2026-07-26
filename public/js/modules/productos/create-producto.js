$(document).ready(function() {
    // Inicializar Select2
    initializeSelect2();

    // Actualizar etiqueta del archivo seleccionado
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);

        // Mostrar vista previa de la imagen
        if (this.files && this.files[0]) {
            let reader = new FileReader();
            reader.onload = function(e) {
                $('#preview-image').attr('src', e.target.result);
                $('#preview-container').show();
            }
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Validación de precios
    $('#precioventa, #preciocompra').on('input', function() {
        let precioCompra = parseFloat($('#preciocompra').val()) || 0;
        let precioVenta = parseFloat($('#precioventa').val()) || 0;

        // Validar que el precio de venta sea mayor al de compra
        if (precioVenta > 0 && precioCompra > 0) {
            if (precioVenta < precioCompra) {
                $('#precio-feedback').html('<div class="text-danger mt-1"><i class="fas fa-exclamation-triangle"></i> El precio de venta no puede ser menor al precio de compra</div>');
            } else {
                // Calcular y mostrar el margen de ganancia
                let ganancia = precioVenta - precioCompra;
                let margenPorcentaje = (ganancia / precioCompra) * 100;

                let mensajeClase = 'text-success';
                let mensajeIcono = 'fas fa-check-circle';

                if (margenPorcentaje < 10) {
                    mensajeClase = 'text-danger';
                    mensajeIcono = 'fas fa-exclamation-circle';
                } else if (margenPorcentaje < 20) {
                    mensajeClase = 'text-warning';
                    mensajeIcono = 'fas fa-exclamation-triangle';
                }

                $('#precio-feedback').html(
                    `<div class="${mensajeClase} mt-1">
                        <i class="${mensajeIcono}"></i>
                        Margen de ganancia: ${margenPorcentaje.toFixed(2)}%
                        (${window.APP.currency} ${ganancia.toFixed(2)})
                    </div>`
                );
            }
        } else {
            $('#precio-feedback').html('');
        }
    });

    // Validación de stock mínimo/máximo
    $('#stockminimo, #stockmaximo').on('input', function() {
        let stockMinimo = parseInt($('#stockminimo').val()) || 0;
        let stockMaximo = parseInt($('#stockmaximo').val()) || 0;

        // Solo validar si ambos tienen valores y stockmaximo no está vacío
        if (stockMaximo > 0) {
            if (stockMaximo < stockMinimo) {
                $('#stock-validation-feedback').html(
                    `<div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        El stock máximo (${stockMaximo}) no puede ser menor al stock mínimo (${stockMinimo})
                    </div>`
                );
            } else {
                $('#stock-validation-feedback').html(
                    `<div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        Configuración de stock válida. Rango: ${stockMinimo} - ${stockMaximo} unidades
                    </div>`
                );
            }
        } else {
            $('#stock-validation-feedback').html('');
        }
    });

    // Validación del formulario antes de enviar
    $('#formCrearProducto').on('submit', function(e) {
        let precioCompra = parseFloat($('#preciocompra').val()) || 0;
        let precioVenta = parseFloat($('#precioventa').val()) || 0;
        let stockMinimo = parseInt($('#stockminimo').val()) || 0;
        let stockMaximo = parseInt($('#stockmaximo').val()) || 0;

        // Validar precios
        if (precioVenta < precioCompra) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error en Precios',
                text: 'El precio de venta no puede ser menor al precio de compra',
                confirmButtonColor: '#3085d6'
            });
            return false;
        }

        // Validar stock
        if (stockMaximo > 0 && stockMaximo < stockMinimo) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error en Stock',
                text: 'El stock máximo no puede ser menor al stock mínimo',
                confirmButtonColor: '#3085d6'
            });
            return false;
        }

        // Validar categoría seleccionada
        if ($('#idcategoria').val() === '') {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Categoría Requerida',
                text: 'Debe seleccionar una categoría para el producto',
                confirmButtonColor: '#3085d6'
            });
            return false;
        }

        // Si todo es válido, mostrar mensaje de carga
        Swal.fire({
            title: 'Guardando producto...',
            html: 'Por favor espere mientras se guarda la información',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    });
});
