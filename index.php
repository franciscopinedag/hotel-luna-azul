<?php
/**
 * Front Controller: único punto de entrada de la aplicación.
 * Toda petición llega aquí como index.php?r=controlador/accion
 */
declare(strict_types=1);

require __DIR__ . '/config/config.php';
require __DIR__ . '/app/core/helpers.php';

// Carga automática de clases (core, modelos y controladores)
spl_autoload_register(function (string $clase): void {
    foreach (['core', 'models', 'controllers'] as $carpeta) {
        $archivo = APP_PATH . "/$carpeta/$clase.php";
        if (is_file($archivo)) {
            require $archivo;
            return;
        }
    }
});

iniciar_sesion();
enviar_cabeceras_seguridad();

$router = new Router([
    // Autenticación
    'auth/login'          => ['AuthController', 'login'],
    'auth/logout'         => ['AuthController', 'logout'],
    // Panel
    'dashboard/index'     => ['DashboardController', 'index'],
    // Huéspedes (CRUD)
    'huespedes/index'     => ['HuespedController', 'index'],
    'huespedes/crear'     => ['HuespedController', 'crear'],
    'huespedes/guardar'   => ['HuespedController', 'guardar'],
    'huespedes/editar'    => ['HuespedController', 'editar'],
    'huespedes/actualizar'=> ['HuespedController', 'actualizar'],
    'huespedes/eliminar'  => ['HuespedController', 'eliminar'],
    // Reservas
    'reservas/index'      => ['ReservaController', 'index'],
    'reservas/crear'      => ['ReservaController', 'crear'],
    'reservas/guardar'    => ['ReservaController', 'guardar'],
    'reservas/ver'        => ['ReservaController', 'ver'],
    'reservas/editar'     => ['ReservaController', 'editar'],
    'reservas/actualizar' => ['ReservaController', 'actualizar'],
    'reservas/cancelar'   => ['ReservaController', 'cancelar'],
    'reservas/finalizar'  => ['ReservaController', 'finalizar'],
    // Habitaciones y bitácora
    'habitaciones/index'  => ['HabitacionController', 'index'],
    'bitacora/index'      => ['BitacoraController', 'index'],
    // Endpoints AJAX (respuestas JSON)
    'api/documento'       => ['ApiController', 'documento'],
    'api/disponibilidad'  => ['ApiController', 'disponibilidad'],
    'api/huespedes'       => ['ApiController', 'huespedes'],
]);

$ruta = $_GET['r'] ?? (esta_autenticado() ? 'dashboard/index' : 'auth/login');
$router->despachar((string) $ruta);
