<?php
require_once __DIR__ . '/../../controllers/ventas/VentaController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'] ?? '';

$authService = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!($authService->tienePermisoNombre($idusuario, 'ventas')) && !($authService->esAdministrador($idusuario))) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL);
    exit;
}

$skip_select2 = true;
$module_scripts = ['ventas/index-ventas'];
$module_styles = ['ventas/ventas'];
include_once '../layouts/header.php';

$esAdmin = $authService->esAdministrador($idusuario);

$controller = new VentaController();
$ventas = $controller->index($esAdmin ? null : $idusuario);
$estadisticas = $controller->getEstadisticas();
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Gestión de Ventas</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Ventas</li>
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
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-shopping-cart"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Ventas Hoy</span>
                        <span class="info-box-number"><?= $estadisticas['ventas_hoy']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-money-bill-wave"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Hoy</span>
                        <span class="info-box-number"><?= number_format($estadisticas['total_hoy'], 2); ?> <?= $appCurrency ?></span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-user-tie"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Cliente Top</span>
                        <span class="info-box-number">
                            <?= $estadisticas['cliente_mas_compro'] ? htmlspecialchars($estadisticas['cliente_mas_compro']['nombre_cliente']) : 'N/A'; ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-box-open"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Producto Top</span>
                        <span class="info-box-number">
                            <?= $estadisticas['producto_mas_vendido'] ? htmlspecialchars($estadisticas['producto_mas_vendido']['producto_nombre']) : 'N/A'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header card-outline card-primary">
                        <h3 class="card-title">Historial de Ventas</h3>
                        <div class="card-tools">
                            <a href="<?= $URL; ?>views/ventas/create.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Nueva Venta
                            </a>
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tablaVentas" class="table table-sm table-bordered table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Nro</th>
                                        <th>Código</th>
                                        <th>Fecha</th>
                                        <th>Cliente</th>
                                        <th>Usuario</th>
                                        <th>Total</th>
                                        <th>Método Pago</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador = 1;
                                    foreach ($ventas as $venta) :
                                        // Info del método de pago ya calculada por el controlador (evita N+1 por fila)
                                        $infoMetodo = $venta['info_metodo_pago'];
                                        $tienePagosMixtos = $infoMetodo['es_mixto'];
                                        $metodosPago = $venta['pagos'];
                                        $texto_metodo = $infoMetodo['texto'];
                                        $clase_badge = $infoMetodo['clase'];

                                        // Estado de la venta
                                        $estado = $venta['estado'];
                                        $clase_estado = $estado ? 'badge-success' : 'badge-danger';
                                        $texto_estado = $estado ? 'Activa' : 'Anulada';
                                    ?>
                                        <tr>
                                            <td class="text-center"><?= $contador++; ?></td>
                                            <td>VENT-<?= str_pad($venta['idventa'], 6, '0', STR_PAD_LEFT); ?></td>
                                            <td><?= date('d/m/Y', strtotime($venta['fechacreacion'])); ?></td>
                                            <td><?= htmlspecialchars($venta['cliente_nombre'] ?? 'Consumidor Final'); ?></td>
                                            <td><?= htmlspecialchars($venta['usuario_nombre'] ?? 'N/A'); ?></td>
                                            <td class="text-right"><?= number_format($venta['totalventa'], 2); ?> <?= $appCurrency ?></td>
                                            <td class="text-center">
                                                <?php if ($tienePagosMixtos): ?>
                                                    <span class="badge <?= $clase_badge; ?>"
                                                        data-toggle="tooltip"
                                                        data-html="true"
                                                        title="<?php
                                                                $tooltipContent = '';
                                                                foreach ($metodosPago as $pago) {
                                                                    $tooltipContent .= ucfirst($pago['metodopago']) . ': ' . number_format($pago['monto'], 2) . ' ' . $appCurrency . '<br>';
                                                                }
                                                                echo htmlspecialchars($tooltipContent);
                                                                ?>">
                                                        <?= $texto_metodo; ?> <i class="fas fa-info-circle"></i>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge <?= $clase_badge; ?>">
                                                        <?= $texto_metodo; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?= $clase_estado; ?>"><?= $texto_estado; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="<?= $URL; ?>views/ventas/show.php?id=<?= $venta['idventa']; ?>" class="btn btn-info btn-sm" data-toggle="tooltip" title="Ver detalles" aria-label="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>

                                                    <?php if ($venta['estado'] == 1) : ?>
                                                        <button type="button" class="btn btn-danger btn-sm btn-anular-venta"
                                                            data-id="<?= $venta['idventa']; ?>"
                                                            data-titulo="VENT-<?= str_pad($venta['idventa'], 6, '0', STR_PAD_LEFT); ?>"
                                                            data-toggle="tooltip" title="Anular venta" aria-label="Anular venta">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    <?php endif; ?>

                                                    <?php if ($venta['estado'] == 1) : ?>
                                                        <a href="<?= $URL; ?>views/ventas/recibo.php?id=<?= $venta['idventa']; ?>"
                                                            class="btn btn-secondary btn-sm" target="_blank" data-toggle="tooltip" title="Imprimir comprobante" aria-label="Imprimir comprobante">
                                                            <i class="fas fa-print"></i>
                                                        </a>
                                                    <?php else : ?>
                                                        <button type="button" class="btn btn-secondary btn-sm" disabled
                                                            data-toggle="tooltip" title="No disponible para ventas anuladas" aria-label="Imprimir comprobante no disponible para ventas anuladas">
                                                            <i class="fas fa-print"></i>
                                                        </button>
                                                    <?php endif; ?>
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

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>