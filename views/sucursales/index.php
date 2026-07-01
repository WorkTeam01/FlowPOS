<?php
require_once __DIR__ . '/../../controllers/sucursal/SucursalController.php';
require_once __DIR__ . '/../../controllers/empresa/EmpresaController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$idusuario = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo ANTES de incluir el header
if (!($auth->tienePermisoNombre($idusuario, 'sucursales')) && !($auth->esAdministrador($idusuario))) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';

    // Redirigir al inicio
    header('Location: ' . $URL . 'index.php');
    exit;
}

// Incluir el encabezado después de verificar permisos
include_once '../layouts/header.php';

// Obtener empresas desde la base de datos
$empresaController = new EmpresaController();
$empresas = $empresaController->index(true);

$controller = new SucursalController();
$sucursales = $controller->index();
$estadisticas = $controller->getEstadisticas();
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Gestión de Sucursales</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Sucursales</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- Info boxes -->
        <div class="row">
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-store"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total de Sucursales</span>
                        <span class="info-box-number"><?= $estadisticas['total']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Sucursales Activas</span>
                        <span class="info-box-number"><?= $estadisticas['activas']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-times-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Sucursales Inactivas</span>
                        <span class="info-box-number"><?= $estadisticas['inactivas']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Usuarios en sucursales</span>
                        <span class="info-box-number">
                            <?= array_sum(array_column($estadisticas['usuarios_por_sucursal'], 'total_usuarios')); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center">
                            <h3 class="card-title mb-2 mb-sm-0">Sucursales registradas</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-primary btn-sm me-2" id="btnNuevaSucursal">
                                    <i class="fas fa-plus"></i> Nueva Sucursal
                                </button>
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body" style="display: block;">
                        <div class="table-responsive">
                            <table id="tablaSucursales" class="table table-bordered table-hover table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 5%">Nro</th>
                                        <th class="text-center" style="width: 30%">Nombre</th>
                                        <th class="text-center" style="width: 25%">Empresa</th>
                                        <th class="text-center" style="width: 15%">Usuarios</th>
                                        <th class="text-center" style="width: 10%">Estado</th>
                                        <th class="text-center" style="width: 15%">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador = 1;
                                    foreach ($sucursales as $sucursal) :
                                        $estado_actual = $sucursal['estado'];
                                        $clase_estado = $estado_actual == 1 ? 'badge-success' : 'badge-danger';
                                        $texto_estado = $estado_actual == 1 ? 'Activo' : 'Inactivo';
                                        $total_usuarios = $sucursal['total_usuarios'] ?? 0;
                                        $clase_usuarios = $total_usuarios > 0 ? 'badge-primary' : 'badge-secondary';
                                    ?>
                                        <tr>
                                            <td class="text-center"><?= $contador++; ?></td>
                                            <td><?= htmlspecialchars($sucursal['nombre']); ?></td>
                                            <td><?= htmlspecialchars($sucursal['empresa_nombre'] ?? 'Sin empresa'); ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= $clase_usuarios; ?>">
                                                    <?= $total_usuarios; ?> usuario<?= $total_usuarios != 1 ? 's' : ''; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?= $clase_estado; ?>"><?= $texto_estado; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-warning btn-sm btn-editar"
                                                        data-id="<?= $sucursal['idsucursal']; ?>"
                                                        data-nombre="<?= htmlspecialchars($sucursal['nombre']); ?>"
                                                        data-idempresa="<?= $sucursal['idempresa']; ?>"
                                                        data-estado="<?= $sucursal['estado']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn <?= $estado_actual == 1 ? 'btn-danger' : 'btn-success'; ?> btn-sm cambiar-estado"
                                                        data-id="<?= $sucursal['idsucursal']; ?>"
                                                        data-estado-actual="<?= $estado_actual; ?>"
                                                        data-usuarios="<?= $total_usuarios; ?>"
                                                        data-toggle="tooltip"
                                                        title="<?= $estado_actual == 1 ? 'Desactivar' : 'Activar'; ?>">
                                                        <i class="fas <?= $estado_actual == 1 ? 'fa-times' : 'fa-check'; ?>"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal para Sucursal -->
<div class="modal fade" id="modalSucursal" tabindex="-1" role="dialog" aria-labelledby="modalSucursalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" id="modalSucursalHeader">
                <h5 class="modal-title" id="modalSucursalLabel">Gestión de Sucursal</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formSucursal" method="post">
                <div class="modal-body">
                    <input type="hidden" id="sucursalAction" name="action" value="create">
                    <input type="hidden" id="idSucursal" name="idsucursal" value="">

                    <div class="form-group">
                        <label for="nombre">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                        <small class="form-text text-muted">Nombre único para la sucursal</small>
                    </div>
                    <div class="form-group">
                        <label for="idempresa">Empresa <span class="text-danger">*</span></label>
                        <select class="form-control select2" id="idempresa" name="idempresa" required>
                            <option value="">Seleccione una empresa</option>
                            <?php foreach ($empresas as $empresa) : ?>
                                <option value="<?= $empresa['idempresa']; ?>">
                                    <?= htmlspecialchars($empresa['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Seleccione la empresa a la que pertenece la sucursal</small>
                    </div>
                    <div class="form-group">
                        <label for="estado">Estado <span class="text-danger">*</span></label>
                        <select class="form-control" id="estado" name="estado" required>
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarSucursal">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>

<script>
    var mensajeErrorDesactivar = "No se puede desactivar la sucursal porque tiene usuarios asociados";
</script>

<!-- Script para la gestión de sucursales -->
<script src="<?= $URL; ?>public/js/modules/sucursales/index-sucursales.js"></script>