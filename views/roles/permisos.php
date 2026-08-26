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

$skip_datatables = true; // Matriz de checkboxes, no es una tabla de listado
$skip_select2 = true;    // Sin selects en esta vista

$module_scripts = ['roles/permisos-rol'];
include_once '../layouts/header.php';

$controller = new RolController();
$matriz = $controller->getMatriz();
$rol_actual = (new Rol())->getIdRolDeUsuario($idusuario);

$grupos = AuthorizationService::agruparPermisosPorCategoria($matriz['permisos']);
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Matriz de Permisos por Rol</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/roles"><i class="fas fa-user-tag"></i> Roles</a></li>
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
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Permisos asignados por rol</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body" style="display: block;">
                        <p class="text-muted">
                            Marcar o desmarcar un permiso para un rol afecta a <strong>todos</strong> los usuarios con ese rol.
                            No es posible editar los permisos de su propio rol (columna deshabilitada).
                        </p>
                        <form id="formMatrizPermisos">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm table-hover" id="tablaMatrizPermisos">
                                    <thead>
                                        <tr>
                                            <th style="min-width: 160px;">Permiso</th>
                                            <?php foreach ($matriz['roles'] as $rol) : ?>
                                                <th class="text-center" style="min-width: 120px;">
                                                    <?= htmlspecialchars($rol['nombre']); ?>
                                                    <?php if ((int) $rol['idrol'] === $rol_actual) : ?>
                                                        <br><small class="text-muted">(su rol)</small>
                                                    <?php endif; ?>
                                                </th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($grupos as $categoria => $permisos_categoria) : ?>
                                            <tr class="table-secondary">
                                                <td colspan="<?= count($matriz['roles']) + 1; ?>">
                                                    <strong><?= htmlspecialchars($categoria); ?></strong>
                                                </td>
                                            </tr>
                                            <?php foreach ($permisos_categoria as $permiso) : ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($permiso['nombre']); ?></td>
                                                    <?php foreach ($matriz['roles'] as $rol) :
                                                        $idrol = (int) $rol['idrol'];
                                                        $es_propio = $idrol === $rol_actual;
                                                        $marcado = in_array((int) $permiso['idpermiso'], $matriz['asignaciones'][$idrol] ?? [], true);
                                                    ?>
                                                        <td class="text-center">
                                                            <div class="icheck-primary d-inline-block">
                                                                <input type="checkbox"
                                                                    class="chk-permiso-rol"
                                                                    data-idrol="<?= $idrol; ?>"
                                                                    value="<?= (int) $permiso['idpermiso']; ?>"
                                                                    <?= $marcado ? 'checked' : ''; ?>
                                                                    <?= $es_propio ? 'disabled' : ''; ?>
                                                                    id="chk_<?= $idrol; ?>_<?= (int) $permiso['idpermiso']; ?>">
                                                                <label for="chk_<?= $idrol; ?>_<?= (int) $permiso['idpermiso']; ?>"></label>
                                                            </div>
                                                        </td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td></td>
                                            <?php foreach ($matriz['roles'] as $rol) :
                                                $idrol = (int) $rol['idrol'];
                                                $es_propio = $idrol === $rol_actual;
                                            ?>
                                                <td class="text-center">
                                                    <button type="button"
                                                        class="btn btn-primary btn-sm btn-guardar-rol"
                                                        data-idrol="<?= $idrol; ?>"
                                                        <?= $es_propio ? 'disabled' : ''; ?>>
                                                        <i class="fas fa-save"></i> Guardar
                                                    </button>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>