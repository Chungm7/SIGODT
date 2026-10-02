var tasa_id = $('#tasa_idx').val();

function init() {
    $("#tasa_form").on("submit", function (e) {
        tasaEditar(e);
    });
}

function tasaEditar(e) {
    e.preventDefault();

    var formData = new FormData($("#tasa_form")[0]);
    var isMultiplica = $('#multiplicaCheckbox').prop('checked') ? 1 : 0;
    formData.append('is_multiplica', isMultiplica);

    $.ajax({
        url: "../../controller/procedimiento.php?op=tasaEditar",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function (data) {
            if (data.success === true) {
                $('#detalle_data').DataTable().ajax.reload();
                $('#modaltasamonto').modal('hide');

                Swal.fire({
                    title: 'Correcto!',
                    text: 'La configuración de la tasa se guardó correctamente',
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                });
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: data.error || 'Error al actualizar la configuración.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            }
        },
        error: function (xhr, status, error) {
            Swal.fire({
                title: 'Error!',
                text: 'Hubo un problema con la conexión o el servidor.',
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
            console.error("Error de AJAX:", error);
        }
    });
}

var area_id = 0;
var tupa_id = 0;
var proced_id = 0;

$(document).ready(function () {
    $('.select2').select2({ width: '100%' });
    combo_areas();
    combo_tupa();

    $("#area_id").change(function () {
        area_id = $(this).val();
        combo_proced(area_id, tupa_id);
        cargardata();
    });

    $("#tupa_id").change(function () {
        tupa_id = $(this).val();
        combo_proced(area_id, tupa_id);
        cargardata();
    });

    $('#proced_id').change(function () {
        proced_id = $(this).val();
        cargardata();
    });
});

function cargardata() {
    if ($.fn.DataTable.isDataTable('#detalle_data')) {
        $('#detalle_data').DataTable().destroy();
    }
    $('#detalle_data').DataTable({
        "aProcessing": true,
        "aServerSide": true,
        dom: 'Bfrtip',
        buttons: [],
        "ajax": {
            url: "../../controller/tasa.php?op=listar_proceds_tasa",
            type: "post",
            data: { proced_id: proced_id, area_id: area_id, tupa_id: tupa_id },
        },
        "bDestroy": true,
        "responsive": true,
        "bInfo": true,
        "iDisplayLength": 10,
        "order": [[2, "asc"]],
        "language": {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron tasas asignadas a este procedimiento",
            "sEmptyTable": "Ninguna tasa asignada al procedimiento seleccionado",
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
            "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
            "sInfoPostFix": "",
            "sSearch": "Buscar:",
            "sUrl": "",
            "sInfoThousands": ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "Siguiente",
                "sPrevious": "Anterior"
            },
            "oAria": {
                "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
        },
    });
}

function combo_areas() {
    $.post("../../controller/area.php?op=combo", function (data) {
        $('#area_id').html(data);
    });
}

function combo_tupa() {
    $.post("../../controller/tupa.php?op=combo", function (data) {
        $('#tupa_id').html(data);
    });
}

function combo_proced(area_id, tupa_id) {
    $.ajax({
        url: "../../controller/procedimiento.php?op=combo",
        type: "POST",
        data: { area_id: area_id, tupa_id: tupa_id },
        dataType: "html",
        success: function (data) {
            $('#proced_id').html(data);
        },
        error: function (xhr, status, error) {
            console.error("Error en la solicitud AJAX:", status, error);
        }
    });
}

function nuevo() {
    var estadoTupaSeleccionado = $("#tupa_id option:selected").data("estado");
    if (estadoTupaSeleccionado == 1) {
        Swal.fire({
            title: 'Documento bloqueado',
            text: 'El Documento (TUPA) seleccionado está bloqueado y no admite modificaciones.',
            icon: 'warning',
            confirmButtonText: 'Entendido'
        });
        return;
    }

    var proced_sel = $('#proced_id').val();
    if (!proced_sel) {
        Swal.fire({
            title: 'Procedimiento requerido',
            text: 'Debe seleccionar un procedimiento antes de asignar tasas.',
            icon: 'warning',
            confirmButtonText: 'Entendido'
        });
        return;
    }

    listar_tasa(proced_sel);
    $('#modalmantenimiento').modal('show');
}

function editar(tasaproced_id) {
    var estadoTupaSeleccionado = $("#tupa_id option:selected").data("estado");
    if (estadoTupaSeleccionado == 1) {
        Swal.fire({
            title: 'Documento bloqueado',
            text: 'El Documento (TUPA) seleccionado está bloqueado y no admite modificaciones.',
            icon: 'warning',
            confirmButtonText: 'Entendido'
        });
        return;
    }

    $('#tasa_form')[0].reset();
    $('#desc_tasa').val('');
    $('#proced_nom').val('');
    $('#tasaproced_pos').val('');
    $('#cod_ref').val('');
    $('#tasaproced_monto').val('');
    $('#multiplicaCheckbox').prop('checked', false);

    $.post("../../controller/procedimiento.php?op=mostrartasaproced", { tasaproced_id: tasaproced_id }, function (data) {
        data = JSON.parse(data);
        $('#tasaproced_id').val(data.tasaproced_id);
        $('#tasa_nom').val(data.tasa_nom);
        $('#desc_tasa').val(data.desc_tasa);
        $('#proced_nom').val(data.proced_nom);
        $('#tasaproced_pos').val(data.tasaproced_pos);
        $('#cod_ref').val(data.cod_ref);
        $('#tasaproced_monto').val(data.tasaproced_monto);
        $('#multiplicaCheckbox').prop('checked', data.is_multiplica == 1);
        $('#lbltitulo').html('Configurar Tasa: ' + data.tasa_nom);
        $('#modaltasamonto').modal('show');
    });
}

function eliminar(tasaproced_id) {
    var estadoTupaSeleccionado = $("#tupa_id option:selected").data("estado");
    if (estadoTupaSeleccionado == 1) {
        Swal.fire({
            title: 'Documento bloqueado',
            text: 'El Documento (TUPA) seleccionado está bloqueado y no admite modificaciones.',
            icon: 'warning',
            confirmButtonText: 'Entendido'
        });
        return;
    }

    Swal.fire({
        title: "Desvincular Tasa",
        text: "¿Desea desvincular la tasa de este procedimiento?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, desvincular",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#d33"
    }).then((result) => {
        if (result.value) {
            $.post("../../controller/procedimiento.php?op=eliminar_proced_tasa", { tasaproced_id: tasaproced_id }, function () {
                $('#detalle_data').DataTable().ajax.reload();
                Swal.fire({
                    title: 'Eliminado!',
                    text: 'La tasa fue desvinculada del procedimiento correctamente',
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                });
            });
        }
    });
}

function listar_tasa(proced_id_param) {
    if ($.fn.DataTable.isDataTable('#tasa_data')) {
        $('#tasa_data').DataTable().destroy();
    }
    $('#tasa_data').DataTable({
        "aProcessing": true,
        "aServerSide": true,
        dom: 'Bfrtip',
        buttons: [],
        "ajax": {
            url: "../../controller/tasa.php?op=listar_detalle_tasa",
            type: "post",
            data: { proced_id: proced_id_param }
        },
        "bDestroy": true,
        "responsive": true,
        "bInfo": true,
        "iDisplayLength": 7,
        "order": [[1, "asc"]],
        "language": {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No hay más tasas disponibles para asignar",
            "sEmptyTable": "Todas las tasas ya están asignadas o no hay tasas registradas",
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
            "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
            "sInfoPostFix": "",
            "sSearch": "Buscar:",
            "sUrl": "",
            "sInfoThousands": ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "Siguiente",
                "sPrevious": "Anterior"
            },
            "oAria": {
                "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
        },
    });
}

function registrardetalle() {
    var table = $('#tasa_data').DataTable();
    var proced_id_val = $('#proced_id').val();
    var tasa_id = [];

    table.rows().every(function () {
        var cell1 = this.cell(this.index(), 0).node();
        var chk = $('input', cell1);
        if (chk.prop("checked")) {
            tasa_id.push(chk.val());
        }
    });

    if (tasa_id.length === 0) {
        Swal.fire({
            title: 'Selección requerida',
            text: 'Debe marcar al menos una tasa para agregar al procedimiento.',
            icon: 'warning',
            confirmButtonText: 'Entendido'
        });
        return;
    }

    var formData = new FormData();
    formData.append('proced_id', proced_id_val);
    formData.append('tasa_id', tasa_id);

    $.ajax({
        url: "../../controller/procedimiento.php?op=insert_proced_tasa",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function () {
            $('#detalle_data').DataTable().ajax.reload();
            $('#modalmantenimiento').modal('hide');
            Swal.fire({
                title: 'Correcto!',
                text: 'Las tasas se asignaron correctamente',
                icon: 'success',
                confirmButtonText: 'Aceptar'
            });
        },
        error: function () {
            Swal.fire({
                title: 'Error!',
                text: 'Ocurrió un error al asignar las tasas.',
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
        }
    });
}

init();
