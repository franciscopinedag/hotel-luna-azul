<?php
/**
 * Funciones auxiliares globales: sesión, seguridad, URLs y formato.
 */

function iniciar_sesion(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('LUNAAZUL');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,                    // JavaScript no puede leer la cookie
            'samesite' => 'Lax',                   // mitiga CSRF
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);
        session_start();
    }

    // Cierre automático por inactividad
    if (isset($_SESSION['usuario'], $_SESSION['ultimo_movimiento'])
        && time() - $_SESSION['ultimo_movimiento'] > SESION_MAX_INACTIVIDAD) {
        unset($_SESSION['usuario']);
        session_regenerate_id(true);
        flash('aviso', 'La sesión se cerró por inactividad.');
    }
    $_SESSION['ultimo_movimiento'] = time();
}

function enviar_cabeceras_seguridad(): void
{
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
}

/** Escapa texto para imprimirlo en HTML (previene XSS). */
function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function url(string $ruta, array $params = []): string
{
    return BASE_URL . 'index.php?' . str_replace('%2F', '/', http_build_query(array_merge(['r' => $ruta], $params)));
}

function asset(string $ruta): string
{
    return BASE_URL . 'public/' . $ruta . '?v=' . APP_VERSION;
}

function es_ajax(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
}

// ---------- CSRF ----------
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valido(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
}

// ---------- Mensajes flash ----------
function flash(?string $tipo = null, ?string $mensaje = null): ?array
{
    if ($tipo !== null) {
        $_SESSION['_flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
        return null;
    }
    $f = $_SESSION['_flash'] ?? null;
    unset($_SESSION['_flash']);
    return $f;
}

// ---------- Autenticación ----------
function esta_autenticado(): bool
{
    return isset($_SESSION['usuario']['id']);
}

function usuario_actual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

// ---------- Formato ----------
function moneda(float|string|null $valor): string
{
    return '$ ' . number_format((float) $valor, 0, ',', '.');
}

function fecha_corta(?string $fecha): string
{
    if (!$fecha) {
        return '';
    }
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $t = strtotime($fecha);
    return date('j', $t) . ' ' . $meses[(int) date('n', $t) - 1] . ' ' . date('Y', $t);
}

function dia_semana(string $fecha): string
{
    return ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'][(int) date('w', strtotime($fecha))];
}

function noches(string $ingreso, string $salida): int
{
    return (int) (new DateTime($ingreso))->diff(new DateTime($salida))->format('%r%a');
}

/** Devuelve el mensaje de error de un campo (si existe) listo para la vista. */
function error_campo(array $errores, string $campo): string
{
    return isset($errores[$campo]) ? e($errores[$campo]) : '';
}

function etiqueta_documento(string $tipo): string
{
    return [
        'CC'  => 'Cédula de ciudadanía',
        'CE'  => 'Cédula de extranjería',
        'PA'  => 'Pasaporte',
        'PPT' => 'Permiso por protección temporal',
    ][$tipo] ?? $tipo;
}
