<?php
require_once __DIR__ . '/../../controllers/categoria/CategoriaController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo ANTES de incluir el header
if (!($auth->tienePermisoNombre($idusuario, 'categorias')) && !($auth->esAdministrador($idusuario))) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';

    // Redirigir al inicio
    header('Location: ' . $URL);
    exit;
}

// Incluir el encabezado después de verificar permisos
include_once '../layouts/header.php';

$controller = new CategoriaController();
$categorias = $controller->index();
$estadisticas = $controller->getEstadisticas();
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Gestión de Categorías</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Categorías</li>
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
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-tags"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total de Categorías</span>
                        <span class="info-box-number"><?= $estadisticas['total']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Categorías Activas</span>
                        <span class="info-box-number"><?= $estadisticas['activas']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-times-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Categorías Inactivas</span>
                        <span class="info-box-number"><?= $estadisticas['inactivas']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-boxes"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Productos en categorías</span>
                        <span class="info-box-number">
                            <?= array_sum(array_column($estadisticas['productos_por_categoria'], 'total_productos')); ?>
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
                            <h3 class="card-title mb-2 mb-sm-0">Categorías registradas</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-primary btn-sm me-2" id="btnNuevaCategoria">
                                    <i class="fas fa-plus"></i> Nueva Categoría
                                </button>
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body" style="display: block;">
                        <div class="table-responsive">
                            <table id="tablaCategorias" class="table table-bordered table-hover table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 5%">Nro</th>
                                        <th class="text-center" style="width: 30%">Nombre</th>
                                        <th class="text-center" style="width: 15%">Productos</th>
                                        <th class="text-center" style="width: 10%">Estado</th>
                                        <th class="text-center" style="width: 10%">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador = 1;
                                    foreach ($categorias as $categoria) :
                                        $estado_actual = $categoria['estado'];
                                        $clase_estado = $estado_actual == 1 ? 'badge-success' : 'badge-danger';
                                        $texto_estado = $estado_actual == 1 ? 'Activo' : 'Inactivo';
                                        $total_productos = $categoria['total_productos'] ?? 0;
                                        $clase_productos = $total_productos > 0 ? 'badge-primary' : 'badge-secondary';
                                    ?>
                                        <tr>
                                            <td class="text-center"><?= $contador++; ?></td>
                                            <td><?= htmlspecialchars($categoria['nombre']); ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= $clase_productos; ?>">
                                                    <?= $total_productos; ?> producto<?= $total_productos != 1 ? 's' : ''; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?= $clase_estado; ?>"><?= $texto_estado; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-warning btn-sm btn-editar"
                                                        data-id="<?= $categoria['idcategoria']; ?>"
                                                        data-nombre="<?= htmlspecialchars($categoria['nombre']); ?>"
                                                        data-estado="<?= $categoria['estado']; ?>"
                                                        data-toggle="tooltip" title="Editar categoría">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn <?= $estado_actual == 1 ? 'btn-danger' : 'btn-success'; ?> btn-sm cambiar-estado"
                                                        data-id="<?= $categoria['idcategoria']; ?>"
                                                        data-estado-actual="<?= $estado_actual; ?>"
                                                        data-productos="<?= $total_productos; ?>"
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

<!-- Modal para Categoría -->
<div class="modal fade" id="modalCategoria" tabindex="-1" role="dialog" aria-labelledby="modalCategoriaLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" id="modalCategoriaHeader">
                <h5 class="modal-title" id="modalCategoriaLabel">Gestión de Categoría</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formCategoria" method="post">
                <div class="modal-body">
                    <input type="hidden" id="categoriaAction" name="action" value="create">
                    <input type="hidden" id="idCategoria" name="idcategoria" value="">

                    <div class="form-group">
                        <label for="nombre">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                        <small class="form-text text-muted">Nombre único para la categoría</small>
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
                    <button type="submit" class="btn btn-primary" id="btnGuardarCategoria">
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
    var mensajeErrorDesactivar = "No se puede desactivar la categoría porque tiene productos asociados";
</script>

<!-- Script para la gestión de categorías -->
<script src="<?= $URL; ?>public/js/modules/categorias/index-categorias.js"></script>