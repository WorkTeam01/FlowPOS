$(document).ready(function () {
    // Activar tooltips
    initializeTooltips();

    // Guardar la pestaña activa en el almacenamiento local
    $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
        localStorage.setItem('lastProductDetailTab', $(e.target).attr('id'));
    });

    // Restaurar la pestaña activa del almacenamiento local
    var lastTab = localStorage.getItem('lastProductDetailTab');
    if (lastTab) {
        $('#' + lastTab).tab('show');
    }

    // Cambiar el estado del producto con SweetAlert2
    $('#btnCambiarEstado').on('click', function () {
        const boton = $(this);
        const estadoActual = parseInt(boton.attr('data-estado'), 10);
        const nombreProducto = boton.data('nombre');
        const activo = estadoActual == 1;

        confirmarCambioEstado({
            id: boton.data('id'),
            estadoActual: estadoActual,
            titulo: activo ? `¿Desactivar producto "${nombreProducto}"?` : `¿Activar producto "${nombreProducto}"?`,
            texto: activo
                ? 'El producto no estará disponible para venta hasta que sea activado nuevamente.'
                : 'El producto estará disponible nuevamente para venta.',
            actionUrl: baseUrl + 'controllers/productos/desactivar_producto.php'
        });
    });
});
