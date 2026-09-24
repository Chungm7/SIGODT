
$(document).ready(function () {

  cargarDepartamentos();
  $('#co_tip_otr_ori').select2({
    dropdownParent: $('#otroOrigenModal .modal-body')
  });
  // Cargar provincias cuando se selecciona un departamento
  $('#otros_origenes_departamento').change(function () {
    let departamento = $(this).val();
    cargarProvincias(departamento);
    $('#otros_origenes_distrito').empty().append('<option value="">Selecciona un distrito</option>'); // Reset distritos
  });

  // Cargar distritos cuando se selecciona una provincia
  $('#otros_origenes_provincia').change(function () {
    let departamento = $('#otros_origenes_departamento').val();
    let provincia = $(this).val();
    cargarDistritos(departamento, provincia);
  });

  // Captura el botón de guardar
  $("#btnGuardarOtroOrigen").on("click", function (event) {
    event.preventDefault(); // Previene el comportamiento por defecto del botón

    // Obtén los valores del formulario
    var coOtrOri = $("#co_otr_ori").val().trim();
    var nuDocOtrOri = $("#nu_doc_otr_ori").val().trim();
    var coTipOtrOri = $("#co_tip_otr_ori").val().trim();
    var deApePatOtr = $("#de_ape_pat_otr").val().trim();
    var deApeMatOtr = $("#de_ape_mat_otr").val().trim();
    var deNomOtr = $("#de_nom_otr").val().trim();
    var deRazSocOtr = $("#de_raz_soc_otr").val().trim();
    var deDirOtroOri = $("#de_dir_otro_ori").val().trim();
    var ubDep = $("#otros_origenes_departamento").val().trim();
    var ubPro = $("#otros_origenes_provincia").val().trim();
    var ubDis = $("#otros_origenes_distrito").val().trim();
    var deEmail = $("#de_email").val().trim();
    var deTelefo = $("#de_telefo").val().trim();

    // Validación de Razón Social
    if (deRazSocOtr === '' || deRazSocOtr.length < 4) {
      Swal.fire({
        icon: 'warning',
        title: 'Advertencia',
        text: 'La Razón Social debe tener al menos 4 caracteres.',
        confirmButtonText: 'Aceptar'
      });
      return; // Detiene la ejecución del código si la validación falla
    }

    // Configura los datos del formulario
    var formData = {
      "co_otr_ori": coOtrOri,
      "nu_doc_otr_ori": nuDocOtrOri,
      "co_tip_otr_ori": coTipOtrOri,
      "de_ape_pat_otr": deApePatOtr,
      "de_ape_mat_otr": deApeMatOtr,
      "de_nom_otr": deNomOtr,
      "de_raz_soc_otr": deRazSocOtr,
      "de_dir_otro_ori": deDirOtroOri,
      "ub_dep": ubDep,
      "ub_pro": ubPro,
      "ub_dis": ubDis,
      "de_email": deEmail,
      "de_telefo": deTelefo
    };

    // Envia los datos al servidor usando AJAX
    $.ajax({
      url: '../../controller/otros_origenes.php?op=update_otros_origenes_data', // Reemplaza con la URL de tu endpoint
      type: 'POST',
      data: formData,
      success: function (response) {
        var data = JSON.parse(response);
        if (data.status === "success") {
          Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: data.message,
            confirmButtonText: 'Aceptar'
          }).then((result) => {
            if (result.isConfirmed) {
              $("#otroOrigenModal").modal("hide");
              search_int();
            }
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: data.message,
            confirmButtonText: 'Aceptar'
          });
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Ocurrió un error al procesar la solicitud.',
          confirmButtonText: 'Aceptar'
        });
        console.error('Error:', textStatus, errorThrown);
      }
    });
  });

  function search_int() {
    var searchValue = $("#search").val();

    // Hacer la llamada AJAX para buscar y actualizar la tabla
    $.ajax({
      url: '../../controller/otros_origenes.php?op=buscar',
      type: 'POST',
      data: { otros_origenes_input: searchValue },
      dataType: 'json',
      success: function (data) {
        console.log(data);
        var $tbody = $("#data-table-body");
        $tbody.empty(); // Limpiar el contenido de la tabla
        
        // Llenar la tabla con los datos recibidos
        $.each(data, function (index, item) {
          
          $tbody.append(
            `<tr>
              <td>${item.co_otr_ori}</td>
              <td>${item.nu_doc_otr_ori}</td>
              <td>${item.de_raz_soc_otr}</td>
              <td>${item.de_nom_otr}</td>
              <td>${item.de_ape_pat_otr}</td>
              <td>${item.de_ape_mat_otr}</td>
              <td>${item.de_dir_otro_ori}</td>
              <td>
                <button type="button" class="btn btn-warning btn-sm" onclick="ver('${item.co_otr_ori}')">
                  <i class="fas fa-edit"></i> Editar
                </button>
                <button type="button" class="btn btn-danger btn-sm" onclick="eliminar(${item.co_otr_ori})">
                  <i class="fas fa-trash-alt"></i> Eliminar
                </button>
              </td>
            </tr>`
          );
        });
      },
      error: function (xhr, status, error) {
        console.error("Error en la búsqueda:", error);
      }
    });
  }
});

