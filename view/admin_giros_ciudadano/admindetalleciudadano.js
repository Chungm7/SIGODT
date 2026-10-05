var ciudadano_id = $("#ciudadano_idx").val();

const formCiudadanosDetalles = document.getElementById("ciudadanosdetalles_form");
if (formCiudadanosDetalles) {
  formCiudadanosDetalles.addEventListener("keydown", function (e) {
    if (e.key === "Enter") {
      if (e.target.tagName.toLowerCase() === "textarea") {
        return;
      }
      e.preventDefault();
      if (e.target.id === "ciudadano_doc") {
        if (typeof ejecutarBusquedaDocAdmin === "function") {
          ejecutarBusquedaDocAdmin();
        } else if (typeof limitabuscadni === "function") {
          limitabuscadni(e.target);
        }
      }
    }
  });
}

document.addEventListener("DOMContentLoaded", function () {
  var modalMantenimiento = document.getElementById("modalmantenimiento");
  if (modalMantenimiento) {
    modalMantenimiento.addEventListener("shown.bs.modal", function () {
      var docInput = document.getElementById("ciudadano_doc");
      if (docInput) {
        docInput.focus();
        docInput.select();
      }
    });
  }
});

$(document).on("shown.bs.modal", "#modalmantenimiento", function () {
  var docInput = document.getElementById("ciudadano_doc");
  if (docInput) {
    docInput.focus();
    docInput.select();
  }
});

function init() {
  $("#ciudadanosdetalles_form").on("submit", function (e) {
    detalleEditar(e);
  });
}

function detalleEditar(e) {
  e.preventDefault();

  if ($("#ciud_id").val() == "") {
    Swal.fire({
      title: "Error!",
      text: "Debe Buscar El Documento",
      icon: "error",
      confirmButtonText: "Aceptar",
      timer: 3000
    });
  } else {
    const rucCheckbox = document.getElementById("ruc_checkbox");
    const emprId = document.getElementById("empr_id").value;

    if (rucCheckbox.checked && !emprId) {
      Swal.fire({
        title: "Error!",
        text: "Debe Ingresar El RUC o desactiar la casilla RUC",
        icon: "error",
        confirmButtonText: "Aceptar",
        timer: 3000
      });
    } else if (
      $("#ciud_id").val() !== "" ||
      $("#menor_edad").val() === "1" ||
      $("#esCarnet").val() === "1" ||
      $("#esCPP").val() === "1"
    ) {
      // Verifica si el campo no está vacío
      // Bloquear el botón de guardar y mostrar una animación de carga
      $("#btnguardar").prop("disabled", true);
      $("#btnguardar").html(
        '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Procesando...'
      );

      var formData = new FormData($("#ciudadanosdetalles_form")[0]);
      var proced_id = $("#proced_id").val();
      var tipo_proced = "ciudadano";
      var ciud_id = $("#ciud_id").val();
      var empr_id = $("#empr_id").val();
      var procedciudadano_id = $("#procedciudadano_id").val();
      formData.append("tipo_proced", tipo_proced);
      formData.append("proced_id", proced_id);
      formData.append("ciud_id", ciud_id);
      if (empr_id == undefined || empr_id.trim() === "") {
        empr_id = "";
      }
      formData.append("empr_id", empr_id);
      formData.append("procedciudadano_id", procedciudadano_id);

      var tido_id = 1;
      if ($("#esCarnet").val() === "1") {
        tido_id = 3;
      } else if ($("#esCPP").val() === "1") {
        tido_id = 4;
      }
      formData.append("tido_id", tido_id);
      $.ajax({
        url: "../../controller/procedimiento.php?op=abrirproced",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function (data) {
          console.log(empr_id);

          // Desbloquear el botón de guardar y restaurar su contenido original
          $("#btnguardar").prop("disabled", false);
          $("#btnguardar").html('<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg> Guardar');

          $("#detalle_data").DataTable().ajax.reload();
          $("#modalmantenimiento").modal("hide");

          Swal.fire({
            title: "Correcto!",
            text: "Se Registró Correctamente",
            icon: "success",
            confirmButtonText: "Aceptar",
            timer: 3000
          });
        },
        error: function (xhr, status, error) {
          // En caso de error, también desbloqueamos el botón y restauramos su contenido original
          $("#btnguardar").prop("disabled", false);
          $("#btnguardar").html('<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg> Guardar');

          // Aquí puedes manejar el error de acuerdo a tus necesidades
          console.error("Error en la solicitud AJAX:", status, error);
        }
      });
    } else {
      Swal.fire({
        title: "Error!",
        text: "Debe Buscar El Documento",
        icon: "error",
        confirmButtonText: "Aceptar"
      });
    }
  }
}

var area_id = 0;
var tupa_id = 0;
var proced_id = 0;

