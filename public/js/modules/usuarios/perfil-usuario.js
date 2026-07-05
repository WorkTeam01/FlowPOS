// Script para mostrar el nombre del archivo en el input de imagen
document.addEventListener('DOMContentLoaded', function () {
    const customFileInputs = document.querySelectorAll('.custom-file-input');
    customFileInputs.forEach(function (input) {
        input.addEventListener('change', function (e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'Seleccionar archivo...';
            const nextSibling = e.target.nextElementSibling;
            if (nextSibling && nextSibling.classList.contains('custom-file-label')) {
                nextSibling.innerText = fileName;
            }
        });
    });
});

document.getElementById('imagen').addEventListener('change', function (e) {
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

        reader.onload = function (e) {
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

// Script para manejar el cambio de contraseña con AJAX
document.addEventListener('DOMContentLoaded', function () {
    const formCambiarPassword = document.getElementById('formCambiarPassword');

    formCambiarPassword.addEventListener('submit', function (e) {
        e.preventDefault();

        // Validar contraseñas
        const claveActual = document.getElementById('clave_actual').value;
        const nuevaClave = document.getElementById('nueva_clave').value;
        const confirmarClave = document.getElementById('confirmar_nueva_clave').value;

        if (!claveActual || !nuevaClave || !confirmarClave) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Todos los campos son obligatorios'
            });
            return;
        }

        if (nuevaClave !== confirmarClave) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Las contraseñas no coinciden'
            });
            return;
        }

        if (nuevaClave.length < 6) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'La contraseña debe tener al menos 6 caracteres'
            });
            return;
        }

        // Preparar datos para enviar
        const appUrl = document.querySelector('meta[name="app-url"]').content;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const formData = new FormData(formCambiarPassword);
        formData.append('csrf_token', csrfToken);

        // Deshabilitar botón
        const btnCambiarPassword = document.getElementById('btnCambiarPassword');
        btnCambiarPassword.disabled = true;
        btnCambiarPassword.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

        // Realizar solicitud AJAX
        fetch(`${appUrl}controllers/usuarios/ajax_cambiar_clave_perfil.php`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                // Habilitar botón
                btnCambiarPassword.disabled = false;
                btnCambiarPassword.innerHTML = 'Cambiar Contraseña';

                if (data.success) {
                    // Mostrar mensaje de éxito y luego cerrar sesión
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: 'Contraseña actualizada correctamente. Se cerrará la sesión.',
                        allowOutsideClick: false,
                        confirmButtonText: 'Aceptar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Redirigir al logout
                            window.location.href = '<?= $URL; ?>controllers/auth/logout.php';
                        }
                    });
                } else {
                    // Mostrar mensaje de error
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                btnCambiarPassword.disabled = false;
                btnCambiarPassword.innerHTML = 'Cambiar Contraseña';

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error al procesar la solicitud'
                });
            });
    });
});