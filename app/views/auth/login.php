<?php $aviso = flash(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='7' fill='%2316213e'/%3E%3Cpath d='M20 6a10 10 0 1 0 6 17A11 11 0 0 1 20 6z' fill='%23e0b04b'/%3E%3C/svg%3E">
  <title>Ingresar | <?= e(APP_NAME) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Literata:opsz,wght@7..72,500;7..72,600&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('css/styles.css')) ?>">
</head>
<body class="pagina-login">
  <main class="login">
    <section class="login-cielo" aria-hidden="true">
      <span class="luna luna-grande"></span>
      <p class="login-lema">Cada habitación, una sola reserva a la vez.</p>
    </section>
    <section class="login-panel">
      <h1>Hotel Luna Azul</h1>
      <p class="sub">Sistema de reservas para recepción</p>

      <?php if ($aviso): ?>
        <div class="alerta alerta-<?= e($aviso['tipo']) ?>" role="status"><?= e($aviso['mensaje']) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alerta alerta-error" role="alert"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="post" action="<?= e(url('auth/login')) ?>" class="form-login" data-validar novalidate>
        <?= csrf_field() ?>
        <label class="campo">
          <span>Usuario</span>
          <input type="text" name="usuario" value="<?= e($usuarioIngresado) ?>" autocomplete="username" required autofocus
                 data-regla="usuario">
          <small class="error-campo"></small>
        </label>
        <label class="campo">
          <span>Contraseña</span>
          <span class="clave">
            <input type="password" name="password" id="password" autocomplete="current-password" required data-regla="requerido">
            <button type="button" class="ver-clave" aria-controls="password">Mostrar</button>
          </span>
          <small class="error-campo"></small>
        </label>
        <button type="submit" class="btn btn-primario btn-bloque">Ingresar</button>
      </form>
      <p class="nota">Acceso de prueba: <code>admin</code> / <code>Admin123*</code></p>
    </section>
  </main>
  <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
