<?php
/**
 * Configuración general de la aplicación
 */

// Nombre de la aplicación
define('APP_NAME', 'Asistencia - Cultura Tretze');
define('APP_VERSION', '1.0');

// URL base (ajustar según donde se despliegue)
define('BASE_URL', '/AplicacionAsistencia');

// Zona horaria
date_default_timezone_set('Europe/Madrid');

// Sesión
define('SESSION_TIMEOUT', 10800); // 3 horas

// Errores (desactivar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_name('ASISTENCIA_SESSION');
    session_start();
}

// Cargar la conexión a la base de datos
require_once __DIR__ . '/database.php';
