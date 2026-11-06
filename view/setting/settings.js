
$(document).ready(function () {
    cargarDatos();
    $('#cambiar-tlf').on('click', function(e) {
        e.preventDefault();
        if (!/^[0-9]{9}$/.test($('#telefono').val())) {
            mostrarModal('error', 'Actualización de datos', 'El número de teléfono no es válido');
            return;
        }
        guardarContacto(this, 'update_telefono', $('#telefono').val());
    });
    $('#cambiar-emailp').on('click', function(e) {
        e.preventDefault();
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test($('#emailp').val())) {
            mostrarModal('error', 'Actualización de datos', 'El correo electrónico no es válido');
            return;
        }
        guardarContacto(this, 'update_emailp', $('#emailp').val());
    });
});

// The same SIINDI operations, now posted directly with server-owned identity.
function cargarDatos() {
    $.post(window.SETTINGS_BASE_URL + 'controller/usuario.php?op=mostrar_datos', {
        csrf_token: window.SETTINGS_CSRF_TOKEN
    }, function(resp) {
        if (!resp.success) {
            mostrarModal('error', 'Error', resp.message || 'Se produjo un error al cargar la información');
            return;
        }
        const data = resp.data || {};
        $('#telefono').val(data.usu_celu01 || '');
        $('#emaili').val(data.usu_emaili || '');
        $('#emailp').val(data.usu_emailp || '');
        $('#txt-telefono').css('color', data.usu_celu01 ? '#047857' : '#DC4545')
            .text(data.usu_celu01 ? 'Su número de teléfono es válido: ' + data.usu_celu01 : 'No cuenta con número de teléfono registrado');
        $('#txt-emailp').css('color', data.usu_emailp ? '#047857' : '#DC4545')
            .text(data.usu_emailp ? 'Su correo electrónico personal es válido: ' + data.usu_emailp : 'No cuenta con correo electrónico personal registrado');
    }, 'json').fail(function() {
        mostrarModal('error', 'Error', 'Error de conexión con el servidor');
    });
}

function guardarContacto(button, operation, dato) {
    const btn = $(button);
    if (btn.prop('disabled')) return;
    btn.prop('disabled', true);
    $.post(window.SETTINGS_BASE_URL + 'controller/usuario.php?op=' + operation, {
        csrf_token: window.SETTINGS_CSRF_TOKEN, dato: dato
    }, function(resp) {
        mostrarModal(resp.success ? 'success' : 'error', 'Actualización de datos', resp.message || (resp.success ? 'Se actualizaron los datos correctamente' : 'No se pudo actualizar'));
        if (resp.success) cargarDatos();
    }, 'json').fail(function() {
        mostrarModal('error', 'Actualización de datos', 'Error de conexión con el servidor');
    }).always(function() { btn.prop('disabled', false); });
}

function mostrarModal(icon, title, message) {
    // Upstream strings are text, never HTML (same source feedback modal).
    Swal.fire({icon: icon, titleText: title, text: String(message), timer: 2000, showConfirmButton: false});
}

function limitarDigitosTlf(input) {
    input.value = input.value.toString().replace(/\D/g, '').slice(0, 9);
}

function verTip(){
    document.getElementById("modaldata").style.display = "block";
    $('#modaldata').html(
        '<div class="alert alert-info" role="alert" style="margin-top:-20px;margin-bottom:-5px;">'+
            '<div class="d-flex">'+
                '<div>'+
                    '<svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon alert-icon icon-2"><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>'+
                '</div>'+
                '<div>'+
                    '<h4 class="alert-title">Información</h4>'+
                    '<div class="text-secondary">Solicita al correo <b>gtie@munichiclayo.gob.pe</b> con el asunto: Reseteo de clave de correo Zimbra, en el contenido del correo indica tus apellidos y nombres, área donde labora actualmente, número de telefono celular y correo electrónico personal. Todos estos datos serán validados, y de ser confirmados se te atenderá en máximo 24 horas, de lo contrario se te indicará que no se pudo completar tu solicitud por haber brindado datos incorrectos.</div>'+
                '</div>'+
            '</div>'+
        '</div>');
}