<?php
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../../controllers/productos/ProductoController.php';
require_once __DIR__ . '/../../controllers/clientes/ClienteController.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar permisos
if (!($authService->tienePermisoNombre($idusuario, 'ventas')) && !($authService->esAdministrador($idusuario))) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

$skip_datatables = true; // Evita cargar DataTables/pdfmake/vfs_fonts (~2.8MB)
$module_scripts = ['ventas/create-venta'];
$module_styles = ['ventas/ventas'];

include_once '../layouts/header.php';

// Obtener datos necesarios
$productoController = new ProductoController();
$productos = $productoController->index();

$clienteController = new ClienteController();
$clientes = $clienteController->index();
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Registrar Nueva Venta</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/ventas"> <i class="fas fa-shopping-cart"></i> Ventas</a></li>
                    <li class="breadcrumb-item active">Nueva Venta</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <form action="<?= $URL; ?>controllers/ventas/crear_venta.php" method="POST" id="form-venta" novalidate>
            <?= csrfField() ?>
            <input type="hidden" name="totalventa" id="totalventa-hidden" value="0">
            <input type="hidden" id="idcliente" name="idcliente" required>

            <div class="row">
                <!-- Columna izquierda: productos -->
                <div class="col-lg-8">
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
                                            <th width="15%">Cantidad</th>
                                            <th width="17%">Precio Unit.</th>
                                            <th width="17%">Descuento</th>
                                            <th width="11%">Subtotal</th>
                                            <th width="5%"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr id="fila-base" style="display: none;">
                                            <td data-label="Producto">
                                                <input type="hidden" class="idproducto" name="productos[]">
                                                <input type="text" class="form-control form-control-sm font-weight-bold nombre-producto border-0 px-0" readonly>
                                                <input type="text" class="form-control form-control-sm text-muted codigo-producto border-0 px-0" readonly style="font-size: 0.75rem; height: auto;">
                                            </td>
                                            <td data-label="Cantidad">
                                                <input type="number" class="form-control cantidad" name="cantidades[]" min="1" value="1" required>
                                                <div class="invalid-feedback">Ingrese una cantidad válida</div>
                                                <small class="text-muted stock-disponible">Disponible: 0</small>
                                            </td>
                                            <td data-label="Precio Unit.">
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><?= $appCurrency ?></span>
                                                    </div>
                                                    <input type="number" class="form-control precio" name="precios[]" step="0.01" min="0.01" value="0.00" required>
                                                    <div class="invalid-feedback">Ingrese un precio válido</div>
                                                </div>
                                            </td>
                                            <td data-label="Descuento">
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><?= $appCurrency ?></span>
                                                    </div>
                                                    <input type="number" class="form-control descuento" name="descuentos[]" step="0.01" min="0" value="0.00">
                                                </div>
                                                <small class="form-text text-muted">Por unidad</small>
                                            </td>
                                            <td data-label="Subtotal" class="text-right">
                                                <span class="subtotal">0.00</span>
                                            </td>
                                            <td data-label="" class="text-center">
                                                <button type="button" class="btn btn-danger btn-sm btn-eliminar-fila">
                                                    <i class="fas fa-trash"></i> <span class="d-md-none">Eliminar</span>
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Las filas de productos se agregarán dinámicamente aquí -->
                                    </tbody>
                                </table>
                            </div>
                            <div id="carrito-vacio" class="text-center text-muted p-5">
                                <i class="fas fa-shopping-basket fa-2x mb-2"></i>
                                <p class="mb-0">Aún no agregó productos. Use "Agregar Producto" para iniciar la venta.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Forma de pago -->
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-money-check-alt"></i> Forma de Pago
                                <span class="text-danger">*</span>
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                                    <label class="btn btn-outline-primary active" id="btn-pago-unico">
                                        <input type="radio" name="tipo_pago" value="unico" checked> Pago Único
                                    </label>
                                    <label class="btn btn-outline-primary" id="btn-pago-mixto">
                                        <input type="radio" name="tipo_pago" value="mixto"> Pago Mixto
                                    </label>
                                </div>
                            </div>

                            <!-- Pago único -->
                            <div id="seccion-pago-unico">
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label>Método de Pago <span class="text-danger">*</span></label>
                                        <select class="form-control select2" id="metodopago-unico" name="metodopago_unico" required>
                                            <option value="">-- Seleccione método de pago --</option>
                                            <option value="efectivo">Efectivo</option>
                                            <option value="tarjeta">Tarjeta</option>
                                            <option value="qr">QR</option>
                                            <option value="transferencia">Transferencia</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Monto <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><?= $appCurrency ?></span>
                                            </div>
                                            <input type="number" class="form-control" id="monto-unico" name="monto_unico" step="0.01" min="0" value="0.00" readonly>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-4" id="div-pago-recibido-unico">
                                        <label>Pago Recibido</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><?= $appCurrency ?></span>
                                            </div>
                                            <input type="number" class="form-control" id="pago-recibido-unico" name="pago_recibido_unico" step="0.01" min="0" value="0.00">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group" id="div-cambio-unico" style="display: none;">
                                    <label>Cambio:</label>
                                    <div class="input-group" style="max-width: 250px;">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><?= $appCurrency ?></span>
                                        </div>
                                        <input type="text" class="form-control" id="cambio-unico" readonly>
                                        <input type="hidden" name="cambio_unico" id="cambio-unico-hidden" value="0.00">
                                    </div>
                                </div>
                            </div>

                            <!-- Pagos mixtos -->
                            <div id="seccion-pago-mixto" style="display: none;">
                                <button type="button" id="btn-agregar-pago" class="btn btn-success btn-sm mb-3">
                                    <i class="fas fa-plus"></i> Agregar método de pago
                                </button>
                                <div id="contenedor-pagos">
                                    <!-- Aquí se agregarán dinámicamente los métodos de pago -->
                                </div>
                            </div>

                            <hr>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Total Pagado</span>
                                <strong id="total-pagado-display" class="text-success">0.00 <?= $appCurrency ?></strong>
                            </div>
                            <small id="diferencia-pago" class="d-block text-right" aria-live="polite"></small>
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div class="card">
                        <div class="card-body">
                            <div class="form-group mb-0">
                                <label for="observacion">Observaciones:</label>
                                <textarea class="form-control" id="observacion" name="observacion" rows="2" placeholder="Observaciones adicionales sobre la venta..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha: cliente y totales (fija) -->
                <div class="col-lg-4">
                    <div class="sidebar-sticky">
                        <!-- Cliente -->
                        <div class="card card-outline card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Cliente</h3>
                            </div>
                            <div class="card-body">
                                <div id="sin-cliente-seleccionado">
                                    <p class="text-muted mb-2">Ningún cliente seleccionado</p>
                                    <button type="button" class="btn btn-outline-primary btn-block" id="btn-abrir-modal-clientes" data-toggle="modal" data-target="#modal-clientes">
                                        <i class="fas fa-search"></i> Buscar Cliente
                                    </button>
                                </div>
                                <div id="info-cliente-seleccionado" style="display: none;">
                                    <strong id="nombre-cliente"></strong>
                                    <div id="documento-cliente" class="text-muted small"></div>
                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-block mt-2" id="btn-cambiar-cliente">
                                        <i class="fas fa-exchange-alt"></i> Cambiar cliente
                                    </button>
                                </div>
                                <div class="invalid-feedback d-block" id="cliente-feedback" aria-live="polite" style="display: none !important;">Seleccione un cliente</div>
                            </div>
                        </div>

                        <!-- Totales -->
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Subtotal</span>
                                    <span><span id="subtotal-venta">0.00</span> <?= $appCurrency ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Descuento</span>
                                    <span><span id="descuento-total">0.00</span> <?= $appCurrency ?></span>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="h5 mb-0">Total</span>
                                    <span class="h3 mb-0 text-primary"><span id="total-venta">0.00</span> <?= $appCurrency ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Acciones -->
                        <div class="card">
                            <div class="card-body">
                                <button type="submit" class="btn btn-primary btn-lg btn-block">
                                    <i class="fas fa-save"></i> Registrar Venta
                                </button>
                                <a href="<?= $URL; ?>views/ventas/index.php" class="btn btn-secondary btn-block">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

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
                <input type="text" class="form-control mb-3" id="modal-buscar-producto" placeholder="Buscar por nombre o código..." autocomplete="off">
                <div id="lista-productos-modal" class="list-group"></div>
                <p id="sin-resultados-productos" class="text-muted text-center mt-3" style="display: none;">No se encontraron productos.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: buscar/seleccionar cliente -->
