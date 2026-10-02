var usu_id = $('#usu_idx').val();

function init() {
    $("#tupa_form").on("submit", function (e) {
        guardaryeditar(e);
    });
}

function guardaryeditar(e) {
    e.preventDefault();
    var formData = new FormData($("#tupa_form")[0]);
    $.ajax({
        url: "../../controller/tupa.php?op=guardaryeditar",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function (data) {
            cargarListaTupa();
            $('#modalmantenimiento').modal('hide');

            Swal.fire({
                title: 'Correcto!',
                text: 'El documento se guardó correctamente',
                icon: 'success',
                confirmButtonText: 'Aceptar'
            });
        },
        error: function () {
            Swal.fire({
                title: 'Error!',
                text: 'Ocurrió un error al procesar el documento',
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
        }
    });
}

function cargarListaTupa() {
    $.post("../../controller/tupa.php?op=listar", function (response) {
        var res = JSON.parse(response);
        var data = res.aaData || [];
        var html = "";

        if (data.length === 0) {
            html = `
            <div class="col-12">
                <div class="card card-dashed text-center p-5">
                    <div class="empty">
                        <div class="empty-icon text-muted mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-off" width="48" height="48" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="3" y1="3" x2="21" y2="21" /><path d="M15 3h-8a2 2 0 0 0 -2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 1.996 -1.866" /><path d="M14 3v4a1 1 0 0 0 1 1h4" /></svg>
                        </div>
                        <p class="empty-title h3">No hay documentos registrados</p>
                        <p class="empty-subtitle text-muted">Comience registrando un nuevo TUPA o TUSNE usando el botón superior.</p>
                    </div>
                </div>
            </div>`;
        } else {
            data.forEach(function (item) {
                var isActivo = (item.est === 2);
                var isBloqueado = (item.tupa_block === '1');
                var tipoDoc = item.tipo_doc || 'TUPA';

                var botonBloqueo = isBloqueado
                    ? `<button type="button" class="btn btn-outline-warning btn-icon" onclick="desbloquear_tupa(${item.tupa_id})" title="Desbloquear para permitir edición">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-lock-open" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 11m0 2a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z" /><path d="M12 16m-1 0a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" /><path d="M8 11v-5a4 4 0 0 1 8 0" /></svg>
                       </button>`
                    : `<button type="button" class="btn btn-outline-secondary btn-icon" onclick="blockear(${item.tupa_id})" title="Bloquear contra modificaciones">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-lock" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 11m0 2a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z" /><path d="M12 16m-1 0a1 1 0 1 0 2 0a1 1 0 0 0 -2 0" /><path d="M8 11v-4a4 4 0 1 1 8 0v4" /></svg>
                       </button>`;

                var botonActivar = isActivo
                    ? `<button type="button" class="btn btn-outline-danger btn-icon" onclick="desactivar(${item.tupa_id})" title="Desactivar vigencia">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-power" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 6a7.75 7.75 0 1 0 10 0" /><line x1="12" y1="4" x2="12" y2="12" /></svg>
                       </button>`
                    : `<button type="button" class="btn btn-outline-success btn-icon" onclick="activar(${item.tupa_id})" title="Activar vigencia">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>
                       </button>`;

                var puedeEliminar = (!isActivo && !isBloqueado);
                var botonEliminar = puedeEliminar
                    ? `<button type="button" class="btn btn-outline-danger btn-icon" onclick="eliminar(${item.tupa_id})" title="Eliminar documento">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                       </button>`
                    : `<button type="button" class="btn btn-outline-secondary btn-icon" disabled title="No se puede eliminar un documento activo o bloqueado">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                       </button>`;

                html += `
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card card-stacked shadow-sm h-100">
                        <div class="card-status-top ${isActivo ? 'bg-success' : 'bg-secondary'}"></div>
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge ${tipoDoc === 'TUPA' ? 'bg-blue-lt' : 'bg-purple-lt'} fw-bold">${tipoDoc}</span>
                                <span class="badge bg-secondary-lt fw-semibold">Año ${item.tupa_año}</span>
                            </div>
                            <h3 class="card-title text-truncate mb-2" title="${item.tupa_nom}">
                                ${item.tupa_nom}
                            </h3>
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="badge ${isActivo ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger'}">
                                    <span class="status-dot status-dot-animated ${isActivo ? 'bg-success' : 'bg-danger'} me-1"></span>
                                    ${isActivo ? 'Vigente' : 'Inactivo'}
                                </span>
                                <span class="badge ${isBloqueado ? 'bg-warning-lt text-warning' : 'bg-teal-lt text-teal'}">
                                    ${isBloqueado ? 'Bloqueado' : 'En Edición'}
                                </span>
                            </div>
                            <p class="text-muted small mb-0">
                                ${isBloqueado ? 'Tarifario protegido contra cambios accidentales.' : 'Permite configurar y modificar procedimientos y tasas asociadas.'}
                            </p>
                        </div>
                        <div class="card-footer bg-transparent d-flex justify-content-between align-items-center pt-2 pb-2">
                            <small class="text-muted">Acciones:</small>
                            <div class="btn-group btn-group-sm" role="group">
                                ${botonBloqueo}
                                <button type="button" class="btn btn-outline-primary btn-icon" onclick="editar(${item.tupa_id})" title="Editar datos">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-edit" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg>
                                </button>
                                ${botonActivar}
                                <button type="button" class="btn btn-outline-info btn-icon" onclick="duplicar(${item.tupa_id})" title="Duplicar TUPA con tasas">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-copy" width="18" height="18" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><rect x="8" y="8" width="12" height="12" rx="2" /><path d="M16 8v-2a2 2 0 0 0 -2 -2h-8a2 2 0 0 0 -2 2v8a2 2 0 0 0 2 2h2" /></svg>
                                </button>
                                ${botonEliminar}
                            </div>
                        </div>
                    </div>
                </div>`;
            });
        }

        $('#lista_tupa').html(html);
    });
}

$(document).ready(function () {
    cargarListaTupa();
});

function blockear(tupa_id) {
    Swal.fire({
        title: "¿Bloquear documento?",
        text: "Los operadores no podrán agregar o editar procedimientos y tasas de este documento mientras permanezca bloqueado.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, bloquear",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#f59f00"
    }).then((result) => {
        if (result.value) {
            $.post("../../controller/tupa.php?op=bloquear", { tupa_id: tupa_id }, function () {
                cargarListaTupa();
                Swal.fire({
                    title: 'Bloqueado!',
                    text: 'El documento fue bloqueado correctamente',
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                });
            });
        }
    });
}

function desbloquear_tupa(tupa_id) {
    Swal.fire({
        title: "¿Desbloquear documento?",
        text: "Se habilitará la adición y edición de procedimientos y tasas para este documento.",
        icon: "info",
        showCancelButton: true,
        confirmButtonText: "Sí, desbloquear",
        cancelButtonText: "Cancelar"
    }).then((result) => {
        if (result.value) {
            $.post("../../controller/tupa.php?op=desbloquear_tupa", { tupa_id: tupa_id }, function () {
                cargarListaTupa();
                Swal.fire({
                    title: 'Desbloqueado!',
                    text: 'El documento fue desbloqueado para edición',
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                });
            });
        }
    });
}

function duplicar(tupa_id) {
    Swal.fire({
        title: "¿Duplicar documento?",
        text: "Se creará una copia íntegra con todos los procedimientos, tasas y montos asignados.",
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Sí, duplicar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#206bc4"
    }).then((result) => {
        if (result.value) {
            $.post("../../controller/tupa.php?op=duplicar", { tupa_id: tupa_id }, function () {
                cargarListaTupa();
                Swal.fire({
                    title: 'Duplicado!',
                    text: 'El documento fue duplicado correctamente con su catálogo',
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                });
            });
        }
    });
}

function activar(tupa_id) {
    Swal.fire({
        title: "¿Activar documento?",
        text: "El documento pasará a estar vigente para emisión de órdenes de giro.",
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Sí, activar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#2fb344"
    }).then((result) => {
        if (result.value) {
            $.post("../../controller/tupa.php?op=activar_tupa", { tupa_id: tupa_id }, function () {
                cargarListaTupa();
                Swal.fire({
                    title: 'Activado!',
                    text: 'El documento se encuentra ahora vigente',
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                });
            });
        }
    });
}

function desactivar(tupa_id) {
    Swal.fire({
        title: "¿Desactivar documento?",
        text: "Los operadores no podrán emitir nuevos giros con este catálogo.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, desactivar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#d63939"
    }).then((result) => {
        if (result.value) {
            $.post("../../controller/tupa.php?op=desactivar_tupa", { tupa_id: tupa_id }, function () {
                cargarListaTupa();
                Swal.fire({
                    title: 'Desactivado!',
                    text: 'El documento fue desactivado',
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                });
            });
        }
    });
}

function editar(tupa_id) {
    $.post("../../controller/tupa.php?op=mostrar", { tupa_id: tupa_id }, function (data) {
        data = JSON.parse(data);
        $('#tupa_id').val(data.tupa_id);
        $('#tupa_nom').val(data.tupa_nom);
        $('#tupa_año').val(data.tupa_año);
        $('#tupa_tipo').val(data.tupa_tipo || data.tipo_doc);
        $('#lbltitulo').html('Editar Documento');
        $('#modalmantenimiento').modal('show');
    });
}

function eliminar(tupa_id) {
    Swal.fire({
        title: "¿Eliminar documento?",
        text: "Esta acción dará de baja definitiva al documento.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#d33"
    }).then((result) => {
        if (result.value) {
            $.post("../../controller/tupa.php?op=eliminar", { tupa_id: tupa_id }, function () {
                cargarListaTupa();
                Swal.fire({
                    title: 'Eliminado!',
                    text: 'El documento fue eliminado correctamente',
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                });
            });
        }
    });
}

function nuevo() {
    $('#tupa_id').val('');
    $('#tupa_tipo').val('TUPA');
    $('#tupa_año').val('2026');
    $('#lbltitulo').html('Nuevo Documento');
    $('#tupa_form')[0].reset();
    $('#modalmantenimiento').modal('show');
}

init();
