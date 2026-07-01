$(document).ready(function () {
    // Actualizar etiqueta del archivo seleccionado
    $('.custom-file-input').on('change', function () {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);

        // Mostrar vista previa de la imagen
        if (this.files && this.files[0]) {
            let reader = new FileReader();
            reader.onload = function (e) {
                $('#preview-image').attr('src', e.target.result);
                $('#preview-container').show();
            }
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Validación de precios
    $('#precioventa').on('keyup', function () {
        let precioCompra = parseFloat($('#preciocompra').val()) || 0;
        let precioVenta = parseFloat($(this).val()) || 0;

        if (precioVenta < precioCompra) {
            $(this).addClass('is-invalid');
            if (!$(this).next('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">El precio de venta no puede ser menor al de compra</div>');
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        }
    });

    // Validación de stock mínimo/máximo
    $('#stockmaximo').on('keyup', function () {
        let stockMinimo = parseInt($('#stockminimo').val()) || 0;
        let stockMaximo = parseInt($(this).val()) || 0;

        if (stockMaximo > 0 && stockMaximo < stockMinimo) {
            $(this).addClass('is-invalid');
            if (!$(this).next('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">El stock máximo no puede ser menor al mínimo</div>');
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        }
    });

    // Validación del formulario antes de enviar
    $('form').on('submit', function (e) {
        let precioCompra = parseFloat($('#preciocompra').val()) || 0;
        let precioVenta = parseFloat($('#precioventa').val()) || 0;
        let stockMinimo = parseInt($('#stockminimo').val()) || 0;
        let stockMaximo = parseInt($('#stockmaximo').val()) || 0;

        // Validar precios
        if (precioVenta < precioCompra) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'El precio de venta no puede ser menor al precio de compra'
            });
            return false;
        }

        // Validar stock
        if (stockMaximo > 0 && stockMaximo < stockMinimo) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'El stock máximo no puede ser menor al stock mínimo'
            });
            return false;
        }

        // Validar categoría seleccionada
        if ($('#idcategoria').val() === '') {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe seleccionar una categoría'
            });
            return false;
        }
    });

    // Validación de código único (simulación con AJAX)
    $('#codigo').on('blur', function () {
        let codigo = $(this).val();
        if (codigo.length > 0) {
            // Aquí iría la llamada AJAX para verificar si el código existe
            // Simulamos una respuesta después de 500ms
            setTimeout(() => {
                // Simulación: si el código contiene "123" lo consideramos como existente
                if (codigo.includes('123')) {
                    $(this).addClass('is-invalid');
                    if (!$(this).next('.invalid-feedback').length) {
                        $(this).after('<div class="invalid-feedback">Este código ya está registrado</div>');
                    }
                    Swal.fire({
                        icon: 'warning',
                        title: 'Código duplicado',
                        text: 'Este código de producto ya está registrado en el sistema'
                    });
                } else {
                    $(this).removeClass('is-invalid');
                    $(this).next('.invalid-feedback').remove();
                }
            }, 500);
        }
    });
});