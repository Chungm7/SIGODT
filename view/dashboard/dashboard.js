// ========== ESTADOS (BADGES) ==========

const estadosDisponibles = ["0", "1", "3", "4", "5", "6"];
const badgeContainer = document.getElementById("badgesEstados");
let selectedEstados = ["all", ...estadosDisponibles]; // Por defecto todos activos

function getEstadosSeleccionados() {
    return selectedEstados.filter(e => e !== "all");
}

function getEstadosParams() {
    return getEstadosSeleccionados().map(e => `estados[]=${encodeURIComponent(e)}`).join("&");
}

// Efecto "active" y lógica de selección
badgeContainer.addEventListener("click", function (e) {
    if (e.target.classList.contains("badge-estado")) {
        let val = e.target.getAttribute("data-value");

        if (val === "all") {
            // Si YA están todos seleccionados, desactiva todos
            if (
                selectedEstados.includes("all") &&
                estadosDisponibles.every(st => selectedEstados.includes(st)) &&
                selectedEstados.length === estadosDisponibles.length + 1
            ) {
                selectedEstados = [];
                badgeContainer.querySelectorAll(".badge-estado").forEach(b => b.classList.remove("active"));
            } else {
                selectedEstados = ["all", ...estadosDisponibles];
                badgeContainer.querySelectorAll(".badge-estado").forEach(b => b.classList.add("active"));
            }
        } else {
            if (selectedEstados.includes(val)) {
                selectedEstados = selectedEstados.filter(v => v !== val);
                e.target.classList.remove("active");
            } else {
                selectedEstados.push(val);
                e.target.classList.add("active");
            }
            // Sincroniza "Todos"
            if (estadosDisponibles.every(st => selectedEstados.includes(st))) {
                selectedEstados = ["all", ...estadosDisponibles];
                badgeContainer.querySelector('[data-value="all"]').classList.add("active");
            } else {
                selectedEstados = selectedEstados.filter(v => v !== "all");
                badgeContainer.querySelector('[data-value="all"]').classList.remove("active");
            }
        }
        // Visualmente todos los badges
        estadosDisponibles.forEach(st => {
            let badge = badgeContainer.querySelector(`[data-value="${st}"]`);
            if (selectedEstados.includes(st)) badge.classList.add("active");
            else badge.classList.remove("active");
        });
        // "Todos"
        if (selectedEstados.includes("all")) badgeContainer.querySelector('[data-value="all"]').classList.add("active");
        else badgeContainer.querySelector('[data-value="all"]').classList.remove("active");

        // Recargar datos en dashboard y tabla
        fetchDashboardData();
        cargarTablaOrdenesDependencia();
    }
});

// ========== FECHAS POR DEFECTO ==========

function setDefaultMonthDates() {
    const now = new Date();
    const yyyy = now.getFullYear();
    const mm = String(now.getMonth() + 1).padStart(2, '0');
    const dd = String(now.getDate()).padStart(2, '0');
    document.getElementById('fechaIni').value = `${yyyy}-${mm}-01`;
    document.getElementById('fechaFin').value = `${yyyy}-${mm}-${dd}`;
}

// ========== DASHBOARD PRINCIPAL ==========

window.chartOrdenesEstado = null;
window.chartRecaudacionArea = null;
window.chartEvolucionRecaudacion = null;

let fechaIni = '';
let fechaFin = '';

