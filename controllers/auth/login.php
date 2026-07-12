<?php

/**
 * Procesador de Login
 * 
 * Este archivo procesa el formulario de login
 * 
 * @version 1.0
 */

// Cargar gestión de sesión y helpers CSRF (define $URL, requireCSRF, getSafeRedirectBack)
require_once __DIR__ . '/../../views/layouts/session.php';

// Incluir el controlador de autenticación
require_once __DIR__ . '/AuthController.php';

// Crear instancia del controlador
$authController = new AuthController();

// Procesar login
$authController->login();
