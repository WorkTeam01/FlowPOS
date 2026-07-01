$(document).ready(function () {
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

    // Validación de email si existe
    $('#email').on('blur', function() {
        let email = $(this).val();
        if (email) {
            if (!validateEmail(email)) {
                $(this).addClass('is-invalid');
                if (!$(this).next('.invalid-feedback').length) {
                    $(this).after('<div class="invalid-feedback">Ingrese un email válido</div>');
                }
            } else {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
            }
        }
    });

    // Validación de celular
    $('#celular').on('blur', function() {
        let celular = $(this).val();
        if (celular) {
            if (!/^[0-9]{9,15}$/.test(celular)) {
                $(this).addClass('is-invalid');
                if (!$(this).next('.invalid-feedback').length) {
                    $(this).after('<div class="invalid-feedback">Ingrese un número de celular válido (9-15 dígitos)</div>');
                }
            } else {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
            }
        }
    });

    // Validación de género (campo obligatorio)
    $('#genero').on('change', function() {
        if ($(this).val() === '') {
            $(this).addClass('is-invalid');
            if (!$(this).next('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">Seleccione un género</div>');
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        }
    });

    // Validación del formulario antes de enviar
    $('form').on('submit', function (e) {
        let isValid = true;
        
        // Validar email si se ingresó
        let email = $('#email').val();
        if (email && !validateEmail(email)) {
            $('#email').addClass('is-invalid');
            if (!$('#email').next('.invalid-feedback').length) {
                $('#email').after('<div class="invalid-feedback">Ingrese un email válido</div>');
            }
            isValid = false;
        }

        // Validar celular si se ingresó
        let celular = $('#celular').val();
        if (celular && !/^[0-9]{9,15}$/.test(celular)) {
            $('#celular').addClass('is-invalid');
            if (!$('#celular').next('.invalid-feedback').length) {
                $('#celular').after('<div class="invalid-feedback">Ingrese un número de celular válido</div>');
            }
            isValid = false;
        }

        // Validar género (obligatorio)
        if ($('#genero').val() === '') {
            $('#genero').addClass('is-invalid');
            if (!$('#genero').next('.invalid-feedback').length) {
                $('#genero').after('<div class="invalid-feedback">Seleccione un género</div>');
            }
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            showError('Por favor corrija los errores en el formulario');
            $('html, body').animate({
                scrollTop: $('.is-invalid').first().offset().top - 100
            }, 500);
            return false;
        }
    });
});

// Función para validar formato de email
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(String(email).toLowerCase());
}

// Función para mostrar errores con SweetAlert
function showError(message) {
    Swal.fire({
        icon: 'error',
        title: 'Error de validación',
        text: message
    });
}