function fetchDashboardData() {
    fechaIni = document.getElementById('fechaIni').value;
    fechaFin = document.getElementById('fechaFin').value;
    const estadosParams = getEstadosParams();

    // 1. Total recaudado
    fetch('../../controller/dashboard.php?op=total_recaudado', {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `fecha_ini=${fechaIni}&fecha_fin=${fechaFin}` + (estadosParams ? "&" + estadosParams : "")
    })
        .then(r => r.json())
        .then(data => {
            document.getElementById("totalRecaudado").innerHTML = "S/ " + parseFloat(data.total).toLocaleString('es-PE', { minimumFractionDigits: 2 });
        });
    // 1bis. Pendiente por Cobrar
    fetch('../../controller/dashboard.php?op=pendiente_por_cobrar', {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `fecha_ini=${fechaIni}&fecha_fin=${fechaFin}` + (estadosParams ? "&" + estadosParams : "")
    })
        .then(r => r.json())
        .then(data => {
            const val = parseFloat(data.pendiente) || 0;
            document.getElementById("pendientePorCobrar").innerHTML =
                "S/ " + val.toLocaleString('es-PE', { minimumFractionDigits: 2 });
        });

    // 2. Procedimientos iniciados
    fetch('../../controller/dashboard.php?op=procedimientos_iniciados', {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `fecha_ini=${fechaIni}&fecha_fin=${fechaFin}` + (estadosParams ? "&" + estadosParams : "")
    })
        .then(r => r.json())
        .then(data => {
            document.getElementById("procedimientosIniciados").innerHTML = data.total;
        });

    // 3. Órdenes por estado (para ordenesPagadas y gráfico)
    fetch('../../controller/dashboard.php?op=ordenes_por_estado', {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `fecha_ini=${fechaIni}&fecha_fin=${fechaFin}` + (estadosParams ? "&" + estadosParams : "")
    })
        .then(r => r.json())
        .then(data => {
            let estados = ["Anulado", "Girado", "Improcedente", "Pagado", "Usado", "Extornado"];
            let colores = ["#d63939", "#228be6", "#f76707", "#2fb344", "#ae3ec9", "#f59f00"];
            let counts = [0, 0, 0, 0, 0, 0];
            data.forEach(d => {
                const raw = parseInt(d.est, 10);
                // si es 2 o mayor, lo mapeamos al índice -1
                const idx = raw >= 2 ? raw - 1 : raw;
                if (idx >= 0 && idx < counts.length) {
                    counts[idx] = parseInt(d.cantidad, 10);
                }
            });
            // Órdenes pagadas
            const totalGeneradas = counts.reduce((sum, v) => sum + v, 0);
            document.getElementById("ordenesPagadas").innerHTML = totalGeneradas;
            // Gráfico Pie de estados
            let optionsPie = {
                chart: { type: 'donut' },
                labels: estados,
                series: counts,
                colors: colores,
                legend: { position: 'bottom' }
            };
            if (window.chartOrdenesEstado && typeof window.chartOrdenesEstado.updateOptions === "function") {
                window.chartOrdenesEstado.updateOptions(optionsPie);
                window.chartOrdenesEstado.updateSeries(counts);
            } else {
                window.chartOrdenesEstado = new ApexCharts(document.querySelector("#chartOrdenesEstado"), optionsPie);
                window.chartOrdenesEstado.render();
            }
        });

    // 4. Recaudación por área/dependencia
    fetch('../../controller/dashboard.php?op=recaudacion_area_estado', {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `fecha_ini=${fechaIni}&fecha_fin=${fechaFin}` + (estadosParams ? "&" + estadosParams : "")
    })
        .then(r => r.json())
        .then(data => {
            let estados = ["Anulado", "Girado", "Improcedente", "Pagado", "Usado", "Extornado"];
            let colores = ["#d63939", "#228be6", "#f76707", "#2fb344", "#ae3ec9", "#f59f00"];
            let sigla2nombre = {};
            data.forEach(row => {
                if (row.depe_abreviatura && row.depe_denominacion)
                    sigla2nombre[row.depe_abreviatura] = row.depe_denominacion;
            });
            let areaSet = new Set();
            let seriesData = Array(estados.length).fill().map(() => ({}));
            data.forEach(row => {
                let area = row.depe_abreviatura || "";
                let est = parseInt(row.estado);
                let total = parseFloat(row.total);
                const idx = est >= 2 ? est - 1 : est;
                areaSet.add(area);
                if (!seriesData[idx][area]) seriesData[idx][area] = 0;
                seriesData[idx][area] += total;
            });
            let areas = Array.from(areaSet);
            let series = estados.map((name, idx) => ({
                name,
                data: areas.map(area => seriesData[idx][area] || 0)
            }));

            let optionsBar = {
                chart: { type: 'bar', height: 300, stacked: true },
                series: series,
                xaxis: {
                    categories: areas,
                    labels: {
                        formatter: function (value) {
                            // Solo muestra la sigla
                            return value;
                        }
                    }
                },
                colors: colores,
                dataLabels: { enabled: false },
                legend: { position: 'bottom' },
                tooltip: {
                    y: {
                        formatter: val => "S/ " + val.toLocaleString('es-PE', { minimumFractionDigits: 2 })
                    },
                    x: {
                        formatter: function (value) {
                            // Muestra el nombre completo si existe
                            return sigla2nombre[value] || value;
                        }
                    }
                }
            };
            if (window.chartRecaudacionArea && typeof window.chartRecaudacionArea.updateOptions === "function") {
                window.chartRecaudacionArea.updateOptions(optionsBar);
                window.chartRecaudacionArea.updateSeries(series);
            } else {
                window.chartRecaudacionArea = new ApexCharts(document.querySelector("#chartRecaudacionArea"), optionsBar);
                window.chartRecaudacionArea.render();
            }
            document.getElementById("usuariosActivos").innerHTML = areas.length;
        });

    fetch('../../controller/dashboard.php?op=evolucion_mensual', {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `fecha_ini=${fechaIni}&fecha_fin=${fechaFin}` + (estadosParams ? "&" + estadosParams : "")
    })
        .then(r => r.json())
        .then(data => {
            // Nombres y colores según índice de estado
            const estadosNombres = [
                "Anulado",
                "Girado",
                "Improcedente",
                "Pagado",
                "Usado",
                "Extornado"
            ];
            const colores = [
                "#d63939",
                "#228be6",

                "#f76707",
                "#2fb344",
                "#ae3ec9",
                "#f59f00"
            ];

            // Obtener lista única y ordenada de meses
            const meses = Array.from(new Set(data.map(e => e.mes_anio))).sort();

            // Inicializar series (una por estado) con ceros
            const series = estadosNombres.map((nombre, idx) => ({
                name: nombre,
                data: meses.map(() => 0)
            }));

            // Llenar datos
            data.forEach(e => {
                const mesIdx = meses.indexOf(e.mes_anio);
                const rawEst = parseInt(e.est, 10);

                // si el estado es 2 o mayor, restamos 1 para compensar el slot que borraste
                const idx = rawEst >= 2 ? rawEst - 1 : rawEst;

                if (idx >= 0 && idx < series.length) {
                    series[idx].data[mesIdx] = parseFloat(e.total);
                }
            });

            // Opciones del bar chart apilado
            const optionsBar = {
                chart: {
                    type: 'bar',
                    stacked: true,
                    height: 300
                },
                plotOptions: {
                    bar: {
                        columnWidth: '50%'
                    }
                },
                series: series,
                xaxis: {
                    categories: meses
                },
                colors: colores,
                dataLabels: { enabled: false },
                legend: { position: 'bottom' }
            };

            // Render o update
            if (window.chartEvolucionRecaudacion?.updateOptions) {
                window.chartEvolucionRecaudacion.updateOptions(optionsBar);
                window.chartEvolucionRecaudacion.updateSeries(series);
            } else {
                window.chartEvolucionRecaudacion =
                    new ApexCharts(
                        document.querySelector("#chartEvolucionRecaudacion"),
                        optionsBar
                    );
                window.chartEvolucionRecaudacion.render();
            }
        });

}

