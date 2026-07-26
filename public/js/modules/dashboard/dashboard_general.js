document.addEventListener('DOMContentLoaded', function() {
    function formatoFecha(fechaIso) {
        return new Date(fechaIso.replace(' ', 'T')).toLocaleDateString('es-BO', {
            day: '2-digit',
            month: 'short'
        });
    }

    function formatoMoneda(valor) {
        const currency = (window.APP && window.APP.currency) ? window.APP.currency : 'Bs';
        return currency + ' ' + parseFloat(valor).toFixed(2);
    }

    function esc(valor) {
        const div = document.createElement('div');
        div.textContent = valor ?? '';
        return div.innerHTML;
    }

    // Función para cargar los datos reales del dashboard vía AJAX
    async function cargarDatosDashboard() {
        try {
            const response = await fetch(baseUrl + 'controllers/dashboard/get_general_dashboard_data.php', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'No se pudieron cargar los datos del dashboard');
            }

            // Estado del inventario
            if (data.inventario) {
                document.getElementById('estado-inventario').classList.add('d-none');
                document.getElementById('detalle-inventario').classList.remove('d-none');
                document.getElementById('total-productos').textContent = data.inventario.totalProductos;
                document.getElementById('total-categorias').textContent = data.inventario.totalCategorias;
                document.getElementById('productos-bajo').textContent = data.inventario.stockBajo;
            } else {
                document.getElementById('estado-inventario').textContent = 'Sin acceso a esta información';
            }

            // Actividad reciente
            const actividadReciente = document.getElementById('actividad-reciente');
            if (data.actividadReciente && data.actividadReciente.length > 0) {
                actividadReciente.innerHTML = data.actividadReciente.map(venta => `
                    <li class="item">
                        <div class="product-info">
                            <a href="javascript:void(0)" class="product-title">Venta #${venta.id}
                                <span class="badge badge-success float-right">${formatoMoneda(venta.total)}</span>
                            </a>
                            <span class="product-description">${formatoFecha(venta.fecha)}</span>
                        </div>
                    </li>
                `).join('');
            } else if (data.actividadReciente) {
                actividadReciente.innerHTML = '<li class="item text-center py-3">Sin actividad reciente</li>';
            } else {
                actividadReciente.innerHTML = '<li class="item text-center py-3">Sin acceso a esta información</li>';
            }

            // Clientes recientes
            const clientesRecientes = document.getElementById('clientes-recientes');
            if (data.clientesRecientes && data.clientesRecientes.length > 0) {
                clientesRecientes.innerHTML = data.clientesRecientes.map(cliente => `
                    <li>
                        <img src="${baseUrl}public/img/user_default.jpg" alt="">
                        <a class="users-list-name" href="#">${esc(cliente.nombre)}</a>
                        <span class="users-list-date">${formatoFecha(cliente.fecha)}</span>
                    </li>
                `).join('');
            } else if (data.clientesRecientes) {
                clientesRecientes.innerHTML = '<li class="text-center py-3 w-100">Sin clientes recientes</li>';
            } else {
                clientesRecientes.innerHTML = '<li class="text-center py-3 w-100">Sin acceso a esta información</li>';
            }
        } catch (error) {
            console.error('Error al cargar datos del dashboard:', error);
            document.getElementById('estado-inventario').textContent = 'Error al cargar los datos';
            document.getElementById('actividad-reciente').innerHTML = '<li class="item text-center py-3">Error al cargar los datos</li>';
            document.getElementById('clientes-recientes').innerHTML = '<li class="text-center py-3 w-100">Error al cargar los datos</li>';
        }
    }

    cargarDatosDashboard();
});
