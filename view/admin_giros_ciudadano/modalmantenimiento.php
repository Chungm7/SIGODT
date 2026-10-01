<div id="modalmantenimiento" class="modal fade" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content bd-0">
            <div class="modal-header pd-y-20 pd-x-25">
                <h6 id="lbltitulo" class="tx-12 mg-b-0 tx-uppercase tx-inverse tx-bold">Nuevo Registro</h6>
            </div>

            <!-- Formulario Mantenimiento -->
            <form method="post" id="ciudadanosdetalles_form">
                <div class="modal-body">
                    <input type="hidden" name="procedciudadano_id" id="procedciudadano_id" />
                    <input type="hidden" name="ciud_id" id="ciud_id" />
                    <input type="hidden" name="empr_id" id="empr_id" />
                    <input type="hidden" name="tipo" id="tipo" />
                    <input type="hidden" name="tiv_id" id="tiv_id" />
                    <input type="hidden" name="menor_edad" id="menor_edad" />
                    <input type="hidden" name="esCarnet" id="esCarnet" />
                    <input type="hidden" name="esCPP" id="esCPP" />
                    <div class="form-group">
                        <div id="accordion" class="accordion" role="tablist" aria-multiselectable="true">
                            <div class="card seccion-1">
                                <div class="card-header d-flex justify-content-between" role="tab" id="headingOne">
                                    <h6 class="mg-b-0" style="width: 100%;">
                                        <a data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne" class="tx-gray-800 transition">
                                            DATOS CIUDADANO
                                            <span class="estado-icono" style="width: 20%; ">
                                                <i class="fa fa-check" style="color:green;"></i>
                                            </span>
                                            <span class="estado-icono" style="width: 20%; ">
                                                <i class="fa fa-close" style="color:red;"></i>
                                            </span>
                                        </a>

                                    </h6>
                                </div><!-- card-header -->
                                <div id="collapseOne" class="collapse show" role="tabpanel" aria-labelledby="headingOne">
                                    <div class="card-block pd-20">
                                        <div style="background-color: #f3f6f8; padding: 10px; border: 1px solid #ccc; border-radius: 10px;">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <label>Tipo de Documento:</label>
                                                    <div class="checkbox-container" style="padding-left: 20px;">
                                                        <div class="checkbox-wrapper-46">
                                                            <input type="checkbox" id="dni_checkbox" class="inp-cbx" name="tipo_documento" value="DNI" onchange="toggleCheckboxes('dni_checkbox')">
                                                            <label for="dni_checkbox" class="cbx">
                                                                <span><svg viewBox="0 0 12 10" height="10px" width="12px">
                                                                        <polyline points="1.5 6 4.5 9 10.5 1"></polyline>
                                                                    </svg></span>
                                                                <span>DNI</span>
                                                            </label>
                                                        </div>
                                                        <div class="checkbox-wrapper-46">
                                                            <input type="checkbox" id="ce_checkbox" class="inp-cbx" name="tipo_documento" value="Carnet de Extranjería" onchange="toggleCheckboxes('ce_checkbox')">
                                                            <label for="ce_checkbox" class="cbx">
                                                                <span><svg viewBox="0 0 12 10" height="10px" width="12px">
                                                                        <polyline points="1.5 6 4.5 9 10.5 1"></polyline>
                                                                    </svg></span>
                                                                <span>CEE</span>
                                                            </label>
                                                        </div>
                                                        <div class="checkbox-wrapper-46">
                                                            <input type="checkbox" id="cpp_checkbox" class="inp-cbx" name="tipo_documento" value="Carne CPP" onchange="toggleCheckboxes('cpp_checkbox')">
                                                            <label for="cpp_checkbox" class="cbx">
                                                                <span><svg viewBox="0 0 12 10" height="10px" width="12px">
                                                                        <polyline points="1.5 6 4.5 9 10.5 1"></polyline>
                                                                    </svg></span>
                                                                <span>Carné CPP</span>
                                                            </label>
                                                        </div>
                                                        <div class="checkbox-wrapper-46">
                                                            <input type="checkbox" id="ruc_checkbox" class="inp-cbx" name="tipo_documento" value="RUC" onchange="ruc_chek('ruc_checkbox')">
                                                            <label for="ruc_checkbox" class="cbx">
                                                                <span><svg viewBox="0 0 12 10" height="10px" width="12px">
                                                                        <polyline points="1.5 6 4.5 9 10.5 1"></polyline>
                                                                    </svg></span>
                                                                <span>RUC</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4 d-flex justify-content-center align-items-center" style="right: 50px;">
                                                    <!-- Aquí puedes poner tu imagen -->
                                                    <img id="imagen_ciudadano" src="../../public/img/perfil.jpeg" alt="Imagen" style="max-width: 150px; max-height: 150px;">
                                                </div>

                                                <div class="col-md-12">
                                                    <label>Número de Documento: <span class="tx-danger">*</span></label>
                                                    <div class="input-group">
                                                        <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="8" name="ciudadano_doc" id="ciudadano_doc" class="form-control" placeholder="Ingresa el DNI (8 dígitos)" oninput="limitabuscadni(this)" required>
                                                        <button class="btn btn-outline-secondary" type="button" id="btn_buscar_doc" onclick="ejecutarBusquedaDocAdmin()" title="Buscar documento">
                                                            <i class="fas fa-search"></i>
                                                        </button>
                                                    </div>
                                                    <div>
                                                        <label id="ciud_mensaje" style="display: none; color: green; width: 100%; ">Correcto!</label>
                                                    </div>


                                                    <label class="form-control-label" for="ciudadano_nombre">Nombre: <span class="tx-danger">*</span></label>


                                                    <input class="form-control tx-uppercase" style="margin-bottom: 10px" id="ciudadano_nombre" type="text" name="ciudadano_nombre" readonly required />
                                                    <div class="row" style="margin-top: 10px">
                                                        <div class="col-md-6" style="margin-bottom: 10px">
                                                            <label class="form-control-label" for="ciudadano_apep">Apellido Paterno: <span class="tx-danger">*</span></label>
                                                            <input class="form-control tx-uppercase" id="ciudadano_apep" type="text" name="ciudadano_apep" readonly required />
                                                        </div>
                                                        <div class="col-md-6" style="margin-bottom: 10px">
                                                            <label class="form-control-label" for="ciudadano_apem">Apellido Materno: <span class="tx-danger">*</span></label>
                                                            <input class="form-control tx-uppercase" id="ciudadano_apem" type="text" name="ciudadano_apem" readonly required />
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                        </div><!-- card-block -->
                                    </div><!-- collapseOne -->
                                </div><!-- card -->
                                <style>
                                    .disabled-link {
                                        pointer-events: none;
                                        color: gray;
                                    }
                                </style>
                                <div class="card seccion-2" id="section_empresa">
                                    <div class="card-header" role="tab" id="headingTwo">
                                        <h6 class="mg-b-0">
                                            <a id="accordionLink" class="collapsed tx-gray-800 transition" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                                DATOS DE EMPRESA
                                                <span class="estado-icono-empr" style="width: 20%;">
                                                    <i class="fa fa-check" style="color:green;"></i>
                                                </span>
                                                <span class="estado-icono-empr" style="width: 20%;">
                                                    <i class="fa fa-close" style="color:red;"></i>
                                                </span>
                                            </a>
                                        </h6>
                                    </div>
                                    <div id="collapseTwo" class="collapse" role="tabpanel" aria-labelledby="headingTwo">
                                        <div class="card-block pd-20">
                                            <div style="background-color: #f3f5ff; padding: 10px; border: 1px solid #ccc; border-radius: 10px;">
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <label>Número de RUC: <span class="tx-danger">*</span></label>
                                                        <input type="text" name="empr_ruc" id="empr_ruc" class="form-control" placeholder="Ingresa el RUC" oninput="limitarbuscarruc(this)">
                                                        <label id="mensaje_empresa" style="display: none; color: green;">Correcto!</label>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-control-label" for="empr_razon_social">Razon Social: <span class="tx-danger">*</span></label>
                                                        <input class="form-control tx-uppercase" id="empr_razon_social" type="text" name="empr_razon_social" readonly />
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-control-label" for="empr_nombre_comercial">Nombre Comercial: <span class="tx-danger">*</span></label>
                                                        <input class="form-control tx-uppercase" id="empr_nombre_comercial" type="text" name="empr_nombre_comercial" readonly />
                                                    </div>
                                                </div>
                                            </div>
                                        </div><!-- card-block -->
                                    </div><!-- collapse -->
                                </div><!-- card -->
                            </div><!-- accordion -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="action" value="add" id="btnguardar" class="btn btn-outline-primary tx-11 tx-uppercase pd-y-12 pd-x-25 tx-mont tx-medium"><i class="fa fa-check"></i> Guardar</button>
                        <button type="reset" class="btn btn-outline-secondary tx-11 tx-uppercase pd-y-12 pd-x-25 tx-mont tx-medium" aria-label="Close" aria-hidden="true" data-dismiss="modal"><i class="fa fa-close"></i> Cancelar</button>
                    </div>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleCheckboxes(checkboxId) {
        const checkboxes = document.querySelectorAll('input[type="checkbox"][name="tipo_documento"]');
        checkboxes.forEach(checkbox => {
            if (checkbox.id !== checkboxId && checkbox.id !== 'ruc_checkbox') {
                checkbox.checked = false;
            }
        });

        const docInput = document.getElementById('ciudadano_doc');
        const mensaje = document.getElementById('ciud_mensaje');
        if (docInput) {
            if (checkboxId === 'dni_checkbox') {
                docInput.setAttribute('maxlength', '8');
                docInput.setAttribute('inputmode', 'numeric');
                docInput.setAttribute('pattern', '[0-9]*');
                docInput.setAttribute('placeholder', 'Ingresa el DNI (8 dígitos)');
            } else {
                docInput.setAttribute('maxlength', '15');
                docInput.setAttribute('inputmode', 'text');
                docInput.removeAttribute('pattern');
                docInput.setAttribute('placeholder', checkboxId === 'ce_checkbox' ? 'Ingresa Carné de Extranjería' : 'Ingresa Carné CPP');
            }
            if (mensaje) mensaje.style.display = 'none';
            docInput.focus();
            if (typeof limitabuscadni === 'function' && docInput.value) {
                limitabuscadni(docInput);
            }
        }
    }

    function ruc_chek(checkboxId) {
        const checkbox = document.getElementById(checkboxId);

        const accordionLink = document.getElementById('accordionLink');

        if (checkbox.checked) {
            accordionLink.classList.remove('disabled-link');
        } else {
            accordionLink.classList.add('disabled-link');

        }
    }


    function limitarADigitosDocumento(input) {
        let valor = input.value.toString().replace(/\D/g, ''); // Remover caracteres no numéricos

        const max_length = 11; // Por defecto, límite de 11 dígitos para RUC

        if (valor.length > max_length) {
            valor = valor.slice(0, max_length); // Truncar el valor si excede el límite
        }
        input.value = valor;
    }

    function limitarADigitosDNI(input) {
        let valor = input.value.toString().replace(/\D/g, ''); // Remover caracteres no numéricos
        const tipo_documento = (document.querySelector('input[name="tipo_documento"]:checked')?.value || 'DNI');
        let max_length = 8; // Por defecto, límite de 8 dígitos para DNI

        if (tipo_documento === "Carnet de Extranjería" || tipo_documento === "Carne CPP") {
            max_length = 15;
        }

        if (valor.length > max_length) {
            valor = valor.slice(0, max_length); // Truncar el valor si excede el límite
        }
        input.value = valor;
    }