// ========== TABLA DE ORDENES POR DEPENDENCIA ==========

function cargarTablaOrdenesDependencia() {
    const estadosParams = getEstadosParams();
    fetch('../../controller/dashboard.php?op=ordenes_por_dependencia_estado', {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `fecha_ini=${fechaIni}&fecha_fin=${fechaFin}` + (estadosParams ? "&" + estadosParams : "")
    })
        .then(r => r.json())
        .then(data => {
            // labels y colores por estado (índice = ogc.est)
            const estados = ["Anulado", "Girado", "Girado", "Improcedente", "Pagado", "Usado", "Extornado"];
            const colores = ["#d63939", "#228be6", "#228be6", "#f76707", "#2fb344", "#ae3ec9", "#f59f00"];

            // Agrupar filas por dependencia
            const depMap = {};
            data.forEach(row => {
                const dep = row.depe_id;
                if (!depMap[dep]) {
                    depMap[dep] = {
                        denominacion: row.depe_denominacion,
                        siglas: row.depe_siglasdoc,
                        estados: {}   // mapa est → { total, total_hoy, total_semana }
                    };
                }
                depMap[dep].estados[row.est] = {
                    total: parseInt(row.total, 10),
                    total_hoy: parseInt(row.total_hoy, 10),
                    total_semana: parseInt(row.total_semana, 10)
                };
            });

            // Construir filas
            let tbody = "";
            Object.entries(depMap).forEach(([dep, obj]) => {
                // Sumar totales globales
                const total = Object.values(obj.estados).reduce((s, e) => s + e.total, 0);
                const totalHoy = Object.values(obj.estados).reduce((s, e) => s + e.total_hoy, 0);
                const totalSemana = Object.values(obj.estados).reduce((s, e) => s + e.total_semana, 0);

                // Generar barra de progreso
                let progressHtml = '<div class="progress" style="height:1rem;">';
                estados.forEach((label, idx) => {
                    const cnt = obj.estados[idx]?.total || 0;
                    const pct = total ? (cnt / total * 100) : 0;
                    if (cnt > 0) {
                        progressHtml += `
                      <div
                        class="progress-bar"
                        role="progressbar"
                        style="width:${pct}%; background-color:${colores[idx]};"
                        title="${label}: ${cnt} (${pct.toFixed(1)}%)"
                      ></div>`;
                    }
                });
                progressHtml += '</div>';

                // Fila HTML
                tbody += `
                <tr>
                    <td>
                        <span class="badge bg-blue text-blue-fg">${obj.siglas || dep}</span><br>
                        <small>${obj.denominacion}</small>
                    </td>
                    <td>${progressHtml}</td>
                    <td>${total}</td>
                    <td>${totalHoy}</td>
                    <td>${totalSemana}</td>
                    <td>
                      <button
                        class="btn btn-outline-primary btn-sm"
                        onclick="abrirResumenDependencia('${dep}')"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-search me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" /></svg>Ver
                      </button>
                    </td>
                </tr>`;
            });

            document.getElementById("tablaOrdenesDependencia").innerHTML = tbody;
        });
}


