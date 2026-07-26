$(document).ready(function () {
    initializeSelect2();

    // Validación del formulario
    $('form').submit(function (e) {
        let isValid = true;

        // Validar campos obligatorios
        $('input[required], select[required]').each(function () {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        // Validar email si se ingresó
        let email = $('#email').val();
        if (email && !validateEmail(email)) {
            $('#email').addClass('is-invalid');
            if (!$('#email').next('.invalid-feedback').length) {
                $('#email').after('<div class="invalid-feedback">Ingrese un email válido</div>');
            }
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                text: 'Por favor corrija los errores en el formulario'
            });
            return false;
        }
    });
});

// Función para validar formato de email
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(String(email).toLowerCase());
}
