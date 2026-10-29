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
  <title>SIGODT :: Acceso Institucional</title>
  <link rel="stylesheet" href="public/tabler/css/tabler.min.css" />
  <link rel="stylesheet" href="public/tabler/css/tabler-flags.min.css" />
  <link rel="stylesheet" href="public/tabler/css/tabler-payments.min.css" />
  <link rel="stylesheet" href="public/tabler/css/tabler-vendors.min.css" />
  <link rel="stylesheet" href="public/lib/inter/inter.css" />
  <link rel="stylesheet" href="public/css/municipal-theme.css" />
  <link rel="icon" href="public/img/mpch.ico" type="image/x-icon">
</head>

<body class="muni-login-body">

  <main class="muni-login-split">
    <!-- 01 Identidad visual / Chiclayo -->
    <section class="muni-login-hero" aria-label="Identidad institucional">
      <div class="muni-login-hero-content">
        <h1 class="muni-login-hero-title">Sistema de Gestión de Órdenes de Derecho de Trámite</h1>
        <p class="muni-login-hero-desc">
          Plataforma institucional para la emisión, seguimiento, consulta y control de órdenes de derecho de trámite de la gestión municipal.
        </p>
        <div class="muni-login-hero-bar" aria-hidden="true"></div>
      </div>
    </section>

    <!-- 02 Acceso institucional -->
    <section class="muni-login-panel" aria-label="Formulario de acceso institucional">
      <div class="muni-login-form-wrap">
        <div class="muni-login-crest">
          <img src="public/img/logo_mpch.png" alt="Escudo de la Municipalidad Provincial de Chiclayo" height="88">
          <p class="muni-login-entity">MUNICIPALIDAD PROVINCIAL DE CHICLAYO</p>
        </div>

        <h2 class="muni-login-heading">Iniciar sesión</h2>
        <p class="muni-login-subheading">Ingresa tus credenciales institucionales para continuar.</p>

        <!-- Mensajes de error -->
        <?php if (isset($_SESSION["login_error"])): ?>
          <div class="alert alert-danger" role="alert">
            <?php
            echo htmlspecialchars($_SESSION["login_error"], ENT_QUOTES, "UTF-8");
            unset($_SESSION["login_error"]);
            ?>
          </div>
        <?php elseif (isset($_GET["m"])): ?>
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
              "11" => "CAPTCHA inválido o expirado. Ingrese el nuevo código.",
              "12" => "No se pudo verificar la seguridad del acceso. Intente nuevamente."
            ];
            echo htmlspecialchars($mensajes[$_GET["m"]] ?? "Error desconocido", ENT_QUOTES, "UTF-8");
            ?>
          </div>
        <?php endif; ?>

        <form id="loginForm" action="" method="POST" autocomplete="off">
          <div class="mb-3">
            <label for="usu_dni" class="muni-login-label">Usuario</label>
            <div class="input-group muni-input-group">
              <input
                type="text"
                id="usu_dni"
                class="form-control"
                name="usu_dni"
                placeholder="Ingrese su DNI"
                inputmode="numeric"
                maxlength="8"
                oninput="limitarADigitosDNI(this)"
                required />
              <span class="muni-input-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                  <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                  <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                </svg>
              </span>
            </div>
          </div>

          <div class="mb-3">
            <div class="muni-login-label-row">
              <label for="usu_pass" class="muni-login-label mb-0">Contraseña</label>
              <a class="muni-login-forgot" href="https://www.munichiclayo.gob.pe/sisSeguridad/view/USURecuperacionContra/index.php?sistema=siagth">¿Olvidaste tu contraseña?</a>
            </div>
            <div class="input-group muni-input-group">
              <input
                type="password"
                id="usu_pass"
                class="form-control"
                name="usu_pass"
                placeholder="Ingrese su Contraseña"
                required />
              <a href="#" class="muni-input-action toggle-password" title="Mostrar contraseña" aria-label="Mostrar u ocultar contraseña">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20"
                  viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                  stroke-linecap="round" stroke-linejoin="round">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                  <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                  <path d="M21 12c-2.4 4-5.4 6-9 6c-3.6 0-6.6-2-9-6c2.4-4 5.4-6 9-6c3.6 0 6.6 2 9 6" />
                </svg>
              </a>
            </div>
          </div>

          <div class="mb-4" id="contcap">
            <div class="muni-captcha-row">
              <div class="muni-captcha-box">
                <img id="captchaImage" src="" alt="CAPTCHA">
              </div>
              <button
                type="button"
                id="btcap"
                class="btn muni-captcha-refresh btn-refresh"
                aria-label="Actualizar CAPTCHA"
                title="Actualizar CAPTCHA">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-reload m-0">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                  <path d="M19.933 13.041a8 8 0 1 1 -9.925 -8.788c3.899 -1 7.935 1.007 9.425 4.747" />
                  <path d="M20 4v5h-5" />
                </svg>
              </button>
            </div>

            <input
              type="text"
              id="captchaInput"
              name="captchaInput"
              class="form-control muni-captcha-input"
              placeholder="Ingrese el captcha"
              aria-label="Código CAPTCHA"
              aria-describedby="captchaStatus"
              minlength="6"
              maxlength="6"
              pattern="[A-Za-z2-9]{6}"
              required
              disabled />
            <div id="captchaStatus" class="muni-captcha-status" role="status" aria-live="polite">Cargando CAPTCHA…</div>
            <noscript>Active JavaScript para cargar el CAPTCHA e ingresar.</noscript>
          </div>

          <input type="hidden" name="enviar" value="si">
          <div class="form-footer mt-2">
            <button id="loginSubmit" type="submit" class="btn muni-login-submit" disabled>
              <span class="me-1" role="status"></span>
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                <path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2" />
                <path d="M20 12h-13l3 -3m0 6l-3 -3" />
              </svg>
              <span>Ingresar</span>
            </button>
          </div>
        </form>
      </div>
    </section>
  </main>

  <!-- Libs JS -->
  <script src="public/lib/jquery/jquery.min.js"></script>
  <script src="public/tabler/js/tabler.min.js?1692870487" defer></script>
  <script src="public/tabler/js/demo.min.js?1692870487" defer></script>
  <script src="public/js/login-captcha.js" defer></script>

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
