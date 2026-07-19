<?php
// Incluir el controlador del dashboard para vendedor
require_once __DIR__ . '/../../controllers/dashboard/DashboardVendedorController.php';
$dashboardController = new DashboardVendedorController();

// Definir scripts específicos para este módulo
$module_scripts = ['dashboard/dashboard_vendedor'];
?>

<!-- ChartJS (solo necesario en este dashboard) -->
<script src="<?= $URL; ?>public/js/plugins/chart/Chart.js"></script>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1 class="m-0">Dashboard de Vendedor</h1>
                <h5 id="periodo-titulo" class="text-muted">Hoy</h5>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Dashboard Vendedor</li>
                </ol>
            </div>
        </div>
    </div>
</section>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- Filtros de período -->
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-body p-3">
                        <div class="row">
                            <!-- Selector de período -->
                            <div class="col-md-4 col-sm-6 mb-2 mb-md-0">
                                <div class="d-flex align-items-center">
                                    <label for="selector-periodo" class="mr-2 mb-0 d-none d-sm-block">Período:</label>
                                    <div class="flex-grow-1">
                                        <select id="selector-periodo" class="form-control select2">
                                            <option value="hoy">Hoy</option>
                                            <option value="semana">Esta semana</option>
                                            <option value="mes">Este mes</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPIs principales - Fila 1 -->
        <div class="row">
            <!-- Ventas del vendedor -->
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-shopping-bag"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Mis Ventas</span>
                        <span class="info-box-number" id="ventas-vendedor" aria-live="polite">
                            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Comisión estimada -->
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box shadow-sm bg-light-success">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-money-bill"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Comisión Estimada</span>
                        <span class="info-box-number" id="comision-vendedor" aria-live="polite">
                            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Número de ventas realizadas -->
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-receipt"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Ventas Realizadas</span>
                        <span class="info-box-number" id="total-ventas" aria-live="polite">
                            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Clientes atendidos -->
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Clientes Atendidos</span>
                        <span class="info-box-number" id="clientes-atendidos" aria-live="polite">
                            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mis Ventas y Metas -->
        <div class="row">
            <!-- Gráfico de rendimiento (70%) -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header border-0">
                        <h3 class="card-title">
                            <i class="fas fa-chart-line mr-1"></i>
                            Mi Rendimiento de Ventas
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer o expandir panel">
                                <i class="fas fa-minus" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="grafico-rendimiento" height="250" role="img" aria-label="Gráfico de mi rendimiento de ventas en el período seleccionado"></canvas>
                    </div>
                </div>
            </div>

            <!-- Progreso hacia metas (30%) -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header border-0">
                        <h3 class="card-title">
                            <i class="fas fa-bullseye mr-1"></i>
                            Progreso hacia Metas
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer o expandir panel">
                                <i class="fas fa-minus" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="metas-contenedor">
                            <!-- Meta de ventas -->
                            <div class="progress-group mb-4">
                                <span class="progress-text">Meta de ventas mensual</span>
                                <span class="float-right" id="meta-ventas-valor">0%</span>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-primary" id="meta-ventas-barra" style="width: 0%"></div>
                                </div>
                                <small class="text-muted" id="meta-ventas-detalle">$0 / $0</small>
                            </div>

                            <!-- Meta de clientes nuevos -->
                            <div class="progress-group mb-4">
                                <span class="progress-text">Clientes nuevos</span>
                                <span class="float-right" id="meta-clientes-valor">0%</span>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-success" id="meta-clientes-barra" style="width: 0%"></div>
                                </div>
                                <small class="text-muted" id="meta-clientes-detalle">0 / 0</small>
                            </div>

                            <!-- Meta de productos vendidos -->
                            <div class="progress-group mb-4">
                                <span class="progress-text">Productos vendidos</span>
                                <span class="float-right" id="meta-productos-valor">0%</span>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-warning" id="meta-productos-barra" style="width: 0%"></div>
                                </div>
                                <small class="text-muted" id="meta-productos-detalle">0 / 0</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mis productos más vendidos y alertas de stock -->
        <div class="row">
            <!-- Productos más vendidos por el vendedor -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header border-0">
                        <h3 class="card-title">
                            <i class="fas fa-crown mr-1"></i>
                            Mis Productos Más Vendidos
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer o expandir panel">
                                <i class="fas fa-minus" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Categoría</th>
                                        <th class="text-right">Precio</th>
                                        <th class="text-right">Unidades</th>
                                    </tr>
                                </thead>
                                <tbody id="tabla-mis-productos">
                                    <tr>
                                        <td colspan="4" class="text-center">
                                            <i class="fas fa-spinner fa-spin mr-1" aria-hidden="true"></i> Cargando datos...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alertas de productos por agotar -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header border-0">
                        <h3 class="card-title">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            Productos por Agotar
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer o expandir panel">
                                <i class="fas fa-minus" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Categoría</th>
                                        <th class="text-center">Stock</th>
                                        <th class="text-center">Estado</th>
                                    </tr>
                                </thead>
                                <tbody id="tabla-productos-agotar">
                                    <tr>
                                        <td colspan="4" class="text-center">
                                            <i class="fas fa-spinner fa-spin mr-1" aria-hidden="true"></i> Cargando datos...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer clearfix">
                        <a href="<?= $URL; ?>views/productos" class="btn btn-sm btn-primary float-right">
                            Ver catálogo completo
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mis últimas ventas -->
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header border-0">
                        <h3 class="card-title">
                            <i class="fas fa-shopping-cart mr-1"></i>
                            Mis Últimas Ventas
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer o expandir panel">
                                <i class="fas fa-minus" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Cliente</th>
                                        <th>Fecha</th>
                                        <th>Productos</th>
                                        <th>Método</th>
                                        <th class="text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="tabla-mis-ventas">
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <i class="fas fa-spinner fa-spin mr-1" aria-hidden="true"></i> Cargando datos...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Action Buttons -->
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title">Acciones Rápidas</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-4 col-sm-6">
                                <a href="<?= $URL; ?>views/ventas/nueva.php" class="btn btn-success btn-block">
                                    <i class="fas fa-cart-plus mr-2"></i> Nueva Venta
                                </a>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <a href="<?= $URL; ?>views/clientes" class="btn btn-info btn-block">
                                    <i class="fas fa-user-plus mr-2"></i> Gestionar Clientes
                                </a>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <a href="<?= $URL; ?>views/productos" class="btn btn-warning btn-block">
                                    <i class="fas fa-search mr-2"></i> Buscar Productos
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<link rel="stylesheet" href="<?= $URL; ?>public/css/modules/dashboard/dashboard.css">