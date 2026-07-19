/**
 * Dashboard Supervisor
 * JavaScript para la visualización de datos en el dashboard de supervisor
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
    let graficoVendedores = null;
    let graficoTendencia = null;
    let graficoCategorias = null;
    let graficoConversion = null;

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
            // Mostrar indicadores en KPIs
            const kpis = document.querySelectorAll('.info-box-number');
            kpis.forEach(kpi => {
                if (!kpi.dataset.originalText && !kpi.querySelector('.fa-spinner')) {
                    kpi.dataset.originalText = kpi.innerHTML;
                    kpi.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                }
            });

            // Añadir indicadores a las tablas
            const tablas = ['lista-vendedores', 'lista-categorias', 'tabla-ranking', 'tabla-alertas', 'leyenda-conversion'];
            tablas.forEach(id => {
                const elemento = document.getElementById(id);
                if (elemento && !elemento.querySelector('.fa-spinner')) {
                    elemento.innerHTML = `
                        <li class="nav-item text-center py-3">
                            <i class="fas fa-spinner fa-spin mr-2"></i> Actualizando datos...
                        </li>
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
    async function cargarDatosDashboard(periodo, fechaInicio = null, fechaFin = null) {
        try {
            // Generar clave de caché
            const claveCache = generarClaveCache(periodo, fechaInicio, fechaFin);

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
            let url = baseUrl + '/controllers/dashboard/get_supervisor_dashboard_data.php';

            // Si la URL no funciona, intentar con rutas relativas
            if (!url.startsWith('http')) {
                url = 'controllers/dashboard/get_supervisor_dashboard_data.php';
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
        console.log("Cargando datos de prueba para visualización");

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
                    {
                        etiqueta: 'Nuevos',
                        valor: 0,
                        color: '#28a745'
                    },
                    {
                        etiqueta: 'Recurrentes',
                        valor: 0,
                        color: '#17a2b8'
                    }
                ]
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

            // Actualizar gráficos (verificando que existan los elementos)
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

            // Actualizar tablas
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
            console.error("Error en actualizarDashboard:", error);
        }
    }

    /**
     * Crea el gráfico de rendimiento por vendedor con manejo adecuado para casos sin datos
     */
    function crearGraficoVendedores(datos) {
        try {
            const canvas = document.getElementById('grafico-vendedores');

            // Verificar si el elemento existe
            if (!canvas) {
                console.error('Elemento grafico-vendedores no encontrado en el DOM');
                return;
            }

            const ctx = canvas.getContext('2d');

            // Destruir gráfico existente si lo hay
            if (graficoVendedores) {
                try {
                    graficoVendedores.destroy();
                } catch (e) {
                    console.warn("Error al destruir gráfico existente:", e);
                }
            }

            // Verificar si hay datos
            if (!datos || datos.length === 0) {
                // Intentar mostrar mensaje de "sin datos" si existe el elemento
                const sinDatosVendedores = document.getElementById('sin-datos-vendedores');
                if (sinDatosVendedores) {
                    canvas.style.display = 'none';
                    sinDatosVendedores.style.display = 'block';
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
            const sinDatosVendedores = document.getElementById('sin-datos-vendedores');
            if (sinDatosVendedores) {
                sinDatosVendedores.style.display = 'none';
            }

            // Preparar datos
            const labels = datos.map(item => item.nombre);
            const ventas = datos.map(item => parseFloat(item.montoVentas));
            const coloresBarras = [
                colores.azul,
                colores.verde,
                colores.amarillo,
                colores.cian,
                colores.naranja
            ];

            graficoVendedores = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Ventas',
                        data: ventas,
                        backgroundColor: coloresBarras,
                        borderWidth: 0
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
                                    return 'Bs. ' + value.toLocaleString();
                                }
                            }
                        }]
                    }
                }
            });

            // Actualizar lista de vendedores
            actualizarListaVendedores(datos);
        } catch (error) {
            console.error("Error en crearGraficoVendedores:", error);
        }
    }

    /**
     * Actualiza la lista de vendedores
     */
    function actualizarListaVendedores(datos) {
        try {
            const listaVendedores = document.getElementById('lista-vendedores');

            if (!listaVendedores) {
                console.error('Elemento lista-vendedores no encontrado en el DOM');
                return;
            }

            if (!datos || datos.length === 0) {
                listaVendedores.innerHTML = `
                <li class="nav-item text-center py-3 text-muted">
                    <i class="fas fa-info-circle mr-1"></i> No hay datos de vendedores para este período
                </li>
            `;
                return;
            }

            let html = '';
            datos.slice(0, 5).forEach((vendedor, index) => {
                const porcentaje = (vendedor.montoVentas / datos[0].montoVentas) * 100;
                const claseBadge = index === 0 ? 'badge-success' : 'badge-info';

                html += `
                <li class="nav-item p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge ${claseBadge} mr-2">${index + 1}</span>
                            <span>${vendedor.nombre}</span>
                        </div>
                        <span class="text-muted">${formatoMoneda(vendedor.montoVentas)}</span>
                    </div>
                    <div class="progress mt-2">
                        <div class="progress-bar bg-primary" style="width: ${porcentaje}%"></div>
                    </div>
                </li>
            `;
            });

            listaVendedores.innerHTML = html;
        } catch (error) {
            console.error("Error en actualizarListaVendedores:", error);
        }
    }

    /**
     * Crea el gráfico de tendencia de ventas con manejo adecuado para casos sin datos
     */
    function crearGraficoTendencia(datos) {
        try {
            const canvas = document.getElementById('grafico-tendencia');

            // Verificar si el elemento existe
            if (!canvas) {
                console.error('Elemento grafico-tendencia no encontrado en el DOM');
                return;
            }

            const ctx = canvas.getContext('2d');

            // Destruir gráfico existente si lo hay
            if (graficoTendencia) {
                try {
                    graficoTendencia.destroy();
                } catch (e) {
                    console.warn("Error al destruir gráfico existente:", e);
                }
            }

            // Verificar si hay datos
            if (!datos || !datos.periodoActual || datos.periodoActual.length === 0) {
                // Intentar mostrar mensaje de "sin datos" si existe el elemento
                const sinDatosTendencia = document.getElementById('sin-datos-tendencia');
                if (sinDatosTendencia) {
                    canvas.style.display = 'none';
                    sinDatosTendencia.style.display = 'block';
                } else {
                    // Si no existe el elemento para mostrar mensaje, dibujarlo en el canvas
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.font = '14px Arial';
                    ctx.textAlign = 'center';
                    ctx.fillStyle = '#6c757d';
                    ctx.fillText('No hay datos disponibles', canvas.width / 2, canvas.height / 2);
                }

                // También actualizar información de comparación a valores cero
                const cambioPorcentajeEl = document.getElementById('cambio-porcentaje');
                const periodoAnteriorEl = document.getElementById('periodo-anterior');
                const periodoActualEl = document.getElementById('periodo-actual');

                if (cambioPorcentajeEl) cambioPorcentajeEl.textContent = '0.0%';
                if (periodoAnteriorEl) periodoAnteriorEl.textContent = formatoMoneda(0);
                if (periodoActualEl) periodoActualEl.textContent = formatoMoneda(0);

                return;
            }

            // Si hay datos, asegurar que el canvas esté visible y el mensaje oculto
            canvas.style.display = 'block';
            const sinDatosTendencia = document.getElementById('sin-datos-tendencia');
            if (sinDatosTendencia) {
                sinDatosTendencia.style.display = 'none';
            }

            // Preparar datos con formato de fecha correcto
            const fechas = datos.periodoActual.map(item => {
                // Usar la fecha del item pero asegurarse de que se muestra correctamente
                const fecha = new Date(item.fecha + 'T00:00:00');
                // Formatear la fecha manualmente para asegurar consistencia
                const dia = fecha.getDate().toString().padStart(2, '0');
                const mes = (fecha.getMonth() + 1).toString().padStart(2, '0');
                return `${dia}/${mes}`;
            });

            // Datasets
            const datasets = [
                {
                    label: 'Período Actual',
                    data: datos.periodoActual.map(item => parseFloat(item.venta)),
                    borderColor: colores.azul,
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    pointBackgroundColor: colores.azul,
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: colores.azul,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    fill: true,
                    tension: 0.1,
                    borderWidth: 2
                }
            ];

            // Añadir período anterior si hay datos
            if (datos.periodoAnterior && datos.periodoAnterior.length > 0) {
                datasets.push({
                    label: 'Período Anterior',
                    data: datos.periodoAnterior.map(item => parseFloat(item.venta)),
                    borderColor: colores.gris,
                    backgroundColor: 'transparent',
                    pointBackgroundColor: colores.gris,
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: colores.gris,
                    pointRadius: 2,
                    pointHoverRadius: 4,
                    fill: false,
                    borderWidth: 2,
                    borderDash: [5, 5]
                });
            }

            graficoTendencia = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: fechas,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltips: {
                        callbacks: {
                            label: function (tooltipItem, data) {
                                return data.datasets[tooltipItem.datasetIndex].label + ': ' +
                                    formatoMoneda(tooltipItem.yLabel);
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
                                    return 'Bs. ' + value.toLocaleString();
                                }
                            }
                        }]
                    }
                }
            });

            // Actualizar información de comparación
            const cambioPorcentajeEl = document.getElementById('cambio-porcentaje');
            const periodoAnteriorEl = document.getElementById('periodo-anterior');
            const periodoActualEl = document.getElementById('periodo-actual');

            // Verificar si los elementos existen
            if (cambioPorcentajeEl && periodoAnteriorEl && periodoActualEl) {
                // Asignar los valores
                cambioPorcentajeEl.textContent = (datos.cambioPorcentual >= 0 ? '+' : '') + formatoPorcentaje(datos.cambioPorcentual);
                cambioPorcentajeEl.classList.remove('text-success', 'text-danger');
                cambioPorcentajeEl.classList.add(datos.cambioPorcentual >= 0 ? 'text-success' : 'text-danger');

                periodoAnteriorEl.textContent = formatoMoneda(datos.totalAnterior);
                periodoActualEl.textContent = formatoMoneda(datos.totalActual);
            }
        } catch (error) {
            console.error("Error en crearGraficoTendencia:", error);
        }
    }

    /**
     * Crea el gráfico de categorías con manejo adecuado para casos sin datos
     */
    function crearGraficoCategorias(datos) {
        try {
            const canvas = document.getElementById('grafico-categorias');

            // Verificar si el elemento existe
            if (!canvas) {
                console.error('Elemento grafico-categorias no encontrado en el DOM');
                return;
            }

            const ctx = canvas.getContext('2d');

            // Destruir gráfico existente si lo hay
            if (graficoCategorias) {
                try {
                    graficoCategorias.destroy();
                } catch (e) {
                    console.warn("Error al destruir gráfico existente:", e);
                }
            }

            // Verificar si hay datos
            if (!datos || datos.length === 0) {
                // Intentar mostrar mensaje de "sin datos" si existe el elemento
                const sinDatosCategorias = document.getElementById('sin-datos-categorias');
                if (sinDatosCategorias) {
                    canvas.style.display = 'none';
                    sinDatosCategorias.style.display = 'block';
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
            const sinDatosCategorias = document.getElementById('sin-datos-categorias');
            if (sinDatosCategorias) {
                sinDatosCategorias.style.display = 'none';
            }

            // Preparar datos
            const labels = datos.map(item => item.categoria);
            const values = datos.map(item => parseFloat(item.ventas));
            const backgroundColors = [
                colores.verde,
                colores.azul,
                colores.amarillo,
                colores.cian,
                colores.naranja,
                colores.morado,
                colores.rosa
            ];

            // Asegurar que hay suficientes colores
            while (backgroundColors.length < datos.length) {
                backgroundColors.push(...backgroundColors);
            }

            graficoCategorias = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: backgroundColors.slice(0, datos.length),
                        borderWidth: 0
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
                            label: function (tooltipItem, data) {
                                const index = tooltipItem.index;
                                const categoria = datos[index];
                                return `${categoria.categoria}: ${formatoMoneda(categoria.ventas)} (${formatoPorcentaje(categoria.porcentaje)})`;
                            }
                        }
                    },
                    cutoutPercentage: 60
                }
            });
        } catch (error) {
            console.error("Error en crearGraficoCategorias:", error);
        }
    }

    /**
 * Crea el gráfico de conversión de clientes con manejo adecuado para casos sin datos
 */
    function crearGraficoConversion(datos) {
        try {
            const canvas = document.getElementById('grafico-conversion');

            // Verificar si el elemento existe
            if (!canvas) {
                console.error('Elemento grafico-conversion no encontrado en el DOM');
                return;
            }

            const ctx = canvas.getContext('2d');

            // Destruir gráfico existente si lo hay
            if (graficoConversion) {
                try {
                    graficoConversion.destroy();
                } catch (e) {
                    console.warn("Error al destruir gráfico existente:", e);
                }
            }

            // Verificar si hay datos
            if (!datos || !datos.distribucion || datos.distribucion.length === 0) {
                // Intentar mostrar mensaje de "sin datos" si existe el elemento
                const sinDatosConversion = document.getElementById('sin-datos-conversion');
                if (sinDatosConversion) {
                    canvas.style.display = 'none';
                    sinDatosConversion.style.display = 'block';
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
            const sinDatosConversion = document.getElementById('sin-datos-conversion');
            if (sinDatosConversion) {
                sinDatosConversion.style.display = 'none';
            }

            // Preparar datos
            const labels = datos.distribucion.map(item => item.etiqueta);
            const values = datos.distribucion.map(item => item.valor);
            const colorList = datos.distribucion.map(item => item.color);

            graficoConversion = new Chart(ctx, {
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
                    legend: {
                        display: false
                    },
                    tooltips: {
                        callbacks: {
                            label: function (tooltipItem, data) {
                                const index = tooltipItem.index;
                                const valor = values[index];
                                const total = values.reduce((a, b) => a + b, 0);
                                const porcentaje = total > 0 ? (valor / total) * 100 : 0;
                                return `${labels[index]}: ${valor} (${porcentaje.toFixed(1)}%)`;
                            }
                        }
                    }
                }
            });

            // Actualizar leyenda
            actualizarLeyendaConversion(datos.distribucion);
        } catch (error) {
            console.error("Error en crearGraficoConversion:", error);
        }
    }

    /**
     * Actualiza la leyenda del gráfico de conversión
     */
    function actualizarLeyendaConversion(distribucion) {
        try {
            const leyendaConversion = document.getElementById('leyenda-conversion');
            if (!leyendaConversion) return;

            if (!distribucion || distribucion.length === 0) {
                leyendaConversion.innerHTML = '<li class="text-center">No hay datos disponibles</li>';
                return;
            }

            let leyendaHtml = '';
            distribucion.forEach(item => {
                leyendaHtml += `
                <li>
                    <span style="background-color: ${item.color}"></span>
                    ${item.etiqueta}: ${item.valor}
                </li>
            `;
            });

            leyendaConversion.innerHTML = leyendaHtml;
        } catch (error) {
            console.error("Error en actualizarLeyendaConversion:", error);
        }
    }

    /**
     * Actualiza la tabla de ranking de vendedores
     */
    function actualizarRankingVendedores(datos) {
        try {
            const tablaRanking = document.getElementById('tabla-ranking');

            if (!tablaRanking) {
                console.error('Elemento tabla-ranking no encontrado en el DOM');
                return;
            }

            if (!datos || datos.length === 0) {
                tablaRanking.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-3 text-muted">
                            <i class="fas fa-info-circle mr-1"></i> No hay datos de vendedores para el período seleccionado
                        </td>
                    </tr>
                `;
                return;
            }

            let html = '';
            datos.forEach((vendedor, index) => {
                // Simular progreso hacia una meta (ajustar según las metas reales)
                const metaVendedor = 10000; // Meta simulada
                const progresoMeta = Math.min(100, Math.round((vendedor.montoVentas / metaVendedor) * 100));
                let claseProgreso = 'bg-success';

                if (progresoMeta < 30) {
                    claseProgreso = 'bg-danger';
                } else if (progresoMeta < 70) {
                    claseProgreso = 'bg-warning';
                }

                html += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${vendedor.nombre}</td>
                    <td>${formatoMoneda(vendedor.montoVentas)}</td>
                    <td>${vendedor.clientes}</td>
                    <td>
                        <div class="progress progress-xs">
                            <div class="progress-bar ${claseProgreso}" style="width: ${progresoMeta}%"></div>
                        </div>
                        <span class="badge ${claseProgreso === 'bg-success' ? 'bg-success' : claseProgreso === 'bg-warning' ? 'bg-warning' : 'bg-danger'}">${progresoMeta}%</span>
                    </td>
                </tr>
            `;
            });

            tablaRanking.innerHTML = html;
        } catch (error) {
            console.error("Error en actualizarRankingVendedores:", error);
        }
    }

    /**
     * Actualiza la lista de categorías
     */
    function actualizarListaCategorias(datos) {
        try {
            const listaCategorias = document.getElementById('lista-categorias');

            if (!listaCategorias) {
                console.error('Elemento lista-categorias no encontrado en el DOM');
                return;
            }

            if (!datos || datos.length === 0) {
                listaCategorias.innerHTML = `
                    <li class="nav-item text-center py-3 text-muted">
                        <i class="fas fa-info-circle mr-1"></i> No hay datos de categorías para este período
                    </li>
                `;
                return;
            }

            const coloresCategorias = [
                colores.verde,
                colores.azul,
                colores.amarillo,
                colores.cian,
                colores.naranja,
                colores.morado,
                colores.rosa
            ];

            let html = '';
            datos.forEach((categoria, index) => {
                const color = coloresCategorias[index % coloresCategorias.length];

                // Estilo más compacto, similar al de métodos de pago
                html += `
                <li class="nav-item">
                    <div class="progress-group">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="progress-text">
                                <span class="badge mr-1" style="background-color: ${color}">&nbsp;</span>
                                ${categoria.categoria}
                            </span>
                            <span class="float-right text-muted">${formatoMoneda(categoria.ventas)} (${formatoPorcentaje(categoria.porcentaje)})</span>
                        </div>
                        <div class="progress progress-sm">
                            <div class="progress-bar" style="width: ${categoria.porcentaje}%; background-color: ${color}"></div>
                        </div>
                    </div>
                </li>
            `;
            });

            listaCategorias.innerHTML = html;
        } catch (error) {
            console.error("Error en actualizarListaCategorias:", error);
        }
    }

    /**
     * Actualiza la tabla de alertas de inventario
     */
    function actualizarAlertasInventario(datos) {
        try {
            const tablaAlertas = document.getElementById('tabla-alertas');

            if (!tablaAlertas) {
                console.error('Elemento tabla-alertas no encontrado en el DOM');
                return;
            }

            if (!datos || datos.length === 0) {
                tablaAlertas.innerHTML = `
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
                    <td class="text-center">
                        <span class="badge ${claseBadge}">${producto.estado === 'crítico' ? 'Crítico' : 'Bajo'}</span>
                    </td>
                    <td class="text-center">
                        <a href="#" class="btn btn-sm btn-primary">Comprar</a>
                    </td>
                </tr>
            `;
            });

            tablaAlertas.innerHTML = html;
        } catch (error) {
            console.error("Error en actualizarAlertasInventario:", error);
        }
    }

    /**
     * Actualiza las estadísticas de conversión de clientes
     */
    function actualizarEstadisticasConversion(datos) {
        try {
            if (!datos) return;

            const clientesNuevosEl = document.getElementById('clientes-nuevos');
            const clientesRecurrentesEl = document.getElementById('clientes-recurrentes');
            const tasaRetencionEl = document.getElementById('tasa-retencion');
            const statsConversionEl = document.getElementById('stats-conversion');

            // Si no existe el contenedor principal, salir
            if (!statsConversionEl) {
                console.warn("Elemento stats-conversion no encontrado");
                return;
            }

            // Verificar si hay datos de conversión
            if (!datos.clientesNuevos && !datos.clientesRecurrentes) {
                // Mostrar mensaje de no hay datos
                statsConversionEl.innerHTML = `
                    <div class="text-center py-3 text-muted">
                        <i class="fas fa-info-circle mr-1"></i> No hay datos de conversión para el período seleccionado
                    </div>
                `;
                return;
            }

            // Actualizar valores individuales si existen los elementos
            if (clientesNuevosEl) {
                clientesNuevosEl.textContent = datos.clientesNuevos;
            }

            if (clientesRecurrentesEl) {
                clientesRecurrentesEl.textContent = datos.clientesRecurrentes;
            }

            if (tasaRetencionEl) {
                tasaRetencionEl.textContent = formatoPorcentaje(datos.tasaRetencion);
            }
        } catch (error) {
            console.error("Error en actualizarEstadisticasConversion:", error);
        }
    }

    /**
     * Actualiza los KPIs principales del dashboard
     */
    function actualizarKPIs(resumen) {
        try {
            // Verificar si hay datos
            if (!resumen) return;

            // Ventas del equipo
            const ventasEquipoEl = document.getElementById('ventas-equipo');
            if (ventasEquipoEl) {
                ventasEquipoEl.innerHTML = formatoMoneda(resumen.ventasEquipo);
            }

            // Cumplimiento de meta
            const metaEquipoEl = document.getElementById('meta-equipo');
            if (metaEquipoEl) {
                metaEquipoEl.innerHTML = formatoPorcentaje(resumen.cumplimientoMeta);
            }

            // Vendedores activos
            const vendedoresActivosEl = document.getElementById('vendedores-activos');
            if (vendedoresActivosEl) {
                vendedoresActivosEl.innerHTML = resumen.vendedoresActivos;
            }

            // Ticket promedio
            const ticketPromedioEl = document.getElementById('ticket-promedio');
            if (ticketPromedioEl) {
                ticketPromedioEl.innerHTML = formatoMoneda(resumen.ticketPromedio);
            }
        } catch (error) {
            console.error("Error en actualizarKPIs:", error);
        }
    }

    // Cargar datos según período seleccionado
    function cargarDatosPeriodo(periodo, fechaInicio = null, fechaFin = null) {
        periodoActual = periodo;

        // Ocultar o mostrar el selector de fechas según corresponda
        const fechasPersonalizadas = document.getElementById('fechas-personalizadas');
        if (fechasPersonalizadas) {
            fechasPersonalizadas.classList.toggle('d-none', periodo !== 'personalizado');
        }

        // Si es personalizado pero no hay fechas seleccionadas, no cargar datos aún
        if (periodo === 'personalizado' && (!fechaInicio || !fechaFin)) {
            return;
        }

        // Cargar datos del servidor
        cargarDatosDashboard(periodo, fechaInicio, fechaFin);

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
            // Para el gráfico de vendedores
            if (!document.getElementById('sin-datos-vendedores')) {
                const contenedorVendedores = document.getElementById('grafico-vendedores');
                if (contenedorVendedores && contenedorVendedores.parentNode) {
                    const sinDatosVendedores = document.createElement('div');
                    sinDatosVendedores.id = 'sin-datos-vendedores';
                    sinDatosVendedores.className = 'text-center text-muted py-4';
                    sinDatosVendedores.innerHTML = '<p><i class="fas fa-info-circle mr-1"></i> No hay datos de rendimiento de vendedores para el período seleccionado</p>';
                    sinDatosVendedores.style.display = 'none';
                    contenedorVendedores.parentNode.appendChild(sinDatosVendedores);
                }
            }

            // Para el gráfico de tendencia
            if (!document.getElementById('sin-datos-tendencia')) {
                const contenedorTendencia = document.getElementById('grafico-tendencia');
                if (contenedorTendencia && contenedorTendencia.parentNode) {
                    const sinDatosTendencia = document.createElement('div');
                    sinDatosTendencia.id = 'sin-datos-tendencia';
                    sinDatosTendencia.className = 'text-center text-muted py-4';
                    sinDatosTendencia.innerHTML = '<p><i class="fas fa-info-circle mr-1"></i> No hay datos de tendencia para el período seleccionado</p>';
                    sinDatosTendencia.style.display = 'none';
                    contenedorTendencia.parentNode.appendChild(sinDatosTendencia);
                }
            }

            // Para el gráfico de categorías
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

            // Para el gráfico de conversión
            if (!document.getElementById('sin-datos-conversion')) {
                const contenedorConversion = document.getElementById('grafico-conversion');
                if (contenedorConversion && contenedorConversion.parentNode) {
                    const sinDatosConversion = document.createElement('div');
                    sinDatosConversion.id = 'sin-datos-conversion';
                    sinDatosConversion.className = 'text-center text-muted py-4';
                    sinDatosConversion.innerHTML = '<p><i class="fas fa-info-circle mr-1"></i> No hay datos de conversión para el período seleccionado</p>';
                    sinDatosConversion.style.display = 'none';
                    contenedorConversion.parentNode.appendChild(sinDatosConversion);
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
                        fechasPersonalizadas.classList.remove('d-none');
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
                    cargarDatosPeriodo('personalizado', fechaDesde, fechaHasta);
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