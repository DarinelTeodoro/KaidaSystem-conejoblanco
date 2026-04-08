// Variables para el sistema de recarga inteligente de comandas
let intervaloActualizacionComandas;
let comandasAnteriores = []; // Guardar el estado anterior de las comandas

// Función para mostrar carga mientras se obtienen los datos
function mostrarCargaComandas() {
    const container = document.getElementById('container_lista_comandas');
    if (container) {
        container.innerHTML = `
            <div class='p-4 text-muted text-center'>
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <div>Cargando comandas...</div>
            </div>
        `;
    }
}


// Función para analizar los cambios entre el HTML anterior y el nuevo
function analizarCambiosComandas(htmlAnterior, htmlNuevo) {
    const resultado = {
        agregadas: 0,
        modificadas: 0,
        eliminadas: 0,
        idsAgregados: [],
        idsModificados: [],
        idsEliminados: []
    };

    // Si el HTML nuevo es un mensaje de "sin comandas", no hay IDs que procesar
    if (htmlNuevo.includes('No hay comandas en esta vista')) {
        return resultado;
    }

    // Extraer IDs de las comandas del HTML anterior (solo si no es mensaje)
    const idsAnteriores = !htmlAnterior.includes('No hay comandas en esta vista') ?
        extraerIdsComandas(htmlAnterior) : [];

    // Extraer IDs de las comandas del HTML nuevo
    const idsNuevos = extraerIdsComandas(htmlNuevo);

    // Encontrar IDs agregados (están en nuevo pero no en anterior)
    resultado.idsAgregados = idsNuevos.filter(id => !idsAnteriores.includes(id));
    resultado.agregadas = resultado.idsAgregados.length;

    // Encontrar IDs eliminados (están en anterior pero no en nuevo)
    resultado.idsEliminados = idsAnteriores.filter(id => !idsNuevos.includes(id));
    resultado.eliminadas = resultado.idsEliminados.length;

    // Encontrar IDs que están en ambos (posibles modificaciones)
    const idsComunes = idsNuevos.filter(id => idsAnteriores.includes(id));

    // Para cada ID común, verificar si el contenido cambió
    resultado.idsModificados = idsComunes.filter(id => {
        const cardAnterior = extraerCardComanda(htmlAnterior, id);
        const cardNuevo = extraerCardComanda(htmlNuevo, id);
        return cardAnterior !== cardNuevo;
    });
    resultado.modificadas = resultado.idsModificados.length;

    return resultado;
}

