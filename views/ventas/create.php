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

include_once '../layouts/header.php';

// Obtener datos necesarios
$productoController = new ProductoController();
$productos = $productoController->index();

$clienteController = new ClienteController();
$clientes = $clienteController->index();
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
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
        <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Formulario de Venta</h3>
                    </div>
                    <form action="<?= $URL; ?>controllers/ventas/crear_venta.php" method="POST" id="form-venta" novalidate>
                        <?= csrfField() ?>
                        <input type="hidden" name="idusuario" value="<?= $_SESSION['usuario_id'] ?>">
                        <input type="hidden" name="totalventa" id="totalventa-hidden" value="0">

                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Vendedor</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario actual') ?>" readonly>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="idcliente">Cliente <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="buscar-cliente" placeholder="Buscar por número de documento o nombre" autocomplete="off">
                                        <input type="hidden" id="idcliente" name="idcliente" required>
                                        <div class="invalid-feedback">Seleccione un cliente</div>
                                        <div id="sugerencias-clientes" class="list-group" style="display: none; position: absolute; z-index: 1000; width: 100%; max-height: 200px; overflow-y: auto;"></div>
                                    </div>
                                    <div id="info-cliente-seleccionado" class="mt-2" style="display: none;">
                                        <strong>Cliente seleccionado:</strong>
                                        <span id="nombre-cliente"></span>
                                        <button type="button" class="btn btn-sm btn-link text-danger" id="quitar-cliente">
                                            <i class="fas fa-times"></i> Quitar
                                        </button>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="fechaventa">Fecha <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="fechaventa" name="fechaventa" value="<?= date('Y-m-d'); ?>" required>
                                        <div class="invalid-feedback">Ingrese una fecha válida</div>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h5>Detalle de Productos <span class="text-danger">*</span></h5>

                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="buscar-producto" placeholder="Ingrese el código del producto">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="button" id="btn-buscar-producto">
                                                <i class="fas fa-search"></i> Buscar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered" id="tabla-productos">
                                    <thead class="bg-light">
                                        <tr>
                                            <th width="30%">Producto</th>
                                            <th width="10%">Código</th>
                                            <th width="15%">Cantidad</th>
                                            <th width="15%">Precio Unitario</th>
                                            <th width="15%">Descuento</th>
                                            <th width="10%">Subtotal</th>
                                            <th width="5%"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr id="fila-base" style="display: none;">
                                            <td>
                                                <input type="hidden" class="form-control idproducto" name="productos[]">
                                                <input type="text" class="form-control nombre-producto" readonly>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control codigo-producto" readonly>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control cantidad" name="cantidades[]" min="1" value="1" required>
                                                <div class="invalid-feedback">Ingrese una cantidad válida</div>
                                                <small class="text-muted stock-disponible">Disponible: 0</small>
                                            </td>
                                            <td>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><?= $appCurrency ?></span>
                                                    </div>
                                                    <input type="number" class="form-control precio" name="precios[]" step="0.01" min="0.01" value="0.00" required>
                                                    <div class="invalid-feedback">Ingrese un precio válido</div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><?= $appCurrency ?></span>
                                                    </div>
                                                    <input type="number" class="form-control descuento" name="descuentos[]" step="0.01" min="0" value="0.00">
                                                </div>
                                                <small class="form-text text-muted">Por unidad</small>
                                            </td>
                                            <td class="text-right">
                                                <span class="subtotal">0.00</span>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-danger btn-sm btn-eliminar-fila">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Las filas de productos se agregarán dinámicamente aquí -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="5" class="text-right"><strong>Subtotal:</strong></td>
                                            <td class="text-right"><strong><span id="subtotal-venta">0.00</span> <?= $appCurrency ?></strong></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" class="text-right"><strong>Descuento Total:</strong></td>
                                            <td class="text-right"><strong><span id="descuento-total">0.00</span> <?= $appCurrency ?></strong></td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" class="text-right"><strong>Total Venta:</strong></td>
                                            <td class="text-right"><strong><span id="total-venta">0.00</span> <?= $appCurrency ?></strong></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <!-- SECCIÓN DE PAGOS -->
                            <div class="card mt-4">
                                <div class="card-header bg-purple">
                                    <h5 class="card-title">
                                        <i class="fas fa-money-check-alt"></i> Forma de Pago
                                        <span class="text-danger">*</span>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <!-- Selector de tipo de pago -->
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Tipo de pago:</label>
                                                <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                                                    <label class="btn btn-outline-primary active" id="btn-pago-unico">
                                                        <input type="radio" name="tipo_pago" value="unico" checked> Pago Único
                                                    </label>
                                                    <label class="btn btn-outline-primary" id="btn-pago-mixto">
                                                        <input type="radio" name="tipo_pago" value="mixto"> Pago Mixto
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Sección para pago único (visible por defecto) -->
                                    <div id="seccion-pago-unico">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Método de Pago <span class="text-danger">*</span></label>
                                                    <select class="form-control" id="metodopago-unico" name="metodopago_unico" required>
                                                        <option value="">-- Seleccione método de pago --</option>
                                                        <option value="efectivo">Efectivo</option>
                                                        <option value="tarjeta">Tarjeta</option>
                                                        <option value="qr">QR</option>
                                                        <option value="transferencia">Transferencia</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Monto <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><?= $appCurrency ?></span>
                                                        </div>
                                                        <input type="number" class="form-control" id="monto-unico" name="monto_unico" step="0.01" min="0" value="0.00" readonly>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4" id="div-pago-recibido-unico">
                                                <div class="form-group">
                                                    <label>Pago Recibido</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><?= $appCurrency ?></span>
                                                        </div>
                                                        <input type="number" class="form-control" id="pago-recibido-unico" name="pago_recibido_unico" step="0.01" min="0" value="0.00">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 offset-md-8" id="div-cambio-unico" style="display: none;">
                                                <div class="form-group">
                                                    <label>Cambio:</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><?= $appCurrency ?></span>
                                                        </div>
                                                        <input type="text" class="form-control" id="cambio-unico" readonly>
                                                        <input type="hidden" name="cambio_unico" id="cambio-unico-hidden" value="0.00">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Sección para pagos mixtos (oculta por defecto) -->
                                    <div id="seccion-pago-mixto" style="display: none;">
                                        <div class="row mb-3">
                                            <div class="col-md-12">
                                                <button type="button" id="btn-agregar-pago" class="btn btn-success btn-sm">
                                                    <i class="fas fa-plus"></i> Agregar método de pago
                                                </button>
                                            </div>
                                        </div>

                                        <div id="contenedor-pagos">
                                            <!-- Aquí se agregarán dinámicamente los métodos de pago -->
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Total Venta:</label>
                                                <h4 id="total-venta-display" class="text-primary">0.00 <?= $appCurrency ?></h4>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Total Pagado:</label>
                                                <h4 id="total-pagado-display" class="text-success">0.00 <?= $appCurrency ?></h4>
                                                <small id="diferencia-pago" class="text-danger"></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Observaciones -->
                            <div class="form-group mt-3">
                                <label for="observacion">Observaciones:</label>
                                <textarea class="form-control" id="observacion" name="observacion" rows="2" placeholder="Observaciones adicionales sobre la venta..."></textarea>
                            </div>
                        </div>

                        <div class="card-footer">
                            <div class="row g-1">
                                <div class="col-12 col-sm-auto">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-save"></i> Registrar Venta
                                    </button>
                                </div>
                                <div class="col-12 col-sm-auto">
                                    <a href="<?= $URL; ?>views/ventas/index.php" class="btn btn-secondary w-100">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Template para nuevo método de pago (oculto) -->
