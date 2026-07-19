<?php
require_once __DIR__ . '/../../controllers/productos/ProductoController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'] ?? '';

$authService = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!($authService->tienePermisoNombre($idusuario, 'productos')) && !($authService->esAdministrador($idusuario))) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL);
    exit;
}

// Incluir el encabezado DESPUÉS de verificar permisos
include_once '../layouts/header.php';

$controller = new ProductoController();
$productos = $controller->index();
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Gestión de Productos</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Productos</li>
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
                <div class="card">
                    <div class="card-header card-outline card-primary">
                        <h3 class="card-title">Listado de Productos</h3>
                        <div class="card-tools">
                            <a href="<?= $URL; ?>views/productos/create.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Nuevo Producto
                            </a>
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tablaProductos" class="table table-sm table-bordered table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Nro</th>
                                        <th>Código</th>
                                        <th>Nombre</th>
                                        <th>Categoría</th>
                                        <th>Imagen</th>
                                        <th>Precio Venta</th>
                                        <th>Stock</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador = 1;
                                    foreach ($productos as $producto) :
                                        $estado_actual = $producto['estado'];
                                        $clase_boton_estado = $estado_actual == 1 ? 'btn-danger' : 'btn-success';
                                        $icono_boton_estado = $estado_actual == 1 ? 'fa-ban' : 'fa-check';
                                        $titulo_alerta = $estado_actual == 1 ? '¿Desactivar Producto?' : '¿Activar Producto?';
                                        $texto_alerta = $estado_actual == 1 ? 'El producto no estará disponible para venta.' : 'El producto estará disponible para venta.';
                                        $confirm_button_text = $estado_actual == 1 ? 'Sí, desactivar' : 'Sí, activar';
                                        $texto_boton_estado = $estado_actual == 1 ? 'Desactivar producto' : 'Activar producto';
                                    ?>
                                        <tr>
                                            <td><?= $contador++; ?></td>
                                            <td><?= htmlspecialchars($producto['codigo'] ?? 'N/A'); ?></td>
                                            <td><?= htmlspecialchars($producto['nombre']); ?></td>
                                            <td><?= htmlspecialchars($producto['categoria_nombre']); ?></td>
                                            <td class="text-center">
                                                <?php if (!empty($producto['imagen'])): ?>
                                                    <img src="<?= $URL; ?>public/uploads/productos/<?= $producto['imagen']; ?>" loading="lazy" alt="Imagen" class="img-thumbnail" width="50">
                                                <?php else : ?>
                                                    <img src="<?= $URL; ?>public/uploads/productos/producto_default.png" loading="lazy" alt="Imagen" class="img-thumbnail" width="50">
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-right"><?= number_format($producto['precioventa'], 2); ?></td>
                                            <td class="text-center <?= $producto['stock'] < $producto['stockminimo'] ? 'text-danger font-weight-bold' : '' ?>">
                                                <?= $producto['stock']; ?>
                                                <?php if ($producto['stock'] < $producto['stockminimo']): ?>
                                                    <i class="fas fa-exclamation-triangle ml-1" title="Stock bajo"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($estado_actual == 1) : ?>
                                                    <span class="badge badge-success">Activo</span>
                                                <?php else : ?>
                                                    <span class="badge badge-danger">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="<?= $URL; ?>views/productos/show.php?id=<?= $producto['idproducto']; ?>" class="btn btn-info btn-sm" data-toggle="tooltip" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?= $URL; ?>views/productos/update.php?id=<?= $producto['idproducto']; ?>" class="btn btn-warning btn-sm" data-toggle="tooltip" title="Editar producto">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn <?= $clase_boton_estado; ?> btn-sm btn-cambiar-estado"
                                                        data-id="<?= $producto['idproducto']; ?>"
                                                        data-estado="<?= $estado_actual; ?>"
                                                        data-nombre="<?= htmlspecialchars($producto['nombre']); ?>"
                                                        data-toggle="tooltip" title="<?= $texto_boton_estado; ?>">
                                                        <i class="fas <?= $icono_boton_estado; ?>"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- /.card-body -->
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

<script src="<?= $URL; ?>public/js/modules/productos/index-productos.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        $('[data-toggle="tooltip"]').tooltip();

        const botonesCambiarEstado = document.querySelectorAll('.btn-cambiar-estado');

        botonesCambiarEstado.forEach(boton => {
            boton.addEventListener('click', function() {
                const productoId = this.dataset.id;
                const estadoActual = this.dataset.estado;
                const nombreProducto = this.dataset.nombre;

                const tituloAlerta = estadoActual == 1 ? `¿Desactivar ${nombreProducto}?` : `¿Activar ${nombreProducto}?`;
                const textoAlerta = estadoActual == 1 ? 'El producto no estará disponible para venta.' : 'El producto estará disponible para venta.';
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
                        const baseUrl = '<?= $URL; ?>';
                        submitCsrfForm(`${baseUrl}controllers/productos/desactivar_producto.php`, {
                            id: productoId,
                            estado: estadoActual
                        });
                    }
                });
            });
        });
    });
</script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>