$(document).ready(function () {
  $(".select2").select2();
  validarCheckEmpresa();
  validarCheckCiud();
  recargarTabla();
  cargardata();
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
    $("#tupa_id option:selected").each(function () {
      tupa_id = $(this).val();
      combo_proced(area_id, tupa_id);
      cargardata();
    });
  });

  $("#proced_id").change(function () {
    $("#proced_id option:selected").each(function () {
      proced_id = $(this).val();
      $("#procedimiento_id").val(proced_id);
      cargardata();
    });
  });
  $("#proced_id").change(function () {
    // Obtener el nombre del trámite seleccionado
    var nombreproced = $("#proced_id option:selected").text();

    // Actualizar el nombre del trámite en el encabezado del modal
    $("#nombreproced").text("Nombre del Trámite: " + nombreproced);
  });
});

function buscarDNI(ciudadano_doc) {
  // Obtener el valor del checkbox seleccionado
  var tipo_documento = $("input[name='tipo_documento']:checked").val();

  // Verificar qué tipo de documento está seleccionado
  if (tipo_documento === "DNI" || tipo_documento === "RUC") {
    var ciudadano_doc = $("#ciudadano_doc").val();
    $.post(
      "../../controller/ciudadano.php?op=consultar_dni",
      { ciudadano_doc: ciudadano_doc },
      function (data) {
        // Verifica si data no está vacía
        if (data.trim() !== "") {
          // Parsea la respuesta JSON del servidor
          var response = JSON.parse(data);
          console.log(response);
          // Verifica si se encontró la persona
          if (response.ciudadano_nombre) {
            $("#ciudadano_nombre").val(response.ciudadano_nombre);
            $("#ciudadano_apep").val(response.ciudadano_apep);
            $("#ciudadano_apem").val(response.ciudadano_apem);
            $("#ciud_id").val(response.ciudadano_id);
            $("#esCarnet").val("0");
            $("#esCPP").val("0");
            validarCheckCiud();
            $("#ciud_mensaje")
              .text("Ciudadano encontrado")
              .css("color", "green")
              .show();
            // Verifica si se recibió la foto del ciudadano
            if (response.ciud_foto) {
              // Actualiza la imagen con la foto recibida (base64)
              $("#imagen_ciudadano").attr("src", response.ciud_foto);
            }
            // Aquí bloqueamos los otros campos
            $("#ciudadano_nombre").attr("readonly", "readonly");
            $("#ciudadano_apep").attr("readonly", "readonly");
            $("#ciudadano_apem").attr("readonly", "readonly");
          } else {
            // Si no se encuentra a la persona, muestra el mensaje "No encontrado" en rojo
            $("#ciud_mensaje").text(response.error).css("color", "red").show();
            validarCheckCiud();
          }
        } else {
          // Si no se encuentra a la persona, muestra el mensaje "No encontrado" en rojo
          $("#ciud_mensaje")
            .text("Ciudadano no encontrado")
            .css("color", "red")
            .show();
        }
      }
    );
  } else if (tipo_documento === "Carnet de Extranjería") {
    $.post(
      "../../controller/ciudadano.php?op=consultar_carnet",
      { ciudadano_doc: ciudadano_doc },
      function (data) {
        // Verifica si data no está vacía
        if (data.trim() !== "") {
          // Parsea la respuesta JSON del servidor
          console.log(data);
          var response = JSON.parse(data);
          console.log(response);
          // Verifica si se encontró la persona
          if (response.ciudadano_nombre) {
            $("#ciudadano_nombre").val(response.ciudadano_nombre);
            $("#ciudadano_apep").val(response.ciudadano_apep);
            $("#ciudadano_apem").val(response.ciudadano_apem);
            $("#ciud_id").val(response.ciudadano_id);
            $("#esCarnet").val("1");
            $("#esCPP").val("0");
            validarCheckCiud();
            $("#ciud_mensaje")
              .text("Ciudadano encontrado")
              .css("color", "green")
              .show();

            // Verifica si se recibió la foto del ciudadano
            if (response.ciud_foto) {
              // Actualiza la imagen con la foto recibida (base64)
              $("#imagen_ciudadano").attr("src", response.ciud_foto);
            }

            // Aquí bloqueamos los otros campos
            $("#ciudadano_nombre").attr("readonly", "readonly");
            $("#ciudadano_apep").attr("readonly", "readonly");
            $("#ciudadano_apem").attr("readonly", "readonly");
            validarCheckCiud();
          } else {
            // Si no se encuentra a la persona, muestra el mensaje "No encontrado" en rojo
            $("#ciud_mensaje").text(response.error).css("color", "red").show();
            $("#esCarnet").val("0");
            validarCheckCiud();
          }
        } else {
          $("#esCarnet").val("0");
          // Si no se encuentra a la persona, muestra el mensaje "No encontrado" en rojo
          $("#ciud_mensaje")
            .text("Ciudadano no encontrado")
            .css("color", "red")
            .show();
        }
      }
    );
  } else if (tipo_documento === "Carne CPP") {
    $.post(
      "../../controller/ciudadano.php?op=consultar_CPP",
      { ciudadano_doc: ciudadano_doc },
      function (data) {
        // Verifica si data no está vacía
        if (data.trim() !== "") {
          // Parsea la respuesta JSON del servidor
          console.log(data);
          var response = JSON.parse(data);
          console.log(response);
          var $iconoCheck = $(".estado-icono .fa-check");
          var $iconoClose = $(".estado-icono .fa-close");
          // Verifica si se encontró la persona
          if (response.ciudadano_nombre) {
            $("#ciudadano_nombre").val(response.ciudadano_nombre);
            $("#ciudadano_apep").val(response.ciudadano_apep);
            $("#ciudadano_apem").val(response.ciudadano_apem);
            $("#ciud_id").val(response.ciudadano_id);
            $("#esCPP").val("1");
            $("#esCarnet").val("0");

            $("#ciud_mensaje")
              .text("Ciudadano encontrado")
              .css("color", "green")
              .show();

            // Verifica si se recibió la foto del ciudadano
            if (response.ciud_foto) {
              // Actualiza la imagen con la foto recibida (base64)
              $("#imagen_ciudadano").attr("src", response.ciud_foto);
            }
            // Aquí bloqueamos los otros campos
            $("#ciudadano_nombre").attr("readonly", "readonly");
            $("#ciudadano_apep").attr("readonly", "readonly");
            $("#ciudadano_apem").attr("readonly", "readonly");
          } else {
            // Si no se encuentra a la persona, muestra el mensaje "No encontrado" en rojo
            $("#ciud_mensaje").text(response.error).css("color", "red").show();
            $("#esCPP").val("1");
            $("#esCarnet").val("0");
            $("#ciudadano_nombre").removeAttr("readonly");
            $("#ciudadano_apep").removeAttr("readonly");
            $("#ciudadano_apem").removeAttr("readonly");
          }
        } else {
          $("#ciudadano_nombre").removeAttr("readonly");
          $("#ciudadano_apep").removeAttr("readonly");
          $("#ciudadano_apem").removeAttr("readonly");
          $("#esCPP").val("1");
          $("#esCarnet").val("0");

          // Si no se encuentra a la persona, muestra el mensaje "No encontrado" en rojo
          $("#ciud_mensaje")
            .text("Ciudadano no encontrado")
            .css("color", "red")
            .show();
        }
      }
    );
  } else {
    // Realizar acciones si no se seleccionó ningún tipo de documento
    Swal.fire({
      title: "Error!",
      text: "Debe ingresar El tipo de documento",
      icon: "error",
      confirmButtonText: "Aceptar"
    });
    // Habilitar nuevamente el botón "Buscar por DNI"
    $("#btnbuscarciu").prop("disabled", false).html("Consultar Doc");
  }
}
function buscaRUC() {
  var empr_ruc = $("#empr_ruc").val();
  $.post(
    "../../controller/empresa.php?op=consultar_sunat",
    { empr_ruc: empr_ruc },
    function (data) {
      if (data) {
        if (data.trim() !== "") {
          var response = JSON.parse(data);
          console.log(data);
          if (response.empr_ruc) {
            // Llenar los campos del formulario con los datos de la empresa
            $("#empr_razon_social").val(response.empr_razon_social);
            $("#empr_nombre_comercial").val(response.empr_nombre_comercial);
            $("#empr_giro").val(response.empr_nombres_giros);
            $("#empr_id").val(response.empr_id);

            // Otros campos
            $("#mensaje_empresa")
              .text("Empresa encontrada")
              .css("color", "green")
              .show();
            $("#spinner").removeClass("fa fa-spinner fa-spin");
            validarCheckEmpresa();
          } else {
            $("#mensaje_empresa")
              .text("Empresa no encontrada")
              .css("color", "red")
              .show();
            $("#spinner").removeClass("fa fa-spinner fa-spin");
          }
        } else {
          $("#mensaje_empresa")
            .text("Empresa no encontrada")
            .css("color", "red")
            .show();
          $("#spinner").removeClass("fa fa-spinner fa-spin");
        }
      }
    }
  );
}

