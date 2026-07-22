<?php
require_once __DIR__ . '/../../controllers/permisos/PermisoController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'] ?? '';

$authService = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!($authService->tienePermisoNombre($idusuario, 'permisos')) && !($authService->esAdministrador($idusuario))) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL);
    exit;
}

// Incluir el encabezado DESPUÉS de verificar permisos
$skip_select2 = true; // Esta vista no usa Select2
include_once '../layouts/header.php';

$module_scripts = ['permisos/index-permisos'];

$controller = new PermisoController();
$permisos = $controller->index();
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Gestión de Permisos</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Permisos</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <!-- Lista de permisos - Columna Principal (8) -->
            <div class="col-md-8">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Listado de Permisos del Sistema</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tablaPermisos" class="table table-bordered table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 10px">#</th>
                                        <th>Nombre</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador = 1;
                                    foreach ($permisos as $permiso) :
                                        $estado = $permiso['estado'];
                                        $clase_estado = $estado ? 'badge-success' : 'badge-danger';
                                        $texto_estado = $estado ? 'Activo' : 'Inactivo';
                                    ?>
                                        <tr>
                                            <td><?= $contador++; ?></td>
                                            <td><?= htmlspecialchars($permiso['nombre']); ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= $clase_estado; ?>"><?= $texto_estado; ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>

            <!-- Información de permisos - Columna Lateral (4) -->
            <div class="col-md-4">
                <!-- Permisos de Operaciones -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Permisos de Operaciones</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <i class="fas fa-cash-register text-primary mr-2"></i> <strong>ventas</strong>
                                <p class="text-muted mb-0 small">Gestión completa de ventas</p>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-cart-plus text-success mr-2"></i> <strong>nueva_venta</strong>
                                <p class="text-muted mb-0 small">Crear nueva venta</p>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-users text-warning mr-2"></i> <strong>clientes</strong>
                                <p class="text-muted mb-0 small">Gestión de clientes</p>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-shopping-cart text-info mr-2"></i> <strong>compras</strong>
                                <p class="text-muted mb-0 small">Gestión de compras</p>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-cart-arrow-down text-danger mr-2"></i> <strong>nueva_compra</strong>
                                <p class="text-muted mb-0 small">Crear nueva compra</p>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Permisos de Inventario -->
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">Permisos de Inventario</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <i class="fas fa-shoe-prints text-primary mr-2"></i> <strong>productos</strong>
                                <p class="text-muted mb-0 small">Gestión de productos</p>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-tags text-warning mr-2"></i> <strong>categorias</strong>
                                <p class="text-muted mb-0 small">Gestión de categorías</p>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Permisos Administrativos -->
                <div class="card card-danger">
                    <div class="card-header">
                        <h3 class="card-title">Permisos Administrativos</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <i class="fas fa-building text-primary mr-2"></i> <strong>empresa</strong>
                                <p class="text-muted mb-0 small">Configuración de empresa</p>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-store-alt text-success mr-2"></i> <strong>sucursales</strong>
                                <p class="text-muted mb-0 small">Gestión de sucursales</p>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-user-tie text-info mr-2"></i> <strong>usuarios</strong>
                                <p class="text-muted mb-0 small">Administración de usuarios</p>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-key text-warning mr-2"></i> <strong>permisos</strong>
                                <p class="text-muted mb-0 small">Gestión de permisos</p>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-user-clock text-danger mr-2"></i> <strong>sesiones</strong>
                                <p class="text-muted mb-0 small">Monitoreo de sesiones</p>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-user mr-2"></i> <strong>perfil</strong>
                                <p class="text-muted mb-0 small">Gestión del perfil de usuario</p>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container-fluid -->
</section>
<!-- /.content -->

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>