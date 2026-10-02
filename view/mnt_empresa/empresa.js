// js/empresa.js

let tabla_empresa;

$(document).ready(function() {
    $('#empr_ruc').on('input', function() {
        // Tomamos solo los números del input
        var valor = $(this).val().replace(/\D/g, ''); // elimina cualquier caracter que no sea dígito

        if (valor.length === 11) {
            $.post("../../controller/empresa.php?op=consultar_sunat_v2",{ empr_ruc: $('#empr_ruc').val() }, function (data) {
                let response;
                try {
                  response = JSON.parse(data);
                  $("#empr_razon_social").val(response.raz_social);
                  $("#empr_nombre_comercial").val(response.nom_comercial);
                  $("#empr_direccion").val(response.domicilio);
                } catch (e) {
                  console.error("Respuesta inválida:", data);
                  return;
                }
            });
        }
    });
});


function listar_todos() {
  if ($.fn.DataTable.isDataTable("#tabla-empresa")) {
    $("#tabla-empresa").DataTable().destroy();
  }
  tabla_empresa = $("#tabla-empresa").DataTable({
    processing: true,
    serverSide: true,
    searching: false,
    ajax: {
      url: "../../controller/empresa.php?op=listar_tabla",
      type: "POST",
      data: function(d) {
        d.search       = $("#search").val().trim();
        d.estado       = $("#select-estado").val();
        d.order_column = d.order[0].column;
        d.order_dir    = d.order[0].dir;
      }
    },
    columns: [
      { data: 0 }, // ID
      { data: 1 }, // RUC
      { data: 2 }, // Razón Social
      { data: 3 }, // Nombre Comercial
      { data: 4 }, // Dirección
      { data: 5, orderable: false }, // Estado
      { data: 6, orderable: false }  // Acción
    ],
    order: [[0, "desc"]],
    pageLength: 10,
    columnDefs: [
      { className: "text-center align-middle", targets: "_all" }
    ]
  });
}

$(document).ready(function () {
  listar_todos();
  $("#search").on("input", function () {
    clearTimeout($.data(this, "timer"));
    let wait = setTimeout(listar_todos, 500);
    $(this).data("timer", wait);
  });
  $("#searchBtn").on("click", listar_todos);
  $("#filterBtn").on("click", function(e){ e.preventDefault(); listar_todos(); });
  $("#resetBtn").on("click", function(e){
    e.preventDefault();
    $("#search").val("");
    $("#select-estado").val("todos");
    listar_todos();
  });

  $(document).on("keydown", "#empresaForm input", function (e) {
    if (e.key === "Enter" || e.keyCode === 13) {
      e.preventDefault();
      const inputId = $(this).attr("id");

      if (inputId === "empr_ruc") {
        $("#empr_razon_social").focus().select();
        return;
      }

      if (inputId === "empr_razon_social") {
        $("#empr_nombre_comercial").focus().select();
        return;
      }

      if (inputId === "empr_nombre_comercial") {
        $("#empr_direccion").focus().select();
        return;
      }

      if (inputId === "empr_direccion") {
        return;
      }
    }
  });

  $(document).on("keydown", "#btnGuardarEmpresa", function (e) {
    if (e.key === "Enter" || e.keyCode === 13) {
      e.preventDefault();
      return false;
    }
  });
});

function nuevoRegistro(){
  $("#empr_id").val("");
  $("#empresaForm")[0].reset();
  $("#modal-title").text("Registrar Empresa");
  $("#empresaModal").modal("show");
  setTimeout(function () {
    $("#empr_ruc").focus();
  }, 400);
}

function editar(id){
  $.post(
    "../../controller/empresa.php?op=mostrar",
    { empr_id: id },
    function(data) {
      const d = JSON.parse(data);
      $("#empr_id").val(d.empr_id);
      $("#empr_categoria").val(d.empr_categoria);
      $("#empr_ruc").val(d.empr_ruc);
      $("#empr_razon_social").val(d.empr_razon_social);
      $("#empr_nombre_comercial").val(d.empr_nombre_comercial);
      $("#empr_direccion").val(d.empr_direccion);
      $("#modal-title").text("Editar Empresa");
      $("#empresaModal").modal("show");
    }
  );
}

function guardar(){
  const op = $("#empr_id").val() ? "editar" : "crear";
  const formArray = $("#empresaForm").serializeArray();
  const data = {};
  formArray.forEach(({ name, value }) => { data[name] = value; });

  $.post(`../../controller/empresa.php?op=${op}`, data)
    .done(res => {
      const r = JSON.parse(res);
      if (r.success) {
        $("#empresaModal").modal("hide");
        listar_todos();
        Swal.fire({
          title: 'Éxito',
          text: r.message,
          icon: 'success',
          confirmButtonText: 'Aceptar'
        });
      } else {
        Swal.fire({
          title: 'Error',
          text: r.message,
          icon: 'error',
          confirmButtonText: 'Aceptar'
        });
      }
    })
    .fail(() => {
      Swal.fire({
        title: 'Error',
        text: 'No se pudo conectar al servidor.',
        icon: 'error',
        confirmButtonText: 'Aceptar'
      });
    });
}

function cambiarEstado(id, estado){
  const nuevo = estado === "A" ? "I" : "A";
  const accion = nuevo === "A" ? "activar" : "inactivar";
  Swal.fire({
    title: 'Confirmar',
    text: `¿Deseas ${accion} esta empresa?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Aceptar',
    cancelButtonText: 'Cancelar'
  }).then(result => {
    if (result.isConfirmed) {
      $.post(
        "../../controller/empresa.php?op=cambiar_estado",
        { empr_id: id }
      )
      .done(() => {
        listar_todos();
        Swal.fire({
          title: 'Listo',
          text: `Empresa ${accion}da correctamente.`,
          icon: 'success',
          confirmButtonText: 'Aceptar'
        });
      })
      .fail(() => {
        Swal.fire({
          title: 'Error',
          text: 'No se pudo conectar al servidor.',
          icon: 'error',
          confirmButtonText: 'Aceptar'
        });
      });
    }
  });
}