</script>
<script>
    function limitabuscadni(input) {
        const checkedEl = document.querySelector('input[name="tipo_documento"]:checked');
        const tipo_documento = checkedEl ? checkedEl.value : 'DNI';

        if (tipo_documento === "DNI") {
            let valor = input.value.toString().replace(/\D/g, '');
            const max_length = 8;

            if (valor.length > max_length) {
                valor = valor.slice(0, max_length);
            }

            if (valor.length === max_length) {
                resetearCampos();
                validarCheckCiud();
                buscarDNI(valor);
                $("#spinner-ciud").remove();
            } else if (valor.length > 0) {
                resetearCampos();
                validarCheckCiud();
                $("input[name='ciud_sex']").prop("disabled", true);
                $("#dateMask").removeAttr("readonly");
                $("#ciud_sex").prop("disabled", false);

                var faltantes = max_length - valor.length;
                $("#spinner-ciud").remove();
                $("#ciud_mensaje").text("Ingrese 8 dígitos (faltan " + faltantes + ")").css("color", "#0054a6").show();
            } else {
                resetearCampos();
                validarCheckCiud();
                $("#spinner-ciud").remove();
                $("#ciud_mensaje").hide();
            }
            input.value = valor;
        } else {
            // Documentos extranjeros (Carnet de Extranjería, CPP)
            let valor = input.value.toString().replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
            const max_length = 15;

            if (valor.length > max_length) {
                valor = valor.slice(0, max_length);
            }

            if (valor.length > 0) {
                $("#spinner-ciud").remove();
                $("#ciud_mensaje").text("Presione Enter o la lupa para buscar").css("color", "#0054a6").show();
            } else {
                resetearCampos();
                validarCheckCiud();
                $("#spinner-ciud").remove();
                $("#ciud_mensaje").hide();
            }
            input.value = valor;
        }
    }

    function ejecutarBusquedaDocAdmin() {
        const input = document.getElementById("ciudadano_doc");
        if (!input) return;
        const checkedEl = document.querySelector('input[name="tipo_documento"]:checked');
        const tipo_documento = checkedEl ? checkedEl.value : 'DNI';
        const valor = input.value.trim();

        if (!valor) {
            input.focus();
            return;
        }

        if (tipo_documento === "DNI") {
            if (valor.length !== 8) {
                $("#ciud_mensaje").text("El DNI debe tener 8 dígitos (actual: " + valor.length + ")").css("color", "#d63939").show();
                input.focus();
                return;
            }
        } else {
            if (valor.length < 3) {
                $("#ciud_mensaje").text("Ingrese un número de documento válido").css("color", "#d63939").show();
                input.focus();
                return;
            }
        }

        resetearCampos();
        validarCheckCiud();
        buscarDNI(valor);
    }


    function resetearCampos() {
        $("#ciudadano_nombre").val('');
        $("#ciudadano_apep").val('');
        $("#ciudadano_apem").val('');
        $("#ciud_id").val('');
        $("#imagen_ciudadano").attr("src", '../../public/img/perfil.jpeg');
    }


    function limitarbuscarruc(input) {

        let valor = input.value.toString().replace(/\D/g, '');
        if (valor.length > 11) {
            valor = valor.slice(0, 11);
        } else if (valor.length == 11) {
            $("#empr_razon_social").val('');
            $("#empr_nombre_comercial").val('');
            $("#empr_id").val('');
            buscaRUC();
            $("#spinner").remove();
        }

        if (valor.length > 0 && valor.length < 11) {
            $("#empr_razon_social").val('');
            $("#empr_nombre_comercial").val('');
            $("#empr_id").val('');
            $("#spinner").remove();
            var faltantesRuc = 11 - valor.length;
            $("#mensaje_empresa").text("Ingrese 11 dígitos (faltan " + faltantesRuc + ")").css("color", "#0054a6").show();
        }
        if (valor.length == 0) {
            $("#spinner").remove();
            $("#mensaje_empresa").hide();
        }
        input.value = valor;
    }

    function limitartel(input) {
        let valor = input.value.toString().replace(/\D/g, '');
        if (valor.length > 9) {
            valor = valor.slice(0, 9);
        }
        input.value = valor;
    }
</script>
<script>
    function limitarADigitosDocumento(input) {
        let valor = input.value.toString().replace(/\D/g, ''); // Remover caracteres no numéricos

        var max_length = 11; // Por defecto, límite de 8 dígitos para DNI


        if (valor.length > max_length) {
            valor = valor.slice(0, max_length); // Truncar el valor si excede el límite
        }
        input.value = valor;
    }

    function limitarADigitosDNI(input) {
        let valor = input.value.toString().replace(/\D/g, ''); // Remover caracteres no numéricos
        var tipo_documento = $("input[name='tipo_documento']:checked").val();
        var max_length = 8; // Por defecto, límite de 8 dígitos para DNI

        if (tipo_documento === "Carnet de Extranjería") {
            max_length = 12; // Cambiar el límite a 12 dígitos para Carnet de Extranjería

        }

        if (tipo_documento === "Carne CPP") {
            max_length = 20; // Cambiar el límite a 12 dígitos para Carnet de Extranjería

        }

        if (valor.length > max_length) {
            valor = valor.slice(0, max_length); // Truncar el valor si excede el límite
        }
        input.value = valor;
    }
</script>