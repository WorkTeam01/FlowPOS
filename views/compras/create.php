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

// Incluir encabezado
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mostrar/ocultar mensaje cuando no hay productos
        function actualizarMensajeProductos() {
            const filas = document.querySelectorAll('#tabla-productos tbody tr.fila-producto');
            const mensaje = document.getElementById('mensaje-sin-productos');

            if (filas.length === 0) {
                mensaje.classList.remove('d-none');
            } else {
                mensaje.classList.add('d-none');
            }
        }

        // Función para agregar una nueva fila de producto
        function agregarFilaProducto(producto) {
            const tbody = document.querySelector('#tabla-productos tbody');

            // Verificar si el producto ya está en la tabla
            const filas = tbody.querySelectorAll('tr.fila-producto');
            for (let fila of filas) {
                const idProducto = fila.querySelector('input[name="productos[]"]').value;
                if (idProducto == producto.idproducto) {
                    // Incrementar cantidad si el producto ya existe
                    const inputCantidad = fila.querySelector('.cantidad');
                    inputCantidad.value = parseInt(inputCantidad.value) + 1;
                    calcularSubtotal(fila);
                    calcularTotal();
                    actualizarMensajeProductos();
                    return;
                }
            }

            // Crear nueva fila si el producto no existe
            const nuevaFila = document.createElement('tr');
            nuevaFila.classList.add('fila-producto');
            nuevaFila.innerHTML = `
            <td>
                <input type="hidden" name="productos[]" value="${producto.idproducto}">
                <i class="fas fa-box-open mr-2 text-muted"></i>
                ${producto.nombre} <small class="text-muted">(${producto.codigo || 'S/COD'})</small>
            </td>
            <td>
                <div class="input-group">
                    <input type="number" class="form-control cantidad" name="cantidades[]" 
                        min="1" value="1" required>
                    <div class="input-group-append">
                        <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                    </div>
                </div>
            </td>
            <td>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-dollar-sign"></i></span>
                    </div>
                    <input type="number" class="form-control precio" name="precios[]" 
                        step="0.01" min="0.01" value="${parseFloat(producto.preciocompra || 0).toFixed(2)}" required>
                </div>
            </td>
            <td class="text-right">
                <span class="subtotal">${parseFloat(producto.preciocompra || 0).toFixed(2)}</span>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm btn-eliminar-fila">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;

            // Agregar eventos
            agregarEventosCalculo(nuevaFila);

            // Evento para eliminar fila
            nuevaFila.querySelector('.btn-eliminar-fila').addEventListener('click', function() {
                this.closest('tr').remove();
                calcularTotal();
                actualizarMensajeProductos();
            });

            // Insertar en la tabla
            tbody.appendChild(nuevaFila);
            calcularTotal();
            actualizarMensajeProductos();
        }

        // Función para agregar eventos de cálculo a una fila
        function agregarEventosCalculo(fila) {
            const cantidad = fila.querySelector('.cantidad');
            const precio = fila.querySelector('.precio');

            cantidad.addEventListener('input', function() {
                calcularSubtotal(fila);
                calcularTotal();
            });

            precio.addEventListener('input', function() {
                calcularSubtotal(fila);
                calcularTotal();
            });
        }

        // Calcular subtotal para una fila
        function calcularSubtotal(fila) {
            const cantidad = parseFloat(fila.querySelector('.cantidad').value) || 0;
            const precio = parseFloat(fila.querySelector('.precio').value) || 0;
            const subtotal = cantidad * precio;
            fila.querySelector('.subtotal').textContent = subtotal.toFixed(2);
        }

        // Calcular total de la compra
        function calcularTotal() {
            let total = 0;
            document.querySelectorAll('.fila-producto').forEach(fila => {
                const subtotal = parseFloat(fila.querySelector('.subtotal').textContent) || 0;
                total += subtotal;
            });
            document.getElementById('total-compra').textContent = total.toFixed(2);
            document.getElementById('input-total-compra').value = total.toFixed(2);
        }

        // Buscar producto por código
        function buscarProducto() {
            const codigo = document.getElementById('buscar-producto').value.trim();
            if (!codigo) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Por favor ingrese un código de producto',
                });
                return;
            }

            // Buscar en la lista de productos
            const productos = <?= json_encode($productos); ?>;
            const productoEncontrado = productos.find(p =>
                p.codigo && p.codigo.toLowerCase() === codigo.toLowerCase() && p.estado == 1
            );

            if (productoEncontrado) {
                agregarFilaProducto(productoEncontrado);
                document.getElementById('buscar-producto').value = '';
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Producto no encontrado',
                    text: 'No se encontró un producto activo con ese código',
                });
            }
        }

        // Evento para botón buscar
        document.getElementById('btn-buscar-producto').addEventListener('click', buscarProducto);

        // Permitir buscar con Enter
        document.getElementById('buscar-producto').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarProducto();
            }
        });

        // Validar formulario antes de enviar
        document.getElementById('form-compra').addEventListener('submit', function(e) {
            e.preventDefault();

            // Resetear errores
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

            let isValid = true;
            let firstInvalidElement = null;

            // Validar campos principales
            const mainFields = ['fechacompra'];
            mainFields.forEach(field => {
                const element = document.getElementById(field);
                if (!element.value) {
                    element.classList.add('is-invalid');
                    isValid = false;
                    if (!firstInvalidElement) firstInvalidElement = element;
                }
            });

            // Validar filas de productos
            const filas = document.querySelectorAll('.fila-producto');
            if (filas.length === 0) {
                isValid = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Debe agregar al menos un producto',
                });
                return;
            }

            filas.forEach((fila, index) => {
                const cantidad = fila.querySelector('.cantidad');
                const precio = fila.querySelector('.precio');

                if (!cantidad.value || parseFloat(cantidad.value) <= 0) {
                    cantidad.classList.add('is-invalid');
                    isValid = false;
                    if (!firstInvalidElement) firstInvalidElement = cantidad;
                }

                if (!precio.value || parseFloat(precio.value) <= 0) {
                    precio.classList.add('is-invalid');
                    isValid = false;
                    if (!firstInvalidElement) firstInvalidElement = precio;
                }
            });

            if (!isValid) {
                if (firstInvalidElement) {
                    firstInvalidElement.focus();
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Por favor complete todos los campos requeridos correctamente',
                });
                return;
            }

            // Validar total
            const total = parseFloat(document.getElementById('input-total-compra').value);
            if (total <= 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'El total de la compra debe ser mayor que cero',
                });
                return;
            }

            // Confirmación antes de enviar
            Swal.fire({
                title: '¿Confirmar compra?',
                text: "¿Está seguro de registrar esta compra?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, registrar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Mostrar mensaje de carga
                    Swal.fire({
                        title: 'Procesando compra...',
                        html: 'Por favor espere mientras se registra la compra',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Enviar formulario
                    this.submit();
                }
            });
        });

        // Inicializar mensaje de productos
        actualizarMensajeProductos();
    });
</script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>