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

// Los usuarios no administradores solo pueden ver sus propias compras
if (!$authService->esAdministrador($idusuario) && (int)$compra['idusuario'] !== (int)$idusuario) {
    $_SESSION['mensaje'] = 'No tiene permisos para ver esta compra.';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

$skip_datatables = true; // Evita cargar DataTables/pdfmake/vfs_fonts (~2.8MB)
$skip_select2 = true;
$module_scripts = ['compras/show-compra'];
include_once '../layouts/header.php';

$estadoInfo = $controller->obtenerInfoEstado($compra['estado']);
$estado_clase = $estadoInfo['clase'];
$estado_texto = $estadoInfo['texto'];
$estado_icono = $estadoInfo['icono'];

$totales = $controller->calcularTotales($compra);
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Detalle de Compra #<?= str_pad($compra['idcompra'], 6, '0', STR_PAD_LEFT); ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/compras"><i class="fas fa-shopping-cart"></i> Compras</a></li>
                    <li class="breadcrumb-item active">Detalle</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <!-- Columna izquierda: contexto de la compra (quién, cuándo, estado) y acciones -->
            <div class="col-lg-4 mb-3">
                <div class="sidebar-sticky">
                    <!-- Tarjeta de información general -->
                    <div class="card card-info card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Información General</h3>
                        </div>
                        <div class="card-body box-profile">
                            <div class="text-center mb-3">
                                <i class="fas fa-receipt fa-3x text-info"></i>
                                <h3 class="mt-2 mb-0 text-info font-weight-bold">
                                    <?= number_format($compra['totalcompra'], 2); ?> <?= $appCurrency ?>
                                </h3>
                                <p class="text-muted mb-0">Total Compra</p>
                            </div>

                            <ul class="list-group list-group-unbordered mb-3">
                                <li class="list-group-item">
                                    <b><i class="fas fa-hashtag mr-2"></i>Código</b>
                                    <span class="float-right">COMP-<?= str_pad($compra['idcompra'], 6, '0', STR_PAD_LEFT); ?></span>
                                </li>
                                <li class="list-group-item">
                                    <b><i class="far fa-calendar-alt mr-2"></i>Fecha Compra</b>
                                    <span class="float-right"><?= date('d/m/Y H:i', strtotime($compra['fechacompra'])); ?></span>
                                </li>
                                <li class="list-group-item">
                                    <b><i class="fas fa-user-tag mr-2"></i>Responsable</b>
                                    <span class="float-right"><?= $compra['usuario_nombre'] ?? 'N/A'; ?></span>
                                </li>
                                <li class="list-group-item">
                                    <b><i class="fas fa-toggle-on mr-2"></i>Estado</b>
                                    <span class="float-right">
                                        <span class="badge badge-<?= $estado_clase; ?>">
                                            <i class="fas fa-<?= $estado_icono; ?> mr-1"></i> <?= $estado_texto ?>
                                        </span>
                                    </span>
                                </li>
                            </ul>

                            <div class="d-flex justify-content-between">
                                <a href="<?= $URL; ?>views/compras/index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Volver
                                </a>

                                <?php if ($compra['estado'] == 1): ?>
                                    <button class="btn btn-danger btn-cancelar" data-id="<?= $compra['idcompra']; ?>">
                                        <i class="fas fa-ban"></i> Cancelar Compra
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <!-- Tarjeta de detalles de productos -->
                <div class="card card-info card-outline">
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
                                        <?php $calculo = $controller->calcularDetalleLinea($detalle); ?>
                                        <tr>
                                            <td><?= $index + 1; ?></td>
                                            <td><?= $detalle['producto_nombre']; ?></td>
                                            <td><?= $detalle['producto_codigo'] ?? 'N/A'; ?></td>
                                            <td class="text-right"><?= $detalle['cantidad']; ?></td>
                                            <td class="text-right"><?= number_format($detalle['preciocompra'], 2); ?> <?= $appCurrency ?></td>
                                            <td class="text-right"><?= number_format($calculo['subtotal'], 2); ?> <?= $appCurrency ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="table-active">
                                        <td colspan="5" class="text-right"><strong><i class="fas fa-file-invoice-dollar mr-1"></i> Total:</strong></td>
                                        <td class="text-right"><strong><?= number_format($totales['total'], 2); ?> <?= $appCurrency ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de información adicional -->
                <?php if (!empty($compra['observaciones']) || $compra['estado'] == 0) : ?>
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
                            <?php if (!empty($compra['observaciones'])) : ?>
                                <div class="form-group mb-0">
                                    <label>Observaciones:</label>
                                    <div class="p-2 bg-light rounded">
                                        <?= nl2br($compra['observaciones']); ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($compra['estado'] == 0) : ?>
                                <div class="alert alert-default-warning <?= !empty($compra['observaciones']) ? 'mt-3' : '' ?> mb-0">
                                    <i class="icon fas fa-info-circle"></i>
                                    Esta compra fue cancelada el <?= date('d/m/Y H:i', strtotime($compra['fechaactualizacion'])); ?>.
                                    El stock de los productos fue revertido.
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
