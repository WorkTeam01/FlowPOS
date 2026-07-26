$(document).ready(function () {
    // Configuración de Toast para SweetAlert2
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    // Toggle password visibility
    $('#toggle-password').click(function () {
        const passwordField = $('#password-field');
        const isHidden = passwordField.attr('type') === 'password';
        const icon = $(this).find('span');

        passwordField.attr('type', isHidden ? 'text' : 'password');
        icon.toggleClass('fa-eye-slash', !isHidden).toggleClass('fa-eye', isHidden);
        $(this)
            .attr('aria-label', isHidden ? 'Ocultar contraseña' : 'Mostrar contraseña')
            .attr('aria-pressed', isHidden ? 'true' : 'false');
    });

    // Add subtle animation to login box
    $('.login-box').addClass('login-animation');

    // Form submission with validation
    $('#login-form').on('submit', function (e) {
        const identifierField = $('input[name="identifier"]');
        const passwordField = $('input[name="clave"]');
        const identifier = identifierField.val().trim();
        const password = passwordField.val().trim();
        let firstInvalid = null;

        identifierField.removeClass('is-invalid');
        $('#identifier-error').text('');
        passwordField.removeClass('is-invalid');
        $('#password-error').text('');

        if (!identifier) {
            identifierField.addClass('is-invalid');
            $('#identifier-error').text('Ingrese su email o número de documento.');
            firstInvalid = firstInvalid || identifierField;
        }

        if (!password) {
            passwordField.addClass('is-invalid');
            $('#password-error').text('Ingrese su contraseña.');
            firstInvalid = firstInvalid || passwordField;
        } else if (password.length < 6) {
            passwordField.addClass('is-invalid');
            $('#password-error').text('La contraseña debe tener al menos 6 caracteres.');
            firstInvalid = firstInvalid || passwordField;
        }

        if (firstInvalid) {
            e.preventDefault();
            firstInvalid.trigger('focus');
            return;
        }

        // Deshabilitar el botón para evitar doble envío mientras el servidor procesa
        $('#login-submit').prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span> Iniciando sesión...');
    });

    // Remove invalid class on input
    $('input').on('input', function () {
        $(this).removeClass('is-invalid');
        const errorId = this.id === 'identifier-field' ? 'identifier-error' : 'password-error';
        $('#' + errorId).text('');
    });

    // Mostrar mensaje flash de sesión (dejado por views/layouts/mensajes.php), si existe
    const flash = document.getElementById('flash-mensaje');
    if (flash) {
        Toast.fire({
            icon: flash.dataset.icono,
            title: flash.dataset.mensaje
        });
    }
});
