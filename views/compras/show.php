<?php
require_once __DIR__ . '/../../controllers/compras/CompraController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!($authService->tienePermisoNombre($idusuario, 'compras')) && !($authService->esAdministrador($idusuario))) {
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
    $_SESSION['mensaje'] = 'ID de compra no válido';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Instanciar el controlador y obtener los datos de la compra
$controller = new CompraController();
$compra = $controller->ver($id);

// Verificar si la compra existe
if (!$compra) {
    $_SESSION['mensaje'] = 'Compra no encontrada';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Determinar clase CSS según estado (1 = activa, 0 = cancelada)
$estado_clase = $compra['estado'] == 1 ? 'success' : 'danger';
$estado_texto = $compra['estado'] == 1 ? 'Activa' : 'Cancelada';
$estado_icono = $compra['estado'] == 1 ? 'check-circle' : 'times-circle';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-file-invoice-dollar mr-2"></i>Detalle de Compra #<?= str_pad($compra['idcompra'], 6, '0', STR_PAD_LEFT); ?></h1>
                <p class="text-muted">Información detallada de la compra registrada</p>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/compras"><i class="fas fa-shopping-cart"></i> Compras</a></li>
                    <li class="breadcrumb-item active"><i class="fas fa-search"></i> Detalle</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-4">
                <!-- Tarjeta de información básica -->
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Información Básica</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body box-profile">
                        <div class="text-center mb-3">
                            <i class="fas fa-receipt fa-5x text-primary"></i>
                        </div>

                        <h3 class="profile-username text-center">
                            Compra #<?= str_pad($compra['idcompra'], 6, '0', STR_PAD_LEFT); ?>
                        </h3>

                        <p class="text-muted text-center">
                            <span class="badge badge-<?= $estado_clase; ?>">
                                <i class="fas fa-<?= $estado_icono; ?> mr-1"></i> <?= $estado_texto; ?>
                            </span>
                        </p>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b><i class="far fa-calendar-alt mr-2"></i>Fecha</b>
                                <span class="float-right">
                                    <?= date('d/m/Y H:i', strtotime($compra['fechacompra'])); ?>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-user-tag mr-2"></i>Usuario</b>
                                <span class="float-right">
                                    <?= htmlspecialchars($compra['usuario_nombre'] ?? 'N/A'); ?>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-file-invoice-dollar mr-2"></i>Total</b>
                                <span class="float-right">
                                    $<?= number_format($compra['totalcompra'], 2); ?>
                                </span>
                            </li>
                        </ul>

                        <div class="d-flex justify-content-between">
                            <?php if ($compra['estado'] == 1): ?>
                                <button class="btn btn-danger btn-cancelar" data-id="<?= $compra['idcompra']; ?>">
                                    <i class="fas fa-ban mr-1"></i> Cancelar Compra
                                </button>
                            <?php endif; ?>
                            <a href="<?= $URL; ?>views/compras/index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de observaciones -->
                <?php if (!empty($compra['observaciones'])): ?>
                    <div class="card card-info card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-comment-dots mr-2"></i>Observaciones</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="callout callout-info">
                                <p><i class="fas fa-info-circle mr-2"></i><?= htmlspecialchars($compra['observaciones']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-md-8">
                <!-- Tarjeta de detalles de productos -->
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-boxes mr-2"></i>Productos Comprados</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="bg-light">
                                    <tr>
                                        <th>#</th>
                                        <th><i class="fas fa-box-open mr-1"></i> Producto</th>
                                        <th><i class="fas fa-barcode mr-1"></i> Código</th>
                                        <th class="text-right"><i class="fas fa-hashtag mr-1"></i> Cantidad</th>
                                        <th class="text-right"><i class="fas fa-dollar-sign mr-1"></i> P. Unitario</th>
                                        <th class="text-right"><i class="fas fa-calculator mr-1"></i> Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($compra['detalles'] as $index => $detalle): ?>
                                        <tr>
                                            <td><?= $index + 1; ?></td>
                                            <td><?= htmlspecialchars($detalle['producto_nombre']); ?></td>
                                            <td><?= htmlspecialchars($detalle['producto_codigo'] ?? 'N/A'); ?></td>
                                            <td class="text-right"><?= $detalle['cantidad']; ?></td>
                                            <td class="text-right">$<?= number_format($detalle['preciocompra'], 2); ?></td>
                                            <td class="text-right">$<?= number_format($detalle['cantidad'] * $detalle['preciocompra'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="table-active">
                                        <td colspan="5" class="text-right"><strong><i class="fas fa-file-invoice-dollar mr-1"></i> Total:</strong></td>
                                        <td class="text-right"><strong>$<?= number_format($compra['totalcompra'], 2); ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de resumen estadístico -->
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i>Resumen Estadístico</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-info"><i class="fas fa-boxes"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Productos</span>
                                        <span class="info-box-number"><?= count($compra['detalles']); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-success"><i class="fas fa-hashtag"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Unidades Totales</span>
                                        <span class="info-box-number">
                                            <?= array_reduce($compra['detalles'], function ($carry, $item) {
                                                return $carry + $item['cantidad'];
                                            }, 0); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-primary"><i class="fas fa-dollar-sign"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Promedio Unitario</span>
                                        <span class="info-box-number">
                                            $<?= number_format($compra['totalcompra'] / array_reduce($compra['detalles'], function ($carry, $item) {
                                                    return $carry + $item['cantidad'];
                                                }, 0), 2); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Manejar botón de cancelar compra
        const btnCancelar = document.querySelector('.btn-cancelar');
        if (btnCancelar) {
            btnCancelar.addEventListener('click', function() {
                const compraId = this.dataset.id;
                confirmarAccion(compraId, 'cancelar');
            });
        }

        function confirmarAccion(id, accion) {
            Swal.fire({
                title: '¿Cancelar esta compra?',
                text: 'La compra será cancelada y el stock de productos será revertido. Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-check mr-2"></i> Sí, cancelar',
                cancelButtonText: '<i class="fas fa-times mr-2"></i> Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Procesando...',
                        html: 'Cancelando la compra y ajustando inventario',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    submitCsrfForm('<?= $URL; ?>controllers/compras/cambiar_estado_compra.php', {
                        id: id,
                        accion: accion
                    });
                }
            });
        }
    });
</script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
