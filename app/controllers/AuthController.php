<?php
/**
 * Autenticación segura:
 * - Contraseñas con bcrypt (password_hash / password_verify).
 * - Regeneración del ID de sesión al ingresar (evita fijación de sesión).
 * - Bloqueo temporal tras varios intentos fallidos (fuerza bruta).
 * - Token CSRF en el formulario.
 */
class AuthController extends Controller
{
    public function login(): void
    {
        if (esta_autenticado()) {
            $this->redirigir('dashboard/index');
        }

        $error = null;
        $usuarioIngresado = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuarioIngresado = trim((string) ($_POST['usuario'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $bloqueadoHasta = $_SESSION['login_bloqueo'] ?? 0;

            if (!csrf_valido($_POST['_csrf'] ?? null)) {
                $error = 'El formulario expiró. Vuelva a intentarlo.';
            } elseif ($bloqueadoHasta > time()) {
                $error = 'Demasiados intentos fallidos. Espere ' . ($bloqueadoHasta - time()) . ' segundos.';
            } elseif ($usuarioIngresado === '' || $password === '') {
                $error = 'Escriba su usuario y contraseña.';
            } else {
                $modelo = new Usuario();
                $u = $modelo->buscarPorUsuario($usuarioIngresado);

                if ($u && $u['activo'] && password_verify($password, $u['password_hash'])) {
                    session_regenerate_id(true);
                    unset($_SESSION['login_intentos'], $_SESSION['login_bloqueo']);
                    $_SESSION['usuario'] = [
                        'id' => (int) $u['id'], 'nombre' => $u['nombre'],
                        'usuario' => $u['usuario'], 'rol' => $u['rol'],
                    ];
                    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
                        $modelo->actualizarHash((int) $u['id'], $password);
                    }
                    $modelo->registrarAcceso((int) $u['id']);
                    (new Bitacora())->registrar('inicio_sesion', 'usuario', (int) $u['id']);
                    flash('ok', 'Bienvenido(a), ' . $u['nombre'] . '.');
                    $this->redirigir('dashboard/index');
                }

                // Mensaje genérico: no revela si el usuario existe
                $_SESSION['login_intentos'] = ($_SESSION['login_intentos'] ?? 0) + 1;
                $restantes = LOGIN_MAX_INTENTOS - $_SESSION['login_intentos'];
                if ($restantes <= 0) {
                    $_SESSION['login_bloqueo'] = time() + LOGIN_BLOQUEO_SEG;
                    $_SESSION['login_intentos'] = 0;
                    $error = 'Demasiados intentos fallidos. El acceso queda bloqueado por ' . LOGIN_BLOQUEO_SEG / 60 . ' minutos.';
                } else {
                    $error = "Usuario o contraseña incorrectos. Le quedan $restantes intento(s).";
                }
                (new Bitacora())->registrar('intento_fallido', 'usuario', null, ['usuario' => $usuarioIngresado]);
            }
        }

        $this->render('auth/login', ['error' => $error, 'usuarioIngresado' => $usuarioIngresado], false);
    }

    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valido($_POST['_csrf'] ?? null) && esta_autenticado()) {
            (new Bitacora())->registrar('cierre_sesion', 'usuario', usuario_actual()['id']);
            $_SESSION = [];
            session_regenerate_id(true);
            flash('ok', 'Sesión cerrada correctamente.');
        }
        $this->redirigir('auth/login');
    }
}