// Función para extraer IDs de comandas del HTML
function extraerIdsComandas(html) {
    const ids = [];
    const regex = /data-id=['"](\d+)['"]/g;
    let match;
    while ((match = regex.exec(html)) !== null) {
        ids.push(match[1]);
    }
    return ids;
}

// Función para extraer una card específica del HTML
function extraerCardComanda(html, id) {
    const regex = new RegExp(`<div[^>]*class=['"]card_comanda[^>]*data-id=['"]${id}['"][^>]*>.*?<\\/div>\\s*<\\/div>?`, 's');
    const match = html.match(regex);
    return match ? match[0] : '';
}

// Función para actualizar las comandas con animación
function actualizarComandasConAnimacion(container, nuevoHtml, cambios) {
    // Si el nuevo HTML es un mensaje de "sin comandas"
    if (nuevoHtml.includes('No hay comandas en esta vista')) {
        container.innerHTML = nuevoHtml;
        return;
    }

    // Verificar si el nuevo HTML tiene contenido válido
    const temp = document.createElement('div');
    temp.innerHTML = nuevoHtml;
    const tieneCards = temp.querySelector('.card_comanda') !== null;

    if (!tieneCards) {
        // Si no hay cards pero tampoco es mensaje de error, mostrar mensaje por defecto
        container.innerHTML = `
            <div class='p-4 text-muted text-center'>
                <i class='bi bi-inboxes fs-1 d-block mb-3'></i>No hay comandas en esta vista
            </div>
        `;
        return;
    }

    // Si no hay cambios, no hacer nada
    if (cambios.agregadas === 0 && cambios.modificadas === 0 && cambios.eliminadas === 0) {
        // Pero asegurarse de que el contenido sea el correcto
        if (container.innerHTML !== nuevoHtml) {
            container.innerHTML = nuevoHtml;
        }
        return;
    }

    // Procesar eliminaciones
    if (cambios.idsEliminados.length > 0) {
        cambios.idsEliminados.forEach(id => {
            const cardExistente = container.querySelector(`.card_comanda[data-id="${id}"]`);
            if (cardExistente) {
                cardExistente.remove();
            }
        });
    }

    // Procesar modificaciones
    if (cambios.idsModificados.length > 0) {
        cambios.idsModificados.forEach(id => {
            const cardExistente = container.querySelector(`.card_comanda[data-id="${id}"]`);
            const nuevaCard = temp.querySelector(`.card_comanda[data-id="${id}"]`);

            if (cardExistente && nuevaCard) {
                cardExistente.outerHTML = nuevaCard.outerHTML;
            }
        });
    }

    // Procesar adiciones
    if (cambios.idsAgregados.length > 0) {
        cambios.idsAgregados.forEach(id => {
            const nuevaCard = temp.querySelector(`.card_comanda[data-id="${id}"]`);
            if (nuevaCard) {
                const cardElement = nuevaCard.cloneNode(true);
                container.appendChild(cardElement);
            }
        });
    }

    // Si después de procesar todo el contenedor quedó vacío, mostrar mensaje
    if (container.children.length === 0 ||
        (container.children.length === 1 && container.children[0].classList && !container.children[0].classList.contains('card_comanda'))) {
        container.innerHTML = `
            <div class='p-4 text-muted text-center'>
                <i class='bi bi-inboxes fs-1 d-block mb-3'></i>No hay comandas en esta vista
            </div>
        `;
    }
}


// Función para mostrar error de comandas
function mostrarErrorComandas(mensaje) {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #dc3545;
        color: white;
        padding: 10px 15px;
        border-radius: 8px;
        z-index: 9999;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        font-size: 14px;
        animation: slideInRight 0.3s ease;
    `;
    toast.innerHTML = `
        <i class="bi bi-exclamation-triangle"></i>
        ${mensaje}
    `;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

// Función para inicializar el sistema de recarga inteligente
function inicializarRecargaInteligente() {
    // Asegurar que el contenedor tenga el ID correcto
    const container = document.getElementById('container_lista_comandas');
    if (container) {
        container.id = 'container_lista_comandas';
    }

    // Crear filtro de estado si no existe
    if (!document.getElementById('filtro_estado')) {
        const filtro = document.createElement('input');
        filtro.type = 'hidden';
        filtro.id = 'filtro_estado';
        filtro.value = 'pendiente';
        document.body.appendChild(filtro);
    }

    // Cargar inicialmente
    cargarComandasPendientes();

    // LIMPIAR CUALQUIER INTERVALO EXISTENTE ANTES DE CREAR UNO NUEVO
    if (intervaloActualizacionComandas) {
        clearInterval(intervaloActualizacionComandas);
    }

    // Configurar intervalo de actualización (cada 10 segundos)
    intervaloActualizacionComandas = setInterval(cargarComandasPendientes, 10000);
}

// Función para limpiar el intervalo al salir
function limpiarIntervaloComandas() {
    if (intervaloActualizacionComandas) {
        clearInterval(intervaloActualizacionComandas);
    }
}

// Modificar la función cargarPendientes existente para usar el nuevo sistema
function cargarPendientes() {
    cargarComandasPendientes();
}

// Agregar las animaciones necesarias
function agregarEstilosComandas() {
    if (!document.querySelector('#comandas-styles')) {
        const style = document.createElement('style');
        style.id = 'comandas-styles';
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
            
            .comanda-highlight {
                animation: highlightFade 1.5s ease;
            }
            
            @keyframes highlightFade {
                0% { background-color: #fff3cd; }
                100% { background-color: transparent; }
            }
            
            .contador-comandas {
                font-size: 0.9rem;
                padding: 4px 8px;
                border-radius: 12px;
                background: #e9ecef;
            }
            
            #container_lista_comandas {
                min-height: 200px;
            }

            /* Estilos para el spinner de carga */
            .spinner-border {
                display: inline-block;
                width: 2rem;
                height: 2rem;
                border: 0.25em solid currentColor;
                border-right-color: transparent;
                border-radius: 50%;
                animation: spinner-border .75s linear infinite;
            }

            @keyframes spinner-border {
                to { transform: rotate(360deg); }
            }

            .visually-hidden {
                position: absolute;
                width: 1px;
                height: 1px;
                padding: 0;
                margin: -1px;
                overflow: hidden;
                clip: rect(0,0,0,0);
                border: 0;
            }
        `;
        document.head.appendChild(style);
    }
}


