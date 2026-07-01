$(document).ready(function () {
    // Inicializar DataTable para compras
    $("#tablaCompras").DataTable({
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
                    "columns": [0, 1, 2, 3, 4, 5],
                    "format": {
                        "body": function (data, row, column, node) {
                            // Formatear estado para copiar el texto del badge
                            if (column === 5) {
                                return $(node).find('span').text();
                            }
                            return data;
                        }
                    }
                }
            }, {
                "extend": 'pdf',
                "title": 'Registro de Compras',
                "filename": 'compras_registro_' + new Date().toISOString().slice(0, 10),
                "pageSize": 'LETTER',
                "exportOptions": {
                    "columns": [0, 1, 2, 3, 4, 5],
                    "format": {
                        "body": function (data, row, column, node) {
                            // Formatear estado para PDF
                            if (column === 5) {
                                const estado = $(node).find('span').text();
                                return estado === 'Activa' ? 'Activa (1)' : 'Cancelada (0)';
                            }
                            return data;
                        }
                    }
                },
                "customize": function (doc) {
                    // Estilo básico
                    doc.defaultStyle.fontSize = 10;
                    doc.styles.tableHeader.fontSize = 11;
                    doc.styles.tableHeader.fillColor = '#4b545c';
                    doc.styles.tableHeader.color = '#ffffff';

                    // Agregar título con fecha
                    doc.content.splice(0, 1, {
                        "text": 'REGISTRO DE COMPRAS',
                        "style": {
                            "fontSize": 16,
                            "alignment": 'center',
                            "bold": true,
                            "margin": [0, 10, 0, 10]
                        }
                    });

                    // Agregar subtítulo
                    doc.content.splice(1, 0, {
                        "text": 'Historial de compras del sistema',
                        "style": {
                            "fontSize": 11,
                            "alignment": 'center',
                            "italic": true,
                            "margin": [0, 0, 0, 10]
                        }
                    });

                    // Agregar fecha de generación
                    doc.content.splice(2, 0, {
                        "text": 'Generado el: ' + new Date().toLocaleString('es-ES'),
                        "style": {
                            "fontSize": 9,
                            "alignment": 'right',
                            "margin": [0, 0, 0, 10]
                        }
                    });

                    // Formatear columnas numéricas
                    doc.content[3].table.body.forEach(function (row) {
                        // Formatear total de compra
                        if (row[4]) {
                            row[4].text = row[4].text.replace(/\./g, ',');
                        }
                    });

                    // Pie de página
                    doc.footer = function (currentPage, pageCount) {
                        return {
                            "columns": [{
                                "text": 'Sistema de Gestión',
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
                "title": 'Registro de Compras',
                "messageTop": 'Historial de compras del sistema',
                "messageBottom": 'Documento generado el ' + new Date().toLocaleDateString('es-ES'),
                "exportOptions": {
                    "columns": [0, 1, 2, 3, 4, 5],
                    "format": {
                        "body": function (data, row, column, node) {
                            // Formatear números para Excel
                            if (column === 4) {
                                return data.replace(/\./g, '').replace(',', '.');
                            }
                            // Formatear estado
                            if (column === 5) {
                                const estado = $(node).find('span').text();
                                return estado === 'Activa' ? '1' : '0';
                            }
                            return data;
                        }
                    }
                }
            }, {
                "extend": 'csv',
                "text": 'CSV',
                "exportOptions": {
                    "columns": [0, 1, 2, 3, 4, 5],
                    "format": {
                        "body": function (data, row, column, node) {
                            // Formatear estado para CSV
                            if (column === 5) {
                                const estado = $(node).find('span').text();
                                return estado === 'Activa' ? '1' : '0';
                            }
                            return data;
                        }
                    }
                }
            }, {
                "extend": 'print',
                "text": 'Imprimir',
                "title": 'Registro de Compras',
                "messageTop": 'Reporte generado el ' + new Date().toLocaleDateString('es-ES'),
                "exportOptions": {
                    "columns": [0, 1, 2, 3, 4, 5],
                    "format": {
                        "body": function (data, row, column, node) {
                            // Formatear estado para impresión
                            if (column === 5) {
                                return $(node).find('span').text();
                            }
                            return data;
                        }
                    }
                },
                "customize": function (win) {
                    $(win.document.body).find('table')
                        .addClass('table-striped')
                        .css('font-size', '12px');

                    // Añadir título personalizado
                    $(win.document.body).prepend(
                        '<h3 class="text-center">Registro de Compras</h3>' +
                        '<p class="text-center">Sistema de Gestión</p>' +
                        '<hr>'
                    );
                }
            }]
        },
        {
            "extend": 'colvis',
            "text": 'Columnas visibles'
        }
        ],
        "pageLength": 10,
        "lengthMenu": [
            [5, 10, 25, 50, 100],
            [5, 10, 25, 50, 100]
        ],
        "language": {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron compras",
            "sEmptyTable": "No hay compras registradas",
            "sInfo": "Mostrando compras del _START_ al _END_ de un total de _TOTAL_",
            "sInfoEmpty": "Mostrando compras del 0 al 0 de un total de 0",
            "sInfoFiltered": "(filtrado de un total de _MAX_ compras)",
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
        "columnDefs": [
            {
                "type": "num",
                "targets": 0,
                "render": function (data, type, row) {
                    if (type === 'export') {
                        return data;
                    }
                    return data;
                }
            }, // Columna Nro
            {
                "type": "string",
                "targets": 1,
                "render": function (data, type, row) {
                    if (type === 'export') {
                        return data.replace('COMP-', '');
                    }
                    return data;
                }
            }, // Código
            {
                "type": "date",
                "targets": 2,
                "render": function (data, type, row) {
                    if (type === 'export') {
                        // Convertir a formato ISO para ordenamiento y exportación
                        const parts = data.split('/');
                        return `${parts[2]}-${parts[1]}-${parts[0]}`;
                    }
                    return data;
                }
            }, // Fecha
            { "type": "string", "targets": 3 }, // Usuario
            {
                "type": "num-fmt",
                "targets": 4,
                "render": function (data, type, row) {
                    if (type === 'export') {
                        return data.replace('.', '').replace(',', '.');
                    }
                    return data;
                }
            }, // Total
            {
                "type": "string",
                "targets": 5,
                "render": function (data, type, row) {
                    if (type === 'export') {
                        const estado = $(data).find('span').text();
                        return estado === 'Activa' ? '1' : '0';
                    }
                    return data;
                }
            }, // Estado
            { "orderable": false, "targets": 6 } // Acciones
        ]
    }).buttons().container().appendTo('#tablaCompras_wrapper .col-md-6:eq(0)');
});