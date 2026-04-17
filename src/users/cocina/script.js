let intervaloActualizacion;
let scrollPositions = {};
let comandasActuales = []; // Guardar el estado actual de las comandas

// Variables para el sistema de audio
let audioEnabled = false;
let audioCtx = null;
let audioInitialized = false;

// ===============================
// Helpers para detectar items nuevos (EDITADAS)
// ===============================
function buildItemKey(item) {
    const extras = (item.extras || [])
        .map(e => `${e.nombre || ''}:${e.qty || 1}`)
        .sort()
        .join(',');

    const comps = [];
    if (item.componentes && typeof item.componentes === 'object') {
        Object.values(item.componentes).forEach(g => {
            (g.items || []).forEach(c => {
                comps.push(`${g.nombre || ''}:${c.nombre || ''}:${c.kind || ''}`);
            });
        });
    }
    comps.sort();

    // Si existe item.id úsalo; si no, cae a composición
    return `${item.id || ''}|${item.tipo || ''}|${item.nombre || ''}|${item.nota || ''}|${extras}|${comps.join(',')}`;
}

function getComandaItemKeys(comanda) {
    const keys = new Set();
    (comanda?.batches || []).forEach(batch => {
        const seq = batch.seq || 1;
        (batch.items || []).forEach(item => {
            keys.add(`${seq}::${buildItemKey(item)}`);
        });
    });
    return keys;
}

// Función beep usando Web Audio API
function beep() {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;

        // Si no hay contexto o está cerrado, crear uno nuevo
        if (!audioCtx || audioCtx.state === 'closed') {
            audioCtx = new AudioContext();
        }

        // Si el contexto está suspendido, intentar reanudarlo
        if (audioCtx.state === 'suspended') {
            audioCtx.resume().then(() => {
                reproducirBeep();
            }).catch(e => console.error('Error al reanudar AudioContext:', e));
        } else if (audioCtx.state === 'running') {
            reproducirBeep();
        }
    } catch (e) {
        console.error('Error beep:', e);
    }
}

function reproducirBeep() {
    try {
        const duration = 0.5; // medio segundo
        const now = audioCtx.currentTime;

        // Dos osciladores para que suene más fuerte / notorio
        const osc1 = audioCtx.createOscillator();
        const osc2 = audioCtx.createOscillator();
        const gain = audioCtx.createGain();

        osc1.type = 'square';   // más agresivo que 'sine'
        osc2.type = 'square';
        osc1.frequency.value = 1200; // tono 1
        osc2.frequency.value = 1600; // tono 2

        osc1.connect(gain);
        osc2.connect(gain);
        gain.connect(audioCtx.destination);

        // Subimos más el volumen (0.8) con envolvente suave
        gain.gain.setValueAtTime(0.001, now);
        gain.gain.exponentialRampToValueAtTime(0.8, now + 0.05);
        gain.gain.exponentialRampToValueAtTime(0.001, now + duration);

        osc1.start(now);
        osc2.start(now);
        osc1.stop(now + duration);
        osc2.stop(now + duration);
    } catch (e) {
        console.error('Error al reproducir beep:', e);
    }
}

// Función para crear overlay de notificación (YA NO SE USA para nueva/editada)
// La dejo por si después la ocupas, pero no se invoca.
function crearOverlayNotificacion(tipo, comandaId) {
    const overlay = document.createElement('div');
    overlay.className = 'ticket-overlay';
    overlay.style.cssText = `
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.98);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 10;
        border-radius: 12px;
        backdrop-filter: blur(5px);
        border: 3px solid ${tipo === 'nueva' ? '#28a745' : '#ffc107'};
        animation: fadeInOverlay 0.3s ease;
    `;

    // Agregar keyframes para la animación si no existen
    if (!document.querySelector('#overlay-keyframes')) {
        const style = document.createElement('style');
        style.id = 'overlay-keyframes';
        style.textContent = `
            @keyframes fadeInOverlay {
                from { opacity: 0; transform: scale(0.9); }
                to { opacity: 1; transform: scale(1); }
            }
        `;
        document.head.appendChild(style);
    }

    const mensaje = tipo === 'nueva' ? '✨ NUEVA COMANDA' : '📝 COMANDA EDITADA';
    const descripcion = tipo === 'nueva' ? 'Se ha agregado una nueva comanda' : 'La comanda ha sido modificada';

    overlay.innerHTML = `
        <div style="text-align: center; padding: 25px;">
            <div style="font-size: 48px; margin-bottom: 15px;">
                ${tipo === 'nueva' ? '🆕' : '✏️'}
            </div>
            <h2 style="color: ${tipo === 'nueva' ? '#28a745' : '#ffc107'}; margin-bottom: 10px; font-weight: bold; font-size: 24px;">
                ${mensaje}
            </h2>
            <p style="color: #666; margin-bottom: 20px; font-size: 14px;">
                ${descripcion}
            </p>
            <button class="btn ${tipo === 'nueva' ? 'btn-success' : 'btn-warning'} btn-aceptar-overlay" style="padding: 10px 30px; font-weight: bold; border-radius: 25px;">
                ACEPTAR
            </button>
        </div>
    `;
    return overlay;
}

