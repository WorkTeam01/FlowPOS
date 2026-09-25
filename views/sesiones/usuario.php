<?php
require_once __DIR__ . '/../../controllers/sesiones/SesionController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../../models/Usuario.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'] ?? '';

$authService = new AuthorizationService();

// Acceso al módulo: permiso granular 'sesiones' o administrador (mismo criterio del índice)
if (!($authService->tienePermisoNombre($idusuario, 'sesiones')) && !($authService->esAdministrador($idusuario))) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL);
    exit;
}

// Validar el usuario solicitado ANTES de incluir header.php
$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
if ($id === false || $id === 0) {
    $_SESSION['mensaje'] = 'Identificador de usuario inválido.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/sesiones/index.php');
    exit;
}

$usuarioModel = new Usuario();
$usuario = $usuarioModel->getById($id);
if (!$usuario) {
    $_SESSION['mensaje'] = 'El usuario solicitado no existe.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/sesiones/index.php');
    exit;
}

$mostrarActivas = isset($_GET['activas']) && $_GET['activas'] == '1';
$puedeCerrar = $authService->esAdministrador($idusuario);

$skip_select2 = true; // Esta vista no usa Select2
$module_scripts = ['sesiones/usuario-sesiones'];

include_once '../layouts/header.php';

$controller = new SesionController();
$todasLasSesiones = $controller->getSesionesUsuario($id);
$sesionesActivas = array_values(array_filter($todasLasSesiones, function ($sesion) {
    return $sesion['estado'] == 1;
}));
$sesiones = $mostrarActivas ? $sesionesActivas : $todasLasSesiones;
$totalSesiones = count($todasLasSesiones);
$totalActivas = count($sesionesActivas);
$totalCerradas = $totalSesiones - $totalActivas;

