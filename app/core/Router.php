<?php
/**
 * Enrutador: traduce la ruta "controlador/accion" al método del controlador.
 * Solo se ejecutan rutas registradas en la lista blanca.
 */
class Router
{
    public function __construct(private array $rutas) {}

    public function despachar(string $ruta): void
    {
        if (!isset($this->rutas[$ruta])) {
            http_response_code(404);
            require APP_PATH . '/views/errores/404.php';
            return;
        }

        [$controlador, $metodo] = $this->rutas[$ruta];

        try {
            (new $controlador())->$metodo();
        } catch (PDOException $e) {
            error_log('[BD] ' . $e->getMessage());
            http_response_code(500);
            $detalle = 'No fue posible comunicarse con la base de datos. Verifique que MySQL esté encendido en XAMPP y que la base "' . DB_NAME . '" exista.';
            if (es_ajax()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'mensaje' => $detalle], JSON_UNESCAPED_UNICODE);
                return;
            }
            require APP_PATH . '/views/errores/500.php';
        }
    }
}
