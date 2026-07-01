/**
 * Script para la gestión de empresas
 * 
 * Este archivo contiene funciones para manejar operaciones AJAX
 * relacionadas con las empresas (crear, editar, cambiar estado)
 */

$(document).ready(function () {
    // Inicializar tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Inicializar DataTable
    const tabla = $("#tablaEmpresas").DataTable({
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
                    columns: [0, 1, 2, 3, 4]
                }
            }, {
                extend: 'pdf',
                title: 'Empresas',
                filename: 'empresas_' + new Date().toISOString().slice(0, 10),
                pageSize: 'LETTER',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4]
                },
                customize: function (doc) {
                    // Estilo básico
                    doc.defaultStyle.fontSize = 10;
                    doc.styles.tableHeader.fontSize = 11;
                    doc.styles.tableHeader.fillColor = '#4b545c';
                    doc.styles.tableHeader.color = '#ffffff';

                    // Agregar título con fecha
                    doc.content.splice(0, 1, {
                        text: 'EMPRESAS',
                        style: {
                            fontSize: 16,
                            alignment: 'center',
                            bold: true,
                            margin: [0, 10, 0, 10]
                        }
                    });

                    // Agregar subtítulo
                    let tituloTexto = 'Empresas registradas';

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
                title: 'Empresas',
                messageTop: 'Registro de empresas del sistema',
                messageBottom: 'Documento generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: {
                    columns: [0, 1, 2, 3, 4],
                    format: {
                        body: function (data, row, column, node) {
                            if (column === 3 || column === 4) {
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
                    columns: [0, 1, 2, 3, 4]
                }
            }, {
                extend: 'print',
                text: 'Imprimir',
                title: 'Empresas',
                messageTop: 'Reporte generado el ' + new Date().toLocaleDateString('es-BO'),
                exportOptions: {
                    columns: [0, 1, 2, 3, 4]
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
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ empresas",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 empresas",
            "sInfoFiltered": "(filtrado de un total de _MAX_ empresas)",
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
    tabla.buttons().container().appendTo('#tablaEmpresas_wrapper .col-md-6:eq(0)');

    // Botón para crear nueva empresa
    $('#btnNuevaEmpresa').on('click', function () {
        // Limpiar formulario
        $('#formEmpresa')[0].reset();
        $('#previewImagen').empty();

        // Configurar para creación
        $('#empresaAction').val('create');
        $('#idEmpresa').val('');
        $('#nombre').val('');
        $('#nit').val('');
        $('#direccion').val('');
        $('#telefono').val('');
        $('#email').val('');
        $('#imagen').val('');
        $('#estado').val('1');

        // Cambiar apariencia del modal
        $('#modalEmpresaHeader').removeClass('bg-warning').addClass('bg-primary');
        $('#modalEmpresaLabel').text('Crear Nueva Empresa');
        $('#btnGuardarEmpresa').removeClass('btn-warning').addClass('btn-primary');
        $('#btnGuardarEmpresa').html('<i class="fas fa-save"></i> Guardar');

        // Mostrar modal
        $('#modalEmpresa').modal('show');
    });

    // Botón para editar empresa
    $(document).on('click', '.btn-editar', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        const nit = $(this).data('nit');
        const direccion = $(this).data('direccion');
        const telefono = $(this).data('telefono');
        const email = $(this).data('email');
        const imagen = $(this).data('imagen');
        const estado = $(this).data('estado');

        // Configurar para edición
        $('#empresaAction').val('edit');
        $('#idEmpresa').val(id);
        $('#nombre').val(nombre);
        $('#nit').val(nit);
        $('#direccion').val(direccion);
        $('#telefono').val(telefono);
        $('#email').val(email);
        $('#estado').val(estado);

        // Mostrar imagen actual si existe
        if (imagen) {
            $('#previewImagen').html(`
                <img src="${baseUrl}public/img/empresas/${imagen}" 
                     alt="Logo actual" 
                     class="img-thumbnail" 
                     style="max-height: 100px;">
                <input type="hidden" name="imagen_actual" value="${imagen}">
            `);
        } else {
            $('#previewImagen').html('<p class="text-muted">No hay logo cargado</p>');
        }

        // Cambiar apariencia del modal
        $('#modalEmpresaHeader').removeClass('bg-primary').addClass('bg-warning');
        $('#modalEmpresaLabel').text('Editar Empresa');
        $('#btnGuardarEmpresa').removeClass('btn-primary').addClass('btn-warning');
        $('#btnGuardarEmpresa').html('<i class="fas fa-save"></i> Actualizar');

        // Mostrar modal
        $('#modalEmpresa').modal('show');
    });

    // Preview de imagen antes de subir
    $('#imagen').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#previewImagen').html(`
                    <img src="${e.target.result}" 
                         alt="Vista previa" 
                         class="img-thumbnail" 
                         style="max-height: 100px;">
                `);
            }
            reader.readAsDataURL(file);
        }
    });

    // Procesar formulario
    $('#formEmpresa').on('submit', function (e) {
        e.preventDefault();

        const action = $('#empresaAction').val();
        
        // Crear FormData para manejar la subida de archivos
        const formData = new FormData(this);
        let url, loadingMsg, successBtn;

        if (action === 'create') {
            url = `${baseUrl}controllers/empresa/crear_empresa_ajax.php`;
            loadingMsg = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            successBtn = '<i class="fas fa-save"></i> Guardar';
        } else {
            url = `${baseUrl}controllers/empresa/actualizar_empresa_ajax.php`;
            loadingMsg = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
            successBtn = '<i class="fas fa-save"></i> Actualizar';
        }

        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function () {
                $('#btnGuardarEmpresa').prop('disabled', true).html(loadingMsg);
            },
            success: function (response) {
                if (response.success) {
                    // Cerrar modal y mostrar mensaje de éxito
                    $('#modalEmpresa').modal('hide');

                    // Recargar la página para mostrar el mensaje con mensajes.php
                    location.reload();
                } else {
                    // Mostrar mensaje de error
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                    $('#btnGuardarEmpresa').prop('disabled', false).html(successBtn);
                }
            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error en la comunicación con el servidor'
                });
                $('#btnGuardarEmpresa').prop('disabled', false).html(successBtn);
            }
        });
    });

    // Manejar cambio de estado
    $(document).on('click', '.cambiar-estado', function () {
        const id = $(this).data('id');
        const estadoActual = $(this).data('estado-actual');
        const sucursales = $(this).data('sucursales');

        // Validar si hay sucursales antes de desactivar
        if (estadoActual == 1 && sucursales > 0) {
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
            title: `¿${textoEstadoCapitalizado} esta empresa?`,
            text: `La empresa será ${textoEstado}da.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: estadoActual == 1 ? '#d33' : '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: `Sí, ${textoEstado}`,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `${baseUrl}controllers/empresa/desactivar_empresa_ajax.php`,
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
    $('#modalEmpresa').on('hidden.bs.modal', function () {
        $('#formEmpresa')[0].reset();
        $('#previewImagen').empty();

        // Asegurar que no queden validaciones o estados de error
        $('.is-invalid').removeClass('is-invalid');

        // Resetear propiedades importantes para dispositivos móviles
        $('#formEmpresa').css({
            'transform': 'none',
            'height': 'auto'
        });

        // Desactivar cualquier tooltip activo
        $('[data-toggle="tooltip"]').tooltip('hide');
    });

    // Mejorar el manejo del modal en dispositivos móviles
    $('#modalEmpresa').on('shown.bs.modal', function () {
        // Asegurar que el scroll funcione correctamente en móviles
        $('.modal-body').css('overflow-y', 'auto');

        // Enfocar el primer campo del formulario
        $('#nombre').trigger('focus');
    });

    // Prevenir que el modal se cierre al hacer clic fuera (para evitar pérdida de datos)
    $('#modalEmpresa').data('backdrop', 'static');
    $('#modalEmpresa').data('keyboard', false);

    // Mostrar nombre del archivo seleccionado en el input file
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);
    });
});