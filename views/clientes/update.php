<?php
require_once __DIR__ . '/../../controllers/clientes/ClienteController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar permisos para el módulo de clientes
if (!$authService->tienePermisoNombre($idusuario, 'clientes') && !$authService->esAdministrador($idusuario)) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Verificar si se proporcionó un ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    $_SESSION['mensaje'] = 'ID de cliente no válido';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Instanciar el controlador y obtener los datos del cliente
$controller = new ClienteController();
$cliente = $controller->editar($id);

// Verificar si el cliente existe
if (!$cliente) {
    $_SESSION['mensaje'] = 'Cliente no encontrado';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

$skip_datatables = true; // Evita cargar DataTables/pdfmake/vfs_fonts (~2.8MB)
$module_scripts = ['clientes/update-cliente'];
include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Editar Cliente</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/clientes"><i class="fas fa-users"></i> Clientes</a></li>
                    <li class="breadcrumb-item active">Editar Cliente</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <!-- Columna del formulario (8 columnas) -->
            <div class="col-md-8">
                <!-- form start -->
                <form action="<?= $URL; ?>controllers/clientes/actualizar_cliente.php" method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="idcliente" value="<?= $cliente['idcliente']; ?>">

                    <!-- Información Personal -->
                    <div class="card card-outline card-warning mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Información Personal</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Nombres -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nombres">Nombres <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            </div>
                                            <input type="text" class="form-control" id="nombres" name="nombres"
                                                placeholder="Ingrese los nombres" value="<?= $cliente['nombres']; ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Género -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="genero">Género <span class="text-danger">*</span></label>
                                        <select class="form-control select2" id="genero" name="genero" required>
                                            <option value="">Seleccione una opción</option>
                                            <option value="Masculino" <?= $cliente['genero'] == 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                                            <option value="Femenino" <?= $cliente['genero'] == 'Femenino' ? 'selected' : ''; ?>>Femenino</option>
                                            <option value="Otros" <?= $cliente['genero'] == 'Otros' ? 'selected' : ''; ?>>Otros</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Apellido Paterno -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="apellidopaterno">Apellido Paterno <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="apellidopaterno" name="apellidopaterno"
                                            placeholder="Ingrese el apellido paterno" value="<?= $cliente['apellidopaterno']; ?>" required>
                                    </div>
                                </div>

                                <!-- Apellido Materno -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="apellidomaterno">Apellido Materno</label>
                                        <input type="text" class="form-control" id="apellidomaterno" name="apellidomaterno"
                                            placeholder="Ingrese el apellido materno" value="<?= $cliente['apellidomaterno'] ?? ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Documentación -->
                    <div class="card card-outline card-warning mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Documentación</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Tipo de Documento -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tipodocumento">Tipo de Documento <span class="text-danger">*</span></label>
                                        <select class="form-control select2" id="tipodocumento" name="tipodocumento" required>
                                            <option value="">Seleccione un tipo de documento</option>
                                            <option value="DNI" <?= $cliente['tipodocumento'] == 'DNI' ? 'selected' : ''; ?>>DNI</option>
                                            <option value="PASAPORTE" <?= $cliente['tipodocumento'] == 'PASAPORTE' ? 'selected' : ''; ?>>Pasaporte</option>
                                            <option value="CI" <?= $cliente['tipodocumento'] == 'CI' ? 'selected' : ''; ?>>Cédula de Identidad</option>
                                            <option value="RUC" <?= $cliente['tipodocumento'] == 'RUC' ? 'selected' : ''; ?>>RUC</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Número de Documento -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="numdocumento">Número de Documento <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                            </div>
                                            <input type="text" class="form-control" id="numdocumento" name="numdocumento"
                                                placeholder="Ingrese el número de documento" value="<?= $cliente['numdocumento']; ?>"
                                                maxlength="25" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información de Contacto -->
                    <div class="card card-outline card-warning mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Información de Contacto</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Dirección -->
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="direccion">Dirección</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                            </div>
                                            <textarea class="form-control" id="direccion" name="direccion" rows="2"
                                                placeholder="Ingrese la dirección"><?= $cliente['direccion'] ?? ''; ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Celular -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="celular">Celular</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-mobile-alt"></i></span>
                                            </div>
                                            <input type="tel" class="form-control" id="celular" name="celular"
                                                placeholder="Ingrese el número de celular" value="<?= $cliente['celular'] ?? ''; ?>"
                                                maxlength="20">
                                        </div>
                                    </div>
                                </div>

                                <!-- Email -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            </div>
                                            <input type="email" class="form-control" id="email" name="email"
                                                placeholder="Ingrese el correo electrónico" value="<?= $cliente['email'] ?? ''; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estado e Información del Sistema -->
                    <div class="card card-outline card-warning mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Estado e Información del Sistema</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Estado -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="estado">Estado</label>
                                        <select class="form-control select2" id="estado" name="estado">
                                            <option value="1" <?= $cliente['estado'] == 1 ? 'selected' : ''; ?>>Activo</option>
                                            <option value="0" <?= $cliente['estado'] == 0 ? 'selected' : ''; ?>>Inactivo</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Información del sistema (solo lectura) -->
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body p-2">
                                            <h6 class="mb-2"><i class="fas fa-info-circle"></i> Información del Sistema</h6>
                                            <dl class="row mb-0">
                                                <dt class="col-sm-6">Creado:</dt>
                                                <dd class="col-sm-6"><?= isset($cliente['fechacreacion']) ? date('d/m/Y H:i', strtotime($cliente['fechacreacion'])) : 'No disponible'; ?></dd>

                                                <dt class="col-sm-6">Actualizado:</dt>
                                                <dd class="col-sm-6"><?= isset($cliente['fechaactualizacion']) ? date('d/m/Y H:i', strtotime($cliente['fechaactualizacion'])) : 'No disponible'; ?></dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="row g-1">
                                <div class="col-12 col-sm-auto">
                                    <button type="submit" class="btn btn-warning w-100">
                                        <i class="fas fa-save mr-1"></i> Actualizar Cliente
                                    </button>
                                </div>
                                <div class="col-12 col-sm-auto">
                                    <a href="<?= $URL; ?>views/clientes" class="btn btn-secondary w-100">
                                        <i class="fas fa-times mr-1"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <!-- /.col-md-8 -->

            <!-- Columna de guía (4 columnas) -->
            <div class="col-md-4 mb-3">
                <div class="sidebar-sticky">
                    <div class="card card-outline card-secondary mb-3">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Guía para Editar Clientes</h3>
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
                                    <p class="mb-0 text-muted small">Los campos marcados con <span class="text-danger">*</span> son obligatorios.</p>
                                </li>
                                <li class="list-group-item">
                                    <h6 class="mb-1"><i class="fas fa-exclamation-triangle text-muted mr-2"></i>Advertencia</h6>
                                    <p class="mb-0 text-muted small">Tenga cuidado al cambiar el tipo o número de documento ya que podría duplicar registros si coincide con otro cliente.</p>
                                </li>
                                <li class="list-group-item">
                                    <h6 class="mb-1 text-danger"><i class="fas fa-ban mr-2"></i>Datos sensibles</h6>
                                    <p class="mb-0 text-muted small">No modifique los datos personales sin el consentimiento del cliente, especialmente si son utilizados para facturación.</p>
                                </li>
                                <li class="list-group-item">
                                    <h6 class="mb-1"><i class="fas fa-check-circle text-muted mr-2"></i>Actualice regularmente</h6>
                                    <p class="mb-0 text-muted small">Es importante mantener actualizados los datos de contacto para una mejor comunicación con los clientes.</p>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Acciones adicionales -->
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Acciones Adicionales</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group">
                                <a href="<?= $URL; ?>views/clientes/show.php?id=<?= $cliente['idcliente']; ?>" class="list-group-item list-group-item-action">
                                    <i class="fas fa-eye mr-2"></i> Ver Detalles del Cliente
                                </a>

                                <a href="<?= $URL; ?>views/ventas/create.php?cliente=<?= $cliente['idcliente']; ?>" class="list-group-item list-group-item-action">
                                    <i class="fas fa-shopping-cart mr-2"></i> Nueva Venta
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.col-md-4 -->
        </div>
        <!-- /.row -->
    </div>
    <!-- /.container-fluid -->
</section>
<!-- /.content -->

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
