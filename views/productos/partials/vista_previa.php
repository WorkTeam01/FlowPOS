<?php
/**
 * Card "Vista Previa" del formulario de producto (crear/editar).
 * Espera definidas antes del include: $vistaPreviaImagen, $vistaPreviaNombre,
 * $vistaPreviaCategoria, $vistaPreviaCodigo, $vistaPreviaPrecio, $vistaPreviaStockLabel,
 * $vistaPreviaStock, $vistaPreviaEstado (HTML del badge de estado).
 */
?>
<div class="card card-outline card-success mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-eye mr-1"></i> Vista Previa</h3>
    </div>
    <div class="card-body">
        <div class="text-center mb-3">
            <img id="preview-vista-imagen" src="<?= $vistaPreviaImagen; ?>" alt="Vista previa del producto" class="img-fluid rounded" style="max-height: 150px;">
        </div>
        <h5 class="text-center mb-3" id="preview-vista-nombre"><?= $vistaPreviaNombre; ?></h5>
        <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><strong>Categoría:</strong></span>
                <span id="preview-vista-categoria" class="text-muted"><?= $vistaPreviaCategoria; ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><strong>Código:</strong></span>
                <span id="preview-vista-codigo" class="text-muted"><?= $vistaPreviaCodigo; ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><strong>Precio Venta:</strong></span>
                <span id="preview-vista-precio" class="text-muted"><?= $vistaPreviaPrecio; ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><strong><?= $vistaPreviaStockLabel; ?>:</strong></span>
                <span id="preview-vista-stock" class="text-muted"><?= $vistaPreviaStock; ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><strong>Estado:</strong></span>
                <span id="preview-vista-estado"><?= $vistaPreviaEstado; ?></span>
            </li>
        </ul>
    </div>
</div>
