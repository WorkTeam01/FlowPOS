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
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
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
            <!-- Columna izquierda (8 columnas) -->
            <div class="col-md-8">
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">Información Detallada del Usuario</h3>
                    </div>
                    <div class="card-body">
                        <!-- Información Personal -->
                        <div class="card card-outline card-info mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Información Personal</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><i class="fas fa-user"></i> Nombre Completo:</label>
                                            <p class="lead">
                                                <?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidopaterno'] . ' ' . $usuario['apellidomaterno']); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><i class="fas fa-id-card"></i> Documento:</label>
                                            <p class="lead">
                                                <?= htmlspecialchars($usuario['tipodocumento'] . ': ' . $usuario['numdocumento']); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label><i class="fas fa-map-marker-alt"></i> Dirección:</label>
                                            <p class="lead">
                                                <?= !empty($usuario['direccion']) ? htmlspecialchars($usuario['direccion']) : '<span class="text-muted">No registrada</span>'; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información de Contacto -->
                        <div class="card card-outline card-info mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Información de Contacto</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><i class="fas fa-envelope"></i> Correo Electrónico:</label>
                                            <p class="lead">
                                                <?php if (!empty($usuario['correo'])): ?>
                                                    <a href="mailto:<?= htmlspecialchars($usuario['correo']); ?>">
                                                        <?= htmlspecialchars($usuario['correo']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">No registrado</span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><i class="fas fa-phone"></i> Teléfono:</label>
                                            <p class="lead">
                                                <?php if (!empty($usuario['telefono'])): ?>
                                                    <a href="tel:<?= htmlspecialchars($usuario['telefono']); ?>">
                                                        <?= htmlspecialchars($usuario['telefono']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">No registrado</span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información del Sistema -->
                        <div class="card card-outline card-info mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Información del Sistema</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><i class="fas fa-user-tag"></i> Cargo:</label>
                                            <p class="lead">
                                                <?= !empty($usuario['cargo']) ? htmlspecialchars($usuario['cargo']) : '<span class="text-muted">No asignado</span>'; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><i class="fas fa-toggle-on"></i> Estado:</label>
                                            <p>
                                                <?php if ($usuario['estado'] == 1): ?>
                                                    <span class="badge badge-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Inactivo</span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><i class="fas fa-calendar-plus"></i> Fecha de Creación:</label>
                                            <p class="text-muted">
                                                <?= isset($usuario['fechacreacion']) ? date('d/m/Y H:i', strtotime($usuario['fechacreacion'])) : 'No disponible'; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><i class="fas fa-calendar-check"></i> Última Actualización:</label>
                                            <p class="text-muted">
                                                <?= isset($usuario['fechaactualizacion']) ? date('d/m/Y H:i', strtotime($usuario['fechaactualizacion'])) : 'No disponible'; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Permisos Asignados -->
                        <div class="card card-outline card-info">
                            <div class="card-header">
                                <h3 class="card-title">Permisos Asignados</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php
                                    // Obtener los permisos asignados al usuario
                                    $permisosAsignados = $authService->obtenerPermisosAsignados($usuario['idusuario']);
                                    $todosLosPermisos = $authService->obtenerTodosLosPermisos();

                                    if (empty($permisosAsignados) && !$authService->esAdministrador($usuario['idusuario'])):
                                    ?>
                                        <div class="col-12">
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle"></i> Este usuario no tiene permisos específicos asignados.
                                            </div>
                                        </div>
                                    <?php elseif ($authService->esAdministrador($usuario['idusuario'])): ?>
                                        <div class="col-12">
                                            <div class="alert alert-info">
                                                <i class="fas fa-crown"></i> Este usuario es Administrador y tiene acceso a todas las funcionalidades del sistema.
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($todosLosPermisos as $permiso): ?>
                                            <div class="col-md-4 mb-2">
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
                        </div>
                    </div>
                    <div class="card-footer">
                        <!-- Botones de acción -->
                        <div class="row g-1">
                            <div class="col-12 col-sm-auto">
                                <a href="<?= $URL; ?>views/usuarios/update.php?id=<?= $usuario['idusuario']; ?>" class="btn btn-warning w-100">
                                    <i class="fas fa-edit mr-1"></i> Editar Usuario
                                </a>
                            </div>
                            <div class="col-12 col-sm-auto">
                                <a href="<?= $URL; ?>views/usuarios/index.php" class="btn btn-secondary w-100">
                                    <i class="fas fa-arrow-left mr-1"></i> Volver a la Lista
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.col-md-8 -->

            <!-- Columna derecha - Perfil y acciones rápidas (4 columnas) -->
            <div class="col-md-4">
                <!-- Tarjeta de perfil -->
                <div class="card card-info card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center">
                            <?php if (isset($usuario['imagen']) && !empty($usuario['imagen'])): ?>
                                <img class="profile-user-img img-fluid img-circle"
                                    src="<?= $URL; ?>public/uploads/usuarios/<?= $usuario['imagen']; ?>"
                                    alt="Imagen de perfil">
                            <?php else: ?>
                                <img class="profile-user-img img-fluid img-circle"
                                    src="<?= $URL; ?>public/uploads/usuarios/user_default.jpg"
                                    alt="Imagen de perfil">
                            <?php endif; ?>
                        </div>

                        <h3 class="profile-username text-center">
                            <?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidopaterno']); ?>
                        </h3>

                        <p class="text-muted text-center"><?= htmlspecialchars($usuario['cargo'] ?? 'Sin cargo asignado'); ?></p>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b><i class="fas fa-id-card mr-1"></i> <?= htmlspecialchars($usuario['tipodocumento']); ?></b>
                                <span class="float-right"><?= htmlspecialchars($usuario['numdocumento']); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-envelope mr-1"></i> Email</b>
                                <span class="float-right text-truncate" style="max-width: 170px;" title="<?= htmlspecialchars($usuario['correo'] ?? 'No registrado'); ?>">
                                    <?= htmlspecialchars($usuario['correo'] ?? 'No registrado'); ?>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-phone mr-1"></i> Teléfono</b>
                                <span class="float-right"><?= htmlspecialchars($usuario['telefono'] ?? 'No registrado'); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-toggle-on mr-1"></i> Estado</b>
                                <span class="float-right">
                                    <?php if ($usuario['estado'] == 1): ?>
                                        <span class="badge badge-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactivo</span>
                                    <?php endif; ?>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Tarjeta de acciones rápidas -->
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">Acciones Rápidas</h3>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php if ($usuario['estado'] == 1): ?>
                                <button type="button" class="list-group-item list-group-item-action"
                                    onclick="cambiarEstado(<?= $usuario['idusuario']; ?>, 0);">
                                    <i class="fas fa-user-slash mr-2"></i> Desactivar Usuario
                                </button>
                            <?php else: ?>
                                <button type="button" class="list-group-item list-group-item-action"
                                    onclick="cambiarEstado(<?= $usuario['idusuario']; ?>, 1);">
                                    <i class="fas fa-user-check mr-2"></i> Activar Usuario
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de información del sistema -->
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">Datos del Sistema</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <tbody>
                                <tr>
                                    <td><i class="fas fa-calendar-plus mr-1"></i> Creado</td>
                                    <td><span class="text-muted"><?= isset($usuario['fechacreacion']) ? date('d/m/Y H:i', strtotime($usuario['fechacreacion'])) : 'No disponible'; ?></span></td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-calendar-check mr-1"></i> Actualizado</td>
                                    <td><span class="text-muted"><?= isset($usuario['fechaactualizacion']) ? date('d/m/Y H:i', strtotime($usuario['fechaactualizacion'])) : 'No disponible'; ?></span></td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-user-shield mr-1"></i> Tipo</td>
                                    <td>
                                        <?php if ($authService->esAdministrador($usuario['idusuario'])): ?>
                                            <span class="badge badge-primary">Administrador</span>
                                        <?php else: ?>
                                            <span class="badge badge-info"><?= htmlspecialchars($usuario['cargo'] ?? 'Usuario estándar'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- /.col-md-4 -->
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container-fluid -->
</section>
<!-- /.content -->

<script>
    // Función para cambiar el estado del usuario
    function cambiarEstado(userId, newStatus) {
        const estadoActual = newStatus == 0 ? 1 : 0; // Convertir al estado actual para el controlador
        const tituloAlerta = newStatus == 1 ? '¿Activar usuario?' : '¿Desactivar usuario?';
        const textoAlerta = newStatus == 1 ?
            "El usuario podrá acceder nuevamente al sistema." :
            "El usuario no podrá acceder al sistema hasta que sea activado nuevamente.";
        const confirmButtonText = newStatus == 1 ? 'Sí, activar' : 'Sí, desactivar';

        Swal.fire({
            title: tituloAlerta,
            text: textoAlerta,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: newStatus == 1 ? '#28a745' : '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: confirmButtonText,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                submitCsrfForm('<?= $URL; ?>controllers/usuarios/desactivar_usuario.php', {
                    id: userId,
                    estado: estadoActual
                });
            }
        });
    }
</script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>