<div id="modaltasas" class="modal fade" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content bd-0">

            <div class="modal-header pd-y-20 pd-x-25" style="background: #0866c6;">
                <h5 id="nombreproced" class="tx-14 mg-b-10 tx-uppercase tx-inverse tx-bold" style=" font-weight: normal;  color: #ffffff; text-align: center; display: contents;">Cargando....</h5>
            </div>


            <!-- Formulario Mantenimiento -->

            <div class="modal-body">
                <input type="hidden" name="procedciudadano_id" id="procedciudadano_id" />
                <input type="hidden" class="estado_procedimiento" name="estado_procedimiento" id="estado_procedimiento" />
                <div class="pd-x-15 pd-b-15">
                    <div class="table-wrapper">
                        <table id="data_tasa" style="font-size: 12px; width: 100%; overflow-x: auto; text-align: center;" class="tabla-responsive">
                            <thead>
                                <tr>
                                    <th class="wd-5p"></th>
                                    <th class="wd-35p">Tasa</th>
                                    <th class="wd-35p">Monto</th>
                                    <th class="wd-15p"></th>
                                    <th class="wd-15p"></th>
                                    <th class="wd-15p"></th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- <button value="imprimirGrupo" id="add_button" onclick="imprimirGrupo()" class="btn btn-success tx-12 tx-uppercase pd-y-10 pd-x-20 tx-mont tx-medium">
                    <i class="fa fa-print mr-2"></i> Imprimir Grupo
                </button> -->

                <button value="pagarGrupo" id="IDpagarGrupo" onclick="pagargrupo()" class="btn btn-primary tx-12 tx-uppercase pd-y-10 pd-x-20 tx-mont tx-medium">
                    <i class="fa fa-dollar mr-2"></i> Girar en Grupo
                </button>
                <button type="button" id="btnEditarComentario" onclick="editarComentario()" class="btn btn-warning tx-12 tx-uppercase pd-y-10 pd-x-20 tx-mont tx-medium">
                    <i class="fa fa-edit mr-2"></i> Ver Ordenes de Giros
                </button>

                <div class="modal-footer">
                    <button type="reset" name="action" value="add" onclick="recargarTabla()" class="btn btn-outline-primary tx-11 tx-uppercase pd-y-12 pd-x-25 tx-mont tx-medium" aria-hidden="true" data-dismiss="modal"><i class="fa fa-check"></i> Aceptar</button>
                </div>
            </div>
        </div>
    </div>
</div>