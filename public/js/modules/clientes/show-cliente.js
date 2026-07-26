function cambiarEstado(clienteId, nuevoEstado) {
    // El controlador espera el estado actual, no el nuevo
    const estadoActual = nuevoEstado == 0 ? 1 : 0;

    const tituloAlerta = nuevoEstado == 1 ? '¿Activar cliente?' : '¿Desactivar cliente?';
    const textoAlerta = nuevoEstado == 1 ?
        "El cliente podrá realizar nuevas compras." :
        "El cliente no podrá realizar compras hasta que sea activado nuevamente.";
    const confirmButtonText = nuevoEstado == 1 ? 'Sí, activar' : 'Sí, desactivar';

    Swal.fire({
        title: tituloAlerta,
        text: textoAlerta,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: nuevoEstado == 1 ? '#28a745' : '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: confirmButtonText,
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            submitCsrfForm(`${baseUrl}controllers/clientes/desactivar_cliente.php`, {
                id: clienteId,
                estado: estadoActual
            });
        }
    });
}
