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

// Obtener productos activos
$productoController = new ProductoController();
$productos = $productoController->index();

$skip_datatables = true; // Evita cargar DataTables/pdfmake/vfs_fonts (~2.8MB)
$skip_select2 = true;
$module_scripts = ['compras/create-compra'];
include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Registrar Nueva Compra</h1>
                <p class="text-muted">Complete el formulario para registrar una nueva compra en el sistema</p>
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
        <div class="row">
            <div class="col-12">
                <!-- Guía de ayuda en el lado derecho -->
                <!-- Tarjeta de guía -->
                <div class="card card-info card-outline collapsed-card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-question-circle mr-2"></i>Guía de Compra</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="text-info"><i class="fas fa-info-circle"></i> Cómo registrar una compra</h5>
                        <div class="callout callout-info">
                            <p>Complete todos los campos marcados con <span class="text-danger">*</span> para registrar una nueva compra.</p>
                        </div>

                        <div class="accordion" id="accordionHelp">
                            <!-- Sección de Información Básica -->
                            <div class="card">
                                <div class="card-header" id="headingBasic">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link btn-block text-left text-info" type="button" data-toggle="collapse" data-target="#collapseBasic" aria-expanded="true" aria-controls="collapseBasic">
                                            <i class="fas fa-info-circle mr-2"></i>Información Básica
                                        </button>
                                    </h2>
                                </div>
                                <div id="collapseBasic" class="collapse show" aria-labelledby="headingBasic" data-parent="#accordionHelp">
                                    <div class="card-body">
                                        <p><strong>Responsable:</strong> Muestra automáticamente el usuario que está registrando la compra.</p>
                                        <p><strong>Fecha de Compra:</strong> Fecha y hora en que se realiza la compra. Se autocompleta con la fecha actual.</p>
                                        <p><strong>Observaciones:</strong> Campo opcional para notas adicionales sobre la compra.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección de Productos -->
                            <div class="card">
                                <div class="card-header" id="headingProducts">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed text-info" type="button" data-toggle="collapse" data-target="#collapseProducts" aria-expanded="false" aria-controls="collapseProducts">
                                            <i class="fas fa-boxes mr-2"></i>Agregar Productos
                                        </button>
                                    </h2>
                                </div>
                                <div id="collapseProducts" class="collapse" aria-labelledby="headingProducts" data-parent="#accordionHelp">
                                    <div class="card-body">
                                        <p><strong>Buscar Producto:</strong> Ingrese el código del producto y presione Buscar o Enter.</p>
                                        <div class="alert alert-secondary">
                                            <small><i class="fas fa-lightbulb mr-1"></i> Consejo: Puede escanear códigos de barras si tiene un lector conectado.</small>
                                        </div>
                                        <p><strong>Cantidad:</strong> Indique cuántas unidades del producto está comprando.</p>
                                        <p><strong>Precio Unitario:</strong> Precio de compra por unidad. Se autocompleta con el precio registrado pero puede modificarlo.</p>
                                        <div class="alert alert-warning">
                                            <small><i class="fas fa-exclamation-triangle mr-1"></i> Importante: Verifique que los precios sean correctos antes de registrar.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección de Totales -->
                            <div class="card">
                                <div class="card-header" id="headingTotals">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed text-info" type="button" data-toggle="collapse" data-target="#collapseTotals" aria-expanded="false" aria-controls="collapseTotals">
                                            <i class="fas fa-calculator mr-2"></i>Totales y Finalización
                                        </button>
                                    </h2>
                                </div>
                                <div id="collapseTotals" class="collapse" aria-labelledby="headingTotals" data-parent="#accordionHelp">
                                    <div class="card-body">
                                        <p><strong>Subtotales:</strong> Se calculan automáticamente como Cantidad × Precio Unitario.</p>
                                        <p><strong>Total:</strong> Suma de todos los subtotales de los productos.</p>
                                        <div class="alert alert-success">
                                            <small><i class="fas fa-check-circle mr-1"></i> El sistema actualizará automáticamente el inventario al registrar la compra.</small>
                                        </div>
                                        <p><strong>Registrar:</strong> Revise toda la información antes de guardar. No podrá modificar la compra después.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-edit mr-2"></i>Formulario de Compra</h3>
                    </div>
                    <form action="<?= $URL; ?>controllers/compras/crear_compra.php" method="POST" id="form-compra" novalidate>
                        <?= csrfField() ?>
                        <div class="card-body">
                            <div class="row">
                                <!-- Usuario Responsable (no editable) -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="nombre_usuario"><i class="fas fa-user"></i> Responsable <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                            </div>
                                            <input type="text" class="form-control" id="nombre_usuario"
                                                value="<?= htmlspecialchars($_SESSION['usuario_nombre']); ?>" readonly>
                                            <input type="hidden" id="idusuario" name="idusuario" value="<?= $idusuario; ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Fecha de Compra -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="fechacompra"><i class="far fa-calendar-alt"></i> Fecha de Compra <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="far fa-clock"></i></span>
                                            </div>
                                            <input type="datetime-local" class="form-control" id="fechacompra" name="fechacompra"
                                                value="<?= date('Y-m-d\TH:i'); ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Estado (oculto, siempre será 1 para nuevas compras) -->
                                <input type="hidden" name="estado" value="1">

                                <!-- Observaciones -->
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="observaciones"><i class="fas fa-comment"></i> Observaciones</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-align-left"></i></span>
                                            </div>
                                            <textarea class="form-control" id="observaciones" name="observaciones"
                                                placeholder="Ingrese observaciones adicionales (opcional)" rows="2"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h5><i class="fas fa-boxes"></i> Detalle de Productos</h5>

                            <!-- Buscador de productos por código -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="buscar-producto"><i class="fas fa-search"></i> Buscar Producto por Código</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                            </div>
                                            <input type="text" class="form-control" id="buscar-producto" placeholder="Ingrese código del producto">
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="button" id="btn-buscar-producto">
                                                    <i class="fas fa-search mr-1"></i> Buscar
                                                </button>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">Ingrese el código del producto y presione Buscar o Enter</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabla de productos -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="tabla-productos">
                                    <thead class="bg-light">
                                        <tr>
                                            <th width="40%"><i class="fas fa-box-open mr-1"></i> Producto</th>
                                            <th width="15%"><i class="fas fa-hashtag mr-1"></i> Cantidad</th>
                                            <th width="20%"><i class="fas fa-dollar-sign mr-1"></i> Precio Unitario</th>
                                            <th width="20%"><i class="fas fa-calculator mr-1"></i> Subtotal</th>
                                            <th width="5%"><i class="fas fa-cog mr-1"></i> Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Las filas de productos se agregarán dinámicamente -->
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-light">
                                            <td colspan="3" class="text-right"><strong><i class="fas fa-file-invoice-dollar mr-1"></i> Total:</strong></td>
                                            <td class="text-right"><strong><span id="total-compra">0.00</span></strong></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="5">
                                                <input type="hidden" name="totalcompra" id="input-total-compra" value="0">
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <!-- Mensaje cuando no hay productos -->
                            <div id="mensaje-sin-productos" class="alert alert-info text-center">
                                <i class="fas fa-info-circle mr-2"></i> No hay productos agregados. Busque y agregue productos usando el campo de búsqueda arriba.
                            </div>
                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <div class="row">
                                <div class="col-12 col-sm-auto mb-2 mb-sm-0">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-save mr-2"></i> Registrar Compra
                                    </button>
                                </div>
                                <div class="col-12 col-sm-auto">
                                    <a href="<?= $URL; ?>views/compras/index.php" class="btn btn-secondary w-100">
                                        <i class="fas fa-times mr-2"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
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

<style>
    .d-none {
        display: none !important;
    }

    .is-invalid {
        border-color: #dc3545;
    }

    .invalid-feedback {
        display: none;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 80%;
        color: #dc3545;
    }

    input[readonly] {
        background-color: #e9ecef;
        opacity: 1;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, .03);
    }
</style>

<div id="datos-compra" data-productos='<?= htmlspecialchars(json_encode($productos), ENT_QUOTES, "UTF-8"); ?>' style="display:none"></div>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>