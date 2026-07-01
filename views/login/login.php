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
    <div class="login-box">
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <h1 class="h3"><?= $appName ?></h1>
                <small class="text-muted d-block mt-1">v<?= htmlspecialchars($appVersion) ?></small>
            </div>
            <div class="card-body login-card-body">
                <p class="login-box-msg">Ingrese sus credenciales para acceder</p>

                <form action="<?= $URL; ?>controllers/auth/login.php" method="post" id="login-form">
                    <!-- Token CSRF para protección contra CSRF -->
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="input-group mb-3">
                        <input type="text" name="identifier" class="form-control" placeholder="Email o Número de documento"
                            autocomplete="username">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-user"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" name="clave" id="password-field" class="form-control" placeholder="Contraseña"
                            autocomplete="current-password">
                        <div class="input-group-append">
                            <div class="input-group-text password-toggle" title="Mostrar/Ocultar contraseña">
                                <span class="fas fa-eye-slash toggle-password" id="toggle-password"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-sign-in-alt mr-2"></i> Iniciar Sesión
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="login-footer text-center mt-3">
            <p class="text-muted">&copy; <?= date('Y'); ?> <?= $appName ?>. Todos los derechos reservados.</p>
        </div>
    </div>

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
                const passwordFieldType = passwordField.attr('type');

                // Toggle password visibility
                if (passwordFieldType === 'password') {
                    passwordField.attr('type', 'text');
                    $(this).removeClass('fa-eye-slash').addClass('fa-eye');
                } else {
                    passwordField.attr('type', 'password');
                    $(this).removeClass('fa-eye').addClass('fa-eye-slash');
                }
            });

            // Add subtle animation to login box
            $('.login-box').addClass('login-animation');

            // Form submission with validation
            $('#login-form').on('submit', function(e) {
                e.preventDefault(); // Prevent default form submission

                const identifier = $('input[name="identifier"]').val().trim();
                const password = $('input[name="clave"]').val().trim();
                let isValid = true;

                // Validar que los campos no estén vacíos
                if (!identifier) {
                    $('input[name="identifier"]').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('input[name="identifier"]').removeClass('is-invalid');
                }

                if (!password) {
                    $('input[name="clave"]').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('input[name="clave"]').removeClass('is-invalid');
                }

                if (!isValid) {
                    Toast.fire({
                        icon: 'error',
                        title: 'Por favor complete todos los campos'
                    });
                    return;
                }

                // Validar la longitud de la contraseña
                if (password.length < 6) {
                    $('input[name="clave"]').addClass('is-invalid');
                    Toast.fire({
                        icon: 'error',
                        title: 'La contraseña debe tener al menos 6 caracteres'
                    });
                    return;
                }

                // Show loading state
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    title: 'Iniciando sesión...',
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Submit the form after small delay to show loading
                setTimeout(() => {
                    this.submit();
                }, 1000);
            });

            // Remove invalid class on input
            $('input').on('input', function() {
                $(this).removeClass('is-invalid');
            });
        });
    </script>

    <?php
    // Incluir mensajes
    require_once __DIR__ . '/../layouts/mensajes.php';
    ?>
</body>

</html>