function validarCheckEmpresa() {
  var empr_id = $("#empr_id").val();

  var $iconoCheck = $(".estado-icono-empr .fa-check");
  var $iconoClose = $(".estado-icono-empr .fa-close");

  if (empr_id !== "") {
    $iconoCheck.show();
    $iconoClose.hide();
  } else {
    $iconoCheck.hide();
    $iconoClose.show();
  }
}
function validarCheckCiud() {
  var ciudadano_nombre = $("#ciudadano_nombre").val();
  var ciud_id = $("#ciud_id").val();
  console.log(ciudadano_nombre);
  var $iconoCheck = $(".estado-icono .fa-check");
  var $iconoClose = $(".estado-icono .fa-close");
  if (ciudadano_nombre !== "" && ciud_id !== "") {
    // Todos los campos tienen valores, mostrar el ícono de check y ocultar el de close
    $iconoCheck.show();
    $iconoClose.hide();
  } else {
    // Al menos uno de los campos está vacío, mostrar el ícono de close y ocultar el de check
    $iconoCheck.hide();
    $iconoClose.show();
  }
}
function cargardata() {
  if ($.fn.DataTable.isDataTable("#detalle_data")) {
    $("#detalle_data").DataTable().destroy();
  }
  $("#detalle_data").DataTable({
    aProcessing: true,
    aServerSide: true,
    dom: "Bfrtip",
    buttons: [],
    ajax: {
      url: "../../controller/procedimiento.php?op=listar_proced_ciudadano",
      type: "post",
      data: { proced_id: proced_id }
    },
    bDestroy: true,
    responsive: true,
    bInfo: true,
    iDisplayLength: 10,
    order: [[3, "des"]],
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
        sPrevious: "Anterior"
      },
      oAria: {
        sSortAscending:
          ": Activar para ordenar la columna de manera ascendente",
        sSortDescending:
          ": Activar para ordenar la columna de manera descendente"
      }
    }
  });
  proced_id = 0;
}
function recargarTabla() {
  var button = document.getElementById("btnRecargar") || document.querySelector(".card-actions button") || document.querySelector(".btn-group button");
  var spinner = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
  var refreshIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-refresh" width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -5v5h5" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 5v-5h-5" /></svg>';

  $.post("../../controller/api_reload.php", function (data, status) {
    console.log(data);
  });

  // Mostrar spinner de carga
  if (button) {
    button.disabled = true;
    button.innerHTML = spinner;
  }

  // Ejecutar cargardata_reload dentro de un try...catch
  try {
    var procediminto_id = $("#proced_id").val();
    if (
      typeof procediminto_id !== "undefined" &&
      procediminto_id !== null &&
      procediminto_id !== ""
    ) {
      cargardata_reload(procediminto_id);
    } else if ($.fn.DataTable.isDataTable("#detalle_data")) {
      $("#detalle_data").DataTable().ajax.reload();
    }
  } catch (error) {
    console.error("Error al ejecutar cargardata_reload:", error);
  }

  // Restaurar el botón después de un tiempo
  setTimeout(function () {
    if (button) {
      button.disabled = false;
      button.innerHTML = refreshIcon;
    }
  }, 800);
}
function cargardata_reload(proced_id) {
  try {
    if (
      typeof procediminto_id !== "undefined" &&
      procediminto_id !== null &&
      procediminto_id !== ""
    ) {
      if ($.fn.DataTable.isDataTable("#detalle_data")) {
        $("#detalle_data").DataTable().destroy();
      }
      $("#detalle_data").DataTable({
        aProcessing: true,
        aServerSide: true,
        dom: "Bfrtip",
        buttons: [],
        ajax: {
          url: "../../controller/procedimiento.php?op=listar_proced_ciudadano",
          type: "post",
          data: { proced_id: proced_id }
        },
        bDestroy: true,
        responsive: false,
        bInfo: true,
        iDisplayLength: 10,
        order: [[3, "desc"]],
        language: {
          sProcessing: "Procesando...",
          sLengthMenu: "Mostrar _MENU_ registros",
          sZeroRecords: "No se encontraron resultados",
          sEmptyTable: "Ningún dato disponible en esta tabla",
          sInfo:
            "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
          sInfoEmpty:
            "Mostrando registros del 0 al 0 de un total de 0 registros",
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
            sPrevious: "Anterior"
          },
          oAria: {
            sSortAscending:
              ": Activar para ordenar la columna de manera ascendente",
            sSortDescending:
              ": Activar para ordenar la columna de manera descendente"
          }
        }
      });
    }
    proced_id = 0;
  } catch (error) {
    console.error("Error al ejecutar cargardata_reload:", error);
    // Manejar el error aquí
    // Puedes realizar acciones adicionales si deseas, como mostrar un mensaje de error personalizado
  }
}
function combo_areas() {
  $.post("../../controller/area.php?op=combo", function (data) {
    $("#area_id").html(data);
  });
}
function combo_tupa() {
  $.post("../../controller/tupa.php?op=combo", function (data) {
    $("#tupa_id").html(data);
  });
}
function eliminar(proceciudadano_id) {
  swal
    .fire({
      title: "Eliminar!",
      text: "Desea Eliminar el Registro?",
      icon: "error",
      confirmButtonText: "Si",
      showCancelButton: true,
      cancelButtonText: "No"
    })
    .then((result) => {
      if (result.value) {
        $.post(
          "../../controller/procedimiento.php?op=eliminar_proced_ciudadano",
          { proceciudadano_id: proceciudadano_id },
          function (data) {
            $("#detalle_data").DataTable().ajax.reload();

            Swal.fire({
              title: "Correcto!",
              text: "Se Elimino Correctamente",
              icon: "success",
              confirmButtonText: "Aceptar"
            });
          }
        );
      }
    });
}
function setProcedencia(proceciudadano_id) {
  swal
    .fire({
      title: "Cambiar Procedencia?!",
      text: "Desea cambiar el estado del proced?",
      icon: "warning",
      confirmButtonText: "Si",
      showCancelButton: true,
      cancelButtonText: "No"
    })
    .then((result) => {
      if (result.value) {
        $.post(
          "../../controller/procedimiento.php?op=cambiar_procedencia_proced_ciudadano",
          { proceciudadano_id: proceciudadano_id },
          function (data) {
            $("#detalle_data").DataTable().ajax.reload();

            Swal.fire({
              title: "Correcto!",
              text: "Se cambió el estado Correctamente",
              icon: "success",
              confirmButtonText: "Aceptar"
            });
          }
        );
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
      $("#proced_id").html(data);
    },
    error: function (xhr, status, error) {
      console.error("Error en la solicitud AJAX:", status, error);
    }
  });
}

