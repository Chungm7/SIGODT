
var usu_id = $('#usu_idx').val();

function init(){
    $("#tasa_form").on("submit",function(e){
        guardaryeditar(e);
    });
}

function guardaryeditar(e){
    e.preventDefault();
    var formData = new FormData($("#tasa_form")[0]);
    $.ajax({
        url: "../../controller/tasa.php?op=guardaryeditar",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function(data){

            $('#tasa_data').DataTable().ajax.reload();
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


$(document).ready(function(){
    $('#tasa_tipo').select2({
        dropdownParent: $('#modalmantenimiento')
    });

    $('#tasa_data').DataTable({
        "aProcessing": true,
        "aServerSide": true,
        dom: 'Bfrtip',
        buttons: [
        ],
        "ajax":{
            url:"../../controller/tasa.php?op=listar",
            type:"post"
        },
        "bDestroy": true,
        "responsive": false,
        "bInfo":true,
        "iDisplayLength": 10,
        "order": [[ 0, "desc" ]],
        "language": {
            "sProcessing":     "Procesando...",
            "sLengthMenu":     "Mostrar _MENU_ registros",
            "sZeroRecords":    "No se encontraron resultados",
            "sEmptyTable":     "Ningún dato disponible en esta tabla",
            "sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0 registros",
            "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
            "sInfoPostFix":    "",
            "sSearch":         "Buscar:",
            "sUrl":            "",
            "sInfoThousands":  ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst":    "Primero",
                "sLast":     "Último",
                "sNext":     "Siguiente",
                "sPrevious": "Anterior"
            },
            "oAria": {
                "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
        },
    });

});

function editar(tasa_id){
    $.post("../../controller/tasa.php?op=mostrar",{tasa_id : tasa_id}, function (data) {
        data = JSON.parse(data);
        $('#tasa_id').val(data.tasa_id);
        $('#tasa_nom').val(data.tasa_nom);
        $('#tasa_tipo').val(data.tasa_tipo);
        $('#tasa_tipo').change();
    });
    $('#lbltitulo').html('Editar Registro');
    $('#modalmantenimiento').modal('show');
}

function eliminar(tasa_id){
    swal.fire({
        title: "Eliminar!",
        text: "Desea Eliminar el Registro?",
        icon: "error",
        confirmButtonText: "Si",
        showCancelButton: true,
        cancelButtonText: "No",
    }).then((result) => {
        if (result.value) {
            $.post("../../controller/tasa.php?op=eliminar",{tasa_id : tasa_id}, function (data) {
                $('#tasa_data').DataTable().ajax.reload();

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

function nuevo(){
    $('#tasa_id').val('');
    $('#lbltitulo').html('Nuevo Registro');
    $('#tasa_form')[0].reset();
    $('#modalmantenimiento').modal('show');
}

init();
