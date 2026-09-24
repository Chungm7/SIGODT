<?php
require_once "config/conexion.php";

if (isset($_POST["enviar"]) && $_POST["enviar"] == "si") {
  require_once "models/Usuario.php";

  $usuario = new Usuario();
  $usuario->login();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <meta http-equiv="X-UA-Compatible" content="ie=edge" />
  <title>SIAGTH</title>
  <link rel="stylesheet" href="public/tabler/css/tabler.min.css" />
  <link rel="stylesheet" href="public/tabler/css/tabler-flags.min.css" />
  <link rel="stylesheet" href="public/tabler/css/tabler-payments.min.css" />
  <link rel="stylesheet" href="public/tabler/css/tabler-vendors.min.css" />
  <link rel="icon" href="public/img/mpch.ico" type="image/x-icon">
  <style>
    .system-title {
      text-align: center;
      margin-bottom: 20px;
    }

    .title-main {
      font-size: 20px;
      /* Ajusta según tu preferencia */
      font-weight: bold;
      color: #32393f;
      /* Color moderno */
      letter-spacing: 1px;
      /* Espaciado entre letras */
      margin-bottom: 5px;
    }

    .title-sub {
      font-size: 24px;
      font-weight: 800;
      color: #dd1c04;
      letter-spacing: 1px;
      text-transform: uppercase;
      text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.1);
    }

    body {
      position: relative;
      margin: 0;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      background-color: #f8f9fa;
      /* Fondo base por si falla la imagen */
    }

    body::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-image: url('public/img/fondo_2.png');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      filter: brightness(50%);
      z-index: -1;
      /* Coloca el fondo detrás del contenido */
    }

    .card {
      position: relative;
      z-index: 1;
      /* Asegúrate de que el formulario esté sobre el fondo */
      background: white;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      border-radius: 10px;
    }
  </style>
</head>

