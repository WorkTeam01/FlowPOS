/**
 * Actualiza la card "Vista Previa" del formulario de producto (crear/editar)
 * con los datos actuales de sus campos. Compartido entre create-producto.js
 * y update-productos.js porque ambos formularios usan los mismos IDs de campo.
 */
function actualizarVistaPreviaProducto() {
    let nombre = $('#nombre').val().trim();
    $('#preview-vista-nombre').text(nombre !== '' ? nombre : 'Nombre del producto');

    let categoria = $('#idcategoria option:selected').text();
    $('#preview-vista-categoria').text($('#idcategoria').val() !== '' ? categoria : '—');

    let codigo = $('#codigo').val().trim();
    $('#preview-vista-codigo').text(codigo !== '' ? codigo : '—');

    let precioVenta = parseFloat($('#precioventa').val());
    $('#preview-vista-precio').text(!isNaN(precioVenta) ? `${window.APP.currency} ${precioVenta.toFixed(2)}` : '—');

    let stock = $('#stock').val();
    $('#preview-vista-stock').text(stock !== '' ? `${stock} unidades` : '—');

    let estadoActivo = $('#estado').val() === '1';
    $('#preview-vista-estado').html(
        estadoActivo ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-secondary">Inactivo</span>'
    );
}

/**
 * Conecta actualizarVistaPreviaProducto() a los campos relevantes del formulario
 * y ejecuta una actualización inicial.
 */
function initVistaPreviaProducto() {
    $('#nombre, #codigo, #stock').on('input', actualizarVistaPreviaProducto);
    $('#idcategoria, #estado').on('change', actualizarVistaPreviaProducto);
    $('#precioventa').on('input', actualizarVistaPreviaProducto);
    actualizarVistaPreviaProducto();
}
