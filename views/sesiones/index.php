<?php
require_once __DIR__ . '/../../controllers/sesiones/SesionController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

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

// Incluir el encabezado DESPUÉS de verificar permisos
$skip_select2 = true; // Esta vista no usa Select2
include_once '../layouts/header.php';

$module_scripts = ['sesiones/index-sesiones'];

$controller = new SesionController();
$sesiones = $mostrarActivas ? $controller->getSesionesActivas() : $controller->index();
$estadisticas = $controller->getEstadisticas();
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
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
                    <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-calendar-week"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Sesiones Semana</span>
                        <span class="info-box-number"><?= $estadisticas['sesiones_semana']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-hourglass-half"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Duración Promedio</span>
                        <span class="info-box-number"><?= round($estadisticas['duracion_promedio']); ?> min</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?= $mostrarActivas ? 'Sesiones Activas' : 'Historial de Sesiones'; ?></h3>
                        <div class="card-tools">
                            <a href="<?= $URL; ?>views/sesiones/index.php<?= $mostrarActivas ? '' : '?activas=1'; ?>" class="btn btn-<?= $mostrarActivas ? 'info' : 'success'; ?> btn-sm">
                                <i class="fas fa-<?= $mostrarActivas ? 'history' : 'user-check'; ?>"></i>
                                <?= $mostrarActivas ? 'Ver todas las sesiones' : 'Ver solo sesiones activas'; ?>
                            </a>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tablaSesiones" class="table table-bordered table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 10px">#</th>
                                        <th>Usuario</th>
                                        <th>Inicio</th>
                                        <th>Fin</th>
                                        <th>Duración</th>
                                        <th>Dispositivo</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador = 1;
                                    foreach ($sesiones as $sesion) :
                                        $estado = $sesion['estado'];
                                        $clase_estado = $estado ? 'badge-success' : 'badge-secondary';
                                        $texto_estado = $estado ? 'Activa' : 'Cerrada';

                                        // Formatear duración
                                        $duracion = $controller->formatearDuracion(
                                            $sesion['horaingreso'],
                                            $estado ? null : $sesion['horasalida']
                                        );

                                        // Detectar dispositivo
                                        $dispositivo = $controller->detectarDispositivo($sesion['navegador']);
                                        $navegador = $controller->detectarNavegador($sesion['navegador']);

                                        // Formatear nombre completo
                                        $nombreCompleto = $sesion['nombre'] . ' ' . $sesion['apellidopaterno'];
                                    ?>
                                        <tr>
                                            <td><?= $contador++; ?></td>
                                            <td>
                                                <div class="user-block">
                                                    <img class="img-circle img-bordered-sm" src="<?= $URL; ?>public/uploads/usuarios/<?= $sesion['imagen'] ?: 'user_default.jpg'; ?>" alt="Usuario">
                                                    <span class="username">
                                                        <a href="#"><?= htmlspecialchars($nombreCompleto); ?></a>
                                                    </span>
                                                    <span class="description"><?= htmlspecialchars($sesion['cargo']); ?></span>
                                                </div>
                                            </td>
                                            <td><?= date('d/m/Y H:i:s', strtotime($sesion['horaingreso'])); ?></td>
                                            <td>
                                                <?= $sesion['horasalida'] ? date('d/m/Y H:i:s', strtotime($sesion['horasalida'])) : '<span class="text-success">Sesión en curso</span>'; ?>
                                            </td>
                                            <td><?= $duracion; ?></td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <?= $dispositivo; ?> - <?= $navegador; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?= $clase_estado; ?>"><?= $texto_estado; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($estado) : ?>
                                                    <button type="button" class="btn btn-danger btn-sm btn-cerrar-sesion"
                                                        data-id="<?= $sesion['idsesion']; ?>"
                                                        data-usuario="<?= htmlspecialchars($nombreCompleto); ?>"
                                                        data-toggle="tooltip" title="Cerrar sesión">
                                                        <i class="fas fa-power-off"></i>
                                                    </button>
                                                <?php else : ?>
                                                    <button type="button" class="btn btn-secondary btn-sm" disabled>
                                                        <i class="fas fa-power-off"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->

            <div class="col-md-4">
                <!-- Top Usuarios -->
                <div class="card">
                    <div class="card-header bg-primary">
                        <h3 class="card-title">
                            <i class="fas fa-trophy"></i> Top Usuarios por Sesiones
                        </h3>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body p-0">
                        <ul class="products-list product-list-in-card pl-2 pr-2">
                            <?php foreach ($estadisticas['usuarios_top'] as $index => $usuario) : ?>
                                <li class="item">
                                    <div class="product-img">
                                        <span class="badge badge-<?= $index === 0 ? 'warning' : 'info'; ?> p-2">
                                            <i class="fas fa-award"></i> #<?= $index + 1; ?>
                                        </span>
                                    </div>
                                    <div class="product-info">
                                        <a href="javascript:void(0)" class="product-title">
                                            <?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidopaterno']); ?>
                                            <span class="badge badge-primary float-right"><?= $usuario['total_sesiones']; ?> sesiones</span>
                                        </a>
                                        <span class="product-description">
                                            <button type="button" class="btn btn-xs btn-default btn-ver-sesiones-usuario"
                                                data-id="<?= $usuario['idusuario']; ?>"
                                                data-nombre="<?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidopaterno']); ?>">
                                                <i class="fas fa-eye"></i> Ver sesiones
                                            </button>
                                            <?php if ($authService->tienePermiso($idusuario, 'admin_completo')) : ?>
                                                <button type="button" class="btn btn-xs btn-danger btn-cerrar-todas-sesiones"
                                                    data-id="<?= $usuario['idusuario']; ?>"
                                                    data-nombre="<?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidopaterno']); ?>">
                                                    <i class="fas fa-power-off"></i> Cerrar todas
                                                </button>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->

                <!-- Información -->
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle"></i> Información
                        </h3>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="callout callout-info">
                            <h5>Monitoreo de Sesiones</h5>
                            <p>Este módulo le permite monitorear las sesiones de los usuarios en el sistema. Puede ver quién está conectado actualmente, cuándo iniciaron sesión, y desde qué dispositivo.</p>
                        </div>
                        <div class="callout callout-warning">
                            <h5>Sesiones Activas</h5>
                            <p>Las sesiones activas son aquellas donde el usuario aún está conectado al sistema. Puede cerrar sesiones activas utilizando el botón correspondiente.</p>
                        </div>
                        <div class="callout callout-danger">
                            <h5>Cierre de Sesiones</h5>
                            <p>Cerrar la sesión de un usuario lo desconectará del sistema. El usuario tendrá que iniciar sesión nuevamente para acceder.</p>
                        </div>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->

                <!-- Estadísticas adicionales -->
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-pie"></i> Estadísticas de Sesiones
                        </h3>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="info-box bg-gradient-info">
                            <span class="info-box-icon"><i class="fas fa-users"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Usuarios Únicos</span>
                                <span class="info-box-number"><?= $estadisticas['usuarios_unicos']; ?></span>
                                <div class="progress">
                                    <div class="progress-bar" style="width: 100%"></div>
                                </div>
                                <span class="progress-description">
                                    Usuarios que han iniciado sesión en el sistema
                                </span>
                            </div>
                        </div>
                        <div class="info-box bg-gradient-success">
                            <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Duración Promedio</span>
                                <span class="info-box-number"><?= round($estadisticas['duracion_promedio']); ?> minutos</span>
                                <div class="progress">
                                    <div class="progress-bar" style="width: 100%"></div>
                                </div>
                                <span class="progress-description">
                                    Tiempo promedio de una sesión
                                </span>
                            </div>
                        </div>
                    </div>
                    <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container-fluid -->
