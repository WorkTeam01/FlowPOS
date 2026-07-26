document.addEventListener('DOMContentLoaded', function () {
    const btnCancelar = document.querySelector('.btn-cancelar');
    if (btnCancelar) {
        btnCancelar.addEventListener('click', function () {
            const compraId = this.dataset.id;
            confirmarAccion(compraId, 'cancelar');
        });
    }

    function confirmarAccion(id, accion) {
        Swal.fire({
            title: '¿Cancelar esta compra?',
            text: 'La compra será cancelada y el stock de productos será revertido. Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-check mr-2"></i> Sí, cancelar',
            cancelButtonText: '<i class="fas fa-times mr-2"></i> Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Procesando...',
                    html: 'Cancelando la compra y ajustando inventario',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                submitCsrfForm(`${baseUrl}controllers/compras/cambiar_estado_compra.php`, {
                    id: id,
                    accion: accion
                });
            }
        });
    }
});
