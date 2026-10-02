<div id="modalmantenimiento" class="modal modal-blur fade" data-backdrop="static" data-keyboard="false" tabindex="-100" style="padding-left: 0px;" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-md modal-dialog-centered" role="document"> <!-- Reducido a modal-md -->
        <div class="modal-content">
            <div class="modal-header pd-y-20 pd-x-25">
                <h5 class="modal-title" id="lbltitulo">Nuevo Registro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <!-- Formulario Mantenimiento -->
            <form method="post" id="tupa_form">
                <div class="modal-body">
                    <input type="hidden" name="tupa_id" id="tupa_id" />

                    <div class="row">
                        <div class="col-lg-12"> <!-- Cambiado a col-lg-12 mt-3 -->
                            <div class="form-group">
                                <label for="tupa_nom" class="form-control-label">Nombre: <span class="tx-danger">*</span></label>
                                <input class="form-control tx-uppercase" id="tupa_nom" type="text" name="tupa_nom" data-autofocus required />
                            </div>
                        </div>
                        <div class="col-lg-12 mt-3"> <!-- Cambiado a col-lg-12 mt-3 -->
                            <div class="form-group">
                                <label for="tupa_tipo" class="form-control-label">Tipo: <span class="tx-danger">*</span></label>
                                <select class="form-control" id="tupa_tipo" name="tupa_tipo" required>
                                    <option value="TUPA">TUPA</option>
                                    <option value="TUSNE">TUSNE</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-12 mt-3"> <!-- Cambiado a col-lg-12 mt-3 -->
                            <div class="form-group">
                                <label for="tupa_año" class="form-control-label">Año: <span class="tx-danger">*</span></label>
                                <select class="form-control" id="tupa_año" name="tupa_año" required>
                                    <option value="2025">2025</option>
                                    <option value="2024">2024</option>
                                    <option value="2023">2023</option>
                                    <option value="2022">2022</option>
                                    <option value="2021">2021</option>
                                    <option value="2020">2020</option>
                                    <option value="2019">2019</option>
                                    <option value="2018">2018</option>
                                    <!-- Añadir más años según sea necesario -->
                                </select>
                            </div>
                        </div>
                    </div>




                </div>

                <div class="modal-footer">
                    <button type="submit" name="action" value="add" class="btn btn-primary btn-5 ms-auto">
                        <i class="fa fa-check"></i> Guardar
                    </button>
                    <button type="reset" class="btn btn-secondary btn-3" aria-label="Close" aria-hidden="true" data-bs-dismiss="modal">
                        <i class="fa fa-close"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>