function nuevo() {
  $("#ciudadano_doc").removeAttr("readonly");
  $("#empr_ruc").removeAttr("readonly");

  $("#ruc_checkbox").prop("checked", false);
  $("#ruc_checkbox").change();

  $("#ciudadano_nombre").attr("readonly", "readonly");
  $("#ciudadano_apep").attr("readonly", "readonly");
  $("#ciudadano_apem").attr("readonly", "readonly");
  var procedId = $("#proced_id").val();
  if (
    $("#proced_id").val() === "" ||
    $("#tupa_id").val() === "" ||
    $("#area_id").val() === "" ||
    procedId == null
  ) {
    // Mostrar mensaje de error si hay valores vacíos
    Swal.fire({
      title: "Error!",
      text: "Seleccionar procedimiento",
      icon: "error",
      confirmButtonText: "Aceptar"
    });
  } else {
    // Solo proceder si no hay valores vacíos
    $("#ciudadano_nombre").val("");
    $("#procedciudadano_id").val("");
    $("#ciud_id").val("");
    $("#empr_id").val("");
    $("#ciudadano_apep").val("");
    $("#ciudadano_apem").val("");
    $("#ciudadano_doc").val("");
    $("#empr_ruc").val("");
    $("#empr_razon_social").val("");
    $("#empr_nombre_comercial").val("");
    $("#esCarnet").val("");
    $("#esCPP").val("");
    $("#dni_checkbox").prop("checked", true);
    if (typeof toggleCheckboxes === "function") {
      toggleCheckboxes("dni_checkbox");
    }
    $("#modalmantenimiento").modal("show");
    setTimeout(function () {
      $("#ciudadano_doc").focus().select();
    }, 150);
    validarCheckEmpresa();
    validarCheckCiud();
    $("#mensaje_empresa")
      .text("Empresa encontrada")
      .css("color", "green")
      .hide();
    $("#ciud_mensaje")
      .text("Ciudadano encontrada")
      .css("color", "green")
      .hide();
  }
}

