/**
 * Script para la gestión de sucursales
 * 
 * Incluye funcionalidades para:
 * - DataTables con reportes
 * - Creación y edición de sucursales
 * - Cambio de estado (activar/desactivar)
 * - Integración con Select2 para selección de empresas
 */

$(document).ready(function () {
    // Inicializar tooltips y Select2
    $('[data-toggle="tooltip"]').tooltip();
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });

    // Inicializar DataTable
    const tabla = $("#tablaSucursales").DataTable({
        "responsive": true,
        "autoWidth": false,
        "buttons": [
            {
                extend: 'collection',
                text: 'Reportes',
                orientation: 'landscape',
                buttons: [
                    {
                        text: 'Copiar',
                        extend: 'copy',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5]
                        }
                    },
                    {
                        extend: 'pdf',
                        title: 'Sucursales',
                        filename: 'sucursales_' + new Date().toISOString().slice(0, 10),
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
                                text: 'SUCURSALES',
                                style: {
                                    fontSize: 16,
                                    alignment: 'center',
                                    bold: true,
                                    margin: [0, 10, 0, 10]
                                }
                            });

                            doc.content.splice(1, 0, {
                                text: 'Sucursales registradas',
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
                                    columns: [
                                        {
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
                    },
                    {
                        extend: 'excel',
                        title: 'Sucursales',
                        messageTop: 'Registro de sucursales del sistema',
                        messageBottom: 'Documento generado el ' + new Date().toLocaleDateString('es-BO'),
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5],
                            format: {
                                body: function (data, row, column, node) {
                                    if (column === 3 || column === 4) {
                                        return $(node).find('span').text();
                                    }
                                    return data;
                                }
                            }
                        }
                    },
                    {
                        extend: 'csv',
                        text: 'CSV',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5]
                        }
                    },
                    {
                        extend: 'print',
                        text: 'Imprimir',
                        title: 'Sucursales',
                        messageTop: 'Reporte generado el ' + new Date().toLocaleDateString('es-BO'),
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5]
                        },
                        customize: function (win) {
                            $(win.document.body).find('table')
                                .addClass('table-striped')
                                .css('font-size', '12px');
                        }
                    }
                ]
            },
            {
                extend: 'colvis',
                text: 'Visualización de columnas'
            }
        ],
        "pageLength": 10,
        "lengthMenu": [
            [5, 10, 25, 50, -1],
            [5, 10, 25, 50, "Todos"]
        ],
        "language": {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ sucursales",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 sucursales",
            "sInfoFiltered": "(filtrado de un total de _MAX_ sucursales)",
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
        }
    });

    // Agregar botones a la tabla
    tabla.buttons().container().appendTo('#tablaSucursales_wrapper .col-md-6:eq(0)');

    // Botón para crear nueva sucursal
    $('#btnNuevaSucursal').on('click', function () {
        // Limpiar formulario
        $('#formSucursal')[0].reset();
        $('#idempresa').val('').trigger('change');

        // Configurar para creación
        $('#sucursalAction').val('create');
        $('#idSucursal').val('');
        $('#nombre').val('');
        $('#estado').val('1');

        // Cambiar apariencia del modal
        $('#modalSucursalHeader').removeClass('bg-warning').addClass('bg-primary');
        $('#modalSucursalLabel').text('Crear Nueva Sucursal');
        $('#btnGuardarSucursal').removeClass('btn-warning').addClass('btn-primary');
        $('#btnGuardarSucursal').html('<i class="fas fa-save"></i> Guardar');

        // Mostrar modal
        $('#modalSucursal').modal('show');
    });

    // Botón para editar sucursal
    $(document).on('click', '.btn-editar', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const idempresa = $(this).data('idempresa');
        const estado = $(this).data('estado');

        // Configurar para edición
        $('#sucursalAction').val('edit');
        $('#idSucursal').val(id);
        $('#nombre').val(nombre);
        $('#idempresa').val(idempresa).trigger('change');
        $('#estado').val(estado);

        // Cambiar apariencia del modal
        $('#modalSucursalHeader').removeClass('bg-primary').addClass('bg-warning');
        $('#modalSucursalLabel').text('Editar Sucursal');
        $('#btnGuardarSucursal').removeClass('btn-primary').addClass('btn-warning');
        $('#btnGuardarSucursal').html('<i class="fas fa-save"></i> Actualizar');

        // Mostrar modal
        $('#modalSucursal').modal('show');
    });

    // Procesar formulario
    $('#formSucursal').on('submit', function (e) {
        e.preventDefault();

        const action = $('#sucursalAction').val();
        const formData = $(this).serialize();
        
        let url, loadingMsg, successBtn;

        if (action === 'create') {
            url = `${baseUrl}controllers/sucursal/crear_sucursal_ajax.php`;
            loadingMsg = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            successBtn = '<i class="fas fa-save"></i> Guardar';
        } else {
            url = `${baseUrl}controllers/sucursal/actualizar_sucursal_ajax.php`;
            loadingMsg = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
            successBtn = '<i class="fas fa-save"></i> Actualizar';
        }

        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: formData,
            beforeSend: function () {
                $('#btnGuardarSucursal').prop('disabled', true).html(loadingMsg);
            },
            success: function (response) {
                if (response.success) {
                    // Cerrar modal y mostrar mensaje de éxito
                    $('#modalSucursal').modal('hide');
                    location.reload();
                } else {
                    // Mostrar mensaje de error
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                    $('#btnGuardarSucursal').prop('disabled', false).html(successBtn);
                }
            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error en la comunicación con el servidor'
                });
                $('#btnGuardarSucursal').prop('disabled', false).html(successBtn);
            }
        });
    });

    // Manejar cambio de estado
    $(document).on('click', '.cambiar-estado', function () {
        const id = $(this).data('id');
        const estadoActual = $(this).data('estado-actual');
        const usuarios = $(this).data('usuarios');

        // Validar si hay usuarios antes de desactivar
        if (estadoActual == 1 && usuarios > 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: mensajeErrorDesactivar
            });
            return;
        }

        const nuevoEstado = estadoActual == 1 ? 0 : 1;
        const textoEstado = estadoActual == 1 ? 'desactivar' : 'activar';
        const textoEstadoCapitalizado = textoEstado.charAt(0).toUpperCase() + textoEstado.slice(1);

        Swal.fire({
            title: `¿${textoEstadoCapitalizado} esta sucursal?`,
            text: `La sucursal será ${textoEstado}da.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: estadoActual == 1 ? '#d33' : '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: `Sí, ${textoEstado}`,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `${baseUrl}controllers/sucursal/desactivar_sucursal_ajax.php`,
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

    // Mejorar manejo del modal
    $('#modalSucursal').on('shown.bs.modal', function () {
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $('#modalSucursal')
        });
        $('#nombre').trigger('focus');
    });

    $('#modalSucursal').on('hidden.bs.modal', function () {
        $('#formSucursal')[0].reset();
        $('.is-invalid').removeClass('is-invalid');
        $('.select2').val('').trigger('change');
        $('[data-toggle="tooltip"]').tooltip('hide');
    });

    // Prevenir que el modal se cierre al hacer clic fuera
    $('#modalSucursal').data('backdrop', 'static');
    $('#modalSucursal').data('keyboard', false);
});