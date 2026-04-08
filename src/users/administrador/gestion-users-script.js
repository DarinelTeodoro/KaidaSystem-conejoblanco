/* ----------------- MODULO USUARIOS ----------------- */
//AGREGAR USUARIO
document.addEventListener('click', e => {
    if (e.target.id === 'open_modal_adduser') {
        document.getElementById('modal_add_user')?.classList.add('visible');
    }
    if (e.target.id === 'close_modal_adduser') {
        document.getElementById('modal_add_user')?.classList.remove('visible');
    }
});

document.addEventListener('change', e => {
    if (e.target.id === 'img_newuser') {
        showImgNewUser(e);
    }
});

function showImgNewUser(e) {
    const cont_img_newuser = document.getElementById('label_img_newuser');
    const input = e.target;
    const file = input.files?.[0];

    if (file) {
        const reader = new FileReader();
        reader.onload = function (ev) {
            cont_img_newuser.style.background =
                `rgb(0, 0, 0, .2) url(${ev.target.result}) center center / cover no-repeat`;
            cont_img_newuser.style.backgroundBlendMode = 'darken';
        };
        reader.readAsDataURL(file);
    }
}

$('#form_add_user').submit(function (event) {
    event.preventDefault();

    var formData = new FormData(this);

    $.ajax({
        type: 'POST',
        url: '../../controller/user-add.php',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function (response) {
            if (response.status === 'EXITO') {
                show_alert(response.status, response.message);
                $('#container_main_home').load('gestion-users.php');
            } else {
                show_alert(response.status, response.message);
            }
        }
    });
});

//EDITAR USUARIO
document.addEventListener('click', (e) => {
    if (e.target.closest('#close_modal_edituser')) {
        document.getElementById('modal_edit_user')?.classList.remove('visible');

        const inp = document.getElementById('img_edituser');
        if (inp) inp.value = '';
    }
});

function edit_user(user) {
    $.ajax({
        type: "post",
        url: "../../controller/user-search.php",
        data: {
            user: user
        },
        dataType: 'json',
        success: function (response) {
            if (response.status === 'EXITO') {
                $('#name_edituser').val(response.nombre);
                $('#rol_edituser').val(response.rol);
                $('#user_edituser').val(response.user);
                document.getElementById('label_img_edituser').style.background = 'rgb(0, 0, 0, .2) url(../../files/img_users/' + response.foto + ') center center / cover no-repeat';
            }
        }
    });

    document.getElementById('modal_edit_user').classList.add('visible');
}

document.addEventListener('change', e => {
    if (e.target.id === 'img_edituser') {
        showImgEditUser(e);
    }
});

function showImgEditUser(e) {
    const cont_img_edituser = document.getElementById('label_img_edituser');
    const input = e.target;
    const file = input.files?.[0];

    if (file) {
        const reader = new FileReader();
        reader.onload = function (ev) {
            cont_img_edituser.style.background =
                `rgb(0, 0, 0, .2) url(${ev.target.result}) center center / cover no-repeat`;
            cont_img_edituser.style.backgroundBlendMode = 'darken';
        };
        reader.readAsDataURL(file);
    }
}

$('#form_edit_user').submit(function (event) {
    event.preventDefault();

    var formData = new FormData(this);

    $.ajax({
        type: 'POST',
        url: '../../controller/user-edit.php',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function (response) {
            if (response.status === 'EXITO') {
                show_alert(response.status, response.message);
                $('#container_main_home').load('gestion-users.php');
            } else {
                show_alert(response.status, response.message);
            }
        }
    });
});

//ELIMINAR USUARIO
function delete_user(id) {
    if (window.confirm("Deseas eliminar el usuario?")) {
        $.ajax({
            type: "post",
            url: "../../controller/user-delete.php",
            data: {
                id_user: id
            },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'EXITO') {
                    show_alert(response.status, response.message);
                    $('#container_main_home').load('gestion-users.php');
                }
            }
        });
    }
}