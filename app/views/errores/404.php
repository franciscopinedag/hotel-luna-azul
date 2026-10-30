<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='7' fill='%2316213e'/%3E%3Cpath d='M20 6a10 10 0 1 0 6 17A11 11 0 0 1 20 6z' fill='%23e0b04b'/%3E%3C/svg%3E">
  <title>Página no encontrada</title><link rel="stylesheet" href="<?= e(asset('css/styles.css')) ?>"></head>
<body class="pagina-error"><main class="error-caja">
  <span class="luna" aria-hidden="true"></span>
  <h1>Esta página no existe</h1>
  <p>La dirección no corresponde a ninguna sección del sistema.</p>
  <a class="btn btn-primario" href="<?= e(url(esta_autenticado() ? 'dashboard/index' : 'auth/login')) ?>">Ir al inicio</a>
</main></body></html>
