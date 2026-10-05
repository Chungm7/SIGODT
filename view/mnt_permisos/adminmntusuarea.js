$(document).ready(function () {
  $('.select2').select2({
    width: '100%'
  });

  combo_area();

  $('#depe_select').on('change', function () {
    var depe_id = $(this).val();
    if (depe_id) {
      listar_area(depe_id);
    } else {
      if ($.fn.DataTable.isDataTable('#detalle_data')) {
        $('#detalle_data').DataTable().clear().draw();
      }
    }
  });

  $(document).on('change', '#check_all', function () {
    var isChecked = $(this).is(':checked');
    $('#usu_data tbody input[name="detallecheck[]"]').prop('checked', isChecked);
  });
});

function combo_area() {
  $.post("../../controller/area.php?op=combo", function (data) {
    $('#depe_select').html(data);
  });
}

function listar_area(depe_id) {
  $('#detalle_data').DataTable({
    aProcessing: true,
    aServerSide: true,
    scrollX: false,
    dom: 'frtip',
    ajax: {
      url: "../../controller/usuario.php?op=listar_area_usu",
      type: "post",
      data: { depe_id: depe_id }
    },
    bDestroy: true,
    responsive: false,
    bInfo: true,
    iDisplayLength: 10,
    order: [[1, "asc"]],
    columnDefs: [
      { targets: 0, className: "text-center" },
      { targets: 4, className: "text-center", orderable: false }
    ],
    language: {
      sProcessing: "Procesando...",
      sLengthMenu: "Mostrar _MENU_ registros",
      sZeroRecords: "No se encontraron usuarios asignados a esta área",
      sEmptyTable: "Ningún usuario asignado a esta área",
      sInfo: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
      sInfoEmpty: "Mostrando 0 a 0 de 0 registros",
      sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
      sSearch: "Buscar:",
      oPaginate: {
        sFirst: "Primero",
        sLast: "Último",
        sNext: "Siguiente",
        sPrevious: "Anterior"
      }
    }
  });
}

function nuevo() {
  var depe_id = $('#depe_select').val();
  if (!depe_id) {
    Swal.fire({
      title: "Atención",
      text: "Debe seleccionar un área antes de asignar usuarios.",
      icon: "warning",
      confirmButtonText: "Aceptar"
    });
    return;
  }

  $('#check_all').prop('checked', false);
  listar_usu(depe_id);
  $('#modalmantenimiento').modal('show');
}

function listar_usu(depe_id) {
  $('#usu_data').DataTable({
    aProcessing: true,
    aServerSide: true,
    scrollX: false,
    dom: 'frtip',
    ajax: {
      url: "../../controller/usuario.php?op=listar_detalle_usu",
      type: "post",
      data: { depe_id: depe_id }
    },
    bDestroy: true,
    responsive: false,
    bInfo: true,
    iDisplayLength: 8,
    order: [[2, "asc"]],
    columnDefs: [
      { targets: 0, className: "text-center", orderable: false },
      { targets: 1, className: "text-center" },
      { targets: 3, className: "text-center" }
    ],
    language: {
      sProcessing: "Procesando...",
      sLengthMenu: "Mostrar _MENU_ registros",
      sZeroRecords: "No hay usuarios disponibles para asignar",
      sEmptyTable: "Todos los usuarios ya están asignados o no hay disponibles",
      sInfo: "Mostrando _START_ al _END_ de _TOTAL_ usuarios disponibles",
      sInfoEmpty: "Mostrando 0 a 0 de 0 usuarios",
      sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
      sSearch: "Buscar:",
      oPaginate: {
        sFirst: "Primero",
        sLast: "Último",
        sNext: "Siguiente",
        sPrevious: "Anterior"
      }
    }
  });
}

function registrardetalle() {
  var depe_id = $('#depe_select').val();
  if (!depe_id) {
    Swal.fire({
      title: "Error",
      text: "No se ha seleccionado un área válida.",
      icon: "error",
      confirmButtonText: "Aceptar"
    });
    return;
  }

  var table = $('#usu_data').DataTable();
  var pers_id = [];

  table.$('input[name="detallecheck[]"]:checked').each(function () {
    pers_id.push($(this).val());
  });

  if (pers_id.length === 0) {
    $('#usu_data tbody input[name="detallecheck[]"]:checked').each(function () {
      pers_id.push($(this).val());
    });
  }

  if (pers_id.length === 0) {
    Swal.fire({
      title: "Atención",
      text: "Seleccione al menos un usuario para asignar.",
      icon: "warning",
      confirmButtonText: "Aceptar"
    });
    return;
  }

  var formData = new FormData();
  formData.append('depe_id', depe_id);
  formData.append('pers_id', pers_id.join(','));

  var $btn = $('#btn_guardar_asignacion');
  $btn.prop('disabled', true);

  $.ajax({
    url: "../../controller/area.php?op=insert_area_usu",
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    success: function () {
      $btn.prop('disabled', false);
      $('#modalmantenimiento').modal('hide');
      $('#detalle_data').DataTable().ajax.reload(null, false);
      Swal.fire({
        title: "¡Correcto!",
        text: "Usuarios asignados exitosamente al área.",
        icon: "success",
        confirmButtonText: "Aceptar"
      });
    },
    error: function () {
      $btn.prop('disabled', false);
      Swal.fire({
        title: "Error",
        text: "Ocurrió un error al intentar asignar los usuarios.",
        icon: "error",
        confirmButtonText: "Aceptar"
      });
    }
  });
}

function eliminar(areausua_id) {
  Swal.fire({
    title: "¿Está seguro de quitar el usuario?",
    text: "El usuario ya no estará asignado a esta área.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d63939",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Sí, quitar",
    cancelButtonText: "Cancelar"
  }).then((result) => {
    if (result.isConfirmed) {
      $.post("../../controller/area.php?op=eliminar_area_usu", { areausua_id: areausua_id }, function () {
        $('#detalle_data').DataTable().ajax.reload(null, false);
        Swal.fire({
          title: "¡Eliminado!",
          text: "El usuario fue retirado correctamente del área.",
          icon: "success",
          confirmButtonText: "Aceptar"
        });
      });
    }
  });
}
