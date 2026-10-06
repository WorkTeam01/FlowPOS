<?php

/**
 * Controlador de Ventas
 * 
 * Gestiona las operaciones relacionadas con las ventas y pagos mixtos
 * 
 * @version 2.0
 */

class VentaController
{
    /**
     * Modelo de Venta
     * @var Venta
     */
    private $modelo;

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        // Incluir el modelo de Venta
        require_once __DIR__ . '/../../models/Venta.php';
        $this->modelo = new Venta();
    }

    /**
     * Muestra la lista de ventas
     *
     * @param int|null $idusuario Si se indica, limita el listado a las ventas de ese usuario
     * @return array Lista de ventas
     */
    public function index($idusuario = null)
    {
        $ventas = $idusuario !== null
            ? $this->modelo->getPorUsuario($idusuario)
            : $this->modelo->getAll();

        return $this->adjuntarInfoMetodoPago($ventas);
    }

    /**
     * Adjunta a cada venta sus pagos y la información de presentación
     * del método de pago (texto, clase de badge, tooltip), evitando
     * consultas N+1 por fila al listar ventas.
     *
     * @param array $ventas Lista de ventas (de getAll()/getPorUsuario())
     * @return array Lista de ventas con las claves 'pagos' e 'info_metodo_pago' agregadas
     */
    private function adjuntarInfoMetodoPago(array $ventas)
    {
        $idsVenta = array_column($ventas, 'idventa');
        $pagosPorVenta = $this->modelo->getMetodosPagoPorVentas($idsVenta);

        foreach ($ventas as &$venta) {
            $pagos = $pagosPorVenta[$venta['idventa']] ?? [];
            $venta['pagos'] = $pagos;
            $venta['info_metodo_pago'] = $this->calcularInfoMetodoPago($pagos);
        }

        return $ventas;
    }

    /**
     * Determina el texto, la clase de badge y el tooltip a mostrar
     * para el/los método(s) de pago de una venta.
     *
     * @param array $pagos Lista de pagos de la venta
     * @return array ['texto', 'clase', 'tooltip', 'es_mixto']
     */
    private function calcularInfoMetodoPago(array $pagos)
    {
        $esMixto = $this->esPagoMixto($pagos);

        if ($esMixto) {
            $tooltip = '';
            foreach ($pagos as $pago) {
                $tooltip .= ucfirst($pago['metodopago']) . ': ' . number_format($pago['monto'], 2) . '<br>';
            }
            return ['texto' => 'Pago Mixto', 'clase' => 'badge-purple', 'tooltip' => $tooltip, 'es_mixto' => true];
        }

        $metodoPago = strtolower(trim($pagos[0]['metodopago'] ?? 'efectivo'));
        $mapaMetodos = [
            'efectivo' => ['texto' => 'Efectivo', 'clase' => 'badge-success'],
            'qr' => ['texto' => 'QR', 'clase' => 'badge-info'],
            'tarjeta' => ['texto' => 'Tarjeta', 'clase' => 'badge-primary'],
            'transferencia' => ['texto' => 'Transferencia', 'clase' => 'badge-secondary'],
        ];
        $info = $mapaMetodos[$metodoPago] ?? ['texto' => ucfirst($metodoPago), 'clase' => 'badge-secondary'];
        $info['tooltip'] = '';
        $info['es_mixto'] = false;

        return $info;
    }

    /**
     * Muestra el formulario para crear una nueva venta
     * 
     * @return array Datos para el formulario
     */
    public function crear()
    {
        // Incluir modelos necesarios
        require_once __DIR__ . '/../../models/Producto.php';
        require_once __DIR__ . '/../../models/Cliente.php';
        require_once __DIR__ . '/../../models/Usuario.php';

        // Obtener productos, clientes y usuarios para los selects
        $productoModel = new Producto();
        $clienteModel = new Cliente();
        $usuarioModel = new Usuario();

        $datos = [
            'productos' => $productoModel->getAll(),
            'clientes' => $clienteModel->getAll(),
            'usuarios' => $usuarioModel->getAll()
        ];

        return $datos;
    }

    /**
     * Prepara los datos de la venta desde $_POST, adaptado para manejar pago único o mixto
     * 
     * @param array $post_data Datos del formulario
     * @return array Datos preparados
     */
    private function prepararDatosVenta($post_data)
    {
        // Información básica de la venta
        $datos = [
            'idcliente' => !empty($post_data['idcliente']) ? (int)$post_data['idcliente'] : null,
            'idusuario' => (int)($_SESSION['usuario_id'] ?? 0),
            'totalventa' => 0, // Se recalcula server-side a partir de los detalles
            'fechaventa' => isset($post_data['fechaventa']) ? trim($post_data['fechaventa']) : date('Y-m-d'),
            'observacion' => isset($post_data['observacion']) ? trim($post_data['observacion']) : null,
            'estado' => 1, // Por defecto activa
            'detalles' => [],
            'pagos' => []
        ];

        // Procesar detalles de la venta (productos)
        if (isset($post_data['productos']) && is_array($post_data['productos'])) {
            require_once __DIR__ . '/../../models/Producto.php';
            $productoModel = new Producto();

            $total = 0;

            foreach ($post_data['productos'] as $key => $idProducto) {
                if (!empty($idProducto)) {
                    // El precio de venta se obtiene siempre del producto en base de datos,
                    // nunca del formulario, para evitar que el cliente lo manipule.
                    $producto = $productoModel->getById((int)$idProducto);
                    $precioventa = $producto ? (float)$producto['precioventa'] : 0;

                    $cantidad = isset($post_data['cantidades'][$key]) ? (int)$post_data['cantidades'][$key] : 1;
                    $descuento = isset($post_data['descuentos'][$key]) ? (float)$post_data['descuentos'][$key] : 0.00;

                    $datos['detalles'][] = [
                        'idproducto' => (int)$idProducto,
                        'cantidad' => $cantidad,
                        'precioventa' => $precioventa,
                        'descuento' => $descuento
                    ];

                    // El descuento es por unidad, igual que en el formulario, el recibo y los dashboards
                    $total += $cantidad * ($precioventa - $descuento);
                }
            }

            $datos['totalventa'] = $total;
        }

        // Procesar métodos de pago según el tipo de pago (único o mixto)
        $tipoPago = isset($post_data['tipo_pago']) ? $post_data['tipo_pago'] : 'mixto';

        if ($tipoPago === 'unico' && isset($post_data['metodopago_unico']) && !empty($post_data['metodopago_unico'])) {
            // Procesar pago único
            $metodoPago = $post_data['metodopago_unico'];
            $monto = isset($post_data['monto_unico']) ? (float)$post_data['monto_unico'] : 0;
            $pagoRecibido = isset($post_data['pago_recibido_unico']) ? (float)$post_data['pago_recibido_unico'] : $monto;
            $cambio = isset($post_data['cambio_unico']) ? (float)$post_data['cambio_unico'] : 0;

            // Si no es efectivo, el pago recibido es igual al monto y el cambio es 0
            if ($metodoPago !== 'efectivo') {
                $pagoRecibido = $monto;
                $cambio = 0;
            } else {
                // El cambio lo calcula el servidor: el enviado por el cliente no es confiable
                $cambio = max(0, round($pagoRecibido - $monto, 2));
            }

            $datos['pagos'][] = [
                'metodopago' => $metodoPago,
                'monto' => $monto,
                'pagorecibido' => $pagoRecibido,
                'cambio' => $cambio
            ];
        } else if (isset($post_data['metodopago']) && is_array($post_data['metodopago'])) {
            // Procesar pago mixto (código existente)
            foreach ($post_data['metodopago'] as $key => $metodoPago) {
                if (!empty($metodoPago)) {
                    $monto = isset($post_data['montopago'][$key]) ? (float)$post_data['montopago'][$key] : 0;
                    $pagoRecibido = isset($post_data['pagorecibido'][$key]) ? (float)$post_data['pagorecibido'][$key] : $monto;
                    $cambio = isset($post_data['cambio'][$key]) ? (float)$post_data['cambio'][$key] : 0;

                    // Si no es efectivo, el pago recibido y el cambio son iguales al monto
                    if ($metodoPago !== 'efectivo') {
                        $pagoRecibido = $monto;
                        $cambio = 0;
                    } else {
                        $cambio = max(0, round($pagoRecibido - $monto, 2));
                    }

                    $datos['pagos'][] = [
                        'metodopago' => $metodoPago,
                        'monto' => $monto,
                        'pagorecibido' => $pagoRecibido,
                        'cambio' => $cambio
                    ];
                }
            }
        }

        return $datos;
    }

    /**
     * Comprueba que el cliente exista y que los productos estén activos: el
     * formulario solo ofrece activos, pero un POST directo puede enviar otros.
     *
     * @param array $datos Datos preparados de la venta
     * @return array Lista de errores (vacía si todo es válido)
     */
    private function validarReferencias(array $datos)
    {
        require_once __DIR__ . '/../../models/Producto.php';
        require_once __DIR__ . '/../../models/Cliente.php';

        if (!empty($datos['idcliente']) && !(new Cliente())->getById($datos['idcliente'])) {
            return ['El cliente seleccionado no existe'];
        }

        $productoModel = new Producto();
        foreach ($datos['detalles'] as $detalle) {
            $producto = $productoModel->getById($detalle['idproducto']);
            if (!$producto || (int) $producto['estado'] !== 1) {
                return ['Uno de los productos no está disponible para la venta'];
            }
        }

        return [];
    }

    /**
     * Procesa el formulario para guardar una nueva venta
     * 
     * @return array Resultado de la operación
     */
    public function guardar()
    {
        // Verificar si se envió el formulario
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return ['success' => false, 'message' => 'Acceso no permitido.', 'icon' => 'warning', 'redirect' => 'index.php'];
        }

        // Preparar datos de la venta
        $datos = $this->modelo->sanitizarDatos($this->prepararDatosVenta($_POST));

        // Validar datos en el modelo
        $errores = $this->modelo->validarDatos($datos);

        if (empty($errores)) {
            $errores = $this->validarReferencias($datos);
        }

        if (!empty($errores)) {
            return ['success' => false, 'message' => $errores[0], 'icon' => 'error', 'redirect' => 'create.php'];
        }

        // Guardar venta usando el modelo
        $idVenta = $this->modelo->crear($datos);

        if ($idVenta) {
            return ['success' => true, 'message' => 'Venta registrada correctamente', 'icon' => 'success', 'redirect' => 'index.php'];
        } else {
            return ['success' => false, 'message' => mensajeErrorSeguro('Error al registrar la venta', $this->modelo->getLastError()), 'icon' => 'error', 'redirect' => 'create.php'];
        }
    }

    /**
     * Muestra los detalles de una venta
     * 
     * @param int $id ID de la venta
     * @return array|null Datos de la venta o redirige en caso de error
     */
    public function ver($id = null)
    {
        // Verificar si se proporcionó un ID
        if (!$id) {
            global $URL;
            $_SESSION['mensaje'] = 'ID de venta no válido';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/ventas');
            exit;
        }

        // Obtener datos de la venta con detalles y métodos de pago
        $venta = $this->modelo->getById($id);

        if (!$venta) {
            global $URL;
            $_SESSION['mensaje'] = 'Venta no encontrada';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $URL . 'views/ventas');
            exit;
        }

        // Devolver los datos de la venta
        return $venta;
    }

    /**
     * Obtiene una venta por ID sin redirigir si no existe
     *
     * @param int $id ID de la venta
     * @return array|null Datos de la venta o null si no existe
     */
    public function obtenerPorId($id)
    {
        return $this->modelo->getById($id);
    }

    /**
     * Calcula subtotal, descuento y total pagado de una venta a partir
     * de sus detalles y pagos.
     *
     * @param array $venta Venta con 'detalles' y 'pagos' (de ver()/getById())
     * @return array ['subtotal', 'descuento', 'total_pagado']
     */
    public function calcularTotales(array $venta)
    {
        $subtotal = 0;
        $descuento = 0;

        foreach ($venta['detalles'] ?? [] as $detalle) {
            $subtotal += $detalle['cantidad'] * ($detalle['precioventa'] - $detalle['descuento']);
            $descuento += $detalle['cantidad'] * $detalle['descuento'];
        }

        return [
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'total_pagado' => array_sum(array_column($venta['pagos'] ?? [], 'monto')),
        ];
    }

    /**
     * Ícono FontAwesome y clase de badge asociados a un método de pago,
     * para uso en el detalle de venta.
     *
     * @param string $metodoPago
     * @return array [claseIcono, claseBadge]
     */
    public function obtenerIconoMetodoPago($metodoPago)
    {
        $mapa = [
            'efectivo' => ['fas fa-money-bill-wave', 'badge-success'],
            'tarjeta' => ['far fa-credit-card', 'badge-primary'],
            'qr' => ['fas fa-qrcode', 'badge-info'],
            'transferencia' => ['fas fa-exchange-alt', 'badge-secondary'],
        ];

        return $mapa[strtolower(trim($metodoPago ?? ''))] ?? ['fas fa-money-bill-alt', 'badge-secondary'];
    }

    /**
     * Determina si una lista de pagos corresponde a un pago mixto
     * (más de un método de pago distinto usado en la misma venta).
     *
     * @param array $pagos
     * @return bool
     */
    public function esPagoMixto(array $pagos)
    {
        $metodosDistintos = array_unique(array_map(
            fn($pago) => strtolower(trim($pago['metodopago'] ?? '')),
            $pagos
        ));

        return count($metodosDistintos) > 1;
    }

    /**
     * Desglosa una línea de detalle de venta en subtotal bruto, descuento
     * total y neto, para uso en el recibo (donde se muestra el detalle
     * por unidad además del total).
     *
     * @param array $detalle Línea de detalle (cantidad, precioventa, descuento)
     * @return array ['subtotal', 'descuento_total', 'neto']
     */
    public function calcularDetalleLinea(array $detalle)
    {
        $cantidad = $detalle['cantidad'] ?? 0;
        $precio = $detalle['precioventa'] ?? 0;
        $descuentoUnitario = $detalle['descuento'] ?? 0;

        $subtotal = $cantidad * $precio;
        $descuentoTotal = $cantidad * $descuentoUnitario;

        return [
            'subtotal' => $subtotal,
            'descuento_total' => $descuentoTotal,
            'neto' => $subtotal - $descuentoTotal,
        ];
    }

    /**
     * Desglosa cada línea de detalle (vía calcularDetalleLinea()) y acumula
     * los totales generales, para que el recibo solo itere sobre datos
     * ya resueltos sin acumular manualmente.
     *
     * @param array $detalles Lista de líneas de detalle de la venta
     * @return array ['lineas' => [[...detalle original, 'calculo' => [...]], ...], 'subtotal_general', 'descuento_general']
     */
    public function calcularDesgloseDetalles(array $detalles)
    {
        $subtotalGeneral = 0;
        $descuentoGeneral = 0;
        $lineas = [];

        foreach ($detalles as $detalle) {
            $calculo = $this->calcularDetalleLinea($detalle);
            $subtotalGeneral += $calculo['subtotal'];
            $descuentoGeneral += $calculo['descuento_total'];
            $lineas[] = $detalle + ['calculo' => $calculo];
        }

        return [
            'lineas' => $lineas,
            'subtotal_general' => $subtotalGeneral,
            'descuento_general' => $descuentoGeneral,
        ];
    }

    /**
     * Total recibido y cambio a partir de la lista de pagos de una venta,
     * para uso en el recibo.
     *
     * @param array $pagos
     * @return array ['total_recibido', 'total_cambio']
     */
    public function calcularResumenPagos(array $pagos)
    {
        return [
            'total_recibido' => array_sum(array_column($pagos, 'pagorecibido')),
            'total_cambio' => array_sum(array_column($pagos, 'cambio')),
        ];
    }

    /**
     * Anula una venta
     *
     * @param int $id ID de la venta
     * @return array Resultado de la operación
     */
    public function anular($id = null)
    {
        if (!$id) {
            return ['success' => false, 'message' => 'ID de venta no válido', 'icon' => 'error'];
        }

        if ($this->modelo->anular($id)) {
            return ['success' => true, 'message' => 'Venta anulada correctamente', 'icon' => 'success'];
        } else {
            return ['success' => false, 'message' => mensajeErrorSeguro('Error al anular la venta', $this->modelo->getLastError()), 'icon' => 'error'];
        }
    }

    /**
     * Obtiene ventas por estado
     * 
     * @param int $estado Estado de las ventas (1: Activo, 0: Inactivo)
     * @return array Lista de ventas con el estado especificado
     */
    public function obtenerPorEstado($estado)
    {
        return $this->modelo->getPorEstado($estado);
    }

    /**
     * Obtiene ventas por usuario
     * 
     * @param int $idUsuario ID del usuario
     * @return array Lista de ventas del usuario
     */
    public function obtenerPorUsuario($idUsuario)
    {
        return $this->modelo->getPorUsuario($idUsuario);
    }

    /**
     * Obtiene ventas por cliente
     * 
     * @param int $idCliente ID del cliente
     * @return array Lista de ventas del cliente
     */
    public function obtenerPorCliente($idCliente)
    {
        return $this->modelo->getPorCliente($idCliente);
    }

    /**
     * Obtiene métodos de pago de una venta
     * 
     * @param int $idVenta ID de la venta
     * @return array Métodos de pago de la venta
     */
    public function obtenerMetodosPago($idVenta)
    {
        return $this->modelo->getMetodosPago($idVenta);
    }

    /**
     * Obtiene estadísticas de ventas
     * 
     * @return array Estadísticas de ventas
     */
    public function getEstadisticas($idusuario = null)
    {
        return $this->modelo->getEstadisticas($idusuario);
    }

}