function limpiarFormularioOtroOrigen() {
  // Limpiar los valores de los inputs y ejecutar el trigger 'change' en los selects
  $("#co_otr_ori").val('')
  $("#co_tip_otr_ori").val('').change();
  $("#nu_doc_otr_ori").val('');
  $("#de_ape_pat_otr").val('');
  $("#de_ape_mat_otr").val('');
  $("#de_nom_otr").val('');
  $("#de_raz_soc_otr").val('');
  $("#de_dir_otro_ori").val('');
  $("#otros_origenes_departamento").val('').change();
  $("#otros_origenes_provincia").val('').change();
  $("#otros_origenes_distrito").val('').change();
  $("#de_email").val('');
  $("#de_telefo").val('');
}

function nuevo() {
  $("#otroOrigenModal").modal("show");
  limpiarFormularioOtroOrigen();

}
function ver(id) {
/*   const formattedId = id.toString().padStart(10, '0'); */
  console.log(id);

  $("#otroOrigenModal").modal("show");
  // Hacer la llamada AJAX para obtener los detalles de la fila seleccionada
  $.ajax({
    url: '../../controller/otros_origenes.php?op=mostrar_co_otr_ori', // Reemplaza con la URL de tu endpoint
    type: 'POST',
    data: { co_otr_ori: id },
    dataType: 'json',
    success: function (data) {
      // Mostrar los datos en el formulario
      if (data.length > 0) {
        var item = data[0];

        $('#co_otr_ori').val(item.co_otr_ori);
        $('#co_tip_otr_ori').val(item.co_tip_otr_ori).trigger('change');
        $('#nu_doc_otr_ori').val(item.nu_doc_otr_ori);
        $('#de_ape_pat_otr').val(item.de_ape_pat_otr);
        $('#de_ape_mat_otr').val(item.de_ape_mat_otr);
        $('#de_nom_otr').val(item.de_nom_otr);
        $('#de_raz_soc_otr').val(item.de_raz_soc_otr);
        $('#de_dir_otro_ori').val(item.de_dir_otro_ori);

        let departamento = item.ub_dep || "";
        let provincia = item.ub_pro || "";
        let distrito = item.ub_dis || "";


        // Carga los datos de dirección en los selectores correspondientes
        if (departamento) {
          setSelectValue('#otros_origenes_departamento', departamento, 100); // Intento cada 100 ms
        }

        if (provincia) {
          setTimeout(function () { // Se asegura que la provincia se establezca después del departamento
            setSelectValue('#otros_origenes_provincia', provincia, 100); // Intento cada 100 ms
          }, 200); // Espera 200 ms antes de comenzar a intentar establecer la provincia
        }

        if (distrito) {
          setTimeout(function () { // Se asegura que el distrito se establezca después de la provincia
            setSelectValue('#otros_origenes_distrito', distrito, 100); // Intento cada 100 ms
          }, 300); // Espera 300 ms antes de comenzar a intentar establecer el distrito
        }
        $('#de_email').val(item.de_email);
        $('#de_telefo').val(item.de_telefo);
        $('#ref_co_otr_ori').val(item.ref_co_otr_ori);
      } else {
        // Limpiar el formulario si no se encuentran datos
        $('#co_tip_otr_ori').val('');
        $('#nu_doc_otr_ori').val('');
        $('#de_ape_pat_otr').val('');
        $('#de_ape_mat_otr').val('');
        $('#de_nom_otr').val('');
        $('#de_raz_soc_otr').val('');
        $('#de_dir_otro_ori').val('');
        $('#ub_dep').val('');
        $('#ub_pro').val('');
        $('#ub_dis').val('');
        $('#de_email').val('');
        $('#de_telefo').val('');
        $('#ref_co_otr_ori').val('');
      }
    },
    error: function (xhr, status, error) {
      console.error("Error al obtener los datos:", error);
      // Manejo de errores
    }
  });
}
function close() {
  $("#otroOrigenModal").modal("hide");
}
function cargarDepartamentos() {
  $.ajax({
    url: "../../controller/otros_origenes.php?op=get_direccion_data",
    method: "POST",
    dataType: "json",
    success: function (data) {
      let departamentos = {};
      data.forEach(function (item) {
        if (!departamentos[item.ubdep]) {
          departamentos[item.ubdep] = item.nodep;
        }
      });

      $('#otros_origenes_departamento').empty().append('<option value="">Selecciona un departamento</option>');
      $.each(departamentos, function (key, value) {
        $('#otros_origenes_departamento').append('<option value="' + key + '">' + value + '</option>');
      });
      $('#otros_origenes_departamento').select2({
        dropdownParent: $('#otroOrigenModal .modal-body')
      });
    }
  });
}

