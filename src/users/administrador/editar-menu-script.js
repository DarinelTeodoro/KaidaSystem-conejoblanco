/* ----------------- MODULO EDITAR MENU ----------------- */

//ELEGIR SECCION
$('#container_data_secciones').load('../../controller/menu-categorias.php');

document.querySelectorAll('.btn_seccion').forEach(button => {
    button.addEventListener('click', function () {
        document.querySelectorAll('.btn_seccion').forEach(btn => btn.classList.remove('seccion_selected'));
        this.classList.add('seccion_selected');

        let seccion = this.dataset.seccion;

        $('#container_data_secciones').html('<div class="fs-5 p-3" align="center">Cargando...</div>');
        setTimeout(() => {
            $('#container_data_secciones').load('../../controller/' + seccion + '.php');
        }, 300)
    });
});

/*------------------CATEGORIAS-------------------*/
//AGREGAR CATEGORIA
document.addEventListener('click', e => {
    if (e.target.id === 'open_modal_addcategoria') {
        document.getElementById('modal_add_categoria')?.classList.add('visible');
    }
    if (e.target.id === 'close_modal_addcategoria') {
        document.getElementById('modal_add_categoria')?.classList.remove('visible');
    }
});

//EDITAR CATEGORIA
document.addEventListener('click', e => {
    if (e.target.id === 'close_modal_editcategoria') {
        document.getElementById('modal_edit_categoria')?.classList.remove('visible');
    }
});

function edit_categoria(idcat) {
    $.ajax({
        type: "post",
        url: "../../controller/menu-categorias-search.php",
        data: {
            id: idcat
        },
        dataType: 'json',
        success: function (response) {
            if (response.status === 'EXITO') {
                $('#id_editcategoria').val(response.idcat);
                $('#name_editcategoria').val(response.categoria);
                $('#descripcion_editcategoria').val(response.descripcion);
                $('#destino_editcategoria').val(response.destino);
            }
        }
    });

    document.getElementById('modal_edit_categoria').classList.add('visible');
}

//ELIMINAR CATEGORIA
function delete_categoria(idcat) {
    if (window.confirm("Deseas eliminar la categoria?")) {
        $.ajax({
            type: "post",
            url: "../../controller/menu-categorias-delete.php",
            data: {
                id: idcat
            },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'EXITO') {
                    show_alert(response.status, response.message);
                    $('#container_data_secciones').load('../../controller/menu-categorias.php');
                }
            }
        });
    }
}








/*------------------COMBOS-------------------*/
//AGREGAR COMBOS
document.addEventListener('click', e => {
    if (e.target.id === 'open_modal_addcombo') {
        document.getElementById('modal_add_combo')?.classList.add('visible');
    }
    if (e.target.id === 'close_modal_addcombo') {
        document.getElementById('modal_add_combo')?.classList.remove('visible');
    }
});


//EDITAR COMBO
function edit_combo(idcombo) {
    $.ajax({
        type: "post",
        url: "../../controller/menu-combos-search.php",
        data: {
            id: idcombo
        },
        dataType: 'json',
        success: function (response) {
            if (response.status === 'EXITO') {
                $('#id_editcombo').val(response.idcom);
                $('#name_editcombo').val(response.combo);
                $('#descripcion_editcombo').val(response.descripcion);
                $('#precio_editcombo').val(response.precio);
            }
        }
    });

    document.getElementById('modal_edit_combo').classList.add('visible');
}


document.addEventListener('click', e => {
    if (e.target.id === 'close_modal_editcombo') {
        document.getElementById('modal_edit_combo')?.classList.remove('visible');
    }
});


//ELIMINAR COMBO
function delete_combo(idcombo) {
    if (window.confirm("Deseas eliminar el combo?")) {
        $.ajax({
            type: "post",
            url: "../../controller/menu-combos-delete.php",
            data: {
                id_combo: idcombo
            },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'EXITO') {
                    show_alert(response.status, response.message);
                    $('#container_data_secciones').load('../../controller/menu-combos.php');
                }
            }
        });
    }
}

//CONFIGURAR COMBO
document.addEventListener('click', e => {
    if (e.target.id === 'close_modal_configcombo') {
        document.getElementById('modal_config_combo')?.classList.remove('visible');
    }
});

