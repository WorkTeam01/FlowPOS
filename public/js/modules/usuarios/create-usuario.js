$(document).ready(function () {
    // Inicializar Select2
    initializeSelect2();

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

    // Validación de contraseñas
    $('#confirmar_clave').on('keyup', function () {
        let clave = $('#clave').val();
        let confirmar = $(this).val();

        if (clave !== confirmar) {
            $(this).addClass('is-invalid');
            if (!$(this).next('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">Las contraseñas no coinciden</div>');
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        }
    });

    // Validación del formulario antes de enviar
    $('form').on('submit', function (e) {
        let clave = $('#clave').val();
        let confirmar = $('#confirmar_clave').val();

        if (clave !== confirmar) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Las contraseñas no coinciden'
            });
            return false;
        }

        if (clave.length < 6) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'La contraseña debe tener al menos 6 caracteres'
            });
            return false;
        }
    });

    // Validación de número de documento según tipo
    $('#tipodocumento').on('change', function () {
        let tipo = $(this).val();
        let numDocInput = $('#numdocumento');

        switch (tipo) {
            case 'DNI':
                numDocInput.attr('maxlength', '8');
                numDocInput.attr('pattern', '[0-9]{8}');
                numDocInput.attr('placeholder', 'Ingrese 8 dígitos');
                break;
            case 'RUC':
                numDocInput.attr('maxlength', '11');
                numDocInput.attr('pattern', '[0-9]{11}');
                numDocInput.attr('placeholder', 'Ingrese 11 dígitos');
                break;
            case 'Pasaporte':
                numDocInput.attr('maxlength', '12');
                numDocInput.removeAttr('pattern');
                numDocInput.attr('placeholder', 'Ingrese el número de pasaporte');
                break;
            default:
                numDocInput.removeAttr('maxlength');
                numDocInput.removeAttr('pattern');
                numDocInput.attr('placeholder', 'Ingrese el número de documento');
        }
    });

    // Botón para seleccionar todos los permisos
    $('#seleccionar-todos').click(function () {
        $('input[name="permisos[]"]').prop('checked', true);

        // Efecto visual
        Swal.fire({
            position: 'top-end',
            icon: 'success',
            title: 'Todos los permisos seleccionados',
            showConfirmButton: false,
            timer: 3000,
            toast: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        });
    });

    // Botón para deseleccionar todos los permisos
    $('#deseleccionar-todos').click(function () {
        $('input[name="permisos[]"]').prop('checked', false);

        // Efecto visual
        Swal.fire({
            position: 'top-end',
            icon: 'info',
            title: 'Todos los permisos deseleccionados',
            showConfirmButton: false,
            timer: 3000,
            toast: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        });
    });
});