// Función para cargar las comandas pendientes con recarga inteligente
function cargarComandasPendientes() {
    const estado = document.getElementById('filtro_estado')?.value || 'pendiente';
    const container = document.getElementById('container_lista_comandas');

    if (!container) return;

    // GUARDAR LA POSICIÓN ACTUAL DEL SCROLL ANTES DE RECARGAR
    const scrollPos = window.scrollY;

    // También guardar la posición de scroll del contenedor si es un contenedor con scroll interno
    const containerScrollPos = container.scrollTop;

    $.ajax({
        type: "POST",
        url: "../../controller/mesero-lista-comandas.php",
        data: { estado: estado },
        dataType: "html",
        timeout: 30000,
        success: function (html) {
            // Guardar el HTML anterior para comparar (si tienes lógica de cambios)
            const htmlAnterior = container.innerHTML;

            // SIEMPRE reemplazar el contenido directamente
            container.innerHTML = html;

            // RESTAURAR LA POSICIÓN DEL SCROLL
            // Usar requestAnimationFrame para asegurar que el DOM ya se renderizó
            requestAnimationFrame(() => {
                window.scrollTo(0, scrollPos);
                container.scrollTop = containerScrollPos;
            });
        },
        error: function (xhr, status, error) {
            console.error('Error al cargar comandas:', error);
            if (container) {
                container.innerHTML = `
                    <div class='p-4 text-muted text-center'>
                        <i class='bi bi-exclamation-triangle fs-1 d-block mb-3' style='color: #dc3545;'></i>
                        Error al cargar las comandas
                        <button class="btn btn-outline-primary mt-3" onclick="cargarComandasPendientes()">
                            <i class="bi bi-arrow-repeat"></i> Reintentar
                        </button>
                    </div>
                `;
            }
        }
    });
}






document.addEventListener('click', (e) => {
    const card = e.target.closest('.card_comanda');
    if (!card) return;

    const id = card.getAttribute('data-id');
    document.getElementById('modal_detalle_comanda')?.classList.add('visible');
    $("#body_modal_detalle_comanda").html('<div class="text-center text-muted">Cargando...</div>');

    $.ajax({
        type: "POST",
        url: "../../controller/mesero-detalle-comanda.php",
        data: { id },
        success: (html) => $("#body_modal_detalle_comanda").html(html)
    });
});

document.getElementById('close_modal_detalle_comanda')
    ?.addEventListener('click', () => {
        document.getElementById('modal_detalle_comanda')?.classList.remove('visible');
    });

    


// ============= INTEGRACIÓN DEL NUEVO SISTEMA =============

// Inicializar todo cuando el documento esté listo
$(document).ready(function () {
    // Agregar estilos para las animaciones
    agregarEstilosComandas();

    // Inicializar recarga inteligente de comandas
    inicializarRecargaInteligente();
});

// Limpiar al salir (ampliar para incluir ambos intervalos)
window.addEventListener('beforeunload', function () {
    limpiarIntervaloComandas();
    limpiarIntervaloProductos();
});

// Si necesitas recargar manualmente desde otros lugares
window.recargarComandas = function () {
    cargarComandasPendientes();
};

// Manejar clics en los botones de filtro
document.querySelectorAll('.btn_list_comandas').forEach(btn => {
    btn.addEventListener('click', function () {
        // Guardar la posición actual del scroll
        scrollPosition = window.scrollY;

        document.querySelectorAll('.btn_list_comandas').forEach(btn => btn.classList.remove('lc_selected'));
        this.classList.add('lc_selected');

        const texto = this.textContent.trim().toLowerCase();
        let estado = null;

        if (texto === 'pendientes') estado = 'pendiente';
        if (texto === 'finalizados') estado = 'finalizado';
        if (texto === 'todas') estado = 'todos';

        // Actualizar el filtro de estado
        const filtroEstado = document.getElementById('filtro_estado');
        if (filtroEstado) {
            filtroEstado.value = estado || 'pendiente';
        }

        // Mostrar mensaje de carga
        mostrarCargaComandas();

        // Usar el nuevo sistema con filtro - llamar después de un pequeño delay
        setTimeout(() => {
            if (typeof window.cargarComandasPendientes === 'function') {
                window.cargarComandasPendientes();
            }
        }, 100);
    });
});