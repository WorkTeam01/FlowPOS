<?php
require_once __DIR__ . '/../../controllers/categoria/CategoriaController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Obtener categorías desde la base de datos

$categoController = new CategoriaController();
$categorias = $categoController->index();

// Verificar si el usuario tiene acceso al módulo
if (!($authService->tienePermisoNombre($idusuario, 'productos')) && !($authService->esAdministrador($idusuario))) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Incluir el encabezado
$skip_datatables = true; // Esta vista no usa tabla; evita cargar DataTables/pdfmake/vfs_fonts (~2.8MB)
include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Crear Producto</h1>
                <p class="text-muted">Complete el formulario para registrar un nuevo producto en el sistema</p>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/productos"><i class="fas fa-boxes"></i> Productos</a></li>
                    <li class="breadcrumb-item active">Crear Producto</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <!-- Columna del formulario (8/12) -->
            <div class="col-md-8">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>Formulario de Nuevo Producto</h3>
                    </div>
                    <!-- /.card-header -->
                    <!-- form start -->
                    <form action="<?= $URL; ?>controllers/productos/crear_producto.php" method="POST" enctype="multipart/form-data" id="formCrearProducto">
                        <?= csrfField() ?>
                        <div class="card-body">
                            <!-- Instrucciones -->
                            <div class="callout callout-info mb-4">
                                <h5><i class="fas fa-info-circle"></i> Información importante:</h5>
                                <p>Los campos marcados con <span class="text-danger">*</span> son obligatorios. Asegúrese de completar todos los datos requeridos.</p>
                            </div>

                            <!-- Sección de Información Básica -->
                            <h5 class="border-bottom border-primary pb-2 mb-3"><i class="fas fa-info-circle mr-2"></i>Información Básica</h5>

                            <div class="row">
                                <!-- Categoría -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="idcategoria">Categoría <span class="text-danger">*</span></label>
                                        <select class="form-control select2" id="idcategoria" name="idcategoria" required>
                                            <option value="">Seleccione una categoría</option>
                                            <?php
                                            foreach ($categorias as $categoria) :
                                                if ($categoria['estado'] == 1) : // Solo mostrar categorías activas
                                            ?>
                                                    <option value="<?= $categoria['idcategoria']; ?>">
                                                        <?= htmlspecialchars($categoria['nombre']); ?>
                                                    </option>
                                            <?php
                                                endif;
                                            endforeach;
                                            ?>
                                        </select>
                                        <small class="form-text text-muted">Seleccione la categoría a la que pertenece el producto</small>
                                    </div>
                                </div>

                                <!-- Código -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="codigo">Código</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                            </div>
                                            <input type="text" class="form-control" id="codigo" name="codigo"
                                                placeholder="Ingrese el código del producto">
                                        </div>
                                        <small class="form-text text-muted">Opcional - Código único del producto (SKU, código de barras, etc.)</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Nombre -->
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="nombre">Nombre del Producto <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                            </div>
                                            <input type="text" class="form-control" id="nombre" name="nombre"
                                                placeholder="Ingrese el nombre del producto" required>
                                        </div>
                                        <small class="form-text text-muted">Nombre completo y descriptivo del producto</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Descripción -->
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="descripcion">Descripción</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-align-left"></i></span>
                                            </div>
                                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                                                placeholder="Ingrese una descripción detallada del producto"></textarea>
                                        </div>
                                        <small class="form-text text-muted">Características, usos, dimensiones u otra información relevante</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección de Precios -->
                            <h5 class="border-bottom border-primary pb-2 mb-3 mt-4"><i class="fas fa-money-bill-wave mr-2"></i>Información de Precios</h5>

                            <div class="row">
                                <!-- Precio de Compra -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="preciocompra">Precio de Compra <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><?= $appCurrency ?></span>
                                            </div>
                                            <input type="number" class="form-control" id="preciocompra" name="preciocompra"
                                                step="0.01" min="0" placeholder="0.00" required>
                                        </div>
                                        <small class="form-text text-muted">Precio al que se adquiere el producto (costo)</small>
                                    </div>
                                </div>

                                <!-- Precio de Venta -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="precioventa">Precio de Venta <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><?= $appCurrency ?></span>
                                            </div>
                                            <input type="number" class="form-control" id="precioventa" name="precioventa"
                                                step="0.01" min="0" placeholder="0.00" required>
                                        </div>
                                        <small class="form-text text-muted">Precio al que se vende el producto</small>
                                        <div id="precio-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección de Inventario -->
                            <h5 class="border-bottom border-primary pb-2 mb-3 mt-4"><i class="fas fa-warehouse mr-2"></i>Información de Inventario</h5>

                            <div class="row">
                                <!-- Stock Inicial -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="stock">Stock Inicial <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-boxes"></i></span>
                                            </div>
                                            <input type="number" class="form-control" id="stock" name="stock"
                                                min="0" value="0" required>
                                        </div>
                                        <small class="form-text text-muted">Cantidad inicial disponible del producto</small>
                                    </div>
                                </div>

                                <!-- Stock Mínimo -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="stockminimo">Stock Mínimo <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-level-down-alt"></i></span>
                                            </div>
                                            <input type="number" class="form-control" id="stockminimo" name="stockminimo"
                                                min="0" value="5">
                                        </div>
                                        <small class="form-text text-muted">Cantidad mínima para generar alertas de reposición</small>
                                    </div>
                                </div>

                                <!-- Stock Máximo -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="stockmaximo">Stock Máximo</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-level-up-alt"></i></span>
                                            </div>
                                            <input type="number" class="form-control" id="stockmaximo" name="stockmaximo"
                                                min="0">
                                        </div>
                                        <small class="form-text text-muted">Cantidad máxima recomendada (opcional)</small>
                                    </div>
                                </div>
                            </div>

                            <div id="stock-validation-feedback"></div>

                            <!-- Sección de Imagen -->
                            <h5 class="border-bottom border-primary pb-2 mb-3 mt-4"><i class="fas fa-image mr-2"></i>Imagen del Producto</h5>

                            <div class="row">
                                <!-- Imagen -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="imagen">Imagen del Producto</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-image"></i></span>
                                            </div>
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="imagen" name="imagen"
                                                    accept="image/*">
                                                <label class="custom-file-label" for="imagen">Seleccionar archivo</label>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">Formatos permitidos: JPG, PNG, GIF, WEBP. Máximo 2MB</small>
                                    </div>
                                </div>

                                <!-- Estado -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="estado">Estado</label>
                                        <select class="form-control select2" id="estado" name="estado">
                                            <option value="1" selected>Activo</option>
                                            <option value="0">Inactivo</option>
                                        </select>
                                        <small class="form-text text-muted">Un producto inactivo no aparecerá en las ventas</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Vista previa de imagen -->
                            <div class="row" id="preview-container" style="display: none;">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Vista Previa:</label><br>
                                        <div class="text-center">
                                            <img id="preview-image" src="#" alt="Vista previa" class="img-thumbnail" style="max-width: 300px; max-height: 300px;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <div class="row">
                                <div class="col-12 col-sm-auto">
                                    <button type="submit" class="btn btn-primary w-100 mb-2 mb-sm-0">
                                        <i class="fas fa-save mr-2"></i> Guardar Producto
                                    </button>
                                </div>
                                <div class="col-12 col-sm-auto">
                                    <a href="<?= $URL; ?>views/productos" class="btn btn-secondary w-100">
                                        <i class="fas fa-times mr-2"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- /.card -->
            </div>

            <!-- Nueva columna para la guía (4/12) -->
            <div class="col-md-4">
                <!-- Tarjeta de guía principal -->
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-question-circle mr-2"></i>Guía de Producto</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="text-info"><i class="fas fa-info-circle"></i> Cómo completar este formulario</h5>
                        <div class="callout callout-info">
                            <p>Complete todos los campos marcados con <span class="text-danger">*</span> ya que son obligatorios para registrar un nuevo producto.</p>
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
                                        <p><strong>Categoría:</strong> Seleccione la categoría a la que pertenece el producto.</p>
                                        <div class="alert alert-secondary">
                                            <small><i class="fas fa-lightbulb mr-1"></i> Consejo: Una correcta categorización facilita la búsqueda y organización de los productos.</small>
                                        </div>
                                        <p><strong>Código:</strong> Puede ser un código de barras, SKU o cualquier identificador único para el producto.</p>
                                        <ul class="pl-3">
                                            <li><small>Es opcional pero recomendable para mejor organización</small></li>
                                            <li><small>Útil para búsquedas rápidas e inventarios</small></li>
                                        </ul>
                                        <p><strong>Nombre:</strong> Debe ser descriptivo y contener información clave del producto.</p>
                                        <p><strong>Descripción:</strong> Incluya características, dimensiones, materiales, usos o cualquier información relevante.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección de Precios -->
                            <div class="card">
                                <div class="card-header" id="headingPrices">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed text-info" type="button" data-toggle="collapse" data-target="#collapsePrices" aria-expanded="false" aria-controls="collapsePrices">
                                            <i class="fas fa-money-bill-wave mr-2"></i>Información de Precios
                                        </button>
                                    </h2>
                                </div>
                                <div id="collapsePrices" class="collapse" aria-labelledby="headingPrices" data-parent="#accordionHelp">
                                    <div class="card-body">
                                        <p><strong>Precio de Compra:</strong> Es el costo al que se adquiere el producto. Es importante para cálculos de rentabilidad.</p>
                                        <p><strong>Precio de Venta:</strong> Es el precio al que se ofrecerá el producto a los clientes.</p>
                                        <div class="alert alert-warning">
                                            <small><i class="fas fa-exclamation-triangle mr-1"></i> Importante: El precio de venta debe ser mayor al precio de compra para garantizar un margen de ganancia.</small>
                                        </div>
                                        <div class="alert alert-secondary">
                                            <small><i class="fas fa-lightbulb mr-1"></i> Consejo: Para calcular un precio de venta adecuado, considere añadir al menos un 20-30% sobre el precio de compra.</small>
                                        </div>
                                        <p><strong>Ejemplo de cálculo:</strong></p>
                                        <ul>
                                            <li><small>Precio de compra: <?= $appCurrency ?> 100</small></li>
                                            <li><small>Margen deseado: 30%</small></li>
                                            <li><small>Precio de venta = 100 + (100 × 0.3) = <?= $appCurrency ?> 130</small></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección de Inventario -->
                            <div class="card">
                                <div class="card-header" id="headingInventory">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed text-info" type="button" data-toggle="collapse" data-target="#collapseInventory" aria-expanded="false" aria-controls="collapseInventory">
                                            <i class="fas fa-warehouse mr-2"></i>Información de Inventario
                                        </button>
                                    </h2>
                                </div>
                                <div id="collapseInventory" class="collapse" aria-labelledby="headingInventory" data-parent="#accordionHelp">
                                    <div class="card-body">
                                        <p><strong>Stock Inicial:</strong> Cantidad con la que se empieza a controlar el inventario del producto.</p>
                                        <p><strong>Stock Mínimo:</strong> Nivel mínimo de existencias antes de generar alertas para reposición.</p>
                                        <div class="alert alert-secondary">
                                            <small><i class="fas fa-lightbulb mr-1"></i> Consejo: Establezca el stock mínimo considerando el tiempo de reposición y la demanda promedio.</small>
                                        </div>
                                        <p><strong>Stock Máximo:</strong> Nivel máximo recomendado para evitar sobrestock.</p>
                                        <div class="alert alert-warning">
                                            <small><i class="fas fa-exclamation-triangle mr-1"></i> Importante: El stock máximo debe ser mayor que el stock mínimo y adecuado a la capacidad de almacenamiento.</small>
                                        </div>
                                        <p><strong>Recomendaciones:</strong></p>
                                        <ul>
                                            <li><small>Para productos de alta rotación: stock mínimo más alto</small></li>
                                            <li><small>Para productos perecederos: stock máximo más bajo</small></li>
                                            <li><small>Para productos voluminosos: considerar espacio de almacenamiento</small></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección de Imagen -->
                            <div class="card">
                                <div class="card-header" id="headingImage">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed text-info" type="button" data-toggle="collapse" data-target="#collapseImage" aria-expanded="false" aria-controls="collapseImage">
                                            <i class="fas fa-image mr-2"></i>Imagen del Producto
                                        </button>
                                    </h2>
                                </div>
                                <div id="collapseImage" class="collapse" aria-labelledby="headingImage" data-parent="#accordionHelp">
                                    <div class="card-body">
                                        <p><strong>Imagen:</strong> Ayuda a identificar visualmente el producto en el sistema.</p>
                                        <div class="alert alert-secondary">
                                            <small><i class="fas fa-lightbulb mr-1"></i> Consejo: Utilice imágenes claras, con buena iluminación y preferiblemente sobre fondo blanco.</small>
                                        </div>
                                        <p><strong>Recomendaciones:</strong></p>
                                        <ul>
                                            <li><small>Dimensiones recomendadas: 600×600 píxeles</small></li>
                                            <li><small>Formatos permitidos: JPG, PNG, GIF, WEBP</small></li>
                                            <li><small>Tamaño máximo: 2MB</small></li>
                                        </ul>
                                        <p><strong>Estado:</strong> Determina si el producto estará disponible para venta.</p>
                                        <ul>
                                            <li><small><strong>Activo:</strong> Aparecerá en las búsquedas y puede venderse</small></li>
                                            <li><small><strong>Inactivo:</strong> No aparecerá en las búsquedas ni podrá venderse</small></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de ejemplo de producto bien configurado -->
                <div class="card card-success mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-check-circle mr-2"></i>Ejemplo de Producto</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <img src="<?= $URL; ?>public/uploads/productos/producto_default.png" alt="Ejemplo de producto" class="img-fluid rounded" style="max-height: 150px;">
                        </div>
                        <h5 class="text-center">Agua Mineral 500ml</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><strong>Categoría:</strong></span>
                                <span>Bebidas</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><strong>Código:</strong></span>
                                <span>BEB001</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><strong>Precio Compra:</strong></span>
                                <span><?= $appCurrency ?> 3.00</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><strong>Precio Venta:</strong></span>
                                <span><?= $appCurrency ?> 5.00</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><strong>Stock Mínimo:</strong></span>
                                <span>10 unidades</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><strong>Stock Máximo:</strong></span>
                                <span>100 unidades</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Tarjeta de consejos para la creación de productos -->
                <div class="card card-warning mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-lightbulb mr-2"></i>Consejos y Buenas Prácticas</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item">
                                <i class="fas fa-tag text-warning mr-2"></i> <strong>Nombres descriptivos</strong>
                                <p class="mb-0 small">Use nombres claros que incluyan características clave como marca, modelo y tamaño.</p>
                            </div>
                            <div class="list-group-item">
                                <i class="fas fa-barcode text-info mr-2"></i> <strong>Códigos consistentes</strong>
                                <p class="mb-0 small">Establezca un sistema coherente para asignar códigos (ej: categoría + número secuencial).</p>
                            </div>
                            <div class="list-group-item">
                                <i class="fas fa-image text-success mr-2"></i> <strong>Imágenes de calidad</strong>
                                <p class="mb-0 small">Las buenas imágenes facilitan la identificación rápida de productos.</p>
                            </div>
                            <div class="list-group-item">
                                <i class="fas fa-balance-scale text-primary mr-2"></i> <strong>Precios adecuados</strong>
                                <p class="mb-0 small">Verifique que los precios ofrezcan un margen de ganancia sostenible.</p>
                            </div>
                            <div class="list-group-item">
                                <i class="fas fa-search text-danger mr-2"></i> <strong>Evite duplicados</strong>
                                <p class="mb-0 small">Antes de crear un producto, verifique que no exista uno similar en el sistema.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<!-- Script para validaciones y vista previa -->
