/**
 * Dashboard Vendedor
 * JavaScript para la visualización de datos en el dashboard de vendedor
 * Usa DashboardCore para lógica compartida
 */

document.addEventListener('DOMContentLoaded', function () {
    const Core = window.DashboardCore;

    // Variables de estado
    let periodoActual = 'hoy';
    let datosActuales = null;

    // Referencias a gráficos
    let graficoRendimiento = null;

    // IDs para loading
    const kpiSelector = '.info-box-number';
    const tableIds = ['tabla-mis-productos', 'tabla-productos-agotar', 'tabla-mis-ventas'];

    // Colores
    const colores = Core.getChartColors();
    const t = Core.t;

    // Crear elemento "sin datos" para gráfico
    Core.crearElementosNoDatos(
        ['grafico-rendimiento'],
        [t('chart.sinDatosRendimiento')]
    );

    // Inicializar selector de período (vendedor no tiene "personalizado" ni "anio")
    const fechas = Core.inicializarSelectorPeriodo(function (periodo) {
        cargarDatosPeriodo(periodo);
    });

    // Cargar datos del dashboard
    async function cargarDatosDashboard(periodo) {
        try {
            const claveCache = Core.generarClaveCache(periodo);
            const cached = Core.getCachedData(claveCache);

            if (cached) {
                actualizarDashboard(cached);
                return true;
            }

            Core.mostrarCargando(true, [kpiSelector], tableIds);

            const data = await Core.cargarDatosDashboard('get_vendedor_dashboard_data.php', {
                periodo: periodo,
                limite: 5
            });

            Core.mostrarCargando(false);

            Core.setCachedData(claveCache, data);
            datosActuales = data;
            actualizarDashboard(data);
            return true;

        } catch (error) {
            Core.mostrarCargando(false);

            if (!datosActuales) {
                cargarDatosDePrueba();
            }

            Core.mostrarError(`${t('errorConexion')}: ${error.message}`);
            console.error('Error cargando datos:', error);
            return false;
        }
    }

    // Datos de prueba
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
                ventas: { meta: 10000, actual: 0, porcentaje: 0 },
                clientes: { meta: 20, actual: 0, porcentaje: 0 },
                productos: { meta: 50, actual: 0, porcentaje: 0 }
            }
        };

        datosActuales = datosPrueba;
        actualizarDashboard(datosPrueba);
    }

    // Actualizar dashboard principal
    function actualizarDashboard(datos) {
        try {
            if (!datos) return;

            // Título período
            if (datos.periodo && datos.periodo.descripcion) {
                const el = document.getElementById('periodo-titulo');
                if (el) el.textContent = datos.periodo.descripcion;
            }

            // KPIs
            actualizarKPIs(datos.resumen);

            // Metas
            actualizarMetas(datos.metas);

            // Gráfico
            crearGraficoRendimiento(datos.datosRendimiento);

            // Tablas
            actualizarMisProductos(datos.productosVendidos);
            actualizarProductosPorAgotar(datos.productosAgotar);
            actualizarMisVentas(datos.ultimasVentas);

        } catch (error) {
            console.error('Error en actualizarDashboard:', error);
        }
    }

    // Gráfico de rendimiento (line chart)
    function crearGraficoRendimiento(datos) {
        try {
            const canvas = document.getElementById('grafico-rendimiento');
            if (!canvas) return;

            if (graficoRendimiento) graficoRendimiento.destroy();

            if (!datos || datos.length === 0) {
                Core.mostrarSinDatos('grafico-rendimiento', true);
                return;
            }

            Core.mostrarSinDatos('grafico-rendimiento', false);

            const labels = datos.map(item => Core.formatearFecha(item.fecha).split('/').slice(0, 2).join('/'));
            const values = datos.map(item => parseFloat(item.venta));

            graficoRendimiento = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Ventas',
                        data: values,
                        borderColor: colores.primary,
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        pointBackgroundColor: colores.primary,
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: colores.primary,
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
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => 'Ventas: ' + Core.formatoMoneda(context.parsed.y)
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: (value) => Core.formatoMoneda(value)
                            }
                        }
                    }
                }
            });

            // Tabla accesible
            Core.crearTablaAccesibleGrafico('grafico-rendimiento', datos,
                (item, i) => `<td>${labels[i]}</td><td>${Core.formatoMoneda(item.venta)}</td>`,
                'Rendimiento de ventas del vendedor'
            );

        } catch (error) {
            console.error('Error en crearGraficoRendimiento:', error);
        }
    }

    // Tabla: Mis productos más vendidos
    function actualizarMisProductos(datos) {
        const tabla = document.getElementById('tabla-mis-productos');
        if (!tabla) return;

        if (!datos || datos.length === 0) {
            tabla.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center py-3 text-muted">
                        <i class="fas fa-info-circle mr-1" aria-hidden="true"></i> ${t('table.sinMisProductos')}
                    </td>
                </tr>`;
            return;
        }

        let html = '';
        datos.forEach(producto => {
            html += `
            <tr>
                <td>${producto.producto}</td>
                <td>${producto.categoria}</td>
                <td class="text-right">${Core.formatoMoneda(producto.precio)}</td>
                <td class="text-right">${producto.unidades}</td>
            </tr>`;
        });
        tabla.innerHTML = html;
    }

    // Tabla: Productos por agotar
    function actualizarProductosPorAgotar(datos) {
        const tabla = document.getElementById('tabla-productos-agotar');
        if (!tabla) return;

        if (!datos || datos.length === 0) {
            tabla.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center py-3 text-muted">
                        <i class="fas fa-info-circle mr-1" aria-hidden="true"></i> ${t('table.sinAlertas')}
                    </td>
                </tr>`;
            return;
        }

        let html = '';
        datos.forEach(producto => {
            const claseBadge = producto.estado === 'crítico' ? 'badge-danger' : 'badge-warning';
            const estadoTexto = producto.estado === 'crítico' ? 'Crítico' : 'Bajo';

            html += `
            <tr>
                <td>${producto.producto}</td>
                <td>${producto.categoria}</td>
                <td class="text-center">${producto.stockActual}</td>
                <td class="text-center">
                    <span class="badge ${claseBadge}">${estadoTexto}</span>
                </td>
            </tr>`;
        });
        tabla.innerHTML = html;
    }

    // Tabla: Mis últimas ventas
    function actualizarMisVentas(datos) {
        const tabla = document.getElementById('tabla-mis-ventas');
        if (!tabla) return;

        if (!datos || datos.length === 0) {
            tabla.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-3 text-muted">
                        <i class="fas fa-info-circle mr-1" aria-hidden="true"></i> ${t('table.sinVentas')}
                    </td>
                </tr>`;
            return;
        }

        let html = '';
        datos.forEach(venta => {
            const fechaFormateada = (typeof formatDateTime === 'function')
                ? formatDateTime(venta.fecha)
                : Core.formatearFechaHora(venta.fecha);

            const montoFormateado = (typeof formatCurrency === 'function')
                ? formatCurrency(venta.total)
                : Core.formatoMoneda(venta.total);

            html += `
            <tr>
                <td>${venta.id}</td>
                <td>${venta.cliente}</td>
                <td>${fechaFormateada}</td>
                <td>${venta.productos}</td>
                <td>${venta.metodo}</td>
                <td class="text-right">${montoFormateado}</td>
            </tr>`;
        });
        tabla.innerHTML = html;
    }

    // KPIs
    function actualizarKPIs(resumen) {
        if (!resumen) return;

        const map = {
            'ventas-vendedor': Core.formatoMoneda(resumen.ventasTotales),
            'comision-vendedor': Core.formatoMoneda(resumen.comisionEstimada),
            'total-ventas': resumen.totalVentas,
            'clientes-atendidos': resumen.clientesAtendidos
        };

        Object.entries(map).forEach(([id, value]) => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = value;
        });
    }

    // Metas (progress bars)
    function actualizarMetas(metas) {
        if (!metas) return;

        const updateMeta = (prefix, meta) => {
            const valorEl = document.getElementById(`${prefix}-valor`);
            const barraEl = document.getElementById(`${prefix}-barra`);
            const detalleEl = document.getElementById(`${prefix}-detalle`);

            if (valorEl) valorEl.textContent = meta.porcentaje + '%';
            if (barraEl) barraEl.style.width = meta.porcentaje + '%';
            if (detalleEl) {
                if (prefix === 'meta-ventas') {
                    detalleEl.textContent = `${Core.formatoMoneda(meta.actual)} / ${Core.formatoMoneda(meta.meta)}`;
                } else {
                    detalleEl.textContent = `${meta.actual} / ${meta.meta}`;
                }
            }
        };

        updateMeta('meta-ventas', metas.ventas);
        updateMeta('meta-clientes', metas.clientes);
        updateMeta('meta-productos', metas.productos);
    }

    // Cargar datos por período
    function cargarDatosPeriodo(periodo) {
        periodoActual = periodo;
        cargarDatosDashboard(periodo);

        const selector = document.getElementById('selector-periodo');
        if (selector && selector.value !== periodo) {
            selector.value = periodo;
            try { $(selector).trigger('change.select2'); } catch (e) { }
        }
    }

    // Inicializar
    try {
        cargarDatosPeriodo('hoy');
    } catch (error) {
        console.error('Error al inicializar dashboard:', error);
        cargarDatosDePrueba();
    }
});