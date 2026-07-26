$(document).ready(function() {
    // Activar tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Guardar la pestaña activa en el almacenamiento local
    $('a[data-toggle="pill"]').on('shown.bs.tab', function(e) {
        localStorage.setItem('lastProductDetailTab', $(e.target).attr('id'));
    });

    // Restaurar la pestaña activa del almacenamiento local
    var lastTab = localStorage.getItem('lastProductDetailTab');
    if (lastTab) {
        $('#' + lastTab).tab('show');
    }

    // Cambiar el estado del producto con SweetAlert2
    $('#btnCambiarEstado').on('click', function() {
        const boton = $(this);
        const productoId = boton.data('id');
        const estadoActual = parseInt(boton.attr('data-estado'), 10);
        const nombreProducto = boton.data('nombre');

        const tituloAlerta = estadoActual == 1 ?
            `¿Desactivar producto "${nombreProducto}"?` :
            `¿Activar producto "${nombreProducto}"?`;

        const textoAlerta = estadoActual == 1 ?
            'El producto no estará disponible para venta hasta que sea activado nuevamente.' :
            'El producto estará disponible nuevamente para venta.';

        const confirmButtonText = estadoActual == 1 ? 'Sí, desactivar' : 'Sí, activar';
        const cancelButtonText = 'Cancelar';

        Swal.fire({
            title: tituloAlerta,
            text: textoAlerta,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: estadoActual == 1 ? '#d33' : '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: confirmButtonText,
            cancelButtonText: cancelButtonText
        }).then((result) => {
            if (result.isConfirmed) {
                submitCsrfForm(baseUrl + 'controllers/productos/desactivar_producto.php', {
                    id: productoId,
                    estado: estadoActual
                });
            }
        });
    });
});
