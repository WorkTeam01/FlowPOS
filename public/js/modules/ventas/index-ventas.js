$(document).ready(function () {
    // Inicializar DataTable
    $("#tablaVentas").DataTable({
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
                    columns: [0, 1, 2, 3, 4, 5, 6, 7]
                }
            }, {
                extend: 'pdf',
                title: 'Ventas del Sistema - ' + window.APP.name + '',
                filename: 'ventas_sistema_' + new Date().toISOString().slice(0, 10),
                pageSize: 'LETTER',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7]
                },
                customize: function (doc) {
                    // Estilo básico
                    doc.defaultStyle.fontSize = 10;
                    doc.styles.tableHeader.fontSize = 11;
                    doc.styles.tableHeader.fillColor = '#4b545c';
                    doc.styles.tableHeader.color = '#ffffff';

                    // Agregar título con fecha
                    doc.content.splice(0, 1, {
                        text: 'VENTAS DEL SISTEMA - ' + window.APP.name.toUpperCase(),
                        style: {
                            fontSize: 16,
                            alignment: 'center',
                            bold: true,
                            margin: [0, 10, 0, 10]
                        }
                    });

                    // Agregar título
                    let tituloTexto = 'Ventas registradas';

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

                    // Formatear columnas numéricas
                    doc.content[3].table.body.forEach(function (row) {
                        if (row[5]) { // Columna de Total
                            row[5].text = row[5].text.replace((window.APP.currency || 'Bs') + '.', '').trim();
                            row[5].alignment = 'right';
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
                title: 'Ventas del Sistema - ' + window.APP.name + '',
                messageTop: 'Registro de ventas del sistema',
                messageBottom: 'Documento generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7],
                    format: {
                        body: function (data, row, column, node) {
                            if (column === 7) { // Columna de estado
                                return $(node).find('span').text();
                            }
                            if (column === 5) { // Columna de total
                                return data.replace((window.APP.currency || 'Bs') + '.', '').trim();
                            }
                            if (column === 6) { // Columna de método de pago
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
                    columns: [0, 1, 2, 3, 4, 5, 6, 7]
                }
            }, {
                extend: 'print',
                text: 'Imprimir',
                title: 'Ventas del Sistema - ' + window.APP.name + '',
                messageTop: 'Reporte generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7],
                    format: {
                        body: function (data, row, column, node) {
                            if (column === 7 || column === 6) { // Columna de estado y método de pago
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
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ Ventas",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 Ventas",
            "sInfoFiltered": "(filtrado de un total de _MAX_ Ventas)",
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
    }).buttons().container().appendTo('#tablaVentas_wrapper .col-md-6:eq(0)');

    // Inicializar tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Funcionalidad para obtener ticket
    $('.btn-imprimir-ticket').on('click', function () {
        const ventaId = $(this).data('id');

        // Aquí se implementaría la lógica para obtener e imprimir el ticket
        // Mostrar un modal con un formulario, etc.
        console.log(`Imprimir ticket para venta ID: ${ventaId}`);

        // Por ahora, simplemente mostraremos un mensaje
        Swal.fire({
            title: 'Función en desarrollo',
            text: 'La impresión de tickets estará disponible próximamente',
            icon: 'info'
        });
    });

    // Funcionalidad para filtrar por rango de fechas
    $('#btn-filtrar-fechas').on('click', function () {
        const fechaInicio = $('#fecha_inicio').val();
        const fechaFin = $('#fecha_fin').val();

        if (fechaInicio && fechaFin) {
            window.location.href = `?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;
        } else {
            Swal.fire({
                title: 'Fechas incompletas',
                text: 'Por favor seleccione ambas fechas para filtrar',
                icon: 'warning'
            });
        }
    });

    // Funcionalidad para restablecer filtros
    $('#btn-restablecer-filtros').on('click', function () {
        window.location.href = window.location.pathname;
    });
});