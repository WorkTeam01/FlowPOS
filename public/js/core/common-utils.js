/**
 * common-utils.js - Utilidades JavaScript básicas para el sistema
 * 
 * Este archivo contiene funciones simples para inicializar componentes
 * comunes en el sistema de Alojamiento Flores.
 * 
 * @version 1.0
 */

/**
 * Inicializa Select2 en los elementos seleccionados sin modificar sus opciones
 * 
 * @param {string} selector - Selector de los elementos a inicializar (opcional)
 * @param {object} options - Opciones adicionales para Select2 (opcional)
 */
function initializeSelect2(selector = '.select2', options = {}) {
    // Opciones predeterminadas para Select2
    const defaultOptions = {
        theme: 'bootstrap4',
        width: '100%',
        allowClear: false,
        minimumResultsForSearch: 7,
        closeOnSelect: true,
        dropdownAutoWidth: true,
        language: {
            noResults: function () {
                return "No se encontraron resultados";
            },
            searching: function () {
                return "Buscando...";
            },
            inputTooShort: function (args) {
                var remainingChars = args.minimum - args.input.length;
                return "Por favor ingrese " + remainingChars + " caracteres más";
            },
            loadingMore: function () {
                return "Cargando más resultados...";
            },
            removeAllItems: function () {
                return "Eliminar todos";
            }
        }
    };

    // Combinar opciones predeterminadas con las opciones personalizadas
    const mergedOptions = $.extend(true, {}, defaultOptions, options);

    // Inicializar Select2 en los elementos seleccionados
    $(selector).each(function () {
        // Si ya tiene Select2, destruirlo primero
        if ($(this).data('select2')) {
            $(this).select2('destroy');
        }

        // Aplicar Select2 sin modificar las opciones existentes
        $(this).select2(mergedOptions);
    });
}

/**
 * Actualiza un elemento Select2 después de cambiar sus opciones
 * 
 * @param {string} selector - Selector del elemento select
 * @param {string|number} valueToSelect - Valor a seleccionar (opcional)
 * @param {object} options - Opciones adicionales para Select2 (opcional)
 */
function refreshSelect2(selector, valueToSelect = null, options = {}) {
    // Destruir la instancia existente de Select2
    if ($(selector).data('select2')) {
        $(selector).select2('destroy');
    }

    // Reinicializar Select2
    initializeSelect2(selector, options);

    // Seleccionar el valor si se proporciona
    if (valueToSelect !== null) {
        $(selector).val(valueToSelect).trigger('change');
    }
}

/**
 * Inicializa tooltips con soporte básico para dispositivos móviles
 */
function initializeTooltips() {
    // Detectar si es un dispositivo táctil
    const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

    // Configuración de tooltips según el tipo de dispositivo
    $('[data-toggle="tooltip"]').tooltip({
        trigger: isTouchDevice ? 'click' : 'hover',
        placement: 'auto',
        delay: isTouchDevice ? { show: 0, hide: 2000 } : { show: 50, hide: 100 }
    });

    if (isTouchDevice) {
        // En dispositivos táctiles, agregar clase para estilizar tooltips
        $('.tooltip').addClass('tooltip-touch');

        // Para enlaces con tooltips, mostrar un botón de info adicional
        $('a[data-toggle="tooltip"]').each(function () {
            const $link = $(this);

            // Verificar si ya tiene un botón de información
            if ($link.find('.info-btn').length === 0) {
                // Agregar un ícono de información junto al enlace
                $link.append('<span class="info-btn ml-1"><i class="fas fa-info-circle text-info"></i></span>');

                // Mostrar tooltip al hacer clic en el ícono de información
                $link.find('.info-btn').on('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $link.tooltip('show');
                });
            }
        });

        // Cerrar tooltips al tocar en otra parte
        $(document).on('touchstart', function (e) {
            if (!$(e.target).closest('[data-toggle="tooltip"], .tooltip').length) {
                $('[data-toggle="tooltip"]').tooltip('hide');
            }
        });
    }
}

// Si ya existe la función formatDate, actualízala para manejar más formatos
function formatDate(date) {
    if (!date) return '';

    // Si ya está en formato legible (contiene /), devolverlo como está
    if (typeof date === 'string' && date.includes('/')) {
        return date;
    }

    try {
        const d = new Date(date);

        // Verificar si es una fecha válida
        if (isNaN(d.getTime())) {
            // Intentar convertir formatos específicos
            if (typeof date === 'string' && date.includes(' ')) {
                // Formato MySQL datetime (YYYY-MM-DD HH:MM:SS)
                const mysqlDate = new Date(date.replace(' ', 'T'));
                if (!isNaN(mysqlDate.getTime())) {
                    return `${String(mysqlDate.getDate()).padStart(2, '0')}/${String(mysqlDate.getMonth() + 1).padStart(2, '0')}/${mysqlDate.getFullYear()}`;
                }
            }
            return date; // Si no podemos convertirla, devolver original
        }

        return `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()}`;
    } catch (e) {
        console.error('Error al formatear fecha:', e);
        return date; // Devolver el valor original si hay error
    }
}

