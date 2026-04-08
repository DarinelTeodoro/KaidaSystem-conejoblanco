<!--Contenedor Principal Inicio-->
<div class="container_main_estadisticas">
    <div class="page-head d-flex justify-content-between align-items-md-center mb-2 gap-2">
        <div>
            <h4 class="mb-0"><i class="bi bi-graph-up me-2"></i>Reporte de ventas</h4>
            <small class="text-muted">Información sobre las ventas realizadas.</small>
        </div>
    </div>
    <div class="line"></div>

    <form method="post" action="" class="container-fluid">
        <div class="d-flex justify-content-end align-items-center">
            <button type="button" class="btn btn-outline-danger" style="height: 38px; border-radius: 0px; margin-right: 5px; font-size: 1.5rem;" onclick="pdf_ventas()"><i class="bi bi-filetype-pdf"></i></button>
            <div><button type="button" class="btn btn-success" style="height: 38px; border-radius: 0px;"><i
                        class="bi bi-arrow-left"></i></button></div>
            <div><input type="date" name="dia" id="dia"
                    style="border-radius: 0px; border-color: #198754; border-right: none; border-left: none;"></div>
            <div><button type="button" class="btn btn-success" style="height: 38px; border-radius: 0px;"><i
                        class="bi bi-arrow-right"></i></button></div>
        </div>
        <!--<div class="d-flex align-items-center justify-content-end mt-2"><button type="submit" class="btn btn-primary" style="width: 100px;">Ir</button></div>-->
    </form>

    <div id="container-data-ventas"></div>
</div>

<script src="estadisticas-operativas.js"></script>