function mostrarError() {
  swal.fire({
    title: "Error!",
    text: "Ciudadano no encontrado",
    icon: "error",
    confirmButtonText: "Aceptar"
  });
}

function tablatasasciudadano(proceciudadano_id) {
  console.log(proceciudadano_id);
  $("#data_tasa").DataTable({
    aProcessing: true,
    aServerSide: true,
    searching: false,
    ordering: false,
    dom: "Bfrtip",
    buttons: [],
    ajax: {
      url: "../../controller/tasa.php?op=listar_proceds_tasa_x_procedciudadano",
      type: "post",
      data: { proceciudadano_id: proceciudadano_id }
    },
    bDestroy: true,
    responsive: true,
    bInfo: false,
    iDisplayLength: 10,
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
        sPrevious: "Anterior"
      },
      oAria: {
        sSortAscending:
          ": Activar para ordenar la columna de manera ascendente",
        sSortDescending:
          ": Activar para ordenar la columna de manera descendente"
      }
    }
  });
}
function cambiarcomentario(ogciud_id) {
  console.log(ogciud_id);
  Swal.fire({
    title: "Actualizar Comentario",
    text: "Ingrese el nuevo comentario para este registro:",
    icon: "question",
    input: "textarea",
    inputPlaceholder: "Comentario",
    showCancelButton: true,
    confirmButtonText: "Actualizar",
    cancelButtonText: "Cancelar",
    cancelButtonColor: "#d33",
    confirmButtonColor: "#3085d6",
    inputValidator: (value) => {
      if (!value) {
        return "El comentario no puede estar vacío";
      }
    }
  }).then((result) => {
    if (result.isConfirmed) {
      var comentario = result.value;

      // Enviar solicitud AJAX para actualizar el comentario
      $.post(
        "../../controller/rc.php?op=cambiarComentario",
        { ogciud_id: ogciud_id, comentario: comentario },
        function (data) {
          if (data.error) {
            Swal.fire({
              title: "Error al actualizar comentario",
              text: data.message,
              icon: "error",
              confirmButtonText: "Aceptar",
              confirmButtonColor: "#d33"
            });
          } else {
            Swal.fire({
              title: "Comentario actualizado",
              text: "El comentario se ha actualizado correctamente.",
              icon: "success",
              confirmButtonText: "Aceptar",
              confirmButtonColor: "#5cb85c"
            });
            $("#data_ordenes").DataTable().ajax.reload();
          }
        }
      ).fail(function (jqXHR, textStatus, errorThrown) {
        Swal.fire({
          title: "Error al actualizar comentario",
          text: "Ocurrió un error al enviar la solicitud: " + textStatus,
          icon: "error",
          confirmButtonText: "Aceptar",
          confirmButtonColor: "#d33"
        });
      });
    }
  });
}

