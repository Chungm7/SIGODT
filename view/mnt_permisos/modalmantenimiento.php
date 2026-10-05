<!-- Modal para seleccionar y asignar usuarios -->
<div class="modal modal-blur fade" id="modalmantenimiento" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content shadow">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="lbltitulo">Asignar Usuarios al Área</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="alert alert-info d-flex align-items-center mb-3 py-2 px-3" role="alert">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
            <path d="M12 9h.01" />
            <path d="M11 12h1v4h1" />
          </svg>
          <div class="small">Marque los usuarios que desea vincular a esta dependencia y presione <strong>Asignar Seleccionados</strong>.</div>
        </div>

        <div class="table-responsive">
          <table id="usu_data" class="table card-table table-vcenter table-hover datatable" style="width:100%">
            <thead>
              <tr>
                <th class="text-center" style="width: 50px;">
                  <input type="checkbox" class="form-check-input" id="check_all" title="Seleccionar todos">
                </th>
                <th class="text-center" style="width: 100px;">DNI</th>
                <th>Nombre Completo</th>
                <th class="text-center" style="width: 100px;">Perfil</th>
              </tr>
            </thead>
            <tbody>
              <!-- Datos cargados dinámicamente -->
            </tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">
          Cancelar
        </button>
        <button type="button" id="btn_guardar_asignacion" onclick="registrardetalle()" class="btn btn-primary ms-auto d-inline-flex align-items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M5 12l5 5l10 -10" />
          </svg>
          <span>Asignar Seleccionados</span>
        </button>
      </div>
    </div>
  </div>
</div>
