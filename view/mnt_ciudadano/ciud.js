// js/ciudadano.js

let tabla_ciudadano;
// js/ciudadano.js (añade al inicio o justo después de cargar jQuery/DataTables)

function cargarTiposDocumento() {
    $.ajax({
        url: "../../controller/ciudadano.php?op=listar_tipos",
        method: "GET",
        dataType: "json",
        success: function (res) {
            if (res.success) {
                const $select = $("#tido_id");
                $select.empty().append('<option value="">Seleccione...</option>');
                res.data.forEach(function (td) {
                    $select.append(
                        $("<option>")
                            .val(td.tido_id)
                            .text(td.tido_descripcion)
                    );
                });
            } else {
                console.error("Error al cargar tipos de documento");
            }
        },
        error: function () {
            console.error("No se pudo conectar al servidor para listar tipos");
        }
    });
}




function listar_todos() {
    if ($.fn.DataTable.isDataTable("#tabla-ciudadano")) {
        $("#tabla-ciudadano").DataTable().destroy();
    }
    tabla_ciudadano = $("#tabla-ciudadano").DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        ajax: {
            url: "../../controller/ciudadano.php?op=listar_tabla",
            type: "POST",
            data: function (d) {
                d.search = $("#search").val().trim();
                d.estado = $("#select-estado").val();
                d.order_column = d.order[0].column;
                d.order_dir = d.order[0].dir;
            }
        },
        columns: [
            { data: 0 },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5 },
            { data: 6, orderable: false },
            { data: 7, orderable: false }
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
    cargarTiposDocumento();
    $("#search").on("input", function () {
        clearTimeout($.data(this, "timer"));
        let wait = setTimeout(listar_todos, 500);
        $(this).data("timer", wait);
    });
    $("#searchBtn").on("click", listar_todos);
    $("#filterBtn").on("click", function (e) { e.preventDefault(); listar_todos(); });
    $("#resetBtn").on("click", function (e) {
        e.preventDefault();
        $("#search").val("");
        $("#select-estado").val("todos");
        listar_todos();
    });
    $(document).on('change', '#ciud_foto_file', function (e) {
        const input = this;
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (ev) {
                $('#previewFoto')
                    .attr('src', ev.target.result)
                    .show();
            };
            reader.readAsDataURL(input.files[0]);
        }
    });
});
// lo fuerzas al scope window

function nuevoRegistro() {
    $("#ciud_id").val("");
    $("#registerForm")[0].reset();
    $("#modal-title").text("Registrar Ciudadano");
    $("#registerModal").modal("show");
}

function editar(id) {
    $.post(
      "../../controller/ciudadano.php?op=mostrar",
      { ciud_id: id },
      function (data) {
        const d = JSON.parse(data);
        // Setear campos
        $("#ciud_id").val(d.ciud_id);
        $("#tido_id").val(d.tido_id);
        $("#ciud_numero_documento").val(d.ciud_numero_documento);
        $("#ciud_primer_apellido").val(d.ciud_primer_apellido);
        $("#ciud_segundo_apellido").val(d.ciud_segundo_apellido);
        $("#ciud_nombre").val(d.ciud_nombre);
        $("#ciud_fecha_nac").val(d.ciud_fecha_nac);
        $("#ciud_sexo").val(d.ciud_sexo);
  
        // Limpiar input file
        $("#ciud_foto_file").val("");
  
        // Previsualizar imagen si existe Base64
        if (d.ciud_foto) {
          $("#previewFoto")
            .attr("src", d.ciud_foto)
            .show();
        } else {
          $("#previewFoto").hide();
        }
  
        // Ajustar título y mostrar modal
        $("#modal-title").text("Editar Ciudadano");
        $("#registerModal").modal("show");
      }
    );
  }
  
function guardar() {
    const op = $("#ciud_id").val() ? "editar" : "crear";
    const formArray = $("#registerForm").serializeArray();
    const data = {};
    formArray.forEach(({ name, value }) => { data[name] = value; });
  
    function enviar() {
      $.post(`../../controller/ciudadano.php?op=${op}`, data)
        .done(res => {
          const r = JSON.parse(res);
          if (r.success) {
            $("#registerModal").modal("hide");
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
  
    const fileInput = document.getElementById("ciud_foto_file");
    const file = fileInput.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = e => {
        data.ciud_foto = e.target.result;
        enviar();
      };
      reader.readAsDataURL(file);
    } else {
      data.ciud_foto = "";
      enviar();
    }
  }
  
  
  function cambiarEstado(id, estado) {
    const nuevo = estado === "A" ? "I" : "A";
    const accion = nuevo === "A" ? "activar" : "inactivar";
  
    Swal.fire({
      title: 'Confirmar',
      text: `¿Deseas ${accion} este ciudadano?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Aceptar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(
          "../../controller/ciudadano.php?op=cambiar_estado",
          { ciud_id: id }
        )
        .done(() => {
          listar_todos();
          Swal.fire({
            title: 'Listo',
            text: `Ciudadano ${accion}do correctamente.`,
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
  