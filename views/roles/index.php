<?php
require_once __DIR__ . '/../../controllers/rol/RolController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'];
$auth = new AuthorizationService();

// Solo administradores acceden al módulo de Roles: no basta el permiso
// granular 'permisos', un supervisor con ese permiso podría auto-otorgarse
// más si pudiera editar la matriz de roles. Guard antes de incluir header.php.
if (!$auth->esAdministrador($idusuario)) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';

    header('Location: ' . $URL);
    exit;
}

$module_scripts = ['roles/index-roles'];
include_once '../layouts/header.php';

$controller = new RolController();
$roles = $controller->index();
$estadisticas = $controller->getEstadisticas();
$admins_activos = $controller->contarAdminsActivos();

// Whitelist de dashboards disponibles (mismos archivos reales en views/dashboard/).
$dashboards_disponibles = [
    'dashboard.php' => 'Dashboard Administrador',
    'dashboard_supervisor.php' => 'Dashboard Supervisor',
    'dashboard_vendedor.php' => 'Dashboard Vendedor',
    'dashboard_general.php' => 'Dashboard General',
];
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Gestión de Roles</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Roles</li>
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
            <div class="col-12 col-sm-6 col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-user-tag"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total de Roles</span>
                        <span class="info-box-number"><?= $estadisticas['total']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Roles Activos</span>
                        <span class="info-box-number"><?= $estadisticas['activos']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-times-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Roles Inactivos</span>
                        <span class="info-box-number"><?= $estadisticas['inactivos']; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center">
                            <h3 class="card-title mb-2 mb-sm-0">Roles registrados</h3>
                            <div class="card-tools">
                                <a href="<?= $URL; ?>views/roles/permisos.php" class="btn btn-info btn-sm me-2">
                                    <i class="fas fa-key"></i> Matriz de Permisos
                                </a>
                                <button type="button" class="btn btn-primary btn-sm me-2" id="btnNuevoRol">
                                    <i class="fas fa-plus"></i> Nuevo Rol
                                </button>
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer panel">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body" style="display: block;">
                        <table id="tablaRoles" class="table table-bordered table-hover table-striped table-sm">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 5%">Nro</th>
                                    <th class="text-center" style="width: 20%">Nombre</th>
                                    <th style="width: 25%">Descripción</th>
                                    <th class="text-center" style="width: 10%">Usuarios</th>
                                    <th class="text-center" style="width: 10%">Tipo</th>
                                    <th class="text-center" style="width: 10%">Estado</th>
                                    <th class="text-center" style="width: 20%">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $contador = 1;
                                foreach ($roles as $rol) :
                                    $estado_actual = $rol['estado'];
                                    $clase_estado = $estado_actual == 1 ? 'badge-success' : 'badge-danger';
                                    $texto_estado = $estado_actual == 1 ? 'Activo' : 'Inactivo';
                                    $total_usuarios = $rol['total_usuarios'] ?? 0;
                                    $clase_usuarios = $total_usuarios > 0 ? 'badge-primary' : 'badge-secondary';
                                    $es_sistema = $rol['es_sistema'] == 1;
                                    $es_admin = $rol['es_admin'] == 1;
                                    // El último rol admin activo no puede desactivarse (lo rechaza
                                    // el servidor); no se ofrece el botón para no invitar al error.
                                    $puede_cambiar_estado = !($es_admin && $estado_actual == 1 && $admins_activos <= 1);
                                ?>
                                    <tr>
                                        <td class="text-center"><?= $contador++; ?></td>
                                        <td><?= $rol['nombre']; ?></td>
                                        <td><?= $rol['descripcion'] ?? ''; ?></td>
                                        <td class="text-center">
                                            <span class="badge <?= $clase_usuarios; ?>">
                                                <?= $total_usuarios; ?> usuario<?= $total_usuarios != 1 ? 's' : ''; ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($es_admin) : ?>
                                                <span class="badge badge-danger" data-toggle="tooltip" title="Este rol tiene acceso total al sistema">Admin</span>
                                            <?php elseif ($es_sistema) : ?>
                                                <span class="badge badge-info" data-toggle="tooltip" title="Rol base del sistema, no eliminable">Sistema</span>
                                            <?php else : ?>
                                                <span class="badge badge-secondary">Personalizado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?= $clase_estado; ?>"><?= $texto_estado; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-warning btn-sm btn-editar"
                                                    data-id="<?= $rol['idrol']; ?>"
                                                    data-nombre="<?= $rol['nombre']; ?>"
                                                    data-descripcion="<?= $rol['descripcion'] ?? ''; ?>"
                                                    data-dashboard="<?= $rol['dashboard']; ?>"
                                                    data-es-sistema="<?= $rol['es_sistema']; ?>"
                                                    data-toggle="tooltip" title="Editar rol"
                                                    aria-label="Editar rol <?= $rol['nombre']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($puede_cambiar_estado) : ?>
                                                    <button type="button" class="btn <?= $estado_actual == 1 ? 'btn-danger' : 'btn-success'; ?> btn-sm cambiar-estado"
                                                        data-id="<?= $rol['idrol']; ?>"
                                                        data-estado-actual="<?= $estado_actual; ?>"
                                                        data-toggle="tooltip"
                                                        title="<?= $estado_actual == 1 ? 'Desactivar' : 'Activar'; ?>"
                                                        aria-label="<?= $estado_actual == 1 ? 'Desactivar' : 'Activar'; ?> rol <?= $rol['nombre']; ?>">
                                                        <i class="fas <?= $estado_actual == 1 ? 'fa-times' : 'fa-check'; ?>"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if (!$es_sistema) : ?>
                                                    <button type="button" class="btn btn-outline-danger btn-sm btn-eliminar"
                                                        data-id="<?= $rol['idrol']; ?>"
                                                        data-nombre="<?= $rol['nombre']; ?>"
                                                        data-usuarios="<?= $total_usuarios; ?>"
                                                        data-toggle="tooltip" title="Eliminar rol"
                                                        aria-label="Eliminar rol <?= $rol['nombre']; ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
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
</section>

<!-- Modal para Rol -->
<div class="modal fade" id="modalRol" tabindex="-1" role="dialog" aria-labelledby="modalRolLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" id="modalRolHeader">
                <h5 class="modal-title" id="modalRolLabel">Gestión de Rol</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formRol" method="post">
                <div class="modal-body">
                    <input type="hidden" id="rolAction" name="action" value="create">
                    <input type="hidden" id="idRol" name="idrol" value="">

                    <div class="form-group">
                        <label for="nombre">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                        <small class="form-text text-muted" id="nombreAyuda">Nombre único para el rol</small>
                    </div>
                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="2" maxlength="255"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="dashboard">Dashboard <span class="text-danger">*</span></label>
                        <select class="form-control select2" id="dashboard" name="dashboard" required>
                            <?php foreach ($dashboards_disponibles as $archivo => $etiqueta) : ?>
                                <option value="<?= htmlspecialchars($archivo); ?>"><?= htmlspecialchars($etiqueta); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Pantalla que verán los usuarios con este rol al iniciar sesión</small>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarRol">
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