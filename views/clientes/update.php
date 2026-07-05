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
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">Formulario de Edición de Cliente</h3>
                    </div>
                    <!-- form start -->
                    <form action="<?= $URL; ?>controllers/clientes/actualizar_cliente.php" method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="idcliente" value="<?= $cliente['idcliente']; ?>">
                        <div class="card-body">
                            <!-- Información Personal -->
                            <div class="card card-outline card-info mb-3">
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
                                                        placeholder="Ingrese los nombres" value="<?= htmlspecialchars($cliente['nombres']); ?>" required>
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
                                                    placeholder="Ingrese el apellido paterno" value="<?= htmlspecialchars($cliente['apellidopaterno']); ?>" required>
                                            </div>
                                        </div>

                                        <!-- Apellido Materno -->
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="apellidomaterno">Apellido Materno</label>
                                                <input type="text" class="form-control" id="apellidomaterno" name="apellidomaterno"
                                                    placeholder="Ingrese el apellido materno" value="<?= htmlspecialchars($cliente['apellidomaterno'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Documentación -->
                            <div class="card card-outline card-info mb-3">
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
                                                        placeholder="Ingrese el número de documento" value="<?= htmlspecialchars($cliente['numdocumento']); ?>"
                                                        maxlength="25" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Información de Contacto -->
                            <div class="card card-outline card-info mb-3">
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
                                                        placeholder="Ingrese la dirección"><?= htmlspecialchars($cliente['direccion'] ?? ''); ?></textarea>
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
                                                        placeholder="Ingrese el número de celular" value="<?= htmlspecialchars($cliente['celular'] ?? ''); ?>"
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
                                                        placeholder="Ingrese el correo electrónico" value="<?= htmlspecialchars($cliente['email'] ?? ''); ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Estado e Información del Sistema -->
                            <div class="card card-outline card-info">
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
                            </div>
                        </div>
                        <!-- /.card-body -->

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
                    </form>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col-md-8 -->

            <!-- Columna de guía (4 columnas) -->
            <div class="col-md-4">
                <!-- Tarjeta de cliente -->
                <div class="card card-warning card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center">
                            <i class="fas fa-user-tie fa-5x text-warning mb-3"></i>
                        </div>

                        <h3 class="profile-username text-center">
                            <?= htmlspecialchars($cliente['nombres'] . ' ' . $cliente['apellidopaterno']); ?>
                        </h3>

                        <p class="text-muted text-center">Cliente <?= $cliente['estado'] == 1 ? 'Activo' : 'Inactivo'; ?></p>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b><i class="fas fa-id-card mr-1"></i> <?= htmlspecialchars($cliente['tipodocumento']); ?></b>
                                <span class="float-right"><?= htmlspecialchars($cliente['numdocumento']); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-phone mr-1"></i> Teléfono</b>
                                <span class="float-right"><?= htmlspecialchars($cliente['celular'] ?? 'No registrado'); ?></span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-envelope mr-1"></i> Email</b>
                                <span class="float-right text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($cliente['email'] ?? 'No registrado'); ?>">
                                    <?= htmlspecialchars($cliente['email'] ?? 'No registrado'); ?>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Guía de edición -->
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">Guía para Editar Clientes</h3>
                    </div>
                    <div class="card-body">
                        <div class="callout callout-info">
                            <h5><i class="fas fa-info-circle"></i> Campos Obligatorios</h5>
                            <p>Los campos marcados con <span class="text-danger">*</span> son obligatorios.</p>
                        </div>

                        <div class="callout callout-warning">
                            <h5><i class="fas fa-exclamation-triangle"></i> Advertencia</h5>
                            <p>Tenga cuidado al cambiar el tipo o número de documento ya que podría duplicar registros si coincide con otro cliente.</p>
                        </div>

                        <div class="callout callout-danger">
                            <h5><i class="fas fa-ban"></i> Datos sensibles</h5>
                            <p>No modifique los datos personales sin el consentimiento del cliente, especialmente si son utilizados para facturación.</p>
                        </div>

                        <div class="callout callout-success">
                            <h5><i class="fas fa-check-circle"></i> Actualice regularmente</h5>
                            <p>Es importante mantener actualizados los datos de contacto para una mejor comunicación con los clientes.</p>
                        </div>
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

                            <a href="<?= $URL; ?>views/ventas/nueva.php?cliente=<?= $cliente['idcliente']; ?>" class="list-group-item list-group-item-action">
                                <i class="fas fa-shopping-cart mr-2"></i> Nueva Venta
                            </a>
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

<script>
    // Inicializar Select2
    $(document).ready(function() {
        initializeSelect2();

        // Validación del formulario
        $('form').submit(function(e) {
            let isValid = true;

            // Validar campos obligatorios
            $('input[required], select[required]').each(function() {
                if (!$(this).val()) {
                    $(this).addClass('is-invalid');
                    isValid = false;
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            // Validar email si se ingresó
            let email = $('#email').val();
            if (email && !validateEmail(email)) {
                $('#email').addClass('is-invalid');
                if (!$('#email').next('.invalid-feedback').length) {
                    $('#email').after('<div class="invalid-feedback">Ingrese un email válido</div>');
                }
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error de validación',
                    text: 'Por favor corrija los errores en el formulario'
                });
                return false;
            }
        });
    });

    // Función para validar formato de email
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(String(email).toLowerCase());
    }
</script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>