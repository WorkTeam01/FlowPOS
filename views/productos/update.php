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

// Obtener categorías desde la base de datos
$categoriaController = new CategoriaController();
$categorias = $categoriaController->index(true);
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1>Editar Producto</h1>
                <p class="text-muted">
                    Modificando datos de: <strong><?= htmlspecialchars($producto['nombre']); ?></strong>
                </p>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/productos"><i class="fas fa-boxes"></i> Productos</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/productos/show.php?id=<?= $producto['idproducto']; ?>"><i class="fas fa-box"></i> Ver Producto</a></li>
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
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-edit mr-2"></i>Formulario de Edición de Producto</h3>
                    </div>
                    <!-- /.card-header -->
                    <!-- form start -->
                    <form action="<?= $URL; ?>controllers/productos/actualizar_producto.php" method="POST" enctype="multipart/form-data" id="formEditarProducto">
                        <input type="hidden" name="idproducto" value="<?= $producto['idproducto']; ?>">
                        <div class="card-body">
                            <!-- Instrucciones -->
                            <div class="callout callout-warning mb-4">
                                <h5><i class="fas fa-info-circle"></i> Editando producto ID: <?= $producto['idproducto']; ?></h5>
                                <p>Los campos marcados con <span class="text-danger">*</span> son obligatorios. Los campos sin modificar mantendrán su valor actual.</p>
                            </div>

                            <!-- Sección de Información Básica -->
                            <h5 class="border-bottom border-warning pb-2 mb-3"><i class="fas fa-info-circle mr-2"></i>Información Básica</h5>

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

                            <!-- Sección de Precios -->
                            <h5 class="border-bottom border-warning pb-2 mb-3 mt-4"><i class="fas fa-money-bill-wave mr-2"></i>Información de Precios</h5>

                            <div class="row">
                                <!-- Precio de Compra -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="preciocompra">Precio de Compra <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Bs</span>
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
                                                <span class="input-group-text">Bs</span>
                                            </div>
                                            <input type="number" class="form-control" id="precioventa" name="precioventa"
                                                step="0.01" min="0" placeholder="0.00"
                                                value="<?= number_format($producto['precioventa'], 2, '.', ''); ?>" required>
                                        </div>
                                        <small class="form-text text-muted">Precio al que se vende el producto</small>
                                        <div id="precio-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección de Inventario -->
                            <h5 class="border-bottom border-warning pb-2 mb-3 mt-4"><i class="fas fa-warehouse mr-2"></i>Información de Inventario</h5>

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

                            <div id="stock-validation-feedback"></div>

                            <!-- Sección de Imagen -->
                            <h5 class="border-bottom border-warning pb-2 mb-3 mt-4"><i class="fas fa-image mr-2"></i>Imagen del Producto</h5>

                            <div class="row">
                                <!-- Imagen -->
                                <div class="col-md-6">
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
                                        <small class="form-text text-muted">Formatos permitidos: JPG, PNG, GIF, WEBP. Máximo 2MB</small>
                                        <small class="form-text text-muted">Deje vacío para mantener la imagen actual</small>
                                    </div>
                                </div>

                                <!-- Estado -->
                                <div class="col-md-6">
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

                            <!-- Imagen Actual -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Imagen Actual</label><br>
                                        <div class="text-center">
                                            <?php if (isset($producto['imagen']) && !empty($producto['imagen'])): ?>
                                                <img src="<?= $URL; ?>public/uploads/productos/<?= $producto['imagen']; ?>"
                                                    alt="Imagen actual" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                                            <?php else: ?>
                                                <img src="<?= $URL; ?>public/uploads/productos/producto_default.png"
                                                    alt="Imagen por defecto" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                                            <?php endif; ?>
                                        </div>
                                        <small class="form-text text-muted text-center">Imagen actual del producto</small>
                                    </div>
                                </div>

                                <!-- Vista previa de imagen nueva -->
                                <div class="col-md-6" id="preview-container" style="display: none;">
                                    <div class="form-group">
                                        <label>Vista Previa Nueva Imagen</label><br>
                                        <div class="text-center">
                                            <img id="preview-image" src="#" alt="Vista previa" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                                        </div>
                                        <small class="form-text text-muted text-center">Nueva imagen seleccionada</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Información del Sistema -->
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="card card-outline card-secondary">
                                        <div class="card-header">
                                            <h3 class="card-title">Información del Sistema</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <strong><i class="far fa-calendar-plus mr-1"></i> Fecha de Creación:</strong>
                                                    <p><?= isset($producto['fechacreacion']) ? date('d/m/Y H:i', strtotime($producto['fechacreacion'])) : 'No disponible'; ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <strong><i class="far fa-calendar-check mr-1"></i> Última Actualización:</strong>
                                                    <p><?= isset($producto['fechaactualizacion']) ? date('d/m/Y H:i', strtotime($producto['fechaactualizacion'])) : 'No disponible'; ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <div class="row">
                                <div class="col-12 col-sm-auto">
                                    <button type="submit" class="btn btn-warning w-100 mb-2 mb-sm-0">
                                        <i class="fas fa-save mr-2"></i> Actualizar Producto
                                    </button>
                                </div>
                                <div class="col-12 col-sm-auto">
                                    <a href="<?= $URL; ?>views/productos/show.php?id=<?= $producto['idproducto']; ?>" class="btn btn-info w-100 mb-2 mb-sm-0">
                                        <i class="fas fa-eye mr-2"></i> Ver Detalles
                                    </a>
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
                        <h3 class="card-title"><i class="fas fa-question-circle mr-2"></i>Guía de Edición</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="text-info"><i class="fas fa-info-circle"></i> Cómo editar este producto</h5>
                        <div class="callout callout-warning">
                            <p>Solo modifique los campos que necesite actualizar. Los campos sin cambios mantendrán su valor actual.</p>
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
                                        <p><strong>Categoría:</strong> Puede cambiar la categoría del producto si fue clasificado incorrectamente.</p>
                                        <p><strong>Código:</strong> Puede modificarlo, pero asegúrese de que siga siendo único en el sistema.</p>
                                        <p><strong>Nombre:</strong> El nombre debe ser descriptivo y contener información clave del producto.</p>
                                        <div class="alert alert-secondary">
                                            <small><i class="fas fa-lightbulb mr-1"></i> Consejo: Mantener un nombre consistente facilita las búsquedas y reportes.</small>
                                        </div>
                                        <p><strong>Descripción:</strong> Puede agregar o modificar detalles sobre características, dimensiones, etc.</p>
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
                                        <p><strong>Precio de Compra:</strong> Actualice si el costo del producto ha cambiado.</p>
                                        <p><strong>Precio de Venta:</strong> Ajuste si necesita modificar el precio de venta al cliente.</p>
                                        <div class="alert alert-warning">
                                            <small><i class="fas fa-exclamation-triangle mr-1"></i> Importante: El precio de venta debe ser mayor al precio de compra para mantener un margen de ganancia.</small>
                                        </div>
                                        <div class="alert alert-secondary">
                                            <small><i class="fas fa-lightbulb mr-1"></i> Consejo: Si actualiza el precio de compra, considere también actualizar el precio de venta para mantener un margen adecuado.</small>
                                        </div>
                                        <p><strong>Nota:</strong> Cambiar los precios de un producto no afectará a las ventas ya realizadas.</p>
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
                                        <p><strong>Stock Actual:</strong> Puede ajustar manualmente la cantidad disponible.</p>
                                        <div class="alert alert-warning">
                                            <small><i class="fas fa-exclamation-triangle mr-1"></i> Importante: Modificar el stock manualmente debe hacerse con cuidado. Idealmente, el stock se ajusta mediante compras y ventas.</small>
                                        </div>
                                        <p><strong>Stock Mínimo:</strong> Nivel mínimo para generar alertas de reposición.</p>
                                        <p><strong>Stock Máximo:</strong> Nivel máximo recomendado para evitar sobrestock.</p>
                                        <div class="alert alert-secondary">
                                            <small><i class="fas fa-lightbulb mr-1"></i> Consejo: Ajuste el stock mínimo según la demanda y tiempos de reposición actuales.</small>
                                        </div>
                                        <?php if ($producto['stock'] < $producto['stockminimo']): ?>
                                            <div class="alert alert-danger">
                                                <small><i class="fas fa-exclamation-circle mr-1"></i> Alerta: El stock actual está por debajo del mínimo. Considere realizar una compra pronto.</small>
                                            </div>
                                        <?php endif; ?>
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
                                        <p><strong>Imagen:</strong> Si sube una nueva imagen, reemplazará la actual.</p>
                                        <div class="alert alert-info">
                                            <small><i class="fas fa-info-circle mr-1"></i> Si no selecciona ninguna imagen, se mantendrá la imagen actual del producto.</small>
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

                <!-- Tarjeta de resumen del producto -->
                <div class="card card-success mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-box-open mr-2"></i>Resumen del Producto</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-hashtag text-primary mr-2"></i>ID de Producto</span>
                                <span class="badge badge-primary"><?= $producto['idproducto']; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-tag text-info mr-2"></i>Categoría Actual</span>
                                <span class="badge badge-info"><?= htmlspecialchars($producto['categoria_nombre'] ?? 'Sin categoría'); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-boxes text-success mr-2"></i>Stock Actual</span>
                                <span class="badge <?= $producto['stock'] < $producto['stockminimo'] ? 'badge-danger' : 'badge-success'; ?>">
                                    <?= $producto['stock']; ?> unidades
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-toggle-on text-warning mr-2"></i>Estado Actual</span>
                                <?php if ($producto['estado'] == 1): ?>
                                    <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactivo</span>
                                <?php endif; ?>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="far fa-calendar-plus mr-2"></i>Fecha de Registro</span>
                                <span class="badge badge-secondary"><?= date('d/m/Y', strtotime($producto['fechacreacion'])); ?></span>
                            </li>
                            <?php if (isset($producto['fechaactualizacion']) && !empty($producto['fechaactualizacion'])): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="far fa-calendar-check mr-2"></i>Última Actualización</span>
                                    <span class="badge badge-secondary"><?= date('d/m/Y', strtotime($producto['fechaactualizacion'])); ?></span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <!-- Tarjeta de consejos para edición -->
                <div class="card card-warning mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-lightbulb mr-2"></i>Consejos para Edición</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i> <strong>No deje campos obligatorios vacíos</strong>
                            <p class="mb-0 small">Aunque esté editando, los campos marcados con <span class="text-danger">*</span> siguen siendo obligatorios.</p>
                        </div>

                        <div class="alert alert-secondary mb-2">
                            <i class="fas fa-money-bill-wave mr-2"></i> <strong>Actualización de precios</strong>
                            <p class="mb-0 small">Si modifica el precio de compra, considere también actualizar el precio de venta para mantener su margen de ganancia.</p>
                        </div>

                        <div class="alert alert-secondary mb-2">
                            <i class="fas fa-warehouse mr-2"></i> <strong>Ajustes de inventario</strong>
                            <p class="mb-0 small">Para ajustes grandes de inventario, considere usar el módulo de compras en lugar de editar directamente el stock.</p>
                        </div>

                        <div class="alert alert-secondary mb-0">
                            <i class="fas fa-camera mr-2"></i> <strong>Sobre las imágenes</strong>
                            <p class="mb-0 small">Las imágenes de buena calidad mejoran la identificación visual del producto en la tienda.</p>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de acciones rápidas -->
                <div class="card card-outline card-secondary mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-bolt mr-2"></i>Acciones Rápidas</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="<?= $URL; ?>views/productos/show.php?id=<?= $producto['idproducto']; ?>" class="list-group-item list-group-item-action">
                                <i class="fas fa-eye mr-2 text-info"></i> Ver detalles del producto
                            </a>
                            <a href="#" class="list-group-item list-group-item-action cambiar-estado-link">
                                <?php if ($producto['estado'] == 1): ?>
                                    <i class="fas fa-ban mr-2 text-danger"></i> Desactivar producto
                                <?php else: ?>
                                    <i class="fas fa-check-circle mr-2 text-success"></i> Activar producto
                                <?php endif; ?>
                            </a>
                            <a href="<?= $URL; ?>views/productos" class="list-group-item list-group-item-action">
                                <i class="fas fa-list mr-2 text-primary"></i> Volver a la lista de productos
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

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
                            (Bs ${ganancia.toFixed(2)})
                        </div>`
                    );
                }
            } else {
                $('#precio-feedback').html('');
            }
        });

        // Calcular margen inicial
        $('#precioventa').trigger('input');

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

        // Mostrar mensaje de validación de stock inicial si ya hay un stock máximo
        if ($('#stockmaximo').val()) {
            $('#stockmaximo').trigger('input');
        }

        // Validación del formulario antes de enviar
        $('#formEditarProducto').on('submit', function(e) {
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
                title: 'Actualizando producto...',
                html: 'Por favor espere mientras se guardan los cambios',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        });

        // SweetAlert2 para confirmar cambio de estado
        $('.cambiar-estado-link').on('click', function(e) {
            e.preventDefault();

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
                    // Redirigir a la acción de cambio de estado
                    window.location.href = `<?= $URL; ?>controllers/productos/desactivar_producto.php?id=${productoId}&estado=${estadoActual}`;
                }
            });
        });
    });
</script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>