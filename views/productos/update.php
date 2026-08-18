<?php
require_once __DIR__ . '/../../controllers/productos/ProductoController.php';
require_once __DIR__ . '/../../controllers/categoria/CategoriaController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!$authService->tienePermisoNombre($idusuario, 'productos') && !$authService->esAdministrador($idusuario)) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

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

// Obtener categorías desde la base de datos
$categoriaController = new CategoriaController();
$categorias = $categoriaController->index(true);

$skip_datatables = true; // Evita cargar DataTables/pdfmake/vfs_fonts (~2.8MB)
$module_scripts = ['productos/vista-previa-producto', 'productos/update-productos'];
include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Editar Producto</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/productos"><i class="fas fa-boxes"></i> Productos</a></li>
                    <li class="breadcrumb-item active">Editar Producto</li>
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
                <!-- form start -->
                <form action="<?= $URL; ?>controllers/productos/actualizar_producto.php" method="POST" enctype="multipart/form-data" id="formEditarProducto">
                    <?= csrfField() ?>
                    <input type="hidden" name="idproducto" value="<?= $producto['idproducto']; ?>">

                    <!-- Información Básica -->
                    <div class="card card-outline card-warning mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Información Básica</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Categoría -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="idcategoria">Categoría <span class="text-danger">*</span></label>
                                        <select class="form-control select2" id="idcategoria" name="idcategoria" required>
                                            <option value="">Seleccione una categoría</option>
                                            <?php foreach ($categorias as $categoria) : ?>
                                                <option value="<?= $categoria['idcategoria']; ?>"
                                                    <?= $producto['idcategoria'] == $categoria['idcategoria'] ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($categoria['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
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
                                                placeholder="Ingrese el código del producto" value="<?= htmlspecialchars($producto['codigo'] ?? ''); ?>">
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
                                                placeholder="Ingrese el nombre del producto"
                                                value="<?= htmlspecialchars($producto['nombre']); ?>" required>
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
                                                placeholder="Ingrese una descripción detallada del producto"><?= htmlspecialchars($producto['descripcion'] ?? ''); ?></textarea>
                                        </div>
                                        <small class="form-text text-muted">Características, usos, dimensiones u otra información relevante</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información de Precios -->
                    <div class="card card-outline card-warning mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Información de Precios</h3>
                        </div>
                        <div class="card-body">
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
                                                step="0.01" min="0" placeholder="0.00"
                                                value="<?= number_format($producto['preciocompra'], 2, '.', ''); ?>" required>
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
                                                step="0.01" min="0" placeholder="0.00"
                                                value="<?= number_format($producto['precioventa'], 2, '.', ''); ?>" required>
                                        </div>
                                        <small class="form-text text-muted">Precio al que se vende el producto</small>
                                        <div id="precio-feedback" aria-live="polite"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información de Inventario -->
                    <div class="card card-outline card-warning mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Información de Inventario</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Stock Actual -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="stock">Stock Actual <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-boxes"></i></span>
                                            </div>
                                            <input type="number" class="form-control" id="stock" name="stock"
                                                min="0" value="<?= $producto['stock']; ?>" required>
                                        </div>
                                        <small class="form-text text-muted">Cantidad actual disponible del producto</small>
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
                                                min="0" value="<?= $producto['stockminimo']; ?>">
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
                                                min="0" value="<?= $producto['stockmaximo'] ?? ''; ?>">
                                        </div>
                                        <small class="form-text text-muted">Cantidad máxima recomendada (opcional)</small>
                                    </div>
                                </div>
                            </div>

                            <div id="stock-validation-feedback" aria-live="polite"></div>
                        </div>
                    </div>

                    <!-- Imagen y Estado -->
                    <div class="card card-outline card-warning mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Imagen y Estado</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Imagen actual / nueva -->
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label for="imagen">Nueva Imagen del Producto</label>
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
                                        <small class="form-text text-muted">Formatos permitidos: JPG, PNG, GIF, WEBP. Máximo 2MB. Deje vacío para mantener la imagen actual</small>
                                    </div>

                                    <div class="row">
                                        <div class="col-6">
                                            <label class="d-block">Imagen Actual</label>
                                            <?php if (!empty($producto['imagen'])): ?>
                                                <img src="<?= $URL; ?>public/uploads/productos/<?= $producto['imagen']; ?>"
                                                    alt="Imagen actual" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                                            <?php else: ?>
                                                <img src="<?= $URL; ?>public/uploads/productos/producto_default.png"
                                                    alt="Imagen por defecto" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                                            <?php endif; ?>
                                        </div>
                                        <!-- Vista previa de imagen nueva -->
                                        <div class="col-6" id="preview-container" style="display: none;">
                                            <label class="d-block">Vista Previa Nueva</label>
                                            <img id="preview-image" src="#" alt="Vista previa" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                                        </div>
                                    </div>
                                </div>

                                <!-- Estado -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="estado">Estado</label>
                                        <select class="form-control select2" id="estado" name="estado">
                                            <option value="1" <?= $producto['estado'] == 1 ? 'selected' : ''; ?>>Activo</option>
                                            <option value="0" <?= $producto['estado'] == 0 ? 'selected' : ''; ?>>Inactivo</option>
                                        </select>
                                        <small class="form-text text-muted">Un producto inactivo no aparecerá en las ventas</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <small class="text-muted"><i class="far fa-calendar-plus mr-1"></i> Creado: <?= isset($producto['fechacreacion']) ? date('d/m/Y H:i', strtotime($producto['fechacreacion'])) : 'No disponible'; ?></small>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted"><i class="far fa-calendar-check mr-1"></i> Última actualización: <?= isset($producto['fechaactualizacion']) ? date('d/m/Y H:i', strtotime($producto['fechaactualizacion'])) : 'No disponible'; ?></small>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="row">
                                <div class="col-12 col-sm-auto mb-2 mb-sm-0">
                                    <button type="submit" class="btn btn-warning w-100">
                                        <i class="fas fa-save mr-1"></i> Actualizar Producto
                                    </button>
                                </div>
                                <div class="col-12 col-sm-auto mb-2 mb-sm-0">
                                    <a href="<?= $URL; ?>views/productos/show.php?id=<?= $producto['idproducto']; ?>" class="btn btn-info w-100">
                                        <i class="fas fa-eye mr-1"></i> Ver Detalles
                                    </a>
                                </div>
                                <div class="col-12 col-sm-auto">
                                    <a href="<?= $URL; ?>views/productos" class="btn btn-secondary w-100">
                                        <i class="fas fa-times mr-1"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Nueva columna para la guía (4/12) -->
            <div class="col-md-4 mb-3">
                <div class="sidebar-sticky">
                    <?php
                    $vistaPreviaImagen = !empty($producto['imagen']) ? $URL . 'public/uploads/productos/' . $producto['imagen'] : $URL . 'public/uploads/productos/producto_default.png';
                    $vistaPreviaNombre = htmlspecialchars($producto['nombre']);
                    $vistaPreviaCategoria = htmlspecialchars($producto['categoria_nombre'] ?? '—');
                    $vistaPreviaCodigo = htmlspecialchars($producto['codigo'] ?? '') !== '' ? htmlspecialchars($producto['codigo']) : '—';
                    $vistaPreviaPrecio = $appCurrency . ' ' . number_format($producto['precioventa'], 2);
                    $vistaPreviaStockLabel = 'Stock Actual';
                    $vistaPreviaStock = $producto['stock'] . ' unidades';
                    $vistaPreviaEstado = $producto['estado'] == 1 ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-secondary">Inactivo</span>';
                    include 'partials/vista_previa.php';
                    ?>

                    <div class="card card-outline card-secondary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Guía para Editar Productos</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Minimizar">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <h6 class="mb-1"><i class="fas fa-asterisk text-danger mr-2 fa-xs"></i>Campos obligatorios</h6>
                                    <p class="mb-0 text-muted small">Los campos marcados con <span class="text-danger">*</span> son obligatorios. Los campos sin modificar mantendrán su valor actual.</p>
                                </li>
                                <li class="list-group-item">
                                    <h6 class="mb-1"><i class="fas fa-money-bill-wave text-muted mr-2"></i>Margen de ganancia</h6>
                                    <p class="mb-0 text-muted small">Si actualiza el precio de compra, considere también actualizar el de venta para mantener un margen adecuado.</p>
                                </li>
                                <li class="list-group-item">
                                    <h6 class="mb-1"><i class="fas fa-warehouse text-muted mr-2"></i>Ajustes de inventario</h6>
                                    <p class="mb-0 text-muted small">Para ajustes grandes de stock, use el módulo de compras en lugar de editar directamente la cantidad.</p>
                                </li>
                                <li class="list-group-item">
                                    <h6 class="mb-1"><i class="fas fa-image text-muted mr-2"></i>Imagen del producto</h6>
                                    <p class="mb-0 text-muted small">Si sube una nueva imagen, reemplazará la actual. Formatos JPG, PNG, GIF o WEBP, máximo 2MB.</p>
                                </li>
                                <li class="list-group-item">
                                    <h6 class="mb-1 text-danger"><i class="fas fa-exclamation-triangle mr-2"></i>Importante</h6>
                                    <p class="mb-0 text-muted small">Un producto inactivo no aparecerá en las búsquedas ni podrá venderse.</p>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</section>
<!-- /.content -->

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>