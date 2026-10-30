<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
<meta http-equiv="X-UA-Compatible" content="ie=edge" />
<link rel="icon" href="../../public/img/mpch.ico" type="image/x-icon">

<!-- Tabler Core CSS (Template base de sisGitse) -->
<link href="../../public/css/tabler.min.css?1692870487" rel="stylesheet" />
<link href="../../public/css/tabler-flags.min.css?1692870487" rel="stylesheet" />
<link href="../../public/css/tabler-payments.min.css?1692870487" rel="stylesheet" />
<link href="../../public/css/tabler-vendors.min.css?1692870487" rel="stylesheet" />
<link href="../../public/css/demo.min.css?1692870487" rel="stylesheet" />
<script src="../../public/js/demo-theme.min.js?1692870487"></script>

<!-- Módulos CSS Reutilizables de Plantilla (sisGitse) -->
<link href="../../public/css/datatable.css" rel="stylesheet" />
<link href="../../public/css/Breadcrumb.css" rel="stylesheet" />
<link href="../../public/css/iconos.css" rel="stylesheet" />
<link href="../../public/css/alerta.css" rel="stylesheet" />
<link href="../../public/css/botones.css" rel="stylesheet" />
<link href="../../public/css/loader.css" rel="stylesheet" />
<link href="../../public/css/emision.css" rel="stylesheet" />

<!-- CSS de Librerías / Locales -->
<link rel="stylesheet" href="../../public/lib/datatables/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../public/lib/datatables/buttons.dataTables.min.css">
<link rel="stylesheet" href="../../public/lib/datatables/responsive.dataTables.min.css">
<link href="../../public/lib/select2/css/select2.min.css" rel="stylesheet" />
<link href="../../public/lib/select2/css/select2-bootstrap4.min.css" rel="stylesheet">
<link href="../html/global/css/select2.css" rel="stylesheet" />

<!-- Toastr & SweetAlert CSS -->
<link href="../../public/lib/toastr/toastr.min.css" rel="stylesheet" />
<link href="../../public/lib/sweetalert2/sweetalert2.min.css" rel="stylesheet" />
<script src="../../public/lib/xlsx/xlsx.full.min.js"></script>

<!-- Tipografía Roboto (Local) -->
<style>
    @import url('../../public/lib/roboto/roboto.css');

    :root {
        --tblr-font-sans-serif: 'Roboto', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Helvetica Neue, sans-serif;
    }
</style>

<!-- Sistema de Diseño Institucional MPCH -->
<link href="../../public/css/municipal-theme.css" rel="stylesheet" />

