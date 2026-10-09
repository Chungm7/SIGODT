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
  <title>SIGODT::Consulta por Nombre / Razón Social</title>
</head>
<body>
  <script src="../../public/tabler/js/demo-theme.min.js"></script>
  <div class="page"><div class="wrapper">
    <?php require_once('../html/mainProfile.php'); ?>
    <?php require_once('../html/menu.php'); ?>
    <div class="page-wrapper">
      <div class="page-body"><div class="container-xl">
        <div class="page-header d-print-none mb-3">
          <ol class="breadcrumb breadcrumb-arrows mb-3" aria-label="breadcrumbs">
            <li class="breadcrumb-item"><a href="../inicio/">SIGODT</a></li>
            <li class="breadcrumb-item">Consultas</li>
            <li class="breadcrumb-item active" aria-current="page">Consulta por Nombre / Razón Social</li>
          </ol>
          <h2 class="page-title">Consulta por Nombre / Razón Social</h2>
          <div class="text-secondary mt-2">Busque ciudadanos o empresas y consulte su historial completo, sin excluir estados ni fechas.</div>
        </div>
        <div class="card shadow-sm mb-3">
          <div class="card-header py-2"><h3 class="card-title fw-bold mb-0">Criterio de Búsqueda</h3></div>
          <div class="card-body">
            <form id="nombreForm" class="row g-2 align-items-end">
              <div class="col-12 col-md-8">
                <label for="nombreSearch" class="form-label">Nombre del ciudadano o razón social / nombre comercial</label>
                <input id="nombreSearch" type="text" class="form-control" required autofocus maxlength="200" placeholder="Ingrese parte del nombre" aria-describedby="nombreHelp">
              </div>
              <div class="col-12 col-md-auto"><button type="submit" class="btn btn-primary w-100">Buscar</button></div>
              <div class="col-12"><div id="nombreHelp" class="form-hint">Búsqueda parcial automática tras 400 ms, desde 3 caracteres. Buscar o Enter consulta de inmediato. Seleccione «Ver historial» para ver sus órdenes.</div></div>
            </form>
          </div>
        </div>
        <div id="nombreMessage" class="alert alert-info mb-3" role="status" aria-live="polite">Ingrese un nombre para buscar.</div>
        <section id="nombreResults" class="card shadow-sm mb-3" aria-labelledby="resultsTitle">
          <div class="card-header py-2"><h3 id="resultsTitle" class="card-title fw-bold mb-0">Entidades y Total de Órdenes</h3></div>
          <div class="table-responsive">
            <table class="table table-vcenter card-table table-striped table-hover">
              <thead><tr><th>Tipo</th><th>Nombre / Razón social</th><th>Documento / RUC</th><th>Total de órdenes</th><th>Acciones</th></tr></thead>
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
            <table class="table table-vcenter card-table table-striped table-hover">
              <thead><tr><th>Orden de Giro</th><th>Fecha</th><th>Hora</th><th>Recibo</th><th>Importe total</th><th>Estado</th><th>Acciones</th></tr></thead>
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