function limitarDigitos(input, cant) {
  let valor = input.value.toString().replace(/\D/g, ''); // Remover caracteres no numéricos

  const max_length = cant; // Por defecto, límite de 11 dígitos para RUC

  if (valor.length > max_length) {
    valor = valor.slice(0, max_length); // Truncar el valor si excede el límite
  }
  input.value = valor;
}



// Función para cargar las provincias basadas en el departamento seleccionado
function cargarProvincias(departamento) {
  $.ajax({
    url: "../../controller/otros_origenes.php?op=get_direccion_data",
    method: "POST",
    dataType: "json",
    success: function (data) {
      let provincias = {};
      data.forEach(function (item) {
        if (item.ubdep === departamento && !provincias[item.ubprv]) {
          provincias[item.ubprv] = item.noprv;
        }
      });

      $('#otros_origenes_provincia').empty().append('<option value="">Selecciona una provincia</option>');
      $('#otros_origenes_provincia').select2({
        dropdownParent: $('#otroOrigenModal .modal-body')
      });
      $.each(provincias, function (key, value) {
        $('#otros_origenes_provincia').append('<option value="' + key + '">' + value + '</option>');
      });
    }
  });
}

// Función para cargar los distritos basados en la provincia seleccionada
function cargarDistritos(departamento, provincia) {
  $.ajax({
    url: "../../controller/otros_origenes.php?op=get_direccion_data",
    method: "POST",
    dataType: "json",
    success: function (data) {
      let distritos = {};
      data.forEach(function (item) {
        if (item.ubdep === departamento && item.ubprv === provincia) {
          distritos[item.ubdis] = item.nodis;
        }
      });

      $('#otros_origenes_distrito').empty().append('<option value="">Selecciona un distrito</option>');
      $.each(distritos, function (key, value) {
        $('#otros_origenes_distrito').append('<option value="' + key + '">' + value + '</option>');
      });
      $('#otros_origenes_distrito').select2({
        dropdownParent: $('#otroOrigenModal .modal-body')
      });
    }
  });
}
function setSelectValue(selector, value, timeout) {
  var interval = setInterval(function () {
    var currentValue = $(selector).val();
    if (currentValue === value) {
      clearInterval(interval); // Detiene el bucle cuando el valor es el esperado
    } else {
      $(selector).val(value).trigger('change'); // Intenta establecer el valor
    }
  }, timeout); // Intervalo de tiempo entre cada intento
}


function eliminar(val) {

  Swal.fire({
    icon: 'warning',
    title: 'Acceso Denegado',
    text: 'No tiene el nivel necesario para realizar esta operación. Por favor, contactarse con la Gerencia de Tecnología de la Municipalidad Provincial de Chiclayo.',
    timer: 5000, // 5 segundos
    timerProgressBar: true,
    showConfirmButton: false,
    willClose: () => {
      // Opcional: alguna acción cuando el Swal se cierre
    }
  });

}
