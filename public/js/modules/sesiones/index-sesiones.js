$(document).ready(function () {
    inicializarTablaSesiones({
        selector: '#tablaSesiones',
        titulo: 'Sesiones del Sistema',
        subtitulo: 'Monitoreo de sesiones de usuarios',
        archivo: 'sesiones_sistema',
        columnas: [0, 1, 2, 3, 4, 5, 6, 7],
        columnasHtml: [1, 3, 5, 6, 7],
        columnaEstado: 6
    });

    cerrarUnaSesion();
});
