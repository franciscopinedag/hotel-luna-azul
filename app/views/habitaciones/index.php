<header class="encabezado">
  <div>
    <h1>Habitaciones</h1>
    <p class="sub">Estado de cada habitación para la noche de hoy</p>
  </div>
</header>

<section class="tipos" aria-label="Tarifas por tipo de habitación">
  <?php foreach ($tipos as $t): ?>
    <div class="tipo">
      <h2><?= e($t['nombre']) ?></h2>
      <p class="tarifa"><?= e(moneda($t['precio_noche'])) ?> <span class="tenue">por noche</span></p>
      <p class="tenue">Hasta <?= (int) $t['capacidad'] ?> persona(s). <?= e($t['descripcion']) ?>.</p>
    </div>
  <?php endforeach; ?>
</section>

<div class="tabla-envoltura">
  <table class="tabla">
    <thead><tr><th>Número</th><th>Tipo</th><th class="num">Piso</th><th class="num">Capacidad</th><th>Esta noche</th><th>Próxima llegada</th></tr></thead>
    <tbody>
    <?php foreach ($habitaciones as $h):
        $oc = $h['ocupacion_hoy'] ? explode('|', $h['ocupacion_hoy']) : null; ?>
      <tr>
        <td><strong><?= e($h['numero']) ?></strong></td>
        <td><?= e($h['tipo']) ?></td>
        <td class="num"><?= (int) $h['piso'] ?></td>
        <td class="num"><?= (int) $h['capacidad'] ?></td>
        <td>
          <?php if ($h['estado'] === 'mantenimiento'): ?>
            <span class="estado estado-mantenimiento">En mantenimiento</span>
          <?php elseif ($oc): ?>
            <span class="estado estado-ocupada">Ocupada</span> <?= e($oc[1]) ?>, sale <?= e(fecha_corta($oc[2])) ?>
          <?php else: ?>
            <span class="estado estado-libre">Libre</span>
          <?php endif; ?>
        </td>
        <td><?= $h['proxima_llegada'] ? e(fecha_corta($h['proxima_llegada'])) : '<span class="tenue">Sin reservas</span>' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
