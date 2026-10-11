<?php
require_once __DIR__ . '/../../controllers/sesiones/SesionController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

require_once __DIR__ . '/../../services/SesionTokenService.php';

$idusuario = $_SESSION['usuario_id'] ?? '';

$authService = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!($authService->tienePermisoNombre($idusuario, 'sesiones')) && !($authService->esAdministrador($idusuario))) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL);
    exit;
}

// Obtener parámetro de filtro
$mostrarActivas = isset($_GET['activas']) && $_GET['activas'] == '1';

$skip_select2 = true; // Esta vista no usa Select2
$module_scripts = ['sesiones/sesiones-common', 'sesiones/index-sesiones'];

// Incluir el encabezado DESPUÉS de verificar permisos
include_once '../layouts/header.php';

$controller = new SesionController();
$sesiones = $mostrarActivas ? $controller->getSesionesActivas() : $controller->index();
$estadisticas = $controller->getEstadisticas();
$puedeCerrar = $authService->esAdministrador($idusuario);
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Monitoreo de Sesiones</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Sesiones</li>
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
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Sesiones Activas</span>
                        <span class="info-box-number"><?= $estadisticas['sesiones_activas']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-user-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Sesiones Hoy</span>
                        <span class="info-box-number"><?= $estadisticas['sesiones_hoy']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-calendar-week"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Sesiones Semana</span>
                        <span class="info-box-number"><?= $estadisticas['sesiones_semana']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-secondary elevation-1"><i class="fas fa-hourglass-half"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Duración Promedio</span>
                        <span class="info-box-number"><?= round($estadisticas['duracion_promedio']); ?> min</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center">
                            <h2 class="card-title h3 mb-2 mb-sm-0"><?= $mostrarActivas ? 'Sesiones Activas' : 'Historial de Sesiones'; ?></h2>
                            <div class="card-tools">
                                <a href="<?= $URL; ?>views/sesiones/index.php<?= $mostrarActivas ? '' : '?activas=1'; ?>" class="btn btn-<?= $mostrarActivas ? 'info' : 'primary'; ?> btn-sm me-2">
                                    <i class="fas fa-<?= $mostrarActivas ? 'history' : 'user-check'; ?>"></i>
                                    <?= $mostrarActivas ? 'Ver todas las sesiones' : 'Ver solo sesiones activas'; ?>
                                </a>
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer panel">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body" style="display: block;">
                        <?php if (!$puedeCerrar) : ?>
                            <div class="alert alert-default-info" role="note">
                                <i class="fas fa-info-circle"></i> Solo un administrador puede cerrar sesiones.
                            </div>
                        <?php endif; ?>
                        <p class="text-muted small mb-2">
                            Las sesiones cerradas se conservan <?= SesionTokenService::RETENCION_DIAS; ?> días.
                            <?php if (!$mostrarActivas && count($sesiones) >= SesionController::LIMITE_HISTORIAL) : ?>
                                Se muestran las últimas <?= SesionController::LIMITE_HISTORIAL; ?> sesiones.
                            <?php endif; ?>
                        </p>
                        <table id="tablaSesiones" class="table table-bordered table-hover table-striped table-sm">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 5%">Nro</th>
                                    <th style="width: 17%">Usuario</th>
                                    <th style="width: 12%">Inicio</th>
                                    <th style="width: 12%">Fin</th>
                                    <th style="width: 10%">Duración</th>
                                    <th style="width: 15%">Origen</th>
                                    <th class="text-center" style="width: 8%">Estado</th>
                                    <th style="width: 11%">Motivo de cierre</th>
                                    <th class="text-center" style="width: 10%">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $contador = 1;
                                foreach ($sesiones as $sesion) :
                                    $estado = $sesion['estado'];
                                    $clase_estado = $estado ? 'badge-success' : 'badge-secondary';
                                    $texto_estado = $estado ? 'Activa' : 'Cerrada';

                                    $segundos = $controller->segundosDuracion(
                                        $sesion['horaingreso'],
                                        $estado ? null : $sesion['horasalida']
                                    );
                                    $duracion = $controller->formatearDuracion(
                                        $sesion['horaingreso'],
                                        $estado ? null : $sesion['horasalida']
                                    );

                                    $dispositivo = $controller->detectarDispositivo($sesion['navegador']);
                                    $navegador = $controller->detectarNavegador($sesion['navegador']);
                                    $motivo = $controller->formatearMotivo($sesion['motivo_cierre'] ?? null);

                                    $nombreCompleto = e(trim($sesion['nombre'] . ' ' . $sesion['apellidopaterno'] . ' ' . ($sesion['apellidomaterno'] ?? '')));
                                    $ipusuario = $sesion['ipusuario'] ?? '';
                                ?>
                                    <tr>
                                        <td class="text-center"><?= $contador++; ?></td>
                                        <td>
                                            <div class="user-block">
                                                <img class="img-circle img-bordered-sm" loading="lazy" decoding="async" src="<?= $URL; ?>public/uploads/usuarios/<?= $sesion['imagen'] ?: 'user_default.jpg'; ?>" alt="Foto de <?= $nombreCompleto; ?>">
                                                <span class="username">
                                                    <a href="<?= $URL; ?>views/sesiones/usuario.php?id=<?= (int) $sesion['idusuario']; ?>"><?= $nombreCompleto; ?></a>
                                                </span>
                                                <span class="description"><?= e($sesion['cargo']); ?></span>
                                            </div>
                                        </td>
                                        <td data-order="<?= strtotime($sesion['horaingreso']); ?>"><?= date('d/m/Y H:i:s', strtotime($sesion['horaingreso'])); ?></td>
                                        <td data-order="<?= $sesion['horasalida'] ? strtotime($sesion['horasalida']) : PHP_INT_MAX; ?>">
                                            <?= $sesion['horasalida'] ? date('d/m/Y H:i:s', strtotime($sesion['horasalida'])) : '<span class="text-muted">—</span>'; ?>
                                        </td>
                                        <td data-order="<?= $segundos; ?>"><?= $duracion; ?></td>
                                        <td>
                                            <span class="badge badge-info"><?= $dispositivo; ?> - <?= $navegador; ?></span>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($ipusuario !== '' ? $ipusuario : '—'); ?></small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?= $clase_estado; ?>"><?= $texto_estado; ?></span>
                                        </td>
                                        <td>
                                            <?php if ($motivo === '—') : ?>
                                                <span class="text-muted"><?= $motivo; ?></span>
                                            <?php else : ?>
                                                <span class="badge badge-secondary"><?= htmlspecialchars($motivo); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($estado && $puedeCerrar) : ?>
                                                <button type="button" class="btn btn-danger btn-sm btn-cerrar-sesion"
                                                    data-id="<?= $sesion['idsesion']; ?>"
                                                    data-usuario="<?= $nombreCompleto; ?>"
                                                    data-toggle="tooltip" title="Cerrar sesión"
                                                    aria-label="Cerrar sesión de <?= $nombreCompleto; ?>">
                                                    <i class="fas fa-power-off"></i>
                                                </button>
                                            <?php else : ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
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
<!-- /.content -->

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>