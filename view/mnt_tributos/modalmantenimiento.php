<div id="modalmantenimiento" class="modal modal-blur fade" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title fw-bold text-primary mb-0">Seleccionar Tasas para el Procedimiento</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <input type="hidden" name="proced_id" id="proced_id"/>
            <div class="modal-body p-0">
                <div class="p-3 text-muted border-bottom bg-light">
                    Marque las tasas que desea vincular a este procedimiento. Una vez vinculadas, podrá configurar su orden, monto e imputación contable.
                </div>
                <div class="p-3">
                    <div class="table-responsive">
                        <table id="tasa_data" class="table table-vcenter table-striped table-hover mb-0" style="width: 100%;">
                            <thead class="table-light">
                                <tr>
                                    <th class="w-1 text-center">Seleccionar</th>
                                    <th>Denominación de la Tasa</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Contenido dinámico -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-x" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                    Cancelar
                </button>
                <button type="button" name="action" onclick="registrardetalle()" class="btn btn-primary ms-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M5 12l5 5l10 -10" />
                    </svg>
                    Asignar Tasas
                </button>
            </div>
        </div>
    </div>
</div>