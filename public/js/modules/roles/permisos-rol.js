/**
 * Script para la matriz de permisos por rol (views/roles/permisos.php)
 */

$(document).ready(function () {
    initializeTooltips();

    // Área táctil: un clic en el relleno de la celda (no sobre el checkbox
    // ni su label) alterna la casilla, para llegar al mínimo de 44px.
    $('#tablaMatrizPermisos').on('click', 'td.chk-celda', function (e) {
        if (e.target !== this) {
            return;
        }
        const $chk = $(this).find('.chk-permiso-rol');
        if (!$chk.prop('disabled')) {
            $chk.prop('checked', !$chk.prop('checked')).trigger('change');
        }
    });

    // Marca la columna (botón Guardar) con cambios sin persistir.
    $('#tablaMatrizPermisos').on('change', '.chk-permiso-rol', function () {
        const idrol = $(this).data('idrol');
        $(`.btn-guardar-rol[data-idrol="${idrol}"]`).addClass('tiene-cambios');
    });

    $(window).on('beforeunload', function () {
        if ($('.btn-guardar-rol.tiene-cambios').length > 0) {
            return 'Hay cambios de permisos sin guardar.';
        }
    });

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
                    $btn.removeClass('tiene-cambios');
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
