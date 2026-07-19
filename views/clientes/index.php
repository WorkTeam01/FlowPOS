<?php
require_once __DIR__ . '/../../controllers/clientes/ClienteController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'] ?? '';

$authService = new AuthorizationService();

// Verificar permisos para el módulo de clientes
if (!$authService->tienePermisoNombre($idusuario, 'clientes') && !$authService->esAdministrador($idusuario)) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL);
    exit;
}

include_once '../layouts/header.php';

$controller = new ClienteController();
$clientes = $controller->index();
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Gestión de Clientes</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"> <i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Clientes</li>
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
                        <h3 class="card-title">Listado de Clientes</h3>
                        <div class="card-tools">
                            <a href="<?= $URL; ?>views/clientes/create.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Nuevo Cliente
                            </a>
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tablaClientes" class="table table-sm table-bordered table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Nro</th>
                                        <th>Nombre Completo</th>
                                        <th>Tipo Documento</th>
                                        <th>Número Documento</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador = 1;
                                    foreach ($clientes as $cliente) :
                                        $estado_actual = $cliente['estado'];
                                        $clase_boton_estado = $estado_actual == 1 ? 'btn-danger' : 'btn-success';
                                        $icono_boton_estado = $estado_actual == 1 ? 'fa-user-slash' : 'fa-user-check';
                                        $titulo_alerta = $estado_actual == 1 ? '¿Desactivar Cliente?' : '¿Activar Cliente?';
                                        $texto_alerta = $estado_actual == 1 ? 'El cliente no podrá realizar reservas.' : 'El cliente podrá realizar reservas nuevamente.';
                                        $confirm_button_text = $estado_actual == 1 ? 'Sí, desactivar' : 'Sí, activar';
                                        $texto_boton_estado = $estado_actual == 1 ? 'Desactivar cliente' : 'Activar cliente';
                                    ?>
                                        <tr>
                                            <td class="text-center"><?= $contador++; ?></td>
                                            <td><?= htmlspecialchars($cliente['nombres'] . ' ' . $cliente['apellidopaterno'] . ' ' . ($cliente['apellidomaterno'] ?? '')); ?></td>
                                            <td><?= htmlspecialchars($cliente['tipodocumento']); ?></td>
                                            <td><?= htmlspecialchars($cliente['numdocumento']); ?></td>
                                            <td class="text-center">
                                                <?php if ($estado_actual == 1) : ?>
                                                    <span class="badge badge-success">Activo</span>
                                                <?php else : ?>
                                                    <span class="badge badge-danger">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="<?= $URL; ?>views/clientes/show.php?id=<?= $cliente['idcliente']; ?>" class="btn btn-info btn-sm" data-toggle="tooltip" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?= $URL; ?>views/clientes/update.php?id=<?= $cliente['idcliente']; ?>" class="btn btn-warning btn-sm" data-toggle="tooltip" title="Editar cliente">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn <?= $clase_boton_estado; ?> btn-sm btn-cambiar-estado"
                                                        data-id="<?= $cliente['idcliente']; ?>"
                                                        data-estado="<?= $estado_actual; ?>"
                                                        data-nombre="<?= htmlspecialchars($cliente['nombres']); ?>"
                                                        data-toggle="tooltip" title="<?= $texto_boton_estado; ?>">
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

<script src="<?= $URL; ?>public/js/modules/clientes/index-clientes.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        $('[data-toggle="tooltip"]').tooltip();

        const botonesCambiarEstado = document.querySelectorAll('.btn-cambiar-estado');

        botonesCambiarEstado.forEach(boton => {
            boton.addEventListener('click', function() {
                const clienteId = this.dataset.id;
                const estadoActual = this.dataset.estado;
                const nombreCliente = this.dataset.nombre;

                const tituloAlerta = estadoActual == 1 ? `¿Desactivar a ${nombreCliente}?` : `¿Activar a ${nombreCliente}?`;
                const textoAlerta = estadoActual == 1 ? 'El cliente no podrá realizar reservas.' : 'El cliente podrá realizar reservas nuevamente.';
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
                        submitCsrfForm(`${baseUrl}controllers/clientes/desactivar_cliente.php`, {
                            id: clienteId,
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