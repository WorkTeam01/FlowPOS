// Configuración compartida de las tablas de sesiones (índice y detalle de usuario)
function inicializarTablaSesiones({ selector, titulo, subtitulo, archivo, columnas, columnasHtml, columnaEstado }) {
    const nombreApp = window.APP.name;

    // Limpia el HTML de una celda (badges, saltos de línea) para las exportaciones
    const textoPlano = function (data, row, column, node) {
        if (columnasHtml.includes(column)) {
            return $(node).text().replace(/\s+/g, ' ').trim();
        }
        return exportarTextoPlano(data);
    };

    const exportOptions = { columns: columnas };
    const exportOptionsTexto = { columns: columnas, format: { body: textoPlano } };

    return $(selector).DataTable({
        "responsive": true,
        "autoWidth": false,
        buttons: [{
            extend: 'collection',
            text: 'Reportes',
            orientation: 'landscape',
            buttons: [{
                text: 'Copiar',
                extend: 'copy',
                exportOptions: exportOptions
            }, {
                extend: 'pdf',
                title: titulo + ' - ' + nombreApp,
                filename: archivo + '_' + new Date().toISOString().slice(0, 10),
                pageSize: 'LETTER',
                exportOptions: exportOptions,
                customize: function (doc) {
                    doc.defaultStyle.fontSize = 10;
                    doc.styles.tableHeader.fontSize = 11;
                    doc.styles.tableHeader.fillColor = '#4b545c';
                    doc.styles.tableHeader.color = '#ffffff';

                    doc.content.splice(0, 1, {
                        text: titulo.toUpperCase() + ' - ' + nombreApp.toUpperCase(),
                        style: { fontSize: 16, alignment: 'center', bold: true, margin: [0, 10, 0, 10] }
                    });

                    doc.content.splice(1, 0, {
                        text: subtitulo,
                        style: { fontSize: 11, alignment: 'center', italic: true, margin: [0, 0, 0, 10] }
                    });

                    doc.content.splice(2, 0, {
                        text: 'Generado el: ' + new Date().toLocaleString('es-BO'),
                        style: { fontSize: 9, alignment: 'right', margin: [0, 0, 0, 10] }
                    });

                    doc.content[3].table.body.forEach(function (row) {
                        if (row[columnaEstado]) {
                            row[columnaEstado].alignment = 'center';
                        }
                    });

                    doc.footer = function (currentPage, pageCount) {
                        return {
                            columns: [
                                { text: 'Sistema de Gestión - ' + nombreApp, alignment: 'left', fontSize: 8 },
                                { text: 'Página ' + currentPage + ' de ' + pageCount, alignment: 'center', fontSize: 8 },
                                { text: 'Confidencial', alignment: 'right', fontSize: 8 }
                            ],
                            margin: [40, 0]
                        };
                    };
                }
            }, {
                extend: 'excel',
                title: titulo + ' - ' + nombreApp,
                messageTop: subtitulo,
                messageBottom: 'Documento generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: exportOptionsTexto
            }, {
                extend: 'csv',
                text: 'CSV',
                exportOptions: exportOptionsTexto
            }, {
                extend: 'print',
                text: 'Imprimir',
                title: titulo + ' - ' + nombreApp,
                messageTop: 'Reporte generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: exportOptionsTexto,
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
        }],
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
    }).buttons().container().appendTo(selector + '_wrapper .col-md-6:eq(0)');
}

// Confirma y envía por POST (con CSRF) un cierre de sesión(es); delegado porque DataTables re-renderiza las filas
function confirmarCierreSesiones({ selectorBoton, titulo, texto, textoConfirmar, actionUrl, campoId, campoNombre }) {
    $(document).on('click', selectorBoton, function () {
        const id = $(this).data('id');
        const nombre = $(this).data(campoNombre);

        Swal.fire({
            titleText: titulo(nombre),
            text: texto,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--swal-danger').trim() || '#dc3545',
            cancelButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--swal-cancel').trim() || '#6c757d',
            confirmButtonText: textoConfirmar,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                submitCsrfForm(baseUrl + actionUrl, { id: id });
            }
        });
    });
}

function cerrarUnaSesion() {
    confirmarCierreSesiones({
        selectorBoton: '.btn-cerrar-sesion',
        titulo: (usuario) => `¿Cerrar sesión de ${usuario}?`,
        texto: 'El usuario será desconectado del sistema.',
        textoConfirmar: 'Sí, cerrar sesión',
        actionUrl: 'controllers/sesiones/cerrar_sesion.php',
        campoNombre: 'usuario'
    });
}
