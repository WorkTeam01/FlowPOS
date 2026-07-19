<?php
// Incluir el controlador del dashboard para supervisor
require_once __DIR__ . '/../../controllers/dashboard/DashboardSupervisorController.php';
$dashboardController = new DashboardSupervisorController();

// Definir scripts específicos para este módulo
$module_scripts = ['dashboard/dashboard_supervisor'];
?>

<!-- ChartJS (solo necesario en este dashboard) -->
<script src="<?= $URL; ?>public/js/plugins/chart/Chart.js"></script>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1 class="m-0">Dashboard de Supervisor</h1>
                <h5 id="periodo-titulo" class="text-muted">Hoy</h5>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= $URL; ?>"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active">Dashboard Supervisor</li>
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
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPIs principales - Fila 1 -->
        <div class="row">
            <!-- Ventas totales del equipo -->
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-shopping-bag"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Ventas del Equipo</span>
                        <span class="info-box-number" id="ventas-equipo" aria-live="polite">
                            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Meta del equipo -->
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box shadow-sm bg-light-success">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-chart-line"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Cumplimiento Meta</span>
                        <span class="info-box-number" id="meta-equipo" aria-live="polite">
                            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Número de vendedores activos -->
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Vendedores Activos</span>
                        <span class="info-box-number" id="vendedores-activos" aria-live="polite">
                            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Ticket promedio -->
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-shopping-cart"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Venta Promedio</span>
                        <span class="info-box-number" id="ticket-promedio" aria-live="polite">
                            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rendimiento por vendedor y Tendencia de ventas -->
        <div class="row">
            <!-- Rendimiento por vendedor (60%) -->
            <div class="col-md-7">
                <div class="card shadow-sm">
                    <div class="card-header border-0">
                        <h3 class="card-title">
                            <i class="fas fa-user-chart mr-1"></i>
                            Rendimiento por Vendedor
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer o expandir panel">
                                <i class="fas fa-minus" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart">
                            <canvas id="grafico-vendedores" height="300" role="img" aria-label="Gráfico de rendimiento de ventas por vendedor del equipo"></canvas>
                        </div>
                    </div>
                    <div class="card-footer bg-white p-0">
                        <ul class="nav nav-pills flex-column" id="lista-vendedores">
                            <li class="nav-item text-center py-3">
                                <i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Cargando datos...
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Tendencia de ventas (40%) -->
            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-header border-0">
                        <h3 class="card-title">
                            <i class="fas fa-chart-area mr-1"></i>
                            Tendencia de Ventas
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer o expandir panel">
                                <i class="fas fa-minus" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart">
                            <canvas id="grafico-tendencia" height="250" role="img" aria-label="Gráfico de tendencia de ventas del equipo"></canvas>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="row">
                            <div class="col-sm-4 text-center">
                                <div class="description-block border-right">
                                    <h5 class="description-header text-success" id="cambio-porcentaje">0%</h5>
                                    <span class="description-text">CAMBIO PORCENTUAL</span>
                                </div>
                            </div>
                            <div class="col-sm-4 text-center">
                                <div class="description-block border-right">
                                    <h5 class="description-header" id="periodo-anterior">$0</h5>
                                    <span class="description-text">PERÍODO ANTERIOR</span>
                                </div>
                            </div>
                            <div class="col-sm-4 text-center">
                                <div class="description-block">
                                    <h5 class="description-header" id="periodo-actual">$0</h5>
                                    <span class="description-text">PERÍODO ACTUAL</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ranking de vendedores y Métricas por categoría -->
        <div class="row">
            <!-- Ranking de vendedores (50%) -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header border-0">
                        <h3 class="card-title">
                            <i class="fas fa-trophy mr-1"></i>
                            Ranking de Vendedores
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
                                        <th style="width: 10px">#</th>
                                        <th>Vendedor</th>
                                        <th>Ventas</th>
                                        <th>Clientes</th>
                                        <th style="width: 40px">Meta</th>
                                    </tr>
                                </thead>
                                <tbody id="tabla-ranking">
                                    <tr>
                                        <td colspan="5" class="text-center">
                                            <i class="fas fa-spinner fa-spin mr-1" aria-hidden="true"></i> Cargando datos...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Métricas por categoría (50%) -->
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
                        <canvas id="grafico-categorias" height="240" role="img" aria-label="Gráfico de ventas del equipo por categoría de producto"></canvas>
                    </div>
                    <div class="card-footer bg-white p-0">
                        <ul class="nav nav-pills flex-column" id="lista-categorias">
                            <li class="nav-item text-center py-3">
                                <i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Cargando datos...
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alertas de stock y conversión de clientes -->
        <div class="row">
            <!-- Alertas de stock (50%) -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header border-0">
                        <h3 class="card-title">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            Alertas de Inventario
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
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tabla-alertas">
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
                            Solicitar Compra
                        </a>
                    </div>
                </div>
            </div>

            <!-- Conversión de clientes (50%) -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header border-0">
                        <h3 class="card-title">
                            <i class="fas fa-user-check mr-1"></i>
                            Conversión de Clientes
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Contraer o expandir panel">
                                <i class="fas fa-minus" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="chart-responsive">
                                    <canvas id="grafico-conversion" height="200" role="img" aria-label="Gráfico de tasa de conversión de clientes"></canvas>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <ul class="chart-legend clearfix" id="leyenda-conversion">
                                    <li><i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Cargando...</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white p-0">
                        <ul class="nav nav-pills flex-column" id="stats-conversion">
                            <li class="nav-item">
                                <div class="nav-link">
                                    Clientes nuevos <span class="float-right text-success" id="clientes-nuevos">
                                        <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                                    </span>
                                </div>
                            </li>
                            <li class="nav-item">
                                <div class="nav-link">
                                    Clientes recurrentes <span class="float-right text-info" id="clientes-recurrentes">
                                        <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                                    </span>
                                </div>
                            </li>
                            <li class="nav-item">
                                <div class="nav-link">
                                    Tasa de retención <span class="float-right text-warning" id="tasa-retencion">
                                        <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                                    </span>
                                </div>
                            </li>
                        </ul>
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
                        <div class="row">
                            <div class="col-md-3 col-sm-6">
                                <a href="<?= $URL; ?>views/ventas" class="btn btn-primary btn-block mb-3">
                                    <i class="fas fa-chart-line mr-2"></i> Reporte de Ventas
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <a href="<?= $URL; ?>views/usuarios" class="btn btn-info btn-block mb-3">
                                    <i class="fas fa-users-cog mr-2"></i> Gestionar Vendedores
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <a href="<?= $URL; ?>views/productos" class="btn btn-warning btn-block mb-3">
                                    <i class="fas fa-boxes mr-2"></i> Gestionar Inventario
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <a href="<?= $URL; ?>views/compras" class="btn btn-success btn-block mb-3">
                                    <i class="fas fa-truck-loading mr-2"></i> Solicitar Compras
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
<style>
    /* Estilos específicos para dashboard de supervisor */
    .chart-legend {
        list-style: none;
        margin-top: 5px;
        padding-left: 0;
    }

    .chart-legend li {
        display: block;
        padding: 5px 0;
        position: relative;
        padding-left: 25px;
    }

    .chart-legend li span {
        display: block;
        position: absolute;
        left: 0;
        top: 5px;
        width: 15px;
        height: 15px;
        border-radius: 50%;
    }

    #lista-categorias .nav-item {
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        padding: 10px 0;
    }

    #lista-categorias .nav-item:last-child {
        border-bottom: none;
    }

    /* Estilos para las listas de categorías en formato compacto */
    #lista-categorias .progress-group {
        margin-bottom: 5px;
    }

    #lista-categorias .progress {
        height: 4px;
        margin-bottom: 4px;
    }

    #lista-categorias .progress-text {
        font-size: 0.9rem;
        margin-bottom: 2px;
    }
</style>