function tablaGiros(procedciudadano_id) {
  if ($.fn.DataTable.isDataTable("#data_ordenes")) {
    $("#data_ordenes").DataTable().destroy();
  }
  $("#data_ordenes").DataTable({
    aProcessing: true,
    aServerSide: false,
    dom: "Bfrtip",
    searching: false,
    ordering: false,
    buttons: [],
    ajax: {
      url: "../../controller/rc.php?op=listargiros",
      type: "post",
      data: { procedciudadano_id: procedciudadano_id },
      dataSrc: ""
    },
    columns: [
      { data: "ogciud_id", className: "text-center fw-bold" },
      { data: "fecha", className: "text-center" },
      { data: "ogciud_comentario" },
      { data: "acciones.editar", className: "text-center" },
      { data: "acciones.imprimir", className: "text-center" }
    ],
    bDestroy: true,
    responsive: true,
    iDisplayLength: 10,
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
      sUrl: "",
      sInfoThousands: ",",
      sLoadingRecords: "Cargando...",
      oPaginate: {
        sFirst: "Primero",
        sLast: "Último",
        sNext: "Siguiente",
        sPrevious: "Anterior"
      },
      oAria: {
        sSortAscending:
          ": Activar para ordenar la columna de manera ascendente",
        sSortDescending:
          ": Activar para ordenar la columna de manera descendente"
      }
    }
  });
}
function editar(procedciudadano_id) {
  if ($("#ruc_checkbox").prop("checked")) {
    $("#ruc_checkbox").prop("checked", false).change();
  }
  $("#ciudadano_doc").removeAttr("readonly");
  $("#empr_ruc").removeAttr("readonly");
  $.post(
    "../../controller/procedimiento.php?op=mostrar_procedciudadano",
    { procedciudadano_id: procedciudadano_id },
    function (data) {
      data = JSON.parse(data);
      console.log(data);
      $("#procedciudadano_id").val(data.procedciudadano_id);
      console.log(procedciudadano_id);
      $("#ciudadano_nombre").val(data.ciudadano_nombre);
      $("#ciudadano_apep").val(data.ciudadano_apep);
      $("#ciudadano_apem").val(data.ciudadano_apem);
      $("#ciudadano_doc").val(data.ciudadano_doc);
      $("#vehi_placa").val(data.ciudadano_placa);
      $("#ciud_id").val(data.ciud_id);

      if ($("#empr_ruc").val() !== "") {
        $("#empr_ruc").val("");
        $("#empr_razon_social").val("");
        $("#empr_nombre_comercial").val("");
        $("#empr_direccion").val("");
        $("#empr_direccion").change();
        $("#ruc_checkbox").prop("checked", true);
        $("#ruc_checkbox").change();
      } else {
        $("#ruc_checkbox").prop("checked", false);
        $("#ruc_checkbox").change();
        $("#empr_ruc").val(data.empr_ruc);
        $("#empr_razon_social").val(data.empr_ruc);
        $("#empr_nombre_comercial").val(data.empr_ruc);
        $("#empr_direccion").val(data.empr_id);
        $("#empr_direccion").change();
      }
      if (data.ciud_sexo === "" || data.ciud_sexo === null) {
        $("#ciud_sex").prop("disabled", false); // Desactivar el select
      } else {
        $("#ciud_sex").prop("disabled", true); // Activar el select
        $("#ciud_sex").val(data.ciud_sexo);
        $("#ciud_sex").change();
      }
      $("#dateMask").attr("readonly", "readonly");
      $("#dateMask").val("");
      $("#dni_checkbox").prop("checked", data.ciudadano_doc.length <= 8);
      $("#ce_checkbox").prop("checked", data.ciudadano_doc.length > 8);
      $("#ciudadano_nombre").attr("readonly", "readonly");
      $("#ciudadano_apep").attr("readonly", "readonly");
      $("#ciudadano_apem").attr("readonly", "readonly");
    }
  );

  $("#lbltitulo").html("Editar Registro");
  $("#modalmantenimiento").modal("show");
}

