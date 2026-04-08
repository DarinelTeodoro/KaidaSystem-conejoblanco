function show_alert(status, message, visible = true) {
    let visibility = visible ? 'block' : 'none';
    // Mostrar la alerta
    document.getElementById('container_main_alert').classList.add('visible');
    // Mostrar/ocultar botón según el parámetro visible
    let btnContainer = document.getElementById('container-btn-acept');
    if (btnContainer) {
        btnContainer.style.display = visibility;
    }
    // Actualizar contenido
    $('#text_message_alert').html(message);
    $('#text_title_alert').html(status);
}

function hide_alert() {
    document.getElementById('container_main_alert').classList.remove('visible');
    /*document.getElementById('body-inicio').style.overflow = 'auto';*/
}


$('#item-recordatorio').submit(function (event) {
    event.preventDefault();

    var formData = new FormData(this);

    $.ajax({
        type: "post",
        url: "../../controller/administrador.php",
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'html',
        success: function (response) {
            document.getElementById('item-recordatorio').classList.remove('visible');
        }
    });
});