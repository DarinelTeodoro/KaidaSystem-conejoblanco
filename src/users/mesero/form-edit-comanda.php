<?php
session_start();
include('../../../cdn.html');
include('../../model/querys.php');

$zona_horaria = new DateTimeZone('America/Mexico_City');
$fecha = new DateTime('now', $zona_horaria);

if (empty($_SESSION['data-useractive'])) {
    header('Location: ../../../index.php');
} else {
    $datauser = consultar_usuario($_SESSION['data-useractive']);

    if ($datauser['rol'] == 'Administrador') {
        header('Location: ../administrador/home.php');
    } elseif ($datauser['rol'] == 'Caja') {
        header('Location: ../caja/home.php');
    } elseif ($datauser['rol'] == 'Barra') {
        header('Location: ../barra/home.php');
    } elseif ($datauser['rol'] == 'Cocina') {
        header('Location: ../cocina/home.php');
    }
}

if (isset($_POST['logout'])) {
    session_destroy();
    echo '<script>window.location.href = "../../../index.php";</script>';
}

$id_comanda = $_GET['id_comanda'];
$data_comanda = detalle_comanda($id_comanda);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="../../../favicon.ico">
    <title>Mesero - Editar Comanda</title>
    <link href="../../../style.css?v=2" rel="stylesheet">
    <link href="style.css?v=2" rel="stylesheet">

    <style>
        #body-page {
            background: #eff0ff;
            padding: 100px 20px 20px;
        }
    </style>
</head>

<body>
    <div class="system_modal_newcomanda m-0">
        <div class="header_modal">
            <a class="btn btn-secondary align-items-center" href="home.php" id="btn-back"
                style="height: 100%; display: flex;"><i class="bi bi-arrow-left"></i></a>
            <button type="button" class="btn btn-light" data-bs-toggle="offcanvas" data-bs-target="#form_new_comanda"
                aria-controls="form_new_comanda">
                <i class="bi bi-cart-fill"></i>
            </button>
        </div>
        <div class="body_modal_newcomanda">
            <div class="columna_newcomanda personalizacion_comanda" id="personalizacion_comanda">
                <div class="pt-3 pb-0 ps-4 pe-4 d-flex align-items-center justify-content-end">
                    <input type="search" name="search-producto-comanda" id="search-producto-comanda"
                        placeholder="Buscar producto/categoria">
                </div>
                <div class="p-4 pt-2" id="productos_seleccionables"></div>
            </div>


            <div></div>
        </div>
    </div>

    <form method="post" action="" class="offcanvas offcanvas-end show mb-0" data-bs-scroll="true"
        data-bs-backdrop="false" tabindex="-1" id="form_new_comanda" aria-labelledby="offcanvasScrollingLabel"
        style="border-left: 1px solid #000000;">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasScrollingLabel">Comanda #<?= $id_comanda ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            <div class="p-2 ps-4 pe-4" id="cont_mesa_pedido" style="display: grid;">
                <label for="cliente-info">Cliente</label>
                <input type="text" name="cliente-info" id="cliente-info"
                    value="<?= $data_comanda['tipo'] == 'mesa' ? 'Mesa ' . $data_comanda['mesa'] : $data_comanda['cliente'] ?>"
                    readonly>
            </div>
            <div class="p-2 ps-4 pe-4" id="cont_nombre_delivery" style="display: none;">
                <label for="nombre-delivery">Nombre/Domicilio cliente</label>
                <input type="text" name="nombre-delivery" id="nombre-delivery" placeholder="Pedido a nombre de...">
            </div>

            <div id="body_detalles_comanda"></div>
        </div>
        <div class="offcanvas-footer">
            <button type="submit" class="btn-send-comanda" id="btn-send-comanda"
                style="width: 100%; border-right: 0px; border-left: 0px;">Enviar Comanda</button>
        </div>
    </form>


    <!--Contenedor Modal Personalizar Combo-->
    <div class="fade_system fixed-top" id="comanda_combo">
        <div class="system_modal">
            <div class="header_modal">
                <span>Arma tu Combo</span>
                <i class="bi bi-x-lg icon_close_modal" id="close_modal_comandacombo"></i>
            </div>
            <div class="body_modal pe-4 ps-4 p-3" id="body_modal_comandacombo">

            </div>
        </div>
    </div>


    <!--Contenedor Modal Seleccionar Variante-->
    <div class="fade_system fixed-top" id="variante_producto">
        <div class="system_modal">
            <div class="header_modal">
                <span>Selecciona la variante</span>
                <i class="bi bi-x-lg icon_close_modal" id="close_modal_selectvariante"></i>
            </div>
            <div class="body_modal pe-4 ps-4 p-3" id="body_modal_selectvariante">

            </div>
        </div>
    </div>


    <!-- Modal Editar Item -->
    <div class="fade_system fixed-top" id="editar_item">
        <div class="system_modal">
            <div class="header_modal">
                <span>Editar</span>
                <i class="bi bi-x-lg icon_close_modal" id="close_modal_editar_item"></i>
            </div>
            <div class="body_modal pe-4 ps-4 p-3">
                <!--<div class="d-grid mb-2">
                    <label>Buscar producto extra</label>
                    <input type="search" id="search_extra_producto" placeholder="Buscar...">
                </div>-->
                <div class="p-2 d-flex align-items-center justify-content-center flex-column">
                    <span class="fs-4 fw-bold">Extras</span>
                    <div class="line"></div>
                </div>

                <div id="lista_extra_productos" style="max-height: 240px; overflow:auto;"></div>

                <hr>

                <div class="d-grid mb-2">
                    <label>Comentarios</label>
                    <textarea id="nota_item" rows="3" placeholder="Sin cebolla, poco hielo, etc."></textarea>
                </div>

                <div class="d-grid">
                    <button type="button" class="btn-send-comanda" id="btn_guardar_edicion_item">Guardar</button>
                </div>
            </div>
        </div>
    </div>


    <!--Contenedor Screen Alert-->
    <div class="container_main_alert fixed-top" id="container_main_alert">
        <div class="container_alert">
            <div class="head_alert">
                <span id="text_title_alert">¡ Alerta !</span>
            </div>
            <div class="body_alert">
                <div class="mb-3"><span id="text_message_alert">Mensaje Alerta</span></div>
                <div id="container-btn-acept"><button type="button" class="btn_accept_alert" id="acept_alert"
                        onclick="hide_alert()">Aceptar</button></div>
            </div>
        </div>
    </div>


    <!--Contenedor Screen Alert 2-->
    <div class="container_main_alert fixed-top" id="container_secondary_alert">
        <div class="container_alert">
            <div class="head_alert">
                <span id="text_title_alert_2">¡ Alerta !</span>
            </div>
            <div class="body_alert">
                <div class="mb-3"><span id="text_message_alert_2">Mensaje Alerta</span></div>
                <div id="container-btn-acept">
                    <button type="button" href="home.php" class="btn_accept_alert me-1" onclick="hide_alert_2()"
                        style="background: #cacaca;">Continuar Agregando</button>
                    <a href="home.php" type="button" class="btn_accept_alert ms-1"
                        style="text-decoration: none; color: #000000; font-family: 'Pompiere', sans-serif; font-weight: bold; font-size: 1.3rem; background: #007e39; color: #ffffff;">Ver
                        Comandas</a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
