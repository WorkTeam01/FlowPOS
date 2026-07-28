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

// Los usuarios no administradores solo pueden ver sus propias ventas
if (!$authService->esAdministrador($idusuario) && (int)$venta['idusuario'] !== (int)$idusuario) {
    $_SESSION['mensaje'] = 'No tiene permisos para ver esta venta.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . 'views/ventas');
    exit;
}

// Determinar si es pago mixto
$esPagoMixto = count($venta['pagos'] ?? []) > 1;

$skip_datatables = true; // Evita cargar DataTables/pdfmake/vfs_fonts (~2.8MB)
$skip_select2 = true;
$module_scripts = ['ventas/show-venta'];
include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
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
        <?php
        $totales = $controller->calcularTotales($venta);
        $subtotalGeneral = $totales['subtotal'];
        $descuentoGeneral = $totales['descuento'];
        $totalPagado = $totales['total_pagado'];
        ?>
        <div class="row">
            <!-- Columna izquierda: contexto de la venta (quién, cuándo, cómo se pagó) y acciones -->
            <div class="col-lg-4 mb-3">
                <div class="sidebar-sticky">
                    <!-- Tarjeta de información general -->
                    <div class="card card-info card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Información General</h3>
                        </div>
                        <div class="card-body box-profile">
                            <div class="text-center mb-3">
                                <i class="fas fa-file-invoice-dollar fa-3x text-info"></i>
                                <h3 class="mt-2 mb-0 text-info font-weight-bold">
                                    <?= number_format($venta['totalventa'], 2); ?> <?= $appCurrency ?>
                                </h3>
                                <p class="text-muted mb-0">Total Venta</p>
                            </div>

                            <ul class="list-group list-group-unbordered mb-3">
                                <li class="list-group-item">
                                    <b><i class="fas fa-hashtag mr-2"></i>Código</b>
                                    <span class="float-right">VENT-<?= str_pad($venta['idventa'], 6, '0', STR_PAD_LEFT); ?></span>
                                </li>
                                <li class="list-group-item">
                                    <b><i class="fas fa-calendar-alt mr-2"></i>Fecha Venta</b>
                                    <span class="float-right"><?= date('d/m/Y H:i', strtotime($venta['fechacreacion'])); ?></span>
                                </li>
                                <li class="list-group-item">
                                    <b><i class="fas fa-user mr-2"></i>Cliente</b>
                                    <span class="float-right"><?= htmlspecialchars($venta['cliente_nombre'] ?? 'Consumidor Final'); ?></span>
                                </li>
                                <li class="list-group-item">
                                    <b><i class="fas fa-user-tie mr-2"></i>Vendedor</b>
                                    <span class="float-right"><?= htmlspecialchars($venta['usuario_nombre'] ?? 'Usuario no registrado'); ?></span>
                                </li>
                                <li class="list-group-item">
                                    <b><i class="fas fa-toggle-on mr-2"></i>Estado</b>
                                    <span class="float-right">
                                        <?php if ($venta['estado'] == 1) : ?>
                                            <span class="badge badge-success">Activa</span>
                                        <?php else : ?>
                                            <span class="badge badge-danger">Anulada</span>
                                        <?php endif; ?>
                                    </span>
                                </li>
                            </ul>

                            <div class="d-flex justify-content-between">
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
                    <div class="card card-outline <?= $esPagoMixto ? 'card-purple' : 'card-info' ?>">
                        <div class="card-header">
                            <h3 class="card-title">
                                <?php if ($esPagoMixto) : ?>
                                    <i class="fas fa-money-check-alt"></i> Pago Mixto
                                <?php else : ?>
                                    <i class="fas fa-money-bill-wave"></i> Método de Pago
                                <?php endif; ?>
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php foreach ($venta['pagos'] as $pago) :
                                [$iconoMetodo, $claseMetodo] = $controller->obtenerIconoMetodoPago($pago['metodopago']);
                            ?>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge <?= $claseMetodo ?>">
                                        <i class="<?= $iconoMetodo ?>"></i> <?= ucfirst($pago['metodopago']) ?>
                                    </span>
                                    <span><?= number_format($pago['monto'], 2) ?> <?= $appCurrency ?></span>
                                </div>
                                <?php if ($pago['metodopago'] === 'efectivo' && $pago['cambio'] > 0) : ?>
                                    <div class="d-flex justify-content-between text-muted small mb-2">
                                        <span>Recibido <?= number_format($pago['pagorecibido'], 2) ?> <?= $appCurrency ?></span>
                                        <span>Cambio <?= number_format($pago['cambio'], 2) ?> <?= $appCurrency ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if ($esPagoMixto) : ?>
                                <div class="d-flex justify-content-between font-weight-bold border-top pt-2 mt-1 mb-0">
                                    <span>Total Pagado</span>
                                    <span><?= number_format($totalPagado, 2) ?> <?= $appCurrency ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna derecha: qué se vendió -->
            <div class="col-lg-8">
                <!-- Tarjeta de detalles de productos -->
                <div class="card card-info card-outline">
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
                            <table class="table table-hover table-striped mb-0">
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
                                    foreach ($venta['detalles'] as $detalle) :
                                        $precioUnitario = $detalle['precioventa'];
                                        $cantidad = $detalle['cantidad'];
                                        $descuento = $detalle['descuento'];
                                        $subtotal = ($precioUnitario * $cantidad) - $descuento;
                                    ?>
                                        <tr>
                                            <td><?= $contador++; ?></td>
                                            <td>
                                                <?= htmlspecialchars($detalle['producto_nombre']); ?>
                                                <small class="text-muted d-block">
                                                    Código: <?= htmlspecialchars($detalle['producto_codigo'] ?? 'N/A'); ?>
                                                </small>
                                            </td>
                                            <td class="text-right"><?= number_format($precioUnitario, 2); ?> <?= $appCurrency ?></td>
                                            <td class="text-center"><?= $cantidad; ?></td>
                                            <td class="text-right">
                                                <?php if ($descuento > 0) : ?>
                                                    <span class="text-danger">-<?= number_format($descuento, 2); ?> <?= $appCurrency ?></span>
                                                <?php else : ?>
                                                    0.00 <?= $appCurrency ?>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-right"><?= number_format($subtotal, 2); ?> <?= $appCurrency ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="4" class="text-right">Subtotal:</th>
                                        <th class="text-right"><?= number_format($subtotalGeneral + $descuentoGeneral, 2); ?> <?= $appCurrency ?></th>
                                        <th></th>
                                    </tr>
                                    <?php if ($descuentoGeneral > 0) : ?>
                                        <tr>
                                            <th colspan="4" class="text-right">Descuento Total:</th>
                                            <th class="text-right text-danger">-<?= number_format($descuentoGeneral, 2); ?> <?= $appCurrency ?></th>
                                            <th></th>
                                        </tr>
                                    <?php endif; ?>
                                    <tr class="bg-light">
                                        <th colspan="5" class="text-right">Total:</th>
                                        <th class="text-right"><?= number_format($venta['totalventa'], 2); ?> <?= $appCurrency ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de información adicional -->
                <?php if (!empty($venta['observacion']) || $venta['estado'] == 0) : ?>
                    <div class="card card-info card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Información Adicional</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($venta['observacion'])) : ?>
                                <div class="form-group mb-0">
                                    <label>Observaciones:</label>
                                    <div class="p-2 bg-light rounded">
                                        <?= nl2br($venta['observacion']); ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($venta['estado'] == 0) : ?>
                                <div class="alert alert-warning <?= !empty($venta['observacion']) ? 'mt-3' : '' ?> mb-0">
                                    <i class="icon fas fa-info-circle"></i>
                                    Esta venta fue anulada el <?= date('d/m/Y H:i', strtotime($venta['fechaactualizacion'])); ?>.
                                    El stock de los productos fue restaurado.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>