<div class="modal fade" id="modal-clientes" tabindex="-1" role="dialog" aria-labelledby="modal-clientes-titulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title" id="modal-clientes-titulo">Buscar Cliente</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="text" class="form-control mb-3" id="modal-buscar-cliente" placeholder="Buscar por número de documento o nombre..." autocomplete="off">
                <div id="lista-clientes-modal" class="list-group"></div>
                <p id="sin-resultados-clientes" class="text-muted text-center mt-3" style="display: none;">No se encontraron clientes.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Template para nuevo método de pago (oculto) -->
<div id="template-metodo-pago" style="display: none;">
    <div class="card metodo-pago-item mb-3">
        <div class="card-header">
            <h3 class="card-title metodo-pago-titulo">Método de pago</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label>Método de Pago <span class="text-danger">*</span></label>
                <select class="form-control select2 select-metodo-pago" name="metodopago[]" required>
                    <option value="">-- Seleccione método de pago --</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="qr">QR</option>
                    <option value="transferencia">Transferencia</option>
                </select>
            </div>
            <div class="form-group">
                <label>Monto <span class="text-danger">*</span></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><?= $appCurrency ?></span>
                    </div>
                    <input type="number" class="form-control monto-pago" name="montopago[]" step="0.01" min="0" value="0.00" required>
                </div>
            </div>
            <div class="form-group div-pago-recibido">
                <label>Pago Recibido</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><?= $appCurrency ?></span>
                    </div>
                    <input type="number" class="form-control pago-recibido" name="pagorecibido[]" step="0.01" min="0" value="0.00">
                </div>
            </div>
            <div class="form-group div-cambio" style="display: none;">
                <label>Cambio:</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><?= $appCurrency ?></span>
                    </div>
                    <input type="text" class="form-control cambio-pago" readonly>
                    <input type="hidden" name="cambio[]" class="cambio-hidden" value="0.00">
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-sm-auto mb-0 mb-sm-0">
                    <button type="button" class="btn btn-danger btn-sm w-100 btn-eliminar-pago">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Datos para el formulario de ventas -->
<div id="datos-venta"
    data-productos='<?= htmlspecialchars(json_encode(array_values(array_filter($productos, function ($p) {
                        return $p['estado'] == 1 && $p['stock'] > 0;
                    }))), ENT_QUOTES, "UTF-8") ?>'
    data-clientes='<?= htmlspecialchars(json_encode($clientes), ENT_QUOTES, "UTF-8") ?>'
    style="display:none"></div>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>