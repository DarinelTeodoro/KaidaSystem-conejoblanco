// Variables para el sistema de recarga inteligente de comandas
let intervaloActualizacionComandas;
let filtroActual = 'pendiente'; // Valor por defecto
let ultimaActualizacionCompletada = true;

function cobrar_denegado(comanda) {
    show_alert('Error', 'Aun hay platillos en preparacion de la COMANDA #' + comanda);
}

// Función para cargar las comandas pendientes con recarga inteligente
function cargarComandasPendientes(forzar = false) {
    // Evitar múltiples llamadas simultáneas
    if (!ultimaActualizacionCompletada && !forzar) {
        return;
    }

    // CORREGIDO: Obtener el valor del filtro
    const filtroEstado = document.getElementById('filtro_estado');
    let estado = filtroEstado ? filtroEstado.value : 'pendiente';

    // IMPORTANTE: Mapear los valores del filtro a los estados correctos para el backend
    // Esto asume que tu backend espera: 'pendiente', 'finalizado', o 'todas'
    if (estado === 'por_cobrar') estado = 'pendiente';  // Si usas 'por_cobrar' en el filtro
    if (estado === 'cobrados') estado = 'finalizado';   // Si usas 'cobrados' en el filtro
    if (estado === 'todos') estado = 'todas';           // Si usas 'todos' en el filtro

    const container = document.getElementById('container_lista_comandas');

    if (!container) return;

    // Actualizar el filtro actual
    filtroActual = estado;

    // Marcar que estamos actualizando
    ultimaActualizacionCompletada = false;

    if (forzar || container.children.length === 0) {
        mostrarCargaComandas();
    }


    // Agregar timestamp para evitar caché
    const timestamp = Date.now();

    $.ajax({
        type: "POST",
        url: "../../controller/caja-lista-comandas.php",
        data: {
            estado: estado,
            _: timestamp // Evitar caché
        },
        dataType: "html",
        timeout: 30000,
        success: function (html) {
            // Verificar si el HTML es válido
            if (html && html.trim().length > 0) {
                container.innerHTML = html;

                // Disparar evento personalizado
                document.dispatchEvent(new CustomEvent('comandas-actualizadas', {
                    detail: { estado: estado, timestamp: timestamp }
                }));
            } else {
                container.innerHTML = `
                    <div class='p-4 text-muted text-center'>
                        <i class='bi bi-inboxes fs-1 d-block mb-3'></i>
                        No hay comandas en esta vista
                    </div>
                `;
            }

            ultimaActualizacionCompletada = true;
        },
        error: function (xhr, status, error) {
            console.error('Error al cargar comandas:', error);

            if (status !== 'abort') {
                container.innerHTML = `
                    <div class='p-4 text-muted text-center'>
                        <i class='bi bi-exclamation-triangle fs-1 d-block mb-3' style='color: #dc3545;'></i>
                        Error al cargar las comandas
                        <button class="btn btn-outline-primary mt-3" onclick="cargarComandasPendientes(true)">
                            <i class="bi bi-arrow-repeat"></i> Reintentar
                        </button>
                    </div>
                `;
            }

            ultimaActualizacionCompletada = true;
        }
    });
}

// Función para inicializar la recarga automática
function inicializarRecargaAutomatica(intervalo = 10000) {
    // Limpiar intervalo existente
    if (intervaloActualizacionComandas) {
        clearInterval(intervaloActualizacionComandas);
    }

    // Cargar inmediatamente
    cargarComandasPendientes(true);

    // Configurar nuevo intervalo
    intervaloActualizacionComandas = setInterval(() => {
        // Solo recargar si la pestaña está activa
        if (!document.hidden) {
            cargarComandasPendientes();
        }
    }, intervalo);
}

// Función para detener la recarga automática
function detenerRecargaAutomatica() {
    if (intervaloActualizacionComandas) {
        clearInterval(intervaloActualizacionComandas);
        intervaloActualizacionComandas = null;
        console.log('Recarga automática detenida');
    }
}

// Escuchar cambios de visibilidad de la pestaña
document.addEventListener('visibilitychange', function () {
    if (document.hidden) {

    } else {
        cargarComandasPendientes(true);
    }
});

// Función para inicializar todo el sistema de comandas
function inicializarSistemaComandas() {
    // Crear filtro de estado si no existe
    if (!document.getElementById('filtro_estado')) {
        const filtro = document.createElement('input');
        filtro.type = 'hidden';
        filtro.id = 'filtro_estado';
        filtro.value = 'pendiente'; // CORREGIDO: Usar 'cobrar' en lugar de 'pendiente'
        document.body.appendChild(filtro);
    }

    // Inicializar recarga automática (cada 10 segundos)
    inicializarRecargaAutomatica(10000);
}

