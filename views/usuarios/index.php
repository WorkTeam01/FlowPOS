<?php
require_once __DIR__ . '/../../controllers/usuarios/UsuarioController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'] ?? '';

$authService = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!($authService->tienePermisoNombre($idusuario, 'usuarios')) && !($authService->esAdministrador($idusuario))) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL);
    exit;
}

// Incluir el encabezado DESPUÉS de verificar permisos
include_once '../layouts/header.php';

$controller = new UsuarioController();
$usuarios = $controller->index();
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Gestión de Usuarios</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Usuarios</li>
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
                <div class="card">
                    <div class="card-header card-outline card-primary">
                        <h3 class="card-title">Listado de Usuarios</h3>
                        <div class="card-tools">
                            <a href="<?= $URL; ?>views/usuarios/create.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Nuevo Usuario
                            </a>
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tablaUsuarios" class="table table-sm table-bordered table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Nro</th>
                                        <th>Nombre</th>
                                        <th>Tipo Documento</th>
                                        <th>Número Documento</th>
                                        <th>Correo</th>
                                        <th>Imágen</th>
                                        <th>Cargo</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador = 1;
                                    foreach ($usuarios as $usuario) :
                                        $estado_actual = $usuario['estado'];
                                        $clase_boton_estado = $estado_actual == 1 ? 'btn-danger' : 'btn-success';
                                        $icono_boton_estado = $estado_actual == 1 ? 'fa-user-slash' : 'fa-user-check';
                                        $titulo_alerta = $estado_actual == 1 ? '¿Desactivar Usuario?' : '¿Activar Usuario?';
                                        $texto_alerta = $estado_actual == 1 ? 'El usuario no podrá acceder al sistema.' : 'El usuario podrá acceder nuevamente al sistema.';
                                        $confirm_button_text = $estado_actual == 1 ? 'Sí, desactivar' : 'Sí, activar';
                                    ?>
                                        <tr>
                                            <td class="text-center"><?= $contador++; ?></td>
                                            <td><?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidopaterno']); ?></td>
                                            <td><?= htmlspecialchars($usuario['tipodocumento']); ?></td>
                                            <td><?= htmlspecialchars($usuario['numdocumento']); ?></td>
                                            <td><?= htmlspecialchars($usuario['correo']); ?></td>
                                            <td class="text-center">
                                                <?php if (isset($usuario['imagen'])): ?>
                                                    <img src="<?= $URL; ?>public/uploads/usuarios/<?= $usuario['imagen']; ?>" loading="lazy" alt="Imagen" class="img-thumbnail" width="50">
                                                <?php else : ?>
                                                    <img src="<?= $URL; ?>public/uploads/usuarios/user_default.jpg" loading="lazy" alt="Imagen" class="img-thumbnail" width="50">
                                                <?php endif; ?>
                                            </td>
                                            <td><?= (!empty($usuario['cargo'])) ? htmlspecialchars($usuario['cargo']) : 'N/A'; ?></td>
                                            <td class="text-center">
                                                <?php if ($estado_actual == 1) : ?>
                                                    <span class="badge badge-success">Activo</span>
                                                <?php else : ?>
                                                    <span class="badge badge-danger">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="<?= $URL; ?>views/usuarios/show.php?id=<?= $usuario['idusuario']; ?>" class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?= $URL; ?>views/usuarios/update.php?id=<?= $usuario['idusuario']; ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn <?= $clase_boton_estado; ?> btn-sm btn-cambiar-estado"
                                                        data-id="<?= $usuario['idusuario']; ?>"
                                                        data-estado="<?= $estado_actual; ?>"
                                                        data-nombre="<?= htmlspecialchars($usuario['nombre']); ?>">
                                                        <i class="fas <?= $icono_boton_estado; ?>"></i>
                                                    </button>
                                                </div>
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
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container-fluid -->
</section>
<!-- /.content -->

<script src="<?= $URL; ?>public/js/modules/usuarios/index-usuarios.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const botonesCambiarEstado = document.querySelectorAll('.btn-cambiar-estado');

        botonesCambiarEstado.forEach(boton => {
            boton.addEventListener('click', function() {
                const usuarioId = this.dataset.id;
                const estadoActual = this.dataset.estado;
                const nombreUsuario = this.dataset.nombre;

                const tituloAlerta = estadoActual == 1 ? `¿Desactivar a ${nombreUsuario}?` : `¿Activar a ${nombreUsuario}?`;
                const textoAlerta = estadoActual == 1 ? 'El usuario no podrá acceder al sistema.' : 'El usuario podrá acceder nuevamente al sistema.';
                const confirmButtonText = estadoActual == 1 ? 'Sí, desactivar' : 'Sí, activar';
                const cancelButtonText = 'Cancelar';

                Swal.fire({
                    title: tituloAlerta,
                    text: textoAlerta,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: estadoActual == 1 ? '#d33' : '#3085d6',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: confirmButtonText,
                    cancelButtonText: cancelButtonText
                }).then((result) => {
                    if (result.isConfirmed) {
                        const baseUrl = '<?= $URL; ?>';
                        submitCsrfForm(`${baseUrl}controllers/usuarios/desactivar_usuario.php`, {
                            id: usuarioId,
                            estado: estadoActual
                        });
                    }
                });
            });
        });
    });
</script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>