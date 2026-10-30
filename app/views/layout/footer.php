  </main>
</div>

<!-- Diálogo de confirmación reutilizable (eliminar, cancelar, finalizar) -->
<dialog id="dlg-confirmar" class="dialogo">
  <form method="dialog">
    <h2 id="dlg-titulo">Confirmar</h2>
    <p id="dlg-mensaje"></p>
    <label class="campo" id="dlg-motivo-campo" hidden>
      <span>Motivo (opcional)</span>
      <input type="text" id="dlg-motivo" maxlength="120">
    </label>
    <div class="acciones">
      <button value="no" class="btn btn-secundario">Volver</button>
      <button value="si" class="btn btn-peligro" id="dlg-aceptar">Confirmar</button>
    </div>
  </form>
</dialog>

<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