$nombreCompleto = trim($usuario['nombre'] . ' ' . $usuario['apellidopaterno'] . ' ' . ($usuario['apellidomaterno'] ?? ''));
$correo = $usuario['correo'] ?? '';
$cargo = $usuario['cargo'] ?? '';
$imagen = $usuario['imagen'] ?? '';
$estadoUsuario = $usuario['estado'] ?? 1;
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Sesiones de Usuario</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/sesiones/index.php"><i class="fas fa-user"></i> Sesiones</a></li>
                    <li class="breadcrumb-item active">Detalle de usuario</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- Datos del usuario -->
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center">
                            <h2 class="card-title h3 mb-2 mb-sm-0"><?= htmlspecialchars($nombreCompleto); ?></h2>
                            <div class="card-tools">
                                <a href="<?= $URL; ?>views/sesiones/index.php" class="btn btn-secondary btn-sm me-2">
                                    <i class="fas fa-arrow-left"></i> Volver
                                </a>
                                <?php if ($puedeCerrar && $totalActivas > 0) : ?>
                                    <button type="button" class="btn btn-danger btn-sm me-2 btn-cerrar-todas"
                                        data-id="<?= (int) $id; ?>"
                                        data-nombre="<?= htmlspecialchars($nombreCompleto); ?>">
                                        <i class="fas fa-power-off"></i> Cerrar todas las sesiones (<?= $totalActivas; ?>)
                                    </button>
                                <?php endif; ?>
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer panel">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body" style="display: block;">
                        <div class="user-block">
                            <img class="img-circle img-bordered-sm" src="<?= $URL; ?>public/uploads/usuarios/<?= $imagen ?: 'user_default.jpg'; ?>" alt="Foto de <?= htmlspecialchars($nombreCompleto); ?>">
                            <span class="username">
                                <?= htmlspecialchars($correo !== '' ? $correo : 'Sin correo registrado'); ?>
                            </span>
                            <span class="description">
                                <?= htmlspecialchars($cargo); ?>
                                <span class="badge badge-<?= $estadoUsuario == 1 ? 'success' : 'danger'; ?>">
                                    <?= $estadoUsuario == 1 ? 'Usuario activo' : 'Usuario inactivo'; ?>
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info boxes -->
        <div class="row">
            <div class="col-12 col-sm-6 col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-history"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Sesiones Totales</span>
                        <span class="info-box-number"><?= $totalSesiones; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-user-check"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Sesiones Activas</span>
                        <span class="info-box-number"><?= $totalActivas; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <div class="info-box">
                    <span class="info-box-icon bg-secondary elevation-1"><i class="fas fa-user-lock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Sesiones Cerradas</span>
                        <span class="info-box-number"><?= $totalCerradas; ?></span>
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
                                <a href="<?= $URL; ?>views/sesiones/usuario.php?id=<?= (int) $id; ?><?= $mostrarActivas ? '' : '&activas=1'; ?>" class="btn btn-<?= $mostrarActivas ? 'info' : 'primary'; ?> btn-sm me-2">
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
                        <table id="tablaSesionesUsuario" class="table table-bordered table-hover table-striped table-sm">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 5%">Nro</th>
                                    <th style="width: 15%">Inicio</th>
                                    <th style="width: 15%">Fin</th>
                                    <th style="width: 12%">Duración</th>
                                    <th style="width: 18%">Origen</th>
                                    <th class="text-center" style="width: 10%">Estado</th>
                                    <th style="width: 13%">Motivo de cierre</th>
                                    <th class="text-center" style="width: 12%">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $contador = 1;
                                foreach ($sesiones as $sesion) :
                                    $estado = $sesion['estado'];
                                    $clase_estado = $estado ? 'badge-success' : 'badge-secondary';
                                    $texto_estado = $estado ? 'Activa' : 'Cerrada';

                                    $duracion = $controller->formatearDuracion(
                                        $sesion['horaingreso'],
                                        $estado ? null : $sesion['horasalida']
                                    );

                                    $dispositivo = $controller->detectarDispositivo($sesion['navegador']);
                                    $navegador = $controller->detectarNavegador($sesion['navegador']);
                                    $motivo = $controller->formatearMotivo($sesion['motivo_cierre'] ?? null);
                                    $ipusuario = $sesion['ipusuario'] ?? '';
                                ?>
                                    <tr>
                                        <td class="text-center"><?= $contador++; ?></td>
                                        <td><?= date('d/m/Y H:i:s', strtotime($sesion['horaingreso'])); ?></td>
                                        <td>
                                            <?= $sesion['horasalida'] ? date('d/m/Y H:i:s', strtotime($sesion['horasalida'])) : '<span class="text-success">Sesión en curso</span>'; ?>
                                        </td>
                                        <td><?= $duracion; ?></td>
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
                                            <?php if ($estado) : ?>
                                                <div class="btn-group">
                                                    <?php if ($puedeCerrar) : ?>
                                                        <button type="button" class="btn btn-danger btn-sm btn-cerrar-sesion"
                                                            data-id="<?= $sesion['idsesion']; ?>"
                                                            data-usuario="<?= htmlspecialchars($nombreCompleto); ?>"
                                                            data-toggle="tooltip" title="Cerrar esta sesión"
                                                            aria-label="Cerrar sesión de <?= htmlspecialchars($nombreCompleto); ?>">
                                                            <i class="fas fa-power-off"></i>
                                                        </button>
                                                    <?php else : ?>
                                                        <?php /* El tooltip va en un <span> contenedor: un <button disabled> no
                                                             dispara eventos de puntero, así que el mensaje nunca se vería. */ ?>
                                                        <span data-toggle="tooltip" title="Solo un administrador puede cerrar sesiones">
                                                            <button type="button" class="btn btn-outline-secondary btn-sm" disabled
                                                                tabindex="-1"
                                                                aria-label="Solo un administrador puede cerrar sesiones">
                                                                <i class="fas fa-lock"></i>
                                                            </button>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else : ?>
                                                <div class="btn-group">
                                                    <span data-toggle="tooltip" title="Esta sesión ya fue cerrada">
                                                        <button type="button" class="btn btn-secondary btn-sm" disabled
                                                            tabindex="-1" aria-label="Sesión ya cerrada">
                                                            <i class="fas fa-power-off"></i>
                                                        </button>
                                                    </span>
                                                </div>
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