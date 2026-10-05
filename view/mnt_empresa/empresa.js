// js/empresa.js

let tabla_empresa;

function listar_todos() {
    if ($.fn.DataTable.isDataTable("#tabla-empresa")) {
        $("#tabla-empresa").DataTable().destroy();
    }
    tabla_empresa = $("#tabla-empresa").DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        ajax: {
            url: "../../controller/empresa.php?op=listar_tabla",
            type: "POST",
            data: function (d) {
                d.search       = $("#search").val().trim();
                d.order_column = d.order && d.order[0] ? d.order[0].column : 0;
                d.order_dir    = d.order && d.order[0] ? d.order[0].dir : "desc";
            }
        },
        columns: [
            { data: 0 },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5, orderable: false },
            { data: 6, orderable: false }
        ],
        order: [[0, "desc"]],
        pageLength: 10,
        language: {
            sProcessing: "Procesando...",
            sLengthMenu: "Mostrar _MENU_ registros",
            sZeroRecords: "No se encontraron empresas",
            sEmptyTable: "Ningún dato disponible en la tabla",
            sInfo: "Mostrando _START_ al _END_ de un total de _TOTAL_ empresas",
            sInfoEmpty: "Mostrando 0 al 0 de un total de 0 registros",
            sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
            sSearch: "Buscar:",
            oPaginate: {
                sFirst: "Primero",
                sLast: "Último",
                sNext: "Siguiente",
                sPrevious: "Anterior"
            }
        },
        autoWidth: false,
        columnDefs: [
            { className: "text-center align-middle text-nowrap", targets: [0, 1, 5, 6] },
            { className: "align-middle", targets: [2, 3] },
            { className: "align-middle text-wrap", targets: [4] }
        ]
    });
}

$(document).ready(function () {
    listar_todos();

    $("#search").on("input", function () {
        clearTimeout($.data(this, "timer"));
        let wait = setTimeout(listar_todos, 350);
        $(this).data("timer", wait);
    });

    $('#empr_ruc').on('input', function () {
        const valor = $(this).val().replace(/\D/g, '').slice(0, 11);
        $(this).val(valor);

        if (valor.length > 0 && valor.length < 11) {
            $("#ruc_status").html('<span class="text-muted small">Faltan ' + (11 - valor.length) + ' dígitos</span>');
        } else if (valor.length === 11 && !$("#empr_id").val()) {
            consultarRucSunat();
        } else {
            $("#ruc_status").text("");
        }
    });

    $(document).on("keydown", "#empresaForm input", function (e) {
        if (e.key === "Enter" || e.keyCode === 13) {
            e.preventDefault();
            const inputId = $(this).attr("id");

            if (inputId === "empr_ruc") {
                if ($(this).val().trim().length === 11 && !$("#empr_id").val()) {
                    consultarRucSunat();
                } else {
                    $("#empr_razon_social").focus().select();
                }
                return;
            }

            if (inputId === "empr_razon_social") {
                $("#empr_nombre_comercial").focus().select();
                return;
            }

            if (inputId === "empr_nombre_comercial") {
                $("#empr_direccion").focus().select();
                return;
            }

            if (inputId === "empr_direccion") {
                return;
            }
        }
    });

    $(document).on("keydown", "#btnGuardarEmpresa", function (e) {
        if (e.key === "Enter" || e.keyCode === 13) {
            e.preventDefault();
            return false;
        }
    });
});

