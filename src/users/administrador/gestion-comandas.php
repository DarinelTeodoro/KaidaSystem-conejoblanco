<!--Contenedor Principal Inicio-->
<div class="container_main_comandas">
    <div class="page-head d-flex justify-content-between align-items-md-center mb-2 gap-2">
        <div>
            <h4 class="mb-0"><i class="bi bi-clipboard me-2"></i>Gestionar comandas</h4>
            <small class="text-muted">Busca una comanda por numero de comanda para gestionarlo</small>
        </div>
    </div>
    <div class="line"></div>

    <form method="post" action="" class="container-fluid p-0" id="search-comanda-gestionar">
        <div class="d-grid align-items-center">
            <label for="comanda_gestionar">Gestionar Comanda #</label>
            <input type="number" name="comanda_gestionar" id="comanda_gestionar" required>
        </div>
        <div class="mt-2">
            <button type="submit" class="btn-add">Buscar</button>
        </div>
    </form>

    <form method="post" action="" id="datos-comanda-gestionar">

    </form>
</div>

<script src="gestion-comandas.js"></script>