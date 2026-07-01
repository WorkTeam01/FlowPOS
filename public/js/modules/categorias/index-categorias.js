/**
 * Script para la gestión de categorías
 * 
 * Este archivo contiene funciones para manejar operaciones AJAX
 * relacionadas con las categorías (crear, editar, cambiar estado)
 */

$(document).ready(function () {
    // Inicializar tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Inicializar DataTable
    const tabla = $("#tablaCategorias").DataTable({
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
                title: 'Categorías',
                filename: 'categorias_' + new Date().toISOString().slice(0, 10),
                pageSize: 'LETTER',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5]
                },
                customize: function (doc) {
                    // Estilo básico
                    doc.defaultStyle.fontSize = 10;
                    doc.styles.tableHeader.fontSize = 11;
                    doc.styles.tableHeader.fillColor = '#4b545c';
                    doc.styles.tableHeader.color = '#ffffff';

                    // Agregar título con fecha
                    doc.content.splice(0, 1, {
                        text: 'CATEGORÍAS',
                        style: {
                            fontSize: 16,
                            alignment: 'center',
                            bold: true,
                            margin: [0, 10, 0, 10]
                        }
                    });

                    // Agregar subtítulo
                    let tituloTexto = 'Categorías registradas';

                    doc.content.splice(1, 0, {
                        text: tituloTexto,
                        style: {
                            fontSize: 11,
                            alignment: 'center',
                            italic: true,
                            margin: [0, 0, 0, 10]
                        }
                    });

                    // Agregar fecha de generación
                    doc.content.splice(2, 0, {
                        text: 'Generado el: ' + new Date().toLocaleString('es-BO'),
                        style: {
                            fontSize: 9,
                            alignment: 'right',
                            margin: [0, 0, 0, 10]
                        }
                    });

                    // Pie de página
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
                title: 'Categorías',
                messageTop: 'Registro de categorías del sistema',
                messageBottom: 'Documento generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5],
                    format: {
                        body: function (data, row, column, node) {
                            if (column === 3) {
                                return $(node).find('span').text();
                            }
                            return data;
                        }
                    }
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
                title: 'Categorías',
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
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ categorías",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 categorías",
            "sInfoFiltered": "(filtrado de un total de _MAX_ categorías)",
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
    tabla.buttons().container().appendTo('#tablaCategorias_wrapper .col-md-6:eq(0)');

    // Botón para crear nueva categoría
    $('#btnNuevaCategoria').on('click', function () {
        // Limpiar formulario
        $('#formCategoria')[0].reset();

        // Configurar para creación
        $('#categoriaAction').val('create');
        $('#idCategoria').val('');
        $('#nombre').val('');
        $('#estado').val('1');

        // Cambiar apariencia del modal
        $('#modalCategoriaHeader').removeClass('bg-warning').addClass('bg-primary');
        $('#modalCategoriaLabel').text('Crear Nueva Categoría');
        $('#btnGuardarCategoria').removeClass('btn-warning').addClass('btn-primary');
        $('#btnGuardarCategoria').html('<i class="fas fa-save"></i> Guardar');

        // Mostrar modal
        $('#modalCategoria').modal('show');
    });

    // Botón para editar categoría
    $(document).on('click', '.btn-editar', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const estado = $(this).data('estado');

        // Configurar para edición
        $('#categoriaAction').val('edit');
        $('#idCategoria').val(id);
        $('#nombre').val(nombre);
        $('#estado').val(estado);

        // Cambiar apariencia del modal
        $('#modalCategoriaHeader').removeClass('bg-primary').addClass('bg-warning');
        $('#modalCategoriaLabel').text('Editar Categoría');
        $('#btnGuardarCategoria').removeClass('btn-primary').addClass('btn-warning');
        $('#btnGuardarCategoria').html('<i class="fas fa-save"></i> Actualizar');

        // Mostrar modal
        $('#modalCategoria').modal('show');
    });

    // Procesar formulario
    $('#formCategoria').on('submit', function (e) {
        e.preventDefault();

        const action = $('#categoriaAction').val();
        
        const formData = $(this).serialize();
        let url, loadingMsg, successBtn;

        if (action === 'create') {
            url = `${baseUrl}controllers/categoria/crear_categoria_ajax.php`;
            loadingMsg = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            successBtn = '<i class="fas fa-save"></i> Guardar';
        } else {
            url = `${baseUrl}controllers/categoria/actualizar_categoria_ajax.php`;
            loadingMsg = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
            successBtn = '<i class="fas fa-save"></i> Actualizar';
        }

        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: formData,
            beforeSend: function () {
                $('#btnGuardarCategoria').prop('disabled', true).html(loadingMsg);
            },
            success: function (response) {
                if (response.success) {
                    // Cerrar modal y mostrar mensaje de éxito
                    $('#modalCategoria').modal('hide');

                    // Recargar la página para mostrar el mensaje con mensajes.php
                    location.reload();
                } else {
                    // Mostrar mensaje de error
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                    $('#btnGuardarCategoria').prop('disabled', false).html(successBtn);
                }
            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error en la comunicación con el servidor'
                });
                $('#btnGuardarCategoria').prop('disabled', false).html(successBtn);
            }
        });
    });

    // Manejar cambio de estado
    $(document).on('click', '.cambiar-estado', function () {
        const id = $(this).data('id');
        const estadoActual = $(this).data('estado-actual');
        const productos = $(this).data('productos');

        // Validar si hay productos antes de desactivar
        if (estadoActual == 1 && productos > 0) {
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
            title: `¿${textoEstadoCapitalizado} esta categoría?`,
            text: `La categoría será ${textoEstado}da.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: estadoActual == 1 ? '#d33' : '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: `Sí, ${textoEstado}`,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `${baseUrl}controllers/categoria/desactivar_categoria_ajax.php`,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        id: id,
                        estado_actual: estadoActual
                    },
                    success: function (response) {
                        if (response.success) {
                            // Recargar la página para mostrar el mensaje con mensajes.php
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
    $('#modalCategoria').on('hidden.bs.modal', function () {
        $('#formCategoria')[0].reset();

        // Asegurar que no queden validaciones o estados de error
        $('.is-invalid').removeClass('is-invalid');

        // Resetear propiedades importantes para dispositivos móviles
        $('#formCategoria').css({
            'transform': 'none',
            'height': 'auto'
        });

        // Desactivar cualquier tooltip activo
        $('[data-toggle="tooltip"]').tooltip('hide');
    });

    // Mejorar el manejo del modal en dispositivos móviles
    $('#modalCategoria').on('shown.bs.modal', function () {
        // Asegurar que el scroll funcione correctamente en móviles
        $('.modal-body').css('overflow-y', 'auto');

        // Enfocar el primer campo del formulario
        $('#nombre').trigger('focus');
    });

    // Prevenir que el modal se cierre al hacer clic fuera (para evitar pérdida de datos)
    $('#modalCategoria').data('backdrop', 'static');
    $('#modalCategoria').data('keyboard', false);
});