function cargarTickets() {
    guardarScrollPositions();

    $.ajax({
        type: "GET",
        url: "../../controller/cocina-comandas-pendientes.php",
        dataType: "json",
        timeout: 30000,
        success: function (todasLasComandas) {
            if (todasLasComandas.error) {
                console.error('Error:', todasLasComandas.error);
                mostrarError('Error al cargar comandas: ' + todasLasComandas.error);
                return;
            }

            // Filtrar comandas que tengan más de 40 segundos
            const ahora = new Date().getTime() / 100;
            const tiempoMinimo = 216400; // 40 segundos en milisegundos


            const comandasFiltradas = todasLasComandas.filter(comanda => {
                // Usar el campo 'fecha' que es el que tiene la fecha
                if (!comanda.fecha) {
                    console.warn('Comanda sin fecha:', comanda);
                    return false; // No mostrar comandas sin fecha
                }

                // Convertir el string de fecha a objeto Date
                // Formato esperado: "2026-03-03 20:42:31"
                const fechaComanda = new Date(comanda.fecha.replace(' ', 'T') + 'Z');
                const tiempoComanda = fechaComanda.getTime() / 100;
                const diferencia = ahora - tiempoComanda;

                // Incluir comandas que tengan más de 40 segundos
                return diferencia > tiempoMinimo;
            });

            // Verificar si hay comandas nuevas (solo para sonido)
            const hayComandasNuevas = comandasFiltradas.some(nuevaComanda =>
                !comandasActuales.some(actual => actual.id === nuevaComanda.id)
            );

            // Actualizar cards con las comandas filtradas
            actualizarCardsCambiadas(comandasFiltradas);

            // Reproducir sonido si está habilitado y hay comandas nuevas
            if (hayComandasNuevas && audioEnabled) {
                beep();
                if (audioCtx && audioCtx.state !== 'running') {
                    mostrarNotificacionSilenciada();
                }
            }

            // Actualizar comandasActuales con las filtradas para la próxima comparación
            comandasActuales = comandasFiltradas;
        },
        error: function (xhr, status, error) {
            console.error('Error al cargar tickets:', error);
            if (status === 'timeout') {
                mostrarError('Timeout al cargar las comandas');
            } else {
                mostrarError('Error de conexión: ' + error);
            }
        }
    });
}

// Función para mostrar notificación cuando el audio está silenciado por el navegador
function mostrarNotificacionSilenciada() {
    if (document.getElementById('notificacion-audio')) return;

    const notificacion = document.createElement('div');
    notificacion.id = 'notificacion-audio';
    notificacion.style.cssText = `
        position: fixed;
        bottom: 80px;
        right: 20px;
        background: #ffc107;
        color: #000;
        padding: 10px 15px;
        border-radius: 8px;
        z-index: 10000;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        font-size: 14px;
        cursor: pointer;
        animation: slideInRight 0.3s ease;
    `;
    notificacion.innerHTML = `
        <i class="bi bi-info-circle"></i>
        Haz clic en "Audio ON" para activar las notificaciones sonoras
    `;

    document.body.appendChild(notificacion);

    setTimeout(() => {
        notificacion.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notificacion.remove(), 300);
    }, 5000);
}