function config_combo(id_combo) {
    //const botones = document.querySelectorAll(".btn_seccion_combo");
    const botonOffCanvas = document.getElementById("btn_seccion_combo");

    //botones.forEach(boton => {
    botonOffCanvas.setAttribute("onclick", "open_offcanvas_productos(" + id_combo + ")");
    //});

    $('#cont_products_combo').html('<div class="text-center">Cargando Productos...</div>');

    $.ajax({
        type: "post",
        url: "../../controller/menu-combos-search.php",
        data: {
            id: id_combo
        },
        dataType: 'json',
        success: function (response) {
            if (response.status === 'EXITO') {
                $('#offcanvas_title_combo').html(response.combo);
                document.getElementById('modal_config_combo').classList.add('visible');

                $.ajax({
                    type: "post",
                    url: "../../controller/menu-combos-products.php",
                    data: {id: id_combo},
                    success: function (response) {
                        $("#cont_products_combo").html(response);
                    }
                });

            }
        }
    });
}

function open_offcanvas_productos(id_combo) {
    let select_products = new bootstrap.Offcanvas(document.getElementById("offcanvas-productos"));
    select_products.toggle();

    $.ajax({
        type: "post",
        url: "../../controller/menu-combos-checkbox.php",
        data: {},
        success: function (response) {
            $('#id_offcanvascombo').val(id_combo)
            $("#cont_list_combo").html(response);
        }
    });
}

document.addEventListener('change', (e) => {
    if (e.target?.id === 'offcanvas-tiposeccion') {

        const valueSeleccionado = e.target.value;

        if (valueSeleccionado == 'multiple') {
            $('#text_nota_seccion').html('<b>Sección Multiple: </b>Se pueden seleccionar mas de un producto de la sección.')
        } else if (valueSeleccionado == 'unico') {
            $('#text_nota_seccion').html('<b>Una Sola Selección: </b>Solo se puede seleccionar un producto de la sección.')
        } else if (valueSeleccionado == 'predeterminado') {
            $('#text_nota_seccion').html('<b>Siempre Icluidos: </b>Estos productos siempre se incluyen en el combo.')
        }
    }
});


function delete_group(idgrupo) {
    if (window.confirm("Deseas eliminar la sección?")) {
        $.ajax({
            type: "post",
            url: "../../controller/menu-combos-delgroups.php",
            data: {
                id_grupo: idgrupo
            },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'EXITO') {
                    show_alert(response.status, response.message);
                    $('#container_data_secciones').load('../../controller/menu-combos.php');
                }
            }
        });
    }
}




/*------------------PRODUCTOS-------------------*/
//AGREGAR PRODUCTO
document.addEventListener('click', e => {
    if (e.target.id === 'open_modal_addproducto') {
        document.getElementById('modal_add_producto')?.classList.add('visible');
    }
    if (e.target.id === 'close_modal_addproducto') {
        document.getElementById('modal_add_producto')?.classList.remove('visible');
    }
});

document.addEventListener('change', e => {
    if (e.target.id === 'img_addproducto') {
        showImgNewProducto(e);
    }
});

function showImgNewProducto(e) {
    const cont_img_newproducto = document.getElementById('label_img_addproducto');
    const input = e.target;
    const file = input.files?.[0];

    if (file) {
        const reader = new FileReader();
        reader.onload = function (ev) {
            cont_img_newproducto.style.background =
                `rgb(0, 0, 0, .2) url(${ev.target.result}) center center / contain no-repeat`;
            cont_img_newproducto.style.backgroundBlendMode = 'darken';
        };
        reader.readAsDataURL(file);
    }
}

//EDITAR PRODUCTO
document.addEventListener('click', (e) => {
    if (e.target.closest('#close_modal_editproducto')) {
        document.getElementById('modal_edit_producto')?.classList.remove('visible');

        const inp = document.getElementById('img_editproducto');
        if (inp) inp.value = '';
    }
});

function edit_producto(idprod) {
    $.ajax({
        type: "post",
        url: "../../controller/menu-productos-search.php",
        data: {
            id: idprod
        },
        dataType: 'json',
        success: function (response) {
            if (response.status === 'EXITO') {
                $('#id_editproducto').val(response.idcat);
                $('#name_editproducto').val(response.producto);
                $('#descripcion_editproducto').val(response.descripcion);
                $('#precio_editproducto').val(response.precio);
                $('#categoria_editproducto').val(response.categoria);
                document.getElementById('label_img_editproducto').style.background = 'rgb(0, 0, 0, .2) url(../../files/img_products/' + response.photo + ') center center / cover no-repeat';
            }
        }
    });

    document.getElementById('modal_edit_producto').classList.add('visible');
}

document.addEventListener('change', e => {
    if (e.target.id === 'img_editproducto') {
        showImgEditProducto(e);
    }
});

function showImgEditProducto(e) {
    const cont_img_editproducto = document.getElementById('label_img_editproducto');
    const input = e.target;
    const file = input.files?.[0];

    if (file) {
        const reader = new FileReader();
        reader.onload = function (ev) {
            cont_img_editproducto.style.background =
                `rgb(0, 0, 0, .2) url(${ev.target.result}) center center / cover no-repeat`;
            cont_img_editproducto.style.backgroundBlendMode = 'darken';
        };
        reader.readAsDataURL(file);
    }
}

