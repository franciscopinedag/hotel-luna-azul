<?php
$v = fn(string $c) => e($h[$c] ?? '');
$accion = $modo === 'crear' ? url('huespedes/guardar') : url('huespedes/actualizar');
$clase = fn(string $c) => isset($errores[$c]) ? 'campo con-error' : 'campo';
?>
<header class="encabezado">
  <div>
    <p class="miga"><a href="<?= e(url('huespedes/index')) ?>">Huéspedes</a></p>
    <h1><?= e($titulo) ?></h1>
  </div>
</header>

<?php if ($errores): ?>
  <div class="alerta alerta-error" role="alert">Revise los <?= count($errores) ?> campo(s) marcados antes de guardar.</div>
<?php endif; ?>

<form method="post" action="<?= e($accion) ?>" class="formulario" id="form-huesped" data-validar novalidate
      data-endpoint-documento="<?= e(url('api/documento')) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int) ($h['id'] ?? 0) ?>">
  <input type="hidden" name="volver" value="<?= e($volver) ?>">

  <fieldset>
    <legend>Identificación</legend>
    <div class="rejilla">
      <label class="<?= $clase('nombre_completo') ?> ancho-2">
        <span>Nombre completo</span>
        <input type="text" name="nombre_completo" value="<?= $v('nombre_completo') ?>" maxlength="120" required data-regla="nombre" autocomplete="name">
        <small class="error-campo"><?= error_campo($errores, 'nombre_completo') ?></small>
      </label>
      <label class="<?= $clase('tipo_documento') ?>">
        <span>Tipo de documento</span>
        <select name="tipo_documento" id="tipo_documento" required data-regla="requerido">
          <option value="">Seleccione…</option>
          <?php foreach (Huesped::TIPOS_DOCUMENTO as $t): ?>
            <option value="<?= $t ?>" <?= ($h['tipo_documento'] ?? '') === $t ? 'selected' : '' ?>><?= e(etiqueta_documento($t)) ?></option>
          <?php endforeach; ?>
        </select>
        <small class="error-campo"><?= error_campo($errores, 'tipo_documento') ?></small>
      </label>
      <label class="<?= $clase('numero_documento') ?>">
        <span>Número de documento</span>
        <input type="text" name="numero_documento" id="numero_documento" value="<?= $v('numero_documento') ?>" maxlength="20" required data-regla="documento">
        <small class="error-campo"><?= error_campo($errores, 'numero_documento') ?></small>
      </label>
      <label class="<?= $clase('edad') ?>">
        <span>Edad</span>
        <input type="number" name="edad" value="<?= $v('edad') ?>" min="18" max="110" required data-regla="edad" inputmode="numeric">
        <small class="error-campo"><?= error_campo($errores, 'edad') ?></small>
      </label>
    </div>
  </fieldset>

  <fieldset>
    <legend>Contacto</legend>
    <div class="rejilla">
      <label class="<?= $clase('direccion') ?> ancho-2">
        <span>Dirección</span>
        <input type="text" name="direccion" value="<?= $v('direccion') ?>" maxlength="150" required data-regla="direccion" autocomplete="street-address">
        <small class="error-campo"><?= error_campo($errores, 'direccion') ?></small>
      </label>
      <label class="<?= $clase('telefono') ?>">
        <span>Teléfono</span>
        <input type="tel" name="telefono" value="<?= $v('telefono') ?>" maxlength="15" required data-regla="telefono" inputmode="numeric" placeholder="6015551234">
        <small class="error-campo"><?= error_campo($errores, 'telefono') ?></small>
      </label>
      <label class="<?= $clase('celular') ?>">
        <span>Celular</span>
        <input type="tel" name="celular" value="<?= $v('celular') ?>" maxlength="15" required data-regla="celular" inputmode="numeric" placeholder="3001234567">
        <small class="error-campo"><?= error_campo($errores, 'celular') ?></small>
      </label>
      <label class="<?= $clase('correo') ?> ancho-2">
        <span>Correo electrónico</span>
        <input type="email" name="correo" value="<?= $v('correo') ?>" maxlength="120" required data-regla="correo" autocomplete="email">
        <small class="error-campo"><?= error_campo($errores, 'correo') ?></small>
      </label>
    </div>
  </fieldset>

  <fieldset>
    <legend>Contacto de emergencia</legend>
    <div class="rejilla">
      <label class="<?= $clase('contacto_emergencia') ?> ancho-2">
        <span>Nombre del contacto</span>
        <input type="text" name="contacto_emergencia" value="<?= $v('contacto_emergencia') ?>" maxlength="120" required data-regla="contacto">
        <small class="error-campo"><?= error_campo($errores, 'contacto_emergencia') ?></small>
      </label>
      <label class="<?= $clase('parentesco_emergencia') ?>">
        <span>Parentesco</span>
        <select name="parentesco_emergencia" required data-regla="requerido">
          <option value="">Seleccione…</option>
          <?php foreach (Huesped::PARENTESCOS as $p): ?>
            <option <?= ($h['parentesco_emergencia'] ?? '') === $p ? 'selected' : '' ?>><?= e($p) ?></option>
          <?php endforeach; ?>
        </select>
        <small class="error-campo"><?= error_campo($errores, 'parentesco_emergencia') ?></small>
      </label>
    </div>
  </fieldset>

  <div class="acciones">
    <a class="btn btn-secundario" href="<?= e(url('huespedes/index')) ?>">Cancelar</a>
    <button type="submit" class="btn btn-primario"><?= $modo === 'crear' ? 'Registrar huésped' : 'Guardar cambios' ?></button>
  </div>
</form>

<?php if ($modo === 'editar'): ?>
<section class="bloque">
  <div class="bloque-cabeza"><h2>Historial de reservas</h2></div>
  <?php if (!$historial): ?>
    <p class="vacio">Este huésped aún no tiene reservas. <a href="<?= e(url('reservas/crear', ['huesped_id' => $h['id']])) ?>">Crear la primera</a>.</p>
  <?php else: ?>
    <div class="tabla-envoltura">
      <table class="tabla">
        <thead><tr><th>Código</th><th>Habitación</th><th>Ingreso</th><th>Salida</th><th>Estado</th></tr></thead>
        <tbody>
        <?php foreach ($historial as $r): ?>
          <tr>
            <td><a href="<?= e(url('reservas/ver', ['id' => $r['id']])) ?>"><?= e($r['codigo']) ?></a></td>
            <td><?= e($r['habitacion'] . ' (' . $r['tipo'] . ')') ?></td>
            <td><?= e(fecha_corta($r['fecha_ingreso'])) ?></td>
            <td><?= e(fecha_corta($r['fecha_salida'])) ?></td>
            <td><span class="estado estado-<?= e($r['estado']) ?>"><?= e(ucfirst($r['estado'])) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php endif; ?>