<!-- Estilos Globales de Plantilla (SIGODT / MPCH) -->
<style>
    /* Estructura para Sticky Footer (Footer siempre al fondo de la pantalla) */
    html, body {
        height: 100%;
    }

    .page {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    .wrapper {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        flex: 1 0 auto;
        width: 100%;
    }

    .page-wrapper {
        display: flex;
        flex-direction: column;
        flex: 1 0 auto;
        min-height: calc(100vh - 180px);
        width: 100%;
    }

    .page-body {
        flex: 1 0 auto;
    }

    .footer {
        margin-top: auto !important;
    }

    .bg-white {
        color: #fcfcfc;
    }

    .bg-gradient-dark {
        background: linear-gradient(135deg, #0052A3 0%, #0066CC 100%);
        color: #fcfcfc !important;
    }

    body:not([data-bs-theme="dark"]) .dropdown-item:hover,
    body:not([data-bs-theme="dark"]) .nav-link:hover {
        background-color: rgba(0, 102, 204, 0.04);
        transition: all 0.2s ease-in-out;
    }

    .brand-mark {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        background: #FFFFFF;
        color: #0066CC;
        box-shadow: 0 2px 4px -2px rgba(30, 58, 138, 0.2);
    }

    /* Modales institucionales MPCH con barra superior de seguridad */
    .modal-header {
        background: #0066CC !important;
        color: #ffffff !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.15) !important;
        padding: 1rem 1.5rem !important;
    }

    .modal-header .modal-title,
    .modal-header h1,
    .modal-header h2,
    .modal-header h3,
    .modal-header h4,
    .modal-header h5 {
        color: #ffffff !important;
        font-weight: 600 !important;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .modal-header .modal-title svg,
    .modal-header h1 svg,
    .modal-header h2 svg,
    .modal-header h3 svg,
    .modal-header h4 svg,
    .modal-header h5 svg {
        color: #ffffff !important;
        stroke: #ffffff !important;
    }

    .modal-header .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%) !important;
        opacity: 0.85;
    }

    .modal-header .btn-close:hover {
        opacity: 1;
    }

    /* Page Titles institucionales */
    .page-title {
        font-weight: 700 !important;
        letter-spacing: -0.2px;
        color: #0F172A;
        text-transform: uppercase;
    }

    /* Encabezados de tabla en azul institucional MPCH */
    th {
        color: #0066CC !important;
    }

    /* Inputs redondeados y contenedores de icono */
    .form-select,
    .form-control {
        border-radius: 8px !important;
        transition: all 0.2s ease;
    }

    .form-select:focus,
    .form-control:focus {
        border-color: #0066CC !important;
        box-shadow: 0 0 0 0.2rem rgba(0, 102, 204, 0.18) !important;
    }

    .icon-container {
        background: #E7F1FB;
        border: 1px solid #BAE6FD;
        border-radius: 50%;
        padding: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
</style>

<!-- Ajustes Específicos del Sistema (SIGODT) -->
<style>
    /* Mayúsculas consistentes */
    input[type="text"],
    input.uppercase {
        text-transform: uppercase;
    }

    tbody {
        font-size: 12px;
        text-transform: uppercase;
    }

    table {
        font-size: 12px;
    }

    table tbody tr td {
        vertical-align: middle;
    }

    .table-responsive {
        min-height: 300px !important;
    }

    /* SweetAlert2 paleta institucional y backdrop blur */
    .swal2-container {
        backdrop-filter: blur(2px);
        -webkit-backdrop-filter: blur(4px);
    }

    .swal2-icon.swal2-warning.swal2-icon-show {
        color: #F59E0B !important;
        border-color: #F59E0B !important;
    }

    .swal2-styled.swal2-confirm {
        background-color: #0066CC !important;
    }

    .swal2-styled.swal2-cancel {
        background-color: #DC2626 !important;
    }

    .swal2-icon.swal2-error {
        border-color: #EF4444;
        color: #EF4444;
    }

    .swal2-icon.swal2-error [class^=swal2-x-mark-line] {
        background-color: #EF4444 !important;
    }

    /* Paginación y búsqueda DataTables */
    .dataTables_wrapper .dataTables_paginate {
        text-align: center !important;
        margin-top: 15px;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        color: #0F172A !important;
        background-color: transparent;
        border-radius: 6px;
        cursor: pointer;
        padding: 6px 12px;
        transition: background 0.2s ease-in-out, color 0.2s ease-in-out;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: #0066CC !important;
        color: white !important;
        border: 1px solid #0066CC;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #0066CC !important;
        color: white !important;
        border: 1px solid #0066CC !important;
        font-weight: bold;
        border-radius: 6px;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current a {
        color: white !important;
    }

    table.dataTable thead th,
    table.dataTable thead td {
        border-bottom: 1px solid #E2E8F0 !important;
        border-top: 1px solid #E2E8F0 !important;
    }

    .dataTables_length,
    .dataTables_info {
        color: #475569 !important;
        margin: 10px;
    }

    .dataTables_length select {
        color: #0F172A !important;
        border-color: #CBD5E1;
        border-radius: 6px;
    }

    .dataTables_wrapper .dataTables_filter {
        margin: 10px;
        text-align: right;
    }

    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #CBD5E1;
        border-radius: 8px;
        padding: 6px 12px;
        background: #F8FAFC;
        transition: all 0.2s ease;
        font-size: 14px;
    }

    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: #0066CC;
        box-shadow: 0 0 0 0.2rem rgba(0, 102, 204, 0.18);
        background: #fff;
        outline: none;
    }

    .dataTables_wrapper .dataTables_filter label {
        font-size: 14px;
        color: #475569;
    }

    .dataTables_wrapper {
        scale: 0.99;
    }
</style>