<body class=" d-flex flex-column">
 
  <div class="page page-center">
    <div class="container container-tight py-4">
      <div class="row align-items-center g-4">
        <div class="col-lg-11">
          <div class="header-colors" style="width: 100%;height: 10px; display: flex">
            <div style="background: #ac4033;width: 25%; height: 100%;">

            </div>
            <div style="background: #d59b2d;width: 25%; height: 100%;">

            </div>
            <div style="background: #61a0a5;width: 25%; height: 100%;">

            </div>
            <div style="background: #0054a6;width: 25%; height: 100%;">

            </div>
          </div>
          <div class="card card-md" style="border-radius: 0 0 5px 5px;">
            <div class="card-body">
              <div class="text-center">
                <a class="navbar-brand navbar-brand-autodark">
                  <img src="public/img/logo_mpch.png" width="100" height="100">
                  <!-- <img src="public/img/sis_logo.png" width="140" height="100"> -->
                </a>
              </div>
              <h2 class="h3 text-center mb-3">MUNICIPALIDAD PROVINCIAL DE CHICLAYO</h2>
              <div class="mb-2 row">
                  <div class="col-3">
                    <img src="public/img/sis_logo.png" width="65" height="65">
                  </div>
                  <div class="col-9">
                    <p class="h3 text-justify"><small>Sistema de Gestion de Ordenes de Derecho de Tramite- Municipalidad de Chiclayo</small></p>
                  </div>
              </div>
              

              <!-- Mensajes de error -->
              <?php if (isset($_GET["m"])): ?>
                <div class="alert alert-danger" role="alert">
                  <?php
                  $mensajes = [
                    "1" => "Error",
                    "2" => "Los campos están vacíos",
                    "3" => "No se encontraron datos",
                    "4" => "Su IP no está habilitada para acceder al sistema",
                    "5" => "El usuario se encuentra inactivo",
                    "6" => "Se encuentra fuera de la hora de acceso",
                    "7" => "El usuario no está vigente",
                    "8" => "Los datos son incorrectos",
                    "9" => "La sesión ha expirado",
                    "10" => "Usuario sin dependencia en el SGD",
                    "11" => "Captcha Invalido"
                  ];
                  echo htmlspecialchars($mensajes[$_GET["m"]] ?? "Error desconocido", ENT_QUOTES, "UTF-8");
                  ?>
                </div>
              <?php endif; ?>

              <form action="" method="POST" autocomplete="off" novalidate>
                <div class="mb-3">
                  <label class="form-label">Usuario</label>
                  <input type="text" class="form-control" name="usu_dni" placeholder="Ingrese su Usuario" oninput="limitarADigitosDNI(this)" required />
                </div>

                <div class="mb-3">
                  <label class="form-label">
                    Contraseña
                    <span class="form-label-description">
                      <a href="https://www.munichiclayo.gob.pe/sisSeguridad/view/USURecuperacionContra/index.php?sistema=siagth">Olvidé mi contraseña</a>
                    </span>
                  </label>
                  <div class="input-group input-group-flat">
                    <input type="password" class="form-control" name="usu_pass" placeholder="Ingrese su Contraseña"
                      required />
                    <span class="input-group-text">
                      <a href="#" class="link-secondary toggle-password" title="Mostrar contraseña">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                          viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                          stroke-linecap="round" stroke-linejoin="round">
                          <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                          <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                          <path d="M21 12c-2.4 4-5.4 6-9 6c-3.6 0-6.6-2-9-6c2.4-4 5.4-6 9-6c3.6 0 6.6 2 9 6" />
                        </svg>
                      </a>
                    </span>
                  </div>
                </div>

                <div class="mb-3">
                  <!-- <label class="form-label">Captcha</label> -->

                  <div class="input-group input-group-flat" id="contcap" style="text-align: center; display: flex;justify-content: space-evenly;">
                    <div>
                      <div class="row">
                        <div class="col-11"
                          style="display: flex; scale: 0.8; justify-content: space-around;border-bottom: 2px solid #0054a6;">
                          <img id="captchaImage" style="min-height: 40px; margin:0px 25px 0px 25px" src="" alt="CAPTCHA">
                        </div>

                        <div class="col-1" style="display: flex; scale: 1; justify-content: space-around;">
                          <button type="button" id="btcap" class="btn btn-primary btn-refresh"
                            onclick="refreshCaptcha()"
                            style="width:50px;background: none; color: #0054a6; border: 2px solid #0054a6; cursor: pointer; padding: 0; margin: 0; display: flex; align-items: center; justify-content: center;">
                            <svg style="margin-left: 5px;" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-reload" style="display: block;">
                              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                              <path d="M19.933 13.041a8 8 0 1 1 -9.925 -8.788c3.899 -1 7.935 1.007 9.425 4.747" />
                              <path d="M20 4v5h-5" />
                            </svg>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>

                  <input type="text" id="captchaInput" name="captchaInput" class="form-control"
                        placeholder="Ingrese el captcha" oninput="validateCaptchaInput()" required />

                  <!-- <div class="hr-text">otros</div>

                  <div class="row">
                      <div class="col"><a href="#" class="btn w-100">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon text-github"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M9 19c-4.3 1.4 -4.3 -2.5 -6 -3m12 5v-3.5c0 -1 .1 -1.4 -.5 -2c2.8 -.3 5.5 -1.4 5.5 -6a4.6 4.6 0 0 0 -1.3 -3.2a4.2 4.2 0 0 0 -.1 -3.2s-1.1 -.3 -3.5 1.3a12.3 12.3 0 0 0 -6.2 0c-2.4 -1.6 -3.5 -1.3 -3.5 -1.3a4.2 4.2 0 0 0 -.1 3.2a4.6 4.6 0 0 0 -1.3 3.2c0 4.6 2.7 5.7 5.5 6c-.6 .6 -.6 1.2 -.5 2v3.5"></path></svg>
                          Entrar con Zimbra
                        </a>
                      </div>
                  </div> -->
                </div>

                <input type="hidden" name="enviar" value="si">
                <div class="form-footer">
                  <input type="hidden" name="enviar" value="si">
                  <button type="submit" class="btn btn-primary w-100"><span class="me-2" role="status"></span>Ingresar</button>
                </div>
                <!-- spinner-border spinner-border-sm
                <div class="progress progress-sm">
                  <div class="progress-bar progress-bar-indeterminate"></div>
                </div> -->
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Libs JS -->
  <!-- Tabler Core -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="public/tabler/js/tabler.min.js?1692870487" defer></script>
  <script src="public/tabler/js/demo.min.js?1692870487" defer></script>

  <script>
    $('#captchaInput').on('input', function () {
        this.value = this.value.toUpperCase();
    });
  </script>

  <script>
      function limitarADigitosRUC(input) {
        let valor = input.value.toString().replace(/\D/g, '');
        if (valor.length > 11) {
          valor = valor.slice(0, 11);
        }
        input.value = valor;
      }
      function limitarADigitosDNI(input) {
        let valor = input.value.toString().replace(/\D/g, '');
        if (valor.length > 8) {
          valor = valor.slice(0, 8);
        }
        input.value = valor;
      }
  </script>

  <script ript type="text/javascript">

    document.addEventListener('DOMContentLoaded', function () {
      refreshCaptcha();
    });

    function refreshCaptcha() {
      fetch('captcha.php?action=generateCaptcha')
        .then(response => response.json())
        .then(data => {
          $('#captchaImage').attr('src', data.captcha);
        })
        .catch(error => console.error('Error al generar CAPTCHA:', error));
    }

    function validateCaptchaInput() {
      var captchaInput = $('#captchaInput');
      var captchaContainer = $('#contcap');
      var refreshIcon = $('#btcap');

      if (captchaInput.val() === "") {
        captchaInput.addClass('is-invalid').removeClass('is-warning');
        captchaContainer.css('borderColor', 'red');
        refreshIcon.css('color', 'red');
      } else {
        captchaInput.addClass('is-warning').removeClass('is-invalid');
        captchaContainer.css('borderColor', '#007bff');
        refreshIcon.css('color', '#007bff');
      }
    }
  </script>
  <script>
    function limitarADigitosDNI(input) {
      let valor = input.value.replace(/\D/g, '');
      if (valor.length > 8) {
        valor = valor.slice(0, 8);
      }
      input.value = valor;
    }
    document.querySelectorAll('.toggle-password').forEach(el => {
      el.addEventListener('click', e => {
        e.preventDefault();
        const input = el.closest('.input-group').querySelector('input');
        input.type = input.type === 'password' ? 'text' : 'password';
      });
    });
  </script>
</body>

</html>