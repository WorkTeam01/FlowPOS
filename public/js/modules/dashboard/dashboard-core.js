/**
 * Dashboard Core
 * Shared logic for all role-based dashboards
 * Eliminates ~1200 lines of duplication across dashboard.js, dashboard_supervisor.js, dashboard_vendedor.js
 */

(function () {
    'use strict';

    // Ensure window.APP exists with defaults
    window.APP = window.APP || { currency: 'Bs', name: 'FlowPOS' };

    /**
     * Modern Chart.js defaults (Chart.js 4+ API)
     * Replaces deprecated Chart.defaults.global
     */
    function configureChartDefaults() {
        if (typeof Chart === 'undefined') return;

        Chart.defaults.font = {
            family: 'Source Sans Pro, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue"',
            size: 12
        };
        Chart.defaults.color = '#666';
        Chart.defaults.responsive = true;
        Chart.defaults.maintainAspectRatio = false;
        Chart.defaults.plugins = {
            legend: {
                labels: {
                    padding: 20,
                    boxWidth: 12
                }
            },
            tooltip: {
                padding: 10
            }
        };
        Chart.defaults.animation = {
            duration: 500
        };
    }

    /**
     * Color palette from CSS custom properties with fallbacks
     * Allows theming via CSS
     */
    function getChartColors() {
        const rootStyles = getComputedStyle(document.documentElement);
        const getVar = (name, fallback) => rootStyles.getPropertyValue(name).trim() || fallback;

        return {
            primary: getVar('--chart-color-primary', '#007bff'),
            success: getVar('--chart-color-success', '#28a745'),
            warning: getVar('--chart-color-warning', '#ffc107'),
            danger: getVar('--chart-color-danger', '#dc3545'),
            info: getVar('--chart-color-info', '#17a2b8'),
            secondary: getVar('--chart-color-secondary', '#6c757d'),
            orange: getVar('--chart-color-orange', '#fd7e14'),
            purple: getVar('--chart-color-purple', '#6f42c1'),
            pink: getVar('--chart-color-pink', '#e83e8c'),

            // Color-blind safe categorical palette (Paul Tol bright qualitative)
            // Works for protanopia, deuteranopia, tritanopia
            categorical: [
                '#4477AA', // blue
                '#EE6677', // red
                '#228833', // green
                '#CCBB44', // yellow
                '#66CCEE', // cyan
                '#AA3377', // purple
                '#BBBBBB', // gray
            ].map((c, i) => getVar(`--chart-color-cat-${i}`, c)),

            // Semantic colors for payment methods (color-blind safe)
            paymentMethods: {
                'Efectivo': getVar('--chart-color-success', '#228833'),
                'Tarjeta': getVar('--chart-color-primary', '#4477AA'),
                'QR': getVar('--chart-color-warning', '#CCBB44'),
                'Transferencia': getVar('--chart-color-info', '#66CCEE')
            }
        };
    }

    /**
     * Generate CanvasPattern for color-blind accessibility
     * Adds pattern fills so charts are readable without color
     * @param {CanvasRenderingContext2D} ctx - Canvas context
     * @param {string} type - Pattern type: 'diagonal', 'dots', 'cross', 'grid', 'horizontal', 'vertical'
     * @param {string} color - Pattern color
     * @param {number} scale - Pattern scale factor
     * @returns {CanvasPattern}
     */
    function createChartPattern(ctx, type, color, scale = 1) {
        const size = 8 * scale;
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;
        const pctx = canvas.getContext('2d');
        pctx.strokeStyle = color;
        pctx.lineWidth = Math.max(1, 1.5 * scale);
        pctx.beginPath();

        switch (type) {
            case 'diagonal':
                pctx.moveTo(0, size);
                pctx.lineTo(size, 0);
                break;
            case 'cross':
                pctx.moveTo(0, 0);
                pctx.lineTo(size, size);
                pctx.moveTo(size, 0);
                pctx.lineTo(0, size);
                break;
            case 'dots':
                pctx.arc(size / 2, size / 2, Math.max(1, 1.5 * scale), 0, Math.PI * 2);
                pctx.fillStyle = color;
                pctx.fill();
                return pctx.createPattern(canvas, 'repeat');
            case 'grid':
                pctx.moveTo(0, 0);
                pctx.lineTo(size, 0);
                pctx.moveTo(0, 0);
                pctx.lineTo(0, size);
                break;
            case 'horizontal':
                pctx.moveTo(0, size / 2);
                pctx.lineTo(size, size / 2);
                break;
            case 'vertical':
                pctx.moveTo(size / 2, 0);
                pctx.lineTo(size / 2, size);
                break;
        }
        pctx.stroke();
        return pctx.createPattern(canvas, 'repeat');
    }

    /**
     * Apply pattern fills to chart dataset for color-blind accessibility
     * @param {Chart} chart - Chart.js instance
     * @param {number} datasetIndex - Dataset index to pattern
     * @param {string} patternType - Pattern type
     */
    function applyPatternFill(chart, datasetIndex, patternType = 'diagonal') {
        const meta = chart.getDatasetMeta(datasetIndex);
        if (!meta || !meta.data.length) return;

        const dataset = chart.data.datasets[datasetIndex];
        const color = dataset.backgroundColor || dataset.borderColor || '#4477AA';

        dataset.backgroundColor = function (context) {
            const ctx = context.chart.ctx;
            return createChartPattern(ctx, patternType, color);
        };
    }

    /**
     * Currency formatting using APP config or fallback
     */
    function formatoMoneda(valor) {
        const currency = (window.APP && window.APP.currency) ? window.APP.currency : 'Bs';
        const num = parseFloat(valor);
        if (isNaN(num)) return currency + ' 0.00';
        return currency + ' ' + num.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    /**
     * Percentage formatting
     */
    function formatoPorcentaje(valor) {
        const num = parseFloat(valor);
        if (isNaN(num)) return '0.0%';
        return num.toFixed(1) + '%';
    }

    /**
     * Date formatting for display (DD/MM/YYYY)
     */
    function formatearFecha(fechaStr) {
        try {
            const fecha = new Date(fechaStr + 'T00:00:00');
            if (isNaN(fecha.getTime())) return fechaStr;
            const dia = fecha.getDate().toString().padStart(2, '0');
            const mes = (fecha.getMonth() + 1).toString().padStart(2, '0');
            const anio = fecha.getFullYear();
            return `${dia}/${mes}/${anio}`;
        } catch (e) {
            return fechaStr;
        }
    }

    /**
     * Date/time formatting for display (DD/MM/YYYY HH:MM)
     */
    function formatearFechaHora(fechaStr) {
        if (!fechaStr) return '';
        try {
            let fecha;
            if (fechaStr.includes(' ')) {
                fecha = new Date(fechaStr.replace(' ', 'T'));
            } else {
                fecha = new Date(fechaStr);
            }
            if (isNaN(fecha.getTime())) return fechaStr;
            const dia = fecha.getDate().toString().padStart(2, '0');
            const mes = (fecha.getMonth() + 1).toString().padStart(2, '0');
            const anio = fecha.getFullYear();
            const hora = fecha.getHours().toString().padStart(2, '0');
            const minutos = fecha.getMinutes().toString().padStart(2, '0');
            return `${dia}/${mes}/${anio} ${hora}:${minutos}`;
        } catch (e) {
            return fechaStr;
        }
    }

    /**
     * Cache management with 5-minute TTL
     */
    const dashboardCache = new Map();

    function generarClaveCache(periodo, fechaInicio = null, fechaFin = null, limite = 5) {
        if (periodo === 'personalizado' && fechaInicio && fechaFin) {
            return `${periodo}_${fechaInicio}_${fechaFin}_${limite}`;
        }
        return `${periodo}_${limite}`;
    }

    function getCachedData(clave) {
        const entry = dashboardCache.get(clave);
        if (!entry) return null;
        const ahora = Date.now();
        if (ahora - entry.timestamp > 5 * 60 * 1000) {
            dashboardCache.delete(clave);
            return null;
        }
        return entry.data;
    }

    function setCachedData(clave, data) {
        dashboardCache.set(clave, { data, timestamp: Date.now() });
    }

    /**
     * Base URL resolution
     */
    function getBaseUrl() {
        if (typeof baseUrl !== 'undefined' && baseUrl) {
            return baseUrl.replace(/\/$/, '');
        }
        return '';
    }

    /**
     * Loading state management
     * Uses Map to store original content (WeakMap doesn't support forEach)
     */
    const originalContentMap = new Map();

    function mostrarCargando(mostrar = true, kpiSelectors = [], tableIds = []) {
        const t = window.DashboardCore.t;
        if (mostrar) {
            // KPIs
            kpiSelectors.forEach(selector => {
                document.querySelectorAll(selector).forEach(el => {
                    if (!originalContentMap.has(el) && !el.querySelector('.fa-spinner')) {
                        originalContentMap.set(el, el.innerHTML);
                        el.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i>';
                    }
                });
            });

            // Tables
            tableIds.forEach(id => {
                const tabla = document.getElementById(id);
                if (tabla && !tabla.querySelector('.fa-spinner')) {
                    if (!originalContentMap.has(tabla)) {
                        originalContentMap.set(tabla, tabla.innerHTML);
                    }
                    const cols = tabla.querySelector('thead tr')?.children.length || 5;
                    tabla.innerHTML = `
                        <tr>
                            <td colspan="${cols}" class="text-center py-3">
                                <i class="fas fa-spinner fa-spin mr-2" aria-hidden="true"></i> ${t('cargando')}
                            </td>
                        </tr>
                    `;
                }
            });
        } else {
            // Restore original content
            originalContentMap.forEach((original, el) => {
                if (el.isConnected) {
                    el.innerHTML = original;
                }
            });
            originalContentMap.clear();
        }
    }

    /**
     * Error display with SweetAlert2 fallback
     */
    function mostrarError(mensaje) {
        const t = window.DashboardCore.t;
        try {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Error',
                    text: mensaje,
                    icon: 'error',
                    confirmButtonText: t('ticket.aceptar') || 'Aceptar'
                });
            } else {
                alert(mensaje);
            }
        } catch (e) {
            alert(mensaje);
        }
    }

    /**
     * Generic AJAX data loader
     * @param {string} endpoint - Controller endpoint (e.g., 'get_dashboard_data.php')
     * @param {Object} params - Query parameters
     * @returns {Promise<Object>} Parsed JSON response
     */
    async function cargarDatosDashboard(endpoint, params) {
        const baseUrl = getBaseUrl();
        let url = baseUrl + '/controllers/dashboard/' + endpoint;

        if (!url.startsWith('http')) {
            url = 'controllers/dashboard/' + endpoint;
        }

        const searchParams = new URLSearchParams(params);
        const response = await fetch(`${url}?${searchParams.toString()}`);

        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
        }

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Respuesta no es JSON:', text);
            throw new Error('Respuesta del servidor no es JSON válido');
        }

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Error al cargar datos del dashboard');
        }
        return data;
    }

    /**
     * Period selector initialization
     * Handles select2, custom date range toggle, and date defaults
     */
    function inicializarSelectorPeriodo(onPeriodChange) {
        try {
            if (typeof $ !== 'undefined' && $.fn.select2 && typeof initializeSelect2 === 'function') {
                initializeSelect2('.select2', { minimumResultsForSearch: Infinity, dropdownAutoWidth: false });
            }
        } catch (e) {
            console.warn('Error al inicializar Select2:', e);
        }

        const selectorPeriodo = document.getElementById('selector-periodo');
        if (selectorPeriodo) {
            $(selectorPeriodo).on('change', function () {
                const periodo = this.value;
                const fechasPersonalizadas = document.getElementById('fechas-personalizadas');

                if (periodo === 'personalizado') {
                    if (fechasPersonalizadas) fechasPersonalizadas.classList.remove('d-none');
                    return;
                }

                if (fechasPersonalizadas) fechasPersonalizadas.classList.add('d-none');
                onPeriodChange(periodo);
            });
        }

        const btnAplicarFechas = document.getElementById('btn-aplicar-fechas');
        if (btnAplicarFechas) {
            btnAplicarFechas.addEventListener('click', function () {
                const fechaDesdeInput = document.getElementById('fecha-desde');
                const fechaHastaInput = document.getElementById('fecha-hasta');

                if (fechaDesdeInput && fechaHastaInput && fechaDesdeInput.value && fechaHastaInput.value) {
                    onPeriodChange('personalizado', fechaDesdeInput.value, fechaHastaInput.value);
                } else {
                    mostrarError('Debe seleccionar ambas fechas para aplicar el filtro personalizado');
                }
            });
        }

        // Set default dates for custom range (last 30 days)
        const hoy = new Date();
        const hace30Dias = new Date(hoy);
        hace30Dias.setDate(hoy.getDate() - 30);

        const fechaHastaInput = document.getElementById('fecha-hasta');
        const fechaDesdeInput = document.getElementById('fecha-desde');

        if (fechaHastaInput) fechaHastaInput.valueAsDate = hoy;
        if (fechaDesdeInput) fechaDesdeInput.valueAsDate = hace30Dias;

        return {
            fechaDesde: fechaDesdeInput?.value,
            fechaHasta: fechaHastaInput?.value
        };
    }

    /**
     * Creates accessible data table for chart
     * Screen readers can access the data even if canvas is not readable
     * @param {string} chartId - Canvas element ID
     * @param {Array} data - Chart data array
     * @param {Function} rowMapper - Function to map data item to table row HTML
     * @param {string} caption - Table caption for accessibility
     */
    function crearTablaAccesibleGrafico(chartId, data, rowMapper, caption) {
        const canvas = document.getElementById(chartId);
        if (!canvas) return;

        // Remove existing accessible table
        const existingTable = document.getElementById(`${chartId}-accessible`);
        if (existingTable) existingTable.remove();

        if (!data || data.length === 0) return;

        const table = document.createElement('table');
        table.id = `${chartId}-accessible`;
        table.className = 'sr-only'; // Visually hidden but accessible
        table.setAttribute('aria-hidden', 'false');

        const captionEl = document.createElement('caption');
        captionEl.textContent = caption;
        table.appendChild(captionEl);

        const tbody = document.createElement('tbody');
        data.forEach((item, index) => {
            const rowHtml = rowMapper(item, index);
            const tr = document.createElement('tr');
            tr.innerHTML = rowHtml;
            tbody.appendChild(tr);
        });
        table.appendChild(tbody);

        // Insert after canvas
        canvas.parentNode.insertBefore(table, canvas.nextSibling);

        // Link canvas to table for screen readers
        canvas.setAttribute('aria-describedby', table.id);
    }

    /**
     * Updates KPI elements with trend indicators (text + color, not color alone)
     * @param {Object} kpis - KPI values
     * @param {Object} tendencias - Trend percentages
     * @param {Object} selectors - Mapping of KPI name to element ID
     */
    function actualizarKPIsConTendencia(kpis, tendencias, selectors) {
        Object.entries(selectors).forEach(([kpiName, elementId]) => {
            const el = document.getElementById(elementId);
            if (!el || kpis[kpiName] === undefined || kpis[kpiName] === null) return;

            const valor = kpis[kpiName];
            const tendencia = tendencias?.[kpiName] ?? 0;
            const esPositivo = tendencia >= 0;
            const icono = esPositivo ? 'fa-arrow-up' : 'fa-arrow-down';
            const claseColor = esPositivo ? 'text-success' : 'text-danger';
            const prefijo = esPositivo ? '+' : '';
            const absTendencia = Math.abs(tendencia);

            // Format value based on KPI type
            let valorFormateado = valor;
            if (typeof valor === 'number' || !isNaN(parseFloat(valor))) {
                // Check if it's a currency KPI by selector name
                if (elementId.includes('venta') || elementId.includes('ganancia') || elementId.includes('comision') || elementId.includes('ticket')) {
                    valorFormateado = formatoMoneda(valor);
                }
            }

            el.innerHTML = `
                ${valorFormateado}
                <small class="${claseColor} ml-1" aria-label="${esPositivo ? 'Aumento' : 'Disminución'} del ${absTendencia}%">
                    <i class="fas ${icono}" aria-hidden="true"></i> ${prefijo}${absTendencia}%
                </small>
            `;
        });
    }

    /**
     * Print ticket generation (shared across dashboards) with i18n support
     */
    function generarTicketImpresion(datos, empresaOpciones = null) {
        const t = window.DashboardCore.t;
        const empresa = empresaOpciones || {
            nombre: t('ticket.empresa'),
            nit: t('ticket.nit'),
            direccion: t('ticket.direccion'),
            telefono: t('ticket.telefono')
        };

        const fechaHora = new Date();
        const fechaFormateada = fechaHora.toLocaleDateString('es-BO');
        const horaFormateada = fechaHora.toLocaleTimeString('es-BO', { hour: '2-digit', minute: '2-digit' });

        let ticket = `
    <div style="font-family: 'Courier New', monospace; width: 300px; margin: 0 auto; font-size: 12px;">
        <div style="text-align: center; padding-bottom: 10px;">
            <div style="font-size: 16px; font-weight: bold;">${empresa.nombre}</div>
            <div>NIT: ${empresa.nit}</div>
            <div>Dirección: ${empresa.direccion || t('ticket.direccion')}</div>
            <div>Teléfono: ${empresa.telefono || t('ticket.telefono')}</div>
        </div>

        <div style="border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 10px 0; margin: 10px 0; text-align: center;">
            <div style="font-size: 14px; font-weight: bold;">${t('ticket.reporteVentas')}</div>
            <div>${t('ticket.fecha')}: ${fechaFormateada} - ${horaFormateada}</div>
        </div>

        <div style="border-bottom: 1px dashed #000; padding-bottom: 10px;">
            <div style="font-weight: bold;">${t('ticket.ventasPeriodo')} ${datos.periodo?.descripcion?.toUpperCase() || t('ticket.ventasPeriodo')}:</div>
            <div>• ${t('ticket.total')}: ${formatoMoneda(datos.kpis?.ventasTotales || datos.kpis?.ventasEquipo || datos.kpis?.ventasVendedor || 0)} (${datos.kpis?.totalTransacciones || datos.kpis?.totalVentas || 0} ${t('ticket.ventasPeriodo').toLowerCase()})</div>
            <div>• ${t('ticket.utilidadEstimada')}: ${formatoMoneda(datos.kpis?.gananciasNetas || datos.kpis?.comisionEstimada || 0)}</div>
        </div>`;

        // Comparative trends if available
        if (datos.tendencias && (datos.tendencias.ventas !== 0 || datos.tendencias.ganancias !== 0)) {
            const signoVentas = datos.tendencias.ventas >= 0 ? '+' : '';
            const diffMonto = (datos.kpis?.ventasTotales || 0) * datos.tendencias.ventas / 100;
            ticket += `
        <div style="border-bottom: 1px dashed #000; padding: 10px 0;">
            <div style="font-weight: bold;">${t('ticket.comparativo')}:</div>
            <div>• ${t('ticket.diferencia')}: ${signoVentas}${formatoMoneda(diffMonto)} (${signoVentas}${datos.tendencias.ventas}%)</div>
        </div>`;
        }

        // Payment methods
        if (datos.metodosPago && datos.metodosPago.length > 0) {
            ticket += `
        <div style="border-bottom: 1px dashed #000; padding: 10px 0;">
            <div style="font-weight: bold;">${t('ticket.metodosPago')}:</div>`;
            datos.metodosPago.forEach(metodo => {
                ticket += `<div>- ${metodo.metodo}: ${formatoMoneda(metodo.monto)} (${formatoPorcentaje(metodo.porcentaje)})</div>`;
            });
            ticket += `</div>`;
        }

        // Top products
        const topProductos = datos.productosMasVendidos || datos.productosVendidos || [];
        if (topProductos.length > 0) {
            ticket += `
        <div style="padding: 10px 0;">
            <div style="font-weight: bold;">${t('ticket.topProductos')}:</div>`;
            topProductos.slice(0, 3).forEach((producto, index) => {
                ticket += `<div>${index + 1}. ${producto.producto} - ${producto.unidades} ${t('ticket.unidades')} (${formatoMoneda(producto.total)})</div>`;
            });
            ticket += `</div>`;
        }

        // Summary
        if (datos.tendencias && datos.tendencias.ventas !== 0) {
            const comparacion = datos.tendencias.ventas >= 0 ? t('ticket.supero') : t('ticket.estuvoPorDebajo');
            const signoVentas = datos.tendencias.ventas >= 0 ? '+' : '';
            const diffMonto = Math.abs((datos.kpis?.ventasTotales || 0) * datos.tendencias.ventas / 100);

            ticket += `
        <div style="border-top: 1px dashed #000; padding-top: 10px; margin-top: 10px; text-align: center;">
            <div style="font-weight: bold;">${t('ticket.resumen')}:</div>
            <div>${datos.periodo?.descripcion || 'Este período'} ${comparacion} ${t('ticket.periodoAnterior')} ${formatoMoneda(diffMonto)} (${signoVentas}${datos.tendencias.ventas}%)</div>
        </div>`;
        }

        ticket += `
        <div style="border-top: 1px dashed #000; padding-top: 10px; margin-top: 10px; text-align: center;">
            ${new Date().getFullYear()} ${t('ticket.copyright')} ${(window.APP && window.APP.name) ? window.APP.name : ''}
        </div>
    </div>`;

        return ticket;
    }

    function imprimirTicket(ticketHTML) {
        const ventanaImpresion = window.open('', '_blank', 'width=600,height=600');
        ventanaImpresion.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
        <title>Ticket de Ventas - ${(window.APP && window.APP.name) ? window.APP.name : ''}</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            @media print {
                body { width: 80mm; margin: 0; padding: 0; }
            }
            body { font-family: 'Courier New', monospace; font-size: 12px; line-height: 1.2; }
        </style>
    </head>
    <body>
        ${ticketHTML}
        <script>
            window.onload = function() {
                window.print();
                setTimeout(function() { window.close(); }, 500);
            };
        </script>
    </body>
    </html>
`);
        ventanaImpresion.document.close();
    }

    /**
     * Initialize print button (shared) with i18n
     */
    function inicializarBotonImprimir(getDatosActuales) {
        const btnImprimir = document.getElementById('btn-imprimir');
        if (!btnImprimir) return;

        btnImprimir.addEventListener('click', function () {
            const datos = getDatosActuales();
            if (!datos) {
                mostrarError(t('ticket.errorImprimir'));
                return;
            }

            try {
                if (typeof Swal !== 'undefined') {
                    const t = window.DashboardCore.t;
                    Swal.fire({
                        title: t('ticket.datosTicket'),
                        html: `
                            <div class="form-group text-left">
                                <label>${t('ticket.nombreEmpresa')}:</label>
                                <input type="text" id="empresa-nombre" class="form-control" value="${t('ticket.empresa')}">
                            </div>
                            <div class="form-group text-left">
                                <label>NIT:</label>
                                <input type="text" id="empresa-nit" class="form-control" value="${t('ticket.nit')}">
                            </div>
                            <div class="form-group text-left">
                                <label>${t('ticket.direccion')}:</label>
                                <input type="text" id="empresa-direccion" class="form-control" value="${t('ticket.direccion')}">
                            </div>
                            <div class="form-group text-left">
                                <label>${t('ticket.telefono')}:</label>
                                <input type="text" id="empresa-telefono" class="form-control" value="${t('ticket.telefono')}">
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: t('ticket.imprimir'),
                        cancelButtonText: t('ticket.cancelar'),
                        preConfirm: () => ({
                            empresa: {
                                nombre: document.getElementById('empresa-nombre').value,
                                nit: document.getElementById('empresa-nit').value,
                                direccion: document.getElementById('empresa-direccion').value,
                                telefono: document.getElementById('empresa-telefono').value
                            }
                        })
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const ticketHTML = generarTicketImpresion(datos, result.value.empresa);
                            imprimirTicket(ticketHTML);
                        }
                    });
                } else {
                    const ticketHTML = generarTicketImpresion(datos);
                    imprimirTicket(ticketHTML);
                }
            } catch (e) {
                console.error('Error al mostrar SweetAlert:', e);
                const ticketHTML = generarTicketImpresion(datos);
                imprimirTicket(ticketHTML);
            }
        });
    }

    /**
     * Creates "no data" placeholder elements for charts
     */
    function crearElementosNoDatos(chartIds, messages) {
        chartIds.forEach((chartId, index) => {
            const placeholderId = `sin-datos-${chartId.replace('grafico-', '')}`;
            if (document.getElementById(placeholderId)) return;

            const canvas = document.getElementById(chartId);
            if (!canvas || !canvas.parentNode) return;

            const placeholder = document.createElement('div');
            placeholder.id = placeholderId;
            placeholder.className = 'text-center text-muted py-4';
            placeholder.style.display = 'none';
            placeholder.innerHTML = `<p><i class="fas fa-info-circle mr-1"></i> ${messages[index] || t('sinDatos')}</p>`;
            canvas.parentNode.appendChild(placeholder);
        });
    }

    function mostrarSinDatos(chartId, mostrar = true) {
        const canvas = document.getElementById(chartId);
        const placeholderId = `sin-datos-${chartId.replace('grafico-', '')}`;
        const placeholder = document.getElementById(placeholderId);

        if (canvas) canvas.style.display = mostrar ? 'none' : 'block';
        if (placeholder) placeholder.style.display = mostrar ? 'block' : 'none';
    }

    /**
     * Lazy-load charts below the fold using IntersectionObserver
     * Charts are only created when they enter the viewport
     * @param {Array<string>} chartIds - Array of canvas element IDs to lazy-load
     * @param {Function} createChartFn - Function that creates the chart (receives chartId)
     * @param {Object} options - IntersectionObserver options (rootMargin, threshold)
     */
    function lazyLoadCharts(chartIds, createChartFn, options = {}) {
        if (!('IntersectionObserver' in window)) {
            // Fallback: create all charts immediately if IntersectionObserver not supported
            chartIds.forEach(id => createChartFn(id));
            return;
        }

        const observerOptions = {
            rootMargin: options.rootMargin || '100px',
            threshold: options.threshold || 0.1
        };

        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const chartId = entry.target.id;
                    createChartFn(chartId);
                    obs.unobserve(entry.target);
                }
            });
        }, observerOptions);

        chartIds.forEach(chartId => {
            const canvas = document.getElementById(chartId);
            if (canvas) {
                observer.observe(canvas);
            }
        });
    }

    /**
     * i18n translation map for print tickets and UI strings
     * Extend this object for additional languages
     */
    const i18n = {
        es: {
            // Print ticket
            ticket: {
                empresa: 'MI EMPRESA',
                nit: '000000000-0',
                direccion: 'Calle Principal #123',
                telefono: '555-1234',
                reporteVentas: 'REPORTE DE VENTAS',
                fecha: 'Fecha',
                ventasPeriodo: 'VENTAS',
                total: 'Total',
                utilidadEstimada: 'Utilidad estimada',
                comparativo: 'COMPARATIVO CON PERIODO ANTERIOR',
                diferencia: 'Diferencia',
                metodosPago: 'MÉTODOS DE PAGO',
                topProductos: 'TOP PRODUCTOS',
                unidades: 'unidades',
                resumen: 'RESUMEN',
                supero: 'superó',
                estuvoPorDebajo: 'estuvo por debajo de',
                periodoAnterior: 'el periodo anterior por',
                copyright: '©',
                // Loading/error states
                cargando: 'Cargando datos...',
                sinDatos: 'No hay datos disponibles para el período seleccionado',
                errorCarga: 'Error al cargar los datos',
                errorConexion: 'Error de conexión',
                errorImprimir: 'No hay datos para imprimir. Cargue primero algunos datos.',
                errorFechas: 'Debe seleccionar ambas fechas para aplicar el filtro personalizado',
                datosTicket: 'Datos para el ticket',
                nombreEmpresa: 'Nombre de empresa',
                imprimir: 'Imprimir',
                cancelar: 'Cancelar',
                aceptar: 'Aceptar'
            },
            // KPI trends
            kpi: {
                aumento: 'Aumento',
                disminucion: 'Disminución'
            },
            // Period selectors
            periodo: {
                hoy: 'Hoy',
                semana: 'Esta semana',
                mes: 'Este mes',
                anio: 'Este año',
                personalizado: 'Personalizado',
                desde: 'Desde',
                hasta: 'Hasta',
                aplicar: 'Aplicar'
            },
            // Chart states
            chart: {
                sinDatosMetodos: 'No hay datos de métodos de pago para el período seleccionado',
                sinDatosCategorias: 'No hay datos de categorías para el período seleccionado',
                sinDatosVendedores: 'No hay datos de rendimiento de vendedores para el período seleccionado',
                sinDatosTendencia: 'No hay datos de tendencia para el período seleccionado',
                sinDatosConversion: 'No hay datos de conversión para el período seleccionado',
                sinDatosRendimiento: 'No hay datos de rendimiento para el período seleccionado'
            },
            // Table empty states
            table: {
                sinProductos: 'No hay datos de productos vendidos para el período seleccionado',
                sinVendedores: 'No hay datos de vendedores para el período seleccionado',
                sinCategorias: 'No hay datos de categorías para el período seleccionado',
                sinConversion: 'No hay datos de conversión para el período seleccionado',
                sinVentas: 'No hay ventas registradas para el período seleccionado',
                sinAlertas: 'No hay productos con stock bajo actualmente',
                sinMisProductos: 'No hay datos de productos vendidos para este período',
                sinLeyenda: 'No hay datos disponibles'
            }
        }
    };

    function t(key, lang = 'es') {
        const keys = key.split('.');
        let obj = i18n[lang];
        for (const k of keys) {
            if (!obj || !obj[k]) return key;
            obj = obj[k];
        }
        return obj;
    }

    // Initialize Chart.js defaults on load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', configureChartDefaults);
    } else {
        configureChartDefaults();
    }

    // Export to global namespace for dashboard modules
    window.DashboardCore = {
        configureChartDefaults,
        getChartColors,
        formatoMoneda,
        formatoPorcentaje,
        formatearFecha,
        formatearFechaHora,
        generarClaveCache,
        getCachedData,
        setCachedData,
        getBaseUrl,
        mostrarCargando,
        mostrarError,
        cargarDatosDashboard,
        inicializarSelectorPeriodo,
        crearTablaAccesibleGrafico,
        actualizarKPIsConTendencia,
        generarTicketImpresion,
        imprimirTicket,
        inicializarBotonImprimir,
        crearElementosNoDatos,
        mostrarSinDatos,
        lazyLoadCharts,
        createChartPattern,
        applyPatternFill,
        t
    };
})();