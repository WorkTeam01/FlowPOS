<?php
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
if (!($auth->tienePermisoNombre($idusuario, 'empresas')) && !($auth->esAdministrador($idusuario))) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';

    // Redirigir al inicio
    header('Location: ' . $URL);
    exit;
}

// Incluir el encabezado después de verificar permisos
include_once '../layouts/header.php';

$controller = new EmpresaController();
$empresas = $controller->index();
$estadisticas = $controller->getEstadisticas();
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Gestión de Empresas</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Empresas</li>
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
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-building"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total de Empresas</span>
                        <span class="info-box-number"><?= $estadisticas['total']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Empresas Activas</span>
                        <span class="info-box-number"><?= $estadisticas['activas']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-times-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Empresas Inactivas</span>
                        <span class="info-box-number"><?= $estadisticas['inactivas']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-store"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Sucursales en empresas</span>
                        <span class="info-box-number">
                            <?= array_sum(array_column($estadisticas['sucursales_por_empresa'], 'total_sucursales')); ?>
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
                            <h3 class="card-title mb-2 mb-sm-0">Empresas registradas</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-primary btn-sm me-2" id="btnNuevaEmpresa">
                                    <i class="fas fa-plus"></i> Nueva Empresa
                                </button>
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body" style="display: block;">
                        <div class="table-responsive">
                            <table id="tablaEmpresas" class="table table-bordered table-hover table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 5%">Nro</th>
                                        <th class="text-center" style="width: 20%">Nombre</th>
                                        <th class="text-center" style="width: 15%">NIT</th>
                                        <th class="text-center" style="width: 15%">Sucursales</th>
                                        <th class="text-center" style="width: 10%">Estado</th>
                                        <th class="text-center" style="width: 10%">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador = 1;
                                    foreach ($empresas as $empresa) :
                                        $estado_actual = $empresa['estado'];
                                        $clase_estado = $estado_actual == 1 ? 'badge-success' : 'badge-danger';
                                        $texto_estado = $estado_actual == 1 ? 'Activo' : 'Inactivo';
                                        $total_sucursales = $empresa['total_sucursales'] ?? 0;
                                        $clase_sucursales = $total_sucursales > 0 ? 'badge-primary' : 'badge-secondary';
                                    ?>
                                        <tr>
                                            <td class="text-center"><?= $contador++; ?></td>
                                            <td><?= htmlspecialchars($empresa['nombre']); ?></td>
                                            <td class="text-center"><?= htmlspecialchars($empresa['nit']); ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= $clase_sucursales; ?>">
                                                    <?= $total_sucursales; ?> sucursal<?= $total_sucursales != 1 ? 'es' : ''; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?= $clase_estado; ?>"><?= $texto_estado; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-warning btn-sm btn-editar"
                                                        data-id="<?= $empresa['idempresa']; ?>"
                                                        data-nombre="<?= htmlspecialchars($empresa['nombre']); ?>"
                                                        data-nit="<?= htmlspecialchars($empresa['nit']); ?>"
                                                        data-direccion="<?= htmlspecialchars($empresa['direccion']); ?>"
                                                        data-telefono="<?= htmlspecialchars($empresa['telefono']); ?>"
                                                        data-email="<?= htmlspecialchars($empresa['email']); ?>"
                                                        data-imagen="<?= htmlspecialchars($empresa['imagen']); ?>"
                                                        data-estado="<?= $empresa['estado']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn <?= $estado_actual == 1 ? 'btn-danger' : 'btn-success'; ?> btn-sm cambiar-estado"
                                                        data-id="<?= $empresa['idempresa']; ?>"
                                                        data-estado-actual="<?= $estado_actual; ?>"
                                                        data-sucursales="<?= $total_sucursales; ?>"
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

<!-- Modal para Empresa -->
<div class="modal fade" id="modalEmpresa" tabindex="-1" role="dialog" aria-labelledby="modalEmpresaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" id="modalEmpresaHeader">
                <h5 class="modal-title" id="modalEmpresaLabel">Gestión de Empresa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formEmpresa" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="empresaAction" name="action" value="create">
                    <input type="hidden" id="idEmpresa" name="idempresa" value="">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nombre">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                            </div>
                            <div class="form-group">
                                <label for="nit">NIT <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nit" name="nit" required>
                            </div>
                            <div class="form-group">
                                <label for="direccion">Dirección <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="direccion" name="direccion" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="telefono">Teléfono <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="telefono" name="telefono" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="imagen">Logo</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="imagen" name="imagen" accept="image/*">
                                    <label class="custom-file-label" for="imagen">Seleccionar archivo</label>
                                </div>
                                <small class="form-text text-muted">Tamaño máximo: 2MB</small>
                                <div id="previewImagen" class="mt-2 text-center"></div>
                            </div>
                        </div>
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
                    <button type="submit" class="btn btn-primary" id="btnGuardarEmpresa">
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
    var mensajeErrorDesactivar = "No se puede desactivar la empresa porque tiene sucursales asociadas";
</script>

<!-- Script para la gestión de empresas -->
<script src="<?= $URL; ?>public/js/modules/empresas/index-empresas.js"></script>