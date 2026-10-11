$(document).ready(function () {
    inicializarTablaSesiones({
        selector: '#tablaSesionesUsuario',
        titulo: 'Sesiones de Usuario',
        subtitulo: 'Historial de sesiones del usuario',
        archivo: 'sesiones_usuario',
        columnas: [0, 1, 2, 3, 4, 5, 6],
        columnasHtml: [2, 4, 5, 6],
        columnaEstado: 5
    });

    cerrarUnaSesion();

    confirmarCierreSesiones({
        selectorBoton: '.btn-cerrar-todas',
        titulo: (nombre) => `¿Cerrar todas las sesiones de ${nombre}?`,
        texto: 'El usuario será desconectado de todas sus sesiones activas.',
        textoConfirmar: 'Sí, cerrar todas',
        actionUrl: 'controllers/sesiones/cerrar_sesiones_usuario.php',
        campoNombre: 'nombre'
    });
});
