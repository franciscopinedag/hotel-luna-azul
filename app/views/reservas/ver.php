<header class="encabezado">
  <div>
    <p class="miga"><a href="<?= e(url('reservas/index')) ?>">Reservas</a></p>
    <h1>Reserva <?= e($r['codigo']) ?></h1>
    <p><span class="estado estado-<?= e($r['estado']) ?>"><?= e(ucfirst($r['estado'])) ?></span></p>
  </div>
  <div class="acciones no-imprimir">
    <button type="button" class="btn btn-secundario" onclick="window.print()">Imprimir comprobante</button>
    <?php if ($r['estado'] === 'confirmada'): ?>
      <a class="btn btn-secundario" href="<?= e(url('reservas/editar', ['id' => $r['id']])) ?>">Modificar</a>
      <?php if ($r['fecha_ingreso'] <= date('Y-m-d')): ?>
        <button type="button" class="btn btn-secundario" data-titulo="Registrar salida" data-boton="Registrar salida"
                data-confirmar="¿Registrar el check-out de <?= e($r['nombre_completo']) ?>? La reserva pasará a finalizada."
                data-accion="<?= e(url('reservas/finalizar')) ?>" data-id="<?= (int) $r['id'] ?>">Registrar salida</button>
      <?php endif; ?>
      <button type="button" class="btn btn-peligro" data-titulo="Cancelar reserva" data-motivo data-boton="Cancelar reserva"
              data-confirmar="¿Cancelar la reserva <?= e($r['codigo']) ?>? La habitación <?= e($r['habitacion']) ?> quedará libre para esas fechas."
              data-accion="<?= e(url('reservas/cancelar')) ?>" data-id="<?= (int) $r['id'] ?>">Cancelar reserva</button>
    <?php endif; ?>
  </div>
</header>

<article class="comprobante">
  <div class="comprobante-fechas">
    <div><span class="tenue">Check-in</span><strong><?= e(fecha_corta($r['fecha_ingreso'])) ?></strong><span class="tenue"><?= e(dia_semana($r['fecha_ingreso'])) ?>, desde las 3:00 p. m.</span></div>
    <div class="noches-centro"><?= (int) $r['noches'] ?> noche(s)</div>
    <div><span class="tenue">Check-out</span><strong><?= e(fecha_corta($r['fecha_salida'])) ?></strong><span class="tenue"><?= e(dia_semana($r['fecha_salida'])) ?>, hasta la 1:00 p. m.</span></div>
  </div>

  <div class="comprobante-datos">
    <section>
      <h2>Huésped titular</h2>
      <dl>
        <dt>Nombre</dt><dd><a href="<?= e(url('huespedes/editar', ['id' => $r['huesped_id']])) ?>"><?= e($r['nombre_completo']) ?></a></dd>
        <dt>Documento</dt><dd><?= e(etiqueta_documento($r['tipo_documento']) . ' ' . $r['numero_documento']) ?></dd>
        <dt>Celular</dt><dd><?= e($r['celular']) ?></dd>
        <dt>Correo</dt><dd><?= e($r['correo']) ?></dd>
      </dl>
    </section>
    <section>
      <h2>Alojamiento</h2>
      <dl>
        <dt>Habitación</dt><dd><?= e($r['habitacion']) ?>, piso <?= (int) $r['piso'] ?></dd>
        <dt>Tipo</dt><dd><?= e($r['tipo']) ?> (máx. <?= (int) $r['capacidad'] ?> pers.)</dd>
        <dt>Huéspedes</dt><dd><?= (int) $r['num_huespedes'] ?></dd>
        <dt>Tarifa por noche</dt><dd><?= e(moneda($r['precio_noche'])) ?></dd>
      </dl>
    </section>
  </div>

  <p class="total comprobante-total"><span>Valor total de la estadía</span><strong><?= e(moneda($r['valor_total'])) ?></strong></p>

  <?php if ($r['observaciones']): ?>
    <p class="observaciones"><strong>Observaciones:</strong> <?= e($r['observaciones']) ?></p>
  <?php endif; ?>
  <p class="tenue pie-comprobante">Registrada por <?= e($r['registrada_por'] ?? 'sistema') ?> el <?= e(fecha_corta($r['created_at'])) ?>. Última modificación: <?= e(fecha_corta($r['updated_at'])) ?>.</p>
</article>
