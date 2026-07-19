<?php
// Incluir archivo de sesión
require_once __DIR__ . '/../../views/layouts/session.php';

// Verificar si ya hay una sesión activa
if (isAuthenticated()) {
    header('Location: ../../index.php');
    exit;
}

// Incluir la configuración
require_once __DIR__ . '/../../config/config.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $appName ?> | Iniciar Sesión</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= $URL; ?>public/css/lib/fontawesome/all.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?= $URL; ?>public/css/lib/adminlte/adminlte.min.css">
    <!-- Font Awesome Webfonts -->
    <link rel="stylesheet" href="<?= $URL; ?>public/css/core/webfonts.css">
    <link rel="icon" type="image/png" href="<?= $URL; ?>public/img/logo_ventas.svg">
    <!-- iCheck -->
    <link rel="stylesheet" href="<?= $URL; ?>public/css/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <!-- Custom login styles -->
    <link rel="stylesheet" href="<?= $URL; ?>public/css/modules/login/login.css">
    <!-- Sweetalert2 -->
    <link rel="stylesheet" href="<?= $URL; ?>public/css/plugins/sweetalert2/sweetalert2.min.css">
    <script src="<?= $URL; ?>public/js/plugins/sweetalert2/sweetalert2.min.js"></script>
</head>

<body class="hold-transition login-page">
    <main class="login-box">
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <h1 class="h3"><?= $appName ?></h1>
                <small class="text-muted d-block mt-1">v<?= htmlspecialchars($appVersion) ?></small>
            </div>
            <div class="card-body login-card-body">
                <p class="login-box-msg">Ingrese sus credenciales para acceder</p>

                <form action="<?= $URL; ?>controllers/auth/login.php" method="post" id="login-form" novalidate>
                    <!-- Token CSRF para protección contra CSRF -->
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="input-group mb-1">
                        <label for="identifier-field" class="sr-only">Email o Número de documento</label>
                        <input type="text" name="identifier" id="identifier-field" class="form-control" placeholder="Email o Número de documento"
                            autocomplete="username" autofocus required aria-describedby="identifier-error">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user" aria-hidden="true"></span>
                            </div>
                        </div>
                        <div class="invalid-feedback" id="identifier-error"></div>
                    </div>
                    <div class="input-group mt-3 mb-1">
                        <label for="password-field" class="sr-only">Contraseña</label>
                        <input type="password" name="clave" id="password-field" class="form-control" placeholder="Contraseña"
                            autocomplete="current-password" required aria-describedby="password-error">
                        <div class="input-group-append">
                            <button type="button" class="input-group-text password-toggle" id="toggle-password"
                                aria-label="Mostrar contraseña" aria-pressed="false">
                                <span class="fas fa-eye-slash" aria-hidden="true"></span>
                            </button>
                        </div>
                        <div class="invalid-feedback" id="password-error"></div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-block" id="login-submit">
                                <i class="fas fa-sign-in-alt mr-2" aria-hidden="true"></i> Iniciar Sesión
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="login-footer text-center mt-3">
            <p class="text-muted">&copy; <?= date('Y'); ?> <?= $appName ?>. Todos los derechos reservados.</p>
        </div>
    </main>

    <!-- jQuery -->
    <script src="<?= $URL; ?>public/js/lib/jquery/jquery.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="<?= $URL; ?>public/js/lib/bootstrap/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="<?= $URL; ?>public/js/lib/adminlte/adminlte.min.js"></script>

    <!-- Custom login script -->
    <script>
        $(document).ready(function() {
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
            $('#toggle-password').click(function() {
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
            $('#login-form').on('submit', function(e) {
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
            $('input').on('input', function() {
                $(this).removeClass('is-invalid');
                const errorId = this.id === 'identifier-field' ? 'identifier-error' : 'password-error';
                $('#' + errorId).text('');
            });
        });
    </script>

    <?php
    // Incluir mensajes
    require_once __DIR__ . '/../layouts/mensajes.php';
    ?>
</body>

</html>