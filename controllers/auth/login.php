<?php

/**
 * Procesador de Login
 * 
 * Este archivo procesa el formulario de login
 * 
 * @version 1.0
 */

// Iniciar sesión
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir el controlador de autenticación
require_once __DIR__ . '/AuthController.php';

// Crear instancia del controlador
$authController = new AuthController();

// Procesar login
$authController->login();