function mostrarError(mensaje) {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #dc3545;
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        z-index: 9999;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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

function actualizarCardsCambiadas(nuevasComandas) {
    const container = document.getElementById('tickets-container');
    if (!container) return;

    // Si no hay comandas actuales, renderizar todo
    if (comandasActuales.length === 0) {
        renderizarTickets(nuevasComandas);
        setTimeout(restaurarScrollPositionsConAnimacion, 100);
        return;
    }

    // Crear un mapa de comandas actuales para búsqueda rápida
    const mapaComandasActuales = {};
    comandasActuales.forEach(comanda => {
        mapaComandasActuales[comanda.id] = comanda;
    });

    // Identificar comandas nuevas, modificadas y eliminadas
    const comandasNuevas = [];
    const comandasModificadas = [];

    nuevasComandas.forEach(nuevaComanda => {
        const comandaActual = mapaComandasActuales[nuevaComanda.id];

        if (!comandaActual) {
            comandasNuevas.push(nuevaComanda);
        } else if (JSON.stringify(comandaActual) !== JSON.stringify(nuevaComanda)) {
            comandasModificadas.push(nuevaComanda);
        }

        delete mapaComandasActuales[nuevaComanda.id];
    });

    const comandasEliminadas = Object.keys(mapaComandasActuales);

    if (comandasNuevas.length > 0) {
        agregarComandasNuevas(comandasNuevas);
    }

    if (comandasModificadas.length > 0) {
        actualizarComandasModificadas(comandasModificadas);
    }

    if (comandasEliminadas.length > 0) {
        eliminarComandas(comandasEliminadas);
    }
}

function agregarComandasNuevas(comandasNuevas) {
    const container = document.getElementById('tickets-container');

    // Filtro adicional de seguridad
    const ahora = new Date().getTime() / 100;
    const tiempoMinimo = 216400;

    comandasNuevas.forEach(comanda => {
        if (!comanda.fecha) return; // Cambiado de created_at a fecha

        const fechaComanda = new Date(comanda.fecha.replace(' ', 'T') + 'Z');
        const tiempoComanda = fechaComanda.getTime() / 100;
        const diferencia = ahora - tiempoComanda;

        // Solo agregar si tiene más de 40 segundos
        if (diferencia > tiempoMinimo) {
            const cardHtml = crearCardComanda(comanda);

            const temp = document.createElement('div');
            temp.innerHTML = cardHtml;
            const nuevaCard = temp.firstElementChild;

            // Animación de entrada
            nuevaCard.style.opacity = '0';
            nuevaCard.style.transform = 'translateY(20px)';
            nuevaCard.style.transition = 'all 0.3s ease';

            container.appendChild(nuevaCard);

            nuevaCard.offsetHeight;

            setTimeout(() => {
                nuevaCard.style.opacity = '1';
                nuevaCard.style.transform = 'translateY(0)';
            }, 10);

            agregarScrollListenerATicket(nuevaCard);
        }
    });
}

function actualizarComandasModificadas(comandasModificadas) {
    comandasModificadas.forEach(comanda => {
        const cardExistente = document.querySelector(`[data-comanda-id="${comanda.id}"]`)?.closest('.col-12');

        if (cardExistente) {
            const ticketCard = cardExistente.querySelector('.ticket-card');
            const oldTicketBody = ticketCard.querySelector('.ticket-body');

            const scrollTopActual = oldTicketBody ? oldTicketBody.scrollTop : 0;

            // Detectar items nuevos vs estado anterior
            const comandaAnterior = comandasActuales.find(c => c.id === comanda.id);
            const oldKeys = comandaAnterior ? getComandaItemKeys(comandaAnterior) : new Set();
            const newKeys = getComandaItemKeys(comanda);
            const highlightKeys = new Set([...newKeys].filter(k => !oldKeys.has(k)));

            // Verificar si hay items nuevos
            const hayItemsNuevos = highlightKeys.size > 0;

            const nuevoHtml = crearCardComanda(comanda, highlightKeys);

            const temp = document.createElement('div');
            temp.innerHTML = nuevoHtml;
            const nuevaCardContainer = temp.firstElementChild;
            const nuevaTicketCard = nuevaCardContainer.querySelector('.ticket-card');
            const nuevoTicketBody = nuevaTicketCard.querySelector('.ticket-body');

            // Animación de entrada
            nuevaCardContainer.style.opacity = '0';
            nuevaCardContainer.style.transition = 'opacity 0.3s ease';

            cardExistente.parentNode.replaceChild(nuevaCardContainer, cardExistente);

            setTimeout(() => {
                nuevaCardContainer.style.opacity = '1';

                // IMPORTANTE: Aquí decidimos dónde hacer scroll
                if (nuevoTicketBody) {
                    if (hayItemsNuevos) {
                        // Si hay items nuevos, scroll instantáneo a la parte superior
                        nuevoTicketBody.scrollTop = 0;
                    } else {
                        // Si no hay nuevos, restaurar la posición anterior
                        nuevoTicketBody.scrollTop = scrollTopActual;
                    }
                }
            }, 10);

            agregarScrollListenerATicket(nuevaCardContainer);
        }
    });
}

function eliminarComandas(comandasEliminadas) {
    comandasEliminadas.forEach(comandaId => {
        const cardExistente = document.querySelector(`[data-comanda-id="${comandaId}"]`)?.closest('.col-12');

        if (cardExistente) {
            cardExistente.style.opacity = '0';
            cardExistente.style.transform = 'translateY(20px)';
            cardExistente.style.transition = 'all 0.3s ease';

            setTimeout(() => {
                if (cardExistente.parentNode) {
                    cardExistente.parentNode.removeChild(cardExistente);
                    cargarTickets();
                }
            }, 300);
        }
    });
}

// Función para agregar event listener de scroll a un ticket específico
function agregarScrollListenerATicket(cardElement) {
    const ticketBody = cardElement.querySelector('.ticket-body');
    if (ticketBody) {
        ticketBody.removeEventListener('scroll', manejarScrollTicket);
        ticketBody.addEventListener('scroll', manejarScrollTicket);
    }
}

// Manejador de scroll para tickets
function manejarScrollTicket(event) {
    const target = event.currentTarget;
    if (target && target.dataset && target.dataset.comandaId) {
        scrollPositions[target.dataset.comandaId] = target.scrollTop;
    }
}

// Función para crear overlay de comanda completada
function crearOverlayCompletado(comandaId) {
    const overlay = document.createElement('div');
    overlay.className = 'ticket-overlay completado';
    overlay.style.cssText = `
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(40, 167, 69, 0.98);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 10;
        border-radius: 12px;
        backdrop-filter: blur(5px);
        border: 3px solid #28a745;
        animation: fadeInOverlay 0.3s ease;
        color: white;
    `;

    overlay.innerHTML = `
        <div style="text-align: center; padding: 25px;">
            <div style="font-size: 48px; margin-bottom: 15px;">
                ✅
            </div>
            <h2 style="color: white; margin-bottom: 10px; font-weight: bold; font-size: 24px;">
                ¡COMANDA COMPLETADA!
            </h2>
            <p style="color: rgba(255,255,255,0.9); margin-bottom: 20px; font-size: 14px;">
                La comanda ha sido marcada como completada
            </p>
        </div>
    `;

    return overlay;
}

// Función para marcar comanda como completada
function marcarComandaCompletada(comandaId, button) {
    const btnOriginal = $(button);
    btnOriginal.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Procesando...');

    $.ajax({
        type: "POST",
        url: "../../controller/cocina-completar-comanda.php",
        data: { comanda_id: comandaId },
        dataType: "json",
        success: function (response) {
            if (response.success) {
                const cardExistente = $(`[data-comanda-id="${comandaId}"]`).closest('.col-12');
                const ticketCard = cardExistente.find('.ticket-card');

                ticketCard.css('position', 'relative');

                const overlay = crearOverlayCompletado(comandaId);
                ticketCard.append(overlay);

                const badge = cardExistente.find('.estado-badge');
                badge.removeClass().addClass('estado-badge estado-completado').text('completado');
            } else {
                alert('Error: ' + (response.error || 'No se pudo completar la comanda'));
                btnOriginal.prop('disabled', false).text('Completar');
            }
        },
        error: function (xhr, status, error) {
            console.error('Error al completar comanda:', error);
            alert('Error de conexión al completar la comanda');
            btnOriginal.prop('disabled', false).text('Completar');
        }
    });
}

// ===============================
// Crear card (ahora soporta highlightKeys)
// ===============================
function crearCardComanda(comanda, highlightKeys = new Set()) {
    let html = `
        <div class="col-12 col-sm-6 col-md-4 col-xl-3 p-3">
            <div class="ticket-card" style="height: 100%;">
                <div class="ticket-header">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="mb-1">Comanda #${comanda.id}</h5>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            ${(comanda.cliente_info == 'Para Llevar' ? '<div class="small rounded bg-warning" style="padding: 2px 5px; color: #000000;">Para Llevar</span>' : '<div class="small opacity-75">'+comanda.cliente_info+'</span>') || '<div class="small opacity-75">Cliente General</span>'}</div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between small mt-2">
                        <span class="text-truncate"><i class="bi bi-person"></i> ${comanda.mesero || 'No asignado'}</span>
                        <span class="text-truncate"><i class="bi bi-clock"></i> ${comanda.fecha ? new Date(comanda.fecha).toLocaleTimeString() : 'Fecha no disponible'}</span>
                    </div>
                </div>
                
                <div class="ticket-body" style="height: calc(100% - 127px); overflow-y: auto; scrollbar-width: none;" data-comanda-id="${comanda.id}">
    `;

    if (comanda.batches && comanda.batches.length > 0) {
        comanda.batches.forEach(batch => {
            html += `
                <div class="batch-section">
                    <div class="d-flex justify-content-between small text-muted mb-2">
                        <span><i class="bi bi-list-ol"></i> ${batch.seq || 1}° Orden</span>
                        <span>${batch.created_at ? new Date(batch.created_at).toLocaleTimeString() : ''}</span>
                    </div>
            `;

            if (batch.items && batch.items.length > 0) {
                batch.items.forEach(item => {
                    const itemKey = `${batch.seq || 1}::${buildItemKey(item)}`;
                    const isNuevo = highlightKeys.has(itemKey);

                    html += `
                        <div class="product-item ${isNuevo ? 'nuevo-item' : ''}" data-item-key="${itemKey}">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>${item.nombre || 'Producto'}</strong>
                                    ${item.tipo === 'combo' ? '<span class="combo-badge">COMBO</span>' : ''}
                                </div>
                            </div>
                    `;

                    // Componentes del combo
                    if (item.componentes && Object.keys(item.componentes).length > 0) {
                        html += `<div class="componente-group mt-1">`;
                        Object.values(item.componentes).forEach(grupo => {
                            if (grupo.nombre) {
                                html += `<div class="small fw-bold text-muted">${grupo.nombre}:</div>`;
                            }
                            if (grupo.items && grupo.items.length > 0) {
                                grupo.items.forEach(comp => {
                                    if (item.tipo !== 'product') {
                                        html += `
                                            <div class="small ms-2">
                                                <i class="bi bi-check-circle-fill ${comp.kind === 'incluido' ? 'text-success' : 'text-primary'}" style="font-size:0.7rem;"></i>
                                                ${comp.nombre || 'Componente'}
                                            </div>
                                        `;
                                    }
                                });
                            }
                        });
                        html += `</div>`;
                    }

                    // Extras
                    if (item.extras && item.extras.length > 0) {
                        html += `<div class="extra-item mt-1">`;
                        item.extras.forEach(extra => {
                            html += `
                                <div class="d-flex justify-content-between small">
                                    <span>+ ${extra.nombre || 'Extra'} x${extra.qty || 1}</span>
                                </div>
                            `;
                        });
                        html += `</div>`;
                    }

                    // Nota
                    if (item.nota) {
                        html += `
                            <div class="nota-box small">
                                <i class="bi bi-chat-text"></i> ${item.nota}
                            </div>
                        `;
                    }

                    html += `</div>`;
                });
            }

            html += `</div>`;
        });
    } else {
        html += `<div class="text-center text-muted py-4">No hay productos en esta comanda</div>`;
    }

    html += `
                </div>
                <div style="height: 36px; padding: 0px 10px;"> `;
    
    if (comanda.estado !== 'finalizado' && comanda.estado !== 'completado') {
        html += `
                            <button class="btn btn-sm btn-success btn-completar" 
                                    onclick="marcarComandaCompletada(${comanda.id}, this)"
                                    style="width: 100%; height: 100%;">
                                <i class="bi bi-check-circle"></i> Completar
                            </button>
        `;
    }

    html += `   </div>
            </div>
        </div>
    `;

    return html;
}

// Funciones de scroll
function guardarScrollPositions() {
    scrollPositions = {};
    const cards = document.querySelectorAll('.ticket-card');

    cards.forEach((card, index) => {
        const ticketBody = card.querySelector('.ticket-body');
        if (ticketBody) {
            const comandaId = ticketBody.dataset.comandaId || index;
            scrollPositions[comandaId] = ticketBody.scrollTop;
        }
    });
}

function restaurarScrollPositionsConAnimacion() {
    const cards = document.querySelectorAll('.ticket-card');

    cards.forEach((card, index) => {
        const ticketBody = card.querySelector('.ticket-body');
        if (ticketBody) {
            const comandaId = ticketBody.dataset.comandaId || index;
            if (scrollPositions[comandaId] !== undefined) {
                scrollSuave(ticketBody, scrollPositions[comandaId], 200);
            }
        }
    });
}

function scrollSuave(elemento, posicionFinal, duracion) {
    if (!elemento) return;

    const posicionInicial = elemento.scrollTop;
    const distancia = posicionFinal - posicionInicial;
    const startTime = performance.now();

    function animacionScroll(currentTime) {
        const tiempoTranscurrido = currentTime - startTime;
        const progreso = Math.min(tiempoTranscurrido / duracion, 1);

        const easing = t => t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t;

        elemento.scrollTop = posicionInicial + (distancia * easing(progreso));

        if (tiempoTranscurrido < duracion) {
            requestAnimationFrame(animacionScroll);
        }
    }

    requestAnimationFrame(animacionScroll);
}

function scrollToPrimerNuevoItem(ticketBody, duracion = 400) {
    if (!ticketBody) return;

    const primerNuevo = ticketBody.querySelector('.product-item.nuevo-item');
    if (!primerNuevo) return; // no hay nuevos, no hacemos nada

    // Posición del item dentro del contenedor scrollable
    const top = primerNuevo.offsetTop - 8; // pequeño margen arriba
    scrollSuave(ticketBody, Math.max(top, 0), duracion);
}

// Función para inicializar el sistema de audio
function inicializarSistemaAudio() {
    const container = document.getElementById('tickets-container');
    const btnAudio = document.getElementById('btnAudioBarra');

    if (!container || !btnAudio) return;

    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (AudioContext && !audioCtx) {
        audioCtx = new AudioContext();
        if (audioCtx.state === 'running') {
            audioCtx.suspend();
        }
    }

    btnAudio.addEventListener('click', function () {
        audioEnabled = !audioEnabled;

        if (audioEnabled) {
            if (audioCtx && audioCtx.state === 'suspended') {
                audioCtx.resume().then(() => {
                    beep();
                }).catch(e => console.error('Error al reanudar AudioContext:', e));
            } else if (!audioCtx) {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                audioCtx = new AudioContext();
                beep();
            } else {
                beep();
            }

            btnAudio.classList.remove('btn-danger');
            btnAudio.classList.add('btn-success');
            btnAudio.innerHTML = '<i class="bi bi-volume-up"></i>';
        } else {
            if (audioCtx && audioCtx.state === 'running') {
                audioCtx.suspend();
            }

            btnAudio.classList.remove('btn-success');
            btnAudio.classList.add('btn-danger');
            btnAudio.innerHTML = '<i class="bi bi-volume-mute"></i>';
        }
    });

    document.addEventListener('click', function initAudioOnFirstClick() {
        if (audioCtx && audioCtx.state === 'suspended' && audioEnabled) {
            audioCtx.resume().catch(e => console.log('No se pudo reanudar AudioContext'));
        }
        document.removeEventListener('click', initAudioOnFirstClick);
    }, { once: true });
}

// Función para inicializar todos los listeners de scroll
function inicializarScrollListeners() {
    document.querySelectorAll('.col-12').forEach(card => {
        agregarScrollListenerATicket(card);
    });
}

function formatMoney(n) {
    if (n === undefined || n === null) return '0.00';
    return Number(n).toFixed(2);
}

// Iniciar actualización automática
$(document).ready(function () {
    cargarTickets();
    intervaloActualizacion = setInterval(cargarTickets, 10000);
    inicializarSistemaAudio();
    setTimeout(inicializarScrollListeners, 500);
    agregarEstilosGlobales();
});

// Función para agregar estilos globales
function agregarEstilosGlobales() {
    const estilos = `
        <style>
            .ticket-card {
                background: white;
                border-radius: 12px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                overflow: hidden;
                transition: box-shadow 0.3s ease;
            }
            
            .ticket-header {
                padding: 15px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
            }
            
            .ticket-body {
                padding: 15px;
                background: #f8f9fa;
            }
            
            .batch-section {
                margin-bottom: 20px;
                padding: 10px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }
            
            .product-item {
                margin-bottom: 10px;
                padding: 8px;
                border-left: 3px solid #667eea;
                background: #fff;
                border-radius: 4px;
            }

            /* NUEVO: items nuevos en comanda editada */
            .product-item.nuevo-item {
                border-left: 3px solid #dc3545 !important;
                background: #ffe5e7 !important;
            }
            @keyframes pulseNuevoItem {
                0% { transform: scale(1); }
                50% { transform: scale(1.01); }
                100% { transform: scale(1); }
            }
            .product-item.nuevo-item {
                animation: pulseNuevoItem 0.8s ease-in-out 2;
            }
            
            .combo-badge {
                background: #ffc107;
                color: #000;
                font-size: 10px;
                padding: 2px 6px;
                border-radius: 4px;
                margin-left: 8px;
                font-weight: bold;
            }
            
            .componente-group {
                margin-left: 8px;
                padding-left: 8px;
                border-left: 2px dashed #dee2e6;
            }
            
            .extra-item {
                background: #e3f2fd;
                padding: 5px 8px;
                border-radius: 4px;
                margin-top: 5px;
            }
            
            .nota-box {
                background: #fff3cd;
                padding: 5px 8px;
                border-radius: 4px;
                margin-top: 5px;
                color: #856404;
            }
            
            .estado-badge {
                padding: 4px 12px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 500;
                text-transform: capitalize;
            }
            
            .estado-pendiente {
                background: #ffc107;
                color: #000;
            }
            
            .estado-en-proceso {
                background: #17a2b8;
                color: white;
            }
            
            .estado-completado {
                background: #28a745;
                color: white;
            }
            
            .gap-2 {
                gap: 0.5rem;
            }
            
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
            
            /* Scrollbar personalizado */
            .ticket-body::-webkit-scrollbar {
                width: 6px;
            }
            
            .ticket-body::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 10px;
            }
            
            .ticket-body::-webkit-scrollbar-thumb {
                background: #c1c1c1;
                border-radius: 10px;
            }
            
            .ticket-body::-webkit-scrollbar-thumb:hover {
                background: #a8a8a8;
            }
        </style>
    `;

    document.head.insertAdjacentHTML('beforeend', estilos);
}

// Limpiar intervalo al salir
window.addEventListener('beforeunload', function () {
    if (intervaloActualizacion) {
        clearInterval(intervaloActualizacion);
    }
});

// Función renderizarTickets (para carga inicial)
function renderizarTickets(todasLasComandas) {
    const container = document.getElementById('tickets-container');
    if (!container) return;

    // Filtrar comandas que tengan más de 40 segundos
    const ahora = new Date().getTime() / 100;
    const tiempoMinimo = 216400; // 40 segundos en milisegundos

    const comandas = todasLasComandas.filter(comanda => {
        if (!comanda.fecha) return false; // Cambiado de created_at a fecha

        // Convertir el string de fecha correctamente
        const fechaComanda = new Date(comanda.fecha.replace(' ', 'T') + 'Z');
        const tiempoComanda = fechaComanda.getTime() / 100;
        const diferencia = ahora - tiempoComanda;

        return diferencia > tiempoMinimo;
    });

    if (!comandas || comandas.length === 0) {
        container.innerHTML = `
            <div class="col-12">
                <div class="alert alert-info text-center d-flex align-items-center justify-content-center flex-column" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; height: 100%; border: none; border-radius: 0px;">
                    <i class="bi bi-info-circle" style="font-size: 3rem;"></i>
                    <h4 style="margin-top: 10px;">No hay comandas pendientes</h4>
                    <p>Las nuevas comandas aparecerán aquí automáticamente.</p>
                </div>
            </div>
        `;
        return;
    }

    let html = '';
    comandas.forEach(comanda => {
        html += crearCardComanda(comanda);
    });

    container.innerHTML = html;

    setTimeout(inicializarScrollListeners, 100);
}

document.addEventListener('click', e => {
    if (e.target.id === 'open_modal_outproducts') {
        document.getElementById('modal_out_products')?.classList.add('visible');
        $.ajax({
            type: "POST",
            url: "../../controller/cocina-productos-agotados.php",
            data: {},
            success: function (response) {
                $('#body_modal_outproductos').html(response);
            }
        });
    }
});

document.addEventListener('click', e => {
    if (e.target.id === 'close_modal_outproducts') {
        document.getElementById('modal_out_products')?.classList.remove('visible');
    }
});

// Función para manejar los switches con mejor feedback
document.addEventListener('change', function (e) {
    if (e.target && e.target.classList.contains('switch-item')) {
        let switchElement = e.target;
        let tipo = switchElement.dataset.tipo;
        let id = switchElement.dataset.id;
        let estado = switchElement.checked ? 0 : 1;

        switchElement.disabled = true;

        $.ajax({
            type: "POST",
            url: "../../controller/actualizar-disponibilidad.php",
            data: { tipo: tipo, id: id, estado: estado },
            dataType: 'json',
            timeout: 5000,

            success: function (response) {
                switchElement.disabled = false;
                if (!response.success) {
                    switchElement.checked = !switchElement.checked;
                }
            },

            error: function (xhr, status, error) {
                switchElement.disabled = false;
                switchElement.checked = !switchElement.checked;
            }
        });
    }
});

// Función para filtrar en tiempo real
document.addEventListener('input', function (e) {
    if (e.target && e.target.id === 'search-varprod') {
        let searchValue = e.target.value.toLowerCase().trim();

        let combosContainer = document.getElementById('combos-container');
        let productosContainer = document.getElementById('productos-container');
        let extrasContainer = document.getElementById('extras-container');

        let titleCombos = document.getElementById('title-seccion-combos');
        let titleProductos = document.getElementById('title-seccion-productos');
        let titleExtras = document.getElementById('title-seccion-extras');

        if (!combosContainer || !productosContainer || !extrasContainer) return;

        let combosWrappers = combosContainer.querySelectorAll('.combo-wrapper');
        let productosWrappers = productosContainer.querySelectorAll('.producto-wrapper');
        let extrasWrappers = extrasContainer.querySelectorAll('.extra-wrapper');

        let combosVisibles = 0;
        let productosVisibles = 0;
        let extrasVisibles = 0;

        if (searchValue === '') {
            combosWrappers.forEach(wrapper => { wrapper.style.display = 'block'; });
            combosVisibles = combosWrappers.length;

            productosWrappers.forEach(wrapper => {
                wrapper.style.display = 'block';
                let variantes = wrapper.querySelectorAll('.variante-item');
                variantes.forEach(v => v.style.display = 'flex');
            });
            productosVisibles = productosWrappers.length;

            extrasWrappers.forEach(wrapper => { wrapper.style.display = 'block'; });
            extrasVisibles = extrasWrappers.length;

        } else {
            // === FILTRAR COMBOS ===
            combosWrappers.forEach(wrapper => {
                let comboTexto = wrapper.dataset.searchable || '';
                if (comboTexto.includes(searchValue)) {
                    wrapper.style.display = 'block';
                    combosVisibles++;
                } else {
                    wrapper.style.display = 'none';
                }
            });

            // === FILTRAR PRODUCTOS Y VARIANTES ===
            productosWrappers.forEach(wrapper => {
                let variantes = wrapper.querySelectorAll('.variante-item');
                let productoTexo = wrapper.dataset.searchable || '';
                let productoVisible = productoTexo.includes(searchValue);

                let variantesVisibles = 0;
                variantes.forEach(variante => {
                    let varianteTexto = variante.dataset.searchable || '';
                    if (varianteTexto.includes(searchValue)) {
                        variante.style.display = 'flex';
                        variantesVisibles++;
                    } else {
                        variante.style.display = 'none';
                    }
                });

                if (productoVisible || variantesVisibles > 0) {
                    wrapper.style.display = 'block';
                    productosVisibles++;
                    if (productoVisible) {
                        variantes.forEach(v => v.style.display = 'flex');
                    }
                } else {
                    wrapper.style.display = 'none';
                }
            });

            // === FILTRAR EXTRAS ===
            extrasWrappers.forEach(wrapper => {
                let extraTexto = wrapper.dataset.searchable || '';
                if (extraTexto.includes(searchValue)) {
                    wrapper.style.display = 'block';
                    extrasVisibles++;
                } else {
                    wrapper.style.display = 'none';
                }
            });
        }

        if (combosVisibles > 0) titleCombos.style.display = 'block';
        else titleCombos.style.display = 'none';

        if (productosVisibles > 0) titleProductos.style.display = 'block';
        else titleProductos.style.display = 'none';

        if (extrasVisibles > 0) titleExtras.style.display = 'block';
        else titleExtras.style.display = 'none';

        let totalVisibles = combosVisibles + productosVisibles + extrasVisibles;

        document.querySelectorAll('.no-results-message').forEach(el => el.remove());

        if (totalVisibles === 0 && searchValue !== '') {
            $('#text-no-reults').html(`<div class="no-results-message text-center p-3 bg-light rounded">No se encontraron resultados para "${searchValue}"</div>`);
        } else {
            $('#text-no-reults').html('');
        }
    }
});



document.addEventListener('click', e => {
    if (e.target.id === 'open_modal_history') {
        document.getElementById('modal_history')?.classList.add('visible');
        $.ajax({
            type: "POST",
            url: "../../controller/cocina-history-comandas.php",
            data: {rol: 'cocina'},
            success: function (response) {
                $('#body_modal_history').html(response);
            }
        });
    }
});

document.addEventListener('click', e => {
    if (e.target.id === 'close_modal_history') {
        document.getElementById('modal_history')?.classList.remove('visible');
    }
});