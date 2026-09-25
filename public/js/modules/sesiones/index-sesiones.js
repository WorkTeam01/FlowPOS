$(document).ready(function () {
    // Limpia el HTML de una celda (badges, saltos de línea) para las exportaciones
    const textoPlano = function (data, row, column, node) {
        if (column === 1 || column === 3 || column === 5 || column === 6 || column === 7) {
            return $(node).text().replace(/\s+/g, ' ').trim();
        }
        return data;
    };

    // Inicializar DataTable
    $("#tablaSesiones").DataTable({
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
                title: 'Sesiones del Sistema - ' + window.APP.name + '',
                filename: 'sesiones_sistema_' + new Date().toISOString().slice(0, 10),
                pageSize: 'LETTER',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7]
                },
                customize: function (doc) {
                    doc.defaultStyle.fontSize = 10;
                    doc.styles.tableHeader.fontSize = 11;
                    doc.styles.tableHeader.fillColor = '#4b545c';
                    doc.styles.tableHeader.color = '#ffffff';

                    doc.content.splice(0, 1, {
                        text: 'SESIONES DEL SISTEMA - ' + window.APP.name.toUpperCase(),
                        style: {
                            fontSize: 16,
                            alignment: 'center',
                            bold: true,
                            margin: [0, 10, 0, 10]
                        }
                    });

                    doc.content.splice(1, 0, {
                        text: 'Monitoreo de sesiones de usuarios',
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

                    // Centrar la columna de Estado
                    doc.content[3].table.body.forEach(function (row) {
                        if (row[6]) {
                            row[6].alignment = 'center';
                        }
                    });

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
                    columns: [0, 1, 2, 3, 4, 5, 6, 7],
                    format: {
                        body: textoPlano
                    }
                }
            }, {
                extend: 'csv',
                text: 'CSV',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7],
                    format: {
                        body: textoPlano
                    }
                }
            }, {
                extend: 'print',
                text: 'Imprimir',
                title: 'Sesiones del Sistema - ' + window.APP.name + '',
                messageTop: 'Reporte generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7],
                    format: {
                        body: textoPlano
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
        },
        "drawCallback": function () {
            initializeTooltips();
        }
    }).buttons().container().appendTo('#tablaSesiones_wrapper .col-md-6:eq(0)');

    initializeTooltips();

    // Cerrar una sesión activa (la fila se re-renderiza al paginar, por eso delegado)
    $(document).on('click', '.btn-cerrar-sesion', function () {
        const sesionId = $(this).data('id');
        const usuario = $(this).data('usuario');

        Swal.fire({
            title: `¿Cerrar sesión de ${usuario}?`,
            text: 'El usuario será desconectado del sistema.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--swal-danger').trim() || '#dc3545',
            cancelButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--swal-cancel').trim() || '#6c757d',
            confirmButtonText: 'Sí, cerrar sesión',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                submitCsrfForm(baseUrl + 'controllers/sesiones/cerrar_sesion.php', {
                    id: sesionId
                });
            }
        });
    });
});
