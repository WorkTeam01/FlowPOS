$(document).ready(function () {
    // Inicializar DataTable para productos
    $("#tablaProductos").DataTable({
        "responsive": true,
        "autoWidth": false,
        "buttons": [{
            "extend": 'collection',
            "text": 'Reportes',
            "orientation": 'landscape',
            "buttons": [{
                "text": 'Copiar',
                "extend": 'copy',
                "exportOptions": {
                    "columns": [0, 1, 2, 3, 4, 5, 6]
                }
            }, {
                "extend": 'pdf',
                "title": 'Inventario de Productos - ' + window.APP.name + '',
                "filename": 'inventario_productos_' + new Date().toISOString().slice(0, 10),
                "pageSize": 'LETTER',
                "exportOptions": {
                    "columns": [0, 1, 2, 3, 4, 5, 6]
                },
                "customize": function (doc) {
                    // Estilo básico
                    doc.defaultStyle.fontSize = 10;
                    doc.styles.tableHeader.fontSize = 11;
                    doc.styles.tableHeader.fillColor = '#4b545c';
                    doc.styles.tableHeader.color = '#ffffff';

                    // Agregar título con fecha
                    doc.content.splice(0, 1, {
                        "text": 'INVENTARIO DE PRODUCTOS - ' + window.APP.name.toUpperCase(),
                        "style": {
                            "fontSize": 16,
                            "alignment": 'center',
                            "bold": true,
                            "margin": [0, 10, 0, 10]
                        }
                    });

                    // Agregar subtítulo
                    doc.content.splice(1, 0, {
                        "text": 'Productos registrados en el sistema',
                        "style": {
                            "fontSize": 11,
                            "alignment": 'center',
                            "italic": true,
                            "margin": [0, 0, 0, 10]
                        }
                    });

                    // Agregar fecha de generación
                    doc.content.splice(2, 0, {
                        "text": 'Generado el: ' + new Date().toLocaleString('es-BO'),
                        "style": {
                            "fontSize": 9,
                            "alignment": 'right',
                            "margin": [0, 0, 0, 10]
                        }
                    });

                    // Formatear columnas numéricas
                    doc.content[3].table.body.forEach(function (row) {
                        // Columna de precio (índice 5)
                        if (row[5]) row[5].text = row[5].text.replace(/[^\d.-]/g, '');
                        // Columna de stock (índice 6)
                        if (row[6]) row[6].text = row[6].text.replace(/[^\d.-]/g, '');
                    });

                    // Pie de página
                    doc.footer = function (currentPage, pageCount) {
                        return {
                            "columns": [{
                                "text": 'Sistema de Gestión - ' + window.APP.name,
                                "alignment": 'left',
                                "fontSize": 8
                            },
                            {
                                "text": 'Página ' + currentPage + ' de ' + pageCount,
                                "alignment": 'center',
                                "fontSize": 8
                            },
                            {
                                "text": 'Confidencial',
                                "alignment": 'right',
                                "fontSize": 8
                            }
                            ],
                            "margin": [40, 0]
                        };
                    };
                }
            }, {
                "extend": 'excel',
                "title": 'Inventario de Productos - ' + window.APP.name + '',
                "messageTop": 'Registro de productos en inventario',
                "messageBottom": 'Documento generado el ' + new Date().toLocaleDateString('es-BO'),
                "exportOptions": {
                    "columns": [0, 1, 2, 3, 4, 5, 6],
                    "format": {
                        "body": function (data, row, column, node) {
                            // Formatear columna de estado
                            if (column === 7) {
                                return $(node).find('span').text();
                            }
                            // Formatear columna de precio
                            if (column === 5) {
                                return data.replace(/[^\d.-]/g, '');
                            }
                            return data;
                        }
                    }
                }
            }, {
                "extend": 'csv',
                "text": 'CSV',
                "exportOptions": {
                    "columns": [0, 1, 2, 3, 4, 5, 6]
                }
            }, {
                "extend": 'print',
                "text": 'Imprimir',
                "title": 'Inventario de Productos - ' + window.APP.name + '',
                "messageTop": 'Reporte generado el ' + new Date().toLocaleDateString('es-BO'),
                "exportOptions": {
                    "columns": [0, 1, 2, 3, 4, 5, 6]
                },
                "customize": function (win) {
                    $(win.document.body).find('table')
                        .addClass('table-striped')
                        .css('font-size', '12px');

                    // Agregar título personalizado
                    $(win.document.body).prepend(
                        '<h3>Inventario de Productos - ' + window.APP.name + '</h3>' +
                        '<p>Reporte generado el ' + new Date().toLocaleDateString('es-BO') + '</p>'
                    );
                }
            }]
        },
        {
            "extend": 'colvis',
            "text": 'Visualización de columnas'
        }
        ],
        "pageLength": 5,
        "lengthMenu": [
            [3, 5, 10, 25, 50],
            [3, 5, 10, 25, 50]
        ],
        "language": {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ productos",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 productos",
            "sInfoFiltered": "(filtrado de un total de _MAX_ productos)",
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
    }).buttons().container().appendTo('#tablaProductos_wrapper .col-md-6:eq(0)');
});