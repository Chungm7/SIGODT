<?php
require_once '../../config/conexion.php';
require_once '../../models/ReporteUsuario.php';
try { ReporteUsuario::positiveId($_SESSION['usua_id_SIGODT'] ?? null); }
catch (InvalidArgumentException $error) {
    header('Location:' . Conectar::ruta() . 'view/404/');
    exit;
}
?>
<!doctype html>
<html lang="es"><head>
<?php require_once '../html/mainHead.php'; ?>
<title>SIGODT::Reportes</title>
<link rel="stylesheet" href="reporteusuario.css">
</head><body>
<script src="../../public/tabler/js/demo-theme.min.js"></script>
<div class="page"><div class="wrapper">
<?php require_once '../html/mainProfile.php'; require_once '../html/menu.php'; ?>
<div class="page-wrapper"><div class="page-body"><main class="container-xl report-page">
<header class="page-header mb-3">
  <ol class="breadcrumb breadcrumb-arrows mb-3" aria-label="Ruta de navegación">
    <li class="breadcrumb-item"><a href="../inicio/">SIGODT</a></li>
    <li class="breadcrumb-item active" aria-current="page">Reportes</li>
  </ol>
  <h1 class="page-title">Reportes</h1>
  <p class="text-muted mt-2 mb-0">Consulte las órdenes que usted emitió, sus conceptos y sus importes por periodo.</p>
</header>
<form id="reportForm" class="card shadow-sm mb-3">
  <div class="card-header"><h2 class="card-title">Filtros de consulta</h2></div>
  <div class="card-body"><div class="row g-3">
    <div class="col-12 col-sm-6 col-md-2"><label for="reportStart" class="form-label">Desde (emisión)</label><input id="reportStart" name="start" type="date" class="form-control" required></div>
    <div class="col-12 col-sm-6 col-md-2"><label for="reportEnd" class="form-label">Hasta (día completo)</label><input id="reportEnd" name="end" type="date" class="form-control" required></div>
    <div class="col-12 col-sm-6 col-md-2"><label for="reportState" class="form-label">Estado</label><select id="reportState" name="state" class="form-select"><option value="todos">Todos los estados</option><option value="emitidos">Solo emitidos (Girados y Pagados)</option><option value="anulados">Solo anuladas y extornadas</option><option value="1">Solo Girados</option><option value="4">Solo Pagados</option></select></div>
    <div class="col-12 col-sm-6 col-md-3"><label for="reportDocument" class="form-label">Documento</label><select id="reportDocument" name="document" class="form-select"><option value="">Todos</option></select></div>
    <div class="col-12 col-sm-6 col-md-3"><label for="reportProcedure" class="form-label">Procedimiento</label><select id="reportProcedure" name="procedure" class="form-select"><option value="">Todos</option></select></div>
    <div class="col-12"><button class="btn btn-primary" type="submit">Consultar</button></div>
  </div></div>
</form>
<p id="reportMessage" role="status" aria-live="polite">Seleccione el periodo y consulte.</p>
<section id="reportResults" hidden>
  <section class="card shadow-sm mb-3" aria-labelledby="reportSummaryTitle">
    <div class="card-header"><h2 id="reportSummaryTitle" class="card-title">Resumen de emisiones</h2></div>
    <div class="card-body">
      <p id="reportIdentity" class="text-muted"></p>
      <dl id="reportSummary" class="report-metrics">
        <div><dt>Total de órdenes emitidas</dt><dd id="reportOrders"></dd></div>
        <div><dt>Órdenes anuladas y extornadas</dt><dd id="reportCancelled"></dd></div>
        <div><dt>Total de tasas emitidas</dt><dd id="reportLines"></dd></div>
        <div><dt>Importe emitido</dt><dd id="reportAmount"></dd></div>
      </dl>
      <p class="mb-2"><strong>El importe emitido no equivale a dinero recaudado.</strong></p>
      <p class="text-muted mb-0">Los filtros cuentan cada orden coincidente una vez y suman solo sus conceptos coincidentes. Se incluyen órdenes anuladas, extornadas y estados históricos.</p>
    </div>
    <div class="card-footer report-actions">
      <button id="reportPdf" class="btn btn-primary" type="button" disabled>Descargar PDF</button>
      <a id="reportPrint" class="btn btn-outline-secondary" target="_blank" rel="noopener">Imprimir / PDF (todo el filtro)</a>
    </div>
  </section>
  <section class="card shadow-sm mb-3" aria-labelledby="reportBreakdownTitle">
    <div class="card-header"><h2 id="reportBreakdownTitle" class="card-title">Desglose por documento y procedimiento</h2></div>
    <p class="card-body text-muted mb-0">Los conteos de órdenes del desglose no son aditivos: una orden puede tener varios procedimientos.</p>
    <div class="table-responsive" tabindex="0" role="region" aria-label="Desglose por documento y procedimiento">
      <table class="table card-table table-vcenter report-breakdown"><thead><tr><th>Documento</th><th>Código</th><th>Procedimiento</th><th>Órdenes</th><th>Conceptos</th><th>Importe (S/)</th></tr></thead><tbody id="reportBreakdown"></tbody></table>
    </div>
  </section>
  <section class="card shadow-sm mb-3" aria-labelledby="reportDetailsTitle">
    <div class="card-header"><h2 id="reportDetailsTitle" class="card-title">Detalle de órdenes</h2></div>
    <div class="table-responsive" tabindex="0" role="region" aria-label="Detalle de órdenes">
      <table class="table card-table table-vcenter report-details"><thead><tr><th>N.º de orden</th><th>Fecha y hora de emisión</th><th>Ciudadano / Razón social</th><th>Documento</th><th>Código del procedimiento</th><th>Nombre del procedimiento</th><th>Estado actual</th><th>Importe (S/)</th></tr></thead><tbody id="reportDetails"></tbody></table>
    </div>
    <nav class="card-footer report-pagination" aria-label="Paginación del detalle"><button id="reportPrev" class="btn btn-outline-secondary" type="button">Anterior</button><span id="reportPage" aria-live="polite"></span><button id="reportNext" class="btn btn-outline-secondary" type="button">Siguiente</button></nav>
  </section>
</section>
</main></div><?php require_once '../html/footer.php'; ?></div>
</div></div>
<?php require_once '../html/mainjs.php'; ?>
<script src="../../public/lib/jspdf/jspdf.umd.min.js"></script>
<script src="../../public/lib/jspdf-autotable/jspdf.plugin.autotable.min.js"></script>
<script src="reportepdf.js"></script>
<script src="reporteusuario.js"></script>
</body></html>
