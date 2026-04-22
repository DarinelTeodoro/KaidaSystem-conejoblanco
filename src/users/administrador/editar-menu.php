<style>
    .btn_seccion i {
        display: none;
        margin: 6px 8px;
    }
    
    @media (width < 833px) {
        .btn_seccion i {
            display: block;
        }
        .btn_seccion b {
            display: none;
        }
    }
</style>
<!--Contenedor Principal Inicio-->
<div class="container_main_editmenu">
    <div class="page-head d-flex justify-content-between align-items-md-center mb-2 gap-2">
        <div>
            <h4 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Editar menú</h4>
            <small class="text-muted">Agregar o eliminar elementos del menú</small>
        </div>
    </div>
    <div class="line"></div>

    <div class="row g-0 pt-3 justify-content-between">
        <div class="col-2 col-md-2 col-xl-2 cat-card">
            <div class="card h-100 shadow-sm border-0">
                <button type="button" class="btn_seccion seccion_selected"
                    data-seccion="menu-categorias"><i class="bi bi-list-check"></i><b>Categorias</b></button>
            </div>
        </div>
        <div class="col-2 col-md-2 col-xl-2 cat-card">
            <div class="card h-100 shadow-sm border-0">
                <button type="button" class="btn_seccion" data-seccion="menu-combos"><i class="bi bi-box-fill"></i><b>Combos</b></button>
            </div>
        </div>
        <div class="col-2 col-md-2 col-xl-2 cat-card">
            <div class="card h-100 shadow-sm border-0">
                <button type="button" class="btn_seccion" data-seccion="menu-productos"><i class="bi bi-fork-knife"></i><b>Productos</b></button>
            </div>
        </div>
        <div class="col-2 col-md-2 col-xl-2 cat-card">
            <div class="card h-100 shadow-sm border-0">
                <button type="button" class="btn_seccion" data-seccion="menu-variantes"><i class="bi bi-collection-fill"></i><b>Variantes</b></button>
            </div>
        </div>
        <div class="col-2 col-md-2 col-xl-2 cat-card">
            <div class="card h-100 shadow-sm border-0">
                <button type="button" class="btn_seccion" data-seccion="menu-extras"><i class="bi bi-bag-plus-fill"></i><b>Extras</b></button>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="container_data_secciones pt-3 pb-3" id="container_data_secciones">

        </div>
    </div>
</div>

<script src="editar-menu-script.js?v=2"></script>