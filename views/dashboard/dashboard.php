<?php
// Incluir el controlador del dashboard
require_once __DIR__ . '/../../controllers/dashboard/DashboardController.php';
$dashboardController = new DashboardController();

// Definir scripts específicos para este módulo
$module_scripts = ['dashboard/dashboard'];
?>

<!-- ChartJS (solo necesario en este dashboard) -->
<script src="<?= $URL; ?>public/js/plugins/chart/Chart.js"></script>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1 class="m-0">Dashboard - <?= $appName ?></h1>
                <h5 id="periodo-titulo" class="text-muted">Hoy</h5>
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
        <!-- Filtros de período y exportación -->
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-body p-3">
                        <!-- Estructura responsive para filtros -->
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
                                            <option value="anio">Este año</option>
                                            <option value="personalizado">Personalizado</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Fechas personalizadas (inicialmente oculto) -->
                            <div id="fechas-personalizadas" class="col-md-6 col-sm-12 mb-2 mb-md-0 d-none">
                                <div class="row">
                                    <div class="col-sm-5 mb-2 mb-sm-0">
                                        <div class="input-group input-group-sm">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Desde</span>
                                            </div>
                                            <input type="date" id="fecha-desde" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-sm-5 mb-2 mb-sm-0">
                                        <div class="input-group input-group-sm">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Hasta</span>
                                            </div>
                                            <input type="date" id="fecha-hasta" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <button id="btn-aplicar-fechas" class="btn btn-sm btn-primary w-100">
                                            <i class="fas fa-check d-sm-none"></i>
                                            <span class="d-none d-sm-inline">Aplicar</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Botones de exportación -->
                            <div class="col-md-2 col-sm-6 ml-auto text-right no-print">
                                <button class="btn btn-sm btn-outline-secondary mr-1" id="btn-imprimir">
                                    <i class="fas fa-print"></i>
                                    <span class="d-none d-sm-inline ml-1">Imprimir</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPIs principales - Fila 1 -->
        <div class="row">
            <!-- Ventas Totales -->
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-shopping-bag"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Ventas Totales</span>
                        <span class="info-box-number" id="ventas-totales" aria-live="polite">
                            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Ganancias Netas (KPI PRINCIPAL) -->
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box shadow-sm bg-light-success">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-chart-line"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Ganancias Netas</span>
                        <span class="info-box-number" id="ganancias-netas" aria-live="polite">
                            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Número de Transacciones -->
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-receipt"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Transacciones</span>
                        <span class="info-box-number" id="total-transacciones" aria-live="polite">
                            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Venta Promedio -->
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-shopping-cart"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Venta Promedio</span>
                        <span class="info-box-number" id="venta-promedio" aria-live="polite">
                            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Productos más vendidos y métodos de pago -->
        <div class="row">
            <!-- Productos más vendidos (70% del ancho) -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header border-0">
                        <h3 class="card-title">
                            <i class="fas fa-crown mr-1"></i>
                            Productos Más Vendidos
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
                                        <th class="text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="tabla-productos-vendidos">
                                    <tr>
                                        <td colspan="5" class="text-center">
                                            <i class="fas fa-spinner fa-spin mr-1" aria-hidden="true"></i> Cargando datos...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer clearfix">
                        <a href="<?= $URL; ?>views/productos" class="btn btn-sm btn-info float-right">
                            Ver todos los productos
                        </a>
                    </div>
                </div>
            </div>

            <!-- Métodos de Pago (30% del ancho) -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header border-0">
                        <h3 class="card-title">
                            <i class="fas fa-chart-pie mr-1"></i>
                            Métodos de Pago
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer o expandir panel">
                                <i class="fas fa-minus" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="grafico-metodos-pago" height="220" role="img" aria-label="Gráfico de distribución de ventas por método de pago"></canvas>

                        <!-- Detalles de distribución por método de pago -->
                        <div class="mt-4" id="metodos-pago-barras">
                            <div class="text-center">
                                <i class="fas fa-spinner fa-spin mr-1" aria-hidden="true"></i> Cargando datos...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Productos por agotar y ventas por categoría -->
        <div class="row">
            <!-- Productos por agotar -->
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
                                        <th class="text-center text-nowrap">Stock Actual</th>
                                        <th class="text-center text-nowrap">Stock Mínimo</th>
                                        <th class="text-center">Estado</th>
                                    </tr>
                                </thead>
                                <tbody id="tabla-productos-agotar">
                                    <tr>
                                        <td colspan="5" class="text-center">
                                            <i class="fas fa-spinner fa-spin mr-1" aria-hidden="true"></i> Cargando datos...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer clearfix">
                        <a href="<?= $URL; ?>views/compras/nueva.php" class="btn btn-sm btn-danger float-right">
                            Realizar compra
                        </a>
                    </div>
                </div>
            </div>

            <!-- Ventas por categoría -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header border-0">
                        <h3 class="card-title">
                            <i class="fas fa-chart-pie mr-1"></i>
                            Ventas por Categoría
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer o expandir panel">
                                <i class="fas fa-minus" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="grafico-categorias" height="380" role="img" aria-label="Gráfico de ventas por categoría de producto"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Últimas ventas -->
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header border-0">
                        <h3 class="card-title">
                            <i class="fas fa-shopping-cart mr-1"></i>
                            Últimas Ventas
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
                                        <th class="text-right">Ganancia</th>
                                    </tr>
                                </thead>
                                <tbody id="tabla-ultimas-ventas">
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <i class="fas fa-spinner fa-spin mr-1" aria-hidden="true"></i> Cargando datos...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer clearfix">
                        <a href="<?= $URL; ?>views/ventas" class="btn btn-sm btn-primary float-right">
                            Ver todas las ventas
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<link rel="stylesheet" href="<?= $URL; ?>public/css/modules/dashboard/dashboard.css">