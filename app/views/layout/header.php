<?php
$u = usuario_actual();
$seccion = explode('/', $_GET['r'] ?? 'dashboard/index')[0];
$menu = [
    'dashboard'    => ['Panel', 'dashboard/index'],
    'reservas'     => ['Reservas', 'reservas/index'],
    'huespedes'    => ['Huéspedes', 'huespedes/index'],
    'habitaciones' => ['Habitaciones', 'habitaciones/index'],
    'bitacora'     => ['Bitácora', 'bitacora/index'],
];
$aviso = flash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='7' fill='%2316213e'/%3E%3Cpath d='M20 6a10 10 0 1 0 6 17A11 11 0 0 1 20 6z' fill='%23e0b04b'/%3E%3C/svg%3E">
  <title><?= e(($titulo ?? 'Panel') . ' | ' . APP_NAME) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Literata:opsz,wght@7..72,500;7..72,600&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('css/styles.css')) ?>">
</head>
<body>
<a class="saltar" href="#contenido">Ir al contenido</a>
<div class="app">
  <aside class="lateral" id="lateral">
    <a class="marca" href="<?= e(url('dashboard/index')) ?>">
      <span class="luna" aria-hidden="true"></span>
      <span class="marca-texto"><strong>Luna Azul</strong><small>Recepción</small></span>
    </a>
    <nav class="menu" aria-label="Menú principal">
      <?php foreach ($menu as $clave => [$texto, $ruta]): ?>
        <a href="<?= e(url($ruta)) ?>" <?= $seccion === $clave ? 'class="activo" aria-current="page"' : '' ?>><?= e($texto) ?></a>
      <?php endforeach; ?>
    </nav>
    <a class="btn btn-farol" href="<?= e(url('reservas/crear')) ?>">Nueva reserva</a>
    <div class="lateral-pie">
      <span class="usuario-nombre"><?= e($u['nombre'] ?? '') ?></span>
      <span class="usuario-rol"><?= e(ucfirst($u['rol'] ?? '')) ?></span>
      <form method="post" action="<?= e(url('auth/logout')) ?>">
        <?= csrf_field() ?>
        <button type="submit" class="enlace-salir">Cerrar sesión</button>
      </form>
    </div>
  </aside>

  <main class="contenido" id="contenido">
    <button type="button" class="menu-movil" aria-controls="lateral" aria-expanded="false">Menú</button>
    <?php if ($aviso): ?>
      <div class="alerta alerta-<?= e($aviso['tipo']) ?>" role="status"><?= e($aviso['mensaje']) ?></div>
    <?php endif; ?>
