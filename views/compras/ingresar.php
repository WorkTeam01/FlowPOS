<?php
require_once __DIR__ . '/../../controllers/usuarios/UsuarioController.php';
require_once __DIR__ . '/../../controllers/productos/ProductoController.php';
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

// Incluir encabezado
$skip_datatables = true; // Esta vista no usa tabla; evita cargar DataTables/pdfmake/vfs_fonts (~2.8MB)
$skip_select2 = true; // Esta vista no usa Select2
include_once '../layouts/header.php';

// Obtener productos activos
$productoController = new ProductoController();
$productos = $productoController->index();
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Registrar Nueva Compra</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/compras">Compras</a></li>
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
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Formulario de Compra</h3>
                    </div>
                    <form action="<?= $URL; ?>controllers/compras/ingresar_compra.php" method="POST" id="form-compra" novalidate>
                        <?= csrfField() ?>
                        <div class="card-body">
                            <div class="row">
                                <!-- Usuario Responsable (no editable) -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="nombre_usuario">Responsable <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nombre_usuario"
                                            value="<?= htmlspecialchars($_SESSION['usuario_nombre']); ?>" readonly>
                                        <input type="hidden" id="idusuario" name="idusuario" value="<?= $idusuario; ?>">
                                    </div>
                                </div>

                                <!-- Fecha de Compra -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="fechacompra">Fecha de Compra <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="fechacompra" name="fechacompra"
                                            value="<?= date('Y-m-d'); ?>" required>
                                    </div>
                                </div>

                                <!-- Estado -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="estado">Estado <span class="text-danger">*</span></label>
                                        <select class="form-control" id="estado" name="estado" required>
                                            <option value="completada" selected>Completada</option>
                                            <option value="pendiente">Pendiente</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Observaciones -->
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="observaciones">Observaciones</label>
                                        <textarea class="form-control" id="observaciones" name="observaciones"
                                            placeholder="Ingrese observaciones adicionales (opcional)" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h5>Detalle de Productos</h5>

                            <!-- Buscador de productos por código -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="buscar-producto">Buscar Producto por Código</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="buscar-producto" placeholder="Ingrese código del producto">
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="button" id="btn-buscar-producto">
                                                    <i class="fas fa-search"></i> Buscar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabla de productos -->
                            <div class="table-responsive">
                                <table class="table table-bordered" id="tabla-productos">
                                    <thead class="bg-light">
                                        <tr>
                                            <th width="40%">Producto</th>
                                            <th width="15%">Cantidad</th>
                                            <th width="20%">Precio Unitario</th>
                                            <th width="20%">Subtotal</th>
                                            <th width="5%"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Las filas de productos se agregarán dinámicamente -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-right"><strong>Total:</strong></td>
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
                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Registrar Compra
                            </button>
                            <a href="<?= $URL; ?>views/compras/ingresar.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
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
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Función para agregar una nueva fila de producto
        function agregarFilaProducto(producto) {
            const tbody = document.querySelector('#tabla-productos tbody');

            // Verificar si el producto ya está en la tabla
            const filas = tbody.querySelectorAll('tr');
            for (let fila of filas) {
                const idProducto = fila.querySelector('input[name="productos[]"]').value;
                if (idProducto == producto.idproducto) {
                    // Incrementar cantidad si el producto ya existe
                    const inputCantidad = fila.querySelector('.cantidad');
                    inputCantidad.value = parseInt(inputCantidad.value) + 1;
                    calcularSubtotal(fila);
                    calcularTotal();
                    return;
                }
            }

            // Crear nueva fila si el producto no existe
            const nuevaFila = document.createElement('tr');
            nuevaFila.classList.add('fila-producto');
            nuevaFila.innerHTML = `
            <td>
                <input type="hidden" name="productos[]" value="${producto.idproducto}">
                ${producto.nombre} (${producto.codigo || 'S/COD'})
            </td>
            <td>
                <input type="number" class="form-control cantidad" name="cantidades[]" 
                    min="1" value="1" required>
            </td>
            <td>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">$</span>
                    </div>
                    <input type="number" class="form-control precio" name="precios[]" 
                        step="0.01" min="0.01" value="${parseFloat(producto.preciocompra).toFixed(2)}" required>
                </div>
            </td>
            <td class="text-right">
                <span class="subtotal">${parseFloat(producto.preciocompra).toFixed(2)}</span>
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
            });

            // Insertar en la tabla
            tbody.appendChild(nuevaFila);
            calcularTotal();
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
        document.getElementById('btn-buscar-producto').addEventListener('click', function() {
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
        });

        // Permitir buscar con Enter
        document.getElementById('buscar-producto').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('btn-buscar-producto').click();
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
            const mainFields = ['fechacompra', 'estado'];
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
                    this.submit();
                }
            });
        });
    });
</script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>