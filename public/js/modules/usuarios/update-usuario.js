$(document).ready(function () {
    // Inicializar Select2
    initializeSelect2();

    // Botón para seleccionar todos los permisos
    $('#seleccionar-todos').click(function () {
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
    $('#deseleccionar-todos').click(function () {
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

    // Validación de contraseñas
    document.getElementById('confirmar_clave').addEventListener('input', function () {
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
    document.querySelector('form').addEventListener('submit', function (e) {
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
