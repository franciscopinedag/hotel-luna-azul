<?php
/**
 * Configuración general de la aplicación.
 * Ajuste DB_USER / DB_PASS si su MySQL de XAMPP tiene contraseña.
 */
define('APP_NAME', 'Hotel Luna Azul');
define('APP_VERSION', '1.0.0');

// --- Base de datos (valores por defecto de XAMPP) ---
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'hotel_luna_azul');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

// --- Rutas internas ---
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('STORAGE_PATH', ROOT_PATH . '/storage');

// URL base calculada automáticamente (funciona en http://localhost/hotel_luna_azul/)
$__dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
define('BASE_URL', rtrim($__dir, '/') . '/');

// --- Seguridad ---
define('SESION_MAX_INACTIVIDAD', 30 * 60);   // 30 minutos
define('LOGIN_MAX_INTENTOS', 5);              // intentos fallidos permitidos
define('LOGIN_BLOQUEO_SEG', 120);             // bloqueo temporal tras superar intentos

// --- Reglas de negocio ---
define('MAX_NOCHES', 30);

date_default_timezone_set('America/Bogota');
