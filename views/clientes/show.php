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

$compras = $controller->getHistorialCompras($id);
$comprasValidas = array_values(array_filter($compras, fn($v) => $v['estado'] == 1));
$totalCompras = count($comprasValidas);
$montoTotal = array_sum(array_column($comprasValidas, 'totalventa'));
$ultimaCompra = !empty($compras) ? $compras[0]['fechacreacion'] : null;

$skip_datatables = true; // Evita cargar DataTables/pdfmake/vfs_fonts (~2.8MB)
$skip_select2 = true;
$module_scripts = ['clientes/show-cliente'];
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
            <!-- Columna izquierda - Perfil y acciones (4 columnas) -->
            <div class="col-md-4">
                <div class="card card-info card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center">
                            <i class="fas fa-user-circle fa-5x text-info mb-3"></i>
                        </div>

                        <h3 class="profile-username text-center">
                            <?= $cliente['nombres'] . ' ' . $cliente['apellidopaterno']; ?>
                        </h3>

                        <p class="text-muted text-center">Cliente desde <?= date('d/m/Y', strtotime($cliente['fechacreacion'])); ?></p>

                        <ul class="list-group list-group-unbordered mb-4">
                            <li class="list-group-item">
                                <b><i class="fas fa-id-card mr-1"></i> <?= $cliente['tipodocumento']; ?></b>
                                <span class="float-right"><?= $cliente['numdocumento']; ?></span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-phone mr-1"></i> Celular</b>
                                <span class="float-right"><?= $cliente['celular'] ?? 'No registrado'; ?></span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-envelope mr-1"></i> Email</b>
                                <span class="float-right text-truncate" style="max-width: 150px;" title="<?= $cliente['email'] ?? 'No registrado'; ?>">
                                    <?= $cliente['email'] ?? 'No registrado'; ?>
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

                        <a href="<?= $URL; ?>views/clientes/update.php?id=<?= $cliente['idcliente']; ?>" class="btn btn-warning btn-block">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <a href="<?= $URL; ?>views/ventas/create.php?cliente=<?= $cliente['idcliente']; ?>" class="btn btn-primary btn-block">
                            <i class="fas fa-shopping-cart"></i> Nueva Venta
                        </a>
                        <a href="<?= $URL; ?>views/clientes" class="btn btn-secondary btn-block">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                        <button type="button" class="btn btn-block btn-cambiar-estado <?= $cliente['estado'] == 1 ? 'btn-danger' : 'btn-success'; ?>"
                            data-id="<?= $cliente['idcliente']; ?>"
                            data-estado="<?= $cliente['estado']; ?>"
                            data-nombre="<?= $cliente['nombres'] . ' ' . $cliente['apellidopaterno']; ?>">
                            <i class="fas <?= $cliente['estado'] == 1 ? 'fa-user-slash' : 'fa-user-check'; ?> mr-2"></i>
                            <?= $cliente['estado'] == 1 ? 'Desactivar Cliente' : 'Activar Cliente'; ?>
                        </button>
                    </div>
                </div>
            </div>
            <!-- /.col-md-4 -->

            <!-- Columna derecha - Información detallada en tabs (8 columnas) -->
            <div class="col-md-8">
                <div class="card card-info card-outline card-outline-tabs">
                    <div class="card-header p-0 border-bottom-0">
                        <ul class="nav nav-tabs" id="detail-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="tab-general" data-toggle="pill" href="#general" role="tab"
                                    aria-controls="general" aria-selected="true">
                                    <i class="fas fa-info-circle mr-1"></i> Información General
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="tab-compras" data-toggle="pill" href="#compras" role="tab"
                                    aria-controls="compras" aria-selected="false">
                                    <i class="fas fa-shopping-bag mr-1"></i> Historial de Compras
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="detail-tabs-content">
                            <!-- Tab Información General -->
                            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="tab-general">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header bg-light">
                                                <h3 class="card-title">Datos Personales</h3>
                                            </div>
                                            <div class="card-body p-0">
                                                <table class="table table-hover">
                                                    <tbody>
                                                        <tr>
                                                            <td><i class="fas fa-signature mr-2"></i>Nombre Completo:</td>
                                                            <td><?= $cliente['nombres'] . ' ' . $cliente['apellidopaterno'] . ' ' . ($cliente['apellidomaterno'] ?? ''); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td><i class="fas fa-venus-mars mr-2"></i>Género:</td>
                                                            <td><?= !empty($cliente['genero']) ? $cliente['genero'] : '<span class="text-muted">No especificado</span>'; ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td><i class="fas fa-id-card mr-2"></i>Documento:</td>
                                                            <td><?= $cliente['tipodocumento']; ?> <?= $cliente['numdocumento']; ?></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header bg-light">
                                                <h3 class="card-title">Contacto</h3>
                                            </div>
                                            <div class="card-body p-0">
                                                <table class="table table-hover">
                                                    <tbody>
                                                        <tr>
                                                            <td><i class="fas fa-map-marker-alt mr-2"></i>Dirección:</td>
                                                            <td><?= !empty($cliente['direccion']) ? $cliente['direccion'] : '<span class="text-muted">No registrada</span>'; ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td><i class="fas fa-mobile-alt mr-2"></i>Celular:</td>
                                                            <td>
                                                                <?php if (!empty($cliente['celular'])): ?>
                                                                    <a href="tel:<?= $cliente['celular']; ?>"><?= $cliente['celular']; ?></a>
                                                                <?php else: ?>
                                                                    <span class="text-muted">No registrado</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td><i class="fas fa-envelope mr-2"></i>Email:</td>
                                                            <td>
                                                                <?php if (!empty($cliente['email'])): ?>
                                                                    <a href="mailto:<?= $cliente['email']; ?>"><?= $cliente['email']; ?></a>
                                                                <?php else: ?>
                                                                    <span class="text-muted">No registrado</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header bg-light">
                                                <h3 class="card-title">Información del Sistema</h3>
                                            </div>
                                            <div class="card-body p-0">
                                                <table class="table table-hover">
                                                    <tbody>
                                                        <tr>
                                                            <td><i class="fas fa-calendar-plus mr-2"></i>Fecha Registro:</td>
                                                            <td><?= date('d/m/Y H:i', strtotime($cliente['fechacreacion'])); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td><i class="fas fa-calendar-check mr-2"></i>Última Actualización:</td>
                                                            <td><?= !empty($cliente['fechaactualizacion']) ? date('d/m/Y H:i', strtotime($cliente['fechaactualizacion'])) : 'Sin actualizaciones'; ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td><i class="fas fa-toggle-on mr-2"></i>Estado:</td>
                                                            <td>
                                                                <?php if ($cliente['estado'] == 1): ?>
                                                                    <span class="badge badge-success">Activo</span>
                                                                <?php else: ?>
                                                                    <span class="badge badge-danger">Inactivo</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab Historial de Compras -->
                            <div class="tab-pane fade" id="compras" role="tabpanel" aria-labelledby="tab-compras">
                                <div class="row">
                                    <div class="col-lg-4 col-md-6">
                                        <div class="small-box bg-info">
                                            <div class="inner">
                                                <h3><?= $totalCompras; ?></h3>
                                                <p>Compras Realizadas</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-shopping-bag"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <div class="small-box bg-success">
                                            <div class="inner">
                                                <h3><?= $appCurrency; ?> <?= number_format($montoTotal, 2); ?></h3>
                                                <p>Monto Total Comprado</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <div class="small-box bg-warning">
                                            <div class="inner">
                                                <h3><?= $ultimaCompra ? date('d/m/Y', strtotime($ultimaCompra)) : 'N/A'; ?></h3>
                                                <p>Última Compra</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-calendar-day"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Ventas Registradas</h3>
                                    </div>
                                    <div class="card-body p-0">
                                        <?php if (!empty($compras)): ?>
                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>Fecha</th>
                                                            <th>Total</th>
                                                            <th>Estado</th>
                                                            <th>Atendido por</th>
                                                            <th></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($compras as $venta): ?>
                                                            <tr>
                                                                <td><?= date('d/m/Y H:i', strtotime($venta['fechacreacion'])); ?></td>
                                                                <td><?= $appCurrency; ?> <?= number_format($venta['totalventa'], 2); ?></td>
                                                                <td>
                                                                    <?php if ($venta['estado'] == 1): ?>
                                                                        <span class="badge badge-success">Completada</span>
                                                                    <?php else: ?>
                                                                        <span class="badge badge-danger">Anulada</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td><?= $venta['usuario_nombre']; ?></td>
                                                                <td class="text-center">
                                                                    <a href="<?= $URL; ?>views/ventas/show.php?id=<?= $venta['idventa']; ?>" class="btn btn-info btn-sm" data-toggle="tooltip" title="Ver venta">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted p-3 mb-0">Este cliente aún no tiene compras registradas.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.col-md-8 -->
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