// Si ya existe la función formatTime, actualízala para ser más robusta
function formatTime(time) {
    if (!time) return '';

    // Si es solo hora (HH:MM)
    if (time.length <= 5) return time;

    try {
        // Si es datetime, extraer solo la parte de la hora
        let date;
        if (time.includes('T')) {
            date = new Date(time);
        } else if (time.includes(' ')) {
            // Formato MySQL (YYYY-MM-DD HH:MM:SS)
            date = new Date(time.replace(' ', 'T'));
        } else {
            date = new Date(time);
        }

        if (isNaN(date.getTime())) {
            return time.substring(0, 5); // Intentar devolver los primeros 5 caracteres
        }

        return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
    } catch (e) {
        console.error('Error al formatear hora:', e);
        return time.substring(0, 5); // Intentar devolver los primeros 5 caracteres
    }
}

// Añadir esta nueva función para formatear fecha y hora juntas
function formatDateTime(datetime) {
    if (!datetime) return '';

    // Si ya está en formato legible (contiene /), verificar si también tiene hora
    if (typeof datetime === 'string' && datetime.includes('/')) {
        // Si ya tiene el formato deseado, devolverlo como está
        if (datetime.match(/\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}/)) {
            return datetime;
        }
    }

    try {
        let date;

        // Manejar diferentes formatos de entrada
        if (typeof datetime === 'string' && datetime.includes(' ')) {
            // Formato MySQL datetime (YYYY-MM-DD HH:MM:SS)
            date = new Date(datetime.replace(' ', 'T'));
        } else {
            date = new Date(datetime);
        }

        // Verificar si es una fecha válida
        if (isNaN(date.getTime())) {
            return datetime; // Si no podemos convertirla, devolver original
        }

        const dia = date.getDate().toString().padStart(2, '0');
        const mes = (date.getMonth() + 1).toString().padStart(2, '0');
        const anio = date.getFullYear();
        const hora = date.getHours().toString().padStart(2, '0');
        const minutos = date.getMinutes().toString().padStart(2, '0');

        return `${dia}/${mes}/${anio} ${hora}:${minutos}`;
    } catch (e) {
        console.error('Error al formatear fecha y hora:', e);
        return datetime; // Devolver el valor original si hay error
    }
}

/**
 * Formatea un número como moneda (Bs)
 * 
 * @param {number} amount - Monto a formatear
 * @param {number} decimals - Número de decimales (opcional)
 * @returns {string} Monto formateado
 */
function formatCurrency(amount, decimals = 2) {
    const currency = (window.APP && window.APP.currency) ? window.APP.currency : 'Bs';
    return currency + ' ' + parseFloat(amount).toFixed(decimals).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

/**
 * Muestra una notificación toast usando SweetAlert2
 * 
 * @param {string} message - Mensaje a mostrar
 * @param {string} icon - Icono (success, error, warning, info)
 * @param {number} timer - Tiempo en milisegundos (opcional)
 */
function showToast(message, icon = 'success', timer = 3000) {
    if (typeof Swal !== 'undefined') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: timer,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        Toast.fire({
            icon: icon,
            title: message
        });
    } else {
        // Fallback si Swal no está disponible
        alert(message);
    }
}

/**
 * Realiza una solicitud AJAX simplificada
 * 
 * @param {string} url - URL de la solicitud
 * @param {string} method - Método HTTP (GET, POST)
 * @param {object} data - Datos a enviar
 * @param {function} successCallback - Función de éxito
 * @param {function} errorCallback - Función de error (opcional)
 */
function ajaxRequest(url, method = 'GET', data = {}, successCallback, errorCallback = null) {
    $.ajax({
        url: url,
        type: method,
        data: data,
        dataType: 'json',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function (response) {
            if (successCallback && typeof successCallback === 'function') {
                successCallback(response);
            }
        },
        error: function (xhr, status, error) {
            console.error('Error AJAX:', error);

            if (errorCallback && typeof errorCallback === 'function') {
                errorCallback(xhr, status, error);
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error en la comunicación con el servidor'
                });
            }
        }
    });
}