
var tasa_id = $('#tasa_idx').val();
function init() {
    $("#tasa_form").on("submit", function (e) {
        tasaEditar(e);
    });
}

function tasaEditar(e) {
    e.preventDefault();  // Evita que el formulario se envíe y recargue la página

    var formData = new FormData($("#tasa_form")[0]);

    // Asegurarse de que el checkbox de multiplicador está correctamente añadido a formData
    var isMultiplica = $('#multiplicaCheckbox').prop('checked') ? 1 : 0;
    formData.append('is_multiplica', isMultiplica);

    $.ajax({
        url: "../../controller/procedimiento.php?op=tasaEditar", // Asegúrate que la URL esté correcta
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        // OPCIONAL: Si quieres obligar a que jQuery espere JSON, descomenta la siguiente línea
        // dataType: 'json',
        success: function (data) {
            // NO hacer JSON.parse, jQuery ya lo parsea si el servidor devuelve application/json
            // Puedes agregar este log si quieres ver qué recibes:
            // console.log(data);

            if (data.success === true) {
                // Recargar la tabla de datos utilizando la API DataTable
                $('#detalle_data').DataTable().ajax.reload();

                // Ocultar el modal después de la operación exitosa
                $('#modaltasamonto').modal('hide');

                Swal.fire({
                    title: 'Correcto!',
                    text: 'Se Registró Correctamente',
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                });
            } else {
                // Mostrar el error si success es falso
                Swal.fire({
                    title: 'Error!',
                    text: data.error || 'Error desconocido', // Usar data.error para mostrar el mensaje de error
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            }
        },
        error: function (xhr, status, error) {
            // Manejo de errores en caso de que la llamada AJAX falle
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

    $('.select2').select2();
    combo_areas();
    combo_tupa();
    $("#area_id").change(function () {
        $("#area_id option:selected").each(function () {
            area_id = $(this).val();
            combo_proced(area_id, tupa_id);
            cargardata();
        });
    });

    $("#tupa_id").change(function () {
        var estadoTupa = $(this).find("option:selected").data("estado");

        if (estadoTupa == 1) {
            // Deshabilitar el botón y cambiar el color de fondo a rojo
            $("#botonRegistrarNuevo").css({
                "border-color": "#ab2e46",
                "color": "#ab2e46"
            });
        } else {
            // Habilitar el botón y restaurar el color de fondo a su estado original
            $("#botonRegistrarNuevo").css({
                "border-color": "",
                "color": ""
            });
        }

        $("#tupa_id option:selected").each(function () {

            tupa_id = $(this).val();
            combo_proced(area_id, tupa_id);
            cargardata();
        });
    });

    $('#proced_id').change(function () {
        $("#proced_id option:selected").each(function () {
            proced_id = $(this).val();
            cargardata();

        });

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
        buttons: [

        ],
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
        "columnDefs": [
            { "type": "num", "targets": [1] },
            { "orderData": [1, 2], "targets": [1] }
        ],
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
    proced_id = 0;
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

function validarMonto() {
    var montoInput = $('#tasaproced_monto').val();
    var regex = /^\d+(\.\d{1,2})?$/;

    if (!regex.test(montoInput)) {
        alert("Por favor, ingrese un monto válido. Puede contener hasta dos decimales.");

        $('#tasaproced_monto').val('');
    }
}

function eliminar(tasaproced_id) {
    var estadoTupaSeleccionado = $("#tupa_id option:selected").data("estado");


    if (estadoTupaSeleccionado === 1) {
        Swal.fire({
            title: 'Alerta!',
            text: 'El TUPA seleccionado está bloqueado. No se puede Modificar.',
            icon: 'warning',
            confirmButtonText: 'Aceptar'
        });
        return;
    }
    swal.fire({
        title: "Eliminar!",
        text: "Desea Eliminar el Registro?",
        icon: "error",
        confirmButtonText: "Si",
        showCancelButton: true,
        cancelButtonText: "No",
    }).then((result) => {
        if (result.value) {
            $.post("../../controller/procedimiento.php?op=eliminar_proced_tasa", { tasaproced_id: tasaproced_id }, function (data) {
                $('#detalle_data').DataTable().ajax.reload();

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
            title: 'Alerta!',
            text: 'El TUPA seleccionado está bloqueado. No se puede Modificar.',
            icon: 'warning',
            confirmButtonText: 'Aceptar'
        });
        return;
    }
    if ($('#proced_id').val() == '') {
        Swal.fire({
            title: 'Error!',
            text: 'Seleccionar un procedimiento',
            icon: 'error',
            confirmButtonText: 'Aceptar'
        })
    } else {
        var proced_id = $('#proced_id').val();
        listar_tasa(proced_id);
        $('#modalmantenimiento').modal('show');
        $('#proced_id').val(proced_id);
        console.log(proced_id);
    }
}

function editar(tasaproced_id) {
    var estadoTupaSeleccionado = $("#tupa_id option:selected").data("estado");

    // Verificar si el TUPA está bloqueado
    if (estadoTupaSeleccionado == 1) {
        Swal.fire({
            title: 'Alerta!',
            text: 'El TUPA seleccionado está bloqueado. No se puede Modificar.',
            icon: 'warning',
            confirmButtonText: 'Aceptar'
        });
        return;
    }

    // Limpiar los valores del formulario antes de cargar nuevos datos
    $('#tasa_form')[0].reset();
    $('#desc_tasa').val('');
    $('#proced_nom').val('');
    $('#tasaproced_pos').val('');
    $('#cod_ref').val('');
    $('#tasaproced_monto').val('');
    $('#is_multiplica').prop('checked', false);  // Asegurarse de desmarcar el checkbox si se recarga el modal

    // Realizamos el llamado AJAX para cargar los datos
    $.post("../../controller/procedimiento.php?op=mostrartasaproced", { tasaproced_id: tasaproced_id }, function (data) {
        data = JSON.parse(data);
        $('#tasaproced_id').val(data.tasaproced_id);
        $('#tasa_nom').val(data.tasa_nom);
        $('#desc_tasa').val(data.desc_tasa);
        $('#proced_nom').val(data.proced_nom);
        $('#tasaproced_pos').val(data.tasaproced_pos);
        $('#cod_ref').val(data.cod_ref);
        $('#tasaproced_monto').val(data.tasaproced_monto);
       // Aquí es donde se asegura que el checkbox se marque si is_multiplica es igual a 1
        $('#multiplicaCheckbox').prop('checked', data.is_multiplica == 1);
    });

    // Cambiar el título del modal a "Editar Registro"
    $('#lbltitulo').html('Editar Registro');

    // Mostrar el modal
    $('#modaltasamonto').modal('show');
}


function listar_tasa(proced_id) {
    $('#tasa_data').DataTable({
        "aProcessing": true,
        "aServerSide": true,
        dom: 'Bfrtip',
        buttons: [

        ],
        "ajax": {
            url: "../../controller/tasa.php?op=listar_detalle_tasa",
            type: "post",
            data: { proced_id: proced_id }
        },
        "bDestroy": true,
        "responsive": true,
        "bInfo": true,
        "iDisplayLength": 7,
        "order": [[1, "desc"]],
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
}

function registrardetalle() {
    table = $('#tasa_data').DataTable();
    var proced_id = $('#proced_id').val();
    var tasa_id = [];

    table.rows().every(function (rowIdx, tableLoop, rowLoop) {
        cell1 = table.cell({ row: rowIdx, column: 0 }).node();
        if ($('input', cell1).prop("checked") == true) {
            id = $('input', cell1).val();
            tasa_id.push([id]);
            console.log(tasa_id);
        }
    });

    if (tasa_id == 0) {
        Swal.fire({
            title: 'Error!',
            text: 'Seleccionar tasas',
            icon: 'error',
            confirmButtonText: 'Aceptar'
        })
    } else {
        /* Creando formulario */
        const formData = new FormData($("#form_detalle")[0]);
        formData.append('proced_id', proced_id);
        formData.append('tasa_id', tasa_id);
        console.log(proced_id);

        $.ajax({
            url: "../../controller/procedimiento.php?op=insert_proced_tasa",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function (data) {
                console.log(data);
                data = JSON.parse(data);

            }
        });

        /* Recargar datatable de los tasas del proced */
        $('#detalle_data').DataTable().ajax.reload();

        $('#tasa_data').DataTable().ajax.reload();
        /* ocultar modal */
        $('#modalmantenimiento').modal('hide');

    }
}

init();
