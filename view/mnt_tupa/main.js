
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

            $('#tupa_data').DataTable().ajax.reload();
            $('#modalmantenimiento').modal('hide');

            Swal.fire({
                title: 'Correcto!',
                text: 'Se Registro Correctamente',
                icon: 'success',
                confirmButtonText: 'Aceptar'
            })
        }
    });
}

$(document).ready(function () {
    $.post("../../controller/tupa.php?op=listar", function (response) {
        const data = JSON.parse(response).aaData;
        let html = "";

        data.forEach(item => {
            const estadoClass = item[3] === 'Activo' ? 'bg-success-lt' : 'bg-danger-lt';
            const bloqueoClass = item[4] === 'SI' ? 'bg-warning-lt' : 'bg-gray-lt';
            const bloqueoText = item[4] === 'SI' ? 'Bloqueado' : 'Libre';


            html += `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h3 class="card-title m-0">${item[0]}</h3>
                            <div class="acciones-iconos">
                                ${item[5]}
                            </div>
                        </div>
                        <p class="text-muted">Año: <strong>${item[1]}</strong> | Tipo: ${item[2]}</p>
                    </div>
                    <div class="card-footer d-flex justify-content-start gap-2">
                        <span class="badge ${estadoClass}">${item[3]}</span>
                        <span class="badge ${bloqueoClass}">${bloqueoText}</span>
                    </div>
                </div>
            </div>`;
        });

        $('#lista_tupa').html(html);
    });
});

function blockear(tupa_id) {
    swal.fire({
        title: "Bloquear?",
        text: "Las personas no podran editar los proceds o las tasas del TUPA mientras esta bloqueado.",
        icon: "error",
        confirmButtonText: "Si",
        showCancelButton: true,
        cancelButtonText: "No",
    }).then((result) => {
        if (result.value) {
            $.post("../../controller/tupa.php?op=bloquear", { tupa_id: tupa_id }, function (data) {
                $('#tupa_data').DataTable().ajax.reload();

                Swal.fire({
                    title: 'Correcto!',
                    text: 'Se a Bloqueado Correctamente',
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                })
            });
        }
    });
}
function desbloquear_tupa(tupa_id) {
    swal.fire({
        title: "Desbloquear?",
        text: "Las personas podran editar los proceds o las tasas del TUPA mientras esta desbloqueado.",
        icon: "error",
        confirmButtonText: "Si",
        showCancelButton: true,
        cancelButtonText: "No",
    }).then((result) => {
        if (result.value) {
            $.post("../../controller/tupa.php?op=desbloquear_tupa", { tupa_id: tupa_id }, function (data) {
                $('#tupa_data').DataTable().ajax.reload();

                Swal.fire({
                    title: 'Correcto!',
                    text: 'Se a Desbloqueado Correctamente',
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                })
            });
        }
    });
}
function duplicar(tupa_id) {
    swal.fire({
        title: "Duplicar?",
        text: "Todos los procedimientos tasas y montos se agregaran al nuevo TUPA",
        icon: "error",
        confirmButtonText: "Si",
        showCancelButton: true,
        cancelButtonText: "No",
    }).then((result) => {
        if (result.value) {
            $.post("../../controller/tupa.php?op=duplicar", { tupa_id: tupa_id }, function (data) {
                $('#tupa_data').DataTable().ajax.reload();
                Swal.fire({
                    title: 'Correcto!',
                    text: 'Se a Duplicado Correctamente',
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                })
            });
        }
    });
}
function activar(tupa_id) {
    swal.fire({
        title: "Activar?",
        text: "Las personas podran Usar los proceds o las tasas del TUPA mientras esta Activo.",
        icon: "error",
        confirmButtonText: "Si",
        showCancelButton: true,
        cancelButtonText: "No",
    }).then((result) => {
        if (result.value) {
            $.post("../../controller/tupa.php?op=activar_tupa", { tupa_id: tupa_id }, function (data) {
                $('#tupa_data').DataTable().ajax.reload();

                Swal.fire({
                    title: 'Correcto!',
                    text: 'Se a Activó Correctamente',
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                })
            });
        }
    });
}
function desactivar(tupa_id) {
    swal.fire({
        title: "Desactivar?",
        text: "Las Giradores no podran Usar los proceds o las tasas del TUPA mientras esta DEsactivado",
        icon: "error",
        confirmButtonText: "Si",
        showCancelButton: true,
        cancelButtonText: "No",
    }).then((result) => {
        if (result.value) {
            $.post("../../controller/tupa.php?op=desactivar_tupa", { tupa_id: tupa_id }, function (data) {
                $('#tupa_data').DataTable().ajax.reload();

                Swal.fire({
                    title: 'Correcto!',
                    text: 'Se a deactivado Correctamente',
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                })
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
        $('#tupa_tipo').val(data.tupa_tipo);
    });
    $('#lbltitulo').html('Editar Registro');
    $('#modalmantenimiento').modal('show');
}

function eliminar(tupa_id) {
    swal.fire({
        title: "Eliminar!",
        text: "Desea Eliminar el Registro?",
        icon: "error",
        confirmButtonText: "Si",
        showCancelButton: true,
        cancelButtonText: "No",
    }).then((result) => {
        if (result.value) {
            $.post("../../controller/tupa.php?op=eliminar", { tupa_id: tupa_id }, function (data) {
                $('#tupa_data').DataTable().ajax.reload();

                Swal.fire({
                    title: 'Correcto!',
                    text: 'Se Elimino Correctamente',
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                })
            });
        }
    });
}

function nuevo() {
    $('#tupa_id').val('');
    $('#tupa_tipo').val('');
    $('#lbltitulo').html('Nuevo Registro');
    $('#tupa_form')[0].reset();
    $('#modalmantenimiento').modal('show');
}

init();