function pagar(tasatciudadano_id, procedciudadano_id, estadotasa) {
  estado_procedimiento = $("#estado_procedimiento").val();
  var estadosValidos = true;
  if (isNaN(estadotasa)) {
    estadotasa = 1;
  }
  if (
    estadotasa != 1 ||
    estado_procedimiento == 0 ||
    estado_procedimiento == 3
  ) {
    estadosValidos = false;
  }

  if (!estadosValidos) {
    Swal.fire({
      title: "Error!",
      text: 'Solo puedes Girar tasas con estado "Pendiente", verificar el estado de la tasa o del procedimiento',
      icon: "error",
      confirmButtonText: "Aceptar"
    });
  } else {
    swal
      .fire({
        title: "Confirmar Orden de Giro",
        text: "¿Está seguro de que desea realizar el Orden de Giro para este registro?",
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Sí, Girar",
        cancelButtonText: "No, Cancelar",
        cancelButtonColor: "#d33",
        confirmButtonColor: "#3085d6",
        input: "text",
        inputPlaceholder: "Ingrese un comentario"
      })
      .then((result) => {
        if (result.isConfirmed) {
          var comentario = result.value;
          $.post(
            "../../controller/tasa.php?op=Pagar_Order_Giro",
            { tasatciudadano_id: tasatciudadano_id, comentario: comentario },
            function (data) {
              // Recargar las tablas después de pagar la orden de giro
              $("#detalle_data").DataTable().ajax.reload();
              $("#data_tasa").DataTable().ajax.reload();

              Swal.fire({
                title: "Orden de Giro Exitoso",
                text: "Espere a la validación del pago.",
                icon: "success",
                confirmButtonText: "Aceptar",
                confirmButtonColor: "#5cb85c"
              });
            }
          );
        }
      });
  }
}
function pagargrupo() {
  table = $("#data_tasa").DataTable();
  var tasatciud_id = [];
  var estadosValidos = true; // Variable para realizar la validación de estados

  table.rows().every(function (rowIdx, tableLoop, rowLoop) {
    cell1 = table.cell({ row: rowIdx, column: 0 }).node();
    if ($("input", cell1).prop("checked") == true) {
      id = $("input", cell1).val();
      tasatciud_id.push(id);

      // Obtener el estado de la tasa desde el atributo de datos
      var estado = $("input", cell1).data("estado");
      estado_procedimiento = $("#estado_procedimiento").val();
      console.log(estado_procedimiento);
      // Verificar si el estado no es 1 (Pendiente)
      if (
        estado != 1 ||
        estado_procedimiento == 0 ||
        estado_procedimiento == 3
      ) {
        estadosValidos = false;
      }
    }
  });

  if (tasatciud_id.length === 0) {
    Swal.fire({
      title: "Error!",
      text: "Seleccionar tasas",
      icon: "error",
      confirmButtonText: "Aceptar"
    });
  } else if (!estadosValidos) {
    Swal.fire({
      title: "Error!",
      text: 'Solo puedes Girar tasas con estado "Pendiente", verificar el estado de la tasa o del procedimiento',
      icon: "error",
      confirmButtonText: "Aceptar"
    });
  } else {
    // Deshabilitar el botón "Pagar todo" y mostrar un indicador de carga
    var girarGrupoHtml = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-coin me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M14.8 9a2 2 0 0 0 -1.8 -1h-2a2 2 0 1 0 0 4h2a2 2 0 1 1 0 4h-2a2 2 0 0 1 -1.8 -1" /><path d="M12 7v10" /></svg> <span>Girar en Grupo</span>';
    $("#IDpagarGrupo")
      .prop("disabled", true)
      .html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Procesando...');

    // Pedir al usuario que ingrese un comentario
    Swal.fire({
      title: "Ingrese un comentario",
      input: "text",
      inputPlaceholder: "Ingrese un comentario",
      showCancelButton: true,
      confirmButtonText: "Girar",
      cancelButtonText: "Cancelar",
      showLoaderOnConfirm: true,
      allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
      if (result.isConfirmed) {
        // Obtener el comentario ingresado por el usuario
        var comentario = result.value;

        // Creando formulario
        const formData = new FormData($("#form_detalle")[0]);
        formData.append("tasatciud_id", tasatciud_id);
        formData.append("comentario", comentario);
        console.log(tasatciud_id);
        $.ajax({
          url: "../../controller/tasa.php?op=Pagar_Ordern_Giro_grupo",
          type: "POST",
          data: formData,
          contentType: false,
          processData: false,
          success: function (data) {
            // Código cuando la operación fue exitosa
            Swal.fire({
              title: "Éxito!",
              text: "Las tasas han sido pagadas exitosamente.",
              icon: "success",
              confirmButtonText: "Aceptar"
            });

            // Habilitar nuevamente el botón "Girar en Grupo"
            $("#IDpagarGrupo").prop("disabled", false).html(girarGrupoHtml);
          }
        });

        $("#detalle_data").DataTable().ajax.reload();
        $("#data_tasa").DataTable().ajax.reload();
      } else {
        // Si el usuario cancela, habilitar nuevamente el botón "Girar en Grupo"
        $("#IDpagarGrupo").prop("disabled", false).html(girarGrupoHtml);
      }
    });
  }
}

