<?php
/**
 * Controlador base: utilidades comunes para renderizar vistas,
 * redirigir, responder JSON y proteger rutas.
 */
abstract class Controller
{
    /** Renderiza una vista dentro del layout (o sin layout). */
    protected function render(string $vista, array $datos = [], bool $conLayout = true): void
    {
        extract($datos, EXTR_SKIP);
        $archivoVista = APP_PATH . '/views/' . $vista . '.php';

        if ($conLayout) {
            require APP_PATH . '/views/layout/header.php';
            require $archivoVista;
            require APP_PATH . '/views/layout/footer.php';
        } else {
            require $archivoVista;
        }
    }

    protected function redirigir(string $ruta, array $params = []): void
    {
        header('Location: ' . url($ruta, $params));
        exit;
    }

    protected function json(array $datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Bloquea el acceso si no hay sesión iniciada. */
    protected function requiereAuth(): void
    {
        if (esta_autenticado()) {
            return;
        }
        if (es_ajax()) {
            $this->json(['ok' => false, 'mensaje' => 'La sesión expiró. Inicie sesión de nuevo.'], 401);
        }
        flash('aviso', 'Inicie sesión para continuar.');
        $this->redirigir('auth/login');
    }

    /** Exige método POST y token CSRF válido. */
    protected function requierePost(string $rutaRetorno, array $params = []): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Método no permitido');
        }
        if (!csrf_valido($_POST['_csrf'] ?? null)) {
            flash('error', 'El formulario expiró por seguridad. Vuelva a enviarlo.');
            $this->redirigir($rutaRetorno, $params);
        }
    }

    protected function idDesde(array $origen): int
    {
        return filter_var($origen['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
    }
}
