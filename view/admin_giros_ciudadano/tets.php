
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Prueba de SweetAlert2</title>
  <!-- Cargar la librería SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <button onclick="cambiarcomentario(1)">Actualizar Comentario</button>

  <script>
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
          console.log('Comentario ingresado:', comentario);
        }
      });
    }
  </script>
</body>
</html>
    