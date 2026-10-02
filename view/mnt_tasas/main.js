var usu_id = $('#usu_idx').val();

function init() {
    $("#tasa_form").on("submit", function (e) {
        guardaryeditar(e);
    });
}

function guardaryeditar(e) {
    e.preventDefault();
    var formData = new FormData($("#tasa_form")[0]);
    $.ajax({
        url: "../../controller/tasa.php?op=guardaryeditar",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function (data) {
            $('#tasa_data').DataTable().ajax.reload();
            $('#modalmantenimiento').modal('hide');

            Swal.fire({
                title: 'Correcto!',
                text: 'La tasa se guardó correctamente',
                icon: 'success',
                confirmButtonText: 'Aceptar'
            });
        },
        error: function () {
            Swal.fire({
                title: 'Error!',
                text: 'Ocurrió un error al procesar la tasa',
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
        }
    });
}

$(document).ready(function () {
    $('#tasa_tipo').select2({
        dropdownParent: $('#modalmantenimiento'),
        width: '100%'
    });

    $('#tasa_data').DataTable({
        "aProcessing": true,
        "aServerSide": true,
        dom: 'Bfrtip',
        buttons: [],
        "ajax": {
            url: "../../controller/tasa.php?op=listar",
            type: "post"
        },
        "bDestroy": true,
        "responsive": true,
        "bInfo": true,
        "iDisplayLength": 10,
        "order": [[0, "asc"]],
        "language": {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
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
});

function editar(tasa_id) {
    $.post("../../controller/tasa.php?op=mostrar", { tasa_id: tasa_id }, function (data) {
        data = JSON.parse(data);
        $('#tasa_id').val(data.tasa_id);
        $('#tasa_nom').val(data.tasa_nom);
        $('#tasa_tipo').val(data.tasa_tipo).trigger('change');
    });
    $('#lbltitulo').html('Editar Tasa');
    $('#modalmantenimiento').modal('show');
}

function eliminar(tasa_id) {
    Swal.fire({
        title: "Eliminar Tasa",
        text: "¿Desea eliminar la tasa seleccionada?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#d33"
    }).then((result) => {
        if (result.value) {
            $.post("../../controller/tasa.php?op=eliminar", { tasa_id: tasa_id }, function (data) {
                $('#tasa_data').DataTable().ajax.reload();
                Swal.fire({
                    title: "Eliminado!",
                    text: "La tasa fue eliminada correctamente",
                    icon: "success",
                    confirmButtonText: "Aceptar"
                });
            });
        }
    });
}

function nuevo() {
    $('#tasa_id').val('');
    $('#lbltitulo').html('Nueva Tasa');
    $('#tasa_form')[0].reset();
    $('#tasa_tipo').val('0').trigger('change');
    $('#modalmantenimiento').modal('show');
}

init();
