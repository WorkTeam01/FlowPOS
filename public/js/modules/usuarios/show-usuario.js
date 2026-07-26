$(document).ready(function() {
    // Recordar la pestaña activa entre visitas
    $('a[data-toggle="pill"]').on('shown.bs.tab', function(e) {
        localStorage.setItem('lastUsuarioDetailTab', $(e.target).attr('id'));
    });
    var lastTab = localStorage.getItem('lastUsuarioDetailTab');
    if (lastTab && document.getElementById(lastTab)) {
        $('#' + lastTab).tab('show');
    }

    // Cambiar el estado del usuario
    $('#btnCambiarEstado').on('click', function(e) {
        e.preventDefault();

        const boton = $(this);
        const usuarioId = boton.data('id');
        const estadoActual = parseInt(boton.attr('data-estado'), 10);
        const nombreUsuario = boton.data('nombre');

        const tituloAlerta = estadoActual == 1 ?
            `¿Desactivar a ${nombreUsuario}?` :
            `¿Activar a ${nombreUsuario}?`;
        const textoAlerta = estadoActual == 1 ?
            'El usuario no podrá acceder al sistema hasta que sea activado nuevamente.' :
            'El usuario podrá acceder nuevamente al sistema.';
        const confirmButtonText = estadoActual == 1 ? 'Sí, desactivar' : 'Sí, activar';

        Swal.fire({
            title: tituloAlerta,
            text: textoAlerta,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: estadoActual == 1 ? '#dc3545' : '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: confirmButtonText,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (!result.isConfirmed) return;

            submitCsrfForm(baseUrl + 'controllers/usuarios/desactivar_usuario.php', {
                id: usuarioId,
                estado: estadoActual == 1 ? 0 : 1
            });
        });
    });
});
