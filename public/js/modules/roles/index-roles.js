/**
 * Script para la gestión de roles
 *
 * Este archivo contiene funciones para manejar operaciones AJAX
 * relacionadas con los roles (crear, editar, cambiar estado, eliminar)
 */

$(document).ready(function () {
    // Inicializar DataTable
    const tabla = $("#tablaRoles").DataTable({
        "responsive": true,
        "autoWidth": false,
        buttons: [{
            extend: 'collection',
            text: 'Reportes',
            orientation: 'landscape',
            buttons: [{
                text: 'Copiar',
                extend: 'copy',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5]
                }
            }, {
                extend: 'pdf',
                title: 'Roles',
                filename: 'roles_' + new Date().toISOString().slice(0, 10),
                pageSize: 'LETTER',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5]
                },
                customize: function (doc) {
                    doc.defaultStyle.fontSize = 10;
                    doc.styles.tableHeader.fontSize = 11;
                    doc.styles.tableHeader.fillColor = '#4b545c';
                    doc.styles.tableHeader.color = '#ffffff';

                    doc.content.splice(0, 1, {
                        text: 'ROLES',
                        style: {
                            fontSize: 16,
                            alignment: 'center',
                            bold: true,
                            margin: [0, 10, 0, 10]
                        }
                    });

                    doc.content.splice(1, 0, {
                        text: 'Roles del sistema',
                        style: {
                            fontSize: 11,
                            alignment: 'center',
                            italic: true,
                            margin: [0, 0, 0, 10]
                        }
                    });

                    doc.content.splice(2, 0, {
                        text: 'Generado el: ' + new Date().toLocaleString('es-BO'),
                        style: {
                            fontSize: 9,
                            alignment: 'right',
                            margin: [0, 0, 0, 10]
                        }
                    });

                    doc.footer = function (currentPage, pageCount) {
                        return {
                            columns: [{
                                text: 'Sistema de Gestión',
                                alignment: 'left',
                                fontSize: 8
                            },
                            {
                                text: 'Página ' + currentPage + ' de ' + pageCount,
                                alignment: 'center',
                                fontSize: 8
                            },
                            {
                                text: 'Confidencial',
                                alignment: 'right',
                                fontSize: 8
                            }
                            ],
                            margin: [40, 0]
                        };
                    };
                }
            }, {
                extend: 'excel',
                title: 'Roles',
                messageTop: 'Registro de roles del sistema',
                messageBottom: 'Documento generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5]
                }
            }, {
                extend: 'csv',
                text: 'CSV',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5]
                }
            }, {
                extend: 'print',
                text: 'Imprimir',
                title: 'Roles',
                messageTop: 'Reporte generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5]
                },
                customize: function (win) {
                    $(win.document.body).find('table')
                        .addClass('table-striped')
                        .css('font-size', '12px');
                }
            }]
        },
        {
            extend: 'colvis',
            text: 'Visualización de columnas'
        }
        ],
        "pageLength": 10,
        lengthMenu: [
            [5, 10, 25, 50, -1],
            [5, 10, 25, 50, "Todos"]
        ],
        "language": {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ roles",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 roles",
            "sInfoFiltered": "(filtrado de un total de _MAX_ roles)",
            "sInfoPostFix": "",
            "sSearch": "Buscar:",
            "sUrl": "",
            "sInfoThousands": ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "Siguiente",
                "sPrevious": "Anterior"
            },
            "oAria": {
                "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
        },
        "drawCallback": function () {
            initializeTooltips();
        }
    });

    // Agregar botones a la tabla
    tabla.buttons().container().appendTo('#tablaRoles_wrapper .col-md-6:eq(0)');

    initializeTooltips();
    initializeSelect2();

    // Botón para crear nuevo rol
    $('#btnNuevoRol').on('click', function () {
        $('#formRol')[0].reset();

        $('#rolAction').val('create');
        $('#idRol').val('');
        $('#nombre').val('').prop('readonly', false);
        $('#descripcion').val('');
        $('#dashboard').val('dashboard_general.php').trigger('change');
        $('#nombreAyuda').text('Nombre único para el rol');

        $('#modalRolHeader').removeClass('bg-warning').addClass('bg-primary');
        $('#modalRolLabel').text('Crear Nuevo Rol');
        $('#btnGuardarRol').removeClass('btn-warning').addClass('btn-primary');
        $('#btnGuardarRol').html('<i class="fas fa-save"></i> Guardar');

        $('#modalRol').modal('show');
    });

    // Botón para editar rol
    $(document).on('click', '.btn-editar', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const descripcion = $(this).data('descripcion');
        const dashboard = $(this).data('dashboard');
        const esSistema = $(this).data('es-sistema') == 1;

        $('#rolAction').val('edit');
        $('#idRol').val(id);
        $('#nombre').val(nombre).prop('readonly', esSistema);
        $('#descripcion').val(descripcion);

        $('#nombreAyuda').text(esSistema
            ? 'Este es un rol de sistema: su nombre no puede modificarse'
            : 'Nombre único para el rol');

        $('#modalRolHeader').removeClass('bg-primary').addClass('bg-warning');
        $('#modalRolLabel').text('Editar Rol');
        $('#btnGuardarRol').removeClass('btn-primary').addClass('btn-warning');
        $('#btnGuardarRol').html('<i class="fas fa-save"></i> Actualizar');

        $('#modalRol').modal('show');

        // El valor del select2 se aplica tras 'shown.bs.modal' (donde se inicializa
        // con dropdownParent), así que se difiere con la propia inicialización.
        $('#modalRol').one('shown.bs.modal', function () {
            $('#dashboard').val(dashboard).trigger('change');
        });
    });

    // Procesar formulario
    $('#formRol').on('submit', function (e) {
        e.preventDefault();

        const action = $('#rolAction').val();

        const formData = $(this).serialize();
        let url, loadingMsg, successBtn;

        if (action === 'create') {
            url = `${baseUrl}controllers/rol/crear_rol_ajax.php`;
            loadingMsg = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            successBtn = '<i class="fas fa-save"></i> Guardar';
        } else {
            url = `${baseUrl}controllers/rol/actualizar_rol_ajax.php`;
            loadingMsg = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
            successBtn = '<i class="fas fa-save"></i> Actualizar';
        }

        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: formData,
            beforeSend: function () {
                $('#btnGuardarRol').prop('disabled', true).html(loadingMsg);
            },
            success: function (response) {
                if (response.success) {
                    $('#modalRol').modal('hide');
                    location.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                    $('#btnGuardarRol').prop('disabled', false).html(successBtn);
                }
            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error en la comunicación con el servidor'
                });
                $('#btnGuardarRol').prop('disabled', false).html(successBtn);
            }
        });
    });

    // Manejar cambio de estado
    $(document).on('click', '.cambiar-estado', function () {
        const id = $(this).data('id');
        const estadoActual = $(this).data('estado-actual');

        const nuevoEstado = estadoActual == 1 ? 0 : 1;
        const textoEstado = estadoActual == 1 ? 'desactivar' : 'activar';
        const textoEstadoCapitalizado = textoEstado.charAt(0).toUpperCase() + textoEstado.slice(1);

        Swal.fire({
            title: `¿${textoEstadoCapitalizado} este rol?`,
            text: `El rol será ${textoEstado}do.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: estadoActual == 1 ? '#d33' : '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: `Sí, ${textoEstado}`,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `${baseUrl}controllers/rol/cambiar_estado_rol_ajax.php`,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        id: id,
                        estado_actual: estadoActual
                    },
                    success: function (response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error(xhr.responseText);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Ocurrió un error en la comunicación con el servidor'
                        });
                    }
                });
            }
        });
    });

    // Manejar eliminación de rol
    $(document).on('click', '.btn-eliminar', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const usuarios = $(this).data('usuarios');

        if (usuarios > 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: `No se puede eliminar el rol "${nombre}" porque tiene ${usuarios} usuario(s) asignado(s)`
            });
            return;
        }

        Swal.fire({
            title: `¿Eliminar el rol "${nombre}"?`,
            text: 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `${baseUrl}controllers/rol/eliminar_rol_ajax.php`,
                    type: 'POST',
                    dataType: 'json',
                    data: { id: id },
                    success: function (response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error(xhr.responseText);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Ocurrió un error en la comunicación con el servidor'
                        });
                    }
                });
            }
        });
    });

    // Limpiar modal al cerrarlo para evitar problemas en dispositivos móviles
    $('#modalRol').on('hidden.bs.modal', function () {
        $('#formRol')[0].reset();
        $('#nombre').prop('readonly', false);
        $('.select2').val('dashboard_general.php').trigger('change');

        $('.is-invalid').removeClass('is-invalid');

        $('#formRol').css({
            'transform': 'none',
            'height': 'auto'
        });

        $('[data-toggle="tooltip"]').tooltip('hide');
    });

    // Mejorar el manejo del modal en dispositivos móviles
    $('#modalRol').on('shown.bs.modal', function () {
        $('.modal-body').css('overflow-y', 'auto');

        initializeSelect2('.select2', { dropdownParent: $('#modalRol') });

        $('#nombre').trigger('focus');
    });

    // Prevenir que el modal se cierre al hacer clic fuera (para evitar pérdida de datos)
    $('#modalRol').data('backdrop', 'static');
    $('#modalRol').data('keyboard', false);
});
