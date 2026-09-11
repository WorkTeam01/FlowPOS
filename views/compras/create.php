<?php
require_once __DIR__ . '/../../controllers/productos/ProductoController.php';
require_once __DIR__ . '/../../controllers/usuarios/UsuarioController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar permisos
if (!($authService->tienePermisoNombre($idusuario, 'compras')) && !($authService->esAdministrador($idusuario))) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Obtener nombre del usuario actual si no está en sesión
$usuarioController = new UsuarioController();
if (!isset($_SESSION['usuario_nombre'])) {
    $usuarioActual = $usuarioController->editar($idusuario);
    $_SESSION['usuario_nombre'] = $usuarioActual['nombre'];
}

// Obtener productos activos, proyectando solo los campos que usa el JS del carrito
$productoController = new ProductoController();
$productos = array_values(array_map(function ($p) {
    return [
        'idproducto' => $p['idproducto'],
        'nombre' => $p['nombre'],
        'codigo' => $p['codigo'],
        'preciocompra' => $p['preciocompra'],
    ];
}, array_filter($productoController->index(), function ($p) {
    return (int)$p['estado'] === 1;
})));

$skip_datatables = true; // Evita cargar DataTables/pdfmake/vfs_fonts (~2.8MB)
$skip_select2 = true;
$module_scripts = ['compras/create-compra'];
$module_styles = ['compras/compras'];
include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Registrar Nueva Compra</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/compras"><i class="fas fa-shopping-cart"></i> Compras</a></li>
                    <li class="breadcrumb-item active">Nueva Compra</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <form action="<?= $URL; ?>controllers/compras/crear_compra.php" method="POST" id="form-compra" novalidate>
            <?= csrfField() ?>
            <input type="hidden" id="idusuario" name="idusuario" value="<?= $idusuario; ?>">

            <div class="row">
                <!-- Columna izquierda: información básica y productos -->
                <div class="col-lg-8">
                    <!-- Información Básica -->
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Información de la Compra</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nombre_usuario"><i class="fas fa-user"></i> Responsable</label>
                                        <input type="text" class="form-control" id="nombre_usuario"
                                            value="<?= htmlspecialchars($_SESSION['usuario_nombre']); ?>" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="fechacompra"><i class="far fa-calendar-alt"></i> Fecha de Compra <span class="text-danger">*</span></label>
                                        <input type="datetime-local" class="form-control" id="fechacompra" name="fechacompra"
                                            value="<?= date('Y-m-d\TH:i'); ?>" required aria-describedby="feedback-fecha">
                                        <div class="invalid-feedback" id="feedback-fecha">Seleccione la fecha de la compra</div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group mb-0">
                                        <label for="observaciones">Observaciones</label>
                                        <textarea class="form-control" id="observaciones" name="observaciones"
                                            placeholder="Ingrese observaciones adicionales (opcional)" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detalle de Productos -->
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title mb-0">Detalle de Productos</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-sm btn-primary" id="btn-abrir-modal-productos" data-toggle="modal" data-target="#modal-productos">
                                    <i class="fas fa-plus"></i> Agregar Producto
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive carrito-scroll">
                                <table class="table table-bordered mb-0" id="tabla-productos">
                                    <thead class="bg-light">
                                        <tr>
                                            <th width="40%">Producto</th>
                                            <th width="20%">Cantidad</th>
                                            <th width="20%">Precio Unit.</th>
                                            <th width="15%">Subtotal</th>
                                            <th width="5%"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr id="fila-base" style="display: none;">
                                            <td data-label="Producto">
                                                <input type="hidden" class="idproducto" name="productos[]">
                                                <span class="font-weight-bold nombre-producto"></span>
                                                <small class="text-muted d-block codigo-producto"></small>
                                            </td>
                                            <td data-label="Cantidad">
                                                <input type="number" class="form-control cantidad" name="cantidades[]"
                                                    min="1" value="1" required aria-label="Cantidad">
                                                <div class="invalid-feedback">Ingrese una cantidad válida</div>
                                            </td>
                                            <td data-label="Precio Unit.">
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><?= $appCurrency ?></span>
                                                    </div>
                                                    <input type="number" class="form-control precio" name="precios[]"
                                                        step="0.01" min="0.01" value="0.00" required aria-label="Precio unitario">
                                                    <div class="invalid-feedback">Ingrese un precio válido</div>
                                                </div>
                                            </td>
                                            <td data-label="Subtotal" class="text-right">
                                                <span class="subtotal">0.00</span>
                                            </td>
                                            <td data-label="" class="text-center">
                                                <button type="button" class="btn btn-danger btn-sm btn-eliminar-fila" aria-label="Eliminar producto">
                                                    <i class="fas fa-trash"></i> <span class="d-md-none">Eliminar</span>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div id="carrito-vacio" class="text-center text-muted p-5">
                                <i class="fas fa-boxes fa-2x mb-2"></i>
                                <p class="mb-0">Aún no agregó productos. Use "Agregar Producto" para iniciar la compra.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha: totales y acciones (fija) -->
                <div class="col-lg-4">
                    <div class="sidebar-sticky">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="h5 mb-0">Total</span>
                                    <span class="h3 mb-0 text-primary"><span id="total-compra">0.00</span> <?= $appCurrency ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <button type="submit" class="btn btn-primary btn-lg btn-block">
                                    <i class="fas fa-save"></i> Registrar Compra
                                </button>
                                <a href="<?= $URL; ?>views/compras/index.php" class="btn btn-secondary btn-block">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <!-- /.container-fluid -->
</section>
<!-- /.content -->

<!-- Modal: buscar/agregar producto -->
<div class="modal fade" id="modal-productos" tabindex="-1" role="dialog" aria-labelledby="modal-productos-titulo" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title" id="modal-productos-titulo">Agregar Producto</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="text" class="form-control mb-3" id="modal-buscar-producto" placeholder="Buscar por nombre o código..." autocomplete="off" aria-label="Buscar producto por nombre o código">
                <div id="lista-productos-modal" class="list-group"></div>
                <p id="sin-resultados-productos" class="text-muted text-center mt-3" style="display: none;">No se encontraron productos.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div id="datos-compra" data-productos='<?= htmlspecialchars(json_encode($productos), ENT_QUOTES, "UTF-8"); ?>' style="display:none"></div>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>