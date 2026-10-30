<header class="encabezado">
  <div>
    <h1>Panel de recepción</h1>
    <p class="sub"><?= e(ucfirst(dia_semana(date('Y-m-d')))) ?>, <?= e(fecha_corta(date('Y-m-d'))) ?></p>
  </div>
</header>

<section class="indicadores" aria-label="Resumen del día">
  <div class="indicador indicador-principal">
    <span class="valor"><?= $porcentaje ?>%</span>
    <span class="etiqueta">de ocupación esta noche (<?= $resumen['ocupadas'] ?> de <?= $totalActivas ?> habitaciones)</span>
  </div>
  <div class="indicador"><span class="valor"><?= $resumen['llegadas'] ?></span><span class="etiqueta">llegadas hoy</span></div>
  <div class="indicador"><span class="valor"><?= $resumen['salidas'] ?></span><span class="etiqueta">salidas hoy</span></div>
  <div class="indicador"><span class="valor"><?= $resumen['proximas'] ?></span><span class="etiqueta">reservas próximas</span></div>
  <div class="indicador"><span class="valor"><?= $totalHuespedes ?></span><span class="etiqueta">huéspedes registrados</span></div>
</section>

<section class="bloque">
  <div class="bloque-cabeza">
    <h2>Tablero de ocupación de los próximos 14 días</h2>
    <p class="sub">Cada barra es una reserva confirmada. Una celda vacía es una noche libre.</p>
  </div>
  <div class="tablero-envoltura">
    <table class="tablero">
      <thead>
        <tr>
          <th scope="col" class="col-hab">Hab.</th>
          <?php foreach ($dias as $i => $dia): ?>
            <th scope="col" class="<?= $i === 0 ? 'hoy' : '' ?>">
              <span class="dia-sem"><?= e(dia_semana($dia)) ?></span><span class="dia-num"><?= date('j', strtotime($dia)) ?></span>
            </th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($habitaciones as $hab): ?>
        <tr>
          <th scope="row" class="col-hab"><?= e($hab['numero']) ?><small><?= e($hab['tipo']) ?></small></th>
          <?php
          $i = 0;
          while ($i < count($dias)):
              $dia = $dias[$i];
              $res = $ocupacion[$hab['id']][$dia] ?? null;
              if (!$res): ?>
                <td class="<?= $i === 0 ? 'hoy' : '' ?>"></td>
              <?php $i++;
              else:
                  // Une las noches consecutivas de la misma reserva en una sola barra
                  $span = 1;
                  while ($i + $span < count($dias) && (($ocupacion[$hab['id']][$dias[$i + $span]]['id'] ?? 0) === $res['id'])) {
                      $span++;
                  }
                  $continua = $res['fecha_ingreso'] < $dia; ?>
                <td colspan="<?= $span ?>" class="ocupada<?= $i === 0 ? ' hoy' : '' ?>">
                  <a class="barra<?= $continua ? ' continua' : '' ?>" href="<?= e(url('reservas/ver', ['id' => $res['id']])) ?>"
                     title="<?= e($res['codigo'] . ': ' . $res['nombre_completo'] . ', sale el ' . fecha_corta($res['fecha_salida'])) ?>">
                    <?= e(explode(' ', $res['nombre_completo'])[0]) ?>
                  </a>
                </td>
              <?php $i += $span;
              endif;
          endwhile; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="bloque">
  <div class="bloque-cabeza"><h2>Movimientos de hoy</h2></div>
  <?php if (!$movimientos): ?>
    <p class="vacio">No hay llegadas ni salidas programadas para hoy.</p>
  <?php else: ?>
    <ul class="movimientos">
      <?php foreach ($movimientos as $m):
          $llega = $m['fecha_ingreso'] === date('Y-m-d'); ?>
        <li>
          <span class="chip <?= $llega ? 'chip-llegada' : 'chip-salida' ?>"><?= $llega ? 'Llega' : 'Sale' ?></span>
          <a href="<?= e(url('reservas/ver', ['id' => $m['id']])) ?>"><?= e($m['nombre_completo']) ?></a>
          <span class="tenue">Habitación <?= e($m['habitacion']) ?> (<?= e($m['tipo']) ?>), reserva <?= e($m['codigo']) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
