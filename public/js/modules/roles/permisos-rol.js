/**
 * Script para la matriz de permisos por rol (views/roles/permisos.php)
 */

$(document).ready(function () {
    initializeTooltips();

    $('.btn-guardar-rol').on('click', function () {
        const $btn = $(this);
        const idrol = $btn.data('idrol');

        const idpermisos = $(`.chk-permiso-rol[data-idrol="${idrol}"]:checked`)
            .map(function () { return $(this).val(); })
            .get();

        const originalHtml = $btn.html();

        $.ajax({
            url: `${baseUrl}controllers/rol/guardar_permisos_rol.php`,
            type: 'POST',
            dataType: 'json',
            data: {
                idrol: idrol,
                idpermiso: idpermisos
            },
            beforeSend: function () {
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
            },
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Permisos actualizados',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            },
            error: function (xhr) {
                console.error(xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error en la comunicación con el servidor'
                });
            },
            complete: function () {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });
});
