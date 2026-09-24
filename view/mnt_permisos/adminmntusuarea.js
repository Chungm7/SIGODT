
var usu_id = $('#usu_idx').val();

function init(){
    $("#usu_form").on("submit",function(e){
       usuEditar(e);
    });
}
function usuEditar(e) {
    e.preventDefault();
    var formData = new FormData($("#usu_form")[0]);

        $.ajax({
            url: "../../controller/area.php?op=usuEditar",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function(data) {
    
                console.log(data);
                try{
                    var jsonData = JSON.parse(data);
                }
                catch(error){
                    Swal.fire({
                        title: 'Error!',
                        text: data.error,
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                }
                
                console.log(jsonData.success);
                if ( jsonData.success == true)  {
                    // Recargar la tabla de datos utilizando la API DataTable
                    $('#detalle_data').DataTable().ajax.reload();
                    // Ocultar el modal después de la operación exitosa
                    $('#modalusumonto').modal('hide');
    
                    Swal.fire({
                        title: 'Correcto!',
                        text: 'Se Registró Correctamente',
                        icon: 'success',
                        confirmButtonText: 'Aceptar'
                    });
                }else if(jsonData.success==false){
                    Swal.fire({
                        title: 'Error!',
                        text: data.error,
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                }
            },
        });    
}


$(document).ready(function(){

    $('.select2').select2();
    combo_area();    
    /* Obtener Id de combo area */
    $('#depe_select').change(function() {
        
        $("#depe_select option:selected").each(function () {
            depe_id = $(this).val();

            /* Listado de datatable */
            $('#detalle_data').DataTable({
                "aProcessing": true,
                "aServerSide": true,
                "scrollX": true,
                dom: 'Bfrtip',
                buttons: [
                ],
                "ajax":{
                    url:"../../controller/usuario.php?op=listar_area_usu",
                    type:"post",
                    data:{depe_id:depe_id},
                },
                "bDestroy": true,
                "responsive": false,
                "bInfo":true,
                "iDisplayLength": 10,
                "order": [[ 1, "desc" ]],
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
    });

});


function eliminar(areausua_id){
    swal.fire({
        title: "Eliminar!",
        text: "Desea Eliminar el Registro?",
        icon: "error",
        confirmButtonText: "Si",
        showCancelButton: true,
        cancelButtonText: "No",
    }).then((result) => {
        if (result.value) {
            $.post("../../controller/area.php?op=eliminar_area_usu",{areausua_id : areausua_id}, function (data) {
                $('#detalle_data').DataTable().ajax.reload();
                console.log
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

function combo_area(){
    $.post("../../controller/area.php?op=combo", function (data) {
        $('#depe_select').html(data);
    });
}


function nuevo(){
    if ($('#depe_select').val()==''){
        Swal.fire({
            title: 'Error!',
            text: 'Seleccionar area',
            icon: 'error',
            confirmButtonText: 'Aceptar'
        })
    }else{
        var depe_id = $('#depe_select').val();
        listar_usu(depe_id);
        $('#modalmantenimiento').modal('show');
    }
}



function listar_usu(depe_id){
    $('#usu_data').DataTable({
        "aProcessing": true,
        "aServerSide": true,
        "scrollX": false,
        dom: 'Bfrtip',
        buttons: [
            
        ],
        "ajax":{
            url:"../../controller/usuario.php?op=listar_detalle_usu",
            type:"post",
            data:{depe_id:depe_id}
        },
        "bDestroy": true,
        "responsive": false,
        "bInfo":true,
        "iDisplayLength": 7,
        "order": [[ 1, "desc" ]],
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
}

function registrardetalle(){
    table = $('#usu_data').DataTable();
    var pers_id =[];

    table.rows().every(function(rowIdx, tableLoop, rowLoop) {
        cell1 = table.cell({ row: rowIdx, column: 0 }).node();
        if ($('input', cell1).prop("checked") == true) {
            id = $('input', cell1).val();
            pers_id.push([id]);
        }
    });

    if (pers_id == 0){
        Swal.fire({
            title: 'Error!',
            text: 'Seleccionar usus',
            icon: 'error',
            confirmButtonText: 'Aceptar'
        })
    }else{
        /* Creando formulario */
        const formData = new FormData($("#form_detalle")[0]);
        formData.append('depe_id',depe_id);
        formData.append('pers_id',pers_id);

        $.ajax({
            url: "../../controller/area.php?op=insert_area_usu",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success : function(data) {
                data = JSON.parse(data);
            }
        });

        /* Recargar datatable de los usus del area */
        $('#detalle_data').DataTable().ajax.reload();

        $('#usu_data').DataTable().ajax.reload();
        /* ocultar modal */
        $('#modalmantenimiento').modal('hide');

    }
}

init();
