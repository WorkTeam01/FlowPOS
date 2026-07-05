<?php

/** @noinspection HtmlDeprecatedAttribute */
// Verificar headers
global $URL;
if (headers_sent()) {
    die("Los headers ya fueron enviados. Verifica que no haya salida de contenido antes de este script.");
}

// Improved mobile detection function
function isMobile(): bool
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $mobileKeywords = [
        'Android',
        'webOS',
        'iPhone',
        'iPad',
        'iPod',
        'BlackBerry',
        'Windows Phone'
    ];

    foreach ($mobileKeywords as $keyword) {
        if (stripos($userAgent, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

// Función para formatear moneda
function formatearMoneda($valor): string
{
    return number_format($valor, 2, ',', '.');
}

// Función para formatear fechas
function formatearFecha($fecha): string
{
    return date('d/m/Y H:i', strtotime($fecha));
}

try {
    // Include required files con manejo de errores
    if (!file_exists('../../libs/TCPDF-main/tcpdf.php')) {
        die("Error: No se encontró TCPDF en la ruta especificada");
    }
    require_once('../../libs/TCPDF-main/tcpdf.php');

    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../views/layouts/session.php';
    require_once __DIR__ . '/../../services/AuthorizationService.php';
    require_once __DIR__ . '/../../controllers/ventas/VentaController.php';

    // Verificar si existe el archivo literal.php
    if (file_exists(__DIR__ . '/../../services/literal.php')) {
        require_once __DIR__ . '/../../services/literal.php';
    } else {
        // Función de respaldo para convertir números a letras
        function numeroletras($numero)
        {
            return "(" . number_format($numero, 2) . " BOLIVIANOS)";
        }
    }

    // Verificar sesión
    if (!isset($_SESSION['usuario_id'])) {
        die("Error: Sesión no iniciada");
    }

    // Verificar permisos
    $idusuario = $_SESSION['usuario_id'];
    $authService = new AuthorizationService();

    if (!($authService->tienePermisoNombre($idusuario, 'ventas'))) {
        die("Error: No tiene permisos para generar este recibo");
    }

    // Set the current date and time
    $fecha_actual = date('d/m/Y');
    $hora_actual = date('H:i');

    // Get request parameters
    if (!isset($_GET['id'])) {
        die("Error: ID de venta requerido para generar el recibo");
    }

    $id_venta = (int)$_GET['id'];

    if ($id_venta <= 0) {
        die("Error: ID de venta inválido");
    }

    // Instanciar controlador de venta
    $ventaController = new VentaController();

    // Obtener datos de la venta
    $venta = $ventaController->ver($id_venta);

    if (!$venta) {
        die("Error: No se encontró la venta especificada");
    }

    // Validar estado de la venta
    if ($venta['estado'] != 1) {
        http_response_code(409);
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Comprobante no disponible</title>
            <style>
                body { font-family: Arial, sans-serif; background: #f4f6f9; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
                .aviso { text-align: center; background: #fff; padding: 2.5rem 3rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,.1); }
                .aviso i { font-size: 3rem; color: #dc3545; }
                .aviso h1 { font-size: 1.25rem; margin: 1rem 0 .5rem; color: #333; }
                .aviso p { color: #666; margin-bottom: 1.5rem; }
                .aviso button { background: #6c757d; color: #fff; border: none; padding: .5rem 1.5rem; border-radius: 4px; cursor: pointer; }
                .aviso button:hover { background: #5a6268; }
            </style>
        </head>
        <body>
            <div class="aviso">
                <i>&#9888;</i>
                <h1>Comprobante no disponible</h1>
                <p>Esta venta fue anulada, por lo que no es posible generar su comprobante.</p>
                <button onclick="window.close()">Cerrar</button>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    // Extraer datos principales con validación
    $cliente = $venta['cliente_nombre'] ?? 'Cliente no disponible';
    $documento_cliente = !empty($venta['numdocumento_cliente']) ? $venta['numdocumento_cliente'] : 'S/N';

    $monto_total = $venta['totalventa'] ?? 0;

    // Crear arreglo de pagos
    $pagos = $venta['pagos'] ?? [];
    $es_pago_mixto = count($pagos) > 1;

    $estado = $venta['estado'] == 1 ? 'ACTIVA' : 'ANULADA';
    $observaciones = $venta['observacion'] ?? '';

    // Extraer detalles de productos
    $detalles = $venta['detalles'] ?? [];

    // Datos de la empresa
    $empresa = [
        'nombre' => $venta['empresa']['nombre'] ?? 'MI EMPRESA',
        'nit' => $venta['empresa']['nit'] ?? '000000000',
        'direccion' => $venta['empresa']['direccion'] ?? 'Ciudad - País',
        'telefono' => $venta['empresa']['telefono'] ?? '+000 00000000'
    ];

    // Datos de la sucursal
    $sucursal = [
        'nombre' => $venta['sucursal']['nombre_sucursal'] ?? 'Principal'
    ];

    // Obtener usuario que procesó
    $usuario_registro = $venta['usuario_nombre'] . ' ' . $venta['usuario_apellido'] ?? $_SESSION['usuario_nombre'] . ' ' . $_SESSION['usuario_apellido'] ?? 'Usuario no disponible';

    // Create PDF
    $is_mobile = isMobile();
    $pdf = new TCPDF('P', 'mm', array(80, 215), true, 'UTF-8', false);
    $font_size = 9;
    $margin = 3;

    // Set document properties
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($appName);
    $pdf->SetTitle('Recibo de Venta');
    $pdf->SetSubject('Recibo de Venta');
    $pdf->SetKeywords('recibo, venta, ' . strtolower(str_replace(' ', '', $appName)));
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins($margin, $margin, $margin);
    $pdf->SetAutoPageBreak(TRUE, $margin);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', $font_size);

    // Set header content
    $html = <<<EOD
<style>
    table { width: 100%; border-collapse: collapse; }
    .titulo { font-size: 13pt; font-weight: bold; text-align: center; }
    .subtitulo { font-size: 10pt; text-align: center; }
    .info { font-size: 9pt; }
    .bold { font-weight: bold; }
    .center { text-align: center; }
    .right { text-align: right; }
    .linea { border-top: 1px dashed #000; height: 1px; margin: 0; padding: 0; }
    .items { font-size: 9pt; }
    .footer { font-size: 10pt; text-align: center; margin-top: 5px; font-weight: bold; }
</style>

<table>
    <tr><td class="titulo">{$empresa['nombre']}</td></tr>
    <tr><td class="subtitulo">NIT: {$empresa['nit']}</td></tr>
    <tr><td class="subtitulo">{$empresa['direccion']}</td></tr>
    <tr><td class="subtitulo">Cel. {$empresa['telefono']}</td></tr>
</table>

<table><tr><td style="border-bottom: 1px dashed #000; height: 1px;"></td></tr></table>

<table class="info">
    <tr>
        <td width="50%"><span class="bold">RECIBO #:</span> {$venta['idventa']}</td>
        <td width="50%"><span class="bold">SUCURSAL:</span><br>{$sucursal['nombre']}</td>
    </tr>
    <tr>
        <td><span class="bold">FECHA:</span> {$fecha_actual} {$hora_actual}</td>
        <td><span class="bold">VENDEDOR:</span><br>{$usuario_registro}</td>
    </tr>
    <tr>
        <td><span class="bold">CLIENTE:</span> {$cliente}</td>
        <td><span class="bold">DOC:</span> {$documento_cliente}</td>
    </tr>
</table>

<table><tr><td style="border-top: 1px dashed #000; height: 1px;"></td></tr></table>

<table class="center"><tr><td class="bold">DETALLE DE PRODUCTOS</td></tr></table>

<table><tr><td style="border-bottom: 1px dashed #000; height: 1px;"></td></tr></table>
EOD;

    $pdf->writeHTML($html, true, false, true, '');

    // Generate products HTML
    $html = '';

    $subtotal_general = 0;
    $descuento_general = 0;

    foreach ($detalles as $index => $detalle) {
        $num = $index + 1;
        $producto = $detalle['producto_nombre'] ?? 'Producto no disponible';
        $codigo = $detalle['producto_codigo'] ?? '';
        $cantidad = $detalle['cantidad'] ?? 0;
        $precio = $detalle['precioventa'] ?? 0;
        $descuento = $detalle['descuento'] ?? 0;

        // Calcular subtotal y descuento para este producto
        $subtotal_producto = $cantidad * $precio;
        $descuento_total_producto = $cantidad * $descuento;
        $neto_producto = $subtotal_producto - $descuento_total_producto;

        // Acumular totales
        $subtotal_general += $subtotal_producto;
        $descuento_general += $descuento_total_producto;

        $precio_formatted = formatearMoneda($precio);
        $subtotal_producto_formatted = formatearMoneda($subtotal_producto);
        $descuento_formatted = formatearMoneda($descuento);
        $descuento_total_formatted = formatearMoneda($descuento_total_producto);
        $neto_producto_formatted = formatearMoneda($neto_producto);

        $html .= <<<EOD
<table class="items">
    <tr>
        <td><strong>{$num}. {$producto}</strong></td>
        <td style="text-align: right;">{$cantidad} x {$precio_formatted} = {$appCurrency} {$subtotal_producto_formatted}</td>
    </tr>
EOD;
        if ($descuento > 0) {
            $html .= <<<EOD
        <tr>
            <td>Descuento por unidad:</td>
            <td style="text-align: right;">- {$appCurrency} {$descuento_total_formatted}</td>
        </tr>
    EOD;
        }
        $html .= <<<EOD
    <tr>
        <td>Subtotal:</td>
        <td style="text-align: right;">{$appCurrency} {$neto_producto_formatted}</td>
    </tr>
</table>
EOD;
    }

    $pdf->writeHTML($html, true, false, true, '');

    // Convertir monto a literal con manejo de errores
    try {
        $monto_total_literal = strtoupper(numeroletras($monto_total));
    } catch (Exception $e) {
        $monto_total_literal = strtoupper(formatearMoneda($monto_total) . " BOLIVIANOS");
    }

    // Formatear montos
    $monto_total_formatted = formatearMoneda($monto_total);

    // Generate payment information
    $html = <<<EOD
<table><tr><td style="border-bottom: 1px dashed #000; height: 1px;"></td></tr></table>

<table class="items">
    <tr>
        <td><strong>TOTAL VENTA:</strong></td>
        <td style="text-align: right; margin-right: 10px;"><strong>{$appCurrency} {$monto_total_formatted}</strong></td>
    </tr>
</table>

<table class="items">
    <tr>
        <td width="12%"><strong>SON:</strong></td>
        <td width="88%">{$monto_total_literal}</td><br>
    </tr>
</table>

<table><tr><td style="border-top: 1px dashed #000; height: 1px;"></td></tr></table>

<table style="text-align: center;"><tr><td><strong>FORMA DE PAGO</strong></td></tr></table>

<table><tr><td style="border-bottom: 1px dashed #000; height: 1px;"></td></tr></table>
EOD;

    $pdf->writeHTML($html, true, false, true, '');

    // Generar información de pagos
    $html = '<table class="items">';

    $total_recibido = 0;
    $total_cambio = 0;

    foreach ($pagos as $pago) {
        $metodo = ucfirst($pago['metodopago'] ?? 'No especificado');
        $monto = formatearMoneda($pago['monto'] ?? 0);

        $html .= <<<EOD
    <tr>
        <td width="60%">- {$metodo}:</td>
        <td width="40%" style="text-align: right;">{$appCurrency} {$monto}</td>
    </tr>
EOD;

        $total_recibido += ($pago['pagorecibido'] ?? 0);
        $total_cambio += ($pago['cambio'] ?? 0);
    }

    // Mostrar total recibido y cambio si corresponde
    if ($total_recibido > $monto_total) {
        $total_recibido_formatted = formatearMoneda($total_recibido);
        $total_cambio_formatted = formatearMoneda($total_cambio);

        $html .= <<<EOD
    <tr>
        <td>Recibido:</td>
        <td style="text-align: right;">{$appCurrency} {$total_recibido_formatted}</td>
    </tr>
    <tr>
        <td>Cambio:</td>
        <td style="text-align: right;">{$appCurrency} {$total_cambio_formatted}</td>
    </tr>
EOD;
    }

    $html .= '</table>';

    $pdf->writeHTML($html, true, false, true, '');

    // Observaciones si existen
    if (!empty($observaciones)) {
        $html = <<<EOD
<table><tr><td style="border-top: 1px dashed #000; height: 1px;"></td></tr></table>
<table class="items">
    <tr>
        <td class="bold">OBSERVACIONES:</td>
    </tr>
    <tr>
        <td>{$observaciones}</td>
    </tr>
</table>
EOD;
        $pdf->writeHTML($html, true, false, true, '');
    }

    // Generate QR code con manejo de errores
    $style = array(
        'border' => 0,
        'vpadding' => '0',
        'hpadding' => '0',
        'fgcolor' => array(0, 0, 0),
        'bgcolor' => array(255, 255, 255),
        'module_width' => 1,
        'module_height' => 1
    );

    $QR = "Venta: {$venta['idventa']} \nCliente: {$cliente} \nTotal: {$appCurrency} {$monto_total_formatted}";

    // Centrar el código QR
    $pdf->Ln(5);
    try {
        $pdf->write2DBarcode($QR, 'QRCODE,L', 20, $pdf->GetY(), 40, 40, $style);
    } catch (Exception $e) {
        // Si falla el QR, continuar sin él
        $pdf->writeHTML('<p class="center">Código QR no disponible</p>', true, false, true, '');
    }

    $pdf->Ln(40);

    // Add a final message
    $html = <<<EOD
<br><p style="text-align: center;">¡GRACIAS POR SU COMPRA!</p>
EOD;
    $pdf->writeHTML($html, true, false, true, '');

    // Set headers
    header('Content-Type: application/pdf');
    header('Content-Disposition: ' . ($is_mobile ? 'attachment' : 'inline') . '; filename="Venta_' . $venta['idventa'] . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    // Output PDF
    $pdf->Output('Venta_' . $venta['idventa'] . '.pdf', $is_mobile ? 'D' : 'I');
} catch (Exception $e) {
    // Capturar cualquier error y mostrarlo
    die("Error al generar el PDF: " . $e->getMessage() . "\nArchivo: " . $e->getFile() . "\nLínea: " . $e->getLine());
} catch (Error $e) {
    // Capturar errores fatales
    die("Error fatal al generar el PDF: " . $e->getMessage() . "\nArchivo: " . $e->getFile() . "\nLínea: " . $e->getLine());
}
