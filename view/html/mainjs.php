<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

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
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>



<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.8/dist/sweetalert2.min.js"></script>
<script src="https://unpkg.com/@popperjs/core@2"></script>

<script>
    $(document).ready(function() {
        const $container = $('#sistemasContainer');

        function cargarSistemas() {
            if ($container.data('loaded')) return;

            $.getJSON('../../controller/usuario.php?op=listar_sistemas', data => {
                $container.empty();

                data.forEach(sis => {
                    const logoSrc = `data:image/png;base64,${sis.sist_logo}`;
                    $container.append(`
                        <div class="col-4 sistema-item"
                            data-nombre="${sis.sist_denominacion.toLowerCase()}">
                        <a href="${sis.sist_url}" target="_blank"
                            class="d-flex flex-column flex-center text-center text-secondary py-2 px-2 link-hoverable"
                            aria-label="${sis.sist_denominacion}">
                            <img src="${logoSrc}"
                                class="w-6 h-6 mx-auto mb-2"
                                width="24" height="24" style="padding: 5px;"
                                alt="${sis.sist_denominacion}">
                            <!-- Solo mostramos las iniciales -->
                            <span class="h5 mb-0">${sis.sist_iniciales}</span>
                        </a>
                        </div>
                    `);
                });

                $container.data('loaded', true);
            }).fail((_, __, err) => console.error('Error al cargar sistemas:', err));
        }


        // Precarga inmediata
        cargarSistemas();

        // O bien, lectura bajo demanda al abrir el dropdown
        $('#sistemasMenu').parent().on('show.bs.dropdown', cargarSistemas);
    });
    // dentro de tu ready, tras la definición de cargarSistemas y el evento show.bs.dropdown
    $('#searchSistemas').on('input', function() {
        const q = $(this).val().trim().toLowerCase();

        // Recorremos cada .sistema-item
        $('#sistemasContainer .sistema-item').each(function() {
            const nombre = $(this).data('nombre'); // el full name en minúsculas

            if (nombre.includes(q)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
</script>