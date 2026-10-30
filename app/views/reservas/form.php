<?php
$accion = $modo === 'crear' ? url('reservas/guardar') : url('reservas/actualizar');
$clase = fn(string $c) => isset($errores[$c]) ? 'campo con-error' : 'campo';
$hoy = date('Y-m-d');
?>
<header class="encabezado">
  <div>
    <p class="miga"><a href="<?= e(url('reservas/index')) ?>">Reservas</a></p>
    <h1><?= e($titulo) ?></h1>
  </div>
</header>

<?php if ($errores): ?>
  <div class="alerta alerta-error" role="alert">La reserva no se guardó. Revise los campos marcados.</div>
<?php endif; ?>

<form method="post" action="<?= e($accion) ?>" class="formulario formulario-reserva" id="form-reserva" data-validar novalidate
      data-endpoint-disponibilidad="<?= e(url('api/disponibilidad')) ?>"
      data-reserva-id="<?= (int) ($r['id'] ?? 0) ?>"
      data-habitacion-actual="<?= (int) ($r['habitacion_id'] ?? 0) ?>"
      data-ingreso-original="<?= e($r['fecha_ingreso'] ?? '') ?>"
      data-max-noches="<?= (int) $maxNoches ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int) ($r['id'] ?? 0) ?>">

  <div class="reserva-columnas">
    <div>
      <fieldset>
        <legend>Huésped titular</legend>
        <label class="<?= $clase('huesped_id') ?>">
          <span>Huésped</span>
          <select name="huesped_id" required data-regla="requerido">
            <option value="">Seleccione…</option>
            <?php foreach ($huespedes as $h): ?>
              <option value="<?= (int) $h['id'] ?>" <?= (int) ($r['huesped_id'] ?? 0) === (int) $h['id'] ? 'selected' : '' ?>>
                <?= e($h['nombre_completo'] . ' (' . $h['tipo_documento'] . ' ' . $h['numero_documento'] . ')') ?>
              </option>
            <?php endforeach; ?>
          </select>
          <small class="error-campo"><?= error_campo($errores, 'huesped_id') ?></small>
        </label>
        <?php if ($modo === 'crear'): ?>
          <p class="nota">¿Es un huésped nuevo? <a href="<?= e(url('huespedes/crear', ['volver' => 'reserva'])) ?>">Regístrelo primero</a> y volverá aquí.</p>
        <?php endif; ?>
      </fieldset>

      <fieldset>
        <legend>Estadía</legend>
        <div class="rejilla">
          <label class="<?= $clase('tipo_id') ?> ancho-2">
            <span>Tipo de habitación</span>
            <select name="tipo_id" id="tipo_id" required data-regla="requerido">
              <option value="">Seleccione…</option>
              <?php foreach ($tipos as $t): ?>
                <option value="<?= (int) $t['id'] ?>" data-capacidad="<?= (int) $t['capacidad'] ?>" data-precio="<?= (float) $t['precio_noche'] ?>"
                  <?= (int) ($r['tipo_id'] ?? 0) === (int) $t['id'] ? 'selected' : '' ?>>
                  <?= e($t['nombre']) ?>, hasta <?= (int) $t['capacidad'] ?> pers., <?= e(moneda($t['precio_noche'])) ?> por noche
                </option>
              <?php endforeach; ?>
            </select>
            <small class="error-campo"><?= error_campo($errores, 'tipo_id') ?></small>
          </label>
          <label class="<?= $clase('fecha_ingreso') ?>">
            <span>Fecha de ingreso (check-in)</span>
            <input type="date" name="fecha_ingreso" id="fecha_ingreso" value="<?= e($r['fecha_ingreso'] ?? '') ?>"
                   min="<?= e(min($hoy, $r['fecha_ingreso'] ?? $hoy)) ?>" required data-regla="ingreso">
            <small class="error-campo"><?= error_campo($errores, 'fecha_ingreso') ?></small>
          </label>
          <label class="<?= $clase('fecha_salida') ?>">
            <span>Fecha de salida (check-out)</span>
            <input type="date" name="fecha_salida" id="fecha_salida" value="<?= e($r['fecha_salida'] ?? '') ?>" required data-regla="salida">
            <small class="error-campo"><?= error_campo($errores, 'fecha_salida') ?></small>
          </label>
          <label class="<?= $clase('num_huespedes') ?>">
            <span>Número de huéspedes</span>
            <input type="number" name="num_huespedes" id="num_huespedes" value="<?= e($r['num_huespedes'] ?? 1) ?>" min="1" max="10" required data-regla="personas">
            <small class="error-campo"><?= error_campo($errores, 'num_huespedes') ?></small>
          </label>
        </div>
      </fieldset>

      <fieldset class="<?= isset($errores['habitacion_id']) ? 'con-error' : '' ?>" id="bloque-habitaciones">
        <legend>Habitación</legend>
        <p class="nota" id="estado-disponibilidad" aria-live="polite">Elija el tipo de habitación y las fechas para ver qué habitaciones están libres.</p>
        <div class="habitaciones-opciones" id="habitaciones-opciones" role="radiogroup" aria-label="Habitaciones disponibles"></div>
        <small class="error-campo" id="error-habitacion"><?= error_campo($errores, 'habitacion_id') ?></small>
      </fieldset>

      <label class="campo">
        <span>Observaciones (opcional)</span>
        <textarea name="observaciones" rows="2" maxlength="255" placeholder="Hora estimada de llegada, solicitudes especiales…"><?= e($r['observaciones'] ?? '') ?></textarea>
      </label>
    </div>

    <aside class="resumen-reserva" aria-live="polite">
      <h2>Resumen</h2>
      <dl>
        <dt>Noches</dt><dd id="res-noches">—</dd>
        <dt>Tarifa por noche</dt><dd id="res-tarifa">—</dd>
        <dt>Habitación</dt><dd id="res-habitacion">—</dd>
      </dl>
      <p class="total"><span>Total estimado</span><strong id="res-total">—</strong></p>
      <button type="submit" class="btn btn-primario btn-bloque"><?= $modo === 'crear' ? 'Confirmar reserva' : 'Guardar cambios' ?></button>
      <a class="btn btn-secundario btn-bloque" href="<?= e($modo === 'crear' ? url('reservas/index') : url('reservas/ver', ['id' => $r['id']])) ?>">Volver sin guardar</a>
    </aside>
  </div>
</form>