function consultarRucSunat() {
    const ruc = $('#empr_ruc').val().trim();
    if (ruc.length !== 11) return;

    $("#ruc_status").html('<span class="spinner-border spinner-border-sm text-primary" role="status"></span> <span class="text-primary small">Consultando SUNAT...</span>');

    $.post("../../controller/empresa.php?op=consultar_sunat_v2", { empr_ruc: ruc }, function (data) {
        $("#ruc_status").text("");
        try {
            const response = JSON.parse(data);
            if (response.raz_social) {
                $("#empr_razon_social").val(response.raz_social);
                $("#empr_nombre_comercial").val(response.nom_comercial || "");
                $("#empr_direccion").val(response.domicilio || "");
                $("#ruc_status").html('<span class="text-success small fw-semibold">✓ SUNAT Sincronizado</span>');
                $("#empr_razon_social").focus().select();
                return;
            }
        } catch (e) {
            console.error("Respuesta SUNAT no parseable:", data);
        }
        $("#ruc_status").html('<span class="text-muted small">No registrado en SUNAT</span>');
        $("#empr_razon_social").focus().select();
    }).fail(function () {
        $("#ruc_status").text("");
        $("#empr_razon_social").focus().select();
    });
}

function nuevoRegistro() {
    $("#empr_id").val("");
    $("#empresaForm")[0].reset();
    $("#ruc_status").text("");
    $("#modal-title").text("Registrar Empresa");
    $("#empresaModal").modal("show");
}

function editar(id) {
    $.post(
        "../../controller/empresa.php?op=mostrar",
        { empr_id: id },
        function (data) {
            const d = JSON.parse(data);
            $("#empr_id").val(d.empr_id);
            $("#empr_ruc").val(d.empr_ruc);
            $("#empr_razon_social").val(d.empr_razon_social);
            $("#empr_nombre_comercial").val(d.empr_nombre_comercial);
            $("#empr_direccion").val(d.empr_direccion);
            $("#ruc_status").text("");
            $("#modal-title").text("Editar Empresa");
            $("#empresaModal").modal("show");
        }
    );
}

function guardar() {
    const $form = $("#empresaForm");
    if (!$form[0].checkValidity()) {
        $form[0].reportValidity();
        return;
    }

    const $btn = $("#btnGuardarEmpresa");
    const op = $("#empr_id").val() ? "editar" : "crear";
    const formArray = $form.serializeArray();
    const data = {};
    formArray.forEach(({ name, value }) => { data[name] = value; });

    $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Guardando...');

    $.post(`../../controller/empresa.php?op=${op}`, data)
        .done(res => {
            const r = JSON.parse(res);
            if (r.success) {
                $("#empresaModal").modal("hide");
                listar_todos();
                Swal.fire({
                    title: 'Correcto',
                    text: r.message || 'La empresa fue guardada con éxito.',
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                });
            } else {
                Swal.fire({
                    title: 'Atención',
                    text: r.message || 'No se pudo guardar el registro.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            }
        })
        .fail(() => {
            Swal.fire({
                title: 'Error de Red',
                text: 'No se pudo conectar al servidor.',
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
        })
        .always(() => {
            $btn.prop("disabled", false).html(`
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M5 12l5 5l10 -10" />
                </svg> Guardar Empresa
            `);
        });
}

function cambiarEstado(id, estado) {
    const nuevo = estado === "A" ? "I" : "A";
    const accion = nuevo === "A" ? "activar" : "inactivar";
    const confirmColor = nuevo === "A" ? "#2fb344" : "#f59f00";

    Swal.fire({
        title: `¿Desea ${accion} esta empresa?`,
        text: nuevo === "A"
            ? "La empresa podrá ser asignada a nuevas órdenes de giro y trámites."
            : "La empresa quedará marcada como inactiva temporalmente.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: `Sí, ${accion}`,
        cancelButtonText: 'Cancelar',
        confirmButtonColor: confirmColor
    }).then(result => {
        if (result.isConfirmed) {
            $.post(
                "../../controller/empresa.php?op=cambiar_estado",
                { empr_id: id, estado: estado }
            )
            .done(() => {
                listar_todos();
                Swal.fire({
                    title: 'Completado',
                    text: `Empresa ${accion}da correctamente.`,
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                });
            })
            .fail(() => {
                Swal.fire({
                    title: 'Error',
                    text: 'No se pudo conectar al servidor.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            });
        }
    });
}
