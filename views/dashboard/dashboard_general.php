<?php
// Este dashboard se muestra para usuarios que no tienen un rol específico
// o cuando se necesita mostrar información general del sistema

// Verificar si el usuario tiene algún permiso específico
require_once __DIR__ . '/../../services/AuthorizationService.php';
$authService = new AuthorizationService();
$currentUser = getCurrentUser();
$idusuariosesion = $currentUser['id'];
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Página Inicial</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</section>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- Bienvenida y resumen -->
        <div class="row">
            <div class="col-md-12">
                <div class="p-5 mb-4 bg-white rounded-3 shadow-sm">
                    <div class="container-fluid py-4">
                        <p class="display-5 fw-bold mb-3">Bienvenido a <?= $appName ?> - <?= $_SESSION['usuario_cargo']; ?></p>
                        <p class="col-md-8 fs-4">
                            Este sistema le permite gestionar todos los aspectos relacionados con la gestión de ventas,
                            incluyendo productos, inventario, ventas, clientes y más.
                        </p>
                        <div class="mt-4">
                            <?php if ($authService->tienePermisoNombre($idusuariosesion, 'usuarios')) : ?>
                                <a href="<?= $URL; ?>views/usuarios" class="btn btn-primary btn-lg me-2">
                                    <i class="fas fa-users"></i> Gestionar Usuarios
                                </a>
                            <?php endif; ?>

                            <?php if ($authService->tienePermisoNombre($idusuariosesion, 'productos')) : ?>
                                <a href="<?= $URL; ?>views/productos" class="btn btn-warning btn-lg me-2">
                                    <i class="fas fa-shoe-prints"></i> Gestionar Productos
                                </a>
                            <?php endif; ?>

                            <?php if ($authService->tienePermisoNombre($idusuariosesion, 'nueva_venta')) : ?>
                                <a href="<?= $URL; ?>views/ventas/nueva.php" class="btn btn-success btn-lg">
                                    <i class="fas fa-cart-plus"></i> Nueva Venta
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información del sistema -->
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-shoe-prints"></i> Estado del Inventario
                        </h3>
                    </div>
                    <div class="card-body">
                        <div id="estado-inventario" class="text-center" aria-live="polite">
                            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Cargando...
                        </div>
                        <div class="mt-3 text-center d-none" id="detalle-inventario" aria-live="polite">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="info-box bg-light">
                                        <div class="info-box-content text-center">
                                            <span class="info-box-text">Total</span>
                                            <span class="info-box-number" id="total-productos">0</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-box bg-light">
                                        <div class="info-box-content text-center">
                                            <span class="info-box-text">Categorías</span>
                                            <span class="info-box-number" id="total-categorias">0</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-box bg-light">
                                        <div class="info-box-content text-center">
                                            <span class="info-box-text">Stock Bajo</span>
                                            <span class="info-box-number text-warning" id="productos-bajo">0</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <?php if ($authService->tienePermisoNombre($idusuariosesion, 'productos')) : ?>
                                <a href="<?= $URL; ?>views/productos" class="btn btn-sm btn-info w-100">
                                    <i class="fas fa-boxes mr-2"></i> Ver Productos
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-cash-register"></i> Actividad Reciente
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <ul class="products-list product-list-in-card pl-2 pr-2" id="actividad-reciente" aria-live="polite">
                            <li class="item text-center py-3">
                                <i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Cargando actividad reciente...
                            </li>
                        </ul>
                    </div>
                    <div class="card-footer text-center">
                        <?php if ($authService->tienePermisoNombre($idusuariosesion, 'ventas')) : ?>
                            <a href="<?= $URL; ?>views/ventas" class="btn btn-sm btn-secondary">
                                <i class="fas fa-history mr-2"></i> Ver Historial
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-users"></i> Clientes Recientes
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <ul class="users-list clearfix" id="clientes-recientes" aria-live="polite">
                            <li class="text-center py-3 w-100">
                                <i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Cargando clientes recientes...
                            </li>
                        </ul>
                    </div>
                    <div class="card-footer text-center">
                        <?php if ($authService->tienePermisoNombre($idusuariosesion, 'clientes')) : ?>
                            <a href="<?= $URL; ?>views/clientes" class="btn btn-sm btn-secondary">
                                <i class="fas fa-users mr-2"></i> Ver Todos los Clientes
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list"></i> Módulos Principales</h3>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php if ($authService->tienePermisoNombre($idusuariosesion, 'productos')) : ?>
                                <a href="<?= $URL; ?>views/productos" class="list-group-item list-group-item-action">
                                    <i class="fas fa-shoe-prints mr-2"></i> Gestión de Productos
                                </a>
                            <?php endif; ?>

                            <?php if ($authService->tienePermisoNombre($idusuariosesion, 'categorias')) : ?>
                                <a href="<?= $URL; ?>views/categorias" class="list-group-item list-group-item-action">
                                    <i class="fas fa-tags mr-2"></i> Categorías
                                </a>
                            <?php endif; ?>

                            <?php if ($authService->tienePermisoNombre($idusuariosesion, 'ventas')) : ?>
                                <a href="<?= $URL; ?>views/ventas" class="list-group-item list-group-item-action">
                                    <i class="fas fa-cash-register mr-2"></i> Ventas
                                </a>
                            <?php endif; ?>

                            <?php if ($authService->tienePermisoNombre($idusuariosesion, 'clientes')) : ?>
                                <a href="<?= $URL; ?>views/clientes" class="list-group-item list-group-item-action">
                                    <i class="fas fa-users mr-2"></i> Clientes
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-cogs"></i> Administración</h3>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php if ($authService->tienePermisoNombre($idusuariosesion, 'usuarios')) : ?>
                                <a href="<?= $URL; ?>views/usuarios" class="list-group-item list-group-item-action">
                                    <i class="fas fa-user-cog mr-2"></i> Gestión de Usuarios
                                </a>
                            <?php endif; ?>

                            <?php if ($authService->tienePermisoNombre($idusuariosesion, 'empresa')) : ?>
                                <a href="<?= $URL; ?>views/empresa" class="list-group-item list-group-item-action">
                                    <i class="fas fa-building mr-2"></i> Configuración de Empresa
                                </a>
                            <?php endif; ?>

                            <?php if ($authService->tienePermisoNombre($idusuariosesion, 'sucursales')) : ?>
                                <a href="<?= $URL; ?>views/sucursales" class="list-group-item list-group-item-action">
                                    <i class="fas fa-store-alt mr-2"></i> Sucursales
                                </a>
                            <?php endif; ?>

                            <?php if ($authService->tienePermisoNombre($idusuariosesion, 'permisos')) : ?>
                                <a href="<?= $URL; ?>views/permisos" class="list-group-item list-group-item-action">
                                    <i class="fas fa-key mr-2"></i> Permisos del Sistema
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /.container-fluid -->
</section>
<!-- /.content -->

<script>
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
</script>