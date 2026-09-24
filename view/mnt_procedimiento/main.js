var usu_id = $("#usu_idx").val();

function init() {
  $("#proced_form").on("submit", function (e) {
    guardaryeditar(e);
  });
}

function guardaryeditar(e) {
  e.preventDefault();

  // Obtener el valor seleccionado de area_id y tupa_id
  var area_id_seleccionado = $("#area_id").val();
  var tupa_id_seleccionado = $("#tupa_id").val();

  // Agregar los valores al formulario
  var formData = new FormData($("#proced_form")[0]);
  formData.append("area_id", area_id_seleccionado);
  formData.append("tupa_id", tupa_id_seleccionado);

  $.ajax({
    url: "../../controller/procedimiento.php?op=guardaryeditar",
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    success: function (data) {
      $("#proceds_data").DataTable().ajax.reload();
      $("#modalmantenimiento").modal("hide");

      Swal.fire({
        title: "Correcto!",
        text: "Se Registró Correctamente",
        icon: "success",
        confirmButtonText: "Aceptar",
      });
    },
  });
}

$(document).ready(function () {
  $(".select2").select2();
  combo_areas2();
  combo_tupa2();
  tupa_id = 0;
  area_id = 0;
  $("#tipo_campo").select2({
    dropdownParent: $("#modalmantenimiento"),
  });
  $("#tipo_administrado").select2({
    dropdownParent: $("#modalmantenimiento"),
  });
  $("#proced_tipoindvasc").select2({
    dropdownParent: $("#modalmantenimiento"),
  });

  $("#area_id").change(function () {
    $("#area_id option:selected").each(function () {
      area_id = $(this).val();
      updateDataTable();
    });
  });

  $("#tupa_id").change(function () {
    $("#tupa_id option:selected").each(function () {
      tupa_id = $(this).val();
      updateDataTable();
    });
  });
  $("#btnnuevoproced").on("click", function (e) {
    e.preventDefault();
    nuevoproced();
  });

  function updateDataTable() {
    // Destruir la tabla existente
    $("#proceds_data").DataTable().destroy();

    // Crear la tabla DataTable con los nuevos parámetros
    $("#proceds_data").DataTable({
      aProcessing: true,
      aServerSide: true,
      dom: "Bfrtip",
      buttons: [],
      ajax: {
        url: "../../controller/procedimiento.php?op=listar",
        type: "post",
        data: { area_id: area_id, tupa_id: tupa_id },
      },
      bDestroy: true,
      responsive: false,
      bInfo: true,
      iDisplayLength: 10,
      order: [[0, "desc"]],
      language: {
        sProcessing: "Procesando...",
        sLengthMenu: "Mostrar _MENU_ registros",
        sZeroRecords: "No se encontraron resultados",
        sEmptyTable: "Ningún dato disponible en esta tabla",
        sInfo:
          "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
        sInfoEmpty: "Mostrando registros del 0 al 0 de un total de 0 registros",
        sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
        sInfoPostFix: "",
        sSearch: "Buscar:",
        sUrl: "",
        sInfoThousands: ",",
        sLoadingRecords: "Cargando...",
        oPaginate: {
          sFirst: "Primero",
          sLast: "Último",
          sNext: "Siguiente",
          sPrevious: "Anterior",
        },
        oAria: {
          sSortAscending:
            ": Activar para ordenar la columna de manera ascendente",
          sSortDescending:
            ": Activar para ordenar la columna de manera descendente",
        },
      },
    });
  }

  // Inicializar la tabla DataTable al cargar la página
  $("#proceds_data").DataTable({
    aProcessing: true,
    aServerSide: true,
    dom: "Bfrtip",
    buttons: [],
    ajax: {
      url: "../../controller/procedimiento.php?op=listar",
      type: "post",
      data: { area_id: area_id, tupa_id: tupa_id },
    },
    bDestroy: true,
    responsive: false,
    bInfo: true,
    iDisplayLength: 10,
    order: [[0, "desc"]],
    language: {
      sProcessing: "Procesando...",
      sLengthMenu: "Mostrar _MENU_ registros",
      sZeroRecords: "No se encontraron resultados",
      sEmptyTable: "Ningún dato disponible en esta tabla",
      sInfo:
        "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
      sInfoEmpty: "Mostrando registros del 0 al 0 de un total de 0 registros",
      sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
      sInfoPostFix: "",
      sSearch: "Buscar:",
      sUrl: "",
      sInfoThousands: ",",
      sLoadingRecords: "Cargando...",
      oPaginate: {
        sFirst: "Primero",
        sLast: "Último",
        sNext: "Siguiente",
        sPrevious: "Anterior",
      },
      oAria: {
        sSortAscending:
          ": Activar para ordenar la columna de manera ascendente",
        sSortDescending:
          ": Activar para ordenar la columna de manera descendente",
      },
    },
  });
});

function combo_areas2() {
  $.post("../../controller/area.php?op=combo", function (data) {
    $("#area_id").html(data);
  });
}
function combo_tupa2() {
  $.post("../../controller/tupa.php?op=combo_ti", function (data) {
    $("#tupa_id").html(data);
  });
}
function editar(proced_id) {
  $.post(
    "../../controller/procedimiento.php?op=mostrar",
    { proced_id: proced_id },
    function (data) {
      data = JSON.parse(data);
      $("#proced_id").val(data.proced_id);
      $("#proced_nom").val(data.proced_nom);
      $("#proced_area").val(data.proced_area);
      $("#proced_cod").val(data.proced_cod);
      $("#tipo_campo").val(data.proced_tipocampo);
      $("#tipo_campo").change();
      $("#tipo_administrado").val(data.proced_administradotipo);
      $("#proced_tipoindvasc").val(data.proced_tipoindvasc);
      $("#tipo_administrado").change();
      $("#proced_tipoindvasc").change();
    }
  );
  $("#lbltitulo").html("Editar Registro");
  $("#modalmantenimiento").modal("show");
}

function eliminar(proced_id) {
  swal
    .fire({
      title: "Eliminar!",
      text: "Desea Eliminar el Registro?",
      icon: "error",
      confirmButtonText: "Si",
      showCancelButton: true,
      cancelButtonText: "No",
    })
    .then((result) => {
      if (result.value) {
        $.post(
          "../../controller/procedimiento.php?op=eliminar",
          { proced_id: proced_id },
          function (data) {
            $("#proceds_data").DataTable().ajax.reload();

            Swal.fire({
              title: "Correcto!",
              text: "Se Elimino Correctamente",
              icon: "success",
              confirmButtonText: "Aceptar",
            });
          }
        );
      }
    });
}

function nuevoproced() {
  if ($("#area_id").val() == "" || $("#tupa_id").val() == "") {
    Swal.fire({
      title: "Error!",
      text: "Seleccionar proced",
      icon: "error",
      confirmButtonText: "Aceptar",
    });
  } else {
    var area_id = $("#area_id").val();
    var tupa_id = $("#tupa_id").val();
    $("#proced_id").val("");
    $("#lbltitulo").html("Nuevo Registro");
    $("#proced_form")[0].reset();
    $("#modalmantenimiento").modal("show");
    
  }
}

init();