// ========== MODAL DE RESUMEN DE DEPENDENCIA ==========

function abrirResumenDependencia(depe_id) {
    let fechaIni = document.getElementById('fechaIni').value;
    let fechaFin = document.getElementById('fechaFin').value;
    const estadosParams = getEstadosParams();

    fetch('../../controller/dashboard.php?op=resumen_dependencia', {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `depe_id=${depe_id}&fecha_ini=${fechaIni}&fecha_fin=${fechaFin}` + (estadosParams ? "&" + estadosParams : "")
    })
        .then(r => r.json())
        .then(data => {
            // Render HTML para los gráficos y la tabla de procedimientos
            let html = `
        <div class="row">
            <div class="col-md-12 mb-3">
                <div id="graficoOrdenesDiarias" style="height: 220px;"></div>
            </div>
            <div class="col-md-12 mb-3">
                <div id="graficoRecaudacionProcTasa" style="height: 220px;"></div>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-6 mb-3">
                <b>Usuarios - Monto Total (S/)</b>
                <div id="graficoUsuariosMontos" style="height: 260px;"></div>
                <div id="tablaUsuariosMontos" class="mt-2 small"></div>
            </div>
            <div class="col-md-6 mb-3">
                <b>Usuarios - Cantidad de Órdenes</b>
                <div id="graficoUsuariosCantidades" style="height: 260px;"></div>
                <div id="tablaUsuariosCantidades" class="mt-2 small"></div>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-12">
                <b>Resumen por procedimiento</b>
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Nombre del Procedimiento</th>
                                <th>Código</th>
                                <th>Cantidad de Órdenes</th>
                                <th>Monto Total S/</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${(data.resumen_procedimientos || []).map(r => `
                                <tr>
                                    <td>${r.proced_nom}</td>
                                    <td>${r.proced_cod}</td>
                                    <td>${r.cantidad}</td>
                                    <td>S/ ${parseFloat(r.total).toFixed(2)}</td>
                                </tr>
                            `).join("")}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        `;

            document.getElementById("contenidoModalResumen").innerHTML = html;

            // 1. Gráfico Órdenes de giro diarias (línea)
            let fechas = data.ordenes_diarias.map(d => d.fecha);
            let cantidades = data.ordenes_diarias.map(d => parseInt(d.cantidad));
            let chartOrdenes = new ApexCharts(document.querySelector("#graficoOrdenesDiarias"), {
                chart: { type: 'line', height: 280 },
                series: [{ name: "Órdenes diarias", data: cantidades }],
                xaxis: { categories: fechas, title: { text: "Fecha" } },
                yaxis: { title: { text: "Órdenes" } },
                dataLabels: { enabled: true }
            });
            chartOrdenes.render();

            // 2. Recaudación por Procedimiento y Tasa (barras agrupadas)
            let procedimientos = [...new Set(data.recaudacion.map(r => r.proced_nom))];
            let tasas = [...new Set(data.recaudacion.map(r => r.tasa_nom))];
            let seriesProcTasa = tasas.map(tasaNom => {
                return {
                    name: tasaNom,
                    data: procedimientos.map(proc => {
                        let obj = data.recaudacion.find(r => r.proced_nom === proc && r.tasa_nom === tasaNom);
                        return obj ? parseFloat(obj.total) : 0;
                    })
                }
            });
            let chartRecaudacion = new ApexCharts(document.querySelector("#graficoRecaudacionProcTasa"), {
                chart: { type: 'bar', stacked: true, height: 280 },
                series: seriesProcTasa,
                xaxis: {
                    categories: procedimientos.map((_, i) => i + 1), // Oculta etiquetas
                    labels: { show: false }
                },
                yaxis: { title: { text: "Total S/" } },
                legend: { position: 'bottom' },
                dataLabels: { enabled: false },
                plotOptions: {
                    bar: { minHeight: 10 }
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return "S/ " + val.toFixed(2);
                        }
                    },
                    x: {
                        formatter: function (val, opts) {
                            return procedimientos[opts.dataPointIndex];
                        }
                    }
                }
            });
            chartRecaudacion.render();

            let usuarios = data.usuarios.map(u => u.usuario);
            let totalesMonto = data.usuarios.map(u => parseFloat(u.total));
            let totalesCant = data.usuarios.map(u => parseInt(u.cantidad));
            let sumaMonto = totalesMonto.reduce((a, b) => a + b, 0);
            let sumaCant = totalesCant.reduce((a, b) => a + b, 0);

            // Monto por usuario
            let chartUsuariosMontos = new ApexCharts(document.querySelector("#graficoUsuariosMontos"), {
                chart: { type: 'bar', height: 210 },
                series: [{ name: "Total S/", data: totalesMonto }],
                xaxis: { categories: usuarios, title: { text: "Usuario" }, labels: { rotate: -45 } },
                yaxis: { title: { text: "Total S/" } },
                plotOptions: { bar: { horizontal: true } },
                dataLabels: {
                    enabled: true,
                    formatter: function (val, opts) {
                        if (!sumaMonto) return "0%";
                        let perc = (val / sumaMonto * 100).toFixed(1);
                        return `S/ ${val.toFixed(2)} (${perc}%)`;
                    }
                }
            });
            chartUsuariosMontos.render();
            // Cantidad por usuario (DONA)
            let chartUsuariosCantidades = new ApexCharts(document.querySelector("#graficoUsuariosCantidades"), {
                chart: { type: 'donut', height: 250 },
                series: totalesCant,
                labels: usuarios,
                legend: { position: 'bottom' },
                dataLabels: {
                    enabled: true,
                    formatter: function (val, opts) {
                        // val = % del segmento
                        let value = totalesCant[opts.seriesIndex];
                        return `${value} (${val.toFixed(1)}%)`;
                    }
                },
                tooltip: {
                    y: {
                        formatter: function (val, opts) {
                            let value = totalesCant[opts.seriesIndex];
                            let perc = sumaCant ? (value / sumaCant * 100).toFixed(1) : 0;
                            return `${value} órdenes (${perc}%)`;
                        }
                    }
                }
            });
            chartUsuariosCantidades.render();



            let modal = new bootstrap.Modal(document.getElementById("modalResumenDependencia"));
            modal.show();
        });
}
function cargarGraficoErroresUD() {
    fetch('../../controller/dashboard.php?op=errores_por_usuario_dependencia', {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `fecha_ini=${fechaIni}&fecha_fin=${fechaFin}`
    })
        .then(r => r.json())
        .then(data => {
            // 1. Obtener lista de usuarios y dependencias
            const usuarios = Array.from(new Set(data.map(e => e.usuario)));
            const dependencias = Array.from(new Set(data.map(e => e.dependencia)));

            // 2. Generar abreviaturas (iniciales)
            const abreviaturas = usuarios.map(u =>
                u
                    .split(' ')
                    .map(palabra => palabra[0]?.toUpperCase() || '')
                    .join('')
            );

            // 3. Series como antes
            const series = dependencias.map(dep => ({
                name: dep,
                data: usuarios.map(u => {
                    const rec = data.find(e => e.usuario === u && e.dependencia === dep);
                    return rec ? parseInt(rec.errores, 10) : 0;
                })
            }));

            // 4. Opciones del gráfico
            const options = {
                chart: { type: 'bar', stacked: true, height: 350 },
                plotOptions: { bar: { columnWidth: '50%' } },
                series,
                xaxis: {
                    // mostramos solo las iniciales
                    categories: abreviaturas,
                    labels: {
                        // opcional: rotar o ajustar estilo
                        rotate: -45,
                        style: { fontSize: '12px' }
                    }
                },
                tooltip: {
                    // tooltip de X devuelve el nombre completo según dataPointIndex
                    x: {
                        formatter: (_, { dataPointIndex }) => usuarios[dataPointIndex]
                    }
                },
                legend: { position: 'bottom' },
                dataLabels: { enabled: false }
            };

            // 5. Render o update
            if (window.chartErroresUD?.updateOptions) {
                window.chartErroresUD.updateOptions(options);
                window.chartErroresUD.updateSeries(series);
            } else {
                window.chartErroresUD = new ApexCharts(
                    document.querySelector("#chartErroresUD"),
                    options
                );
                window.chartErroresUD.render();
            }
        });
}



// ========== EVENTOS Y AUTOLOAD ==========

document.addEventListener('DOMContentLoaded', function () {
    setDefaultMonthDates();
    fetchDashboardData();
    cargarTablaOrdenesDependencia();
    cargarGraficoErroresUD();
});

document.getElementById('filtrarBtn').addEventListener('click', function () {
    fetchDashboardData();
    cargarTablaOrdenesDependencia();
    cargarGraficoErroresUD();
});

window.onload = function () {
    fetchDashboardData();
    cargarTablaOrdenesDependencia();
    cargarGraficoErroresUD();
};