<script src="../../../script.js?v=2"></script>

<script>
    function show_alert_2(status, message) {
        document.getElementById('container_secondary_alert').classList.add('visible');
        $('#text_message_alert_2').html(message);
        $('#text_title_alert_2').html(status);
    }

    function hide_alert_2() {
        document.getElementById('container_secondary_alert').classList.remove('visible');
    }


    // ════════════════════════════════════════════════════════════════
    //  RENDER DE PRODUCTOS (JSON → DOM)
    //  Reemplaza la carga de HTML con $.load()
    // ════════════════════════════════════════════════════════════════

    let hashProductosActual = null;           // último hash recibido
    let intervaloActualizacionProductos = null;

    /**
     * Pide el JSON al servidor.
     * Si el hash no cambió, no toca el DOM.
     * Si cambió, reconstruye el catálogo completo.
     */
    async function cargarProductosComanda(forzar = false) {
        try {
            const res = await fetch('../../controller/mesero-productos-json.php?_=' + Date.now());
            if (!res.ok) throw new Error('HTTP ' + res.status);

            const data = await res.json();

            // Sin cambios → nada que hacer
            if (!forzar && data.hash === hashProductosActual) return;

            hashProductosActual = data.hash;

            // Guardar scroll de cada carrusel antes de redibujar
            const cont = document.getElementById('productos_seleccionables');
            if (!cont) return;

            const scrollsPrevios = {};
            cont.querySelectorAll('.carrousel_productos[data-cat]').forEach(c => {
                scrollsPrevios[c.dataset.cat] = c.scrollLeft;
            });

            // Renderizar
            cont.innerHTML = renderCatalogo(data.combos, data.categorias);

            // Restaurar scroll
            cont.querySelectorAll('.carrousel_productos[data-cat]').forEach(c => {
                if (scrollsPrevios[c.dataset.cat] !== undefined) {
                    c.scrollLeft = scrollsPrevios[c.dataset.cat];
                }
            });

            // Re-aplicar filtro de búsqueda si hay texto activo
            if (window.refiltrarProductosComanda) window.refiltrarProductosComanda();

        } catch (err) {
            console.warn('Error cargando productos:', err);
        }
    }

    /**
     * Construye el HTML completo del catálogo a partir del JSON.
     */
    function renderCatalogo(combos, categorias) {
        let html = '';

        /* ── Sección COMBOS ────────────────────────────────────────────
        html += '<div class="division_categorias_secciones"><span class="fw-bold">COMBOS</span></div>';
        html += '<div class="carrousel_productos pt-2 pb-2 mb-1">';

        if (combos && combos.length) {
            combos.forEach(combo => {
                const agotado = combo.disponibilidad === 1;
                const searchText = (combo.nombre + ' combos').toLowerCase();
                const accion = agotado ? '' : `onclick="armar_combo(${combo.id})"`;
                const claseCarta = agotado ? 'card_producto agotado' : 'card_producto';
                const badgeHtml = agotado
                    ? '<span class="p-1 fw-bold rounded shadow bg-secondary text-light">Agotado</span>'
                    : '';

                html += `
                <div class="${claseCarta}" data-search="${escapeAttr(searchText)}" ${accion}>
                    <div style="height:100%;width:100%;
                        background:rgb(0,0,0,0.1) url('../../files/img_products/default.webp') center/cover no-repeat;
                        border-radius:10px 10px 0 0;background-blend-mode:darken;">
                        <div class="d-flex align-items-center justify-content-center"
                            style="font-size:.8rem;height:100%;width:100%;">${badgeHtml}</div>
                    </div>
                    <div class="comanda_producto_descipcion">
                        <div class="p-1"><span class="lh-1 text-uppercase">${escapeHtmlRender(combo.nombre)}</span></div>
                        <div class="pe-1 ps-1"><span class="text-primary">$${formatMoney(combo.precio)}</span></div>
                    </div>
                </div>`;
            });
        }
        html += '</div>'; */

        // ── Secciones por categoría ───────────────────────────────────
        if (categorias && categorias.length) {
            categorias.forEach(cat => {
                const catNombre = cat.nombre.toLowerCase();

                html += `
                <div class="division_categorias_secciones" data-cat="${escapeAttr(catNombre)}">
                    <span class="fw-bold">${escapeHtmlRender(cat.nombre)}</span>
                </div>
                <div class="carrousel_productos pt-2 pb-2 mb-1" data-cat="${escapeAttr(catNombre)}">`;

                cat.productos.forEach(prod => {
                    const agotado = prod.disponibilidad === 1;
                    const searchText = (prod.nombre + ' ' + cat.nombre).toLowerCase();
                    const claseCarta = agotado ? 'card_producto agotado' : 'card_producto';
                    const badgeHtml = agotado
                        ? '<span class="p-1 fw-bold rounded shadow bg-secondary text-light">Agotado</span>'
                        : '';

                    let accion = '';
                    if (!agotado) {
                        if (prod.tiene_variantes) {
                            accion = `onclick="select_variante(${prod.id})"`;
                        } else {
                            const nombre = escapeAttr(prod.nombre);
                            accion = `onclick="addprodto_pedido(${prod.id},'${nombre}',${prod.precio},'${prod.destino}')"`;
                        }
                    }

                    html += `
                    <div class="${claseCarta}" data-search="${escapeAttr(searchText)}" ${accion}>
                        <div style="height:100%;width:100%;
                            background:rgb(0,0,0,0.1) url('../../files/img_products/${prod.photo}') center/cover no-repeat;
                            border-radius:10px 10px 0 0;background-blend-mode:darken;">
                            <div class="d-flex align-items-center justify-content-center"
                                style="font-size:.8rem;height:100%;width:100%;">${badgeHtml}</div>
                        </div>
                        <div class="comanda_producto_descipcion">
                            <div class="p-1"><span class="lh-1 text-uppercase">${escapeHtmlRender(prod.nombre)}</span></div>
                            <div class="pe-1 ps-1"><span class="text-primary">$${formatMoney(prod.precio)}</span></div>
                        </div>
                    </div>`;
                });

                html += '</div>'; // fin carrousel categoría
            });
        }

        return html;
    }

    // ── Utilidades de render ──────────────────────────────────────────
    function escapeAttr(str) {
        return (str || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
    }

    function escapeHtmlRender(str) {
        return (str || '').replace(/[&<>"']/g, m =>
            ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m])
        );
    }

    function formatMoney(n) {
        return Number(n || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // ── Polling ───────────────────────────────────────────────────────
    function inicializarRecargaProductos() {
        cargarProductosComanda(true);                                   // carga inmediata
        intervaloActualizacionProductos = setInterval(
            () => cargarProductosComanda(),                             // cada 15 s compara hash
            15000
        );
    }

    function limpiarIntervaloProductos() {
        if (intervaloActualizacionProductos)
            clearInterval(intervaloActualizacionProductos);
    }




    function resetearSelectMesas() {
        $.ajax({
            type: "POST",
            url: "../../controller/mesero-cargar-mesas.php",
            data: { comanda_id: 0 },
            success: function (response) {
                $('#mesa-pedido').html(response);
            }
        });
    }



    $(document).ready(function () {
        document.getElementById('search-producto-comanda').value = '';

        // Limpiar el store de items
        ComandaStore.state.items = [];
        ComandaStore.render();

        //cargarProductosComanda(true); // Usar la nueva función con forzar recarga
        // INICIALIZAR RECARGA AUTOMÁTICA DE PRODUCTOS
        inicializarRecargaProductos();
        // Cargar catálogo de extras
        ComandaStore.loadCatalog();
    });







    /*Seleccionar combo
    document.getElementById('close_modal_comandacombo')
        ?.addEventListener('click', () => {
            document.getElementById('comanda_combo')
                ?.classList.remove('visible');
        });


    function armar_combo(id_combo) {
        document.getElementById('comanda_combo').classList.add('visible');
        $('#body_modal_comandacombo').html('<div class="loader_combo text-center">Cargando Combo...</div>');

        $.ajax({
            type: "post",
            url: "../../controller/mesero-armar-combo.php",
            data: { id: id_combo },
            success: function (response) {
                $("#body_modal_comandacombo").html(response);
            }
        });
    }*/



    //Buscador de productos en comanda
    (function () {
        const input = document.getElementById('search-producto-comanda');

        function normalizar(txt) {
            return (txt || '')
                .toString()
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '') // quita acentos
                .trim();
        }

        function aplicarFiltro() {
            const q = normalizar(input.value);
            const cont = document.getElementById('productos_seleccionables');
            if (!cont) return;

            const secciones = cont.querySelectorAll('.division_categorias_secciones');
            const carouseles = cont.querySelectorAll('.carrousel_productos');

            // Si está vacío: mostrar todo
            if (!q) {
                cont.querySelectorAll('.card_producto').forEach(c => (c.style.display = ''));
                secciones.forEach(s => (s.style.display = ''));
                carouseles.forEach(c => (c.style.display = ''));
                return;
            }

            // 1) Mostrar/ocultar cards según data-search
            cont.querySelectorAll('.card_producto').forEach(card => {
                const haystack = normalizar(card.getAttribute('data-search'));
                card.style.display = haystack.includes(q) ? '' : 'none';
            });

            // 2) Ocultar secciones/carouseles que quedaron sin cards visibles
            carouseles.forEach(car => {
                const visibles = Array.from(car.querySelectorAll('.card_producto'))
                    .some(card => card.style.display !== 'none');

                car.style.display = visibles ? '' : 'none';

                // Oculta/mostrar su header anterior (la división de categoría)
                const header = car.previousElementSibling;
                if (header && header.classList.contains('division_categorias_secciones')) {
                    header.style.display = visibles ? '' : 'none';
                }
            });
        }

        // input en vivo
        input.addEventListener('input', aplicarFiltro);

        // opcional: al abrir modal / recargar productos, re-aplica filtro actual
        window.refiltrarProductosComanda = aplicarFiltro;
    })();


    //Seleccionar variante
    function select_variante(id_producto) {
        document.getElementById('variante_producto').classList.add('visible');
        $('#body_modal_selectvariante').html('<div class="loader_variante text-center">Cargando Combo...</div>');

        $.ajax({
            type: "post",
            url: "../../controller/mesero-select-variante.php",
            data: { id: id_producto },
            success: function (response) {
                $("#body_modal_selectvariante").html(response);
            }
        });
    }


    document.getElementById('close_modal_selectvariante')
        ?.addEventListener('click', () => {
            document.getElementById('variante_producto')
                ?.classList.remove('visible');
        });




    /***********************
 *  Carrito de comanda
 ***********************/
    const ComandaStore = (() => {
        const state = {
            items: [], // array de Item
            productsCatalog: [], // [{id, nombre, precio}]
            editingUid: null,
        };

        const uid = () => `itm_${Date.now()}_${Math.random().toString(16).slice(2)}`;

        const money = (n) => {
            const x = Number(n || 0);
            return x.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };

        function itemTotal(item) {
            const qty = Number(item.qty || 1);
            const base = Number(item.base?.precio || 0) * qty;
            const extras = (item.extras || []).reduce((acc, ex) => acc + (Number(ex.precio || 0) * Number(ex.qty || 1)), 0);
            return base + extras;
        }

        function comandaTotal(items) {
            return (items || []).reduce((acc, it) => acc + itemTotal(it), 0);
        }

        function findItem(u) {
            return state.items.find(i => i.uid === u);
        }

        function removeItem(u) {
            state.items = state.items.filter(i => i.uid !== u);
            render();
        }

        function resaltar() {
            var myOffcanvas = new bootstrap.Offcanvas(document.getElementById('form_new_comanda'));
            myOffcanvas.show();
        }

        function addProductSimple({ product_id, nombre, precio, df }) {
            state.items.push({
                uid: uid(),
                type: "product",
                df: df,
                qty: 1,
                base: { product_id, nombre, precio, variante_id: null, variante_nombre: null },
                components: [], // para producto simple no aplica
                extras: [],     // [{product_id, nombre, precio, qty}]
                note: ""
            });
            resaltar();
            render();
        }

        function addProductVariant({ product_id, variante_id, prodNombre, varNombre, precio, precio_base, incremento, df }) {
            state.items.push({
                uid: uid(),
                type: "product",
                df: df,
                qty: 1,
                base: {
                    product_id,
                    nombre: prodNombre,
                    precio: Number(precio || 0),
                    precio_base: Number(precio_base || 0),
                    incremento: Number(incremento || 0),
                    variante_id,
                    variante_nombre: varNombre
                },
                components: [
                    { kind: "variante", product_id, variante_id, nombre: `${prodNombre} - ${varNombre}`, precio_original: Number(precio_base || 0), precio: Number(incremento || 0) }
                ],
                extras: [], // ahora serán extras (menu_extras)
                note: ""
            });
            resaltar();
            render();
        }


        function addCombo({ combo_id, combo_nombre, combo_precio, seleccion, incluidos, note, df }) {
            // seleccion: [{grupo_id, grupo_nombre, producto_id, producto_nombre}]
            // incluidos: [{producto_id, producto_nombre}]
            state.items.push({
                uid: uid(),
                type: "combo",
                df: "Ambos",
                qty: 1,
                base: { combo_id, nombre: combo_nombre, precio: combo_precio },
                components: [
                    ...incluidos.map(p => ({ kind: "incluido", ...p })),
                    ...seleccion.map(p => ({ kind: "seleccion", ...p })),
                ],
                extras: [],
                note: note || ""
            });
            resaltar();
            render();
        }

        function setEditing(uid) {
            state.editingUid = uid;
            const item = findItem(uid);
            if (!item) return;

            // precarga nota en textarea
            document.getElementById('nota_item').value = item.note || "";

            // FORZAR RECARGA DEL CATÁLOGO DE EXTRAS
            loadCatalog(true).then(() => {
                // pinta listado de productos con los datos más recientes
                renderExtrasCatalog("");
            });

            document.getElementById('editar_item').classList.add('visible');
        }

        function closeEditing() {
            state.editingUid = null;
            document.getElementById('editar_item').classList.remove('visible');
        }

        function addExtraToItem(uid, ex) {
            const item = findItem(uid);
            if (!item) return;

            const existing = item.extras.find(e => e.extra_id === ex.id);
            if (existing) existing.qty += 1;
            else {
                item.extras.push({
                    extra_id: ex.id,
                    nombre: ex.nombre,
                    precio: ex.precio,
                    qty: 1
                });
            }
            render();
            // Actualizar el catálogo de extras para reflejar el nuevo subtotal
            renderExtrasCatalog(document.getElementById('search_extra_producto')?.value || '');
        }


        function decExtra(uid, extra_id) {
            const item = findItem(uid);
            if (!item) return;

            extra_id = Number(extra_id);
            item.extras = item.extras || [];

            const idx = item.extras.findIndex(e => Number(e.extra_id) === extra_id);
            if (idx === -1) return;

            const ex = item.extras[idx];
            ex.qty = Number(ex.qty || 0) - 1;

            // Si llega a 0, lo eliminamos del array
            if (ex.qty <= 0) {
                item.extras.splice(idx, 1);
            }

            render();
            // Actualizar el catálogo de extras para reflejar el nuevo subtotal
            renderExtrasCatalog(document.getElementById('search_extra_producto')?.value || '');
        }

        function incExtra(uid, extra_id) {
            const item = findItem(uid);
            if (!item) return;

            extra_id = Number(extra_id);
            item.extras = item.extras || [];

            const ex = item.extras.find(e => Number(e.extra_id) === extra_id);
            if (!ex) return;

            ex.qty = Number(ex.qty || 0) + 1;
            render();
            // Actualizar el catálogo de extras para reflejar el nuevo subtotal
            renderExtrasCatalog(document.getElementById('search_extra_producto')?.value || '');
        }

        function saveEditing() {
            const u = state.editingUid;
            const item = findItem(u);
            if (!item) return;
            item.note = document.getElementById('nota_item').value || "";
            closeEditing();
            render();
        }

        function render() {
            const cont = document.getElementById('body_detalles_comanda');
            if (!cont) return;

            if (!state.items.length) {
                cont.innerHTML = `<div class="p-3 text-muted text-center">No hay productos agregados.</div>`;
                return;
            }

            const totalComanda = comandaTotal(state.items);

            cont.innerHTML = `
    <div class="p-2 mb-2 sticky-top" style="background:#003558; border-bottom:1px solid #000000; border-top:1px solid #000000;">
      <div class="d-flex justify-content-between align-items-center">
        <div class="text-light">Total comanda</div>
        <div class="fw-bold text-warning">$${money(totalComanda)}</div>
      </div>
    </div>

    ${state.items.map(item => {
                const title = item.type === "combo"
                    ? `🍱 ${item.base.nombre}`
                    : `🍽️ ${item.base.nombre}${item.base.variante_nombre ? ` <span class="text-muted">(${item.base.variante_nombre})</span>` : ""}`;

                let precioHtml = '';
                let seleccionesHtml = '';

                if (item.type === "combo") {
                    // Para combo: el precio va junto al título
                    precioHtml = `<span class="text-muted">$${money(item.base.precio)}</span>`;

                    // Las selecciones van en un contenedor separado
                    const incluidos = item.components.filter(c => c.kind === "incluido");
                    const seleccion = item.components.filter(c => c.kind === "seleccion");

                    // Agrupar seleccionados por grupo
                    const seleccionPorGrupo = {};
                    seleccion.forEach(s => {
                        const grupoNombre = s.grupo_nombre || "Otros";
                        if (!seleccionPorGrupo[grupoNombre]) {
                            seleccionPorGrupo[grupoNombre] = [];
                        }
                        seleccionPorGrupo[grupoNombre].push(s);
                    });

                    seleccionesHtml = '<div class="combo-detalles mt-2" style="font-size:0.9rem;">';

                    // Mostrar incluidos
                    if (incluidos.length > 0) {
                        seleccionesHtml += `
                    <div class="mb-1">
                        <span class="text-muted fw-bold">Incluye:</span>
                        <ul class="m-0 ps-3" style="list-style-type: none; padding-left: 0 !important;">
                            ${incluidos.map(i => `
                                <li style="margin-left: 0; padding-left: 0;">
                                    <i class="bi bi-check-circle-fill text-success" style="font-size:0.7rem;"></i>
                                    ${i.producto_nombre}
                                </li>
                            `).join("")}
                        </ul>
                    </div>
                `;
                    }

                    // Mostrar seleccionados por grupo
                    if (Object.keys(seleccionPorGrupo).length > 0) {
                        Object.entries(seleccionPorGrupo).forEach(([grupo, productos]) => {
                            seleccionesHtml += `
                        <div class="mb-1">
                            <span class="text-muted fw-bold">${grupo}:</span>
                            <ul class="m-0 ps-3" style="list-style-type: none; padding-left: 0 !important;">
                                ${productos.map(p => `
                                    <li style="margin-left: 0; padding-left: 0;">
                                        <i class="bi bi-check-circle-fill text-primary" style="font-size:0.7rem;"></i>
                                        ${p.producto_nombre}
                                    </li>
                                `).join("")}
                            </ul>
                        </div>
                    `;
                        });
                    }

                    seleccionesHtml += '</div>';
                } else {
                    // Para productos normales
                    const comps = item.components || [];
                    precioHtml = comps.length
                        ? `<ul class="m-0 ps-3">
                    ${comps.map(c => {
                            return `${c.precio ? ` <span class="text-muted">$${money(c.precio_original + c.precio)}</span>` : ` <span class="text-muted" style="font-size:.85rem;">$${money(c.precio_original)}</span>`}`;
                        }).join("")}
                  </ul>`
                        : `<span class="text-muted">$${money(item.base.precio)}</span>`;
                }

                const extras = item.extras || [];
                const extrasHtml = extras.length
                    ? `<div>
                ${extras.map(ex => `
                    <div><span class="text-muted" style="font-size: 0.9rem;">+ ${ex.nombre} x ${ex.qty} = $${money(ex.precio * ex.qty)}</span></div>
                `).join("")}
              </div>`
                    : "";

                const noteHtml = item.note
                    ? `<div class="mt-1"><div class="p-1 pb-0"><span class="text-muted" style="font-size:.85rem;">Nota:</span></div><div class="p-1" style="background: rgb(255, 193, 7, 0.2);">${escapeHtml(item.note)}</div></div>`
                    : "";

                const totalItem = itemTotal(item);

                return `
        <div class="p-2 mb-2" style="background:#fff; border-top:0.6px solid #000; border-bottom:0.6px solid #000;">
            <div class="d-flex justify-content-between ps-2 pe-2">
                <div class="fw-bold">${title}</div>
                <div>${precioHtml}</div>
            </div>
            
            <!-- Contenedor separado para las selecciones del combo (solo si es combo) -->
            ${item.type === "combo" ? seleccionesHtml : ''}
            
            <div class="d-flex justify-content-between">
                <div>
                    ${extrasHtml}
                </div>
                <div class="d-flex flex-column align-items-start">
                    <div><button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="ComandaUI.editItem('${item.uid}')" style="width: 100px;">Notas/Extras</button></div>
                    <div><button type="button" class="btn btn-sm btn-outline-danger mt-1" onclick="ComandaUI.removeItem('${item.uid}')" style="width: 100px;">Quitar</button></div>
                </div>
            </div>
            <div>
                ${noteHtml}
            </div>
            <div class="d-flex justify-content-between fw-bold mt-1 ps-2 pe-2">
                <div class="text-muted" style="font-size: 0.9rem;">Total</div>
                <div><span class="text-success">$${money(totalItem)}</span></div>
            </div>
        </div>
      `;
            }).join("")}
  `;
        }


        function escapeHtml(str) {
            return (str || "").replace(/[&<>"']/g, (m) => ({
                "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;"
            }[m]));
        }

        // Carga catálogo de extras (para extras)
        async function loadCatalog(forzar = false) {
            return new Promise((resolve) => {
                const ahora = Date.now();
                const cacheKey = forzar ? ahora : '';

                $.ajax({
                    url: "../../controller/mesero-extras-json.php" + (cacheKey ? '?_=' + cacheKey : ''),
                    method: "GET",
                    dataType: "json",
                    cache: !forzar, // Deshabilitar caché si se fuerza
                    success: (data) => {
                        state.productsCatalog = Array.isArray(data) ? data : [];
                        resolve();
                    },
                    error: () => resolve()
                });
            });
        }


        function renderExtrasCatalog(q) {
            const list = document.getElementById('lista_extra_productos');
            if (!list) return;

            // Calcular subtotal de extras del item actual
            const currentItem = state.editingUid ? findItem(state.editingUid) : null;

            // DETERMINAR QUÉ TIPOS DE EXTRAS MOSTRAR
            let tiposExtrasPermitidos = [];

            if (currentItem) {
                if (currentItem.df === 'Ambos') {
                    // Los combos pueden tener extras de ambos tipos
                    tiposExtrasPermitidos = ['cocina', 'barra'];
                } else {
                    // Productos normales: determinar si es de cocina o barra
                    const tipoProducto = currentItem.df || 'cocina'; // Por defecto cocina
                    tiposExtrasPermitidos = [tipoProducto];
                }
            }

            const extrasSubtotal = currentItem?.extras?.reduce((total, extra) => {
                return total + (extra.precio * extra.qty);
            }, 0) || 0;

            const needle = (q || "").toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();

            // Filtrar por búsqueda Y por tipo permitido
            const filtered = state.productsCatalog.filter(p => {
                // Filtrar por búsqueda
                const hay = (p.nombre || "").toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
                const coincideBusqueda = !needle || hay.includes(needle);

                // Filtrar por tipo permitido
                const tipoPermitido = tiposExtrasPermitidos.length === 0 ||
                    tiposExtrasPermitidos.includes(p.tipo) ||
                    (currentItem?.type === 'combo' && (p.tipo === 'cocina' || p.tipo === 'barra'));

                return coincideBusqueda && tipoPermitido;
            }).slice(0, 80);

            // Crear el HTML con el header del subtotal
            let html = `
        <div class="p-2 mb-2" style="border-bottom:1px solid #000;">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted">Subtotal Extras:</span>
                <span class="fw-bold text-success">$${money(extrasSubtotal)}</span>
            </div>
        </div>
    `;

            // Si no hay extras disponibles del tipo necesario
            if (filtered.length === 0) {
                html += `
            <div class="p-4 text-center text-muted">
                <i class="bi bi-info-circle fs-1 d-block mb-3"></i>
                No hay extras disponibles para este ${currentItem?.type === 'combo' ? 'combo' : 'producto'}
            </div>
        `;
                list.innerHTML = html;
                return;
            }

            html += filtered.map(p => {
                // Verificar si este extra ya está en el item actual
                const existingExtra = currentItem?.extras?.find(e => e.extra_id === p.id);
                const currentQty = existingExtra ? existingExtra.qty : 0;
                const extraTotal = existingExtra ? existingExtra.precio * existingExtra.qty : 0;

                // Aplicar la condición de disponibilidad y tipo
                const botonesHtml = p.disponibilidad == 1 ?
                    '<button type="button" class="btn btn-sm btn-secondary" disabled>Agotado</button>' :
                    (currentQty > 0 ? `
                <button type="button" class="btn btn-outline-secondary" onclick="ComandaUI.decExtra('${state.editingUid}', ${p.id})"><i class="bi bi-dash-lg"></i></button>
                <button class="btn btn-dark" type="button" disabled><span class="badge p-0" style="font-size: 1.2rem;">${currentQty}</span></button>
                <button type="button" class="btn btn-outline-secondary" onclick="ComandaUI.incExtra('${state.editingUid}', ${p.id})"><i class="bi bi-plus-lg"></i></button>
            ` : `
                <button type="button" class="btn btn-sm btn-outline-success" onclick="ComandaUI.addExtraCurrent(${p.id})">Agregar</button>
            `);

                return `
        <div class="d-flex align-items-center justify-content-between p-2" style="border-bottom:1px solid #eee;">
            <div style="flex: 1;">
                <div>${p.nombre} <span class="badge bg-${p.tipo === 'barra' ? 'info' : 'warning'}">${p.tipo === 'barra' ? '<i class="bi bi-cup-hot"></i>' : '<i class="bi bi-egg-fried"></i>'}</span></div>
                <div class="d-flex gap-2">
                    <span class="text-muted" style="font-size:.85rem;">$${money(p.precio)} c/u</span>
                    ${currentQty > 0 ? `<span class="text-success" style="font-size:.85rem;">= $${money(extraTotal)}</span>` : ''}
                </div>
            </div>
            <div class="d-flex align-items-center" style="gap:8px;">
                ${botonesHtml}
            </div>
        </div>
    `;
            }).join("");

            list.innerHTML = html;
        }

        // API pública
        // En la API pública de ComandaStore, agrega:
        return {
            state,
            render,
            addProductSimple,
            addProductVariant,
            //addCombo,
            removeItem,
            setEditing,
            closeEditing,
            saveEditing,
            addExtraToItem,
            incExtra,
            decExtra,
            loadCatalog,
            renderExtrasCatalog,
            findItem,
        };
    })();

    /***********************
     * Puente UI (para onclick)
     ***********************/
    window.ComandaUI = {
        removeItem: (uid) => ComandaStore.removeItem(uid),
        editItem: (uid) => ComandaStore.setEditing(uid),
        addExtraCurrent: (extraId) => {
            const uid = ComandaStore.state.editingUid;
            const ex = ComandaStore.state.productsCatalog.find(p => p.id === extraId);
            if (!uid || !ex) return;

            // Agregar el extra con cantidad 1
            const item = ComandaStore.findItem(uid);
            if (item) {
                const existing = item.extras.find(e => e.extra_id === ex.id);
                if (existing) {
                    existing.qty += 1;
                } else {
                    item.extras.push({
                        extra_id: ex.id,
                        nombre: ex.nombre,
                        precio: ex.precio,
                        qty: 1
                    });
                }
                ComandaStore.render();
                // Refrescar el catálogo de extras para mostrar los botones +/-
                ComandaStore.renderExtrasCatalog(document.getElementById('search_extra_producto')?.value || '');
            }
        },
        incExtra: (uid, extraId) => {
            ComandaStore.incExtra(uid, extraId);
            // Refrescar el catálogo de extras después de incrementar
            ComandaStore.renderExtrasCatalog(document.getElementById('search_extra_producto')?.value || '');
        },
        decExtra: (uid, extraId) => {
            ComandaStore.decExtra(uid, extraId);
            // Refrescar el catálogo de extras después de decrementar
            ComandaStore.renderExtrasCatalog(document.getElementById('search_extra_producto')?.value || '');
        },
    };

    /***********************
     * Hooks para tus acciones existentes
     ***********************/

    // 3) Producto simple (sin variantes)
    window.addprodto_pedido = function (id, nombre, precio, df) {
        ComandaStore.addProductSimple({ product_id: id, nombre, precio, df });
    };


    window.addvarto_pedido = function (product_id, variante_id, prodNombre, varNombre, precioBase, incremento, df) {
        const base = Number(precioBase || 0);
        const inc = Number(incremento || 0);
        const precioFinal = base + inc;

        ComandaStore.addProductVariant({
            product_id,
            variante_id,
            prodNombre,
            varNombre,
            precio: precioFinal,
            precio_base: base,
            incremento: inc,
            df: df
        });

        document.getElementById('variante_producto')?.classList.remove('visible');
    };

    // 1) Combo agregado
    window.addcombto_pedido = function () {
        const combo_id = Number(document.getElementById('combo_id_actual')?.value || 0);
        const combo_nombre = document.getElementById('combo_nombre_actual')?.value || 'Combo';
        const combo_precio = Number(document.getElementById('combo_precio_actual')?.value || 0);
        const note = document.getElementById('notas_combo')?.value || "";

        const modal = document.getElementById('body_modal_comandacombo');
        if (!modal || !combo_id) return;

        // limpia marcas previas
        modal.querySelectorAll('.grupo_opciones').forEach(g => {
            g.style.outline = '';
            g.style.borderRadius = '';
        });
        modal.querySelectorAll('.msg-error-combo').forEach(m => m.remove());

        // ✅ VALIDACIÓN POR GRUPO
        let ok = true;
        const errores = [];

        modal.querySelectorAll('.grupo_opciones').forEach(grupo => {
            const tipo = (grupo.getAttribute('data-tipo') || '').trim();

            // no validar predeterminado
            if (tipo === 'predeterminado') return;

            const header = grupo.querySelector('.division_categorias_secciones');
            const grupoNombre = header ? header.textContent.trim() : 'Grupo';

            const inputs = Array.from(grupo.querySelectorAll('input.btn-check'));
            if (!inputs.length) return;

            if (tipo === 'multiple') {
                const marcados = inputs.filter(i => i.checked).length;
                if (marcados < 1) {
                    ok = false;
                    errores.push(`Selecciona al menos una opción en "${grupoNombre}".`);
                    marcarErrorGrupo(grupo, 'Selecciona al menos una opción.');
                }
            }

            if (tipo === 'unico') {
                const marcados = inputs.filter(i => i.checked).length;
                if (marcados < 1) {
                    ok = false;
                    errores.push(`Selecciona una opción en "${grupoNombre}".`);
                    marcarErrorGrupo(grupo, 'Selecciona una opción.');
                }
            }
        });


        if (!ok) {
            const htmlErrores = `
            <ul style="margin:0; padding-left:18px;" align="justify">
                ${errores.map(e => `<li>${e}</li>`).join("")}
            </ul>
        `;

            show_alert("ALERTA", htmlErrores);
            // mensaje general arriba (opcional)
            //show_alert("ALERTA", errores.join("\n"));
            return;
        }

        // Recolecta seleccionados
        const seleccion = [];
        modal.querySelectorAll('input.btn-check:checked').forEach(inp => {
            const value = Number(inp.value);
            const label = modal.querySelector(`label[for="${inp.id}"]`);
            const nombre = label ? label.textContent.trim() : `Producto ${value}`;

            const name = inp.name || "";
            const m = name.match(/^grupo_(\d+)/);
            const grupo_id = m ? Number(m[1]) : null;

            let grupo_nombre = "";
            const grupoBox = inp.closest('.grupo_opciones');
            const header = grupoBox?.querySelector('.division_categorias_secciones');
            if (header) grupo_nombre = header.textContent.trim();

            seleccion.push({
                grupo_id,
                grupo_nombre,
                producto_id: value,
                producto_nombre: nombre,
                kind: 'seleccion'
            });
        });

        // Incluidos predeterminados
        const incluidos = [];
        modal.querySelectorAll('.grupo_opciones input[readonly]').forEach(ro => {
            incluidos.push({ producto_id: ro.dataset.idproducto, producto_nombre: ro.value, kind: 'incluido' });
        });

        ComandaStore.addCombo({
            combo_id,
            combo_nombre,
            combo_precio,
            seleccion,
            incluidos,
            note
        });

        document.getElementById('comanda_combo')?.classList.remove('visible');
    };

    function marcarErrorGrupo(grupoEl, msg) {
        //grupoEl.style.outline = '2px solid #dc3545';
        grupoEl.style.borderRadius = '0px';

        const div = document.createElement('div');
        div.className = 'msg-error-combo text-danger';
        div.style.fontSize = '0.7rem';
        div.style.marginTop = '6px';
        div.textContent = msg;

        grupoEl.appendChild(div);
    }

    // modal editar item
    document.getElementById('close_modal_editar_item')
        ?.addEventListener('click', () => ComandaStore.closeEditing());

    document.getElementById('btn_guardar_edicion_item')
        ?.addEventListener('click', () => ComandaStore.saveEditing());

    document.getElementById('search_extra_producto')
        ?.addEventListener('input', (e) => ComandaStore.renderExtrasCatalog(e.target.value));



    /***********************
     * Al abrir modal nueva comanda:
     * - cargas productos seleccionables
     * - y cargas catálogo para extras (JSON)
     ***********************/
    // Función para resetear scroll de un modal
    function resetearScrollModal() {
        const modal = document.getElementById('personalizacion_comanda');
        modal.scrollTop = 0;
    }


    // Modificar el modal de edición de ítem
    document.getElementById('editar_item')
        ?.addEventListener('transitionend', function () {
            if (this.classList.contains('visible')) {
                resetearScrollModal();
            }
        });



    document.getElementById('form_new_comanda')
        ?.addEventListener('submit', function (e) {
            e.preventDefault();
            document.getElementById('btn-send-comanda').disabled = true;

            // no permitir vacío
            if (!ComandaStore.state.items || ComandaStore.state.items.length === 0) {
                document.getElementById('btn-send-comanda').disabled = false;
                show_alert("ALERTA", "Debes agregar al menos un producto.");
                return;
            }

            const payload = { items: ComandaStore.state.items };
            show_alert('Procesando Peticion', 'Esperando respuesta del servidor, porfavor espere.', false);

            $.ajax({
                type: "POST",
                url: "../../controller/mesero-agregar-items-comanda.php",
                data: { comanda_id: <?= $id_comanda ?>, payload: JSON.stringify(payload) },
                success: function () {
                    ComandaStore.state.items = [];
                    ComandaStore.render();
                    document.getElementById('btn-send-comanda').disabled = false;
                    document.getElementById('search-producto-comanda').value = '';
                    hide_alert();
                    show_alert_2("EXITO", "Productos agregados exitosamente.");
                },
                error: function (xhr, status, error) {
                    var mensajeError = xhr.responseText;
                    show_alert("ERROR", mensajeError);
                    document.getElementById('btn-send-comanda').disabled = false;

                }
            });
        });
</script>