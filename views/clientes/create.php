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
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Formulario de Nuevo Cliente</h3>
                    </div>
                    <!-- form start -->
                    <form action="<?= $URL; ?>controllers/clientes/crear_cliente.php" method="POST">
                        <?= csrfField() ?>
                        <div class="card-body">
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
                            <div class="card card-outline card-primary">
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
                            </div>
                        </div>
                        <!-- /.card-body -->

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
                    </form>
                </div>
                <!-- /.card -->
            </div>
            <!-- /.col-md-8 -->

            <!-- Columna de guía (4 columnas) -->
            <div class="col-md-4">
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">Guía para Registrar Clientes</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="callout callout-info">
                            <h5><i class="fas fa-info-circle"></i> Campos Obligatorios</h5>
                            <p>Los campos marcados con <span class="text-danger">*</span> son obligatorios para el registro.</p>
                        </div>

                        <div class="callout callout-warning">
                            <h5><i class="fas fa-id-card"></i> Documentación</h5>
                            <p>Verificar que el número de documento sea correcto según el tipo:</p>
                            <ul>
                                <li><strong>DNI:</strong> 8 dígitos numéricos</li>
                                <li><strong>RUC:</strong> 11 dígitos numéricos</li>
                                <li><strong>Cédula de Identidad:</strong> Formato según país</li>
                            </ul>
                        </div>

                        <div class="callout callout-success">
                            <h5><i class="fas fa-star"></i> Ventajas de registrar clientes</h5>
                            <ul>
                                <li>Gestión fácil y rápida de las ventas</li>
                                <li>Historial completo de compras</li>
                                <li>Posibilidad de ofrecer promociones personalizadas</li>
                                <li>Comunicación directa vía correo electrónico</li>
                            </ul>
                        </div>

                        <div class="card bg-light">
                            <div class="card-body">
                                <h5><i class="fas fa-lightbulb"></i> Consejos</h5>
                                <ul>
                                    <li>Informe al cliente sobre los beneficios de registrarse en el sistema</li>
                                    <li>Solicite un número de contacto actualizado</li>
                                    <li>El correo electrónico es útil para enviar comprobantes</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de datos para reportes -->
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">Datos para reportes</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Dato</th>
                                    <th>Importancia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Nombres y Apellidos</td>
                                    <td><span class="badge badge-danger">Alta</span></td>
                                </tr>
                                <tr>
                                    <td>Documento</td>
                                    <td><span class="badge badge-danger">Alta</span></td>
                                </tr>
                                <tr>
                                    <td>Celular</td>
                                    <td><span class="badge badge-warning">Media</span></td>
                                </tr>
                                <tr>
                                    <td>Email</td>
                                    <td><span class="badge badge-warning">Media</span></td>
                                </tr>
                                <tr>
                                    <td>Dirección</td>
                                    <td><span class="badge badge-info">Baja</span></td>
                                </tr>
                            </tbody>
                        </table>
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

<!-- Script para inicializar Select2 -->
<script>
    // Inicializar Select2
    $(document).ready(function() {
        initializeSelect2();
    });
</script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>