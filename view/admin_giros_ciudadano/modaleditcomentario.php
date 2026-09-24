<div id="modaleditcomentario" class="modal fade" data-backdrop="static" data-keyboard="false" style="overflow-y: scroll;">
   
<div class="modal-dialog modal-lg" role="document" style="border: 1px solid #ccc; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1)">
        <div class="modal-content bd-0">
            <div class="modal-header pd-y-20 pd-x-25" style="background: #6f42c1;">
                <h5 id="nombreproced" class="tx-14 mg-b-10 tx-uppercase tx-inverse tx-bold" style=" font-weight: normal;  color: #ffffff; text-align: center; display: contents;">Registro de Ordenes de Giros</h5>
            </div>
            <!-- Modal Body -->
            <div class="modal-body">
                <input type="hidden" name="procedciud_id" id="procedciud_id" />
                <!-- Table to display group plates -->
                <div class="pd-x-15 pd-b-15">
                    <div class="table-wrapper">
                        <table id="data_ordenes" class="table display responsive" style="font-size: 12px;">
                            <thead>
                                <tr>
                                    <th>Orden de Giro</th>
                                    <th>Fecha</th>
                                    <th>Comentario</th>
                                    <th>Editar</th>
                                    <th>Imprimir</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button type="button" style="background: #6f42c1;" class="btn btn-primary" onclick="$('#modaleditcomentario').modal('hide');">Aceptar</button>
                </div>
            </div>
        </div>

    </div>
</div>