<?php
$nombresAccion = [
    'crear' => 'Creó', 'actualizar' => 'Modificó', 'eliminar' => 'Eliminó', 'cancelar' => 'Canceló',
    'finalizar' => 'Registró salida de', 'inicio_sesion' => 'Inició sesión', 'cierre_sesion' => 'Cerró sesión',
    'intento_fallido' => 'Intento fallido de acceso',
];
?>
<header class="encabezado">
  <div>
    <h1>Bitácora de actividad</h1>
    <p class="sub">Registro no relacional: cada evento es un documento JSON en <code>storage/bitacora.jsonl</code></p>
  </div>
</header>

<nav class="pestanas" aria-label="Filtrar por entidad">
  <?php foreach (['' => 'Todo', 'reserva' => 'Reservas', 'huesped' => 'Huéspedes', 'usuario' => 'Accesos'] as $k => $txt): ?>
    <a href="<?= e(url('bitacora/index', $k ? ['entidad' => $k] : [])) ?>" <?= $entidad === $k ? 'class="activo" aria-current="page"' : '' ?>><?= e($txt) ?></a>
  <?php endforeach; ?>
</nav>

<?php if (!$registros): ?>
  <p class="vacio">Aún no hay actividad registrada. Cada vez que se crea, modifica, cancela o elimina un registro, aparecerá aquí.</p>
<?php else: ?>
  <ol class="bitacora">
    <?php foreach ($registros as $doc): ?>
      <li>
        <time datetime="<?= e($doc['fecha']) ?>"><?= e(date('d/m/Y H:i', strtotime($doc['fecha']))) ?></time>
        <div>
          <p><strong><?= e($doc['usuario']) ?></strong> <?= e(mb_strtolower($nombresAccion[$doc['accion']] ?? $doc['accion'])) ?>
            <?= in_array($doc['entidad'], ['reserva', 'huesped'], true) ? e($doc['entidad'] === 'reserva' ? 'la reserva' : 'el huésped') : '' ?>
            <?= e($doc['detalle']['codigo'] ?? $doc['detalle']['nombre'] ?? '') ?></p>
          <details>
            <summary>Ver documento JSON</summary>
            <pre><?= e(json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
          </details>
        </div>
      </li>
    <?php endforeach; ?>
  </ol>
<?php endif; ?>
