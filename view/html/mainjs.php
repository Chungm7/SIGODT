<!-- jQuery cargado en mainHead.php para disponibilidad inmediata de componentes -->

<!-- Tabler Core -->
<script src="../../public/tabler/js/tabler.min.js" defer></script>
<script src="../../public/tabler/js/demo.min.js" defer></script>
<!-- Libs JS -->
<script src="../../public/tabler/libs/apexcharts/dist/apexcharts.min.js?1692870487" defer></script>

<!-- Polyfill para navegadores antiguos (opcional) -->
<script src="../../public/tabler/libs/countup.js/dist/requestAnimationFrame.polyfill.js"></script>

<!-- Versión UMD de CountUp (asegúrate de que exista en tu carpeta) -->
<script src="../../public/tabler/libs/countup.js/dist/countUp.umd.js"></script>

<!-- Toastr JS -->
<script src="../../public/lib/toastr/toastr.min.js"></script>

<script src="../../public/lib/datatables/jquery.dataTables.min.js"></script>
<script src="../../public/lib/datatables/dataTables.buttons.min.js"></script>
<script src="../../public/lib/datatables/buttons.html5.min.js"></script>
<script src="../../public/lib/datatables/buttons.print.min.js"></script>

<script src="../../public/lib/select2/js/select2.min.js"></script>

<!-- SweetAlert2 JS -->
<script src="../../public/lib/sweetalert2/sweetalert2.min.js"></script>
<script src="../../public/lib/popper/popper.min.js"></script>

<script>
    // Prevención global: no permitir guardar ni enviar formularios al presionar Enter en modales
    $(document).on("keydown", ".modal input, .modal select, .modal button, .modal .btn", function (e) {
        if (e.key === "Enter" || e.keyCode === 13) {
            // Permitir saltos de línea normales en textareas
            if (e.target && e.target.tagName && e.target.tagName.toLowerCase() === "textarea") {
                return;
            }
            // Permitir selección de opciones en buscadores de Select2
            if ($(e.target).hasClass("select2-search__field")) {
                return;
            }
            e.preventDefault();
        }
    });

    // Autofocus automático al abrir cualquier modal: enfoca el primer campo editable
    $(document).on("shown.bs.modal", ".modal", function () {
        const $modal = $(this);
        let $target = $modal.find("[data-autofocus]:visible:not([disabled]):not([readonly]), [autofocus]:visible:not([disabled]):not([readonly])").first();
        if (!$target.length) {
            $target = $modal.find("input:visible:not([disabled]):not([readonly]):not([type='hidden']), select:visible:not([disabled]), textarea:visible:not([disabled])").first();
        }
        if ($target.length) {
            $target.focus();
            if ($target.is("input:text, input[type='number']")) {
                $target.select();
            }
        }
    });
</script>
<?php require_once __DIR__ . '/spinner.php'; ?>
