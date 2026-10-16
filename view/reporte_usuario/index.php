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
<title>SIGODT::Mis emisiones</title>
</head><body>
<div class="page"><div class="wrapper">
<?php require_once '../html/mainProfile.php'; require_once '../html/menu.php'; ?>
<div class="page-wrapper"><div class="page-body"><main class="container-xl">
<h1>Reporte de mis emisiones</h1>
<p>Consulte únicamente las órdenes que usted emitió. El importe emitido no equivale a dinero recaudado.</p>
<form id="reportForm" class="card card-body mb-3">
  <div class="row g-3">
    <div class="col-md-3"><label for="reportStart" class="form-label">Desde (emisión)</label><input id="reportStart" name="start" type="date" class="form-control" required></div>
    <div class="col-md-3"><label for="reportEnd" class="form-label">Hasta (día completo)</label><input id="reportEnd" name="end" type="date" class="form-control" required></div>
    <div class="col-md-3"><label for="reportDocument" class="form-label">Documento</label><select id="reportDocument" name="document" class="form-select"><option value="">Todos</option></select></div>
    <div class="col-md-3"><label for="reportProcedure" class="form-label">Procedimiento</label><select id="reportProcedure" name="procedure" class="form-select"><option value="">Todos</option></select></div>
    <div class="col-12"><button class="btn btn-primary" type="submit">Consultar</button></div>
  </div>
</form>
<p id="reportMessage" role="status" aria-live="polite">Seleccione el periodo y consulte.</p>
<section id="reportResults" hidden>
  <h2>Resumen</h2><p id="reportIdentity"></p><p id="reportSummary" class="alert alert-info"></p>
  <p>Los filtros cuentan cada orden coincidente una vez y suman solo sus conceptos coincidentes. Se incluyen órdenes anuladas, extornadas y estados históricos.</p>
  <a id="reportCsv" class="btn btn-outline-primary" download>Exportar CSV para Excel</a>
  <a id="reportPrint" class="btn btn-outline-secondary" target="_blank" rel="noopener">Imprimir / PDF (todo el filtro)</a>
  <h2 class="mt-3">Desglose por documento y procedimiento</h2>
  <p>Los conteos de órdenes del desglose no son aditivos: una orden puede tener varios procedimientos.</p>
  <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Documento</th><th>Código</th><th>Procedimiento</th><th>Órdenes</th><th>Conceptos</th><th>Importe (S/)</th></tr></thead><tbody id="reportBreakdown"></tbody></table></div>
  <h2>Detalle de órdenes</h2>
  <div class="table-responsive"><table class="table table-striped"><thead><tr><th>N.º de orden</th><th>Fecha y hora de emisión</th><th>Ciudadano / Razón social</th><th>Documento</th><th>Código del procedimiento</th><th>Nombre del procedimiento</th><th>Estado actual</th><th>Importe (S/)</th></tr></thead><tbody id="reportDetails"></tbody></table></div>
  <div class="d-flex justify-content-between mb-3"><button id="reportPrev" class="btn btn-outline-secondary" type="button">Anterior</button><span id="reportPage"></span><button id="reportNext" class="btn btn-outline-secondary" type="button">Siguiente</button></div>
</section>
</main></div><?php require_once '../html/footer.php'; ?></div>
</div></div>
<?php require_once '../html/mainjs.php'; ?>
<script src="reporteusuario.js"></script>
</body></html>