function imprimirGrupo() {
  table = $("#data_tasa").DataTable();
  var tasatciudadano_id = [];
  var estadosValidos = true; // Variable para realizar la validación de estados

  table.rows().every(function (rowIdx, tableLoop, rowLoop) {
    cell1 = table.cell({ row: rowIdx, column: 0 }).node();
    if ($("input", cell1).prop("checked") == true) {
      id = $("input", cell1).val();
      tasatciudadano_id.push(id);

      // Obtener el estado de la tasa desde el atributo de datos
      var estado = $("input", cell1).data("estado");

      // Verificar si el estado no es 2
      if (estado === 3 || estado === 1) {
        estadosValidos = false;
      }
    }
  });

  if (tasatciudadano_id.length === 0) {
    Swal.fire({
      title: "Error!",
      text: "Seleccionar tasas",
      icon: "error",
      confirmButtonText: "Aceptar"
    });
  } else if (!estadosValidos) {
    Swal.fire({
      title: "Error!",
      text: 'No se pueden imprimir tasas con estados diferentes de "Pagado"',
      icon: "error",
      confirmButtonText: "Aceptar"
    });
  } else {
    /* Creando formulario */
    const formData = new FormData($("#form_detalle")[0]);
    formData.append("tasatciudadano_id", tasatciudadano_id);
    redirect_by_post(
      "../../controller/rc.php?op=imprimir",
      { tasatciudadano_id: tasatciudadano_id.join(",") },
      true
    );
  }
}
function imprimir(tasatciudadano_id) {
  redirect_by_post(
    "../../controller/rc.php?op=imprimir",
    { tasatciudadano_id, tasatciudadano_id },
    true
  );
}
function imprimirGiro(ogciud_id) {
  console.log(ogciud_id);
  redirect_by_post(
    "../../controller/rc.php?op=imprimirxid",
    { ogciud_id, ogciud_id },
    true
  );
}

function cancelarPago(tasatciudadano_id, proceciudadano_id) {
  swal
    .fire({
      title: "Cancelar Pago",
      text: "¿Está seguro de que desea cancelar el pago para este registro?",
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Sí",
      cancelButtonText: "No",
      cancelButtonColor: "#d33",
      confirmButtonColor: "#3085d6"
    })
    .then((result) => {
      if (result.isConfirmed) {
        $.post(
          "../../controller/tasa.php?op=CancelarPago",
          { tasatciudadano_id: tasatciudadano_id },
          function (data) {
            $("#detalle_data").DataTable().ajax.reload();
            $("#data_tasa").DataTable().ajax.reload();

            Swal.fire({
              title: "Proceso Exitoso",
              icon: "success",
              confirmButtonText: "Aceptar",
              confirmButtonColor: "#5cb85c"
            });
            var progressBar = document.getElementById("Bar");
            if (progressBar) {
              progressBar.innerHTML = "";
            }
            progressbar_carga(proceciudadano_id);
          }
        );
      }
    });
}

function redirect_by_post(purl, pparameters, in_new_tab) {
  pparameters = typeof pparameters == "undefined" ? {} : pparameters;
  in_new_tab = typeof in_new_tab == "undefined" ? true : in_new_tab;

  var form = document.createElement("form");
  $(form)
    .attr("id", "reg-form")
    .attr("name", "reg-form")
    .attr("action", purl)
    .attr("method", "post")
    .attr("enctype", "multipart/form-data");
  if (in_new_tab) {
    $(form).attr("target", "_blank");
  }
  $.each(pparameters, function (key) {
    $(form).append(
      '<input type="text" name="' + key + '" value="' + this + '" />'
    );
  });
  document.body.appendChild(form);
  form.submit();
  document.body.removeChild(form);

  return false;
}

function ver(tamiteciudadano_id) {
  var estado = parseInt($("#" + tamiteciudadano_id).data("estado"));
  $("#modaltasas").modal("show");
  console.log(tamiteciudadano_id);
  $("#estado_procedimiento").val(estado); // Establecer el valor del estado en el input oculto
  tablatasasciudadano(tamiteciudadano_id);
  $("#procedciud_id").val(tamiteciudadano_id);
}

function editarComentario() {
  $("#modaltasas").modal("hide");
  $("#modaleditcomentario").modal("handleUpdate");
  $("#modaleditcomentario").modal("show");
  $("#modaleditcomentario").modal("handleUpdate");
  tablaGiros($("#procedciud_id").val());
}

function imprimirInformacion() {
  var proced_id = $("#proced_id").val(); // Obtener el valor del trámite seleccionado
  console.log(proced_id);
  if (
    $("#proced_id").val() === "" ||
    $("#tupa_id").val() === "" ||
    $("#area_id").val() === "" ||
    proced_id == null
  ) {
    // Mostrar mensaje de error si hay valores vacíos
    Swal.fire({
      title: "Error!",
      text: "Seleccionar proced",
      icon: "error",
      confirmButtonText: "Aceptar"
    });
  } else {
    redirect_by_post(
      "../../controller/portadaproced.php?op=imprimirInfo",
      { proced_id: proced_id },
      true
    );
  }
}

init();
