<?php

/**
 * Procesador de Logout
 * 
 * Este archivo procesa el cierre de sesión
 * 
 * @version 1.0
 */

require_once __DIR__ . '/../../views/layouts/session.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: ' . $URL . 'index.php');
    exit;
}

requireCSRF();

// Incluir el controlador de autenticación
require_once __DIR__ . '/AuthController.php';

// Crear instancia del controlador
$authController = new AuthController();

// Procesar logout
$authController->logout();
