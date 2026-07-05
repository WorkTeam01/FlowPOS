<?php
require_once __DIR__ . '/../../controllers/clientes/ClienteController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar permisos para el módulo de clientes
if (!$authService->tienePermisoNombre($idusuario, 'clientes') && !$authService->esAdministrador($idusuario)) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Verificar si se proporcionó un ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    $_SESSION['mensaje'] = 'ID de cliente no válido';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Instanciar el controlador y obtener los datos del cliente
$controller = new ClienteController();
$cliente = $controller->editar($id);

// Verificar si el cliente existe
if (!$cliente) {
    $_SESSION['mensaje'] = 'Cliente no encontrado';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Detalle del Cliente</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/clientes"><i class="fas fa-users"></i> Clientes</a></li>
                    <li class="breadcrumb-item active">Detalle del Cliente</li>
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
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Información Detallada del Cliente</h3>
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
                                                <?= htmlspecialchars($cliente['nombres'] . ' ' . $cliente['apellidopaterno'] . ' ' . ($cliente['apellidomaterno'] ?? '')); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><i class="fas fa-venus-mars"></i> Género:</label>
                                            <p class="lead">
                                                <?= !empty($cliente['genero']) ? htmlspecialchars($cliente['genero']) : '<span class="text-muted">No especificado</span>'; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Documentación -->
                        <div class="card card-outline card-info mb-3">
                            <div class="card-header">
                                <h3 class="card-title">Documentación</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><i class="fas fa-id-card"></i> Tipo de Documento:</label>
                                            <p class="lead">
                                                <?= htmlspecialchars($cliente['tipodocumento']); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><i class="fas fa-hashtag"></i> Número de Documento:</label>
                                            <p class="lead">
                                                <?= htmlspecialchars($cliente['numdocumento']); ?>
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
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label><i class="fas fa-map-marker-alt"></i> Dirección:</label>
                                            <p class="lead">
                                                <?= !empty($cliente['direccion']) ? htmlspecialchars($cliente['direccion']) : '<span class="text-muted">No registrada</span>'; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><i class="fas fa-mobile-alt"></i> Celular:</label>
                                            <p class="lead">
                                                <?php if (!empty($cliente['celular'])): ?>
                                                    <a href="tel:<?= htmlspecialchars($cliente['celular']); ?>">
                                                        <?= htmlspecialchars($cliente['celular']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">No registrado</span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><i class="fas fa-envelope"></i> Email:</label>
                                            <p class="lead">
                                                <?php if (!empty($cliente['email'])): ?>
                                                    <a href="mailto:<?= htmlspecialchars($cliente['email']); ?>">
                                                        <?= htmlspecialchars($cliente['email']); ?>
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
                        <div class="card card-outline card-info">
                            <div class="card-header">
                                <h3 class="card-title">Información del Sistema</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><i class="fas fa-toggle-on"></i> Estado:</label>
                                            <p>
                                                <?php if ($cliente['estado'] == 1): ?>
                                                    <span class="badge badge-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Inactivo</span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><i class="fas fa-calendar-plus"></i> Fecha de Registro:</label>
                                            <p class="text-muted">
                                                <?= date('d/m/Y H:i', strtotime($cliente['fechacreacion'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><i class="fas fa-calendar-check"></i> Última Actualización:</label>
                                            <p class="text-muted">
                                                <?= !empty($cliente['fechaactualizacion']) ? date('d/m/Y H:i', strtotime($cliente['fechaactualizacion'])) : 'Sin actualizaciones'; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <!-- Botones de acción principales -->
                        <div class="row g-1">
                            <div class="col-12 col-sm-auto">
                                <a href="<?= $URL; ?>views/clientes/update.php" class="btn btn-warning w-100">
                                    <i class="fas fa-edit mr-1"></i> Editar Cliente
                                </a>
                            </div>
                            <div class="col-12 col-sm-auto">
                                <a href="<?= $URL; ?>views/ventas/nueva.php" class="btn btn-success w-100">
                                    <i class="fas fa-shopping-cart mr-1"></i> Nueva Venta
                                </a>
                            </div>
                            <div class="col-12 col-sm-auto">
                                <a href="<?= $URL; ?>views/clientes" class="btn btn-secondary w-100">
                                    <i class="fas fa-arrow-left mr-1"></i> Volver
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.col-md-8 -->

            <!-- Columna derecha - Perfil y acciones rápidas (4 columnas) -->
            <div class="col-md-4">
                <!-- Tarjeta de perfil del cliente -->
                <div class="card card-primary card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center">
                            <i class="fas fa-user-circle fa-5x text-primary mb-3"></i>
                        </div>

                        <h3 class="profile-username text-center">
                            <?= htmlspecialchars($cliente['nombres'] . ' ' . $cliente['apellidopaterno']); ?>
                        </h3>

                        <p class="text-muted text-center">Cliente desde <?= date('d/m/Y', strtotime($cliente['fechacreacion'])); ?></p>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b><i class="fas fa-id-card mr-1"></i> <?= htmlspecialchars($cliente['tipodocumento']); ?></b>
                                <span class="float-right"><?= htmlspecialchars($cliente['numdocumento']); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-phone mr-1"></i> Celular</b>
                                <span class="float-right"><?= htmlspecialchars($cliente['celular'] ?? 'No registrado'); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-envelope mr-1"></i> Email</b>
                                <span class="float-right text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($cliente['email'] ?? 'No registrado'); ?>">
                                    <?= htmlspecialchars($cliente['email'] ?? 'No registrado'); ?>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-toggle-on mr-1"></i> Estado</b>
                                <span class="float-right">
                                    <?php if ($cliente['estado'] == 1): ?>
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
                    <div class="card-body p-0">
                        <div class="list-group">
                            <?php if ($cliente['estado'] == 1): ?>
                                <button type="button" class="list-group-item list-group-item-action"
                                    onclick="cambiarEstado(<?= $cliente['idcliente']; ?>, 0);">
                                    <i class="fas fa-user-slash mr-2"></i> Desactivar Cliente
                                </button>
                            <?php else: ?>
                                <button type="button" class="list-group-item list-group-item-action"
                                    onclick="cambiarEstado(<?= $cliente['idcliente']; ?>, 1);">
                                    <i class="fas fa-user-check mr-2"></i> Activar Cliente
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de estadísticas (opcional, implementar si existe la funcionalidad) -->
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">Estadísticas del Cliente</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <tbody>
                                <tr>
                                    <td><i class="fas fa-shopping-bag mr-1"></i> Total Compras</td>
                                    <td>0</td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-money-bill-wave mr-1"></i> Monto Total</td>
                                    <td>Bs 0.00</td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-calendar-day mr-1"></i> Última Compra</td>
                                    <td><span class="text-muted">No hay compras</span></td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-star mr-1"></i> Categoría Cliente</td>
                                    <td><span class="badge badge-secondary">Nuevo</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-light">
                        <small class="text-muted">Las estadísticas se actualizan con cada compra</small>
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
    // Función para cambiar el estado del cliente
    function cambiarEstado(clienteId, nuevoEstado) {
        // El controlador espera el estado actual, no el nuevo
        const estadoActual = nuevoEstado == 0 ? 1 : 0;

        const tituloAlerta = nuevoEstado == 1 ? '¿Activar cliente?' : '¿Desactivar cliente?';
        const textoAlerta = nuevoEstado == 1 ?
            "El cliente podrá realizar nuevas compras." :
            "El cliente no podrá realizar compras hasta que sea activado nuevamente.";
        const confirmButtonText = nuevoEstado == 1 ? 'Sí, activar' : 'Sí, desactivar';

        Swal.fire({
            title: tituloAlerta,
            text: textoAlerta,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: nuevoEstado == 1 ? '#28a745' : '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: confirmButtonText,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Usar la misma ruta que usa index.php
                submitCsrfForm('<?= $URL; ?>controllers/clientes/desactivar_cliente.php', {
                    id: clienteId,
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