//ELIMINAR PRODUCTO
function delete_producto(idprod) {
    if (window.confirm("Deseas eliminar el producto?")) {
        $.ajax({
            type: "post",
            url: "../../controller/menu-productos-delete.php",
            data: {
                id: idprod
            },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'EXITO') {
                    show_alert(response.status, response.message);
                    $('#container_data_secciones').load('../../controller/menu-productos.php');
                }
            }
        });
    }
}








/*------------------VARIANTES-------------------*/
//AGREGAR VARIANTE
document.addEventListener('click', e => {
    if (e.target.id === 'open_modal_addvariante') {
        document.getElementById('modal_add_variante')?.classList.add('visible');
    }
    if (e.target.id === 'close_modal_addvariante') {
        document.getElementById('modal_add_variante')?.classList.remove('visible');
    }
});

document.addEventListener('change', (e) => {
    if (e.target && e.target.id === 'producto_addvariante') {

        $.ajax({
            type: "post",
            url: "../../controller/menu-productos-search.php",
            data: {
                id: e.target.value
            },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'EXITO') {
                    $('#var_precioproducto').html(response.precio);
                    $('#var_preciofinal').html(response.precio);
                    $('#precio_addvariante').val('');
                    $('#inputvar_precioproducto').val(response.precio);

                    document.getElementById('name_addvariante').disabled = false;
                    document.getElementById('precio_addvariante').disabled = false;
                    document.getElementById('btn_add_variante').disabled = false;
                }
            }
        });
    }
});


document.addEventListener('input', (e) => {
    if (e.target?.id === 'precio_addvariante') {
        const precioBase = parseFloat($('#inputvar_precioproducto').val()) || 0;
        const extra = parseFloat(e.target.value) || 0;

        const precioFinal = precioBase + extra;

        $('#var_preciofinal').html(precioFinal.toFixed(2));
    }
});


//EDITAR VARIANTE
function edit_variante(idvar) {
    $.ajax({
        type: "post",
        url: "../../controller/menu-variantes-search.php",
        data: {
            id: idvar
        },
        dataType: 'json',
        success: function (response) {
            if (response.status === 'EXITO') {
                $('#id_editvariante').val(response.idvar);
                $('#name_editvariante').val(response.variante);
                $('#precio_editvariante').val(response.incremento);
            }
        }
    });

    document.getElementById('modal_edit_variante').classList.add('visible');
}


document.addEventListener('click', e => {
    if (e.target.id === 'close_modal_editvariante') {
        document.getElementById('modal_edit_variante')?.classList.remove('visible');
    }
});


//ELIMINAR VARIANTE
function delete_variante(idvar) {
    if (window.confirm("Deseas eliminar variante?")) {
        $.ajax({
            type: "post",
            url: "../../controller/menu-variantes-delete.php",
            data: {
                id: idvar
            },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'EXITO') {
                    show_alert(response.status, response.message);
                    $('#container_data_secciones').load('../../controller/menu-variantes.php');
                }
            }
        });
    }
}






/*------------------EXTRAS-------------------*/
//AGREGAR EXTRAS
document.addEventListener('click', e => {
    if (e.target.id === 'open_modal_addextra') {
        document.getElementById('modal_add_extra')?.classList.add('visible');
    }
    if (e.target.id === 'close_modal_addextra') {
        document.getElementById('modal_add_extra')?.classList.remove('visible');
    }
});


//EDITAR EXTRA
function edit_extra(idextra) {
    $.ajax({
        type: "post",
        url: "../../controller/menu-extras-search.php",
        data: {
            id: idextra
        },
        dataType: 'json',
        success: function (response) {
            if (response.status === 'EXITO') {
                $('#id_editextra').val(response.idext);
                $('#name_editextra').val(response.extra);
                $('#precio_editextra').val(response.precio);
                $('#destino_editextra').val(response.destino);
            }
        }
    });

    document.getElementById('modal_edit_extra').classList.add('visible');
}


document.addEventListener('click', e => {
    if (e.target.id === 'close_modal_editextra') {
        document.getElementById('modal_edit_extra')?.classList.remove('visible');
    }
});

//ELIMINAR EXTRA
function delete_extra(idextra) {
    if (window.confirm("Deseas eliminar extra?")) {
        $.ajax({
            type: "post",
            url: "../../controller/menu-extras-delete.php",
            data: {
                id: idextra
            },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'EXITO') {
                    show_alert(response.status, response.message);
                    $('#container_data_secciones').load('../../controller/menu-extras.php');
                }
            }
        });
    }
}