/**
 * Dashboard Administrador
 * JavaScript para la visualización de datos en el dashboard principal
 * Usa DashboardCore para lógica compartida
 */

document.addEventListener('DOMContentLoaded', function () {
    const Core = window.DashboardCore;

    // Variables de estado
    let periodoActual = 'hoy';
    let fechaDesde = null;
    let fechaHasta = null;
    let datosActuales = null;

    // Referencias a gráficos
    let graficoMetodosPago = null;
    let graficoCategorias = null;

    // IDs de elementos para loading
    const kpiSelector = '.info-box-number';
    const tableIds = ['tabla-productos-vendidos', 'tabla-productos-agotar', 'tabla-ultimas-ventas'];

    // Configurar colores de gráficos desde CSS custom properties
    const colores = Core.getChartColors();
    const t = Core.t;

    // Crear elementos "sin datos" para gráficos
    Core.crearElementosNoDatos(
        ['grafico-metodos-pago', 'grafico-categorias'],
        [
            t('chart.sinDatosMetodos'),
            t('chart.sinDatosCategorias')
        ]
    );

    // Inicializar selector de período
    const fechas = Core.inicializarSelectorPeriodo(function (periodo, fDesde, fHasta) {
        if (periodo === 'personalizado') {
            fechaDesde = fDesde;
            fechaHasta = fHasta;
        }
        cargarDatosPeriodo(periodo, fDesde, fHasta);
    });
    fechaDesde = fechas.fechaDesde;
    fechaHasta = fechas.fechaHasta;

    // Inicializar botón de impresión
    Core.inicializarBotonImprimir(function () {
        return datosActuales;
    });

    // Cargar datos del dashboard mediante AJAX
    async function cargarDatosDashboard(periodo, fechaInicio = null, fechaFin = null) {
        try {
            const claveCache = Core.generarClaveCache(periodo, fechaInicio, fechaFin);
            const cached = Core.getCachedData(claveCache);

            if (cached) {
                actualizarDashboard(cached);
                return true;
            }

            Core.mostrarCargando(true, [kpiSelector], tableIds);

            const data = await Core.cargarDatosDashboard('get_dashboard_data.php', {
                periodo: periodo,
                fecha_inicio: fechaInicio,
                fecha_fin: fechaFin,
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

    // Datos de prueba para visualización
    function cargarDatosDePrueba() {
        console.log('Cargando datos de prueba para visualización');

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
                ventasTotales: 0,
                gananciasNetas: 0,
                totalTransacciones: 0,
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
        if (!datos) {
            console.error('No hay datos para actualizar el dashboard');
            return;
        }

        // Actualizar título del período
        if (datos.periodo && datos.periodo.descripcion) {
            const periodoTitulo = document.getElementById('periodo-titulo');
            if (periodoTitulo) periodoTitulo.textContent = datos.periodo.descripcion;
        }

        // Actualizar KPIs con tendencias (accesibles: texto + color)
        Core.actualizarKPIsConTendencia(datos.kpis, datos.tendencias, {
            ventasTotales: 'ventas-totales',
            gananciasNetas: 'ganancias-netas',
            totalTransacciones: 'total-transacciones',
            ventaPromedio: 'venta-promedio'
        });

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

    // Crear gráfico de métodos de pago (pie chart)
    function crearGraficoMetodosPago(datos) {
        try {
            const ctx = document.getElementById('grafico-metodos-pago');
            if (!ctx) return;

            if (graficoMetodosPago) {
                graficoMetodosPago.destroy();
            }

            if (!datos || datos.length === 0) {
                Core.mostrarSinDatos('grafico-metodos-pago', true);
                return;
            }

            Core.mostrarSinDatos('grafico-metodos-pago', false);

            const labels = datos.map(item => item.metodo);
            const values = datos.map(item => item.porcentaje);
            const backgroundColors = labels.map(label => colores.paymentMethods[label] || colores.categorical[0]);

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
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { padding: 20, boxWidth: 12 }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    const item = datos[context.dataIndex];
                                    return `${item.metodo}: ${Core.formatoMoneda(item.monto)} (${Core.formatoPorcentaje(item.porcentaje)})`;
                                }
                            }
                        }
                    }
                }
            });

            // Tabla accesible para screen readers
            Core.crearTablaAccesibleGrafico('grafico-metodos-pago', datos,
                (item) => `<td>${item.metodo}</td><td>${Core.formatoMoneda(item.monto)}</td><td>${Core.formatoPorcentaje(item.porcentaje)}</td>`,
                'Distribución de ventas por método de pago'
            );

        } catch (error) {
            console.error('Error en crearGraficoMetodosPago:', error);
        }
    }

    // Crear gráfico de categorías (horizontal bar)
    function crearGraficoCategorias(datos) {
        try {
            const ctx = document.getElementById('grafico-categorias');
            if (!ctx) return;

            if (graficoCategorias) {
                graficoCategorias.destroy();
            }

            if (!datos || datos.length === 0) {
                Core.mostrarSinDatos('grafico-categorias', true);
                return;
            }

            Core.mostrarSinDatos('grafico-categorias', false);

            const labels = datos.map(item => item.categoria);
            const values = datos.map(item => item.ventas);
            const backgroundColors = colores.categorical.slice(0, datos.length);

            graficoCategorias = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Ventas por Categoría',
                        data: values,
                        backgroundColor: backgroundColors,
                        borderWidth: 0
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    const item = datos[context.dataIndex];
                                    return `${item.categoria}: ${Core.formatoMoneda(item.ventas)} (${Core.formatoPorcentaje(item.porcentaje)})`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    return Core.formatoMoneda(value);
                                }
                            }
                        },
                        y: { grid: { display: false } }
                    }
                }
            });

            // Tabla accesible
            Core.crearTablaAccesibleGrafico('grafico-categorias', datos,
                (item) => `<td>${item.categoria}</td><td>${Core.formatoMoneda(item.ventas)}</td><td>${Core.formatoPorcentaje(item.porcentaje)}</td>`,
                'Ventas por categoría de producto'
            );

        } catch (error) {
            console.error('Error en crearGraficoCategorias:', error);
        }
    }

    // Actualizar tabla de productos más vendidos
    function actualizarProductosMasVendidos(datos) {
        const tabla = document.getElementById('tabla-productos-vendidos');
        if (!tabla) return;

        if (!datos || datos.length === 0) {
            tabla.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-3 text-muted">
                        <i class="fas fa-info-circle mr-1" aria-hidden="true"></i> ${t('table.sinProductos')}
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
                <td class="text-right">${Core.formatoMoneda(producto.total)}</td>
            </tr>`;
        });
        tabla.innerHTML = html;
    }

    // Actualizar tabla de productos por agotar
    function actualizarProductosPorAgotar(datos) {
        const tabla = document.getElementById('tabla-productos-agotar');
        if (!tabla) return;

        if (!datos || datos.length === 0) {
            tabla.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-3 text-muted">
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
                <td class="text-center">${producto.stockMinimo}</td>
                <td class="text-center">
                    <span class="badge ${claseBadge}">${estadoTexto}</span>
                </td>
            </tr>`;
        });
        tabla.innerHTML = html;
    }

    // Actualizar tabla de últimas ventas
    function actualizarUltimasVentas(datos) {
        const tabla = document.getElementById('tabla-ultimas-ventas');
        if (!tabla) return;

        if (!datos || datos.length === 0) {
            tabla.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-3 text-muted">
                        <i class="fas fa-info-circle mr-1" aria-hidden="true"></i> ${t('table.sinVentas')}
                    </td>
                </tr>`;
            return;
        }

        let html = '';
        datos.forEach(venta => {
            // Usar funciones globales si están disponibles, sino fallback
            const fechaFormateada = (typeof formatDateTime === 'function')
                ? formatDateTime(venta.fecha)
                : Core.formatearFechaHora(venta.fecha);

            const montoFormateado = (typeof formatCurrency === 'function')
                ? formatCurrency(venta.total)
                : Core.formatoMoneda(venta.total);

            const gananciaFormateada = (typeof formatCurrency === 'function')
                ? formatCurrency(venta.ganancia)
                : Core.formatoMoneda(venta.ganancia);

            html += `
            <tr>
                <td>${venta.id}</td>
                <td>${venta.cliente}</td>
                <td>${fechaFormateada}</td>
                <td>${venta.productos}</td>
                <td>${venta.metodo}</td>
                <td class="text-right">${montoFormateado}</td>
                <td class="text-right text-success">${gananciaFormateada}</td>
            </tr>`;
        });
        tabla.innerHTML = html;
    }

    // Actualizar barras de progreso de métodos de pago
    function actualizarBarrasMetodosPago(datos) {
        const contenedor = document.getElementById('metodos-pago-barras');
        if (!contenedor) return;

        if (!datos || datos.length === 0) {
            contenedor.innerHTML = '';
            return;
        }

        let html = '';
        datos.forEach(metodo => {
            const colorClase = colores.paymentMethods[metodo.metodo] ? `bg-${Object.keys(colores.paymentMethods).find(k => colores.paymentMethods[k] === colores.paymentMethods[metodo.metodo])}` : 'bg-secondary';

            html += `
            <div class="progress-group">
                <span class="progress-text">${metodo.metodo}</span>
                <span class="float-right">${Core.formatoMoneda(metodo.monto)} (${Core.formatoPorcentaje(metodo.porcentaje)})</span>
                <div class="progress progress-sm">
                    <div class="${colorClase}" style="width: ${metodo.porcentaje}%"></div>
                </div>
            </div>`;
        });
        contenedor.innerHTML = html;
    }

    // Cargar datos según período seleccionado
    function cargarDatosPeriodo(periodo, fechaInicio = null, fechaFin = null) {
        periodoActual = periodo;

        const fechasPersonalizadas = document.getElementById('fechas-personalizadas');
        if (fechasPersonalizadas) {
            fechasPersonalizadas.classList.toggle('d-none', periodo !== 'personalizado');
        }

        if (periodo === 'personalizado' && (!fechaInicio || !fechaFin)) {
            return;
        }

        cargarDatosDashboard(periodo, fechaInicio, fechaFin);

        const selectorPeriodo = document.getElementById('selector-periodo');
        if (selectorPeriodo && selectorPeriodo.value !== periodo) {
            selectorPeriodo.value = periodo;
            try {
                $(selectorPeriodo).trigger('change.select2');
            } catch (e) {
                console.warn('Error al actualizar select2:', e);
            }
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