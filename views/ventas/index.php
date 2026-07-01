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

// Incluir el encabezado DESPUÉS de verificar permisos
include_once '../layouts/header.php';

$controller = new VentaController();
$ventas = $controller->index();
$estadisticas = $controller->getEstadisticas();
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
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
                        <span class="info-box-number"><?= number_format($estadisticas['total_hoy'], 2); ?> Bs.</span>
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

        <!-- Segunda fila de estadísticas para métodos de pago -->
        <div class="row">
            <?php if (!empty($estadisticas['metodos_pago'])): ?>
                <?php foreach ($estadisticas['metodos_pago'] as $metodoPago): ?>
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-primary elevation-1">
                                <?php
                                $iconoMetodo = 'fas fa-money-bill-alt';
                                switch ($metodoPago['metodopago']) {
                                    case 'efectivo':
                                        $iconoMetodo = 'fas fa-money-bill-wave';
                                        break;
                                    case 'tarjeta':
                                        $iconoMetodo = 'far fa-credit-card';
                                        break;
                                    case 'qr':
                                        $iconoMetodo = 'fas fa-qrcode';
                                        break;
                                    case 'transferencia':
                                        $iconoMetodo = 'fas fa-exchange-alt';
                                        break;
                                }
                                ?>
                                <i class="<?= $iconoMetodo; ?>"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">
                                    <?= ucfirst($metodoPago['metodopago']); ?>
                                </span>
                                <span class="info-box-number">
                                    <?= number_format($metodoPago['total'], 2); ?> Bs.
                                </span>
                                <span class="text-muted">
                                    <?= $metodoPago['cantidad']; ?> transacciones
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="icon fas fa-info-circle"></i> No hay información de métodos de pago para hoy.
                    </div>
                </div>
            <?php endif; ?>
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
                                        // Verificar si esta venta tiene pagos mixtos
                                        $tienePagosMixtos = $controller->tienePagosMixtos($venta['idventa']);

                                        // Obtener la lista de pagos (independientemente de si son mixtos o no)
                                        $metodosPago = $controller->obtenerMetodosPago($venta['idventa']);

                                        // Determinar texto y clase de badge para método de pago
                                        $texto_metodo = '';
                                        $clase_badge = '';

                                        if ($tienePagosMixtos) {
                                            $texto_metodo = 'Pago Mixto';
                                            $clase_badge = 'badge-purple';
                                        } else {
                                            // Obtener el método de pago principal y asegurarse de que sea en minúsculas y sin espacios
                                            $metodo_pago = strtolower(trim($metodosPago[0]['metodopago'] ?? 'efectivo'));

                                            switch ($metodo_pago) {
                                                case 'efectivo':
                                                    $clase_badge = 'badge-success';
                                                    $texto_metodo = 'Efectivo';
                                                    break;
                                                case 'qr':
                                                    $clase_badge = 'badge-info';
                                                    $texto_metodo = 'QR';
                                                    break;
                                                case 'tarjeta':
                                                    $clase_badge = 'badge-primary';
                                                    $texto_metodo = 'Tarjeta';
                                                    break;
                                                case 'transferencia':
                                                    $clase_badge = 'badge-secondary';
                                                    $texto_metodo = 'Transferencia';
                                                    break;
                                                default:
                                                    $clase_badge = 'badge-secondary';
                                                    $texto_metodo = ucfirst($metodo_pago);
                                                    // Añadir log para métodos desconocidos
                                                    error_log("Método de pago desconocido: '$metodo_pago'");
                                            }
                                        }
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
                                            <td class="text-right"><?= number_format($venta['totalventa'], 2); ?> Bs.</td>
                                            <td class="text-center">
                                                <?php if ($tienePagosMixtos): ?>
                                                    <span class="badge <?= $clase_badge; ?>"
                                                        data-toggle="tooltip"
                                                        data-html="true"
                                                        title="<?php
                                                                $tooltipContent = '';
                                                                foreach ($metodosPago as $pago) {
                                                                    $tooltipContent .= ucfirst($pago['metodopago']) . ': ' . number_format($pago['monto'], 2) . ' Bs.<br>';
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
                                                    <a href="<?= $URL; ?>views/ventas/show.php?id=<?= $venta['idventa']; ?>" class="btn btn-info btn-sm" data-toggle="tooltip" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>

                                                    <?php if ($venta['estado'] == 1) : ?>
                                                        <button type="button" class="btn btn-danger btn-sm btn-anular-venta"
                                                            data-id="<?= $venta['idventa']; ?>"
                                                            data-titulo="VENT-<?= str_pad($venta['idventa'], 6, '0', STR_PAD_LEFT); ?>"
                                                            data-toggle="tooltip" title="Anular venta">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    <?php endif; ?>

                                                    <a href="<?= $URL; ?>views/ventas/recibo.php?id=<?= $venta['idventa']; ?>"
                                                        class="btn btn-secondary btn-sm" target="_blank" title="Imprimir comprobante">
                                                        <i class="fas fa-print"></i>
                                                    </a>
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

<!-- Estilos personalizados para el módulo de ventas -->
<style>
    .badge-purple {
        background-color: #9c27b0;
        color: white;
    }

    .tooltip-inner {
        max-width: 250px;
        text-align: left;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const botonesAnular = document.querySelectorAll('.btn-anular-venta');
        const botonesImprimir = document.querySelectorAll('.btn-imprimir-ticket');
        const btnImprimir = document.getElementById('btn-imprimir');

        // Inicializar tooltips
        $('[data-toggle="tooltip"]').tooltip();

        // Botones de anulación
        botonesAnular.forEach(boton => {
            boton.addEventListener('click', function() {
                const ventaId = this.dataset.id;
                const tituloVenta = this.dataset.titulo;

                Swal.fire({
                    title: `¿Anular venta ${tituloVenta}?`,
                    text: 'La venta será anulada y el stock de productos será revertido. Esta acción no se puede deshacer.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, anular',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `<?= $URL; ?>controllers/ventas/anular_venta.php?id=${ventaId}`;
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

<script src="<?= $URL; ?>public/js/modules/ventas/index-ventas.js"></script>