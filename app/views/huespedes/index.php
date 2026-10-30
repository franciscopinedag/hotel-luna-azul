<header class="encabezado">
  <div>
    <h1>Huéspedes</h1>
    <p class="sub" id="conteo-huespedes"><?= count($huespedes) ?> registrado(s)</p>
  </div>
  <a class="btn btn-primario" href="<?= e(url('huespedes/crear')) ?>">Registrar huésped</a>
</header>

<form class="filtros" method="get" action="<?= e(BASE_URL . 'index.php') ?>" role="search">
  <input type="hidden" name="r" value="huespedes/index">
  <label class="campo campo-busqueda">
    <span class="sr-only">Buscar huésped</span>
    <input type="search" name="q" id="buscar-huesped" value="<?= e($q) ?>" placeholder="Buscar por nombre, documento, correo, celular o ciudad"
           data-endpoint="<?= e(url('api/huespedes')) ?>" autocomplete="off">
  </label>
  <noscript><button class="btn btn-secundario">Buscar</button></noscript>
</form>

<div class="tabla-envoltura">
  <table class="tabla" id="tabla-huespedes"
         data-url-editar="<?= e(url('huespedes/editar')) ?>"
         data-url-eliminar="<?= e(url('huespedes/eliminar')) ?>"
         data-url-reservar="<?= e(url('reservas/crear')) ?>">
    <thead>
      <tr><th>Nombre completo</th><th>Documento</th><th>Celular</th><th>Correo</th><th class="num">Edad</th><th class="num">Reservas</th><th><span class="sr-only">Acciones</span></th></tr>
    </thead>
    <tbody>
    <?php foreach ($huespedes as $h): ?>
      <tr>
        <td><?= e($h['nombre_completo']) ?></td>
        <td><span class="doc"><?= e($h['tipo_documento']) ?></span> <?= e($h['numero_documento']) ?></td>
        <td><?= e($h['celular']) ?></td>
        <td><?= e($h['correo']) ?></td>
        <td class="num"><?= (int) $h['edad'] ?></td>
        <td class="num"><?= (int) $h['total_reservas'] ?></td>
        <td class="acciones-fila">
          <a href="<?= e(url('reservas/crear', ['huesped_id' => $h['id']])) ?>">Reservar</a>
          <a href="<?= e(url('huespedes/editar', ['id' => $h['id']])) ?>">Editar</a>
          <button type="button" class="enlace-peligro"
                  data-confirmar="¿Eliminar a <?= e($h['nombre_completo']) ?>? Esta acción no se puede deshacer."
                  data-accion="<?= e(url('huespedes/eliminar')) ?>" data-id="<?= (int) $h['id'] ?>" data-titulo="Eliminar huésped">Eliminar</button>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <p class="vacio" id="sin-resultados" <?= $huespedes ? 'hidden' : '' ?>>No hay huéspedes que coincidan. <a href="<?= e(url('huespedes/crear')) ?>">Registrar uno nuevo</a>.</p>
</div>
