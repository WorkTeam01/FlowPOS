<?php
// Incluir el archivo de sesión
require_once __DIR__ . '/session.php';

// Verificar si el usuario está autenticado
requireLogin();

// Obtener datos del usuario actual
$currentUser = getCurrentUser();
$idusuariosesion = $currentUser['id'];

// Incluir el servicio de autorización
require_once __DIR__ . '/../../services/AuthorizationService.php';
$authService = new AuthorizationService();

// Usar la variable global URL
global $URL;

?>

<!DOCTYPE html>

<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= csrfMetaTag() ?>
    <meta name="app-url" content="<?= $URL; ?>">

    <title><?= $appName ?></title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Bootstrap -->
    <link rel="stylesheet" href="<?= $URL; ?>public/css/lib/bootstrap/bootstrap.min.css">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="<?= $URL; ?>public/css/lib/fontawesome/all.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?= $URL; ?>public/css/lib/adminlte/adminlte.min.css">
    <!-- Font Awesome Webfonts -->
    <link rel="stylesheet" href="<?= $URL; ?>public/css/core/webfonts.css">
    <link rel="stylesheet" href="<?= $URL; ?>public/css/core/common.css">
    <link rel="icon" type="image/png" href="<?= $URL; ?>public/img/logo_ventas.svg">
    <!-- Datatables -->
    <link rel="stylesheet" href="<?= $URL; ?>public/css/plugins/datatables/datatables.min.css">
    <link rel="stylesheet" href="<?= $URL; ?>public/css/plugins/datatables/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="<?= $URL; ?>public/css/plugins/datatables/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="<?= $URL; ?>public/css/plugins/datatables/buttons.bootstrap4.min.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="<?= $URL; ?>public/css/plugins/select2/select2.min.css">
    <link rel="stylesheet" href="<?= $URL; ?>public/css/plugins/select2/select2-bootstrap4.min.css">
    <!-- Sweetalert2 -->
    <link rel="stylesheet" href="<?= $URL; ?>public/css/plugins/sweetalert2/sweetalert2.min.css">
    <script src="<?= $URL; ?>public/js/plugins/sweetalert2/sweetalert2.min.js"></script>
    <!-- jQuery -->
    <script src="<?= $URL; ?>public/js/lib/jquery/jquery.min.js"></script>

    <!-- Estilos específicos por módulo -->
    <?php if (isset($module_styles) && is_array($module_styles)): ?>
        <?php foreach ($module_styles as $style): ?>
            <link rel="stylesheet" href="<?= $URL; ?>public/css/modules/<?= $style; ?>.css">
        <?php endforeach; ?>
    <?php endif; ?>

    <script>
        const baseUrl = '<?= $URL ?>';
        window.APP = {
            name: '<?= addslashes($appName) ?>',
            version: '<?= addslashes($appVersion) ?>',
            currency: '<?= addslashes($appCurrency) ?>'
        };
    </script>
</head>

