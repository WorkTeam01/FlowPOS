<?php
require_once __DIR__ . '/../../controllers/permisos/PermisoController.php';
require_once __DIR__ . '/../../controllers/rol/RolController.php';
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

$permisoController = new PermisoController();
$permisos = $permisoController->index();

// Matriz inversa: por cada permiso, qué roles activos lo tienen. El catálogo
// permiso es un contrato con el código PHP (~40 llamadas tienePermisoNombre()
// hardcodeadas), no dato editable por UI — esta vista es solo de auditoría;
// editar asignaciones se hace en Roles > Matriz de Permisos.
$rolController = new RolController();
$matriz = $rolController->getMatriz();

$roles_por_permiso = [];
foreach ($matriz['asignaciones'] as $idrol => $idpermisos) {
    foreach ($idpermisos as $idpermiso) {
        $roles_por_permiso[$idpermiso][] = $idrol;
    }
}

$roles_por_id = [];
foreach ($matriz['roles'] as $rol) {
    $roles_por_id[$rol['idrol']] = $rol['nombre'];
}
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Catálogo de Permisos</h1>
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
        <?php if ($authService->esAdministrador($idusuario)) : ?>
            <div class="alert alert-info">
                Este listado es de solo lectura. Para asignar o quitar permisos de un rol, usar
                <a href="<?= $URL; ?>views/roles/permisos.php" class="alert-link">Roles &gt; Matriz de Permisos</a>.
            </div>
        <?php endif; ?>
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Permisos del sistema y roles que los tienen asignados</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <table id="tablaPermisos" class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 10px">#</th>
                                    <th>Nombre</th>
                                    <th>Estado</th>
                                    <th>Roles con este permiso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $contador = 1;
                                foreach ($permisos as $permiso) :
                                    $estado = $permiso['estado'];
                                    $clase_estado = $estado ? 'badge-success' : 'badge-danger';
                                    $texto_estado = $estado ? 'Activo' : 'Inactivo';
                                    $ids_roles = $roles_por_permiso[$permiso['idpermiso']] ?? [];
                                ?>
                                    <tr>
                                        <td><?= $contador++; ?></td>
                                        <td><?= htmlspecialchars($permiso['nombre']); ?></td>
                                        <td class="text-center">
                                            <span class="badge <?= $clase_estado; ?>"><?= $texto_estado; ?></span>
                                        </td>
                                        <td>
                                            <?php if (empty($ids_roles)) : ?>
                                                <span class="text-muted">Ningún rol</span>
                                            <?php else : ?>
                                                <?php foreach ($ids_roles as $idrol) : ?>
                                                    <span class="badge badge-secondary"><?= htmlspecialchars($roles_por_id[$idrol] ?? $idrol); ?></span>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
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
