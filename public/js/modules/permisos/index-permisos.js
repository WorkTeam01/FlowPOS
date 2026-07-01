$(document).ready(function () {
    // Inicializar DataTable
    $("#tablaPermisos").DataTable({
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
                    columns: [0, 1, 2]
                }
            }, {
                extend: 'pdf',
                title: 'Permisos del Sistema - ' + window.APP.name + '',
                filename: 'permisos_sistema_' + new Date().toISOString().slice(0, 10),
                pageSize: 'LETTER',
                exportOptions: {
                    columns: [0, 1, 2]
                },
                customize: function (doc) {
                    // Estilo básico
                    doc.defaultStyle.fontSize = 10;
                    doc.styles.tableHeader.fontSize = 11;
                    doc.styles.tableHeader.fillColor = '#4b545c';
                    doc.styles.tableHeader.color = '#ffffff';

                    // Agregar título con fecha
                    doc.content.splice(0, 1, {
                        text: 'PERMISOS DEL SISTEMA - ' + window.APP.name.toUpperCase(),
                        style: {
                            fontSize: 16,
                            alignment: 'center',
                            bold: true,
                            margin: [0, 10, 0, 10]
                        }
                    });

                    // Agregar título
                    let tituloTexto = 'Permisos registrados';

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

                    // Formatear estado
                    doc.content[3].table.body.forEach(function (row) {
                        if (row[2]) { // Columna de Estado
                            row[2].alignment = 'center';
                        }
                    });

                    // Pie de página
                    doc.footer = function (currentPage, pageCount) {
                        return {
                            columns: [{
                                text: 'Sistema de Gestión - ' + window.APP.name,
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
                title: 'Permisos del Sistema - ' + window.APP.name + '',
                messageTop: 'Registro de permisos del sistema',
                messageBottom: 'Documento generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: {
                    columns: [0, 1, 2],
                    format: {
                        body: function (data, row, column, node) {
                            if (column === 2) { // Columna de estado
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
                    columns: [0, 1, 2]
                }
            }, {
                extend: 'print',
                text: 'Imprimir',
                title: 'Permisos del Sistema - ' + window.APP.name + '',
                messageTop: 'Reporte generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: {
                    columns: [0, 1, 2],
                    format: {
                        body: function (data, row, column, node) {
                            if (column === 2) { // Columna de estado
                                return $(node).find('span').text();
                            }
                            return data;
                        }
                    }
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
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, "Todos"]
        ],
        "language": {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ Permisos",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 Permisos",
            "sInfoFiltered": "(filtrado de un total de _MAX_ Permisos)",
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
    }).buttons().container().appendTo('#tablaPermisos_wrapper .col-md-6:eq(0)');

    // Inicializar tooltips
    $('[data-toggle="tooltip"]').tooltip();
});