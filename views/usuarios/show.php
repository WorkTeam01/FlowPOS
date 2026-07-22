<?php
require_once __DIR__ . '/../../controllers/usuarios/UsuarioController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!($authService->tienePermisoNombre($idusuario, 'usuarios')) && !($authService->esAdministrador($idusuario))) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Incluir el encabezado
$skip_datatables = true; // Esta vista no usa tabla; evita cargar DataTables/pdfmake/vfs_fonts (~2.8MB)
$skip_select2 = true; // Esta vista no usa Select2
include_once '../layouts/header.php';

// Verificar si se proporcionó un ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    $_SESSION['mensaje'] = 'ID de usuario no válido';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Instanciar el controlador y obtener los datos del usuario
$controller = new UsuarioController();
$usuario = $controller->editar($id);
// Verificar si el usuario existe
if (!$usuario) {
    $_SESSION['mensaje'] = 'Usuario no encontrado';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

$permisosAsignados = $authService->obtenerPermisosAsignados($usuario['idusuario']);
$todosLosPermisos = $authService->obtenerTodosLosPermisos();
$esAdmin = $authService->esAdministrador($usuario['idusuario']);
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Detalle de Usuario</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/usuarios"><i class="fas fa-users"></i> Usuarios</a></li>
                    <li class="breadcrumb-item active">Detalle de Usuario</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <!-- Columna de perfil -->
            <div class="col-md-4">
              <div class="sidebar-sticky">
                <!-- Tarjeta de perfil con imagen -->
                <div class="card card-info card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center position-relative mb-4">
                            <?php if (!empty($usuario['imagen'])): ?>
                                <img class="profile-user-img img-fluid img-circle"
                                    src="<?= $URL; ?>public/uploads/usuarios/<?= htmlspecialchars($usuario['imagen']); ?>"
                                    alt="Imagen de perfil">
                            <?php else: ?>
                                <img class="profile-user-img img-fluid img-circle"
                                    src="<?= $URL; ?>public/uploads/usuarios/user_default.jpg"
                                    alt="Imagen de perfil">
                            <?php endif; ?>

                            <!-- Indicador de estado sobre la imagen -->
                            <span id="avatarEstadoBadge" class="position-absolute badge <?= $usuario['estado'] == 1 ? 'badge-success' : 'badge-danger'; ?>"
                                style="top: 0; right: 50%; transform: translateX(60px);">
                                <i class="fas <?= $usuario['estado'] == 1 ? 'fa-check' : 'fa-times'; ?>"></i>
                            </span>
                        </div>

                        <h3 class="profile-username text-center">
                            <?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidopaterno']); ?>
                        </h3>

                        <p class="text-muted text-center">
                            <?= htmlspecialchars($usuario['cargo'] ?? 'Sin cargo asignado'); ?>
                        </p>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b><i class="fas fa-id-card mr-2"></i><?= htmlspecialchars($usuario['tipodocumento']); ?></b>
                                <span class="float-right"><?= htmlspecialchars($usuario['numdocumento']); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-envelope mr-2"></i>Correo</b>
                                <span class="float-right text-truncate" style="max-width: 170px;" title="<?= htmlspecialchars($usuario['correo'] ?? 'No registrado'); ?>">
                                    <?= htmlspecialchars($usuario['correo'] ?? 'No registrado'); ?>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-phone mr-2"></i>Teléfono</b>
                                <span class="float-right"><?= htmlspecialchars($usuario['telefono'] ?? 'No registrado'); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-calendar-alt mr-2"></i>Fecha Registro</b>
                                <span class="float-right">
                                    <?= isset($usuario['fechacreacion']) ? date('d/m/Y', strtotime($usuario['fechacreacion'])) : 'No disponible'; ?>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Tarjeta de acciones -->
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-cogs mr-2"></i>Acciones</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="<?= $URL; ?>views/usuarios/update.php?id=<?= $usuario['idusuario']; ?>" class="list-group-item list-group-item-action">
                                <i class="fas fa-edit mr-2 text-warning"></i> Editar usuario
                            </a>
                            <a href="#" id="btnCambiarEstado" class="list-group-item list-group-item-action"
                                data-id="<?= $usuario['idusuario']; ?>"
                                data-estado="<?= $usuario['estado']; ?>"
                                data-nombre="<?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidopaterno']); ?>">
                                <i class="fas <?= $usuario['estado'] == 1 ? 'fa-user-slash text-danger' : 'fa-user-check text-success'; ?> mr-2"></i>
                                <span id="btnCambiarEstadoTexto"><?= $usuario['estado'] == 1 ? 'Desactivar usuario' : 'Activar usuario'; ?></span>
                            </a>
                            <a href="<?= $URL; ?>views/usuarios/index.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-list mr-2 text-primary"></i> Volver a la lista de usuarios
                            </a>
                        </div>
                    </div>
                </div>
              </div>
            </div>
            <!-- /.col-md-4 -->

            <!-- Columna de información detallada -->
            <div class="col-md-8">
                <div class="card card-info card-outline card-outline-tabs">
                    <div class="card-header p-0 border-bottom-0">
                        <ul class="nav nav-tabs" id="detail-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="tab-personal" data-toggle="pill" href="#personal" role="tab" aria-controls="personal" aria-selected="true">
                                    <i class="fas fa-user mr-1"></i> Personal
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="tab-contacto" data-toggle="pill" href="#contacto" role="tab" aria-controls="contacto" aria-selected="false">
                                    <i class="fas fa-address-book mr-1"></i> Contacto
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="tab-permisos" data-toggle="pill" href="#permisos" role="tab" aria-controls="permisos" aria-selected="false">
                                    <i class="fas fa-key mr-1"></i> Permisos
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="tab-sistema" data-toggle="pill" href="#sistema" role="tab" aria-controls="sistema" aria-selected="false">
                                    <i class="fas fa-cogs mr-1"></i> Sistema
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="detail-tabs-content">
                            <!-- Tab Información Personal -->
                            <div class="tab-pane fade show active" id="personal" role="tabpanel" aria-labelledby="tab-personal">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="info-box bg-light">
                                            <div class="info-box-content">
                                                <h5 class="info-box-text text-center text-muted">Nombre Completo</h5>
                                                <h6 class="info-box-number text-center text-muted mb-0">
                                                    <?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidopaterno'] . ' ' . $usuario['apellidomaterno']); ?>
                                                </h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover table-striped">
                                        <tbody>
                                            <tr>
                                                <th style="width: 30%"><i class="fas fa-id-badge mr-2"></i>Tipo Documento</th>
                                                <td><?= htmlspecialchars($usuario['tipodocumento']); ?></td>
                                            </tr>
                                            <tr>
                                                <th><i class="fas fa-hashtag mr-2"></i>Número Documento</th>
                                                <td><?= htmlspecialchars($usuario['numdocumento']); ?></td>
                                            </tr>
                                            <tr>
                                                <th><i class="fas fa-map-marker-alt mr-2"></i>Dirección</th>
                                                <td>
                                                    <?php if (!empty($usuario['direccion'])): ?>
                                                        <?= htmlspecialchars($usuario['direccion']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">No registrada</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Tab Información de Contacto -->
                            <div class="tab-pane fade" id="contacto" role="tabpanel" aria-labelledby="tab-contacto">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-info"><i class="fas fa-envelope"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Correo Electrónico</span>
                                                <span class="info-box-number">
                                                    <?php if (!empty($usuario['correo'])): ?>
                                                        <a href="mailto:<?= htmlspecialchars($usuario['correo']); ?>" class="text-info">
                                                            <?= htmlspecialchars($usuario['correo']); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">No registrado</span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-success"><i class="fas fa-phone"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Teléfono</span>
                                                <span class="info-box-number">
                                                    <?php if (!empty($usuario['telefono'])): ?>
                                                        <a href="tel:<?= htmlspecialchars($usuario['telefono']); ?>" class="text-success">
                                                            <?= htmlspecialchars($usuario['telefono']); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">No registrado</span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab Permisos -->
                            <div class="tab-pane fade" id="permisos" role="tabpanel" aria-labelledby="tab-permisos">
                                <div class="row">
                                    <?php if (empty($permisosAsignados) && !$esAdmin): ?>
                                        <div class="col-12">
                                            <div class="alert alert-warning mb-0">
                                                <i class="fas fa-exclamation-triangle"></i> Este usuario no tiene permisos específicos asignados.
                                            </div>
                                        </div>
                                    <?php elseif ($esAdmin): ?>
                                        <div class="col-12">
                                            <div class="alert alert-info mb-0">
                                                <i class="fas fa-crown"></i> Este usuario es Administrador y tiene acceso a todas las funcionalidades del sistema.
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($todosLosPermisos as $permiso): ?>
                                            <div class="col-md-6 mb-2">
                                                <div class="d-flex align-items-center">
                                                    <?php if (in_array($permiso['idpermiso'], $permisosAsignados)): ?>
                                                        <span class="badge badge-success mr-2"><i class="fas fa-check"></i></span>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary mr-2"><i class="fas fa-times"></i></span>
                                                    <?php endif; ?>
                                                    <span><?= htmlspecialchars($permiso['nombre']) ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Tab Información del Sistema -->
                            <div class="tab-pane fade" id="sistema" role="tabpanel" aria-labelledby="tab-sistema">
                                <div class="timeline">
                                    <!-- Fecha de Creación -->
                                    <div>
                                        <i class="fas fa-user-plus bg-primary"></i>
                                        <div class="timeline-item">
                                            <span class="time"><i class="fas fa-clock"></i> <?= isset($usuario['fechacreacion']) ? date('H:i', strtotime($usuario['fechacreacion'])) : ''; ?></span>
                                            <h3 class="timeline-header"><strong>Registro en el Sistema</strong></h3>
                                            <div class="timeline-body">
                                                Este usuario fue registrado el <?= isset($usuario['fechacreacion']) ? date('d/m/Y', strtotime($usuario['fechacreacion'])) : 'fecha no disponible'; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Última Actualización -->
                                    <?php if (!empty($usuario['fechaactualizacion'])): ?>
                                        <div>
                                            <i class="fas fa-edit bg-warning"></i>
                                            <div class="timeline-item">
                                                <span class="time"><i class="fas fa-clock"></i> <?= date('H:i', strtotime($usuario['fechaactualizacion'])); ?></span>
                                                <h3 class="timeline-header"><strong>Última Actualización</strong></h3>
                                                <div class="timeline-body">
                                                    La información fue actualizada por última vez el <?= date('d/m/Y', strtotime($usuario['fechaactualizacion'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Estado del Usuario -->
                                    <div>
                                        <i id="timelineEstadoIcono" class="fas <?= $usuario['estado'] == 1 ? 'fa-check-circle bg-success' : 'fa-times-circle bg-danger'; ?>"></i>
                                        <div class="timeline-item">
                                            <h3 class="timeline-header"><strong>Estado de la Cuenta</strong></h3>
                                            <div class="timeline-body">
                                                El usuario se encuentra actualmente
                                                <span id="timelineEstadoBadge" class="badge <?= $usuario['estado'] == 1 ? 'badge-success' : 'badge-danger'; ?>">
                                                    <?= $usuario['estado'] == 1 ? 'Activo' : 'Inactivo'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Cargo del Usuario -->
                                    <div>
                                        <i class="fas fa-user-tag bg-info"></i>
                                        <div class="timeline-item">
                                            <h3 class="timeline-header"><strong>Cargo en el Sistema</strong></h3>
                                            <div class="timeline-body">
                                                <span class="badge badge-info"><?= htmlspecialchars($usuario['cargo'] ?? 'Sin cargo'); ?></span>
                                                <p class="mt-2">
                                                    <?php if ($esAdmin): ?>
                                                        Usuario con permisos completos para administrar el sistema.
                                                    <?php else: ?>
                                                        Usuario con permisos personalizados según lo asignado en la pestaña Permisos.
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <i class="fas fa-clock bg-gray"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.col-md-8 -->
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container-fluid -->
</section>
<!-- /.content -->

<script>
    $(document).ready(function() {
        // Recordar la pestaña activa entre visitas
        $('a[data-toggle="pill"]').on('shown.bs.tab', function(e) {
            localStorage.setItem('lastUsuarioDetailTab', $(e.target).attr('id'));
        });
        var lastTab = localStorage.getItem('lastUsuarioDetailTab');
        if (lastTab && document.getElementById(lastTab)) {
            $('#' + lastTab).tab('show');
        }

        // Cambiar el estado del usuario
        $('#btnCambiarEstado').on('click', function(e) {
            e.preventDefault();

            const boton = $(this);
            const usuarioId = boton.data('id');
            const estadoActual = parseInt(boton.attr('data-estado'), 10);
            const nombreUsuario = boton.data('nombre');

            const tituloAlerta = estadoActual == 1 ?
                `¿Desactivar a ${nombreUsuario}?` :
                `¿Activar a ${nombreUsuario}?`;
            const textoAlerta = estadoActual == 1 ?
                'El usuario no podrá acceder al sistema hasta que sea activado nuevamente.' :
                'El usuario podrá acceder nuevamente al sistema.';
            const confirmButtonText = estadoActual == 1 ? 'Sí, desactivar' : 'Sí, activar';

            Swal.fire({
                title: tituloAlerta,
                text: textoAlerta,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: estadoActual == 1 ? '#dc3545' : '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: confirmButtonText,
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (!result.isConfirmed) return;

                submitCsrfForm('<?= $URL; ?>controllers/usuarios/desactivar_usuario.php', {
                    id: usuarioId,
                    estado: estadoActual == 1 ? 0 : 1
                });
            });
        });
    });
</script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
