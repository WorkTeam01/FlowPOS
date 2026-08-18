<?php
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

$module_scripts = ['clientes/create-cliente'];
$skip_datatables = true; // Esta vista no usa tabla; evita cargar DataTables/pdfmake/vfs_fonts (~2.8MB)

include_once '../layouts/header.php';
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Registrar Cliente</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/clientes"><i class="fas fa-users"></i> Clientes</a></li>
                    <li class="breadcrumb-item active">Registrar Cliente</li>
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
                <form action="<?= $URL; ?>controllers/clientes/crear_cliente.php" method="POST">
                    <?= csrfField() ?>

                    <!-- Información Personal -->
                    <div class="card card-outline card-primary mb-3">
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
                                                placeholder="Ingrese los nombres" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Género -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="genero">Género <span class="text-danger">*</span></label>
                                        <select class="form-control select2" id="genero" name="genero" required>
                                            <option value="">Seleccione una opción</option>
                                            <option value="Masculino">Masculino</option>
                                            <option value="Femenino">Femenino</option>
                                            <option value="Otros">Otros</option>
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
                                            placeholder="Ingrese el apellido paterno" required>
                                    </div>
                                </div>

                                <!-- Apellido Materno -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="apellidomaterno">Apellido Materno</label>
                                        <input type="text" class="form-control" id="apellidomaterno" name="apellidomaterno"
                                            placeholder="Ingrese el apellido materno">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Documentación -->
                    <div class="card card-outline card-primary mb-3">
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
                                            <option value="DNI">DNI</option>
                                            <option value="Pasaporte">Pasaporte</option>
                                            <option value="CI">Cédula de Identidad</option>
                                            <option value="RUC">RUC</option>
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
                                                placeholder="Ingrese el número de documento" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información de Contacto -->
                    <div class="card card-outline card-primary mb-3">
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
                                                placeholder="Ingrese la dirección"></textarea>
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
                                                placeholder="Ingrese el número de celular">
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
                                                placeholder="Ingrese el correo electrónico">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estado -->
                    <div class="card card-outline card-primary mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Estado</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="estado">Estado</label>
                                        <select class="form-control select2" id="estado" name="estado">
                                            <option value="1" selected>Activo</option>
                                            <option value="0">Inactivo</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="row g-1">
                                <div class="col-12 col-sm-auto">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-save mr-1"></i> Guardar Cliente
                                    </button>
                                </div>
                                <div class="col-12 col-sm-auto">
                                    <a href="<?= $URL; ?>views/clientes/index.php" class="btn btn-secondary w-100">
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
                    <div class="card card-outline card-secondary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Guía para Registrar Clientes</h3>
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
                                    <p class="mb-0 text-muted small">Los campos marcados con <span class="text-danger">*</span> son obligatorios para el registro.</p>
                                </li>
                                <li class="list-group-item">
                                    <h6 class="mb-1"><i class="fas fa-id-card text-muted mr-2"></i>Documentación</h6>
                                    <p class="mb-0 text-muted small">DNI: 8 dígitos numéricos. RUC: 11 dígitos numéricos. Cédula de Identidad: formato según país.</p>
                                </li>
                                <li class="list-group-item">
                                    <h6 class="mb-1"><i class="fas fa-star text-muted mr-2"></i>Ventajas de registrar clientes</h6>
                                    <p class="mb-0 text-muted small">Historial de compras, promociones personalizadas y comunicación directa vía correo electrónico.</p>
                                </li>
                                <li class="list-group-item">
                                    <h6 class="mb-1"><i class="fas fa-lightbulb text-muted mr-2"></i>Consejos</h6>
                                    <p class="mb-0 text-muted small">Solicite un número de contacto actualizado; el correo electrónico es útil para enviar comprobantes.</p>
                                </li>
                            </ul>
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
