<?php
include('../../model/querys.php');
?>

<!--Contenedor Principal Dashboard-->
<div class="container_main_users">
    <div class="page-head d-flex justify-content-between align-items-md-center mb-2 gap-2">
        <div>
            <h4 class="mb-0"><i class="bi bi-people me-2"></i>Gestión de usuarios</h4>
            <small class="text-muted">Alta, edición y baja de cuentas</small>
        </div>
    </div>
    <div class="line"></div>

    <div class="container-fluid">
        <div class="pt-1 pb-2" align="end">
            <button type="button" class="btn-add" id="open_modal_adduser">Agregar Usuario</button>
        </div>

        <div id="container_list_users">
            <div class="row g-3 mb-4">
                <?php
                $array_userstable = all_users();

                if ($array_userstable) {
                    foreach ($array_userstable as $usertable) {
                        echo '
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="kpi shadow border border-1 border-dark">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="foto_usuario" style="background: rgb(0, 0, 0, 0) url(\'../../files/img_users/'. $usertable['photo'] .'\') center center / cover no-repeat;">
                                        
                                    </div>
                                    <div>
                                        <h6>' . $usertable['name'] . '</h6>
                                        <div class="kpi-sub">' . $usertable['rol'] . '</div>
                                    </div>
                                </div>
                                <div class="kpi-value mt-3" align="end">
                                    <button type="button" class="btn-edit" onclick="edit_user(\'' . $usertable['username'] . '\')"><i class="bi bi-pen"></i></button>
                                    <button type="button" class="btn-delete" onclick="delete_user(' . $usertable['id'] . ')"><i class="bi bi-trash3"></i></button>
                                </div>
                            </div>
                        </div>
                    ';
                    }
                }
                ?>
            </div>
        </div>
    </div>
</div>

<div class="fade_modal_system fixed-top" id="modal_add_user">
    <div class="container_form_modal">
        <div class="head_form_modal">
            <span>Agregar Usuario</span>
            <i class="bi bi-x-lg icon_close_modal" id="close_modal_adduser"></i>
        </div>
        <form action="" method="post" class="body_form_modal" id="form_add_user" enctype="multipart/form-data">
            <div class="d-grid align-items-center justify-content-center pt-3 ps-4 pe-4 pb-1" align="center">
                <label class="label_img" for="img_newuser" id="label_img_newuser">
                    <i class="bi bi-camera-fill"></i>
                </label>
                <input type="file" accept="image/png, image/jpeg, image/webp" name="img_newuser" id="img_newuser">
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="username_newuser">Usuario</label>
                <input type="text" name="username_newuser" id="username_newuser" required>
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="password_newuser">Contraseña</label>
                <input type="password" name="password_newuser" id="password_newuser" required>
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="name_newuser">Nombre del Usuario</label>
                <input type="text" name="name_newuser" id="name_newuser" required>
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="rol_newuser">Rol del Usuario</label>
                <select name="rol_newuser" id="rol_newuser">
                    <option value="Administrador">Administrador</option>
                    <option value="Barra">Barra</option>
                    <option value="Cocina">Cocina</option>
                    <option value="Caja">Caja</option>
                    <option value="Mesero">Mesero</option>
                </select>
            </div>
            <div class="p-2 ps-4 pe-4 pb-4 d-grid">
                <button type="submit" class="btn_execute_modal">Agregar Usuario</button>
            </div>
        </form>
    </div>
</div>



<div class="fade_modal_system fixed-top" id="modal_edit_user">
    <div class="container_form_modal">
        <div class="head_form_modal">
            <span>Editar Usuario</span>
            <i class="bi bi-x-lg icon_close_modal" id="close_modal_edituser"></i>
        </div>
        <form action="" method="post" class="body_form_modal" id="form_edit_user" enctype="multipart/form-data">
            <div class="d-grid align-items-center justify-content-center pt-3 ps-4 pe-4 pb-1" align="center">
                <label class="label_img" for="img_edituser" id="label_img_edituser">
                    <i class="bi bi-camera-fill"></i>
                </label>
                <input type="file" accept="image/png, image/jpeg, image/webp" name="img_edituser" id="img_edituser">
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="user_edituser">Usuario</label>
                <input type="text" name="user_edituser" id="user_edituser" readonly>
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="name_edituser">Nombre del Usuario</label>
                <input type="text" name="name_edituser" id="name_edituser" required>
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="password_edituser">Contraseña</label>
                <input type="password" name="password_edituser" id="password_edituser" placeholder="********">
                <i class="text_nota">**Llenar este campo solo si se desea cambiar la contraseña.</i>
            </div>
            <div class="d-grid p-2 ps-4 pe-4">
                <label for="rol_edituser">Rol del Usuario</label>
                <select name="rol_edituser" id="rol_edituser">
                    <option value="Administrador">Administrador</option>
                    <option value="Barra">Barra</option>
                    <option value="Cocina">Cocina</option>
                    <option value="Caja">Caja</option>
                    <option value="Mesero">Mesero</option>
                </select>
            </div>
            <div class="p-2 ps-4 pe-4 pb-4 d-grid">
                <button type="submit" class="btn_execute_modal">Actualizar Usuario</button>
            </div>
        </form>
    </div>
</div>

<script src="gestion-users-script.js"></script>