/**
 * Dashboard
 * JavaScript para la visualización de datos en el dashboard
 */

document.addEventListener('DOMContentLoaded', function () {
    // Configuración global de Chart.js
    Chart.defaults.global.defaultFontFamily = 'Source Sans Pro, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica Neue';
    Chart.defaults.global.defaultFontSize = 12;
    Chart.defaults.global.defaultFontColor = '#666';
    Chart.defaults.global.responsive = true;
    Chart.defaults.global.maintainAspectRatio = false;

    // Variables de estado
    let periodoActual = 'hoy';
    let fechaDesde = null;
    let fechaHasta = null;
    let datosActuales = null;
    let cargando = false;
    const dashboardCache = {};

    // Colores para gráficos
    const colores = {
        azul: '#007bff',
        verde: '#28a745',
        amarillo: '#ffc107',
        rojo: '#dc3545',
        cian: '#17a2b8',
        gris: '#6c757d',
        naranja: '#fd7e14',
        morado: '#6f42c1',
        rosa: '#e83e8c'
    };

    // Referencias a gráficos
    let graficoMetodosPago = null;
    let graficoCategorias = null;

    // Función para formatear moneda
    const formatoMoneda = (valor) => {
        const currency = (window.APP && window.APP.currency) ? window.APP.currency : 'Bs';
        return currency + ' ' + parseFloat(valor).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    };

    // Función para formatear porcentaje
    const formatoPorcentaje = (valor) => {
        return parseFloat(valor).toFixed(1) + '%';
    };

    // Función para generar una clave de caché
    function generarClaveCache(periodo, fechaInicio = null, fechaFin = null) {
        if (periodo === 'personalizado' && fechaInicio && fechaFin) {
            return `${periodo}_${fechaInicio}_${fechaFin}`;
        }
        return periodo;
    }

    // Función para mostrar indicador de carga
    function mostrarCargando(mostrar = true) {
        cargando = mostrar;

        if (mostrar) {
            // En lugar de un overlay completo, mostrar indicadores más sutiles
            const kpis = document.querySelectorAll('.info-box-number');
            kpis.forEach(kpi => {
                // Guardar el texto actual si no está guardado
                if (!kpi.dataset.originalText && !kpi.querySelector('.fa-spinner')) {
                    kpi.dataset.originalText = kpi.innerHTML;
                    kpi.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                }
            });

            // Añadir indicadores a las tablas
            const tablas = ['tabla-productos-vendidos', 'tabla-productos-agotar', 'tabla-ultimas-ventas'];
            tablas.forEach(id => {
                const tabla = document.getElementById(id);
                if (tabla && !tabla.querySelector('.fa-spinner')) {
                    tabla.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center py-3">
                                <i class="fas fa-spinner fa-spin mr-2"></i> Actualizando datos...
                            </td>
                        </tr>
                    `;
                }
            });
        } else {
            // Eliminar overlay de carga
            const overlay = document.getElementById('loading-overlay');
            if (overlay) {
                overlay.remove();
            }
        }
    }

    // Función para mostrar mensajes de error
    function mostrarError(mensaje) {
        Swal.fire({
            title: 'Error',
            text: mensaje,
            icon: 'error',
            confirmButtonText: 'Aceptar'
        });
    }

    // Función para obtener la URL base correcta
    function getBaseUrl() {
        if (typeof baseUrl !== 'undefined' && baseUrl) {
            return baseUrl.replace(/\/$/, '');
        }
        return '';
    }

    // Función para cargar datos del dashboard mediante AJAX
    async function cargarDatosDashboard(periodo, fechaInicio = null, fechaFin = null) {
        try {
            // Generar clave de caché
            const claveCache = generarClaveCache(periodo, fechaInicio, fechaFin);

            // Verificar si los datos están en caché y no tienen más de 5 minutos
            const ahora = new Date().getTime();
            if (dashboardCache[claveCache] &&
                (ahora - dashboardCache[claveCache].timestamp) < 5 * 60 * 1000) { // 5 minutos en milisegundos

                actualizarDashboard(dashboardCache[claveCache].data);
                return true;
            }

            mostrarCargando(true);

            // Obtener la URL base correcta
            const baseUrl = getBaseUrl();
            let url = baseUrl + '/controllers/dashboard/get_dashboard_data.php';

            // Si la URL no funciona, intentar con rutas relativas
            if (!url.startsWith('http')) {
                url = 'controllers/dashboard/get_dashboard_data.php';
            }

            let params = new URLSearchParams();
            params.append('periodo', periodo);

            if (fechaInicio && fechaFin) {
                params.append('fecha_inicio', fechaInicio);
                params.append('fecha_fin', fechaFin);
            }

            // Realizar petición AJAX
            const response = await fetch(`${url}?${params.toString()}`);

            // Manejar posibles errores HTTP
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
            }

            // Intentar parsear la respuesta como JSON
            let data;
            try {
                data = await response.json();
            } catch (e) {
                const text = await response.text();
                console.error('Respuesta no válida:', text);
                throw new Error('La respuesta del servidor no es un JSON válido');
            }

            mostrarCargando(false);

            if (!data.success) {
                mostrarError(data.message || 'Error al cargar datos del dashboard');
                return false;
            }

            // Guardar datos en caché
            dashboardCache[claveCache] = {
                data: data,
                timestamp: ahora
            };

            // Guardar datos y actualizar interfaz
            datosActuales = data;
            actualizarDashboard(data);
            return true;

        } catch (error) {
            mostrarCargando(false);

            // Si no hay datos, cargar datos de ejemplo para mostrar la interfaz
            if (!datosActuales) {
                cargarDatosDePrueba();
            }

            mostrarError('Error de conexión: ' + error.message);
            console.error('Error cargando datos:', error);
            return false;
        }
    }

    // Función para cargar datos de prueba cuando no hay datos disponibles
    function cargarDatosDePrueba() {
        console.log("Cargando datos de prueba para visualización");

        const datosPrueba = {
            success: true,
            periodo: {
                tipo: 'hoy',
                fechaInicio: new Date().toISOString().split('T')[0],
                fechaFin: new Date().toISOString().split('T')[0],
                descripcion: 'Hoy (datos de ejemplo)'
            },
            kpis: {
                ventasTotales: 0,
                gananciasNetas: 0,
                totalTransacciones: 0,
                ventaPromedio: 0
            },
            tendencias: {
                ventas: 0,
                ganancias: 0,
                transacciones: 0,
                ventaPromedio: 0
            },
            metodosPago: [],
            productosMasVendidos: [],
            ventasPorCategoria: [],
            productosAgotar: [],
            ultimasVentas: []
        };

        datosActuales = datosPrueba;
        actualizarDashboard(datosPrueba);
    }

    // Función principal para actualizar el dashboard con datos
    function actualizarDashboard(datos) {
        // Verificar si hay datos
        if (!datos) {
            console.error("No hay datos para actualizar el dashboard");
            return;
        }

        // Actualizar título del período
        if (datos.periodo && datos.periodo.descripcion) {
            const periodoTitulo = document.getElementById('periodo-titulo');
            if (periodoTitulo) {
                periodoTitulo.textContent = datos.periodo.descripcion;
            }
        }

        // Actualizar KPIs
        actualizarKPIs(datos.kpis, datos.tendencias);

        // Actualizar gráficos
        crearGraficoMetodosPago(datos.metodosPago);
        crearGraficoCategorias(datos.ventasPorCategoria);

        // Actualizar tablas
        actualizarProductosMasVendidos(datos.productosMasVendidos);
        actualizarProductosPorAgotar(datos.productosAgotar);
        actualizarUltimasVentas(datos.ultimasVentas);

        // Actualizar barras de progreso de métodos de pago
        actualizarBarrasMetodosPago(datos.metodosPago);
    }

    // Crear gráfico de métodos de pago
    function crearGraficoMetodosPago(datos) {
        try {
            const ctx = document.getElementById('grafico-metodos-pago');
            if (!ctx) {
                console.warn("Elemento grafico-metodos-pago no encontrado");
                return;
            }

            // Destruir gráfico existente si lo hay
            if (graficoMetodosPago) {
                graficoMetodosPago.destroy();
            }

            // Verificar si hay datos
            if (!datos || datos.length === 0) {
                // Intentar mostrar mensaje de "sin datos" si existe el elemento
                const sinDatosMetodos = document.getElementById('sin-datos-metodos');
                if (sinDatosMetodos) {
                    ctx.style.display = 'none';
                    sinDatosMetodos.style.display = 'block';
                } else {
                    // Si no existe el elemento para mostrar mensaje, dibujarlo en el canvas
                    const context = ctx.getContext('2d');
                    context.clearRect(0, 0, ctx.width, ctx.height);
                    context.font = '14px Arial';
                    context.textAlign = 'center';
                    context.fillStyle = '#6c757d';
                    context.fillText('No hay datos disponibles', ctx.width / 2, ctx.height / 2);
                }
                return;
            }

            // Si hay datos, asegurar que el canvas esté visible y el mensaje oculto
            ctx.style.display = 'block';
            const sinDatosMetodos = document.getElementById('sin-datos-metodos');
            if (sinDatosMetodos) {
                sinDatosMetodos.style.display = 'none';
            }

            // Preparar datos
            const labels = datos.map(item => item.metodo);
            const values = datos.map(item => item.porcentaje);
            const backgroundColors = [
                colores.verde,
                colores.cian,
                colores.amarillo,
                colores.azul,
                colores.naranja
            ];

            graficoMetodosPago = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: backgroundColors,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            boxWidth: 12
                        }
                    },
                    tooltips: {
                        callbacks: {
                            label: function (tooltipItem, data) {
                                const index = tooltipItem.index;
                                const metodo = datos[index];
                                return `${metodo.metodo}: ${formatoMoneda(metodo.monto)} (${formatoPorcentaje(metodo.porcentaje)})`;
                            }
                        }
                    },
                    animation: {
                        duration: 500
                    }
                }
            });
        } catch (error) {
            console.error("Error en crearGraficoMetodosPago:", error);
        }
    }

    // Crear gráfico de categorías
    function crearGraficoCategorias(datos) {
        try {
            const ctx = document.getElementById('grafico-categorias');
            if (!ctx) {
                console.warn("Elemento grafico-categorias no encontrado");
                return;
            }

            // Destruir gráfico existente si lo hay
            if (graficoCategorias) {
                graficoCategorias.destroy();
            }

            // Verificar si hay datos
            if (!datos || datos.length === 0) {
                // Intentar mostrar mensaje de "sin datos" si existe el elemento
                const sinDatosCategorias = document.getElementById('sin-datos-categorias');
                if (sinDatosCategorias) {
                    ctx.style.display = 'none';
                    sinDatosCategorias.style.display = 'block';
                } else {
                    // Si no existe el elemento para mostrar mensaje, dibujarlo en el canvas
                    const context = ctx.getContext('2d');
                    context.clearRect(0, 0, ctx.width, ctx.height);
                    context.font = '14px Arial';
                    context.textAlign = 'center';
                    context.fillStyle = '#6c757d';
                    context.fillText('No hay datos disponibles', ctx.width / 2, ctx.height / 2);
                }
                return;
            }

            // Si hay datos, asegurar que el canvas esté visible y el mensaje oculto
            ctx.style.display = 'block';
            const sinDatosCategorias = document.getElementById('sin-datos-categorias');
            if (sinDatosCategorias) {
                sinDatosCategorias.style.display = 'none';
            }

            // Preparar datos
            const labels = datos.map(item => item.categoria);
            const values = datos.map(item => item.ventas);
            const backgroundColors = [
                colores.azul,
                colores.verde,
                colores.amarillo,
                colores.rojo,
                colores.morado,
                colores.cian,
                colores.naranja
            ];

            // Asegurar que hay suficientes colores
            while (backgroundColors.length < datos.length) {
                backgroundColors.push(...backgroundColors);
            }

            graficoCategorias = new Chart(ctx, {
                type: 'horizontalBar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Ventas por Categoría',
                        data: values,
                        backgroundColor: backgroundColors.slice(0, datos.length),
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    legend: {
                        display: false
                    },
                    tooltips: {
                        callbacks: {
                            label: function (tooltipItem, data) {
                                const index = tooltipItem.index;
                                const categoria = datos[index];
                                return `${categoria.categoria}: ${formatoMoneda(categoria.ventas)} (${formatoPorcentaje(categoria.porcentaje)})`;
                            }
                        }
                    },
                    scales: {
                        xAxes: [{
                            ticks: {
                                beginAtZero: true,
                                callback: function (value) {
                                    return ((window.APP && window.APP.currency) ? window.APP.currency : 'Bs') + ' ' + value.toLocaleString();
                                }
                            },
                            gridLines: {
                                display: true
                            }
                        }],
                        yAxes: [{
                            gridLines: {
                                display: false
                            }
                        }]
                    }
                }
            });
        } catch (error) {
            console.error("Error en crearGraficoCategorias:", error);
        }
    }

    // Actualizar tabla de productos más vendidos
    function actualizarProductosMasVendidos(datos) {
        const tablaProductos = document.getElementById('tabla-productos-vendidos');

        if (!tablaProductos) {
            console.warn("Elemento tabla-productos-vendidos no encontrado");
            return;
        }

        if (!datos || datos.length === 0) {
            tablaProductos.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-3 text-muted">
                    <i class="fas fa-info-circle mr-1"></i> No hay datos de productos vendidos para el período seleccionado
                </td>
            </tr>
        `;
            return;
        }

        let html = '';
        datos.forEach(producto => {
            html += `
            <tr>
                <td>${producto.producto}</td>
                <td>${producto.categoria}</td>
                <td class="text-right">${formatoMoneda(producto.precio)}</td>
                <td class="text-right">${producto.unidades}</td>
                <td class="text-right">${formatoMoneda(producto.total)}</td>
            </tr>
        `;
        });

        tablaProductos.innerHTML = html;
    }

    // Actualizar tabla de productos por agotar
    function actualizarProductosPorAgotar(datos) {
        const tablaProductos = document.getElementById('tabla-productos-agotar');

        if (!tablaProductos) {
            console.warn("Elemento tabla-productos-agotar no encontrado");
            return;
        }

        if (!datos || datos.length === 0) {
            tablaProductos.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-3 text-muted">
                    <i class="fas fa-info-circle mr-1"></i> No hay productos con stock bajo actualmente
                </td>
            </tr>
        `;
            return;
        }

        let html = '';
        datos.forEach(producto => {
            let claseBadge = producto.estado === 'crítico' ? 'badge-danger' : 'badge-warning';

            html += `
            <tr>
                <td>${producto.producto}</td>
                <td>${producto.categoria}</td>
                <td class="text-center">${producto.stockActual}</td>
                <td class="text-center">${producto.stockMinimo}</td>
                <td class="text-center">
                    <span class="badge ${claseBadge}">${producto.estado === 'crítico' ? 'Crítico' : 'Bajo'}</span>
                </td>
            </tr>
        `;
        });

        tablaProductos.innerHTML = html;
    }

    // Actualizar tabla de últimas ventas
    function actualizarUltimasVentas(datos) {
        const tablaVentas = document.getElementById('tabla-ultimas-ventas');

        if (!tablaVentas) {
            console.warn("Elemento tabla-ultimas-ventas no encontrado");
            return;
        }

        if (!datos || datos.length === 0) {
            tablaVentas.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-3 text-muted">
                    <i class="fas fa-info-circle mr-1"></i> No hay ventas registradas para el período seleccionado
                </td>
            </tr>
        `;
            return;
        }

        let html = '';
        datos.forEach(venta => {
            // Formatear fecha
            let fechaFormateada;
            if (typeof formatDateTime === 'function') {
                fechaFormateada = formatDateTime(venta.fecha);
            } else {
                // Función de respaldo
                fechaFormateada = venta.fecha;
                if (typeof venta.fecha === 'string') {
                    try {
                        const date = new Date(venta.fecha);
                        if (!isNaN(date.getTime())) {
                            fechaFormateada = date.toLocaleDateString('es-BO') + ' ' +
                                date.toLocaleTimeString('es-BO', { hour: '2-digit', minute: '2-digit' });
                        }
                    } catch (e) {
                        console.warn("Error al formatear fecha:", e);
                    }
                }
            }

            // Formatear montos
            let montoFormateado, gananciaFormateada;
            if (typeof formatCurrency === 'function') {
                montoFormateado = formatCurrency(venta.total);
                gananciaFormateada = formatCurrency(venta.ganancia);
            } else {
                // Función de respaldo
                montoFormateado = formatoMoneda(venta.total);
                gananciaFormateada = formatoMoneda(venta.ganancia);
            }

            html += `
            <tr>
                <td>${venta.id}</td>
                <td>${venta.cliente}</td>
                <td>${fechaFormateada}</td>
                <td>${venta.productos}</td>
                <td>${venta.metodo}</td>
                <td class="text-right">${montoFormateado}</td>
                <td class="text-right text-success">${gananciaFormateada}</td>
            </tr>
        `;
        });

        tablaVentas.innerHTML = html;
    }

    // Actualizar barras de progreso de métodos de pago
    function actualizarBarrasMetodosPago(datos) {
        const contenedorBarras = document.getElementById('metodos-pago-barras');

        if (!contenedorBarras) {
            console.warn("Elemento metodos-pago-barras no encontrado");
            return;
        }

        // Si no hay datos, simplemente vaciar el contenedor sin mostrar mensaje
        if (!datos || datos.length === 0) {
            contenedorBarras.innerHTML = '';
            return;
        }

        let html = '';
        // Colores para los métodos de pago
        const coloresMetodos = {
            'Efectivo': 'bg-success',
            'Tarjeta': 'bg-info',
            'QR': 'bg-warning',
            'Transferencia': 'bg-primary'
        };

        datos.forEach(metodo => {
            const colorClase = coloresMetodos[metodo.metodo] || 'bg-secondary';

            html += `
            <div class="progress-group">
                <span class="progress-text">${metodo.metodo}</span>
                <span class="float-right">${formatoMoneda(metodo.monto)} (${formatoPorcentaje(metodo.porcentaje)})</span>
                <div class="progress progress-sm">
                    <div class="${colorClase}" style="width: ${metodo.porcentaje}%"></div>
                </div>
            </div>
        `;
        });

        contenedorBarras.innerHTML = html;
    }

    // Actualizar KPIs principales
    function actualizarKPIs(kpis, tendencias) {
        // Verificar si hay datos
        if (!kpis) return;

        // Ventas totales
        const ventasTotalesEl = document.getElementById('ventas-totales');
        if (ventasTotalesEl) {
            const tendenciaVentas = tendencias ? tendencias.ventas : 0;
            const claseIcono = tendenciaVentas >= 0 ? 'text-success' : 'text-danger';
            const iconoFlecha = tendenciaVentas >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';

            ventasTotalesEl.innerHTML = `
                ${formatoMoneda(kpis.ventasTotales)}
                <small class="${claseIcono} ml-1"><i class="fas ${iconoFlecha}"></i> ${Math.abs(tendenciaVentas)}%</small>
            `;
        }

        // Ganancias netas
        const gananciasNetasEl = document.getElementById('ganancias-netas');
        if (gananciasNetasEl) {
            const tendenciaGanancias = tendencias ? tendencias.ganancias : 0;
            const claseIcono = tendenciaGanancias >= 0 ? 'text-success' : 'text-danger';
            const iconoFlecha = tendenciaGanancias >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';

            gananciasNetasEl.innerHTML = `
                ${formatoMoneda(kpis.gananciasNetas)}
                <small class="${claseIcono} ml-1"><i class="fas ${iconoFlecha}"></i> ${Math.abs(tendenciaGanancias)}%</small>
            `;
        }

        // Total transacciones
        const totalTransaccionesEl = document.getElementById('total-transacciones');
        if (totalTransaccionesEl) {
            const tendenciaTransacciones = tendencias ? tendencias.transacciones : 0;
            const claseIcono = tendenciaTransacciones >= 0 ? 'text-success' : 'text-danger';
            const iconoFlecha = tendenciaTransacciones >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';

            totalTransaccionesEl.innerHTML = `
                ${kpis.totalTransacciones}
                <small class="${claseIcono} ml-1"><i class="fas ${iconoFlecha}"></i> ${Math.abs(tendenciaTransacciones)}%</small>
            `;
        }

        // Venta promedio
        const ventaPromedioEl = document.getElementById('venta-promedio');
        if (ventaPromedioEl) {
            const tendenciaVentaPromedio = tendencias ? tendencias.ventaPromedio : 0;
            const claseIcono = tendenciaVentaPromedio >= 0 ? 'text-success' : 'text-danger';
            const iconoFlecha = tendenciaVentaPromedio >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';

            ventaPromedioEl.innerHTML = `
                ${formatoMoneda(kpis.ventaPromedio)}
                <small class="${claseIcono} ml-1"><i class="fas ${iconoFlecha}"></i> ${Math.abs(tendenciaVentaPromedio)}%</small>
            `;
        }
    }

    // Cargar datos según período seleccionado
    function cargarDatosPeriodo(periodo) {
        periodoActual = periodo;

        // Ocultar o mostrar el selector de fechas según corresponda
        const fechasPersonalizadas = document.getElementById('fechas-personalizadas');
        if (fechasPersonalizadas) {
            fechasPersonalizadas.style.display = (periodo === 'personalizado') ? 'block' : 'none';
        }

        // Si es personalizado pero no hay fechas seleccionadas, no cargar datos aún
        if (periodo === 'personalizado' && (!fechaDesde || !fechaHasta)) {
            return;
        }

        // Cargar datos del servidor
        cargarDatosDashboard(periodo, fechaDesde, fechaHasta);

        // Actualizar el valor seleccionado en el select
        const selectorPeriodo = document.getElementById('selector-periodo');
        if (selectorPeriodo && selectorPeriodo.value !== periodo) {
            selectorPeriodo.value = periodo;
            try {
                $(selectorPeriodo).trigger('change.select2'); // Actualizar select2
            } catch (e) {
                console.warn("Error al actualizar select2:", e);
            }
        }
    }

    function crearElementosNoDatosGraficos() {
        try {
            // Para métodos de pago
            if (!document.getElementById('sin-datos-metodos')) {
                const contenedorMetodos = document.getElementById('grafico-metodos-pago');
                if (contenedorMetodos && contenedorMetodos.parentNode) {
                    const sinDatosMetodos = document.createElement('div');
                    sinDatosMetodos.id = 'sin-datos-metodos';
                    sinDatosMetodos.className = 'text-center text-muted py-4';
                    sinDatosMetodos.innerHTML = '<p><i class="fas fa-info-circle mr-1"></i> No hay datos de métodos de pago para el período seleccionado</p>';
                    sinDatosMetodos.style.display = 'none';
                    contenedorMetodos.parentNode.appendChild(sinDatosMetodos);
                }
            }

            // Para categorías
            if (!document.getElementById('sin-datos-categorias')) {
                const contenedorCategorias = document.getElementById('grafico-categorias');
                if (contenedorCategorias && contenedorCategorias.parentNode) {
                    const sinDatosCategorias = document.createElement('div');
                    sinDatosCategorias.id = 'sin-datos-categorias';
                    sinDatosCategorias.className = 'text-center text-muted py-4';
                    sinDatosCategorias.innerHTML = '<p><i class="fas fa-info-circle mr-1"></i> No hay datos de categorías para el período seleccionado</p>';
                    sinDatosCategorias.style.display = 'none';
                    contenedorCategorias.parentNode.appendChild(sinDatosCategorias);
                }
            }
        } catch (error) {
            console.error("Error al crear elementos 'no hay datos' para gráficos:", error);
        }
    }

    // Inicializar componentes interactivos
    function inicializarComponentes() {
        // Inicializar Select2 si está disponible
        try {
            if (typeof $ !== 'undefined' && $.fn.select2) {
                $('.select2').select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    allowClear: false,
                    minimumResultsForSearch: Infinity,
                    closeOnSelect: true,
                    dropdownAutoWidth: false
                });
            }
        } catch (e) {
            console.warn("Error al inicializar Select2:", e);
        }

        // Selector de período — usar jQuery para capturar eventos de Select2
        const selectorPeriodo = document.getElementById('selector-periodo');
        if (selectorPeriodo) {
            $(selectorPeriodo).on('change', function () {
                const periodo = this.value;

                if (periodo === 'personalizado') {
                    const fechasPersonalizadas = document.getElementById('fechas-personalizadas');
                    if (fechasPersonalizadas) {
                        fechasPersonalizadas.style.display = 'block';
                    }
                    return;
                }

                cargarDatosPeriodo(periodo);
            });
        }

        // Botón aplicar fechas personalizadas
        const btnAplicarFechas = document.getElementById('btn-aplicar-fechas');
        if (btnAplicarFechas) {
            btnAplicarFechas.addEventListener('click', function () {
                const fechaDesdeInput = document.getElementById('fecha-desde');
                const fechaHastaInput = document.getElementById('fecha-hasta');

                if (fechaDesdeInput && fechaHastaInput && fechaDesdeInput.value && fechaHastaInput.value) {
                    // Actualizar variables globales
                    fechaDesde = fechaDesdeInput.value;
                    fechaHasta = fechaHastaInput.value;

                    // Cargar datos con las nuevas fechas
                    cargarDatosPeriodo('personalizado');
                } else {
                    try {
                        Swal.fire({
                            title: 'Error',
                            text: 'Debe seleccionar ambas fechas para aplicar el filtro personalizado',
                            icon: 'error',
                            confirmButtonText: 'Entendido'
                        });
                    } catch (e) {
                        alert('Debe seleccionar ambas fechas para aplicar el filtro personalizado');
                    }
                }
            });
        }

        // Evento del botón de impresión
        const btnImprimir = document.getElementById('btn-imprimir');
        if (btnImprimir) {
            btnImprimir.addEventListener('click', function () {
                // Verificar si tenemos datos cargados
                if (!datosActuales) {
                    try {
                        Swal.fire({
                            title: 'Error',
                            text: 'No hay datos para imprimir. Cargue primero algunos datos.',
                            icon: 'error'
                        });
                    } catch (e) {
                        alert('No hay datos para imprimir. Cargue primero algunos datos.');
                    }
                    return;
                }

                // Mostrar opciones de impresión con SweetAlert2 (solo para personalizar datos de empresa)
                try {
                    Swal.fire({
                        title: 'Datos para el ticket',
                        html: `
                            <div class="form-group text-left">
                                <label>Nombre de empresa:</label>
                                <input type="text" id="empresa-nombre" class="form-control" value="MI EMPRESA">
                            </div>
                            <div class="form-group text-left">
                                <label>NIT:</label>
                                <input type="text" id="empresa-nit" class="form-control" value="000000000">
                            </div>
                            <div class="form-group text-left">
                                <label>Dirección:</label>
                                <input type="text" id="empresa-direccion" class="form-control" value="Calle Principal #123">
                            </div>
                            <div class="form-group text-left">
                                <label>Teléfono:</label>
                                <input type="text" id="empresa-telefono" class="form-control" value="555-1234">
                                </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'Imprimir',
                        cancelButtonText: 'Cancelar',
                        preConfirm: () => {
                            return {
                                empresa: {
                                    nombre: document.getElementById('empresa-nombre').value,
                                    nit: document.getElementById('empresa-nit').value,
                                    direccion: document.getElementById('empresa-direccion').value,
                                    telefono: document.getElementById('empresa-telefono').value
                                }
                            };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const opciones = result.value;

                            // Generar ticket con los datos personalizados
                            const ticketHTML = generarTicketImpresion(datosActuales, opciones.empresa);
                            imprimirTicket(ticketHTML);
                        }
                    });
                } catch (e) {
                    console.error("Error al mostrar SweetAlert:", e);
                    // Imprimir directamente sin opciones si SweetAlert falla
                    const ticketHTML = generarTicketImpresion(datosActuales);
                    imprimirTicket(ticketHTML);
                }
            });
        }

        // Función para generar ticket de impresión
        function generarTicketImpresion(datos, empresaOpciones = null) {
            // Usar datos de empresa personalizados o predeterminados
            const empresa = empresaOpciones || {
                nombre: "MI EMPRESA",
                nit: "000000000-0",
                direccion: "Calle Principal #123",
                telefono: "555-1234"
            };

            // Formatear fecha actual
            const fechaHora = new Date();
            const fechaFormateada = fechaHora.toLocaleDateString('es-BO');
            const horaFormateada = fechaHora.toLocaleTimeString('es-BO', { hour: '2-digit', minute: '2-digit' });

            // Crear estructura del ticket con monospaced font para alineación correcta
            let ticket = `
    <div style="font-family: 'Courier New', monospace; width: 300px; margin: 0 auto; font-size: 12px;">
        <div style="text-align: center; padding-bottom: 10px;">
            <div style="font-size: 16px; font-weight: bold;">${empresa.nombre}</div>
            <div>NIT: ${empresa.nit}</div>
            <div>Dirección: ${empresa.direccion || 'Calle Principal #123'}</div>
            <div>Teléfono: ${empresa.telefono || '555-1234'}</div>
        </div>
        
        <div style="border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 10px 0; margin: 10px 0; text-align: center;">
            <div style="font-size: 14px; font-weight: bold;">REPORTE DE VENTAS</div>
            <div>Fecha: ${fechaFormateada} - ${horaFormateada}</div>
        </div>
        
        <div style="border-bottom: 1px dashed #000; padding-bottom: 10px;">
            <div style="font-weight: bold;">VENTAS ${datos.periodo.descripcion.toUpperCase()}:</div>
            <div>• Total: ${formatoMoneda(datos.kpis.ventasTotales)} (${datos.kpis.totalTransacciones} ventas)</div>
            <div>• Utilidad estimada: ${formatoMoneda(datos.kpis.gananciasNetas)}</div>
        </div>`;

            // Añadir comparativo si hay tendencias
            if (datos.tendencias && (datos.tendencias.ventas !== 0 || datos.tendencias.ganancias !== 0)) {
                const signoVentas = datos.tendencias.ventas >= 0 ? '+' : '';
                ticket += `
        <div style="border-bottom: 1px dashed #000; padding: 10px 0;">
            <div style="font-weight: bold;">COMPARATIVO CON PERIODO ANTERIOR:</div>
            <div>• Diferencia: ${signoVentas}${formatoMoneda(datos.kpis.ventasTotales * datos.tendencias.ventas / 100)} (${signoVentas}${datos.tendencias.ventas}%)</div>
        </div>`;
            }

            // Añadir métodos de pago
            if (datos.metodosPago && datos.metodosPago.length > 0) {
                ticket += `
        <div style="border-bottom: 1px dashed #000; padding: 10px 0;">
            <div style="font-weight: bold;">MÉTODOS DE PAGO:</div>`;

                datos.metodosPago.forEach(metodo => {
                    ticket += `<div>- ${metodo.metodo}: ${formatoMoneda(metodo.monto)} (${formatoPorcentaje(metodo.porcentaje)})</div>`;
                });

                ticket += `</div>`;
            }

            // Añadir top productos
            if (datos.productosMasVendidos && datos.productosMasVendidos.length > 0) {
                ticket += `
        <div style="padding: 10px 0;">
            <div style="font-weight: bold;">TOP PRODUCTOS:</div>`;

                datos.productosMasVendidos.slice(0, 3).forEach((producto, index) => {
                    ticket += `<div>${index + 1}. ${producto.producto} - ${producto.unidades} unidades (${formatoMoneda(producto.total)})</div>`;
                });

                ticket += `</div>`;
            }

            // Añadir línea separadora única antes del resumen
            ticket += `<div style="border-top: 1px dashed #000; margin-top: 10px;"></div>`;

            // Añadir resumen
            if (datos.tendencias && datos.tendencias.ventas !== 0) {
                const comparacion = datos.tendencias.ventas >= 0 ? 'superó' : 'estuvo por debajo de';
                const signoVentas = datos.tendencias.ventas >= 0 ? '+' : '';

                ticket += `
        <div style="text-align: center; padding: 10px 0;">
            <div style="font-weight: bold;">RESUMEN:</div>
            <div>${datos.periodo.descripcion} ${comparacion} el periodo anterior por ${formatoMoneda(Math.abs(datos.kpis.ventasTotales * datos.tendencias.ventas / 100))} (${signoVentas}${datos.tendencias.ventas}%)</div>
        </div>`;
            }

            // Línea separadora final y pie de página
            ticket += `
        <div style="border-top: 1px dashed #000; padding-top: 10px; margin-top: 10px; text-align: center;">
            ${new Date().getFullYear()} © ${(window.APP && window.APP.name) ? window.APP.name : ''}
        </div>
    </div>`;

            return ticket;
        }

        // Función para imprimir el ticket
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
                    body {
                        width: 80mm;
                        margin: 0;
                        padding: 0;
                    }
                }
                body {
                    font-family: 'Courier New', monospace;
                    font-size: 12px;
                    line-height: 1.2;
                }
            </style>
        </head>
        <body>
            ${ticketHTML}
            <script>
                window.onload = function() {
                    window.print();
                    setTimeout(function() {
                        window.close();
                    }, 500);
                };
            </script>
        </body>
        </html>
    `);

            ventanaImpresion.document.close();
        }

        // Establecer fechas por defecto para el selector personalizado
        const hoy = new Date();
        const hace30Dias = new Date(hoy);
        hace30Dias.setDate(hoy.getDate() - 30);

        const fechaHastaInput = document.getElementById('fecha-hasta');
        const fechaDesdeInput = document.getElementById('fecha-desde');

        if (fechaHastaInput) {
            fechaHastaInput.valueAsDate = hoy;
        }

        if (fechaDesdeInput) {
            fechaDesdeInput.valueAsDate = hace30Dias;
        }

        // Almacenar fechas iniciales
        if (fechaDesdeInput && fechaHastaInput) {
            fechaDesde = fechaDesdeInput.value;
            fechaHasta = fechaHastaInput.value;
        }
    }

    // Añadir clase para fondo de KPI de ganancias
    const estiloKPI = document.createElement('style');
    estiloKPI.textContent = `
        .bg-light-success {
            background-color: rgba(40, 167, 69, 0.1);
        }
        
        @media print {
            .card-tools, 
            .main-header, 
            .main-sidebar,
            .main-footer,
            .no-print {
                display: none !important;
            }
            
            .content-wrapper {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            
            .card {
                box-shadow: none !important;
                border: 1px solid #ddd;
            }
        }
        
        #loading-overlay {
            transition: opacity 0.3s ease-in-out;
        }
    `;
    document.head.appendChild(estiloKPI);

    // Inicializar dashboard
    try {
        // Crear elementos de "no hay datos" para los gráficos
        crearElementosNoDatosGraficos();

        // Inicializar componentes
        inicializarComponentes();

        // Cargar datos iniciales
        cargarDatosPeriodo('hoy');
    } catch (error) {
        console.error("Error al inicializar dashboard:", error);

        // Intentar cargar datos de prueba si falla
        try {
            cargarDatosDePrueba();
        } catch (e) {
            console.error("Error al cargar datos de prueba:", e);
        }
    }
});