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
        if (typeof ejecutarBusquedaDoc === "function") {
          ejecutarBusquedaDoc();
        } else if (typeof limitabuscadni === "function") {
          limitabuscadni(e.target);
        }
      } else if (e.target.id === "empr_ruc" && typeof limitarbuscarruc === "function") {
        limitarbuscarruc(e.target);
      }
    }
  });
}

$("#empr_razon_social").on("change", function () {

  // let direcciones = $(this).find(":selected").data("direcciones") || [];
  let nombre = $(this).find(":selected").data("nombre") || "";
  let id = $(this).val();

  $("#empr_id").val(id);
  $("#empr_nombre_comercial").val(nombre);

  // let $direccion = $("#select_direccion");
  // $direccion.empty().append('<option value="">Seleccione dirección</option>');

  // if (direcciones.length > 0) {
  //   direcciones.forEach(dir => {
  //     $direccion.append(`
  //       <option value="${dir}">
  //         ${dir}
  //       </option>
  //     `);
  //   });
  //   $direccion.prop("disabled", false);
  // } else {
  //   $direccion.prop("disabled", true);
  // }
});


document.addEventListener("DOMContentLoaded", function () {
  var modalPayment = document.getElementById("modal-payment");
  if (modalPayment) {
    modalPayment.addEventListener("shown.bs.modal", function () {
      document.getElementById("comentarioInput")?.focus();
    });
  }

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
  let tipo_admin = $("#tipo_admin").val();
  let ciud_id = $("#ciud_id").val();
  let ciud_doc = $("#ciudadano_doc").val().trim();
  let escpp = $("#esCPP").val();
  let menor_edad = $("#menor_edad").val();
  let esCarnet = $("#esCarnet").val();
  let empr_id = $("#empr_id").val();
  let empr_ruc = $("#empr_ruc").val().trim();
  let empr_razon_social = $("#empr_razon_social").val().trim();

  // Validación de DNI y RUC antes de proceder
  if (tipo_admin === "C" || tipo_admin === "D") {
    if (!ciud_id || ciud_doc.length < 8) {
      Swal.fire({
        title: "Error!",
        text: "Debe ingresar y buscar un Documento válido para Ciudadano (mínimo 8 dígitos).",
        icon: "error",
        confirmButtonText: "Aceptar",
        timer: 3000
      });
      return;
    }
  } else if (tipo_admin === "E") {
    // Ahora se valida que AMBOS sean obligatorios (DNI y RUC)
    if (!ciud_id || ciud_doc.length < 8) {
      Swal.fire({
        title: "Error!",
        text: "Debe ingresar y buscar un Documento válido para Ciudadano (mínimo 8 dígitos).",
        icon: "error",
        confirmButtonText: "Aceptar",
        timer: 3000
      });
      return;
    }

    if (empr_ruc.length < 11) {
      Swal.fire({
        title: "Error!",
        text: "Debe ingresar y buscar un RUC válido para Empresa (mínimo 11 dígitos).",
        icon: "error",
        confirmButtonText: "Aceptar",
        timer: 3000
      });
      return;
    }
	
	if (!empr_razon_social){
      Swal.fire({
        title: "Error!",
        text: "La razón social esta vacio",
        icon: "error",
        confirmButtonText: "Aceptar",
        timer: 3000
      });
      return;
    }
  }

  // Bloquear el botón y mostrar animación de carga
  $("#btnguardar").prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Procesando...');

  let formData = new FormData($("#ciudadanosdetalles_form")[0]);
  formData.append("tipo_proced", "ciudadano");
  formData.append("proced_id", $("#proced_id").val());
  formData.append("ciud_id", ciud_id || "");
  formData.set("empr_id", $("#select_direccion").val() || $("#empr_id").val());
  formData.append("procedciudadano_id", $("#procedciudadano_id").val());
  formData.append("empr_razon_social", $("#empr_razon_social").val());

  let tido_id = 1;
  if (esCarnet === "1") tido_id = 3;
  if (escpp === "1") tido_id = 4;
  formData.append("tido_id", tido_id);

  $.ajax({
    url: "../../controller/procedimiento.php?op=abrirproced",
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    success: function (response) {
      let res = JSON.parse(response);
      $("#btnguardar").prop("disabled", false).html('<i class="fa fa-check"></i> Guardar');

      if (res.success) {
        $("#detalle_data").DataTable().ajax.reload();
        $("#modalmantenimiento").modal("hide");

        if (res.procedciudadano_id) {
          const Toast = Swal.mixin({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true
          });
          Toast.fire({
            icon: "success",
            title: `Registrado: ${res.codigo}`
          });

          // Encadenar apertura directa del modal de tasas
          setTimeout(function () {
            ver(res.procedciudadano_id);
          }, 350);
        } else {
          Swal.fire({
            title: "¡Correcto!",
            text: `Se Registró Correctamente.\nCódigo: ${res.codigo}`,
            icon: "success",
            confirmButtonText: "Aceptar",
            timer: 4000
          });
        }
      } else {
        Swal.fire({
          title: "Error!",
          text: res.message,
          icon: "error",
          confirmButtonText: "Aceptar",
          timer: 3000
        });
      }
    },
    error: function (xhr, status, error) {
      $("#btnguardar").prop("disabled", false).html('<i class="fa fa-check"></i> Guardar');
      console.error("Error en la solicitud AJAX:", status, error);
    }
  });

}


var area_id = 0;
var proced_id = 0;

$(document).ready(function () {
  $(".select2").select2();
  combo_tupa();


  getdataload();
  recargarTabla();
  cargardata();
  combo_areas();


  $("#area_id").change(function () {
    $("#area_id option:selected").each(function () {
      area_id = $(this).val();
      combo_proced(area_id);
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

  // Calculo en vivo del total de tasas seleccionadas
  $(document).on("change input", "#tasaListContainer input[type='checkbox'], #tasaListContainer input[type='number']", function () {
    calcularTotalTasas();
  });
});



function buscarDNI(ciudadano_doc, tipo_documento) {
  var doc = (ciudadano_doc || $("#ciudadano_doc").val() || "").toString().trim();
  var tipo = (tipo_documento || $("#name_select_tipo").text() || "DNI").trim();

  // Verificar qué tipo de documento está seleccionado
  if (tipo === "DNI" || tipo === "RUC") {
    if (doc.length > 8) {
      doc = doc.slice(0, 8);
    }
    $.post(
      "../../controller/ciudadano.php?op=consultar_dni",
      { ciudadano_doc: doc },
      function (data) {
        if (data.trim() !== "") {
          var response = JSON.parse(data);

          if (response.ciudadano_nombre) {
            $("#ciudadano_nombre").val(response.ciudadano_nombre);
            $("#ciudadano_apep").val(response.ciudadano_apep);
            $("#ciudadano_apem").val(response.ciudadano_apem);
            $("#ciud_id").val(response.ciudadano_id);
            $("#esCarnet").val("0");
            $("#esCPP").val("0");

            $("#ciud_mensaje")
              .attr("class", "alert alert-success py-1 mb-2")
              .text("Ciudadano encontrado")
              .removeClass("d-none")
              .show();
            if (response.ciud_foto) {
              $("#imagen_ciudadano").attr("src", response.ciud_foto);
            }
            $("#ciudadano_nombre, #ciudadano_apep, #ciudadano_apem").attr("readonly", "readonly");
          } else {
            $("#ciud_mensaje")
              .attr("class", "alert alert-danger py-1 mb-2")
              .text(response.error || "Ciudadano no encontrado")
              .removeClass("d-none")
              .show();
          }
        } else {
          $("#ciud_mensaje")
            .attr("class", "alert alert-danger py-1 mb-2")
            .text("Ciudadano no encontrado")
            .removeClass("d-none")
            .show();
        }
      }
    );
  } else if (tipo === "CEE") {
    $.post(
      "../../controller/ciudadano.php?op=consultar_carnet",
      { ciudadano_doc: doc },
      function (data) {
        if (data.trim() !== "") {
          var response = JSON.parse(data);

          if (response.ciudadano_nombre) {
            $("#ciudadano_nombre").val(response.ciudadano_nombre);
            $("#ciudadano_apep").val(response.ciudadano_apep);
            $("#ciudadano_apem").val(response.ciudadano_apem);
            $("#ciud_id").val(response.ciudadano_id);
            $("#esCarnet").val("1");
            $("#esCPP").val("0");

            $("#ciud_mensaje")
              .attr("class", "alert alert-success py-1 mb-2")
              .text("Ciudadano encontrado")
              .removeClass("d-none")
              .show();

            if (response.ciud_foto) {
              $("#imagen_ciudadano").attr("src", response.ciud_foto);
            }
            $("#ciudadano_nombre, #ciudadano_apep, #ciudadano_apem").attr("readonly", "readonly");
          } else {
            $("#ciud_mensaje")
              .attr("class", "alert alert-danger py-1 mb-2")
              .text(response.error || "No encontrado")
              .removeClass("d-none")
              .show();
            $("#esCarnet").val("0");
          }
        } else {
          $("#esCarnet").val("0");
          $("#ciud_mensaje")
            .attr("class", "alert alert-danger py-1 mb-2")
            .text("Ciudadano no encontrado")
            .removeClass("d-none")
            .show();
        }
      }
    );
  } else if (tipo === "CPP") {
    $.post(
      "../../controller/ciudadano.php?op=consultar_CPP",
      { ciudadano_doc: doc },
      function (data) {
        if (data.trim() !== "") {
          var response = JSON.parse(data);

          if (response.ciudadano_nombre) {
            $("#ciudadano_nombre").val(response.ciudadano_nombre);
            $("#ciudadano_apep").val(response.ciudadano_apep);
            $("#ciudadano_apem").val(response.ciudadano_apem);
            $("#ciud_id").val(response.ciudadano_id);
            $("#esCPP").val("1");
            $("#esCarnet").val("0");

            $("#ciud_mensaje")
              .attr("class", "alert alert-success py-1 mb-2")
              .text("Ciudadano encontrado")
              .removeClass("d-none")
              .show();

            if (response.ciud_foto) {
              $("#imagen_ciudadano").attr("src", response.ciud_foto);
            }
            $("#ciudadano_nombre, #ciudadano_apep, #ciudadano_apem").attr("readonly", "readonly");
          } else {
            $("#ciud_mensaje")
              .attr("class", "alert alert-danger py-1 mb-2")
              .text(response.error || "No encontrado")
              .removeClass("d-none")
              .show();
            $("#esCPP").val("1");
            $("#esCarnet").val("0");
            $("#ciudadano_nombre, #ciudadano_apep, #ciudadano_apem").removeAttr("readonly");
          }
        } else {
          $("#ciudadano_nombre, #ciudadano_apep, #ciudadano_apem").removeAttr("readonly");
          $("#esCPP").val("1");
          $("#esCarnet").val("0");

          $("#ciud_mensaje")
            .attr("class", "alert alert-danger py-1 mb-2")
            .text("Ciudadano no encontrado")
            .removeClass("d-none")
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

  let empr_ruc = $("#empr_ruc").val().trim();
  if (!empr_ruc) return;

  $.post(
    "../../controller/empresa.php?op=consultar_sunat",
    { empr_ruc },
    function (data) {

      let response;
      try {
        response = JSON.parse(data);
      } catch (e) {
        console.error("Respuesta inválida:", data);
        return;
      }
      console.log(response);

      if (response.empresas && response.empresas.length > 0) {

        let empresa = response.empresas[0];
        let ruc = empresa.empr_ruc;
        let razon = empresa.empr_razon_social;
        let estado = empresa.empr_estado_sunat;
        let condicion = empresa.empr_condicion_sunat;

        // 🔹 Llenar razón social
        $("#empr_razon_social").val(razon);

        let colorEstado = (estado === "ACTIVO") ? "#198754" : "#dc3545";
        let colorCondicion = (condicion === "HABIDO") ? "#198754" : "#dc3545";

        Swal.fire({
          title: "Resultado de Consulta RUC",
          width: 650,
          confirmButtonText: "Aceptar",
          confirmButtonColor: "#0d6efd",
          allowOutsideClick: false,
          allowEscapeKey: false,
          backdrop: true,
          html: `
            <div style="text-align:left; font-size:14px;">

              <div style="background:#f1f3f5; padding:18px; border-radius:8px; border:1px solid #ced4da;">

                <div style="margin-bottom:15px;">
                  <div style="font-weight:600; color:#495057;">RUC Consultado</div>
                  <div style="font-size:16px;">${ruc}</div>
                </div>

                <div style="margin-bottom:15px;">
                  <div style="font-weight:600; color:#495057;">Razón Social</div>
                  <div style="font-size:16px;">${razon}</div>
                </div>

                <hr style="margin:18px 0;">

                <div style="margin-bottom:15px;">
                  <div style="font-weight:600; color:#495057;">
                    Estado del Contribuyente (Información SUNAT)
                  </div>
                  <div style="font-size:16px; font-weight:bold; color:${colorEstado};">
                    ${estado}
                  </div>
                </div>

                <div>
                  <div style="font-weight:600; color:#495057;">
                    Condición del Domicilio Fiscal (Información SUNAT)
                  </div>
                  <div style="font-size:16px; font-weight:bold; color:${colorCondicion};">
                    ${condicion}
                  </div>
                </div>

              </div>

              <div style="margin-top:12px; font-size:12px; color:#6c757d;">
                * Datos obtenidos directamente del servicio de consulta SUNAT.
              </div>

            </div>
          `
        });

        $("#mensaje_empresa")
          .removeClass("d-none")
          .text("Empresa encontrada")
          .css("color", "green");

      } else {

        $("#mensaje_empresa")
          .removeClass("d-none")
          .text("Empresa no encontrada")
          .css("color", "red");
      }
    }
  );
}




function cargardata() {
  if ($.fn.DataTable.isDataTable("#detalle_data")) {
    $("#detalle_data").DataTable().destroy();
  }
  $("#detalle_data").DataTable({
    aProcessing: true,
    aServerSide: true,
    dom: "Bfrtip",
    ordering: false,
    buttons: [],
    ajax: {
      url: "../../controller/procedimiento.php?op=listar_proced_ciudadano_usuario",
      type: "post",
      data: { proced_id: proced_id }
    },
    bDestroy: true,
    responsive: true,
    bInfo: false,
    iDisplayLength: 10,
    language: {
      sProcessing: "Procesando...",
      sLengthMenu: "Mostrar _MENU_ registros",
      sZeroRecords: "No se encontraron resultados",
      sEmptyTable: "Ningún dato disponible en esta tabla o Falta Seleccionar una Opción",
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
function eliminar(proceciudadano_id, est, orden_giro) {

  swal
    .fire({
      title: "Eliminar!",
      text: "Desea Eliminar el Registro?",
      icon: "error",
      confirmButtonText: "Si",
      showCancelButton: true,
      cancelButtonText: "No",
      input: "text",
      inputPlaceholder: "Ingrese su DNI"
    })
    .then((result) => {
      if (result.isConfirmed) {
        var dni = result.value;
        // Obtener el DNI de la sesión
        var usu_dni = $("#usua_dni_SIGODT").val();
        // Comparar el DNI ingresado con el de la sesión
        if (dni === usu_dni) {
          // Verificar si el estado es 1 o 2
          if (est === 1 || est === 2 || est === 4 || est === 9) {
            // Si los DNI coinciden y el estado es 1 o 2, proceder con la eliminación
            $.post(
              "../../controller/procedimiento.php?op=eliminar_proced_ciudadano",
              { proceciudadano_id: proceciudadano_id,
                orden_giro: orden_giro
               },
              function (data) {
                $("#detalle_data").DataTable().ajax.reload();
                Swal.fire({
                  title: "Correcto!",
                  text: "Se Eliminó Correctamente",
                  icon: "success",
                  confirmButtonText: "Aceptar"
                });
              }
            );
          } else {
            // Si el estado no es 1 o 2, mostrar un mensaje de error
            Swal.fire({
              title: "Error!",
              text: "El estado del registro no permite la eliminación.",
              icon: "error",
              confirmButtonText: "Aceptar"
            });
          }
        } else {
          // Si los DNI no coinciden, mostrar un mensaje de error
          Swal.fire({
            title: "Error!",
            text: "El DNI ingresado no coincide con el de la sesión. No tiene permiso para eliminar el registro.",
            icon: "error",
            confirmButtonText: "Aceptar"
          });
        }
      }
    });
}
function recargarTabla(event) {
  // Si se recibe el evento, prevenir la acción predeterminada
  if (event && event.preventDefault) {
    event.preventDefault();
  }

  const button = document.querySelector("#btnRecargar");
  if (!button) {
    console.error("El botón con id 'btnRecargar' no se encontró en el documento.");
    return;
  }

  const spinner = '<i class="fa fa-spinner fa-spin"></i>'; // Spinner de carga

  // Mostrar spinner de carga y deshabilitar el botón 
  button.disabled = true;
  button.innerHTML = spinner;

  try {
    // Obtener el valor del procedimiento
    let procedimiento_id = $("#proced_id").val();
    if (procedimiento_id && procedimiento_id !== "") {
      cargardata_reload(procedimiento_id);
    } else {
      console.warn("No se seleccionó ningún procedimiento.");
    }
  } catch (error) {
    console.error("Error al ejecutar cargardata_reload:", error);
  }

  setTimeout(() => {
    button.disabled = false;
    button.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24"
                           stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                           <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                           <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" />
                           <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" />
                         </svg>`;
  }, 1000);

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
        ordering: false,
        dom: "Bfrtip",
        buttons: [],
        ajax: {
          url: "../../controller/procedimiento.php?op=listar_proced_ciudadano_usuario",
          type: "post",
          data: { proced_id: proced_id }
        },
        bDestroy: true,
        responsive: false,
        bInfo: false,
        iDisplayLength: 10,
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
function calcularTotalTasas() {
  let total = 0;
  let seleccionadas = 0;
  let totalDisponibles = 0;

  $("#tasaListContainer .list-group-item").each(function () {
    const $checkbox = $(this).find("input[type='checkbox']");
    if ($checkbox.length && $checkbox.data("estado") == 1) {
      totalDisponibles++;
      if ($checkbox.prop("checked")) {
        const monto = parseFloat($checkbox.data("monto")) || 0;
        const $cantInput = $(this).find("input[type='number']");
        const cantidad = $cantInput.length ? (parseInt($cantInput.val()) || 1) : 1;
        total += monto * cantidad;
        seleccionadas++;
      }
    }
  });

  if (totalDisponibles > 1) {
    $("#IDpagarGrupo").html(
      `<i class="fa fa-dollar mr-2"></i> Girar Seleccionadas (${seleccionadas}) - S/ ${total.toFixed(2)}`
    );
    $("#IDpagarGrupo").prop("disabled", seleccionadas === 0);
  }
}

function listarTasas(proceciudadano_id) {
  // Muestra un spinner dentro del contenedor de tasas
  $("#tasaListContainer").html(
    '<div id="tablaSpinner" style="text-align: center; padding: 20px;">' +
    '<i class="fa fa-spinner fa-spin" style="font-size: 24px;"></i>' +
    '</div>'
  );

  $.ajax({
    url: "../../controller/tasa.php?op=listar_proceds_tasa_x_procedciudadano_usu",
    type: "POST",
    data: { proceciudadano_id: proceciudadano_id },
    dataType: "json",
    success: function (response) {
      var html = "";
      if (response.aaData && response.aaData.length > 0) {
        response.aaData.forEach(function (item) {
          // Suponemos que item es un array con los siguientes índices:
          // [0]: checkbox, [1]: posición (N°), [2]: tasa_nom, [3]: monto, [4]: estado_html, [5]: pago_button, [6]: is_multiplica
          var checkbox = item[0];
          var pos = item[1];
          var tasaNom = item[2];
          var monto = item[3];
          var estadoHtml = item[4];
          var pagoButton = item[5];
          var estado = item[7];
          var isMultiplica = item[6];  // Obtenemos el valor de is_multiplica

          html += '<div class="list-group-item">';
          html += '  <div class="row align-items-center">';
          html += '    <div class="col-auto">' + checkbox + '</div>';
          html += '    <div class="col">';
          html += '      <div><strong>Tasa:</strong> ' + tasaNom + '</div>';
          html += '      <div><strong>Importe:</strong> ' + monto + '</div>';
          html += '      <div><strong>Estado:</strong> ' + estadoHtml + '</div>';


          html += '    </div>';
          // Si is_multiplica es 1, agregamos el input de cantidad
          if (isMultiplica == "1" && estado == "1") {
            html += '      <div class="col-auto"><strong>Cantidad:</strong> ' +
              `<input type="number" class="form-control" name="cantidad_${item[8]}" value="1" min="1" />` +
              '</div>';
          }

          html += '    <div class="col-auto">' + pagoButton + '</div>';
          html += '  </div>';
          html += '</div>';
        });
      } else {
        html = '<div class="alert alert-warning">No se encontraron tasas.</div>';
      }

      $("#tasaListContainer").html(html);
      $("#IDpagarGrupo").attr("data-procedciudadano_id", proceciudadano_id);

      // Mostrar "Girar en Grupo" solo cuando exista mas de 1 tasa y calcular total inicial
      if (response.aaData && response.aaData.length > 1) {
        $("#IDpagarGrupo").show();
        calcularTotalTasas();
      } else {
        $("#IDpagarGrupo").hide();
      }
    },
    error: function (xhr, status, error) {
      console.error("Error al cargar tasas:", error);
      $("#tasaListContainer").html('<div class="alert alert-danger">Error al cargar tasas.</div>');
    }
  });
}


function combo_areas() {
  $.post("../../controller/procedimiento.php?op=getAreas_usu", function (data) {
    $("#area_id").html(data);
    $("#area_id").prop("selectedIndex", 1);
    combo_proced($("#area_id").val());
  });
}
function combo_tupa() {
  $.post("../../controller/tupa.php?op=gettupa_vigente", function (data) {
    // Parsear la respuesta JSON
    var response = JSON.parse(data);
    // Actualizar el ID del tupa
    $("#tupa_id").val(response.tupa_id);

    // Actualizar el nombre del tupa
    $("#tupa_nom").text(response.tupa_nom);
    $("#tusne_nom").text(response.tusne_nom);

  });
}
function combo_proced(area_id) {
  tupa_id = $("#tupa_id").val();
  $.ajax({
    url: "../../controller/procedimiento.php?op=combo_tupa_tusne",
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
$('#proced_id').on('change', function () {
  let proced_id = $(this).val();
  if (proced_id) {
    $.ajax({
      url: "../../controller/procedimiento.php?op=mostrar_detalle",
      type: "POST",
      data: { proced_id: proced_id },
      dataType: "json",
      success: function (data) {
        mostrarDetalleProcedimiento(data);
      },
      error: function (xhr, status, error) {
        console.error("Error al cargar detalle del procedimiento:", status, error);
      }
    });
  } else {
    $("#cardDetalleProcedimiento").html(""); // Limpiar si no hay selección
  }
});

function mostrarDetalleProcedimiento(data) {
  if (!data.procedimiento) {
    $("#cardDetalleProcedimiento").html("<div class='alert alert-warning'>No se encontraron datos.</div>");
    return;
  }
  let proc = data.procedimiento;
  let tasasHtml = "";

  if (data.tasas.length > 0) {
    tasasHtml = `
            <table class="table table-sm table-bordered mt-2">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tasa</th>
                        <th>Monto</th>
                        <th>Multiplica</th>
                        <th>Descripción</th>
                    </tr>
                </thead>
                <tbody>
        `;
    data.tasas.forEach((tasa, idx) => {
      let multiplicaBadge = tasa.is_multiplica == 1 
    ? '<span class="badge bg-blue text-blue-fg">Sí</span>' 
    : '<span class="badge bg-green text-green-fg">No</span>';
      tasasHtml += `
                <tr>
                    <td>${idx + 1}</td>
                    <td>${tasa.tasa_nom}</td>
                    <td>S/ ${parseFloat(tasa.tasaproced_monto).toFixed(2)}</td>
                    <td class="text-center">${multiplicaBadge}</td>
                    <td>${tasa.desc_tasa || "-"}</td>
                </tr>
            `;
    });
    tasasHtml += "</tbody></table>";
  } else {
    tasasHtml = "<div class='alert alert-info'>Este procedimiento no tiene tasas asociadas.</div>";
  }

 let cardHtml = `
    <div class="row g-3 mt-1">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-body">
                     <dl>
                        <dt>Código:</dt>
                        <dd>${proc.proced_cod}</dd>
                        <dt>Nombre:</dt>
                        <dd>${proc.proced_nom}</dd>
                       
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
              ${tasasHtml}
        </div>
    </div>
`;
  $("#cardDetalleProcedimiento").html(cardHtml);
}

function nuevo() {
  $("#procedciudadano_id").val('');
  $("#ciud_id").val('');
  $("#empr_id").val('');
  $("#tipo").val('');
  $("#tiv_id").val('');
  $("#menor_edad").val('');
  $("#esCarnet").val('');
  $("#esCPP").val('');
  $("#ruc_checkbox").val('');
  $("#tipo_admin").val('');

  if (typeof seleccionarTipo === 'function') {
    seleccionarTipo('DNI');
  } else {
    $("#name_select_tipo").text('DNI');
    $("#ciudadano_doc").attr('maxlength', '8').attr('inputmode', 'numeric').attr('placeholder', 'Ingresa el DNI (8 dígitos)');
  }

  $("#ciudadano_doc").removeAttr("readonly");
  $("#empr_ruc").removeAttr("readonly");

  $("#ruc_checkbox").prop("checked", false);
  $("#ruc_checkbox").change();

  $("#ciudadano_nombre, #ciudadano_apep, #ciudadano_apem").attr("readonly", "readonly");

  var procedId = $("#proced_id").val();

  if (!procedId) {
    $("#info_procedimiento").html(`<div class="alert alert-danger">Seleccionar procedimiento</div>`);
    return;
  }

  // Resetear campos

  $("#ciudadano_nombre, #ciudadano_apep, #ciudadano_apem, #ciudadano_doc, #empr_ruc, #empr_nombre_comercial, #empr_razon_social").val("");
	/*
  $("#empr_razon_social")
    .empty()
    .append('<option value="">Seleccione razón social</option>');
*/
  // $("#select_direccion")
  //   .empty()
  //   .append('<option value="">Seleccione dirección</option>')
  //   .prop("disabled", true);

  $("#select_direccion").html('');

  $("#empr_id").val("");

  $("#imagen_ciudadano").attr("src", "../../public/img/perfil.jpeg");

  // Ocultar alertas
  $("#info_procedimiento, #ciud_mensaje, #mensaje_empresa").html("").removeClass("alert-danger alert-warning alert-info").addClass("d-none");

  // Cargar datos del procedimiento
  $.ajax({
    url: "../../controller/procedimiento.php?op=get_proces_by_id",
    type: "POST",
    data: { proced_id: procedId },
    dataType: "json",
    success: function (response) {
      if (response.length > 0) {
        var proc = response[0];

        // Mostrar información del procedimiento
        $("#info_procedimiento").html(`
          <div class="alert alert-info">
            <strong>${proc.proced_nom}</strong><br>
            <small><b>Código:</b> ${proc.proced_cod} | <b>Dependencia:</b> ${proc.depe_denominacion}</small>
          </div>
        `).removeClass("d-none");

        $('#tipo_admin').val(proc.proced_administradotipo);

        // Controlar visibilidad y obligatoriedad según "proced_administradotipo"
        if (proc.proced_administradotipo === "C") {
          $(".ciud_container").show();
          $(".empr_container").hide();
          $(".empr_container input").prop("required", false);
          $("#label_ruc, #label_empr_razon_social, #label_empr_nombre_comercial").removeClass("required");
        } else if (proc.proced_administradotipo === "E") {
          $(".ciud_container, .empr_container").show();
          $(".empr_container input").prop("required", true);
          $("#label_ruc, #label_empr_razon_social, #label_empr_nombre_comercial").addClass("required");
        } else if (proc.proced_administradotipo === "D") {
          $(".ciud_container, .empr_container").show();
          $(".empr_container input").prop("required", false);
          $("#label_ruc, #label_empr_razon_social, #label_empr_nombre_comercial").removeClass("required");
        }

      } else {
        $("#info_procedimiento").html(`<div class="alert alert-warning">No se encontraron datos del procedimiento.</div>`).removeClass("d-none");
      }
    },
    error: function () {
      $("#info_procedimiento").html(`<div class="alert alert-danger">Error al obtener los datos.</div>`).removeClass("d-none");
    }
  });

  $("#modalmantenimiento").modal("show");
  setTimeout(function () {
    $("#ciudadano_doc").focus().select();
  }, 150);
}


function cambiarcomentario(ogciud_id) {

  swal
    .fire({
      title: "Actualizar Comentario",
      text: "Ingrese el nuevo comentario para este registro:",
      icon: "question",
      input: "text",
      inputPlaceholder: "Comentario",
      showCancelButton: true,
      confirmButtonText: "Actualizar",
      cancelButtonText: "Cancelar",
      cancelButtonColor: "#d33",
      confirmButtonColor: "#3085d6"
    })
    .then((result) => {
      if (result.isConfirmed) {
        var comentario = result.value;

        // Enviar solicitud AJAX para actualizar el comentario
        $.post(
          "../../controller/rc.php?op=cambiarComentario",
          { ogciud_id: ogciud_id, comentario: comentario },
          function (data) {
            if (data.error) {
              swal.fire({
                title: "Error al actualizar comentario",
                text: data.message,
                icon: "error",
                confirmButtonText: "Aceptar",
                confirmButtonColor: "#d33"
              });
            } else {
              swal.fire({
                title: "Comentario actualizado",
                text: "El comentario se ha actualizado correctamente.",
                icon: "success",
                confirmButtonText: "Aceptar",
                confirmButtonColor: "#5cb85c"
              });
              $("#data_ordenes").DataTable().ajax.reload();
            }
          }
        );
      }
    });
}
function listarGiros(procedciudadano_id) {
  $.post("../../controller/rc.php?op=listargiros", { procedciudadano_id: procedciudadano_id }, function (data) {
    let html = '';

    if (data.length === 0) {
      html = `
        <div class="alert alert-warning alert-dismissible" role="alert">
            <div class="alert-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" 
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" 
                     stroke-linecap="round" stroke-linejoin="round" class="icon alert-icon icon-2">
                    <path d="M12 9v4"></path>
                    <path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636-2.87l-8.106-13.536a1.914 1.914 0 0 0-3.274 0z"></path>
                    <path d="M12 16h.01"></path>
                </svg>
            </div>
            <div>
                <h4 class="alert-heading">¡No hay giros registrados!</h4>
                <div class="alert-description">Actualmente no hay giros registrados en esta orden.</div>
            </div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
      `;
    } else {
      data.forEach(giros => {
        html += `
          <div class="list-group-item">
            <div class="row align-items-center">
              <div class="col-auto">
                <i class="fa fa-dollar-sign fa-2x text-success"></i>
              </div>
              <div class="col">
                <div class="text-truncate">
                  <strong>Orden de Giro: ${giros.ogciud_id}</strong><br>
                  <small>N° Recibo: ${giros.recibo_nro}</small><br>
                  <small>Obvs: ${giros.ogciud_comentario}</small><br>
                  <div class="text-secondary small">
                    <i class="fa fa-calendar-alt"></i> ${giros.fecha}
                  </div>
                  <!-- Aquí agregamos el badge del estado -->
                  <span class="badge ${giros.badge}">${giros.estado}</span>
                </div>
              </div>
              <div class="col-auto">
                <button class="btn btn-ghost-warning" onclick="cambiarcomentario('${giros.ogciud_id}')" title="Editar">
                  <i class="fa fa-edit"></i> 
                </button>
                <button class="btn btn-ghost-danger" onclick="imprimirGiro('${giros.ogciud_id}')" title="Imprimir">
                  <i class="fa fa-print"></i> 
                </button>
              </div>
            </div>
          </div>
        `;
      });
    }
    $("#girosListContainer").html(html);
  }, "json");
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

      $("#procedciudadano_id").val(data.procedciudadano_id);

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
  var estado_procedimiento = $("#estado_procedimiento").val();
  getdataload();
  console.log(tasatciudadano_id);

  var estadosValidos = true;
  if (isNaN(estadotasa)) {
    estadotasa = 1;
  }
  if (estadotasa != 1 || estado_procedimiento == 0 || estado_procedimiento == 3) {
    estadosValidos = false;
  }

  if (!estadosValidos) {
    Swal.fire({
      title: "Error!",
      text: 'Solo puedes girar tasas con estado "Pendiente", verifica el estado.',
      icon: "error",
      confirmButtonText: "Aceptar"
    });
    return;
  }

  // Mostrar modal de pago
  var modalEl = document.getElementById('modal-payment');
  var paymentModal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
  $('#modal-payment').appendTo('body').modal('show');
  paymentModal.show();
  $('#comentarioInput').val('');

  // Listener único
  $('#btn-action').off('click').on('click', function (e) {
    e.preventDefault();
    var comentario = $('#comentarioInput').val();

    // Capturamos cantidad si existe un input tipo number en la misma fila (o usamos 1)
    var cantidad = $(`input[name="cantidad_${tasatciudadano_id}"]`).val() || 1;

    $('#btn-action').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Procesando...');
    $('#btn-cancel').addClass('disabled');

    $.post(
      "../../controller/tasa.php?op=Pagar_Orden_Giro",
      {
        tasatciudadano_id: tasatciudadano_id,
        cantidad: cantidad,               // ahora enviamos cantidad
        comentario: comentario
      },
      function (response) {
        paymentModal.hide();
        $('#btn-action').prop('disabled', false).html('Aceptar');
        $('#btn-cancel').removeClass('disabled');
        var data = JSON.parse(response);
        $("#IDpagarGrupo").prop("disabled", false).html("Pagar en Grupo");
        $('#loadingSpinner').hide();

        if (data.error) {
          Swal.fire({
            title: "Ups!",
            text: data.message,
            icon: "error",
            confirmButtonText: "Aceptar"
          }).then(() => {
            window.location.href = "../../index.php";
          });
        } else {
          // Éxito
          $("#detalle_data").DataTable().ajax.reload();
          $("#data_tasa").DataTable().ajax.reload();
          getdataload();
          listarTasas(procedciudadano_id);
          listarGiros(procedciudadano_id);

          Swal.fire({
            title: "¡Orden de Giro Exitosa!",
            text: "¿Desea imprimir el comprobante térmico ahora?",
            icon: "success",
            showCancelButton: true,
            confirmButtonText: '<i class="fa fa-print me-1"></i> Imprimir Ticket',
            cancelButtonText: '<i class="fa fa-times me-1"></i> Cerrar',
            confirmButtonColor: "#206bc4",
            cancelButtonColor: "#6c757d",
            focusConfirm: true
          }).then((result) => {
            if (result.isConfirmed) {
              imprimir(tasatciudadano_id);
            }
            $("#modaltasas").modal("hide");
            recargarTabla();
          });
        }
      }
    );
  });
}


function pagargrupo() {
  var tasatciud_id = [];
  var cantidades = []; // Nuevo arreglo para almacenar las cantidades
  var estadosValidos = true; // Variable para validar estados de las tasas seleccionadas
  var procedciudadano_id = $("#IDpagarGrupo").data("procedciudadano_id");

  // Recorremos los elementos de la lista (no las filas de la tabla)
  $(".list-group-item").each(function () {
    var checkbox = $(this).find("input[type='checkbox']");

    // Verificar si el checkbox está seleccionado
    if (checkbox.prop("checked") == true) {
      var id = checkbox.val();
      var cantidad = $(this).find("input[type='number']").val(); // Obtenemos la cantidad de la tasa seleccionada

      // Si la cantidad no está definida, usamos 1 por defecto
      cantidad = cantidad ? cantidad : 1;

      tasatciud_id.push(id);
      cantidades.push(cantidad); // Añadir la cantidad al arreglo

      // Obtener el estado de la tasa desde el atributo data-estado
      var estado = checkbox.data("estado");
      var estado_procedimiento = $("#estado_procedimiento").val();

      // Verificar si el estado de la tasa no es 'Pendiente' (estado = 1)
      if (estado != 1 || estado_procedimiento == 0 || estado_procedimiento == 3) {
        estadosValidos = false;
      }
    }
  });

  // Si no hay tasas seleccionadas
  if (tasatciud_id.length === 0) {
    Swal.fire({
      title: "Error!",
      text: "Por favor, selecciona las tasas que deseas pagar.",
      icon: "error",
      confirmButtonText: "Aceptar"
    });
  }
  // Si alguna tasa no es 'Pendiente' o el estado del procedimiento no es válido
  else if (!estadosValidos) {
    Swal.fire({
      title: "Error!",
      text: 'Solo puedes pagar tasas con estado "Pendiente", verifica el estado de la tasa o del procedimiento.',
      icon: "error",
      confirmButtonText: "Aceptar"
    });
  }
  else {
    // Deshabilitar el botón "Pagar en grupo" y mostrar el spinner
    $("#IDpagarGrupo")
      .prop("disabled", true)
      .html('<i class="fa fa-spinner fa-spin"></i> Procesando...');

    // Mostrar el modal de pago (se usa el mismo modal para ingresar comentario)
    var modalEl = document.getElementById('modal-payment');
    var paymentModal = new bootstrap.Modal(modalEl, {
      backdrop: 'static',
      keyboard: false
    });

    // Antes de mostrar el modal de pago, reubícalo al final del body
    $('#modal-payment').appendTo('body').modal('show');
    paymentModal.show();

    // Limpiar el input del comentario
    $('#comentarioInput').val('');

    // Quitar cualquier listener anterior para evitar múltiples bindings
    $('#btn-action').off('click').on('click', function (e) {
      e.preventDefault();
      var comentario = $('#comentarioInput').val();

      $('#btn-action').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Procesando...');
      $('#btn-cancel').addClass('disabled');

      // Enviar la solicitud al backend para procesar el pago en grupo
      $.post(
        "../../controller/tasa.php?op=Pagar_Orden_Giro_grupo",  // Asegúrate de que la ruta sea la correcta
        {
          tasatciud_id: tasatciud_id.join(','), // Unimos el array de IDs en una cadena separada por comas
          cantidades: cantidades.join(','),    // Unimos el array de cantidades en una cadena separada por comas
          comentario: comentario
        },
        function (response) {
          paymentModal.hide(); // Ocultar el modal tras recibir respuesta
          $('#btn-action').prop('disabled', false).html('Aceptar');
          $('#btn-cancel').removeClass('disabled');
          var data = JSON.parse(response);

          // Ocultar el spinner y reactivar el botón "Pagar en grupo"
          $("#IDpagarGrupo").prop("disabled", false).html("Pagar en Grupo");
          $('#loadingSpinner').hide(); // Esconder el spinner de carga

          if (data.error) {
            // Si el backend devuelve un error
            Swal.fire({
              title: "Ups!",
              text: data.error,
              icon: "error",
              confirmButtonText: "Aceptar"
            }).then(() => {
              // Si lo deseas, redirige a otra página en caso de error
              // window.location.href = "tu-pagina-de-error.php";
            });
          } else if (data.success) {
            // Recargar las tablas y datos
            $("#detalle_data").DataTable().ajax.reload();
            $("#data_tasa").DataTable().ajax.reload();
            getdataload();
            listarTasas(procedciudadano_id);
            listarGiros(procedciudadano_id);

            Swal.fire({
              title: "¡Orden de Giro Exitosa!",
              text: "¿Desea imprimir los comprobantes térmicos ahora?",
              icon: "success",
              showCancelButton: true,
              confirmButtonText: '<i class="fa fa-print me-1"></i> Imprimir Tickets',
              cancelButtonText: '<i class="fa fa-times me-1"></i> Cerrar',
              confirmButtonColor: "#206bc4",
              cancelButtonColor: "#6c757d",
              focusConfirm: true
            }).then((result) => {
              if (result.isConfirmed) {
                redirect_by_post(
                  "../../controller/rc.php?op=imprimir",
                  { tasatciudadano_id: tasatciud_id.join(",") },
                  true
                );
              }
              $("#modaltasas").modal("hide");
              recargarTabla();
            });
          }
        }).fail(function (xhr, status, error) {
          // Si la llamada AJAX falla
          $("#IDpagarGrupo").prop("disabled", false).html("Pagar en Grupo");
          $('#loadingSpinner').hide();

          Swal.fire({
            title: "Error!",
            text: "Hubo un problema con la conexión. Intenta nuevamente.",
            icon: "error",
            confirmButtonText: "Aceptar"
          });
        });
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
  getdataload();
  redirect_by_post(
    "../../controller/rc.php?op=imprimir",
    { tasatciudadano_id, tasatciudadano_id },
    true
  );
}
function imprimirGiro(ogciud_id) {
  getdataload();

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
  // Oculta el contenido y muestra el spinner y oculta alerta
  $("#modalContent").hide();
  $("#modalAlert").hide();
  $("#modalSpinner").show();

  // Establece el estado inicial de forma segura
  var estadoDom = $("#" + tamiteciudadano_id).data("estado");
  var estado = typeof estadoDom !== "undefined" ? parseInt(estadoDom) : 1;
  $("#estado_procedimiento").val(isNaN(estado) ? 1 : estado);

  // Cargar la tabla de tasas (según tu lógica)

  $("#procedciud_id").val(tamiteciudadano_id);

  // Petición AJAX para obtener los datos del procedimiento ciudadano
  $.ajax({
    url: '../../controller/procedimiento.php?op=get_data_proced_ciud',
    type: 'POST',
    data: { proceciudadano_id: tamiteciudadano_id },
    dataType: 'json',
    success: function (data) {
      if (data && data.length > 0) {
        var item = data[0];
        if (typeof item.est !== "undefined" && item.est !== null) {
          $("#estado_procedimiento").val(parseInt(item.est));
        }
        // Actualiza el header del modal
        var codigo = item.procedciudadano_cod;
        var nombre = item.proced_nom;
        $("#nombreproced_modal").html(codigo + " - " + nombre);

        // Actualiza la foto (se asume que item.ciud_foto viene en base64)
        if (item.ciud_foto) {
          $("#ciudadano_foto").attr("src", item.ciud_foto);
        } else {
          $("#ciudadano_foto").attr("src", "../../public/img/perfil.jpeg");
        }

        // Actualiza los demás datos
        $("#pc_cod").html(item.procedciudadano_cod);
        $("#pc_proced").html(item.proced_nom);
        $("#ciudadano_nombre_card").html(item.ciud_nombre + " " + item.ciud_primer_apellido + " " + item.ciud_segundo_apellido);
        $("#ciudadano_dni").html(item.ciud_numero_documento);

        // Si el RUC es null, no se muestran RUC ni razón social
        if (typeof item.empr_ruc === "string" && item.empr_ruc.trim() !== "") {
          $("#ruc").html(item.empr_ruc);
          $("#empresa").html(item.empr_razon_social);
          $("#fila_ruc").css("display", "table-row");  // Muestra la fila si hay RUC
          $("#fila_empresa").css("display", "table-row");  // Muestra la fila si hay Empresa
        } else {
          $("#fila_ruc").css("display", "none");  // Oculta la fila si no hay RUC
          $("#fila_empresa").css("display", "none");  // Oculta la fila si no hay Empresa
        }
        listarTasas(tamiteciudadano_id);
        listarGiros(tamiteciudadano_id);
        // Oculta el spinner y muestra el contenido
        $("#modalSpinner").hide();
        $("#modalContent").show();
      } else {
        $("#nombreproced_modal").html("Datos no encontrados");
        $("#modalSpinner").hide();
        $("#modalAlert").show();
      }
    },
    error: function (xhr, status, error) {
      console.error("Error al obtener datos: ", error);
      $("#nombreproced_modal").html("Error al cargar datos");
      $("#modalSpinner").hide();
      $("#modalAlert").show();
    }
  });

  // Muestra el modal y carga otros datos
  $("#modaltasas").modal("show");
  getdataload();

}



function editarComentario() {
  // Alternar entre el contenedor de Giros y el contenedor de Tasas
  var girosVisible = $("#girosListContainer").is(":visible");

  // Si la lista de Giros está visible, se oculta y se muestra la de Tasas
  if (girosVisible) {
    $("#girosListContainer").hide();
    $("#tasaListContainer").show();
    // Cambiar el texto, icono y color del botón para "Ver Giros"
    $("#btnEditarComentario").html('<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-cash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 15h-3a1 1 0 0 1 -1 -1v-8a1 1 0 0 1 1 -1h12a1 1 0 0 1 1 1v3" /><path d="M7 9m0 1a1 1 0 0 1 1 -1h12a1 1 0 0 1 1 1v8a1 1 0 0 1 -1 1h-12a1 1 0 0 1 -1 -1z" /><path d="M12 14a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /></svg> Ver Ordenes de Giros')
      .removeClass('btn-warning').addClass('btn-success');
  } else {
    // Si la lista de Tasas está visible, se oculta y se muestra la de Giros
    $("#tasaListContainer").hide();
    $("#girosListContainer").show();
    // Cambiar el texto, icono y color del botón para "Ver Tasas"
    $("#btnEditarComentario").html('<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-cash-register"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M21 15h-2.5c-.398 0 -.779 .158 -1.061 .439c-.281 .281 -.439 .663 -.439 1.061c0 .398 .158 .779 .439 1.061c.281 .281 .663 .439 1.061 .439h1c.398 0 .779 .158 1.061 .439c.281 .281 .439 .663 .439 1.061c0 .398 -.158 .779 -.439 1.061c-.281 .281 -.663 .439 -1.061 .439h-2.5" /><path d="M19 21v1m0 -8v1" /><path d="M13 21h-7c-.53 0 -1.039 -.211 -1.414 -.586c-.375 -.375 -.586 -.884 -.586 -1.414v-10c0 -.53 .211 -1.039 .586 -1.414c.375 -.375 .884 -.586 1.414 -.586h2m12 3.12v-1.12c0 -.53 -.211 -1.039 -.586 -1.414c-.375 -.375 -.884 -.586 -1.414 -.586h-2" /><path d="M16 10v-6c0 -.53 -.211 -1.039 -.586 -1.414c-.375 -.375 -.884 -.586 -1.414 -.586h-4c-.53 0 -1.039 .211 -1.414 .586c-.375 .375 -.586 .884 -.586 1.414v6m8 0h-8m8 0h1m-9 0h-1" /><path d="M8 14v.01" /><path d="M8 17v.01" /><path d="M12 13.99v.01" /><path d="M12 17v.01" /></svg> Ver Tasas')
      .removeClass('btn-success').addClass('btn-warning');
  }
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
function getdataload() {
  $.ajax({
    url: "../../controller/ordengiro.php?op=getgirosUsuarios_totales",
    type: "POST",
    dataType: "json",
    success: function (response) {
      if (response.error) {
        console.error(response.error);
        return;
      }

      // Calcular la diferencia entre hoy y ayer
      var diferencia = response.total_dia - response.total_ayer;
      var diffPercent = 0;
      if (response.total_ayer != 0) {
        diffPercent = (diferencia / response.total_ayer) * 100;
      } else {
        diffPercent = 0;
      }

      // Determinar el icono y color según la diferencia
      var iconHtml = "";
      var todayColor = "";
      if (diferencia > 0) {
        iconHtml = '<i class="fa fa-arrow-up"></i>';
        todayColor = "#2cb5a0"; // Verde: aumento
      } else if (diferencia < 0) {
        iconHtml = '<i class="fa fa-arrow-down"></i>';
        todayColor = "#ff9510"; // Naranja/rojo: disminución
      } else {
        iconHtml = '<i class="fa fa-minus"></i>';
        todayColor = "#0866c6"; // Neutro
      }

      var formattedPercent = Math.abs(diffPercent).toFixed(1) + '%';

      // Actualizar los contadores con las métricas
      $("#totalGeneral").text(response.total_general);
      $("#totalAyer").text(response.total_ayer);
      $("#totalDia").html(response.total_dia + ' <span style="color:' + todayColor + ';">' + iconHtml + ' ' + formattedPercent + '</span>');
    },
    error: function (xhr, status, error) {
      console.error(xhr.responseText);
    }
  });
}

init();
