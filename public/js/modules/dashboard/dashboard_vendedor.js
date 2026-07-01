/**
 * Dashboard Vendedor
 * JavaScript para la visualización de datos en el dashboard de vendedor
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
    let graficoRendimiento = null;

    // Función para formatear moneda
    const formatoMoneda = (valor) => {
        const currency = (window.APP && window.APP.currency) ? window.APP.currency : 'Bs';
        return currency + ' ' + parseFloat(valor).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    };

    // Función para formatear porcentaje
    const formatoPorcentaje = (valor) => {
        return parseFloat(valor).toFixed(1) + '%';
    };

    // Función para formatear fechas correctamente
    const formatearFecha = (fechaStr) => {
        try {
            const fecha = new Date(fechaStr + 'T00:00:00');
            if (isNaN(fecha.getTime())) return fechaStr;

            const dia = fecha.getDate().toString().padStart(2, '0');
            const mes = (fecha.getMonth() + 1).toString().padStart(2, '0');
            const anio = fecha.getFullYear();
            return `${dia}/${mes}/${anio}`;
        } catch (e) {
            console.warn("Error formateando fecha:", e);
            return fechaStr;
        }
    };

    // Función para formatear fecha y hora
    const formatearFechaHora = (fechaStr) => {
        if (!fechaStr) return '';
        try {
            // Para manejar formato MySQL datetime
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
            console.warn("Error formateando fecha y hora:", e);
            return fechaStr;
        }
    };

    // Función para generar una clave de caché
    function generarClaveCache(periodo) {
        return periodo;
    }

    // Función para mostrar indicador de carga
    function mostrarCargando(mostrar = true) {
        cargando = mostrar;

        if (mostrar) {
            // Mostrar indicadores en KPIs
            const kpis = document.querySelectorAll('.info-box-number');
            kpis.forEach(kpi => {
                if (!kpi.dataset.originalText && !kpi.querySelector('.fa-spinner')) {
                    kpi.dataset.originalText = kpi.innerHTML;
                    kpi.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                }
            });

            // Añadir indicadores a las tablas
            const tablas = ['tabla-mis-productos', 'tabla-productos-agotar', 'tabla-mis-ventas'];
            tablas.forEach(id => {
                const tabla = document.getElementById(id);
                if (tabla && !tabla.querySelector('.fa-spinner')) {
                    const columnas = id === 'tabla-mis-ventas' ? 6 : 4;
                    tabla.innerHTML = `
                        <tr>
                            <td colspan="${columnas}" class="text-center py-3">
                                <i class="fas fa-spinner fa-spin mr-2"></i> Actualizando datos...
                            </td>
                        </tr>
                    `;
                }
            });
        }
    }

    // Función para mostrar mensajes de error
    function mostrarError(mensaje) {
        try {
            Swal.fire({
                title: 'Error',
                text: mensaje,
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
        } catch (e) {
            alert(mensaje);
        }
    }

    // Función para obtener la URL base correcta
    function getBaseUrl() {
        if (typeof baseUrl !== 'undefined' && baseUrl) {
            return baseUrl.replace(/\/$/, '');
        }
        return '';
    }

    // Función para cargar datos del dashboard mediante AJAX
    async function cargarDatosDashboard(periodo) {
        try {
            // Generar clave de caché
            const claveCache = generarClaveCache(periodo);

            // Verificar si los datos están en caché y no tienen más de 5 minutos
            const ahora = new Date().getTime();
            if (dashboardCache[claveCache] &&
                (ahora - dashboardCache[claveCache].timestamp) < 5 * 60 * 1000) {
                actualizarDashboard(dashboardCache[claveCache].data);
                return true;
            }

            mostrarCargando(true);

            // Obtener la URL base correcta
            const baseUrl = getBaseUrl();
            let url = baseUrl + '/controllers/dashboard/get_vendedor_dashboard_data.php';

            // Si la URL no funciona, intentar con rutas relativas
            if (!url.startsWith('http')) {
                url = 'controllers/dashboard/get_vendedor_dashboard_data.php';
            }

            let params = new URLSearchParams();
            params.append('periodo', periodo);

            // Realizar petición AJAX
            const response = await fetch(`${url}?${params.toString()}`);

            // Manejar posibles errores HTTP
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
            }

            // Verificar si la respuesta es JSON válido
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Respuesta no es JSON:', text);
                throw new Error('Respuesta del servidor no es JSON válido. Verifique la consola para más detalles.');
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

    /**
 * Función para cargar datos de prueba cuando no hay datos disponibles
 */
    function cargarDatosDePrueba() {
        const datosPrueba = {
            success: true,
            periodo: {
                tipo: 'hoy',
                fechaInicio: new Date().toISOString().split('T')[0],
                fechaFin: new Date().toISOString().split('T')[0],
                descripcion: 'Hoy (datos de ejemplo)'
            },
            resumen: {
                ventasTotales: 0,
                comisionEstimada: 0,
                totalVentas: 0,
                clientesAtendidos: 0
            },
            productosVendidos: [],
            ultimasVentas: [],
            datosRendimiento: [],
            productosAgotar: [],
            metas: {
                ventas: {
                    meta: 10000,
                    actual: 0,
                    porcentaje: 0
                },
                clientes: {
                    meta: 20,
                    actual: 0,
                    porcentaje: 0
                },
                productos: {
                    meta: 50,
                    actual: 0,
                    porcentaje: 0
                }
            }
        };

        datosActuales = datosPrueba;
        actualizarDashboard(datosPrueba);
    }

    /**
     * Función principal para actualizar el dashboard con datos
     */
    function actualizarDashboard(datos) {
        try {
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
            actualizarKPIs(datos.resumen);

            // Actualizar metas
            actualizarMetas(datos.metas);

            // Actualizar gráfico de rendimiento
            crearGraficoRendimiento(datos.datosRendimiento);

            // Actualizar tablas
            actualizarMisProductos(datos.productosVendidos);
            actualizarProductosPorAgotar(datos.productosAgotar);
            actualizarMisVentas(datos.ultimasVentas);
        } catch (error) {
            console.error("Error en actualizarDashboard:", error);
        }
    }


    /**
     * Crea el gráfico de rendimiento con manejo adecuado para casos sin datos
     */
    function crearGraficoRendimiento(datos) {
        try {
            const canvas = document.getElementById('grafico-rendimiento');

            // Verificar si el elemento existe
            if (!canvas) {
                console.error('Elemento grafico-rendimiento no encontrado en el DOM');
                return;
            }

            const ctx = canvas.getContext('2d');

            // Destruir gráfico existente si lo hay
            if (graficoRendimiento) {
                try {
                    graficoRendimiento.destroy();
                } catch (e) {
                    console.warn("Error al destruir gráfico existente:", e);
                }
            }

            // Verificar si hay datos
            if (!datos || datos.length === 0) {
                // Intentar mostrar mensaje de "sin datos" si existe el elemento
                const sinDatosRendimiento = document.getElementById('sin-datos-rendimiento');
                if (sinDatosRendimiento) {
                    canvas.style.display = 'none';
                    sinDatosRendimiento.style.display = 'block';
                } else {
                    // Si no existe el elemento para mostrar mensaje, dibujarlo en el canvas
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.font = '14px Arial';
                    ctx.textAlign = 'center';
                    ctx.fillStyle = '#6c757d';
                    ctx.fillText('No hay datos disponibles', canvas.width / 2, canvas.height / 2);
                }
                return;
            }

            // Si hay datos, asegurar que el canvas esté visible y el mensaje oculto
            canvas.style.display = 'block';
            const sinDatosRendimiento = document.getElementById('sin-datos-rendimiento');
            if (sinDatosRendimiento) {
                sinDatosRendimiento.style.display = 'none';
            }

            // Preparar datos con formato de fecha correcto
            const labels = datos.map(item => {
                return formatearFecha(item.fecha).split('/').slice(0, 2).join('/'); // Solo día/mes
            });

            const values = datos.map(item => parseFloat(item.venta));

            // Crear gráfico de rendimiento
            graficoRendimiento = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Ventas',
                        data: values,
                        borderColor: colores.azul,
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        pointBackgroundColor: colores.azul,
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: colores.azul,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.1,
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        display: false
                    },
                    tooltips: {
                        callbacks: {
                            label: function (tooltipItem) {
                                return 'Ventas: ' + formatoMoneda(tooltipItem.yLabel);
                            }
                        }
                    },
                    scales: {
                        xAxes: [{
                            gridLines: {
                                display: false
                            }
                        }],
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                callback: function (value) {
                                    return ((window.APP && window.APP.currency) ? window.APP.currency : 'Bs') + ' ' + value.toLocaleString();
                                }
                            }
                        }]
                    }
                }
            });
        } catch (error) {
            console.error("Error en crearGraficoRendimiento:", error);
        }
    }

    // Actualizar tabla de mis productos más vendidos
    function actualizarMisProductos(datos) {
        const tablaProductos = document.getElementById('tabla-mis-productos');

        if (!tablaProductos) {
            console.error('Elemento tabla-mis-productos no encontrado en el DOM');
            return;
        }

        if (!datos || datos.length === 0) {
            tablaProductos.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center py-3">
                        No hay datos de productos vendidos para este período
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
                </tr>
            `;
        });

        tablaProductos.innerHTML = html;
    }

    // Actualizar tabla de productos por agotar
    function actualizarProductosPorAgotar(datos) {
        const tablaProductos = document.getElementById('tabla-productos-agotar');

        if (!tablaProductos) {
            console.error('Elemento tabla-productos-agotar no encontrado en el DOM');
            return;
        }

        if (!datos || datos.length === 0) {
            tablaProductos.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center py-3">
                        No hay productos con stock bajo actualmente
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
                    <td class="text-center">
                        <span class="badge ${claseBadge}">${producto.estado === 'crítico' ? 'Crítico' : 'Bajo'}</span>
                    </td>
                </tr>
            `;
        });

        tablaProductos.innerHTML = html;
    }

    // Actualizar tabla de mis últimas ventas
    function actualizarMisVentas(datos) {
        const tablaVentas = document.getElementById('tabla-mis-ventas');

        if (!tablaVentas) {
            console.error('Elemento tabla-mis-ventas no encontrado en el DOM');
            return;
        }

        if (!datos || datos.length === 0) {
            tablaVentas.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-3">
                    No hay ventas registradas para este período
                </td>
            </tr>
        `;
            return;
        }

        let html = '';

        datos.forEach(venta => {
            // Verificar si la función global formatDateTime existe
            let fechaFormateada;
            if (typeof formatDateTime === 'function') {
                fechaFormateada = formatDateTime(venta.fecha);
            } else {
                // Función de respaldo por si no existe la global
                fechaFormateada = formatearFechaHora(venta.fecha);
            }

            // Verificar si la función global formatCurrency existe
            let montoFormateado;
            if (typeof formatCurrency === 'function') {
                montoFormateado = formatCurrency(venta.total);
            } else {
                // Función de respaldo
                montoFormateado = formatoMoneda(venta.total);
            }

            html += `
            <tr>
                <td>${venta.id}</td>
                <td>${venta.cliente}</td>
                <td>${fechaFormateada}</td>
                <td>${venta.productos}</td>
                <td>${venta.metodo}</td>
                <td class="text-right">${montoFormateado}</td>
            </tr>
        `;
        });

        tablaVentas.innerHTML = html;
    }

    // Actualizar KPIs principales
    function actualizarKPIs(resumen) {
        // Verificar si hay datos
        if (!resumen) return;

        // Ventas del vendedor
        const ventasVendedorEl = document.getElementById('ventas-vendedor');
        if (ventasVendedorEl) {
            ventasVendedorEl.innerHTML = formatoMoneda(resumen.ventasTotales);
        }

        // Comisión estimada
        const comisionVendedorEl = document.getElementById('comision-vendedor');
        if (comisionVendedorEl) {
            comisionVendedorEl.innerHTML = formatoMoneda(resumen.comisionEstimada);
        }

        // Total de ventas realizadas
        const totalVentasEl = document.getElementById('total-ventas');
        if (totalVentasEl) {
            totalVentasEl.innerHTML = resumen.totalVentas;
        }

        // Clientes atendidos
        const clientesAtendidosEl = document.getElementById('clientes-atendidos');
        if (clientesAtendidosEl) {
            clientesAtendidosEl.innerHTML = resumen.clientesAtendidos;
        }
    }

    // Actualizar metas
    function actualizarMetas(metas) {
        if (!metas) return;

        // Meta de ventas
        if (metas.ventas) {
            const metaVentasValorEl = document.getElementById('meta-ventas-valor');
            const metaVentasBarraEl = document.getElementById('meta-ventas-barra');
            const metaVentasDetalleEl = document.getElementById('meta-ventas-detalle');

            if (metaVentasValorEl) metaVentasValorEl.textContent = metas.ventas.porcentaje + '%';
            if (metaVentasBarraEl) metaVentasBarraEl.style.width = metas.ventas.porcentaje + '%';
            if (metaVentasDetalleEl) metaVentasDetalleEl.textContent =
                `${formatoMoneda(metas.ventas.actual)} / ${formatoMoneda(metas.ventas.meta)}`;
        }

        // Meta de clientes
        if (metas.clientes) {
            const metaClientesValorEl = document.getElementById('meta-clientes-valor');
            const metaClientesBarraEl = document.getElementById('meta-clientes-barra');
            const metaClientesDetalleEl = document.getElementById('meta-clientes-detalle');

            if (metaClientesValorEl) metaClientesValorEl.textContent = metas.clientes.porcentaje + '%';
            if (metaClientesBarraEl) metaClientesBarraEl.style.width = metas.clientes.porcentaje + '%';
            if (metaClientesDetalleEl) metaClientesDetalleEl.textContent =
                `${metas.clientes.actual} / ${metas.clientes.meta}`;
        }

        // Meta de productos
        if (metas.productos) {
            const metaProductosValorEl = document.getElementById('meta-productos-valor');
            const metaProductosBarraEl = document.getElementById('meta-productos-barra');
            const metaProductosDetalleEl = document.getElementById('meta-productos-detalle');

            if (metaProductosValorEl) metaProductosValorEl.textContent = metas.productos.porcentaje + '%';
            if (metaProductosBarraEl) metaProductosBarraEl.style.width = metas.productos.porcentaje + '%';
            if (metaProductosDetalleEl) metaProductosDetalleEl.textContent =
                `${metas.productos.actual} / ${metas.productos.meta}`;
        }
    }

    // Cargar datos según período seleccionado
    function cargarDatosPeriodo(periodo) {
        periodoActual = periodo;
        cargarDatosDashboard(periodo);

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

    /**
     * Crea elementos para mostrar mensajes de "no hay datos" en gráficos
     */
    function crearElementosNoDatosGraficos() {
        try {
            // Para el gráfico de rendimiento
            if (!document.getElementById('sin-datos-rendimiento')) {
                const contenedorRendimiento = document.getElementById('grafico-rendimiento');
                if (contenedorRendimiento && contenedorRendimiento.parentNode) {
                    const sinDatosRendimiento = document.createElement('div');
                    sinDatosRendimiento.id = 'sin-datos-rendimiento';
                    sinDatosRendimiento.className = 'text-center text-muted py-4';
                    sinDatosRendimiento.innerHTML = '<p><i class="fas fa-info-circle mr-1"></i> No hay datos de rendimiento para el período seleccionado</p>';
                    sinDatosRendimiento.style.display = 'none';
                    contenedorRendimiento.parentNode.appendChild(sinDatosRendimiento);
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
                cargarDatosPeriodo(periodo);
            });
        }
    }

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