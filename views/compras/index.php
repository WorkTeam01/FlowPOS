<?php
require_once __DIR__ . '/../../controllers/compras/CompraController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'] ?? '';

$authService = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!($authService->tienePermisoNombre($idusuario, 'compras')) && !($authService->esAdministrador($idusuario))) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL);
    exit;
}

$skip_select2 = true;
$module_scripts = ['compras/index-compras'];
$module_styles = ['compras/compras'];
include_once '../layouts/header.php';

$esAdmin = $authService->esAdministrador($idusuario);

$controller = new CompraController();
$compras = $controller->index($esAdmin ? null : $idusuario);
$estadisticas = $controller->getEstadisticas();
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Gestión de Compras</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Compras</li>
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
                        <span class="info-box-text">Compras Hoy</span>
                        <span class="info-box-number"><?= $estadisticas['compras_hoy']; ?></span>
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
                        <span class="info-box-text">Responsable Top</span>
                        <span class="info-box-number">
                            <?= $estadisticas['usuario_mas_compro'] ? $estadisticas['usuario_mas_compro']['nombre_usuario'] : 'N/A'; ?>
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
                            <?= $estadisticas['producto_mas_comprado'] ? $estadisticas['producto_mas_comprado']['producto_nombre'] : 'N/A'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header card-outline card-primary">
                        <h3 class="card-title">Historial de Compras</h3>
                        <div class="card-tools">
                            <a href="<?= $URL; ?>views/compras/create.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Nueva Compra
                            </a>
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer panel">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <table id="tablaCompras" class="table table-sm table-bordered table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>Nro</th>
                                    <th>Código</th>
                                    <th>Fecha</th>
                                    <th>Usuario</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $contador = 1;
                                foreach ($compras as $compra) :
                                    $estado_actual = $compra['estado'];
                                    $estadoInfo = $controller->obtenerInfoEstado($estado_actual);
                                ?>
                                    <tr>
                                        <td class="text-center"><?= $contador++; ?></td>
                                        <td>COMP-<?= str_pad($compra['idcompra'], 6, '0', STR_PAD_LEFT); ?></td>
                                        <td><?= date('d/m/Y', strtotime($compra['fechacompra'])); ?></td>
                                        <td><?= $compra['usuario_nombre'] ?? 'N/A'; ?></td>
                                        <td class="text-right"><?= number_format($compra['totalcompra'], 2); ?> <?= $appCurrency ?></td>
                                        <td class="text-center">
                                            <span class="badge badge-<?= $estadoInfo['clase']; ?>"><?= $estadoInfo['texto']; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a href="<?= $URL; ?>views/compras/show.php?id=<?= $compra['idcompra']; ?>" class="btn btn-info btn-sm" data-toggle="tooltip" title="Ver detalles" aria-label="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>

                                                <?php if ($estado_actual == 1): ?>
                                                    <button type="button" class="btn btn-danger btn-sm btn-cambiar-estado"
                                                        data-id="<?= $compra['idcompra']; ?>"
                                                        data-accion="cancelar"
                                                        data-titulo="COMP-<?= str_pad($compra['idcompra'], 6, '0', STR_PAD_LEFT); ?>"
                                                        data-toggle="tooltip" title="Cancelar compra" aria-label="Cancelar compra">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
