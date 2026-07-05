<?php
require_once __DIR__ . '/../../controllers/ventas/VentaController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'] ?? '';
$authService = new AuthorizationService();

// Verificar permisos
if (!($authService->tienePermisoNombre($idusuario, 'ventas'))) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/ventas');
    exit;
}

// Verificar ID de venta
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    $_SESSION['mensaje'] = 'ID de venta no válido';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/ventas');
    exit;
}

// Obtener datos de la venta
$controller = new VentaController();
$venta = $controller->ver($id);

if (!$venta) {
    $_SESSION['mensaje'] = 'Venta no encontrada';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/ventas');
    exit;
}

// Determinar si es pago mixto
$esPagoMixto = count($venta['pagos'] ?? []) > 1;

include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Detalle de Venta #<?= str_pad($venta['idventa'], 6, '0', STR_PAD_LEFT); ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/ventas/"> <i class="fas fa-shopping-cart"></i> Ventas</a></li>
                    <li class="breadcrumb-item active">Detalle de Venta</li>
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
                        <h3 class="card-title">Información General</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Código:</label>
                            <p class="text-muted">VENT-<?= str_pad($venta['idventa'], 6, '0', STR_PAD_LEFT); ?></p>
                        </div>

                        <div class="form-group">
                            <label>Fecha Venta:</label>
                            <p class="text-muted"><?= date('d/m/Y H:i', strtotime($venta['fechacreacion'])); ?></p>
                        </div>

                        <div class="form-group">
                            <label>Cliente:</label>
                            <p class="text-muted"><?= htmlspecialchars($venta['cliente_nombre'] ?? 'Consumidor Final'); ?></p>
                        </div>

                        <div class="form-group">
                            <label>Vendedor:</label>
                            <p class="text-muted"><?= htmlspecialchars($venta['usuario_nombre'] ?? 'Usuario no registrado'); ?></p>
                        </div>

                        <div class="form-group">
                            <label>Estado:</label>
                            <p>
                                <?php if ($venta['estado'] == 1) : ?>
                                    <span class="badge badge-success">Activa</span>
                                <?php else : ?>
                                    <span class="badge badge-danger">Anulada</span>
                                <?php endif; ?>
                            </p>
                        </div>

                        <div class="form-group">
                            <label>Total Venta:</label>
                            <p class="text-primary font-weight-bold" style="font-size: 1.5rem;">
                                <?= number_format($venta['totalventa'], 2); ?> Bs.
                            </p>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?= $URL; ?>views/ventas/index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>

                            <?php if ($venta['estado'] == 1) : ?>
                                <button type="button" class="btn btn-danger btn-anular-venta"
                                    data-id="<?= $venta['idventa']; ?>"
                                    data-nombre="Venta #<?= str_pad($venta['idventa'], 6, '0', STR_PAD_LEFT); ?>">
                                    <i class="fas fa-ban"></i> Anular Venta
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de métodos de pago -->
                <div class="card card-outline <?= $esPagoMixto ? 'card-purple' : 'card-success' ?>">
                    <div class="card-header">
                        <h3 class="card-title">
                            <?php if ($esPagoMixto): ?>
                                <i class="fas fa-money-check-alt"></i> Pago Mixto
                            <?php else: ?>
                                <i class="fas fa-money-bill-wave"></i> Método de Pago
                            <?php endif; ?>
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Método</th>
                                    <th class="text-right">Monto</th>
                                    <th class="text-right">Recibido</th>
                                    <th class="text-right">Cambio</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $totalPagado = 0;
                                foreach ($venta['pagos'] as $pago):
                                    $totalPagado += $pago['monto'];

                                    // Determinar ícono y clase para el método de pago
                                    $iconoMetodo = 'fas fa-money-bill-alt';
                                    $claseMetodo = 'badge-secondary';

                                    switch ($pago['metodopago']) {
                                        case 'efectivo':
                                            $iconoMetodo = 'fas fa-money-bill-wave';
                                            $claseMetodo = 'badge-success';
                                            break;
                                        case 'tarjeta':
                                            $iconoMetodo = 'far fa-credit-card';
                                            $claseMetodo = 'badge-primary';
                                            break;
                                        case 'qr':
                                            $iconoMetodo = 'fas fa-qrcode';
                                            $claseMetodo = 'badge-info';
                                            break;
                                        case 'transferencia':
                                            $iconoMetodo = 'fas fa-exchange-alt';
                                            $claseMetodo = 'badge-secondary';
                                            break;
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <span class="badge <?= $claseMetodo ?>">
                                                <i class="<?= $iconoMetodo ?>"></i>
                                                <?= ucfirst($pago['metodopago']) ?>
                                            </span>
                                        </td>
                                        <td class="text-right"><?= number_format($pago['monto'], 2) ?> Bs.</td>
                                        <td class="text-right"><?= number_format($pago['pagorecibido'], 2) ?> Bs.</td>
                                        <td class="text-right"><?= number_format($pago['cambio'], 2) ?> Bs.</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total Pagado:</th>
                                    <th class="text-right"><?= number_format($totalPagado, 2) ?> Bs.</th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Tarjeta de detalles de productos -->
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Productos Vendidos</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Producto</th>
                                        <th class="text-right">Precio Unit.</th>
                                        <th class="text-center">Cantidad</th>
                                        <th class="text-right">Descuento</th>
                                        <th class="text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador = 1;
                                    $subtotalGeneral = 0;
                                    $descuentoGeneral = 0;

                                    foreach ($venta['detalles'] as $detalle) :
                                        $precioUnitario = $detalle['precioventa'];
                                        $cantidad = $detalle['cantidad'];
                                        $descuento = $detalle['descuento'];
                                        $subtotal = ($precioUnitario * $cantidad) - $descuento;

                                        $subtotalGeneral += $subtotal;
                                        $descuentoGeneral += $descuento;
                                    ?>
                                        <tr>
                                            <td><?= $contador++; ?></td>
                                            <td>
                                                <?= htmlspecialchars($detalle['producto_nombre']); ?>
                                                <small class="text-muted d-block">
                                                    Código: <?= htmlspecialchars($detalle['producto_codigo'] ?? 'N/A'); ?>
                                                </small>
                                            </td>
                                            <td class="text-right"><?= number_format($precioUnitario, 2); ?> Bs.</td>
                                            <td class="text-center"><?= $cantidad; ?></td>
                                            <td class="text-right">
                                                <?php if ($descuento > 0): ?>
                                                    <span class="text-danger">
                                                        <?= number_format($descuento, 2); ?> Bs.
                                                    </span>
                                                <?php else: ?>
                                                    0.00 Bs.
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-right"><?= number_format($subtotal, 2); ?> Bs.</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="4" class="text-right">Subtotal:</th>
                                        <th class="text-right"><?= number_format($subtotalGeneral + $descuentoGeneral, 2); ?> Bs.</th>
                                        <th></th>
                                    </tr>
                                    <tr>
                                        <th colspan="4" class="text-right">Descuento Total:</th>
                                        <th class="text-right text-danger">-<?= number_format($descuentoGeneral, 2); ?> Bs.</th>
                                        <th></th>
                                    </tr>
                                    <tr class="bg-light">
                                        <th colspan="5" class="text-right">Total:</th>
                                        <th class="text-right"><?= number_format($venta['totalventa'], 2); ?> Bs.</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de información adicional -->
                <div class="card card-secondary card-outline mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Información Adicional</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Fecha de Creación:</label>
                                    <p class="text-muted">
                                        <?= date('d/m/Y H:i', strtotime($venta['fechacreacion'])); ?>
                                    </p>
                                </div>
                            </div>
                            <?php if (!empty($venta['fechaactualizacion'])) : ?>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Última Actualización:</label>
                                        <p class="text-muted">
                                            <?= date('d/m/Y H:i', strtotime($venta['fechaactualizacion'])); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($venta['observacion'])) : ?>
                            <div class="form-group">
                                <label>Observaciones:</label>
                                <div class="p-2 bg-light rounded">
                                    <?= nl2br(htmlspecialchars($venta['observacion'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($venta['estado'] == 0) : ?>
                            <div class="alert alert-warning">
                                <i class="icon fas fa-info-circle"></i>
                                Esta venta fue anulada el <?= date('d/m/Y H:i', strtotime($venta['fechaactualizacion'])); ?>.
                                El stock de los productos fue restaurado.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configuración del botón de anulación
        const btnAnular = document.querySelector('.btn-anular-venta');

        if (btnAnular) {
            btnAnular.addEventListener('click', function() {
                const ventaId = this.dataset.id;
                const nombreVenta = this.dataset.nombre;

                Swal.fire({
                    title: `¿Anular ${nombreVenta}?`,
                    text: "Esta acción restaurará el stock de los productos y no se puede deshacer.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, anular',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitCsrfForm('<?= $URL; ?>controllers/ventas/anular_venta.php', {
                            id: ventaId
                        });
                    }
                });
            });
        }
    });
</script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>