</section>
<!-- /.content -->

<!-- Modal para ver sesiones de usuario -->
<div class="modal fade" id="modalSesionesUsuario">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h4 class="modal-title">Sesiones de <span id="nombreUsuarioModal"></span></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center p-4" id="cargandoSesiones">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando sesiones...</p>
                </div>
                <div id="contenidoSesionesUsuario"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar tooltips
        $('[data-toggle="tooltip"]').tooltip();

        // Botones para cerrar sesión
        const botonesCerrarSesion = document.querySelectorAll('.btn-cerrar-sesion');
        botonesCerrarSesion.forEach(boton => {
            boton.addEventListener('click', function() {
                const sesionId = this.dataset.id;
                const usuario = this.dataset.usuario;

                Swal.fire({
                    title: `¿Cerrar sesión de ${usuario}?`,
                    text: 'El usuario será desconectado del sistema.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, cerrar sesión',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitCsrfForm('<?= $URL; ?>controllers/sesiones/cerrar_sesion.php', {
                            id: sesionId
                        });
                    }
                });
            });
        });

        // Botones para cerrar todas las sesiones de un usuario
        const botonesCerrarTodasSesiones = document.querySelectorAll('.btn-cerrar-todas-sesiones');
        botonesCerrarTodasSesiones.forEach(boton => {
            boton.addEventListener('click', function() {
                const usuarioId = this.dataset.id;
                const nombre = this.dataset.nombre;

                Swal.fire({
                    title: `¿Cerrar todas las sesiones de ${nombre}?`,
                    text: 'El usuario será desconectado de todas sus sesiones activas.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, cerrar todas',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitCsrfForm('<?= $URL; ?>controllers/sesiones/cerrar_sesiones_usuario.php', {
                            id: usuarioId
                        });
                    }
                });
            });
        });

        // Botones para ver sesiones de un usuario
        const botonesVerSesiones = document.querySelectorAll('.btn-ver-sesiones-usuario');
        botonesVerSesiones.forEach(boton => {
            boton.addEventListener('click', function() {
                const usuarioId = this.dataset.id;
                const nombre = this.dataset.nombre;

                // Mostrar modal
                $('#nombreUsuarioModal').text(nombre);
                $('#cargandoSesiones').show();
                $('#contenidoSesionesUsuario').empty();
                $('#modalSesionesUsuario').modal('show');

                // Cargar sesiones del usuario (simulado por ahora)
                setTimeout(() => {
                    $('#cargandoSesiones').hide();
                    $('#contenidoSesionesUsuario').html(`
                        <div class="alert alert-info">
                            <p><strong>Nota:</strong> Esta funcionalidad estará disponible en una próxima actualización.</p>
                            <p>Puede ver todas las sesiones del usuario ${nombre} en la tabla principal filtrando por su nombre.</p>
                        </div>
                    `);
                }, 1000);
            });
        });
    });
</script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>