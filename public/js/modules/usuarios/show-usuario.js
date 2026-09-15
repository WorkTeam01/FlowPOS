$(document).ready(function () {
    // Recordar la pestaña activa entre visitas
    $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
        localStorage.setItem('lastUsuarioDetailTab', $(e.target).attr('id'));
    });
    var lastTab = localStorage.getItem('lastUsuarioDetailTab');
    if (lastTab && document.getElementById(lastTab)) {
        $('#' + lastTab).tab('show');
    }

    // Cambiar el estado del usuario
    $('#btnCambiarEstado').on('click', function (e) {
        e.preventDefault();

        const boton = $(this);
        const estadoActual = parseInt(boton.attr('data-estado'), 10);

        confirmarCambioEstado({
            id: boton.data('id'),
            estadoActual,
            titulo: estadoActual == 1 ?
                `¿Desactivar a ${boton.data('nombre')}?` :
                `¿Activar a ${boton.data('nombre')}?`,
            texto: estadoActual == 1 ?
                'El usuario no podrá acceder al sistema hasta que sea activado nuevamente.' :
                'El usuario podrá acceder nuevamente al sistema.',
            actionUrl: baseUrl + 'controllers/usuarios/desactivar_usuario.php'
        });
    });
});
