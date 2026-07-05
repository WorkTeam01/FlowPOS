<?php
require_once __DIR__ . '/../../controllers/productos/ProductoController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!($authService->tienePermisoNombre($idusuario, 'productos')) && !($authService->esAdministrador($idusuario))) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL);
    exit;
}

// Incluir el encabezado
include_once '../layouts/header.php';
// Verificar si se proporcionó un ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    $_SESSION['mensaje'] = 'ID de producto no válido';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Instanciar el controlador y obtener los datos del producto
$controller = new ProductoController();
$producto = $controller->editar($id);

// Verificar si el producto existe
if (!$producto) {
    $_SESSION['mensaje'] = 'Producto no encontrado';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}
?>

<style>
    /* Color info para el texto de las pestañas no activas */
    #detail-tabs .nav-link:not(.active) {
        color: #17a2b8;
        /* Color info */
    }

    /* Opcional: Color info más intenso al pasar el mouse por pestañas no activas */
    #detail-tabs .nav-link:not(.active):hover {
        color: #138496;
        /* Un tono más oscuro de info */
    }
</style>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Detalle de Producto</h1>
                <p class="text-muted">Información completa del producto seleccionado</p>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/productos"><i class="fas fa-boxes"></i> Productos</a></li>
                    <li class="breadcrumb-item active">Detalle de Producto</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <!-- Columna izquierda - Imagen y datos básicos -->
            <div class="col-md-4">
                <!-- Tarjeta de imagen y datos básicos -->
                <div class="card card-info card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center position-relative mb-4">
                            <?php if (isset($producto['imagen']) && !empty($producto['imagen'])): ?>
                                <img class="img-fluid rounded"
                                    src="<?= $URL; ?>public/uploads/productos/<?= $producto['imagen']; ?>"
                                    alt="Imagen del producto" style="max-height: 250px;">
                            <?php else: ?>
                                <img class="img-fluid rounded"
                                    src="<?= $URL; ?>public/uploads/productos/producto_default.png"
                                    alt="Imagen por defecto" style="max-height: 250px;">
                            <?php endif; ?>

                            <!-- Indicador de estado sobre la imagen -->
                            <span class="position-absolute badge <?= $producto['estado'] == 1 ? 'badge-success' : 'badge-danger'; ?>"
                                style="top: 0; right: 0;">
                                <i class="fas <?= $producto['estado'] == 1 ? 'fa-check' : 'fa-times'; ?>"></i>
                                <?= $producto['estado'] == 1 ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </div>

                        <h3 class="profile-username text-center">
                            <?= htmlspecialchars($producto['nombre']); ?>
                        </h3>

                        <p class="text-muted text-center">
                            <?= htmlspecialchars($producto['categoria_nombre'] ?? 'Sin categoría'); ?>
                        </p>

                        <ul class="list-group list-group-unbordered mb-4">
                            <li class="list-group-item">
                                <b><i class="fas fa-barcode mr-2"></i>Código</b>
                                <span class="float-right">
                                    <?= !empty($producto['codigo']) ? htmlspecialchars($producto['codigo']) : 'N/A'; ?>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-tag mr-2"></i>Precio Venta</b>
                                <span class="float-right">
                                    <span class="badge badge-success">
                                        Bs <?= number_format($producto['precioventa'], 2); ?>
                                    </span>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-shopping-basket mr-2"></i>Stock Actual</b>
                                <span class="float-right <?= $producto['stock'] < $producto['stockminimo'] ? 'text-danger font-weight-bold' : ''; ?>">
                                    <?= $producto['stock']; ?> unidades
                                    <?php if ($producto['stock'] < $producto['stockminimo']): ?>
                                        <i class="fas fa-exclamation-triangle ml-1" title="Stock bajo"></i>
                                    <?php endif; ?>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-toggle-on mr-2"></i>Estado</b>
                                <span class="float-right">
                                    <?php if ($producto['estado'] == 1): ?>
                                        <span class="badge badge-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactivo</span>
                                    <?php endif; ?>
                                </span>
                            </li>
                        </ul>

                        <div class="d-flex justify-content-between">
                            <a href="<?= $URL; ?>views/productos/update.php?id=<?= $producto['idproducto']; ?>" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <a href="<?= $URL; ?>views/productos/index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Acciones adicionales -->
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-cogs mr-2"></i>Acciones</h3>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-block <?= $producto['estado'] == 1 ? 'btn-danger' : 'btn-success'; ?>" id="btnCambiarEstado">
                            <i class="fas <?= $producto['estado'] == 1 ? 'fa-ban' : 'fa-check'; ?> mr-2"></i>
                            <?= $producto['estado'] == 1 ? 'Desactivar Producto' : 'Activar Producto'; ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Columna derecha - Información detallada en tabs -->
            <div class="col-md-8">
                <!-- Información detallada en pestañas -->
                <div class="card card-info card-outline card-tabs">
                    <div class="card-header p-0 border-bottom-0">
                        <ul class="nav nav-tabs" id="detail-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="tab-general" data-toggle="pill" href="#general" role="tab"
                                    aria-controls="general" aria-selected="true">
                                    <i class="fas fa-info-circle mr-1"></i> Información General
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="tab-inventario" data-toggle="pill" href="#inventario" role="tab"
                                    aria-controls="inventario" aria-selected="false">
                                    <i class="fas fa-boxes mr-1"></i> Inventario
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="tab-precios" data-toggle="pill" href="#precios" role="tab"
                                    aria-controls="precios" aria-selected="false">
                                    <i class="fas fa-money-bill-wave mr-1"></i> Precios
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="detail-tabs-content">
                            <!-- Tab Información General -->
                            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="tab-general">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="info-box bg-light">
                                            <div class="info-box-content">
                                                <h5 class="info-box-text text-center text-muted">Descripción del Producto</h5>
                                                <div class="info-box-number text-center text-muted mb-0">
                                                    <?php if (!empty($producto['descripcion'])): ?>
                                                        <?= nl2br(htmlspecialchars($producto['descripcion'])); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">No hay descripción disponible</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header bg-light">
                                                <h3 class="card-title">Datos Básicos</h3>
                                            </div>
                                            <div class="card-body p-0">
                                                <table class="table table-hover">
                                                    <tbody>
                                                        <tr>
                                                            <td><i class="fas fa-signature mr-2"></i>Nombre:</td>
                                                            <td><?= htmlspecialchars($producto['nombre']); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td><i class="fas fa-list-alt mr-2"></i>Categoría:</td>
                                                            <td><?= htmlspecialchars($producto['categoria_nombre'] ?? 'Sin categoría'); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td><i class="fas fa-barcode mr-2"></i>Código:</td>
                                                            <td><?= !empty($producto['codigo']) ? htmlspecialchars($producto['codigo']) : 'N/A'; ?></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header bg-light">
                                                <h3 class="card-title">Información del Sistema</h3>
                                            </div>
                                            <div class="card-body p-0">
                                                <table class="table table-hover">
                                                    <tbody>
                                                        <tr>
                                                            <td><i class="fas fa-calendar-plus mr-2"></i>Fecha Creación:</td>
                                                            <td><?= isset($producto['fechacreacion']) ? date('d/m/Y', strtotime($producto['fechacreacion'])) : 'N/A'; ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td><i class="fas fa-calendar-check mr-2"></i>Última Actualización:</td>
                                                            <td><?= isset($producto['fechaactualizacion']) ? date('d/m/Y', strtotime($producto['fechaactualizacion'])) : 'N/A'; ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td><i class="fas fa-toggle-on mr-2"></i>Estado:</td>
                                                            <td>
                                                                <?php if ($producto['estado'] == 1): ?>
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

                            <!-- Tab Inventario -->
                            <div class="tab-pane fade" id="inventario" role="tabpanel" aria-labelledby="tab-inventario">
                                <div class="row">
                                    <div class="col-lg-4 col-md-6">
                                        <div class="small-box bg-info">
                                            <div class="inner">
                                                <h3><?= $producto['stock']; ?></h3>
                                                <p>Unidades en Stock</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-boxes"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <div class="small-box bg-warning">
                                            <div class="inner">
                                                <h3><?= $producto['stockminimo']; ?></h3>
                                                <p>Stock Mínimo</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <div class="small-box bg-success">
                                            <div class="inner">
                                                <h3><?= !empty($producto['stockmaximo']) ? $producto['stockmaximo'] : 'N/A'; ?></h3>
                                                <p>Stock Máximo</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-warehouse"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Estado de Inventario -->
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Estado de Inventario</h3>
                                    </div>
                                    <div class="card-body">
                                        <?php
                                        // Calcular porcentaje de stock en relación al mínimo
                                        $porcentaje = ($producto['stockminimo'] > 0) ?
                                            min(100, round(($producto['stock'] / $producto['stockminimo']) * 100)) : 100;

                                        // Determinar clase de la barra de progreso
                                        $barraClase = 'bg-success';
                                        if ($porcentaje <= 25) {
                                            $barraClase = 'bg-danger';
                                        } elseif ($porcentaje <= 50) {
                                            $barraClase = 'bg-warning';
                                        } elseif ($porcentaje <= 75) {
                                            $barraClase = 'bg-info';
                                        }
                                        ?>

                                        <div class="progress">
                                            <div class="progress-bar <?= $barraClase; ?>" role="progressbar"
                                                aria-valuenow="<?= $porcentaje; ?>" aria-valuemin="0" aria-valuemax="100"
                                                style="width: <?= $porcentaje; ?>%">
                                                <?= $porcentaje; ?>%
                                            </div>
                                        </div>

                                        <div class="mt-3">
                                            <?php if ($producto['stock'] < $producto['stockminimo']): ?>
                                                <div class="alert alert-danger">
                                                    <i class="fas fa-exclamation-circle mr-2"></i>
                                                    <strong>¡Alerta de stock bajo!</strong> El stock actual está por debajo del mínimo recomendado.
                                                    Se recomienda realizar una compra pronto.
                                                </div>
                                            <?php elseif ($producto['stock'] < ($producto['stockminimo'] * 1.5)): ?>
                                                <div class="alert alert-warning">
                                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                                    <strong>Stock cercano al mínimo.</strong> El stock actual se está acercando al nivel mínimo recomendado.
                                                </div>
                                            <?php else: ?>
                                                <div class="alert alert-success">
                                                    <i class="fas fa-check-circle mr-2"></i>
                                                    <strong>Stock saludable.</strong> El nivel de inventario está en un nivel óptimo.
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($producto['stockmaximo']) && $producto['stock'] > $producto['stockmaximo']): ?>
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle mr-2"></i>
                                                    <strong>Stock sobre el máximo.</strong> El inventario actual supera el nivel máximo recomendado.
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Últimos Movimientos -->
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Últimos Movimientos</h3>
                                    </div>
                                    <div class="card-body">
                                        <?php
                                        // Obtener los últimos movimientos del producto
                                        $movimientos = $controller->getUltimosMovimientos($producto['idproducto']);

                                        if (!empty($movimientos)): ?>
                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>Fecha</th>
                                                            <th>Tipo</th>
                                                            <th>Cantidad</th>
                                                            <th>Precio</th>
                                                            <th>Total</th>
                                                            <th>Referencia</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($movimientos as $movimiento): ?>
                                                            <tr>
                                                                <td><?= date('d/m/Y', strtotime($movimiento['fecha'])); ?></td>
                                                                <td>
                                                                    <span class="badge badge-<?= $movimiento['color']; ?>">
                                                                        <i class="fas <?= $movimiento['icono']; ?> mr-1"></i>
                                                                        <?php
                                                                        switch ($movimiento['tipo_movimiento']) {
                                                                            case 'venta':
                                                                                echo 'Venta';
                                                                                break;
                                                                            case 'compra':
                                                                                echo 'Compra';
                                                                                break;
                                                                            case 'servicio_baño':
                                                                                echo 'Servicio Baño';
                                                                                break;
                                                                            default:
                                                                                echo ucfirst($movimiento['tipo_movimiento']);
                                                                        }
                                                                        ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <span class="<?= $movimiento['tipo_movimiento'] == 'compra' ? 'text-success' : 'text-danger'; ?>">
                                                                        <?= $movimiento['operacion']; ?><?= $movimiento['cantidad']; ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <?php if ($movimiento['tipo_movimiento'] != 'servicio_baño'): ?>
                                                                        Bs <?= number_format($movimiento['precio'], 2); ?>
                                                                    <?php else: ?>
                                                                        <span class="text-muted">N/A</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <?php if ($movimiento['tipo_movimiento'] != 'servicio_baño'): ?>
                                                                        Bs <?= number_format($movimiento['total'], 2); ?>
                                                                    <?php else: ?>
                                                                        <span class="text-muted">Consumo interno</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <?php if ($movimiento['tipo_movimiento'] == 'venta'): ?>
                                                                        <?= !empty($movimiento['referencia']) ? htmlspecialchars($movimiento['referencia']) : 'Cliente no registrado'; ?>
                                                                    <?php elseif ($movimiento['tipo_movimiento'] == 'servicio_baño'): ?>
                                                                        <?= htmlspecialchars($movimiento['referencia']); ?>
                                                                        <small class="d-block text-muted">Por: <?= htmlspecialchars($movimiento['usuario']); ?></small>
                                                                    <?php else: ?>
                                                                        <?= htmlspecialchars($movimiento['referencia']); ?>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted">No hay movimientos registrados para este producto.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab Precios -->
                            <div class="tab-pane fade" id="precios" role="tabpanel" aria-labelledby="tab-precios">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-box bg-success">
                                            <span class="info-box-icon"><i class="fas fa-tag"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Precio de Venta</span>
                                                <span class="info-box-number">Bs <?= number_format($producto['precioventa'], 2); ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="info-box bg-warning">
                                            <span class="info-box-icon"><i class="fas fa-shopping-cart"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Precio de Compra</span>
                                                <span class="info-box-number">Bs <?= number_format($producto['preciocompra'], 2); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Análisis de Rentabilidad</h3>
                                    </div>
                                    <div class="card-body">
                                        <?php
                                        // Calcular margen y ganancia
                                        $ganancia = $producto['precioventa'] - $producto['preciocompra'];
                                        $margenPorcentaje = ($producto['preciocompra'] > 0) ?
                                            round(($ganancia / $producto['preciocompra']) * 100) : 0;
                                        ?>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Ganancia por unidad:</label>
                                                    <p class="text-success font-weight-bold">
                                                        Bs <?= number_format($ganancia, 2); ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Margen de ganancia:</label>
                                                    <p class="text-success font-weight-bold">
                                                        <?= $margenPorcentaje; ?>%
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="progress">
                                            <div class="progress-bar bg-success" role="progressbar"
                                                aria-valuenow="<?= $margenPorcentaje; ?>" aria-valuemin="0" aria-valuemax="100"
                                                style="width: <?= min(100, $margenPorcentaje); ?>%">
                                                <?= $margenPorcentaje; ?>%
                                            </div>
                                        </div>

                                        <div class="mt-3">
                                            <?php if ($margenPorcentaje < 10): ?>
                                                <div class="alert alert-danger">
                                                    <i class="fas fa-exclamation-circle mr-2"></i>
                                                    <strong>Margen bajo.</strong> El margen de ganancia está por debajo del 10%.
                                                </div>
                                            <?php elseif ($margenPorcentaje < 20): ?>
                                                <div class="alert alert-warning">
                                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                                    <strong>Margen moderado.</strong> El margen de ganancia está entre 10% y 20%.
                                                </div>
                                            <?php else: ?>
                                                <div class="alert alert-success">
                                                    <i class="fas fa-check-circle mr-2"></i>
                                                    <strong>Buen margen.</strong> El margen de ganancia es superior al 20%.
                                                </div>
                                            <?php endif; ?>
                                        </div>
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

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>