<script>
    $(document).ready(function() {
        // Inicializar Select2
        initializeSelect2();

        // Actualizar etiqueta del archivo seleccionado
        $('.custom-file-input').on('change', function() {
            let fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').addClass("selected").html(fileName);

            // Mostrar vista previa de la imagen
            if (this.files && this.files[0]) {
                let reader = new FileReader();
                reader.onload = function(e) {
                    $('#preview-image').attr('src', e.target.result);
                    $('#preview-container').show();
                }
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Validación de precios
        $('#precioventa, #preciocompra').on('input', function() {
            let precioCompra = parseFloat($('#preciocompra').val()) || 0;
            let precioVenta = parseFloat($('#precioventa').val()) || 0;

            // Validar que el precio de venta sea mayor al de compra
            if (precioVenta > 0 && precioCompra > 0) {
                if (precioVenta < precioCompra) {
                    $('#precio-feedback').html('<div class="text-danger mt-1"><i class="fas fa-exclamation-triangle"></i> El precio de venta no puede ser menor al precio de compra</div>');
                } else {
                    // Calcular y mostrar el margen de ganancia
                    let ganancia = precioVenta - precioCompra;
                    let margenPorcentaje = (ganancia / precioCompra) * 100;

                    let mensajeClase = 'text-success';
                    let mensajeIcono = 'fas fa-check-circle';

                    if (margenPorcentaje < 10) {
                        mensajeClase = 'text-danger';
                        mensajeIcono = 'fas fa-exclamation-circle';
                    } else if (margenPorcentaje < 20) {
                        mensajeClase = 'text-warning';
                        mensajeIcono = 'fas fa-exclamation-triangle';
                    }

                    $('#precio-feedback').html(
                        `<div class="${mensajeClase} mt-1">
                            <i class="${mensajeIcono}"></i> 
                            Margen de ganancia: ${margenPorcentaje.toFixed(2)}% 
                            (<?= $appCurrency ?> ${ganancia.toFixed(2)})
                        </div>`
                    );
                }
            } else {
                $('#precio-feedback').html('');
            }
        });

        // Validación de stock mínimo/máximo
        $('#stockminimo, #stockmaximo').on('input', function() {
            let stockMinimo = parseInt($('#stockminimo').val()) || 0;
            let stockMaximo = parseInt($('#stockmaximo').val()) || 0;

            // Solo validar si ambos tienen valores y stockmaximo no está vacío
            if (stockMaximo > 0) {
                if (stockMaximo < stockMinimo) {
                    $('#stock-validation-feedback').html(
                        `<div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> 
                            El stock máximo (${stockMaximo}) no puede ser menor al stock mínimo (${stockMinimo})
                        </div>`
                    );
                } else {
                    $('#stock-validation-feedback').html(
                        `<div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> 
                            Configuración de stock válida. Rango: ${stockMinimo} - ${stockMaximo} unidades
                        </div>`
                    );
                }
            } else {
                $('#stock-validation-feedback').html('');
            }
        });

        // Validación del formulario antes de enviar
        $('#formCrearProducto').on('submit', function(e) {
            let precioCompra = parseFloat($('#preciocompra').val()) || 0;
            let precioVenta = parseFloat($('#precioventa').val()) || 0;
            let stockMinimo = parseInt($('#stockminimo').val()) || 0;
            let stockMaximo = parseInt($('#stockmaximo').val()) || 0;

            // Validar precios
            if (precioVenta < precioCompra) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error en Precios',
                    text: 'El precio de venta no puede ser menor al precio de compra',
                    confirmButtonColor: '#3085d6'
                });
                return false;
            }

            // Validar stock
            if (stockMaximo > 0 && stockMaximo < stockMinimo) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error en Stock',
                    text: 'El stock máximo no puede ser menor al stock mínimo',
                    confirmButtonColor: '#3085d6'
                });
                return false;
            }

            // Validar categoría seleccionada
            if ($('#idcategoria').val() === '') {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Categoría Requerida',
                    text: 'Debe seleccionar una categoría para el producto',
                    confirmButtonColor: '#3085d6'
                });
                return false;
            }

            // Si todo es válido, mostrar mensaje de carga
            Swal.fire({
                title: 'Guardando producto...',
                html: 'Por favor espere mientras se guarda la información',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        });
    });
</script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>