/**
 * Dashboard Supervisor
 * JavaScript para la visualización de datos en el dashboard de supervisor
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
    let graficoVendedores = null;
    let graficoTendencia = null;
    let graficoCategorias = null;
    let graficoConversion = null;

    // IDs para loading
    const kpiSelector = '.info-box-number';
    const listIds = ['lista-vendedores', 'lista-categorias', 'tabla-ranking', 'tabla-alertas', 'leyenda-conversion'];

    // Colores
    const colores = Core.getChartColors();
    const t = Core.t;

    // Crear elementos "sin datos"
    Core.crearElementosNoDatos(
        ['grafico-vendedores', 'grafico-tendencia', 'grafico-categorias', 'grafico-conversion'],
        [
            t('chart.sinDatosVendedores'),
            t('chart.sinDatosTendencia'),
            t('chart.sinDatosCategorias'),
            t('chart.sinDatosConversion')
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

    // Cargar datos del dashboard
    async function cargarDatosDashboard(periodo, fechaInicio = null, fechaFin = null) {
        try {
            const claveCache = Core.generarClaveCache(periodo, fechaInicio, fechaFin);
            const cached = Core.getCachedData(claveCache);

            if (cached) {
                actualizarDashboard(cached);
                return true;
            }

            Core.mostrarCargando(true, [kpiSelector], listIds);

            const data = await Core.cargarDatosDashboard('get_supervisor_dashboard_data.php', {
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

    // Datos de prueba
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
            resumen: {
                ventasEquipo: 0,
                cumplimientoMeta: 0,
                vendedoresActivos: 0,
                ticketPromedio: 0
            },
            rendimientoVendedores: [],
            tendencia: {
                periodoActual: [],
                periodoAnterior: [],
                totalActual: 0,
                totalAnterior: 0,
                cambioPorcentual: 0
            },
            ventasPorCategoria: [],
            productosAgotar: [],
            conversion: {
                clientesNuevos: 0,
                clientesRecurrentes: 0,
                totalClientes: 0,
                tasaRetencion: 0,
                distribucion: [
                    { etiqueta: 'Nuevos', valor: 0, color: colores.success },
                    { etiqueta: 'Recurrentes', valor: 0, color: colores.info }
                ]
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

            // KPIs (sin tendencias en supervisor, solo valores)
            actualizarKPIs(datos.resumen);

            // Gráficos
            if (document.getElementById('grafico-vendedores')) {
                crearGraficoVendedores(datos.rendimientoVendedores);
            }
            if (document.getElementById('grafico-tendencia')) {
                crearGraficoTendencia(datos.tendencia);
            }
            if (document.getElementById('grafico-categorias')) {
                crearGraficoCategorias(datos.ventasPorCategoria);
            }
            if (document.getElementById('grafico-conversion')) {
                crearGraficoConversion(datos.conversion);
            }

            // Tablas/Lista
            if (document.getElementById('tabla-ranking')) {
                actualizarRankingVendedores(datos.rendimientoVendedores);
            }
            if (document.getElementById('lista-categorias')) {
                actualizarListaCategorias(datos.ventasPorCategoria);
            }
            if (document.getElementById('tabla-alertas')) {
                actualizarAlertasInventario(datos.productosAgotar);
            }
            if (document.getElementById('stats-conversion')) {
                actualizarEstadisticasConversion(datos.conversion);
            }

        } catch (error) {
            console.error('Error en actualizarDashboard:', error);
        }
    }

    // Gráfico de vendedores (bar)
    function crearGraficoVendedores(datos) {
        try {
            const canvas = document.getElementById('grafico-vendedores');
            if (!canvas) return;

            if (graficoVendedores) graficoVendedores.destroy();

            if (!datos || datos.length === 0) {
                Core.mostrarSinDatos('grafico-vendedores', true);
                return;
            }

            Core.mostrarSinDatos('grafico-vendedores', false);

            const labels = datos.map(item => item.nombre);
            const values = datos.map(item => parseFloat(item.montoVentas));
            const backgroundColors = colores.categorical.slice(0, datos.length);

            graficoVendedores = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Ventas',
                        data: values,
                        backgroundColor: backgroundColors,
                        borderWidth: 0
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
            Core.crearTablaAccesibleGrafico('grafico-vendedores', datos,
                (item) => `<td>${item.nombre}</td><td>${Core.formatoMoneda(item.montoVentas)}</td>`,
                'Rendimiento de ventas por vendedor'
            );

            actualizarListaVendedores(datos);

        } catch (error) {
            console.error('Error en crearGraficoVendedores:', error);
        }
    }

    // Lista de vendedores (card footer)
    function actualizarListaVendedores(datos) {
        const contenedor = document.getElementById('lista-vendedores');
        if (!contenedor) return;

        if (!datos || datos.length === 0) {
            contenedor.innerHTML = `
                <li class="nav-item text-center py-3 text-muted">
                    <i class="fas fa-info-circle mr-1" aria-hidden="true"></i> ${t('table.sinVendedores')}
                </li>`;
            return;
        }

        let html = '';
        const maxVentas = datos[0]?.montoVentas || 1;
        datos.slice(0, 5).forEach((vendedor, index) => {
            const porcentaje = (vendedor.montoVentas / maxVentas) * 100;
            const claseBadge = index === 0 ? 'badge-success' : 'badge-info';

            html += `
            <li class="nav-item p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge ${claseBadge} mr-2">${index + 1}</span>
                        <span>${vendedor.nombre}</span>
                    </div>
                    <span class="text-muted">${Core.formatoMoneda(vendedor.montoVentas)}</span>
                </div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-primary" style="width: ${porcentaje}%"></div>
                </div>
            </li>`;
        });
        contenedor.innerHTML = html;
    }

    // Gráfico de tendencia (line)
    function crearGraficoTendencia(datos) {
        try {
            const canvas = document.getElementById('grafico-tendencia');
            if (!canvas) return;

            if (graficoTendencia) graficoTendencia.destroy();

            if (!datos || !datos.periodoActual || datos.periodoActual.length === 0) {
                Core.mostrarSinDatos('grafico-tendencia', true);

                // Reset comparison values
                const cambioEl = document.getElementById('cambio-porcentaje');
                const antEl = document.getElementById('periodo-anterior');
                const actEl = document.getElementById('periodo-actual');
                if (cambioEl) cambioEl.textContent = '0.0%';
                if (antEl) antEl.textContent = Core.formatoMoneda(0);
                if (actEl) actEl.textContent = Core.formatoMoneda(0);
                return;
            }

            Core.mostrarSinDatos('grafico-tendencia', false);

            const labels = datos.periodoActual.map(item => {
                const fecha = new Date(item.fecha + 'T00:00:00');
                const dia = fecha.getDate().toString().padStart(2, '0');
                const mes = (fecha.getMonth() + 1).toString().padStart(2, '0');
                return `${dia}/${mes}`;
            });

            const datasets = [{
                label: 'Período Actual',
                data: datos.periodoActual.map(item => parseFloat(item.venta)),
                borderColor: colores.primary,
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                pointBackgroundColor: colores.primary,
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: colores.primary,
                pointRadius: 3,
                pointHoverRadius: 5,
                fill: true,
                tension: 0.1,
                borderWidth: 2
            }];

            if (datos.periodoAnterior && datos.periodoAnterior.length > 0) {
                datasets.push({
                    label: 'Período Anterior',
                    data: datos.periodoAnterior.map(item => parseFloat(item.venta)),
                    borderColor: colores.secondary,
                    backgroundColor: 'transparent',
                    pointBackgroundColor: colores.secondary,
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: colores.secondary,
                    pointRadius: 2,
                    pointHoverRadius: 4,
                    fill: false,
                    borderWidth: 2,
                    borderDash: [5, 5]
                });
            }

            graficoTendencia = new Chart(canvas, {
                type: 'line',
                data: { labels, datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: true, position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: (context) => context.dataset.label + ': ' + Core.formatoMoneda(context.parsed.y)
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: {
                            beginAtZero: true,
                            ticks: { callback: (value) => Core.formatoMoneda(value) }
                        }
                    }
                }
            });

            // Actualizar comparación
            const cambioEl = document.getElementById('cambio-porcentaje');
            const antEl = document.getElementById('periodo-anterior');
            const actEl = document.getElementById('periodo-actual');

            if (cambioEl && antEl && actEl) {
                const cambio = datos.cambioPorcentual || 0;
                cambioEl.textContent = (cambio >= 0 ? '+' : '') + Core.formatoPorcentaje(cambio);
                cambioEl.classList.remove('text-success', 'text-danger');
                cambioEl.classList.add(cambio >= 0 ? 'text-success' : 'text-danger');
                antEl.textContent = Core.formatoMoneda(datos.totalAnterior || 0);
                actEl.textContent = Core.formatoMoneda(datos.totalActual || 0);
            }

            // Tabla accesible
            Core.crearTablaAccesibleGrafico('grafico-tendencia', datos.periodoActual,
                (item, i) => `<td>${labels[i]}</td><td>${Core.formatoMoneda(item.venta)}</td>`,
                'Tendencia de ventas del equipo'
            );

        } catch (error) {
            console.error('Error en crearGraficoTendencia:', error);
        }
    }

    // Gráfico de categorías (doughnut)
    function crearGraficoCategorias(datos) {
        try {
            const canvas = document.getElementById('grafico-categorias');
            if (!canvas) return;

            if (graficoCategorias) graficoCategorias.destroy();

            if (!datos || datos.length === 0) {
                Core.mostrarSinDatos('grafico-categorias', true);
                return;
            }

            Core.mostrarSinDatos('grafico-categorias', false);

            const labels = datos.map(item => item.categoria);
            const values = datos.map(item => parseFloat(item.ventas));
            const backgroundColors = colores.categorical.slice(0, datos.length);

            graficoCategorias = new Chart(canvas, {
                type: 'doughnut',
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
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const item = datos[context.dataIndex];
                                    return `${item.categoria}: ${Core.formatoMoneda(item.ventas)} (${Core.formatoPorcentaje(item.porcentaje)})`;
                                }
                            }
                        }
                    },
                    cutout: '60%'
                }
            });

            // Tabla accesible
            Core.crearTablaAccesibleGrafico('grafico-categorias', datos,
                (item) => `<td>${item.categoria}</td><td>${Core.formatoMoneda(item.ventas)}</td><td>${Core.formatoPorcentaje(item.porcentaje)}</td>`,
                'Ventas del equipo por categoría de producto'
            );

        } catch (error) {
            console.error('Error en crearGraficoCategorias:', error);
        }
    }

    // Gráfico de conversión (pie)
    function crearGraficoConversion(datos) {
        try {
            const canvas = document.getElementById('grafico-conversion');
            if (!canvas) return;

            if (graficoConversion) graficoConversion.destroy();

            if (!datos || !datos.distribucion || datos.distribucion.length === 0) {
                Core.mostrarSinDatos('grafico-conversion', true);
                return;
            }

            Core.mostrarSinDatos('grafico-conversion', false);

            const labels = datos.distribucion.map(item => item.etiqueta);
            const values = datos.distribucion.map(item => item.valor);
            const colorList = datos.distribucion.map(item => item.color);

            graficoConversion = new Chart(canvas, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colorList,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const total = values.reduce((a, b) => a + b, 0);
                                    const pct = total > 0 ? (values[context.dataIndex] / total) * 100 : 0;
                                    return `${labels[context.dataIndex]}: ${values[context.dataIndex]} (${pct.toFixed(1)}%)`;
                                }
                            }
                        }
                    }
                }
            });

            actualizarLeyendaConversion(datos.distribucion);

            // Tabla accesible
            Core.crearTablaAccesibleGrafico('grafico-conversion', datos.distribucion,
                (item) => `<td>${item.etiqueta}</td><td>${item.valor}</td>`,
                'Distribución de conversión de clientes'
            );

        } catch (error) {
            console.error('Error en crearGraficoConversion:', error);
        }
    }

    // Leyenda de conversión
    function actualizarLeyendaConversion(distribucion) {
        const contenedor = document.getElementById('leyenda-conversion');
        if (!contenedor) return;

        if (!distribucion || distribucion.length === 0) {
            contenedor.innerHTML = `<li class="text-center">${t('table.sinLeyenda')}</li>`;
            return;
        }

        let html = '';
        distribucion.forEach(item => {
            html += `
            <li>
                <span style="background-color: ${item.color}; display: inline-block; width: 12px; height: 12px; border-radius: 50%; margin-right: 8px;"></span>
                ${item.etiqueta}: ${item.valor}
            </li>`;
        });
        contenedor.innerHTML = html;
    }

    // Ranking de vendedores (table)
    function actualizarRankingVendedores(datos) {
        const tabla = document.getElementById('tabla-ranking');
        if (!tabla) return;

        if (!datos || datos.length === 0) {
            tabla.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-3 text-muted">
                        <i class="fas fa-info-circle mr-1" aria-hidden="true"></i> ${t('table.sinVendedores')}
                    </td>
                </tr>`;
            return;
        }

        let html = '';
        datos.forEach((vendedor, index) => {
            const metaVendedor = 10000;
            const progresoMeta = Math.min(100, Math.round((vendedor.montoVentas / metaVendedor) * 100));
            let claseProgreso = 'bg-success';
            let claseBadge = 'bg-success';

            if (progresoMeta < 30) {
                claseProgreso = 'bg-danger';
                claseBadge = 'bg-danger';
            } else if (progresoMeta < 70) {
                claseProgreso = 'bg-warning';
                claseBadge = 'bg-warning';
            }

            html += `
            <tr>
                <td>${index + 1}</td>
                <td>${vendedor.nombre}</td>
                <td>${Core.formatoMoneda(vendedor.montoVentas)}</td>
                <td>${vendedor.clientes}</td>
                <td>
                    <div class="progress progress-xs">
                        <div class="progress-bar ${claseProgreso}" style="width: ${progresoMeta}%"></div>
                    </div>
                    <span class="badge ${claseBadge}">${progresoMeta}%</span>
                </td>
            </tr>`;
        });
        tabla.innerHTML = html;
    }

    // Lista de categorías (progress bars)
    function actualizarListaCategorias(datos) {
        const contenedor = document.getElementById('lista-categorias');
        if (!contenedor) return;

        if (!datos || datos.length === 0) {
            contenedor.innerHTML = `
                <li class="nav-item text-center py-3 text-muted">
                    <i class="fas fa-info-circle mr-1" aria-hidden="true"></i> No hay datos de categorías para este período
                </li>`;
            return;
        }

        let html = '';
        datos.forEach((categoria, index) => {
            const color = colores.categorical[index % colores.categorical.length];

            html += `
            <li class="nav-item">
                <div class="progress-group">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="progress-text">
                            <span class="badge mr-1" style="background-color: ${color}">&nbsp;</span>
                            ${categoria.categoria}
                        </span>
                        <span class="float-right text-muted">${Core.formatoMoneda(categoria.ventas)} (${Core.formatoPorcentaje(categoria.porcentaje)})</span>
                    </div>
                    <div class="progress progress-sm">
                        <div class="progress-bar" style="width: ${categoria.porcentaje}%; background-color: ${color}"></div>
                    </div>
                </div>
            </li>`;
        });
        contenedor.innerHTML = html;
    }

    // Alertas de inventario
    function actualizarAlertasInventario(datos) {
        const tabla = document.getElementById('tabla-alertas');
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
                <td class="text-center">
                    <span class="badge ${claseBadge}">${estadoTexto}</span>
                </td>
                <td class="text-center">
                    <a href="#" class="btn btn-sm btn-primary">Comprar</a>
                </td>
            </tr>`;
        });
        tabla.innerHTML = html;
    }

    // Estadísticas de conversión
    function actualizarEstadisticasConversion(datos) {
        if (!datos) return;

        const contenedor = document.getElementById('stats-conversion');
        if (!contenedor) return;

        if (!datos.clientesNuevos && !datos.clientesRecurrentes) {
            contenedor.innerHTML = `
                <div class="text-center py-3 text-muted">
                    <i class="fas fa-info-circle mr-1" aria-hidden="true"></i> ${t('table.sinConversion')}
                </div>`;
            return;
        }

        const nuevosEl = document.getElementById('clientes-nuevos');
        const recurrentesEl = document.getElementById('clientes-recurrentes');
        const retencionEl = document.getElementById('tasa-retencion');

        if (nuevosEl) nuevosEl.textContent = datos.clientesNuevos || 0;
        if (recurrentesEl) recurrentesEl.textContent = datos.clientesRecurrentes || 0;
        if (retencionEl) retencionEl.textContent = Core.formatoPorcentaje(datos.tasaRetencion || 0);
    }

    // KPIs
    function actualizarKPIs(resumen) {
        if (!resumen) return;

        const map = {
            'ventas-equipo': Core.formatoMoneda(resumen.ventasEquipo),
            'meta-equipo': Core.formatoPorcentaje(resumen.cumplimientoMeta),
            'vendedores-activos': resumen.vendedoresActivos,
            'ticket-promedio': Core.formatoMoneda(resumen.ticketPromedio)
        };

        Object.entries(map).forEach(([id, value]) => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = value;
        });
    }

    // Cargar datos por período
    function cargarDatosPeriodo(periodo, fechaInicio = null, fechaFin = null) {
        periodoActual = periodo;

        const fechasPersonalizadas = document.getElementById('fechas-personalizadas');
        if (fechasPersonalizadas) {
            fechasPersonalizadas.classList.toggle('d-none', periodo !== 'personalizado');
        }

        if (periodo === 'personalizado' && (!fechaInicio || !fechaFin)) return;

        cargarDatosDashboard(periodo, fechaInicio, fechaFin);

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