<div id="template-metodo-pago" style="display: none;">
    <div class="metodo-pago-item mb-3 p-3 border rounded">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Método de Pago <span class="text-danger">*</span></label>
                    <select class="form-control select-metodo-pago" name="metodopago[]" required>
                        <option value="">-- Seleccione método de pago --</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="qr">QR</option>
                        <option value="transferencia">Transferencia</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Monto <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><?= $appCurrency ?></span>
                        </div>
                        <input type="number" class="form-control monto-pago" name="montopago[]" step="0.01" min="0" value="0.00" required>
                    </div>
                </div>
            </div>
            <div class="col-md-3 div-pago-recibido">
                <div class="form-group">
                    <label>Pago Recibido</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><?= $appCurrency ?></span>
                        </div>
                        <input type="number" class="form-control pago-recibido" name="pagorecibido[]" step="0.01" min="0" value="0.00">
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-block btn-eliminar-pago">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </div>
            <div class="col-md-4 offset-md-7 div-cambio" style="display: none;">
                <div class="form-group">
                    <label>Cambio:</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><?= $appCurrency ?></span>
                        </div>
                        <input type="text" class="form-control cambio-pago" readonly>
                        <input type="hidden" name="cambio[]" class="cambio-hidden" value="0.00">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts para el formulario de ventas -->
<script>
    // Estas variables son necesarias para el archivo JS
    // Exportamos los datos desde PHP a variables JavaScript globales
    const productosDisponibles = <?= json_encode(array_filter($productos, function ($p) {
                                        return $p['estado'] == 1 && $p['stock'] > 0;
                                    })) ?>;

    const clientesDisponibles = <?= json_encode($clientes) ?>;
</script>

<script src="<?= $URL; ?>public/js/modules/ventas/create-venta.js"></script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>