<script>
    // Inicializar plugins cuando el documento esté listo
    $(document).ready(function() {
        // Activar tooltips
        $('[data-toggle="tooltip"]').tooltip();

        // Guardar la pestaña activa en el almacenamiento local
        $('a[data-toggle="pill"]').on('shown.bs.tab', function(e) {
            localStorage.setItem('lastProductDetailTab', $(e.target).attr('id'));
        });

        // Restaurar la pestaña activa del almacenamiento local
        var lastTab = localStorage.getItem('lastProductDetailTab');
        if (lastTab) {
            $('#' + lastTab).tab('show');
        }

        // Cambiar el estado del producto con SweetAlert2
        $('#btnCambiarEstado').on('click', function() {
            const productoId = <?= $producto['idproducto']; ?>;
            const estadoActual = <?= $producto['estado']; ?>;
            const nombreProducto = "<?= htmlspecialchars($producto['nombre']); ?>";

            const tituloAlerta = estadoActual == 1 ?
                `¿Desactivar producto "${nombreProducto}"?` :
                `¿Activar producto "${nombreProducto}"?`;

            const textoAlerta = estadoActual == 1 ?
                'El producto no estará disponible para venta hasta que sea activado nuevamente.' :
                'El producto estará disponible nuevamente para venta.';

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
                    submitCsrfForm('<?= $URL; ?>controllers/productos/desactivar_producto.php', {
                        id: productoId,
                        estado: estadoActual
                    });
                }
            });
        });
    });
</script>