// Modificar la función mostrarCargaComandas para que sea más amigable
function mostrarCargaComandas(forzar = false) {
    const container = document.getElementById('container_lista_comandas');
    if (container) {
        // SI ES FORZADO (por cambio de filtro), mostrar el spinner siempre
        if (forzar) {
            container.innerHTML = `
                <div class='p-4 text-muted text-center'>
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <div>Cargando comandas...</div>
                </div>
            `;
            return; // Salimos para no ejecutar la condición de abajo
        }

        // Solo mostrar carga si el contenedor está vacío o tiene mensaje de error (comportamiento original)
        const tieneError = container.innerHTML.includes('exclamation-triangle');
        const estaVacio = container.children.length === 0 ||
            container.innerHTML.includes('No hay comandas');

        if (tieneError || estaVacio) {
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
}

document.getElementById('close_modal_cobrarcomanda')
    ?.addEventListener('click', () => {
        document.getElementById('detalles_comanda')?.classList.remove('visible');
        // Limpiar variables globales al cerrar
        window.detalleComandaActual = null;
        window.itemsComandaActual = null;
        cuentasSeparadas = [];
    });

document.getElementById('close_modal_metodopago')
    ?.addEventListener('click', () => {
        document.getElementById('modal_metodo_pago')?.classList.remove('visible');
    });

function cobrar_comanda(idcomanda) {
    document.getElementById('detalles_comanda').classList.add('visible');

    const modalBody = document.getElementById('body_modal_detallescomanda');
    modalBody.innerHTML = '<div class="p-3 text-center fs-5 d-flex align-items-center justify-content-center" style="height: 100%; width: 100%;">Cargando...</div>';

    // Cargar detalles de la comanda
    $.ajax({
        type: "POST",
        url: "../../controller/caja-detalle-cobro.php",
        data: { id: idcomanda },
        dataType: "json",
        success: function (data) {
            renderDetalleCobro(data);
        },
        error: function () {
            modalBody.innerHTML = '<div class="p-3 text-center text-danger">Error al cargar los detalles</div>';
        }
    });
}

function renderDetalleCobro(data) {
    const modalBody = document.getElementById('body_modal_detallescomanda');
    const comanda = data.comanda;
    const total = data.total;

    // Guardar los datos en variables globales para acceder después
    window.detalleComandaActual = data;
    window.itemsComandaActual = [];

    // Procesar items para tenerlos disponibles
    data.batches.forEach(batch => {
        batch.items.forEach(item => {
            // Calcular el subtotal correcto incluyendo extras
            let subtotalConExtras = item.subtotal; // Este ya debería incluir extras según tu backend

            // También podemos calcularlo manualmente por si acaso
            if (item.extras && item.extras.length > 0) {
                const extrasTotal = item.extras.reduce((sum, ex) => sum + (ex.total || 0), 0);
                subtotalConExtras = (item.precio * item.qty) + extrasTotal;
            }

            window.itemsComandaActual.push({
                id: item.id,
                nombre: item.nombre,
                qty: item.qty,
                precio: item.precio,
                precio_unitario: item.precio, // Precio sin extras
                subtotal: subtotalConExtras, // Subtotal CON extras incluidos
                subtotal_sin_extras: item.precio * item.qty, // Subtotal sin extras
                extras: item.extras || [], // Guardar los extras completos
                tipo: item.tipo,
                componentes: item.componentes || {},
                nota: item.nota || ''
            });
        });
    });

    let html = `
        <div class="p-2 pe-1 ps-1">
            <div class="mb-3" align="center">
                <div class="fw-bold fs-5" id="id-comanda-selected">Comanda #${comanda.id}</div>
                <div>
                    <div><span class="text-uppercase text-danger fw-bold">${comanda.tipo === 'mesa' ? 'Mesa ' + comanda.mesa : comanda.cliente}</span></div> 
                    <div><span>${data.mesero} | </span><span class="text-muted">${new Date(comanda.created_at).toLocaleDateString()} ${new Date(comanda.created_at).toLocaleTimeString()}</span></div>
                </div>
            </div>
            <div class="line"></div>
            <div class="mb-3">
                <div>
    `;

    // Mostrar productos agrupados por batch
    data.batches.forEach(batch => {
        html += `<div class="mb-2 p-2" style="background: #f8f9fa; border-radius: 5px;">`;
        html += `<small class="text-muted">${batch.seq}° Orden - ${batch.created_at}</small>`;

        batch.items.forEach(item => {
            html += `
                <div class="p-2 mt-2" style="background: white; border: 1px solid #dee2e6; border-radius: 5px;">
                    <div class="d-flex justify-content-between">
                        <div>
                            <span class="fw-bold">${item.nombre}</span>
                            <span class="text-muted"> x${item.qty}</span>
                        </div>
                        <div class="fw-bold">$${formatMoney(item.precio)}</div>
                    </div>
            `;

            // Componentes del combo
            if (item.tipo === 'combo' && Object.keys(item.componentes).length > 0) {
                Object.entries(item.componentes).forEach(([grupo, productos]) => {
                    html += `
                        <div class="ms-3 mt-1">
                            <small class="text-muted fw-bold">${grupo}:</small>
                            <div class="ms-2">
                                ${productos.map(p => `
                                    <div><small>${p.kind === 'incluido' ? '✓' : '•'} ${p.nombre}</small></div>
                                `).join('')}
                            </div>
                        </div>
                    `;
                });
            }

            // Extras
            if (item.extras.length > 0) {
                html += `
                    <div class="ms-3 mt-1">
                        <small class="text-muted fw-bold">Extras:</small>
                        ${item.extras.map(ex => `
                            <div class="d-flex justify-content-between">
                                <small>+ ${ex.nombre} x${ex.qty}</small>
                                <small>$${formatMoney(ex.total)}</small>
                            </div>
                        `).join('')}
                    </div>
                `;
            }

            // Nota
            /*if (item.nota) {
                html += `<div class="mt-1 p-1" style="background: #fff3cd; border-radius: 3px;"><small>📝 ${item.nota}</small></div>`;
            }*/

            html += `
                    <div class="text-end mt-1">
                        <small class="text-muted">Subtotal: </small>
                        <span class="fw-bold text-success">$${formatMoney(item.subtotal)}</span>
                    </div>
                </div>
            `;
        });

        html += `</div>`;
    });

    let botonesxcobro = '';
    if (comanda.estado === 'finalizado') {
        botonesxcobro += `
        <div class="mb-3" align="center">
            <div class="d-grid col-6 col-md-6 col-xl-6">
                <button type="button" class="btn btn-outline-success" onclick="ver_ticket(${comanda.id})">
                    <i class="bi bi-receipt"></i> Ticket
                </button>
            </div>
        </div>
        `;
    } else {
        botonesxcobro += `
        <div class="mb-3">
            <div class="mb-2 text-center">Selecciona método de pago:</div>
                <div class="row g-3 pt-3 justify-content-center">
                    <div class="d-grid col-6 col-md-6 col-xl-6">
                        <button type="button" class="btn btn-outline-success" onclick="mostrarPagoEfectivo(${total})">
                            <i class="bi bi-cash"></i> Efectivo
                        </button>
                    </div>
                    <div class="d-grid col-6 col-md-6 col-xl-6">
                        <button type="button" class="btn btn-outline-primary" onclick="mostrarPagoTarjeta(${total})">
                            <i class="bi bi-credit-card"></i> Tarjeta
                        </button>
                    </div>
                    <div class="d-grid col-6 col-md-6 col-xl-6">
                        <button type="button" class="btn btn-outline-danger" onclick="mostrarPagoMixto(${total})">
                            <i class="bi bi-cash-stack"></i> Mixto
                        </button>
                    </div>
                    <div class="d-grid col-12 col-md-6 col-xl-6">
                        <button type="button" class="btn btn-outline-secondary" onclick="mostrarCuentasSeparadas(${total})">
                            <i class="bi bi-person-lines-fill"></i> Cuentas separadas
                        </button>
                    </div>
                    <div class="d-grid col-6 col-md-6 col-xl-6">
                        <button type="button" class="btn btn-outline-dark" onclick="ver_ticket(${comanda.id})">
                            <i class="bi bi-receipt"></i> Recibo
                        </button>
                    </div>
                </div>
            </div>`;
    }

    html += `
                </div>
            </div>
            
            <div class="mb-3 p-2" style="background: #e9ecef; border-radius: 5px;">
                <div class="d-flex justify-content-between fw-bold fs-5">
                    <span>TOTAL:</span>
                    <span class="text-primary">$${formatMoney(total)}</span>
                </div>
            </div>

            ${botonesxcobro}
        </div>
    `;

    modalBody.innerHTML = html;
}

// Funciones para mostrar las diferentes formas de pago
/***************************************************************************EFECTIVO************************************************************/
function mostrarPagoEfectivo(total) {
    document.getElementById('modal_metodo_pago')?.classList.add('visible');
    const container = document.getElementById('body_modal_metodopago');
    const title = document.getElementById('text_metodo_pago');

    title.innerHTML = '---- ------';
    container.innerHTML = '<div class="p-3 text-center">Cargando...</div>'

    setTimeout(() => {
        title.innerHTML = 'Pago en Efectivo';
        container.innerHTML = `
            <div class="p-0 pt-3">
                <div class="mb-2 d-flex align-items-center">
                    <input type="hidden" id="aux-total-comanda" value="${formatMoney(total)}">
                    <span class="text-muted me-2">Total de la comanda:</span><b class="text-success fs-5">$${formatMoney(total)}</b>
                </div>
                <div class="mb-2">
                    <label class="form-label">Propina</label>
                    <select class="form-select" id="tipo-propina-efectivo" onchange="togglePropinaEfectivo()">
                        <option value="0">Sin propina</option>
                        <option value="porcentaje">Porcentaje (%)</option>
                        <option value="monto">Monto fijo ($)</option>
                    </select>
                </div>
                <div id="propina-efectivo-container"></div>
                <div class="mb-2">
                    <label class="form-label">Total con Propina</label>
                    <div class="fw-bold text-success fs-5" id="plus-propina">$${formatMoney(total)}</div>
                </div>
                <div class="mb-2">
                    <label class="form-label">Monto recibido ($)</label>
                    <input type="number" class="form-control" id="monto-recibido" min="0" step="0.01" oninput="calcularCambioEfectivo(${total})">
                </div>
                <div class="mb-2">
                    <label class="form-label">Cambio/Faltante:</label>
                    <div class="fw-bold text-success fs-5" id="cambio-efectivo">$0.00</div>
                </div>
                <button type="button" class="btn btn-success w-100" onclick="procesarPagoEfectivo(${total})">Cobrar</button>
            </div>
        `;
    }, 300);
}

function togglePropinaEfectivo() {
    const tipo = document.getElementById('tipo-propina-efectivo').value;
    const container = document.getElementById('propina-efectivo-container');
    const total = parseFloat(document.getElementById('aux-total-comanda').value);

    if (tipo === 'porcentaje') {
        container.innerHTML = `
            <div class="mb-2">
                <label class="form-label">Porcentaje de propina (%)</label>
                <input type="number" class="form-control" id="propina-porcentaje" min="0" max="100" step="0.1" oninput="calcularCambioEfectivo(${total})">
            </div>
        `;
    } else if (tipo === 'monto') {
        container.innerHTML = `
            <div class="mb-2">
                <label class="form-label">Monto de propina ($)</label>
                <input type="number" class="form-control" id="propina-monto" min="0" step="0.01" oninput="calcularCambioEfectivo(${total})">
            </div>
        `;
    } else {
        container.innerHTML = '';
    }
}

function calcularCambioEfectivo(total) {
    const montoRecibido = parseFloat(document.getElementById('monto-recibido').value) || 0;
    let propina = 0;

    const tipoPropina = document.getElementById('tipo-propina-efectivo')?.value;

    if (tipoPropina === 'porcentaje') {
        const porcentaje = parseFloat(document.getElementById('propina-porcentaje')?.value) || 0;
        propina = total * (porcentaje / 100);
    } else if (tipoPropina === 'monto') {
        propina = parseFloat(document.getElementById('propina-monto')?.value) || 0;
    }

    const totalConPropina = total + propina;
    const cambio = montoRecibido - totalConPropina;

    document.getElementById('plus-propina').textContent = '$' + formatMoney(totalConPropina);

    if (cambio < 0) {
        document.getElementById('cambio-efectivo').classList.remove('text-success');
        document.getElementById('cambio-efectivo').classList.add('text-danger');
        document.getElementById('cambio-efectivo').textContent = '$' + formatMoney(cambio * -1);
    } else if (cambio >= 0) {
        document.getElementById('cambio-efectivo').classList.remove('text-danger');
        document.getElementById('cambio-efectivo').classList.add('text-success');
        document.getElementById('cambio-efectivo').textContent = '$' + formatMoney(cambio);
    }
}

/***************************************************************************TARJETA************************************************************/
function mostrarPagoTarjeta(total) {
    document.getElementById('modal_metodo_pago')?.classList.add('visible');
    const container = document.getElementById('body_modal_metodopago');
    const title = document.getElementById('text_metodo_pago');

    title.innerHTML = '---- ------';
    container.innerHTML = '<div class="p-3 text-center">Cargando...</div>'

    setTimeout(() => {
        title.innerHTML = 'Pago con Tarjeta';
        container.innerHTML = `
            <div class="p-0 pt-3">
                <div class="mb-2 d-flex align-items-center">
                    <input type="hidden" id="aux-total-comanda" value="${formatMoney(total)}">
                    <span class="text-muted me-2">Total de la comanda:</span><b class="text-primary fs-5">$${formatMoney(total)}</b>
                </div>
                <div class="mb-2">
                    <label class="form-label">Propina</label>
                    <select class="form-select" id="tipo-propina-tarjeta" onchange="togglePropinaTarjeta(${total})">
                        <option value="0">Sin propina</option>
                        <option value="porcentaje">Porcentaje (%)</option>
                        <option value="monto">Monto fijo ($)</option>
                    </select>
                </div>
                <div id="propina-tarjeta-container"></div>
                <div class="mb-2">
                    <label class="form-label">Total a pagar:</label>
                    <div class="fw-bold text-primary fs-5" id="total-tarjeta">$${formatMoney(total)}</div>
                </div>
                <button type="button" class="btn btn-primary w-100" onclick="procesarPagoTarjeta(${total})">Cobrar</button>
            </div>
        `;
    }, 300);
}

function togglePropinaTarjeta(total) {
    const tipo = document.getElementById('tipo-propina-tarjeta').value;
    const container = document.getElementById('propina-tarjeta-container');
    const totalElement = document.getElementById('total-tarjeta');

    if (tipo === 'porcentaje') {
        container.innerHTML = `
            <div class="mb-2">
                <label class="form-label">Porcentaje de propina (%)</label>
                <input type="number" class="form-control" id="propina-porcentaje-tarjeta" min="0" max="100" step="0.1" oninput="actualizarTotalTarjeta(${total})">
            </div>
        `;
    } else if (tipo === 'monto') {
        container.innerHTML = `
            <div class="mb-2">
                <label class="form-label">Monto de propina ($)</label>
                <input type="number" class="form-control" id="propina-monto-tarjeta" min="0" step="0.01" oninput="actualizarTotalTarjeta(${total})">
            </div>
        `;
    } else {
        container.innerHTML = '';
        totalElement.textContent = '$' + formatMoney(total);
    }
}

function actualizarTotalTarjeta(total) {
    const tipo = document.getElementById('tipo-propina-tarjeta').value;
    let propina = 0;

    if (tipo === 'porcentaje') {
        const porcentaje = parseFloat(document.getElementById('propina-porcentaje-tarjeta')?.value) || 0;
        propina = total * (porcentaje / 100);
    } else if (tipo === 'monto') {
        propina = parseFloat(document.getElementById('propina-monto-tarjeta')?.value) || 0;
    }

    const totalConPropina = total + propina;
    document.getElementById('total-tarjeta').textContent = '$' + formatMoney(totalConPropina);
}

/***************************************************************************MIXTO************************************************************/
function mostrarPagoMixto(total) {
    document.getElementById('modal_metodo_pago')?.classList.add('visible');
    const container = document.getElementById('body_modal_metodopago');
    const title = document.getElementById('text_metodo_pago');

    title.innerHTML = '---- ------';
    container.innerHTML = '<div class="p-3 text-center">Cargando...</div>'

    setTimeout(() => {
        title.innerHTML = 'Pago Mixto (Efectivo + Tarjeta)';
        container.innerHTML = `
            <div class="p-0 pt-3">
                <div class="mb-2 d-flex align-items-center">
                    <input type="hidden" id="aux-total-comanda" value="${formatMoney(total)}">
                    <span class="text-muted me-2">Total de la comanda:</span><b class="text-danger fs-5">$${formatMoney(total)}</b>
                </div>
                <div class="mb-2">
                    <label class="form-label">Propina</label>
                    <select class="form-select" id="tipo-propina-mixto" onchange="togglePropinaMixto(${total})">
                        <option value="0">Sin propina</option>
                        <option value="porcentaje">Porcentaje (%)</option>
                        <option value="monto">Monto fijo ($)</option>
                    </select>
                </div>
                <div id="propina-mixto-container"></div>
                <div class="mb-2">
                    <label class="form-label">Monto en efectivo ($)</label>
                    <input type="number" class="form-control" id="monto-efectivo-mixto" min="0" step="0.01" oninput="calcularRestanteTarjeta(${total})">
                </div>
                <div class="mb-2">
                    <label class="form-label">Restante a pagar con tarjeta:</label>
                    <div class="fs-5 fw-bold text-danger" id="restante-tarjeta">$${formatMoney(total)}</div>
                </div>
                <button type="button" class="btn btn-danger w-100" onclick="procesarPagoMixto(${total})">Cobrar</button>
            </div>
        `;
    }, 300);
}

function togglePropinaMixto(total) {
    const tipo = document.getElementById('tipo-propina-mixto').value;
    const container = document.getElementById('propina-mixto-container');

    if (tipo === 'porcentaje') {
        container.innerHTML = `
            <div class="mb-2">
                <label class="form-label">Porcentaje de propina (%)</label>
                <input type="number" class="form-control" id="propina-porcentaje-mixto" min="0" max="100" step="0.1" oninput="calcularRestanteTarjeta(${total})">
            </div>
        `;
    } else if (tipo === 'monto') {
        container.innerHTML = `
            <div class="mb-2">
                <label class="form-label">Monto de propina ($)</label>
                <input type="number" class="form-control" id="propina-monto-mixto" min="0" step="0.01" oninput="calcularRestanteTarjeta(${total})">
            </div>
        `;
    } else {
        container.innerHTML = '';
    }
}

function calcularRestanteTarjeta(total) {
    const montoEfectivo = parseFloat(document.getElementById('monto-efectivo-mixto')?.value) || 0;
    const tipoPropina = document.getElementById('tipo-propina-mixto')?.value;
    let propina = 0;

    if (tipoPropina === 'porcentaje') {
        const porcentaje = parseFloat(document.getElementById('propina-porcentaje-mixto')?.value) || 0;
        propina = total * (porcentaje / 100);
    } else if (tipoPropina === 'monto') {
        propina = parseFloat(document.getElementById('propina-monto-mixto')?.value) || 0;
    }

    const totalConPropina = total + propina;
    const restante = Math.max(0, totalConPropina - montoEfectivo);

    document.getElementById('restante-tarjeta').textContent = '$' + formatMoney(restante);
}

/***************************************************************************CUENTAS SEPARADAS************************************************************/
// Variable global para almacenar las cuentas
let cuentasSeparadas = [];

function mostrarCuentasSeparadas(total) {
    document.getElementById('modal_metodo_pago')?.classList.add('visible');
    const container = document.getElementById('body_modal_metodopago');
    const title = document.getElementById('text_metodo_pago');

    title.innerHTML = '---- ------';
    container.innerHTML = '<div class="p-3 text-center">Cargando...</div>'

    setTimeout(() => {
        title.innerHTML = 'Cuentas Separadas';

        // Resetear cuentas
        cuentasSeparadas = [];

        const html = `
            <div class="p-0">
                <div class="mb-3">
                    <div class="mb-1 sticky-top pt-2 pb-2" align="end" style="background: #ffffff;">
                        <button type="button" class="btn btn-sm btn-dark border border-2 border-dark" onclick="agregarNuevaCuenta()">
                            <i class="bi bi-plus-circle"></i> Nueva Cuenta
                        </button>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">Total comanda:</span>
                        <span class="fw-bold"><span class="text-success" id="total-comanda-original">$${formatMoney(total)}</span></span>
                    </div>
                    
                    <div id="cuentas-container" class="mb-3">
                        <!-- Aquí se agregarán las cuentas dinámicamente -->
                    </div>
                    
                    <div class="mt-3 p-2" style="background: #e9ecef; border-radius: 5px;">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Total a pagar:</span>
                            <span class="fw-bold text-primary" id="total-cuentas">$${formatMoney(0)}</span>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid">
                    <button type="button" class="btn btn-success" onclick="procesarCuentasSeparadas()">Procesar Pagos</button>
                </div>
            </div>
        `;

        container.innerHTML = html;

        // Inicializar con una cuenta por defecto
        agregarNuevaCuenta(false);

        // Renderizar la lista de productos disponibles
        setTimeout(() => {
            renderizarProductosParaCuentas();
        }, 100);
    }, 300);
}

function agregarNuevaCuenta(mostrarAlerta = true) {
    const cuentaId = Date.now() + Math.random();
    const nuevaCuenta = {
        id: cuentaId,
        items: [],
        metodoPago: 'efectivo',
        propina: {
            tipo: '0',
            valor: 0
        }
    };

    cuentasSeparadas.push(nuevaCuenta);
    renderizarCuentas();

    // Actualizar la lista de productos para mostrar las nuevas opciones de cuenta
    actualizarSelectsProductos();

    if (mostrarAlerta) {
        show_alert('EXITO', 'Nueva cuenta agregada');
    }
}


// Modificar eliminarCuenta para devolver los productos a la lista disponible
function eliminarCuenta(cuentaId) {
    // Obtener la cuenta antes de eliminarla
    const cuenta = cuentasSeparadas.find(c => c.id === cuentaId);

    if (cuenta && cuenta.items.length > 0) {
        // Los productos de esta cuenta volverán a estar disponibles automáticamente
        // cuando se renderice la lista de productos nuevamente
        //console.log(`Devolviendo ${cuenta.items.length} productos a la lista disponible`);
        show_alert('CUENTA ELIMINADA', 'Se devolvieron ' + cuenta.items.length + ' producto(s) a la lista disponible.');
    } else {
        show_alert('EXITO', 'Cuenta eliminada.');
    }

    cuentasSeparadas = cuentasSeparadas.filter(c => c.id !== cuentaId);
    renderizarCuentas();
    actualizarTotalCuentas();

    // Regenerar completamente la lista de productos disponibles
    renderizarProductosParaCuentas();
}

// Nueva función para quitar un producto específico de una cuenta
function quitarProductoDeCuenta(cuentaId, itemId) {
    const cuenta = cuentasSeparadas.find(c => c.id === cuentaId);
    if (!cuenta) return;

    // Eliminar el producto de la cuenta
    cuenta.items = cuenta.items.filter(item => item.id !== itemId);

    // Actualizar la UI
    renderizarCuentas(cuentaId);
    actualizarTotalCuentas();

    // Regenerar la lista de productos disponibles para que aparezca el producto quitado
    renderizarProductosParaCuentas();
    const inputRecibido = document.getElementById(`monto-recibido-${cuentaId}`);
    actualizarMontoRecibidoCuenta(cuentaId, inputRecibido.value)
}

// Versión modificada - ahora acepta un ID de cuenta opcional
function renderizarCuentas(cuentaIdEspecifica = null) {
    const container = document.getElementById('cuentas-container');
    if (!container) return;

    if (cuentasSeparadas.length === 0) {
        container.innerHTML = '<div class="text-center text-muted p-3">No hay cuentas agregadas</div>';
        return;
    }

    // Si se especifica una cuenta, solo actualizar esa
    if (cuentaIdEspecifica) {
        const cuenta = cuentasSeparadas.find(c => c.id === cuentaIdEspecifica);
        if (!cuenta) return;

        const index = cuentasSeparadas.findIndex(c => c.id === cuentaIdEspecifica);
        const cuentaElement = document.querySelector(`[data-cuenta-id="${cuentaIdEspecifica}"]`);

        if (cuentaElement) {
            // Reemplazar solo el HTML de esa cuenta
            const newCuentaHtml = generarHtmlCuenta(cuenta, index);
            cuentaElement.outerHTML = newCuentaHtml;
        }
        return;
    }

    // Si no hay ID específico, renderizar todas (solo al inicio o al agregar/eliminar)
    let html = '';
    cuentasSeparadas.forEach((cuenta, index) => {
        html += generarHtmlCuenta(cuenta, index);
    });
    container.innerHTML = html;
    actualizarTotalCuentas();
}

// Nueva función auxiliar para generar HTML de una sola cuenta
function generarHtmlCuenta(cuenta, index) {
    const totalCuenta = calcularTotalCuenta(cuenta);
    const color = getColorPorIndex(index);

    return `
        <div class="card mb-3" style="border: 2px solid #${color};" data-cuenta-id="${cuenta.id}">
            <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #${color}20;">
                <span class="fw-bold">Cuenta Individual</span>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarCuenta(${cuenta.id})">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <small class="text-muted">Productos seleccionados:</small>
                    <div id="items-cuenta-${cuenta.id}" class="mt-1">
                        ${renderizarItemsCuenta(cuenta)}
                    </div>
                </div>
                
                <div class="row mb-2">
                    <div class="col">
                        <label class="form-label">Método de pago</label>
                        <select class="form-select" onchange="cambiarMetodoPagoCuenta(${cuenta.id}, this.value)">
                            <option value="efectivo" ${cuenta.metodoPago === 'efectivo' ? 'selected' : ''}>Efectivo</option>
                            <option value="tarjeta" ${cuenta.metodoPago === 'tarjeta' ? 'selected' : ''}>Tarjeta</option>
                        </select>
                    </div>
                    <div class="col">
                        <label class="form-label">Propina</label>
                        <select class="form-select" onchange="cambiarTipoPropinaCuenta(${cuenta.id}, this.value)">
                            <option value="0" ${cuenta.propina.tipo === '0' ? 'selected' : ''}>Sin propina</option>
                            <option value="porcentaje" ${cuenta.propina.tipo === 'porcentaje' ? 'selected' : ''}>Porcentaje (%)</option>
                            <option value="monto" ${cuenta.propina.tipo === 'monto' ? 'selected' : ''}>Monto fijo ($)</option>
                        </select>
                    </div>
                </div>
                
                <div id="propina-cuenta-${cuenta.id}" class="mb-2">
                    ${renderizarPropinaCuenta(cuenta)}
                </div>
                
                ${cuenta.metodoPago === 'efectivo' ? `
                    <div class="mb-2">
                        <label class="form-label">Monto recibido ($)</label>
                        <input type="number" class="form-control" id="monto-recibido-${cuenta.id}" 
                               min="0" step="0.01" value="${cuenta.montoRecibido || ''}" 
                               oninput="actualizarMontoRecibidoCuenta(${cuenta.id}, this.value)">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Cambio/Faltante:</label>
                        <span class="fw-bold text-success" id="cambio-cuenta-${cuenta.id}">$${formatMoney(calcularCambioCuenta(cuenta))}</span>
                    </div>
                ` : ''}
                
                <div class="d-flex justify-content-between fw-bold mt-2 pt-2" style="border-top: 1px dashed #dee2e6;">
                    <span>Total cuenta:</span>
                    <span class="text-primary" id="total-cuenta-${cuenta.id}">$${formatMoney(totalCuenta)}</span>
                </div>
            </div>
        </div>
    `;
}

function getColorPorIndex(index) {
    const colores = ['0d6efd', '198754', 'dc3545', 'ffc107', '0dcaf0', '6f42c1'];
    return colores[index % colores.length];
}

// Modificar renderizarItemsCuenta para agregar botón de quitar
function renderizarItemsCuenta(cuenta) {
    if (cuenta.items.length === 0) {
        return '<div class="text-muted small">Ningún producto seleccionado</div>';
    }

    return cuenta.items.map(item => `
        <div class="d-flex justify-content-between align-items-center small mb-1 p-1" style="border-bottom: 1px dotted #dee2e6;">
            <div style="flex: 1;">
                <span class="fw-bold">${item.nombre} x${item.qty}</span>
                ${item.extras && item.extras.length > 0 ?
            `<div class="text-muted" style="font-size:0.75rem;">${item.extras.map(e => `${e.nombre} x${e.qty}`).join(', ')}</div>`
            : ''}
            </div>
            <div class="d-flex align-items-center">
                <span class="text-success me-2">$${formatMoney(item.subtotal)}</span>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="quitarProductoDeCuenta(${cuenta.id}, ${item.id})">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        </div>
    `).join('');
}

function renderizarPropinaCuenta(cuenta) {
    if (cuenta.propina.tipo === 'porcentaje') {
        return `
            <div>
                <label class="form-label">Porcentaje de propina (%)</label>
                <input type="number" class="form-control" id="propina-porcentaje-${cuenta.id}" 
                       min="0" max="100" step="0.1" value="${cuenta.propina.valor || ''}"
                       oninput="actualizarPropinaCuenta(${cuenta.id})">
            </div>
        `;
    } else if (cuenta.propina.tipo === 'monto') {
        return `
            <div>
                <label class="form-label">Monto de propina ($)</label>
                <input type="number" class="form-control" id="propina-monto-${cuenta.id}" 
                       min="0" step="0.01" value="${cuenta.propina.valor || ''}"
                       oninput="actualizarPropinaCuenta(${cuenta.id})">
            </div>
        `;
    }
    return '';
}

// Modificar renderizarProductosParaCuentas para asignar IDs únicos a cada producto
function renderizarProductosParaCuentas() {
    const container = document.getElementById('cuentas-container');
    if (!container || !window.itemsComandaActual || window.itemsComandaActual.length === 0) {
        return;
    }

    // Verificar si ya existe la lista de productos y eliminarla si es necesario
    const existingProductList = document.getElementById('productos-cuentas-container');
    if (existingProductList) {
        existingProductList.remove();
    }

    // Crear la lista de productos disponibles
    const productosHtml = document.createElement('div');
    productosHtml.id = 'productos-cuentas-container';
    productosHtml.className = 'mb-3 p-2';
    productosHtml.style.background = '#f8f9fa';
    productosHtml.style.borderRadius = '5px';
    productosHtml.style.maxHeight = '200px';
    productosHtml.style.overflowY = 'auto';

    let productosContent = '<div class="fw-bold mb-2"><i class="text-muted" style="font-size: 0.8rem;">Selecciona los productos para cada cuenta:</i></div>';

    // Filtrar productos que ya están asignados a alguna cuenta
    const productosDisponibles = window.itemsComandaActual.filter(item => {
        return !cuentasSeparadas.some(cuenta =>
            cuenta.items.some(i => i.id === item.id)
        );
    });

    if (productosDisponibles.length === 0) {
        productosContent += '<div class="text-muted text-center p-2">No hay productos disponibles</div>';
    } else {
        productosDisponibles.forEach(item => {
            // Construir el detalle de extras si existen
            let extrasDetalle = '';
            if (item.extras && item.extras.length > 0) {
                extrasDetalle = `
                    <div class="ms-3 small text-muted">
                        ${item.extras.map(ex =>
                    `+ ${ex.nombre} x${ex.qty}`
                ).join('<br>')}
                    </div>
                `;
            }

            productosContent += `
                <div class="p-2" style="border-bottom: 1px solid #000000;" id="producto-container-${item.id}">
                    <div>
                        <span class="fw-bold">${item.nombre}</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div>${extrasDetalle}</div>
                        <div class="lh-1">
                            <div class="text-center text-muted" style="font-size: 0.8rem;">Total:</div>
                            <div class="fw-bold fs-5 text-success">$${formatMoney(item.subtotal)}</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mt-2">
                        <div class="col-8 d-grid">
                            <select class="form-select form-select-sm" id="cuenta-select-${item.id}" style="width: auto; display: inline-block; margin-right: 5px;">
                                <option value="">Seleccionar cuenta</option>
                                ${cuentasSeparadas.map((cuenta, index) =>
                `<option value="${cuenta.id}">Cuenta ${index + 1}</option>`
            ).join('')}
                            </select>
                        </div>
                        <div class="col-4 d-grid">
                            <button type="button" class="btn btn-primary" onclick="agregarItemACuenta(${item.id})" style="height: 38px;">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
    }

    productosHtml.innerHTML = productosContent;

    // Insertar antes del contenedor de cuentas
    container.parentNode.insertBefore(productosHtml, container);
}

function actualizarSelectsProductos() {
    // Actualizar los selects de productos para reflejar las nuevas cuentas
    if (!window.itemsComandaActual) return;

    window.itemsComandaActual.forEach(item => {
        const select = document.getElementById(`cuenta-select-${item.id}`);
        if (select) {
            // Guardar el valor seleccionado actual
            const currentValue = select.value;

            // Regenerar opciones
            let options = '<option value="">Seleccionar cuenta</option>';
            cuentasSeparadas.forEach((cuenta, index) => {
                const selected = cuenta.id == currentValue ? 'selected' : '';
                options += `<option value="${cuenta.id}" ${selected}>Cuenta ${index + 1}</option>`;
            });

            select.innerHTML = options;
        }
    });
}

// Modificar agregarItemACuenta para que realmente elimine el producto de la lista visual
function agregarItemACuenta(itemId) {
    const select = document.getElementById(`cuenta-select-${itemId}`);
    if (!select) return;

    const cuentaId = parseFloat(select.value);

    if (!cuentaId) {
        show_alert('ALERTA', 'Selecciona una cuenta primero');
        return;
    }

    // Buscar el item en los items de la comanda
    const itemOriginal = window.itemsComandaActual.find(item => item.id === itemId);

    if (itemOriginal) {
        const cuenta = cuentasSeparadas.find(c => c.id === cuentaId);
        if (cuenta) {
            // Verificar si el item ya está en alguna cuenta
            const itemEnOtraCuenta = cuentasSeparadas.some(c =>
                c.id !== cuentaId && c.items.some(i => i.id === itemId)
            );

            if (itemEnOtraCuenta) {
                show_alert('ALERTA', 'Este producto ya está asignado a otra cuenta');
                return;
            }

            // Verificar si el item ya está en esta cuenta
            const itemEnEstaCuenta = cuenta.items.some(i => i.id === itemId);
            if (itemEnEstaCuenta) {
                show_alert('ALERTA', 'Este producto ya está en esta cuenta');
                return;
            }

            cuenta.items.push(itemOriginal);
            renderizarCuentas(cuentaId);

            // Eliminar el producto de la lista de disponibles (remover todo el contenedor padre)
            const productoContainer = document.getElementById(`producto-container-${itemId}`);
            if (productoContainer) {
                productoContainer.remove();
            } else {
                // Si no encuentra por ID, buscar por el select
                const productoDiv = select.closest('.p-2');
                if (productoDiv) {
                    productoDiv.remove();
                }
            }

            const inputRecibido = document.getElementById(`monto-recibido-${cuentaId}`);
            actualizarMontoRecibidoCuenta(cuentaId, inputRecibido.value)
        }
    }
}

function cambiarMetodoPagoCuenta(cuentaId, metodo) {
    const cuenta = cuentasSeparadas.find(c => c.id === cuentaId);
    if (cuenta) {
        // Guardar el método anterior para saber si hubo cambio
        const metodoAnterior = cuenta.metodoPago;
        cuenta.metodoPago = metodo;

        // Guardar el monto recibido SOLO si estábamos en efectivo y existe el input
        if (metodoAnterior === 'efectivo') {
            const inputRecibido = document.getElementById(`monto-recibido-${cuentaId}`);
            if (inputRecibido) {
                cuenta.montoRecibido = inputRecibido.value;
            }
        }

        // Actualizar la UI de esta cuenta
        renderizarCuentas(cuentaId);

        // Si el nuevo método es efectivo, restaurar el valor guardado
        if (metodo === 'efectivo' && cuenta.montoRecibido) {
            // Usar setTimeout para asegurar que el input ya existe en el DOM
            setTimeout(() => {
                const nuevoInput = document.getElementById(`monto-recibido-${cuentaId}`);
                if (nuevoInput) {
                    nuevoInput.value = cuenta.montoRecibido;
                    // Recalcular el cambio con el valor restaurado
                    actualizarMontoRecibidoCuenta(cuentaId, cuenta.montoRecibido);
                }
            }, 0);
        }
    }
}

function cambiarTipoPropinaCuenta(cuentaId, tipo) {
    const cuenta = cuentasSeparadas.find(c => c.id === cuentaId);
    if (cuenta) {
        cuenta.propina.tipo = tipo;
        cuenta.propina.valor = 0;
        renderizarCuentas(cuentaId);
        const nuevoInput = document.getElementById(`monto-recibido-${cuentaId}`);
        actualizarMontoRecibidoCuenta(cuentaId, nuevoInput.value);
    }

    actualizarTotalCuentas();
}

function actualizarPropinaCuenta(cuentaId) {
    const cuenta = cuentasSeparadas.find(c => c.id === cuentaId);
    if (!cuenta) return;

    if (cuenta.propina.tipo === 'porcentaje') {
        const input = document.getElementById(`propina-porcentaje-${cuentaId}`);
        cuenta.propina.valor = parseFloat(input.value) || 0;
    } else if (cuenta.propina.tipo === 'monto') {
        const input = document.getElementById(`propina-monto-${cuentaId}`);
        cuenta.propina.valor = parseFloat(input.value) || 0;
    }

    // Actualizar total de la cuenta
    const totalElement = document.getElementById(`total-cuenta-${cuentaId}`);
    if (totalElement) {
        totalElement.textContent = '$' + formatMoney(calcularTotalCuenta(cuenta));
    }

    // Si es efectivo, actualizar cambio
    if (cuenta.metodoPago === 'efectivo') {
        const cambioElement = document.getElementById(`cambio-cuenta-${cuentaId}`);
        if (cambioElement) {
            const montoRecibido = parseFloat(document.getElementById(`monto-recibido-${cuentaId}`)?.value) || 0;
            const total = calcularTotalCuenta(cuenta);
            const cambio = montoRecibido - total;
            actualizarTextoCambio(cambioElement, cambio);
        }
    }

    actualizarTotalCuentas();
}

function actualizarMontoRecibidoCuenta(cuentaId, valor) {
    const cuenta = cuentasSeparadas.find(c => c.id === cuentaId);
    if (!cuenta) return;

    cuenta.montoRecibido = valor;
    const montoRecibido = parseFloat(valor) || 0;
    const total = calcularTotalCuenta(cuenta);
    const cambio = montoRecibido - total;

    const cambioElement = document.getElementById(`cambio-cuenta-${cuentaId}`);
    if (cambioElement) {
        actualizarTextoCambio(cambioElement, cambio);
    }
}

function actualizarTextoCambio(element, cambio) {
    if (cambio < 0) {
        element.classList.remove('text-success');
        element.classList.add('text-danger');
        element.textContent = '$' + formatMoney(cambio * -1);
    } else {
        element.classList.remove('text-danger');
        element.classList.add('text-success');
        element.textContent = '$' + formatMoney(cambio);
    }
}

function calcularTotalCuenta(cuenta) {
    const subtotal = cuenta.items.reduce((sum, item) => sum + (item.subtotal || 0), 0);

    let propina = 0;
    if (cuenta.propina.tipo === 'porcentaje') {
        propina = subtotal * (cuenta.propina.valor / 100);
    } else if (cuenta.propina.tipo === 'monto') {
        propina = cuenta.propina.valor;
    }

    return subtotal + propina;
}

function calcularCambioCuenta(cuentaId) {
    const cuenta = cuentasSeparadas.find(c => c.id === cuentaId);
    if (!cuenta || cuenta.metodoPago !== 'efectivo') return;

    const montoRecibido = parseFloat(document.getElementById(`monto-recibido-${cuentaId}`).value) || 0;
    const total = calcularTotalCuenta(cuenta);
    const cambio = montoRecibido - total;

    if (cambio < 0) {
        document.getElementById(`cambio-cuenta-${cuentaId}`).classList.remove('text-success');
        document.getElementById(`cambio-cuenta-${cuentaId}`).classList.add('text-danger');
        document.getElementById(`cambio-cuenta-${cuentaId}`).textContent = '$' + formatMoney(cambio * -1);
    } else {
        document.getElementById(`cambio-cuenta-${cuentaId}`).classList.remove('text-danger');
        document.getElementById(`cambio-cuenta-${cuentaId}`).classList.add('text-success');
        document.getElementById(`cambio-cuenta-${cuentaId}`).textContent = '$' + formatMoney(cambio);
    }

    //document.getElementById(`cambio-cuenta-${cuentaId}`).textContent = '$' + formatMoney(Math.max(0, cambio));
}

function actualizarTotalCuentas() {
    const totalCuentas = cuentasSeparadas.reduce((sum, cuenta) => sum + calcularTotalCuenta(cuenta), 0);
    const totalElement = document.getElementById('total-cuentas');
    if (totalElement) {
        totalElement.textContent = '$' + formatMoney(totalCuentas);
    }
}

function cancelarCuentasSeparadas() {
    if (cuentasSeparadas.length > 0) {
        if (confirm('¿Estás seguro de cancelar? Se perderán las cuentas creadas.')) {
            cuentasSeparadas = [];
            document.getElementById('modal_metodo_pago')?.classList.remove('visible');
        }
    } else {
        document.getElementById('modal_metodo_pago')?.classList.remove('visible');
    }
}

// Funciones auxiliares
function obtenerTotalComanda() {
    const totalElement = document.getElementById('total-comanda-original');
    if (totalElement) {
        return parseFloat(totalElement.textContent.replace('$', ''));
    }
    return 0;
}

function obtenerComandaId() {
    const titleElement = document.getElementById('id-comanda-selected');
    if (titleElement) {
        const match = titleElement.textContent.match(/#(\d+)/);
        return match ? parseInt(match[1]) : 0;
    }
    return 0;
}

function formatMoney(n) {
    return Number(n).toFixed(2);
}

// Funciones de procesamiento de pagos
function procesarPagoEfectivo(total) {
    const tipoPropina = document.getElementById('tipo-propina-efectivo').value;
    const montoRecibido = parseFloat(document.getElementById('monto-recibido').value) || 0;
    let propinaValor = 0;
    let propinaCalculada = 0;

    if (tipoPropina === 'porcentaje') {
        propinaValor = parseFloat(document.getElementById('propina-porcentaje').value) || 0;
        propinaCalculada = total * (propinaValor / 100);
    } else if (tipoPropina === 'monto') {
        propinaValor = parseFloat(document.getElementById('propina-monto').value) || 0;
        propinaCalculada = propinaValor;
    }

    const montoTotal = total + propinaCalculada;
    const cambio = montoRecibido - montoTotal;

    if (cambio < 0) {
        show_alert('ALERTA', 'Hace falta para $' + formatMoney(cambio * -1) + ' pesos completar el pago');
        return;
    }

    const pagoData = {
        comanda_id: obtenerComandaId(),
        tipo_pago: 'simple',
        metodo: 'efectivo',
        total_comanda: total,
        monto: montoTotal,
        propina_tipo: tipoPropina,
        propina_valor: propinaValor,
        propina_calculada: propinaCalculada,
        monto_recibido: montoRecibido,
        cambio: cambio
    };

    enviarPago(pagoData);
}

function procesarPagoTarjeta(total) {
    const tipoPropina = document.getElementById('tipo-propina-tarjeta').value;
    let propinaValor = 0;
    let propinaCalculada = 0;

    if (tipoPropina === 'porcentaje') {
        propinaValor = parseFloat(document.getElementById('propina-porcentaje-tarjeta').value) || 0;
        propinaCalculada = total * (propinaValor / 100);
    } else if (tipoPropina === 'monto') {
        propinaValor = parseFloat(document.getElementById('propina-monto-tarjeta').value) || 0;
        propinaCalculada = propinaValor;
    }

    const referencia = '0000';

    const pagoData = {
        comanda_id: obtenerComandaId(),
        tipo_pago: 'simple',
        metodo: 'tarjeta',
        total_comanda: total,
        monto: total + propinaCalculada,
        propina_tipo: tipoPropina,
        propina_valor: propinaValor,
        propina_calculada: propinaCalculada,
        referencia_tarjeta: referencia
    };

    enviarPago(pagoData);
}

function procesarPagoMixto(total) {
    const tipoPropina = document.getElementById('tipo-propina-mixto').value;
    const montoEfectivo = parseFloat(document.getElementById('monto-efectivo-mixto').value) || 0;
    let propinaValor = 0;
    let propinaCalculada = 0;

    if (tipoPropina === 'porcentaje') {
        propinaValor = parseFloat(document.getElementById('propina-porcentaje-mixto').value) || 0;
        propinaCalculada = total * (propinaValor / 100);
    } else if (tipoPropina === 'monto') {
        propinaValor = parseFloat(document.getElementById('propina-monto-mixto').value) || 0;
        propinaCalculada = propinaValor;
    }

    const totalConPropina = total + propinaCalculada;
    const montoTarjeta = Math.max(0, totalConPropina - montoEfectivo);

    if (montoEfectivo == 0) {
        show_alert('ALERTA', 'El metodo de pago no es mixto, es pago con "TARJETA"')
        return;
    }

    if (montoTarjeta == 0) {
        show_alert('ALERTA', 'El metodo de pago no es mixto, es pago con "EFECTIVO"')
        return;
    }

    let referenciaTarjeta = null;
    if (montoTarjeta > 0) {
        referenciaTarjeta = '00000';
    }

    const pagoData = {
        comanda_id: obtenerComandaId(),
        tipo_pago: 'mixto',
        total_comanda: total,
        propina_tipo: tipoPropina,
        propina_valor: propinaValor,
        propina_calculada: propinaCalculada,
        monto_efectivo: montoEfectivo,
        monto_tarjeta: montoTarjeta,
        monto_recibido_efectivo: montoEfectivo,
        referencia_tarjeta: referenciaTarjeta
    };

    enviarPago(pagoData);
}

function procesarCuentasSeparadas() {
    // Validar que haya cuentas
    if (!cuentasSeparadas || cuentasSeparadas.length === 0) {
        show_alert('ERROR', 'No hay cuentas para procesar');
        return;
    }

    // Si solo hay una cuenta
    if (cuentasSeparadas.length === 1) {
        show_alert('ALERTA', 'Solo hay una cuenta, seleccione el metodo de pago entre "EFECTIVO", "TARJETA" o  "MIXTO".');
        return;
    }

    // Validar que todas las cuentas tengan items
    const cuentasVacias = cuentasSeparadas.filter(c => c.items.length === 0);
    if (cuentasVacias.length > 0) {
        show_alert('ERROR', 'Todas las cuentas deben tener al menos un producto');
        return;
    }

    // VALIDACIÓN IMPORTANTE: Verificar que no queden productos sin asignar
    const productosDisponibles = window.itemsComandaActual.filter(item => {
        return !cuentasSeparadas.some(cuenta =>
            cuenta.items.some(i => i.id === item.id)
        );
    });

    if (productosDisponibles.length > 0) {
        let mensaje = 'Los siguientes productos no están asignados a ninguna cuenta:\n';
        productosDisponibles.forEach(item => {
            mensaje += `\n- ${item.nombre} x${item.qty} ($${formatMoney(item.subtotal)})`;
        });
        show_alert('ALERTA', mensaje);
        return;
    }

    // Validar que el total de las cuentas sea igual al total de la comanda
    const totalComanda = obtenerTotalComanda();
    const totalCuentas = cuentasSeparadas.reduce((sum, cuenta) => sum + calcularTotalCuenta(cuenta), 0);

    // Preparar datos para cada cuenta
    const cuentasData = [];

    for (let i = 0; i < cuentasSeparadas.length; i++) {
        const cuenta = cuentasSeparadas[i];
        const subtotal = cuenta.items.reduce((sum, item) => sum + (item.subtotal || 0), 0);
        const total = calcularTotalCuenta(cuenta);

        let propinaCalculada = 0;
        if (cuenta.propina.tipo === 'porcentaje') {
            propinaCalculada = subtotal * (cuenta.propina.valor / 100);
        } else if (cuenta.propina.tipo === 'monto') {
            propinaCalculada = cuenta.propina.valor;
        }

        // Obtener valores específicos según método de pago
        let montoRecibido = null;
        let cambio = null;
        let referenciaTarjeta = null;

        if (cuenta.metodoPago === 'efectivo') {
            const inputRecibido = document.getElementById(`monto-recibido-${cuenta.id}`);
            const cambioElement = document.getElementById(`cambio-cuenta-${cuenta.id}`);

            montoRecibido = inputRecibido ? parseFloat(inputRecibido.value) || 0 : 0;
            cambio = cambioElement ? parseFloat(cambioElement.textContent.replace('$', '')) || 0 : 0;

            if (montoRecibido < total) {
                const faltante = total - montoRecibido;
                show_alert('ALERTA', `La cuenta #${i + 1} le faltan $${formatMoney(faltante)} pesos para liquidar la cuenta`);
                return;
            }
        } else if (cuenta.metodoPago === 'tarjeta') {
            referenciaTarjeta = '0000';
        }

        cuentasData.push({
            numero_cuenta: i + 1,
            metodo_pago: cuenta.metodoPago,
            items: cuenta.items.map(item => item.id),
            subtotal: subtotal,
            propina_tipo: cuenta.propina.tipo,
            propina_valor: cuenta.propina.valor,
            propina_calculada: propinaCalculada,
            total: total,
            monto_recibido: montoRecibido,
            cambio: cambio,
            referencia_tarjeta: referenciaTarjeta
        });
    }

    // Construir objeto completo de pago
    const pagoData = {
        comanda_id: obtenerComandaId(),
        tipo_pago: 'cuentas',
        total_comanda: totalComanda,
        cuentas: cuentasData
    };

    Swal.fire({
        title: `¿Procesar pago de ${cuentasData.length} cuenta(s) por el total de $${formatMoney(totalCuentas)} pesos?`,
        showDenyButton: false,
        showCancelButton: true,
        confirmButtonText: "Confirmar"
    }).then((result) => {
        if (result.isConfirmed) {
            enviarPago(pagoData);
        }
    });
}

function enviarPago(pagoData) {
    document.querySelectorAll('.btn').forEach(btn => btn.disabled = true);
    show_alert('Procesando Pago', 'Esta operacion puede tomar unos segundos. Espere porfavor.', false);
    $.ajax({
        type: "POST",
        url: "../../controller/caja-procesar-pago.php",
        data: { pago_data: JSON.stringify(pagoData) },
        dataType: "json",
        success: function (response) {
            if (response.success) {
                show_alert('ÉXITO', 'Pagos procesados correctamente');
                document.querySelectorAll('.btn').forEach(btn => btn.disabled = false);
                // Cerrar modales
                document.getElementById('detalles_comanda')?.classList.remove('visible');
                document.getElementById('modal_metodo_pago')?.classList.remove('visible');
                // Limpiar variables
                cuentasSeparadas = [];
                // Recargar lista de pendientes
                cargarComandasPendientes(true);
            } else {
                show_alert('ERROR', 'Error al procesar el pago: ' + (response.error || 'Error desconocido'));
            }
        },
        error: function (xhr) {
            let errorMsg = 'Error de conexión';
            try {
                const response = JSON.parse(xhr.responseText);
                errorMsg = response.error || errorMsg;
            } catch (e) {
                errorMsg = 'Error al procesar el pago';
            }
            show_alert('ERROR', errorMsg);
            console.error('Error:', xhr.responseText);
        }
    });
}


// ====== BOTONES DE FILTRO (SOLO SEGUNDO CÓDIGO) ======
function asegurarFiltroEstadoCaja() {
    let filtro = document.getElementById('filtro_estado');
    if (!filtro) {
        filtro = document.createElement('input');
        filtro.type = 'hidden';
        filtro.id = 'filtro_estado';
        filtro.value = 'pendiente';
        document.body.appendChild(filtro);
    }
    return filtro;
}

function estadoDesdeTextoBoton(btn) {
    const t = (btn.textContent || '').trim().toLowerCase();

    // Ajuste por textos típicos en caja
    if (t === 'por cobrar' || t === 'pendientes' || t === 'pendiente') return 'pendiente';
    if (t === 'cobrados' || t === 'finalizados' || t === 'finalizado') return 'finalizado';
    if (t === 'todos' || t === 'todas') return 'todas';

    // default seguro
    return 'pendiente';
}

function aplicarFiltroCaja(estado) {
    const filtro = asegurarFiltroEstadoCaja();

    // Normalizar/validar
    if (!['pendiente', 'finalizado', 'todas'].includes(estado)) estado = 'pendiente';

    // Guardar estado para backend + persistencia
    filtro.value = estado;

    // Pintar botón seleccionado
    document.querySelectorAll('.btn_list_comandas').forEach(b => b.classList.remove('lc_selected'));
    const btnActivo = Array.from(document.querySelectorAll('.btn_list_comandas'))
        .find(b => estadoDesdeTextoBoton(b) === estado);
    if (btnActivo) btnActivo.classList.add('lc_selected');

    // --- CAMBIO IMPORTANTE AQUÍ ---
    // Forzar la visualización del spinner de carga ANTES de hacer la petición AJAX
    mostrarCargaComandas(true); // Pasamos true para forzar el spinner

    // Recargar (forzado) al cambiar filtro. El 'true' aquí es para el parámetro 'forzar' de cargarComandasPendientes
    cargarComandasPendientes(true);
}

function inicializarBotonesCaja() {
    asegurarFiltroEstadoCaja();

    // bind clicks una sola vez
    document.querySelectorAll('.btn_list_comandas').forEach(btn => {
        btn.addEventListener('click', function () {
            const estado = estadoDesdeTextoBoton(this);
            aplicarFiltroCaja(estado);
        });
    });
}

// Limpiar al salir
window.addEventListener('beforeunload', function () {
    detenerRecargaAutomatica();
});

$(document).ready(function () {
    inicializarSistemaComandas();
    inicializarBotonesCaja();
});


function ver_ticket(comanda_id) {
    document.getElementById('modal_ticket').classList.add('visible');
    document.getElementById('ticketFrame').src = `../../controller/comanda-ticket.php?comanda_id=${comanda_id}`;
    document.getElementById('ticketFrameAuxiliar').src = `../../controller/comanda-ticket.php?comanda_id=${comanda_id}`;
}

document.getElementById('close_modal_ticket')
    ?.addEventListener('click', () => {
        document.getElementById('modal_ticket')?.classList.remove('visible');
    });

document.getElementById('print-ticket')?.addEventListener('click', () => {
    const ticketFrame = document.getElementById('ticketFrameAuxiliar');

    try {
        if (ticketFrame?.contentWindow) {
            const doPrint = function () {
                // ✅ Limpiar el handler inmediatamente para que no se dispare de nuevo
                ticketFrame.onload = null;

                const iframeDoc = ticketFrame.contentDocument || ticketFrame.contentWindow.document;

                const style = iframeDoc.createElement('style');
                style.textContent = `
                    @page { margin: 0; padding: 0px; }
                    body { margin: 0; padding: 0px; }
                    .main-container { margin: 0 !important; padding: 6px; }
                `;
                iframeDoc.head.appendChild(style);

                ticketFrame.contentWindow.focus();
                ticketFrame.contentWindow.print();
            };

            if (ticketFrame.contentDocument?.readyState === 'complete') {
                // El iframe ya cargó, imprimir directo
                doPrint();
            } else {
                // Esperar a que cargue y luego imprimir
                ticketFrame.onload = doPrint;
            }
        }
    } catch (error) {
        if (ticketFrame?.src) window.open(ticketFrame.src, '_blank');
    }
});