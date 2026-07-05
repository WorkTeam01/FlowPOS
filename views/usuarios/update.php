<?php
require_once __DIR__ . '/../../controllers/usuarios/UsuarioController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

// Verificar si el usuario tiene acceso al módulo
if (!($authService->tienePermisoNombre($idusuario, 'usuarios')) && !($authService->esAdministrador($idusuario))) {
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
    $_SESSION['mensaje'] = 'ID de usuario no válido';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}

// Instanciar el controlador y obtener los datos del usuario
$controller = new UsuarioController();
$usuario = $controller->editar($id);

// Verificar si el usuario existe
if (!$usuario) {
    $_SESSION['mensaje'] = 'Usuario no encontrado';
    $_SESSION['icono'] = 'error';
    header('Location: index.php');
    exit;
}
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Editar Usuario</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>views/usuarios"><i class="fas fa-users"></i> Usuarios</a></li>
                    <li class="breadcrumb-item active">Editar Usuario</li>
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
                        <h3 class="card-title">Formulario de Edición de Usuario</h3>
                    </div>
                    <!-- /.card-header -->
                    <!-- form start -->
                    <form action="<?= $URL; ?>controllers/usuarios/actualizar_usuario.php" method="POST" enctype="multipart/form-data">
                        <?= csrfField() ?>
                        <input type="hidden" name="idusuario" value="<?= $usuario['idusuario']; ?>">
                        <div class="card-body">
                            <!-- Información Personal -->
                            <div class="card card-outline card-info mb-3">
                                <div class="card-header">
                                    <h3 class="card-title">Información Personal</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <!-- Nombre -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="nombre">Nombre <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="nombre" name="nombre"
                                                    placeholder="Ingrese el nombre" value="<?= htmlspecialchars($usuario['nombre']); ?>" required>
                                            </div>
                                        </div>

                                        <!-- Apellido Paterno -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="apellidopaterno">Apellido Paterno <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="apellidopaterno" name="apellidopaterno"
                                                    placeholder="Ingrese el apellido paterno" value="<?= htmlspecialchars($usuario['apellidopaterno']); ?>" required>
                                            </div>
                                        </div>

                                        <!-- Apellido Materno -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="apellidomaterno">Apellido Materno</label>
                                                <input type="text" class="form-control" id="apellidomaterno" name="apellidomaterno"
                                                    placeholder="Ingrese el apellido materno" value="<?= htmlspecialchars($usuario['apellidomaterno'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <!-- Tipo de Documento -->
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="tipodocumento">Tipo de Documento <span class="text-danger">*</span></label>
                                                <select class="form-control select2" id="tipodocumento" name="tipodocumento" required>
                                                    <option value="">Seleccione un tipo de documento</option>
                                                    <option value="DNI" <?= $usuario['tipodocumento'] == 'DNI' ? 'selected' : ''; ?>>DNI</option>
                                                    <option value="PASAPORTE" <?= $usuario['tipodocumento'] == 'PASAPORTE' ? 'selected' : ''; ?>>Pasaporte</option>
                                                    <option value="CI" <?= $usuario['tipodocumento'] == 'CI' ? 'selected' : ''; ?>>Cédula de Identidad</option>
                                                    <option value="RUC" <?= $usuario['tipodocumento'] == 'RUC' ? 'selected' : ''; ?>>RUC</option>
                                                    <option value="OTROS" <?= $usuario['tipodocumento'] == 'OTROS' ? 'selected' : ''; ?>>Otros</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Número de Documento -->
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="numdocumento">Número de Documento <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="numdocumento" name="numdocumento"
                                                    placeholder="Ingrese el número de documento" value="<?= htmlspecialchars($usuario['numdocumento']); ?>"
                                                    maxlength="25" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <!-- Dirección -->
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="direccion">Dirección</label>
                                                <textarea class="form-control" id="direccion" name="direccion" rows="2"
                                                    placeholder="Ingrese la dirección"><?= htmlspecialchars($usuario['direccion'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Información de Contacto y Acceso -->
                            <div class="card card-outline card-info mb-3">
                                <div class="card-header">
                                    <h3 class="card-title">Información de Contacto y Acceso</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <!-- Teléfono -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="telefono">Teléfono</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                                    </div>
                                                    <input type="tel" class="form-control" id="telefono" name="telefono"
                                                        placeholder="Ingrese el teléfono" value="<?= htmlspecialchars($usuario['telefono'] ?? ''); ?>"
                                                        maxlength="20">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Correo -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="correo">Correo Electrónico <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                                    </div>
                                                    <input type="email" class="form-control" id="correo" name="correo"
                                                        placeholder="ejemplo@correo.com" value="<?= htmlspecialchars($usuario['correo']); ?>"
                                                        required>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Cargo -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="cargo">Cargo <span class="text-danger">*</span></label>
                                                <select class="form-control select2" id="cargo" name="cargo" required>
                                                    <option value="">Seleccione un cargo</option>
                                                    <option value="Administrador" <?= $usuario['cargo'] == 'Administrador' ? 'selected' : ''; ?>>Administrador</option>
                                                    <option value="Supervisor" <?= $usuario['cargo'] == 'Supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                                                    <option value="Vendedor" <?= $usuario['cargo'] == 'Vendedor' ? 'selected' : ''; ?>>Vendedor</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Cambio de Contraseña -->
                            <div class="card card-outline card-info mb-3">
                                <div class="card-header">
                                    <h3 class="card-title">Cambio de Contraseña (opcional)</h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body" style="display: none;">
                                    <div class="row">
                                        <!-- Contraseña -->
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="clave">Nueva Contraseña</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                    </div>
                                                    <input type="password" class="form-control" id="clave" name="clave"
                                                        placeholder="Dejar en blanco para mantener la actual" autocomplete="off">
                                                </div>
                                                <small class="form-text text-muted">Mínimo 6 caracteres. Debe contener letras y números.</small>
                                            </div>
                                        </div>

                                        <!-- Confirmar Contraseña -->
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="confirmar_clave">Confirmar Nueva Contraseña</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                    </div>
                                                    <input type="password" class="form-control" id="confirmar_clave" name="confirmar_clave"
                                                        placeholder="Confirme la nueva contraseña" autocomplete="off">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="alert alert-info mt-3">
                                        <i class="fas fa-info-circle"></i> Dejar estos campos en blanco si no desea cambiar la contraseña.
                                    </div>
                                </div>
                            </div>

                            <!-- Imagen y Estado -->
                            <div class="card card-outline card-info mb-3">
                                <div class="card-header">
                                    <h3 class="card-title">Imagen y Estado</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <!-- Imagen -->
                                        <div class="col-md-8">
                                            <div class="form-group">
                                                <label for="imagen">Imagen de Perfil</label>
                                                <div class="input-group">
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="imagen" name="imagen"
                                                            accept="image/*">
                                                        <label class="custom-file-label" for="imagen">Seleccionar archivo</label>
                                                    </div>
                                                </div>
                                                <small class="form-text text-muted">Formatos permitidos: JPG, PNG, GIF, WEBP. Máximo 2MB</small>
                                            </div>

                                            <!-- Imagen actual -->
                                            <div class="form-group">
                                                <label>Imagen Actual:</label><br>
                                                <?php if (isset($usuario['imagen']) && !empty($usuario['imagen'])): ?>
                                                    <img src="<?= $URL; ?>public/uploads/usuarios/<?= $usuario['imagen']; ?>"
                                                        alt="Imagen actual" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                                                <?php else: ?>
                                                    <img src="<?= $URL; ?>public/uploads/usuarios/user_default.jpg"
                                                        alt="Imagen por defecto" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                                                <?php endif; ?>
                                            </div>

                                            <!-- Vista previa de imagen nueva -->
                                            <div id="preview-container" style="display: none;">
                                                <div class="form-group">
                                                    <label>Vista Previa Nueva Imagen:</label><br>
                                                    <img id="preview-image" src="#" alt="Vista previa" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Estado e Información adicional -->
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="estado">Estado</label>
                                                <select class="form-control select2" id="estado" name="estado">
                                                    <option value="1" <?= $usuario['estado'] == 1 ? 'selected' : ''; ?>>Activo</option>
                                                    <option value="0" <?= $usuario['estado'] == 0 ? 'selected' : ''; ?>>Inactivo</option>
                                                </select>
                                            </div>

                                            <!-- Información del Sistema -->
                                            <div class="card bg-light">
                                                <div class="card-body p-2">
                                                    <h6 class="mb-2"><i class="fas fa-info-circle"></i> Información del Sistema</h6>
                                                    <dl class="row mb-0">
                                                        <dt class="col-sm-6">Creado:</dt>
                                                        <dd class="col-sm-6"><?= isset($usuario['fechacreacion']) ? date('d/m/Y H:i', strtotime($usuario['fechacreacion'])) : 'No disponible'; ?></dd>

                                                        <dt class="col-sm-6">Actualizado:</dt>
                                                        <dd class="col-sm-6"><?= isset($usuario['fechaactualizacion']) ? date('d/m/Y H:i', strtotime($usuario['fechaactualizacion'])) : 'No disponible'; ?></dd>
                                                    </dl>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección de Permisos -->
                            <div class="card card-outline card-info">
                                <div class="card-header">
                                    <h3 class="card-title">Asignación de Permisos</h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-outline-primary btn-xs" id="seleccionar-todos">
                                            <i class="fas fa-check-square mr-1"></i> Seleccionar todos
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-xs" id="deseleccionar-todos">
                                            <i class="fas fa-square mr-1"></i> Deseleccionar todos
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php
                                        // Obtener todos los permisos disponibles
                                        $permisos = $authService->obtenerTodosLosPermisos();
                                        foreach ($permisos as $permiso) :
                                            // Verificar si el usuario tiene este permiso asignado
                                            $checked = $authService->tienePermisoAsignado($usuario['idusuario'], $permiso['idpermiso']) ? 'checked' : '';
                                        ?>
                                            <div class="col-md-4">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input"
                                                        id="permiso_<?= $permiso['idpermiso'] ?>"
                                                        name="permisos[]"
                                                        value="<?= $permiso['idpermiso'] ?>"
                                                        <?= $checked ?>>
                                                    <label class="custom-control-label" for="permiso_<?= $permiso['idpermiso'] ?>">
                                                        <?= htmlspecialchars($permiso['nombre']) ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /.card-body -->

                        <div class="card-footer">
                            <div class="row g-1">
                                <div class="col-12 col-sm-auto">
                                    <button type="submit" class="btn btn-warning w-100">
                                        <i class="fas fa-save mr-1"></i> Actualizar Usuario
                                    </button>
                                </div>
                                <div class="col-12 col-sm-auto">
                                    <a href="<?= $URL; ?>views/usuarios" class="btn btn-secondary w-100">
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
                        <h3 class="card-title">Guía para Editar Usuarios</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="callout callout-info">
                            <h5><i class="fas fa-info-circle"></i> Campos Obligatorios</h5>
                            <p>Los campos marcados con <span class="text-danger">*</span> son obligatorios.</p>
                        </div>

                        <div class="callout callout-warning">
                            <h5><i class="fas fa-key"></i> Cambio de Contraseña</h5>
                            <p>Solo complete los campos de contraseña si desea cambiarla. Caso contrario, déjelos en blanco.</p>
                            <p>Recomendaciones para contraseñas:</p>
                            <ul>
                                <li>Mínimo 6 caracteres de longitud</li>
                                <li>Combinar letras y números</li>
                                <li>Incluir al menos una mayúscula</li>
                            </ul>
                        </div>

                        <div class="callout callout-success">
                            <h5><i class="fas fa-user-shield"></i> Asignación de Permisos</h5>
                            <p>Gestione cuidadosamente los permisos según el rol del usuario:</p>
                            <ul>
                                <li><strong>Administrador:</strong> Acceso completo al sistema</li>
                                <li><strong>Supervisor:</strong> Acceso a reportes y gestión limitada</li>
                                <li><strong>Vendedor:</strong> Acceso a ventas y consultas básicas</li>
                            </ul>
                        </div>

                        <div class="callout callout-danger">
                            <h5><i class="fas fa-exclamation-triangle"></i> Importante</h5>
                            <p>Si quita todos los permisos, el usuario no podrá acceder a ninguna funcionalidad del sistema.</p>
                            <p>Tenga cuidado al cambiar el estado a inactivo, ya que el usuario no podrá iniciar sesión.</p>
                        </div>

                        <div class="card bg-light">
                            <div class="card-body">
                                <h5><i class="fas fa-question-circle"></i> ¿Necesitas ayuda?</h5>
                                <p>Si tienes dudas sobre cómo editar este usuario, contacta al administrador del sistema.</p>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar Select2
        initializeSelect2();

        // Botón para seleccionar todos los permisos
        $('#seleccionar-todos').click(function() {
            $('input[name="permisos[]"]').prop('checked', true);

            // Efecto visual
            Swal.fire({
                position: 'top-end',
                icon: 'success',
                title: 'Todos los permisos seleccionados',
                showConfirmButton: false,
                timer: 3000,
                toast: true,
                didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                }
            });
        });

        // Botón para deseleccionar todos los permisos
        $('#deseleccionar-todos').click(function() {
            $('input[name="permisos[]"]').prop('checked', false);

            // Efecto visual
            Swal.fire({
                position: 'top-end',
                icon: 'info',
                title: 'Todos los permisos deseleccionados',
                showConfirmButton: false,
                timer: 3000,
                toast: true,
                didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                }
            });
        });

        // Función para mostrar la vista previa de la imagen
        document.getElementById('imagen').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validar tamaño del archivo (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Archivo muy grande',
                        text: 'El archivo no debe superar los 2MB'
                    });
                    e.target.value = '';
                    return;
                }

                // Validar tipo de archivo
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Tipo de archivo no válido',
                        text: 'Solo se permiten archivos JPG, PNG, GIF y WEBP'
                    });
                    e.target.value = '';
                    return;
                }

                const reader = new FileReader();
                const previewContainer = document.getElementById('preview-container');
                const previewImage = document.getElementById('preview-image');

                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewContainer.style.display = 'block';
                }

                reader.readAsDataURL(file);

                // Actualizar el label con el nombre del archivo
                const fileName = file.name;
                const label = e.target.nextElementSibling;
                label.textContent = fileName;
            }
        });

        // Validación de contraseñas
        document.getElementById('confirmar_clave').addEventListener('input', function() {
            const clave = document.getElementById('clave').value;
            const confirmar = this.value;

            if (clave && confirmar) {
                if (clave !== confirmar) {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                } else {
                    this.classList.add('is-valid');
                    this.classList.remove('is-invalid');
                }
            } else {
                this.classList.remove('is-valid');
                this.classList.remove('is-invalid');
            }
        });

        // Validación del formulario antes de enviar
        document.querySelector('form').addEventListener('submit', function(e) {
            const clave = document.getElementById('clave').value;
            const confirmar = document.getElementById('confirmar_clave').value;

            if (clave !== '' || confirmar !== '') {
                if (clave !== confirmar) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error en las contraseñas',
                        text: 'Las contraseñas no coinciden'
                    });
                    return false;
                }

                if (clave.length < 6) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Contraseña muy corta',
                        text: 'La contraseña debe tener al menos 6 caracteres'
                    });
                    return false;
                }
            }
        });
    });
</script>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>