<!-- Modal para seleccionar usuarios -->
<div id="modalmantenimiento" class="modal modal-blur fade" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-md modal-dialog-centered" role="document"> <!-- Ajusté el tamaño del modal -->
    <div class="modal-content bd-0">
      <div class="modal-header pd-y-20 pd-x-25">
         <h5 class="modal-title" id="lbltitulo">Seleccionar usuarios:</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
       
      </div>
      
      <!-- Formulario Mantenimiento -->
      <div class="modal-body">
        <div class="table-responsive">
          <table id="usu_data" class="table table-bordered table-hover" style="width:100%">
            <thead>
              <tr>
                <th class="wd-10p text-center">Seleccionar</th> <!-- Checkbox -->
                <th class="wd-20p text-center">DNI</th>
                <th class="wd-30p text-center">Nombre</th>
                <th class="wd-20p text-center">Rol</th>
              </tr>
            </thead>
            <tbody>
              <!-- Aquí van los datos dinámicamente -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- Footer del modal -->
      <div class="modal-footer">
        <button type="button" name="action" onclick="registrardetalle()" class="btn btn-outline-primary tx-11 tx-uppercase pd-y-12 pd-x-25 tx-mont tx-medium">
          <i class="fa fa-check"></i> Guardar
        </button>
        <button type="reset" class="btn btn-outline-secondary tx-11 tx-uppercase pd-y-12 pd-x-25 tx-mont tx-medium" aria-label="Close" aria-hidden="true" data-dismiss="modal">
          <i class="fa fa-close"></i> Cancelar
        </button>
      </div>
    </div>
  </div>
</div>
