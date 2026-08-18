document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-cambiar-estado').forEach(function (boton) {
        boton.addEventListener('click', function () {
            const estadoActual = this.dataset.estado;
            const nombreCliente = this.dataset.nombre;
            const activo = estadoActual == 1;

            confirmarCambioEstado({
                id: this.dataset.id,
                estadoActual: estadoActual,
                titulo: activo ? `¿Desactivar a ${nombreCliente}?` : `¿Activar a ${nombreCliente}?`,
                texto: activo
                    ? 'El cliente no podrá realizar compras hasta que sea activado nuevamente.'
                    : 'El cliente podrá realizar nuevas compras.',
                actionUrl: `${baseUrl}controllers/clientes/desactivar_cliente.php`
            });
        });
    });
});

$(document).ready(function () {
    initializeTooltips();

    // Guardar la pestaña activa en el almacenamiento local
    $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
        localStorage.setItem('lastClientDetailTab', $(e.target).attr('id'));
    });

    // Restaurar la pestaña activa del almacenamiento local
    var lastTab = localStorage.getItem('lastClientDetailTab');
    if (lastTab) {
        $('#' + lastTab).tab('show');
    }
});
