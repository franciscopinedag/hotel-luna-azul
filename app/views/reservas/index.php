<header class="encabezado">
  <div>
    <h1>Reservas</h1>
    <p class="sub"><?= count($reservas) ?> resultado(s)</p>
  </div>
  <a class="btn btn-primario" href="<?= e(url('reservas/crear')) ?>">Nueva reserva</a>
</header>

<form class="filtros" method="get" action="<?= e(BASE_URL . 'index.php') ?>">
  <input type="hidden" name="r" value="reservas/index">
  <label class="campo campo-busqueda">
    <span>Buscar</span>
    <input type="search" name="q" value="<?= e($filtros['q']) ?>" placeholder="Huésped, documento, código o habitación">
  </label>
  <label class="campo">
    <span>Estado</span>
    <select name="estado">
      <option value="">Todos</option>
      <?php foreach (Reserva::ESTADOS as $est): ?>
        <option value="<?= $est ?>" <?= $filtros['estado'] === $est ? 'selected' : '' ?>><?= ucfirst($est) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label class="campo">
    <span>Hospedados la noche del</span>
    <input type="date" name="fecha" value="<?= e($filtros['fecha']) ?>">
  </label>
  <div class="filtros-acciones">
    <button class="btn btn-secundario">Filtrar</button>
    <?php if (array_filter($filtros)): ?><a href="<?= e(url('reservas/index')) ?>">Limpiar</a><?php endif; ?>
  </div>
</form>

<div class="tabla-envoltura">
  <table class="tabla">
    <thead>
      <tr><th>Código</th><th>Huésped</th><th>Habitación</th><th>Ingreso</th><th>Salida</th><th class="num">Noches</th><th class="num">Pers.</th><th class="num">Valor</th><th>Estado</th><th><span class="sr-only">Acciones</span></th></tr>
    </thead>
    <tbody>
    <?php foreach ($reservas as $r): ?>
      <tr class="<?= $r['estado'] === 'cancelada' ? 'fila-tenue' : '' ?>">
        <td><a href="<?= e(url('reservas/ver', ['id' => $r['id']])) ?>"><?= e($r['codigo']) ?></a></td>
        <td><?= e($r['nombre_completo']) ?><small class="bajo"><?= e($r['tipo_documento'] . ' ' . $r['numero_documento']) ?></small></td>
        <td><?= e($r['habitacion']) ?><small class="bajo"><?= e($r['tipo']) ?></small></td>
        <td><?= e(fecha_corta($r['fecha_ingreso'])) ?></td>
        <td><?= e(fecha_corta($r['fecha_salida'])) ?></td>
        <td class="num"><?= (int) $r['noches'] ?></td>
        <td class="num"><?= (int) $r['num_huespedes'] ?></td>
        <td class="num"><?= e(moneda($r['valor_total'])) ?></td>
        <td><span class="estado estado-<?= e($r['estado']) ?>"><?= e(ucfirst($r['estado'])) ?></span></td>
        <td class="acciones-fila">
          <a href="<?= e(url('reservas/ver', ['id' => $r['id']])) ?>">Ver</a>
          <?php if ($r['estado'] === 'confirmada'): ?>
            <a href="<?= e(url('reservas/editar', ['id' => $r['id']])) ?>">Modificar</a>
            <button type="button" class="enlace-peligro" data-titulo="Cancelar reserva" data-motivo
                    data-confirmar="¿Cancelar la reserva <?= e($r['codigo']) ?> de <?= e($r['nombre_completo']) ?>? La habitación <?= e($r['habitacion']) ?> quedará libre."
                    data-accion="<?= e(url('reservas/cancelar')) ?>" data-id="<?= (int) $r['id'] ?>">Cancelar</button>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (!$reservas): ?>
    <p class="vacio">No hay reservas con esos filtros. <a href="<?= e(url('reservas/crear')) ?>">Crear una reserva</a>.</p>
  <?php endif; ?>
</div>