<body class="hold-transition sidebar-mini">
    <div class="wrapper">

        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-light">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="<?= $URL; ?>" class="nav-link">FlowPOS</a>
                </li>
            </ul>

            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                        <i class="fas fa-expand-arrows-alt"></i>
                    </a>
                </li>
                <li class="nav-item dropdown user-menu">
                    <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                        <i class="fas fa-user"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <!-- User image -->
                        <li class="user-header">
                            <img src="<?= $URL; ?>public/uploads/usuarios/<?= $currentUser['imagen']; ?>" loading="eager" class="img-circle elevation-2" alt="User Image">
                            <p>
                                <?= htmlspecialchars($currentUser['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                                <small><?= htmlspecialchars($currentUser['cargo'], ENT_QUOTES, 'UTF-8'); ?></small>
                            </p>
                        </li>
                        <!-- Menu Footer-->
                        <li class="user-footer">
                            <?php if ($authService->tienePermisoNombre($idusuariosesion, 'perfil')) : ?>
                                <a href="<?= $URL; ?>views/usuarios/perfil.php?id=<?= $currentUser['id']; ?>" class="btn btn-default btn-flat">Perfil</a>
                            <?php endif; ?>
                            <a href="#" onclick="submitCsrfForm('<?= $URL; ?>controllers/auth/logout.php', {}); return false;" class="btn btn-default btn-flat float-right">Cerrar Sesión</a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-light-primary elevation-1">
            <!-- Brand Logo -->
            <a href="<?= $URL; ?>" class="brand-link">
                <img src="<?= $URL; ?>public/img/logo_ventas.svg" loading="eager" alt="Logo" class="brand-image img-circle elevation-0" style="opacity: .8">
                <span class="brand-text"><?= $appName ?></span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">

                <!-- Sidebar Menu -->
                <nav class="mt-4">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                        <!-- Dashboard -->
                        <li class="nav-item">
                            <a href="<?= $URL; ?>" class="nav-link">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>

                        <!-- Ventas -->
                        <?php if (
                            $authService->tienePermisoNombre($idusuariosesion, 'ventas') ||
                            $authService->tienePermisoNombre($idusuariosesion, 'nueva_venta') ||
                            $authService->tienePermisoNombre($idusuariosesion, 'clientes')
                        ) : ?>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon fas fa-cash-register"></i>
                                    <p>
                                        Ventas
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <!-- Nueva Venta -->
                                    <?php if ($authService->tienePermisoNombre($idusuariosesion, 'nueva_venta')) : ?>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/ventas/nueva.php" class="nav-link">
                                                <i class="fas fa-plus nav-icon"></i>
                                                <p>Nueva venta</p>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <!-- Ventas -->
                                    <?php if ($authService->tienePermisoNombre($idusuariosesion, 'ventas')) : ?>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/ventas" class="nav-link">
                                                <i class="fas fa-list nav-icon"></i>
                                                <p>Historial de ventas</p>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <!-- Clientes -->
                                    <?php if ($authService->tienePermisoNombre($idusuariosesion, 'clientes')) : ?>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/clientes" class="nav-link">
                                                <i class="nav-icon fas fa-users"></i>
                                                <p>Clientes</p>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                        <?php endif; ?>

                        <!-- Inventario -->
                        <?php if ($authService->tienePermisoNombre($idusuariosesion, 'productos') || $authService->tienePermisoNombre($idusuariosesion, 'categorias')) : ?>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon fas fa-boxes"></i>
                                    <p>
                                        Inventario
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <!-- Productos -->
                                    <?php if ($authService->tienePermisoNombre($idusuariosesion, 'productos')) : ?>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/productos" class="nav-link">
                                                <i class="fas fa-shoe-prints nav-icon"></i>
                                                <p>Productos</p>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <!-- Categorías -->
                                    <?php if ($authService->tienePermisoNombre($idusuariosesion, 'categorias')) : ?>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/categorias" class="nav-link">
                                                <i class="fas fa-tags nav-icon"></i>
                                                <p>Categorías</p>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                        <?php endif; ?>

                        <!-- Compras -->
                        <?php if ($authService->tienePermisoNombre($idusuariosesion, 'compras') || $authService->tienePermisoNombre($idusuariosesion, 'nueva_compra')) : ?>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon fas fa-shopping-cart"></i>
                                    <p>
                                        Compras
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <!-- Nueva Compra -->
                                    <?php if ($authService->tienePermisoNombre($idusuariosesion, 'nueva_compra')) : ?>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/compras/ingresar.php" class="nav-link">
                                                <i class="fas fa-plus nav-icon"></i>
                                                <p>Nueva compra</p>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <!-- Historial de Compras -->
                                    <?php if ($authService->tienePermisoNombre($idusuariosesion, 'compras')) : ?>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/compras" class="nav-link">
                                                <i class="fas fa-list nav-icon"></i>
                                                <p>Historial de compras</p>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                        <?php endif; ?>

                        <!-- Configuración -->
                        <?php if ($authService->tienePermisoNombre($idusuariosesion, 'empresa') || $authService->tienePermisoNombre($idusuariosesion, 'sucursales')) : ?>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon fas fa-cogs"></i>
                                    <p>
                                        Configuración
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <!-- Empresa -->
                                    <?php if ($authService->tienePermisoNombre($idusuariosesion, 'empresa')) : ?>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/empresa" class="nav-link">
                                                <i class="fas fa-building nav-icon"></i>
                                                <p>Empresa</p>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <!-- Sucursales -->
                                    <?php if ($authService->tienePermisoNombre($idusuariosesion, 'sucursales')) : ?>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/sucursales" class="nav-link">
                                                <i class="fas fa-store-alt nav-icon"></i>
                                                <p>Sucursales</p>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                        <?php endif; ?>

                        <!-- Administración -->
                        <?php if ($authService->tienePermisoNombre($idusuariosesion, 'usuarios') || $authService->tienePermisoNombre($idusuariosesion, 'permisos') || $authService->tienePermisoNombre($idusuariosesion, 'sesiones')) : ?>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon fas fa-user-shield"></i>
                                    <p>
                                        Administración
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <?php if ($authService->tienePermisoNombre($idusuariosesion, 'usuarios')) : ?>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/usuarios" class="nav-link">
                                                <i class="fas fa-user-alt nav-icon"></i>
                                                <p>Usuarios</p>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <?php if ($authService->tienePermisoNombre($idusuariosesion, 'permisos')) : ?>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/permisos" class="nav-link">
                                                <i class="fas fa-key nav-icon"></i>
                                                <p>Permisos</p>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <?php if ($authService->tienePermisoNombre($idusuariosesion, 'sesiones')) : ?>
                                        <li class="nav-item">
                                            <a href="<?= $URL; ?>views/sesiones" class="nav-link">
                                                <i class="fas fa-user-clock nav-icon"></i>
                                                <p>Sesiones</p>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <main class="content-wrapper">