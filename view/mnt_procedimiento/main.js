var usu_id = $("#usu_idx").val();
var area_id = 0;
var tupa_id = 0;

function init() {
  $("#proced_form").on("submit", function (e) {
    guardaryeditar(e);
  });
}

function guardaryeditar(e) {
  e.preventDefault();

  var area_id_seleccionado = $("#area_id").val();
  var tupa_id_seleccionado = $("#tupa_id").val();

  if (!area_id_seleccionado || !tupa_id_seleccionado) {
    Swal.fire({
      title: "Filtros requeridos",
      text: "Debe seleccionar un Documento (TUPA) y un Área.",
      icon: "warning",
      confirmButtonText: "Entendido",
    });
    return;
  }

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
        text: "Se registró el procedimiento correctamente",
        icon: "success",
        confirmButtonText: "Aceptar",
      });
    },
    error: function () {
      Swal.fire({
        title: "Error!",
        text: "Ocurrió un error al guardar el procedimiento.",
        icon: "error",
        confirmButtonText: "Aceptar",
      });
    }
  });
}

function updateDataTable() {
  if ($.fn.DataTable.isDataTable("#proceds_data")) {
    $("#proceds_data").DataTable().destroy();
  }

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
    responsive: true,
    bInfo: true,
    iDisplayLength: 10,
    order: [[0, "desc"]],
    language: {
      sProcessing: "Procesando...",
      sLengthMenu: "Mostrar _MENU_ registros",
      sZeroRecords: "No se encontraron resultados",
      sEmptyTable: "Ningún dato disponible en esta tabla",
      sInfo: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
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
        sSortAscending: ": Activar para ordenar la columna de manera ascendente",
        sSortDescending: ": Activar para ordenar la columna de manera descendente",
      },
    },
  });
}

$(document).ready(function () {
  $(".select2").select2({ width: "100%" });
  combo_areas2();
  combo_tupa2();

  $("#tipo_campo").select2({
    dropdownParent: $("#modalmantenimiento"),
    width: "100%",
  });
  $("#tipo_administrado").select2({
    dropdownParent: $("#modalmantenimiento"),
    width: "100%",
  });
  $("#proced_tipoindvasc").select2({
    dropdownParent: $("#modalmantenimiento"),
    width: "100%",
  });

  $("#area_id").change(function () {
    area_id = $(this).val();
    updateDataTable();
  });

  $("#tupa_id").change(function () {
    tupa_id = $(this).val();
    updateDataTable();
  });

  $("#btnnuevoproced").on("click", function (e) {
    e.preventDefault();
    nuevoproced();
  });

  updateDataTable();
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
      $("#tipo_campo").val(data.proced_tipocampo).trigger("change");
      $("#tipo_administrado").val(data.proced_administradotipo).trigger("change");
      $("#proced_tipoindvasc").val(data.proced_tipoindvasc).trigger("change");
      $("#lbltitulo").html("Editar Procedimiento");
      $("#modalmantenimiento").modal("show");
    }
  );
}

function eliminar(proced_id) {
  Swal.fire({
    title: "Eliminar Procedimiento",
    text: "¿Desea eliminar el procedimiento seleccionado?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#d33",
  }).then((result) => {
    if (result.value) {
      $.post(
        "../../controller/procedimiento.php?op=eliminar",
        { proced_id: proced_id },
        function (data) {
          $("#proceds_data").DataTable().ajax.reload();
          Swal.fire({
            title: "Eliminado!",
            text: "El procedimiento fue eliminado correctamente",
            icon: "success",
            confirmButtonText: "Aceptar",
          });
        }
      );
    }
  });
}

function nuevoproced() {
  var area_val = $("#area_id").val();
  var tupa_val = $("#tupa_id").val();

  if (!area_val || !tupa_val) {
    Swal.fire({
      title: "Filtros requeridos",
      text: "Debe seleccionar un Documento (TUPA) y un Área antes de agregar un procedimiento.",
      icon: "warning",
      confirmButtonText: "Entendido",
    });
    return;
  }

  $("#proced_id").val("");
  $("#lbltitulo").html("Nuevo Procedimiento");
  $("#proced_form")[0].reset();
  $("#tipo_campo").val("2").trigger("change");
  $("#tipo_administrado").val("E").trigger("change");
  $("#proced_tipoindvasc").val("C").trigger("change");
  $("#modalmantenimiento").modal("show");
}

init();
