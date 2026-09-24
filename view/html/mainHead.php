<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
<meta http-equiv="X-UA-Compatible" content="ie=edge" />
<link rel="icon" href="../../public/img/mpch.ico" type="image/x-icon">
<!-- CSS files -->
<link href="../../public/tabler/css/tabler.min.css?1692870487" rel="stylesheet" />
<link href="../../public/tabler/css/tabler-flags.min.css?1692870487" rel="stylesheet" />
<link href="../../public/tabler/css/tabler-payments.min.css?1692870487" rel="stylesheet" />
<link href="../../public/tabler/css/tabler-vendors.min.css?1692870487" rel="stylesheet" />
<link href="../../public/tabler/css/demo.min.css?1692870487" rel="stylesheet" />
<!-- CSS y JS de Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">

<link href="../html/global/css/select2.css" rel="stylesheet" />
<style>
    @import url('https://rsms.me/inter/inter.css');

    :root {
        --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
    }

    body {
        font-feature-settings: "cv03", "cv04", "cv11";
    }
</style>

<!-- FontAwesome 5.15.4 (CDN) -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

<!-- Toastr CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />


<!-- SweetAlert CSS -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.8/dist/sweetalert2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>



<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
<style>
    table {
        font-size: 12px;
    }

    table tbody tr td {
        vertical-align: middle;
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
</style>

<!-- Estilos de tablas datatable -->
<style>
    input.uppercase {
        text-transform: uppercase;
    }

    tbody {
        font-size: 12px;
        text-transform: uppercase;
    }

    .table-responsive {
        min-height: 300px !important;
    }


    /* Centrar y mejorar el espaciado de la paginación */
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

    /* Cambia el color de los botones al pasar el mouse */
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: #0054a6 !important;
        color: white !important;
        border: 1px solid #007bff;
    }

    /* Aplica estilo al botón activo */
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #0054a6 !important;
        color: white !important;
        border: 1px solid #0054a6 !important;
        font-weight: bold;
        border-radius: 5px;
    }

    /* Fuerza el color blanco en el botón actual */
    .dataTables_wrapper .dataTables_paginate .paginate_button.current a {
        color: white !important;
    }

    table.dataTable thead th,
    table.dataTable thead td {

        border-bottom: 1px solid #dce1e7 !important;
        border-top: 1px solid #dce1e7 !important;
    }

    .dataTables_length {
        color: #6c7a91 !important;
        margin: 10px;
    }

    .dataTables_length select {
        color: #6c7a91 !important;
    }

    .dataTables_info {
        color: #6c7a91 !important;
    }

    /* Contenedor del filtro */
    .dataTables_wrapper .dataTables_filter {
        margin: 10px;
        text-align: right;
        /* Alinea el filtro a la derecha */
    }

    /* Estilo del input de búsqueda */
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #ccc;
        border-radius: 4px;
        padding: 6px 12px;
        background: #f8f8f8;
        transition: all 0.3s ease;
        font-size: 14px;
    }

    /* Estilo al hacer focus en el input */
    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: #66afe9;
        box-shadow: 0 0 8px rgba(102, 175, 233, 0.6);
        background: #fff;
        outline: none;
    }

    /* Opcional: Estilo para el label del filtro */
    .dataTables_wrapper .dataTables_filter label {
        font-size: 14px;
        color: #333;
    }

    .dataTables_wrapper {
        scale: 0.99;
    }
</style>

<!-- todo mayuscula -->
<style>
    input[type="text"] {
        text-transform: uppercase;
        /* Convierte el texto a mayúsculas */
    }
</style>
<style>
    /* .select2-container {
        width: 100% !important;
    } */
</style>