$('#search-comanda-gestionar').submit(function (event) {
    event.preventDefault();

    $("#datos-comanda-gestionar").html('<div class="text-center fs-5">Cargando...</div>');
    var formData = new FormData(this);

    $.ajax({
        type: "post",
        url: "../../controller/comanda-gestionar.php",
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'html',
        success: function (response) {
            setTimeout(() => {
                $("#datos-comanda-gestionar").html(response);
            }, 300)
        }
    });
});


function auxiliaRecargar(id) {
    $("#datos-comanda-gestionar").html('<div class="text-center fs-5">Cargando...</div>');
    $.ajax({
        type: "post",
        url: "../../controller/comanda-gestionar.php",
        data: { comanda_gestionar: id },
        dataType: 'html',
        success: function (response) {
            setTimeout(() => {
                $("#datos-comanda-gestionar").html(response);
            }, 300)
        }
    });
}

//La funcion para cancelar comanda esta en gestion-comandas.php


function eliminar_item(destino, id_comanda, id_item) {
    Swal.fire({
        title: `¿Estás seguro de eliminar el producto? Esta acción no se puede deshacer.`,
        showDenyButton: false,
        showCancelButton: true,
        confirmButtonText: "Confirmar"
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                type: "post",
                url: "../../controller/comanda-eliminar-item.php",
                data: { destino: destino, id_item: id_item, id_comanda: id_comanda },
                dataType: 'html',
                success: function (response) {
                    auxiliaRecargar(response);
                }
            });
        }
    });
}