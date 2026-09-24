
<!-- Modal de Pago -->
<div class="modal modal-blur fade" id="modal-payment" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-sm modal-dialog-centered" role="document" >
    <div class="modal-content" style="box-shadow: 2px 4px 6px 3px #0000006b; border-radius: 0px 0px 10px 10px ">
      <button type="button" class="btn-close" data-bs-dismiss="modal" id="close_modal" aria-label="Close"></button>
      <div class="modal-status bg-primary"></div>
      <div class="modal-body text-center py-4">
        <h3 id="modal-payment-title">Confirmar Orden de Giro</h3>
        <div id="modal-payment-message" class="text-secondary mb-3">
          ¿Está seguro de que desea realizar el Orden de Giro para este registro?
        </div>
        <!-- Input para el comentario -->
        <div class="mb-3">
          <label for="comentarioInput" class="form-label">Comentario</label>
          <input type="text" class="form-control" id="comentarioInput" placeholder="Ingrese un comentario">
        </div>
        <!-- Spinner oculto por defecto -->
        <div id="modal-spinner" class="d-none mt-3">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <div class="w-100">
          <div class="row">
            <div class="col">
              <a href="#" id="btn-cancel" class="btn w-100" data-bs-dismiss="modal">Cancelar</a>
            </div>
            <div class="col">
              <a href="#" id="btn-action" class="btn btn-primary w-100">Aceptar</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
