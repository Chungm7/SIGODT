<?php
/* Llamamos al archivo de conexion.php */
require_once("../../config/conexion.php");
if (isset($_SESSION["usua_id_colas"])) {
  $nombre_completo = isset($_SESSION["nombre_completo_colas"]) ? $_SESSION["nombre_completo_colas"] : "Usuario";
  $perfil = isset($_SESSION["rol_nombre_colas"]) ? $_SESSION["rol_nombre_colas"] : "Sin perfil";
  ?>
  <!DOCTYPE html>
  <html lang="es">

  <head>
    <?php require_once("../html/mainHead.php"); ?>
    <title>RRHH::Configuración</title>
  </head>

  <body>
  <script src="../../public/tabler/js/demo-theme.min.js?1692870487"></script>
    <script src="./dist/js/demo-theme.min.js?1692870487"></script>
    <div class="page">
      <?php require_once("../html/mainProfile.php"); ?>
      <?php require_once("../html/menu.php"); ?>
      <div class="page-wrapper">
        <!-- Page header -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <h2 class="page-title">
                  Ajustes de Cuenta
                </h2>
              </div>
            </div>
          </div>
        </div>
        <!-- Page body -->
        <div class="page-body">
          <div class="container-xl">
            <div class="card">
              <div class="row g-0">
                <div class="col-12 col-md-3 border-end">
                  <div class="card-body">
                    <h4 class="subheader">Business settings</h4>
                    <div class="list-group list-group-transparent">
                      <a href="../setting/"
                        class="list-group-item list-group-item-action d-flex align-items-center active">Mi Perfil</a>

                    </div>

                  </div>
                </div>
                <div class="col-12 col-md-9 d-flex flex-column">
                  <div class="card-body">
                    <h2 class="mb-4">Mi Cuenta</h2>
                    <h3 class="card-title mt-4">Perfil Personal</h3>
                    <div class="row g-3">
                      <div class="col-md">
                        <div class="form-label">Nombre</div>
                        <input type="text" class="form-control" value="ADMIN">
                      </div>
                      <div class="col-md">
                        <div class="form-label">Rol</div>
                        <input type="text" class="form-control" value="Digitador">
                      </div>
                      <div class="col-md">
                        <div class="form-label">Sistema</div>
                        <input type="text" class="form-control" value="RRHH">
                      </div>
                    </div>
                    <h3 class="card-title mt-4">Correo Electronico</h3>
                    <p class="card-subtitle">Este Correo sera usado para la Recuperación de contraseña</p>
                    <div>
                      <div class="row g-2">
                        <div class="col-auto">
                          <input type="text" class="form-control w-auto" value="muni@gmail.com">
                        </div>
                        <div class="col-auto"><a href="#" class="btn">
                            Cambiar
                          </a></div>
                      </div>
                    </div>
                    <h3 class="card-title mt-4">Contraseña</h3>
                    <p class="card-subtitle">Puedes cambiar la contraseña de manera permanente, pero no te olvides de
                      cambiarla con frecuencia.</p>
                    <div>
                      <a href="#" class="btn">
                        Cambiar Contraseña
                      </a>
                    </div>

                  </div>
                  <div class="card-footer bg-transparent mt-auto">
                    <div class="btn-list justify-content-end">
                      <a href="#" class="btn">
                        Cancel
                      </a>
                      <a href="#" class="btn btn-primary">
                        Submit
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <footer class="footer footer-transparent d-print-none">
          <div class="container-xl">
            <div class="row text-center align-items-center flex-row-reverse">
              <div class="col-lg-auto ms-lg-auto">
                <ul class="list-inline list-inline-dots mb-0">
                  <li class="list-inline-item"><a href="https://tabler.io/docs" target="_blank" class="link-secondary"
                      rel="noopener">Documentation</a></li>
                  <li class="list-inline-item"><a href="./license.html" class="link-secondary">License</a></li>
                  <li class="list-inline-item"><a href="https://github.com/tabler/tabler" target="_blank"
                      class="link-secondary" rel="noopener">Source code</a></li>
                  <li class="list-inline-item">
                    <a href="https://github.com/sponsors/codecalm" target="_blank" class="link-secondary" rel="noopener">
                      <!-- Download SVG icon from http://tabler-icons.io/i/heart -->
                      <svg xmlns="http://www.w3.org/2000/svg" class="icon text-pink icon-filled icon-inline" width="24"
                        height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M19.5 12.572l-7.5 7.428l-7.5 -7.428a5 5 0 1 1 7.5 -6.566a5 5 0 1 1 7.5 6.572" />
                      </svg>
                      Sponsor
                    </a>
                  </li>
                </ul>
              </div>
              <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                <ul class="list-inline list-inline-dots mb-0">
                  <li class="list-inline-item">
                    Copyright &copy; 2023
                    <a href="." class="link-secondary">Tabler</a>.
                    All rights reserved.
                  </li>
                  <li class="list-inline-item">
                    <a href="./changelog.html" class="link-secondary" rel="noopener">
                      v1.0.0-beta20
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </footer>
      </div>
    </div>
    <?php require_once("../html/mainjs.php"); ?>
  </body>

  </html>
  <?php
} else {
  /* Si no ha iniciado sesión se redirecciona a la ventana principal */
  header("Location:" . Conectar::ruta() . "view/404/");
}
?>