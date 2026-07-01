$(document).ready(function () {
    // Inicializar DataTable
    $("#tablaSesiones").DataTable({
        "responsive": true,
        "autoWidth": false,
        buttons: [{
            extend: 'collection',
            text: 'Exportar',
            orientation: 'landscape',
            buttons: [{
                text: 'Copiar',
                extend: 'copy',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            }, {
                extend: 'pdf',
                title: 'Sesiones del Sistema - ' + window.APP.name + '',
                filename: 'sesiones_sistema_' + new Date().toISOString().slice(0, 10),
                pageSize: 'LETTER',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                },
                customize: function (doc) {
                    // Estilo básico
                    doc.defaultStyle.fontSize = 10;
                    doc.styles.tableHeader.fontSize = 11;
                    doc.styles.tableHeader.fillColor = '#4b545c';
                    doc.styles.tableHeader.color = '#ffffff';

                    // Agregar título con fecha
                    doc.content.splice(0, 1, {
                        text: 'SESIONES DEL SISTEMA - ' + window.APP.name.toUpperCase(),
                        style: {
                            fontSize: 16,
                            alignment: 'center',
                            bold: true,
                            margin: [0, 10, 0, 10]
                        }
                    });

                    // Agregar título
                    let tituloTexto = 'Monitoreo de sesiones de usuarios';

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
                        if (row[6]) { // Columna de Estado
                            row[6].alignment = 'center';
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
                title: 'Sesiones del Sistema - ' + window.APP.name + '',
                messageTop: 'Monitoreo de sesiones de usuarios',
                messageBottom: 'Documento generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6],
                    format: {
                        body: function (data, row, column, node) {
                            // Extraer el nombre del usuario de la columna usuario (HTML complejo)
                            if (column === 1) {
                                return $(node).find('.username a').text();
                            }
                            // Formatear estado
                            if (column === 6) {
                                return $(node).find('span').text();
                            }
                            // Extraer dispositivo
                            if (column === 5) {
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
                    columns: [0, 1, 2, 3, 4, 5, 6],
                    format: {
                        body: function (data, row, column, node) {
                            // Extraer el nombre del usuario de la columna usuario (HTML complejo)
                            if (column === 1) {
                                return $(node).find('.username a').text();
                            }
                            // Formatear estado
                            if (column === 6) {
                                return $(node).find('span').text();
                            }
                            // Extraer dispositivo
                            if (column === 5) {
                                return $(node).find('span').text();
                            }
                            return data;
                        }
                    }
                }
            }, {
                extend: 'print',
                text: 'Imprimir',
                title: 'Sesiones del Sistema - ' + window.APP.name + '',
                messageTop: 'Reporte generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6],
                    format: {
                        body: function (data, row, column, node) {
                            // Columnas que necesitan extraer texto específico
                            if (column === 1 || column === 5 || column === 6) {
                                return $(node).text().trim();
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
            text: 'Columnas'
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
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ Sesiones",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 Sesiones",
            "sInfoFiltered": "(filtrado de un total de _MAX_ Sesiones)",
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
    }).buttons().container().appendTo('#tablaSesiones_wrapper .col-md-6:eq(0)');
});