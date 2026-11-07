<?php
require_once('../../config/conexion.php');
if (!isset($_SESSION['usua_id_SIGODT'])) {
    header('Location:' . Conectar::ruta() . 'view/404/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <?php require_once('../html/mainHead.php'); ?>
</head>
<body>
  <script src="../../public/tabler/js/demo-theme.min.js"></script>
  <div class="page"><div class="wrapper">
    <?php require_once('../html/mainProfile.php'); ?>
    <?php require_once('../html/menu.php'); ?>
    <div class="page-wrapper">
      <div class="page-body"><div class="container-xl">
        <!-- Breadcrumb Contextual -->
        <div class="mpch-subsys-breadcrumb">
          <a href="../inicio/" class="mpch-bc-sys text-decoration-none" title="Ir al Inicio">SIGODT</a>
          <span class="mpch-bc-sep">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l6 6l-6 6" /></svg>
          </span>
          <span class="mpch-bc-sec">Consultas</span>
          <span class="mpch-bc-sep">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l6 6l-6 6" /></svg>
          </span>
          <span class="mpch-bc-cur">Consulta por Nombre / Razón Social</span>
        </div>

        <!-- Encabezado de la Página (Título y Descripción) -->
        <div class="page-header page-header-context d-print-none mb-3">
          <div class="row align-items-center">
            <div class="col">
              <h2 class="page-title text-uppercase">Consulta por Nombre / Razón Social</h2>
              <div class="text-secondary mt-1" style="font-size: 14px;">Busque ciudadanos o empresas y consulte su historial completo, sin excluir estados ni fechas.</div>
            </div>
          </div>
        </div>
        <div class="card shadow-sm muni-stripe-top mb-3">
          <div class="card-header py-2"><h3 class="card-title fw-bold mb-0">Criterio de Búsqueda</h3></div>
          <div class="card-body">
            <form id="nombreForm" class="row g-2 align-items-end">
              <div class="col-12 col-md-8">
                <label for="nombreSearch" class="form-label fw-medium">Nombre del ciudadano o razón social / nombre comercial</label>
                <div class="input-group">
                  <span class="input-group-text">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                      <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                      <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                      <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                    </svg>
                  </span>
                  <input id="nombreSearch" type="text" class="form-control" required autofocus maxlength="200" placeholder="Ingrese parte del nombre" aria-describedby="nombreHelp">
                </div>
              </div>
              <div class="col-12 col-md-auto">
                <button type="submit" class="btn btn-muni-action w-100">
                  <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-search me-1" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                    <path d="M21 21l-6 -6" />
                  </svg>
                  BUSCAR
                </button>
              </div>
              <div class="col-12"><div id="nombreHelp" class="form-hint">Búsqueda parcial automática tras 400 ms, desde 3 caracteres. Buscar o Enter consulta de inmediato. Seleccione «Ver historial» para ver sus órdenes.</div></div>
            </form>
          </div>
        </div>
        <div id="nombreMessage" class="alert alert-info mb-3" role="status" aria-live="polite">Ingrese un nombre para buscar.</div>
        <section id="nombreResults" class="card shadow-sm mb-3" aria-labelledby="resultsTitle">
          <div class="card-header py-2"><h3 id="resultsTitle" class="card-title fw-bold mb-0">Entidades y Total de Órdenes</h3></div>
          <div class="table-responsive">
            <table class="table table-vcenter card-table table-striped table-hover dataTable" id="tablaEntities">
              <thead><tr><th class="sorting" data-sort="tipo">Tipo</th><th class="sorting_asc" data-sort="nombre">Nombre / Razón social</th><th class="sorting" data-sort="documento">Documento / RUC</th><th class="sorting" data-sort="ordenes">Total de órdenes</th><th class="sorting_disabled">Acciones</th></tr></thead>
              <tbody id="nombreEntities"></tbody>
            </table>
          </div>
          <div class="card-footer d-flex align-items-center justify-content-between gap-2">
            <button id="entitiesPrev" type="button" class="btn btn-outline-secondary" disabled>Anterior</button>
            <span id="entitiesPage" class="text-secondary"></span>
            <button id="entitiesNext" type="button" class="btn btn-outline-secondary" disabled>Siguiente</button>
          </div>
        </section>
        <section id="nombreHistory" class="card shadow-sm mb-3" hidden aria-labelledby="historyTitle">
          <div class="card-header py-2 d-flex flex-wrap gap-2 justify-content-between">
            <h3 id="historyTitle" class="card-title fw-bold mb-0">Historial de Órdenes</h3>
            <button id="historyBack" type="button" class="btn btn-outline-secondary btn-sm">Volver a resultados</button>
          </div>
          <div class="card-body py-2"><p id="historyIdentity" class="mb-0"></p></div>
          <div class="table-responsive">
            <table class="table table-vcenter card-table table-striped table-hover dataTable" id="tablaHistory">
              <thead><tr><th class="sorting" data-sort="orden">Orden de Giro</th><th class="sorting_desc" data-sort="fecha">Fecha</th><th class="sorting_disabled">Hora</th><th class="sorting" data-sort="recibo">Recibo</th><th class="sorting text-end" data-sort="importe">Importe total</th><th class="sorting" data-sort="estado">Estado</th><th class="sorting_disabled">Acciones</th></tr></thead>
              <tbody id="nombreOrders"></tbody>
            </table>
          </div>
          <div class="card-footer d-flex align-items-center justify-content-between gap-2">
            <button id="historyPrev" type="button" class="btn btn-outline-secondary" disabled>Anterior</button>
            <span id="historyPage" class="text-secondary"></span>
            <button id="historyNext" type="button" class="btn btn-outline-secondary" disabled>Siguiente</button>
          </div>
          <div class="card-body text-secondary small">La impresión utiliza el servicio existente; algunas órdenes inactivas pueden no tener detalle imprimible.</div>
        </section>
      </div></div>
      <?php require_once('../html/footer.php'); ?>
    </div>
  </div></div>
  <?php require_once('../html/mainjs.php'); ?>
  <script src="consultarnombre.js"></script>
</body>
</html>
