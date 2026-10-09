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

<!-- CSS de Librerías / CDN -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet">
<link href="../html/global/css/select2.css" rel="stylesheet" />

<!-- FontAwesome 5.15.4 (CDN) -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

<!-- Toastr & SweetAlert CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.8/dist/sweetalert2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<!-- Tipografía Inter -->
<style>
    @import url('https://rsms.me/inter/inter.css');

    :root {
        --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
    }

    body {
        font-feature-settings: "cv03", "cv04", "cv11";
    }
</style>

<!-- Estilos Globales de Plantilla (sisGitse) -->
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
        background: linear-gradient(135deg, #141E30 0%, #243B55 100%);
        color: #fcfcfc !important;
    }

    body:not([data-bs-theme="dark"]) .dropdown-item:hover,
    body:not([data-bs-theme="dark"]) .nav-link:hover {
        background-color: rgba(0, 0, 0, 0.03);
        transition: all 0.2s ease-in-out;
    }

    .brand-mark {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        background: #111318;
        color: white;
        box-shadow: 0 7px 20px rgba(0, 0, 0, .12);
    }

    /* Select2 ajustes consistentes */
    .select2-container .select2-selection--single {
        height: 38px !important;
        padding: 5px 0;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 28px !important;
        padding-left: 10px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }

    .select2-dropdown {
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
    }

    .select2-container--default .select2-results__option {
        padding: 8px 12px;
    }

    .select2-container--default .select2-results__option--highlighted {
        background-color: #0d6efd;
    }

    /* Modales estilo sisGitse (bg-gradient-dark y cierre blanco) */
    .modal-header {
        background: linear-gradient(135deg, #141E30 0%, #243B55 100%) !important;
        color: #fcfcfc !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
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

    /* Page Titles estilo sisGitse */
    .page-title {
        font-weight: 700 !important;
        letter-spacing: -0.2px;
        color: #111827;
        text-transform: uppercase;
    }

    /* Encabezados de tabla en azul institucional sisGitse */
    th {
        color: #0054a6 !important;
    }

    /* Inputs redondeados y contenedores de icono estilo sisGitse */
    .form-select,
    .form-control {
        border-radius: 8px !important;
        transition: all 0.2s ease;
    }

    .form-select:focus,
    .form-control:focus {
        border-color: #0d6efd !important;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15) !important;
    }

    .icon-container {
        background: rgba(13, 110, 253, 0.1);
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
        color: #FFC107 !important;
        border-color: #FFC107 !important;
    }

    .swal2-styled.swal2-confirm {
        background-color: #0165c9 !important;
    }

    .swal2-styled.swal2-cancel {
        background-color: #de2b2b !important;
    }

    .swal2-icon.swal2-error {
        border-color: #fe0a0a;
        color: #fe0a0a;
    }

    .swal2-icon.swal2-error [class^=swal2-x-mark-line] {
        background-color: #fe0a0a !important;
    }

    /* Paginación y búsqueda DataTables */
    .dataTables_wrapper .dataTables_paginate {
        text-align: center !important;
        margin-top: 15px;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        color: black !important;
        background-color: transparent;
        border-radius: 5px;
        cursor: pointer;
        padding: 6px 12px;
        transition: background 0.3s ease-in-out, color 0.3s ease-in-out;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: #0054a6 !important;
        color: white !important;
        border: 1px solid #007bff;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #0054a6 !important;
        color: white !important;
        border: 1px solid #0054a6 !important;
        font-weight: bold;
        border-radius: 5px;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current a {
        color: white !important;
    }

    table.dataTable thead th,
    table.dataTable thead td {
        border-bottom: 1px solid #dce1e7 !important;
        border-top: 1px solid #dce1e7 !important;
    }

    .dataTables_length,
    .dataTables_info {
        color: #6c7a91 !important;
        margin: 10px;
    }

    .dataTables_length select {
        color: #6c7a91 !important;
    }

    .dataTables_wrapper .dataTables_filter {
        margin: 10px;
        text-align: right;
    }

    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #ccc;
        border-radius: 4px;
        padding: 6px 12px;
        background: #f8f8f8;
        transition: all 0.3s ease;
        font-size: 14px;
    }

    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: #66afe9;
        box-shadow: 0 0 8px rgba(102, 175, 233, 0.6);
        background: #fff;
        outline: none;
    }

    .dataTables_wrapper .dataTables_filter label {
        font-size: 14px;
        color: #333;
    }

    .dataTables_wrapper {
        scale: 0.99;
    }
</style>