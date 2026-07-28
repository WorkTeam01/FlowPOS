document.addEventListener('DOMContentLoaded', function () {
    const btnAnular = document.querySelector('.btn-anular-venta');

    if (btnAnular) {
        btnAnular.addEventListener('click', function () {
            const ventaId = this.dataset.id;
            const nombreVenta = this.dataset.nombre;

            Swal.fire({
                title: `¿Anular ${nombreVenta}?`,
                text: "Esta acción restaurará el stock de los productos y no se puede deshacer.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d ',
                confirmButtonText: 'Sí, anular',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitCsrfForm(`${baseUrl}controllers/ventas/anular_venta.php`, {
                        id: ventaId
                    });
                }
            });
        });
    }
});
