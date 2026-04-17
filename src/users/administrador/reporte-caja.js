// Función para formatear fecha como YYYY-MM-DD para input type="date"
function formatearFechaParaInput(fecha) {
    const year = fecha.getFullYear();
    const month = String(fecha.getMonth() + 1).padStart(2, '0');
    const day = String(fecha.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Función para cargar los datos de la fecha seleccionada
function cargarDatosPorFecha(fecha) {
    mostrarCarga();

    $.ajax({
        url: '../../controller/reporte-caja.php', // Ajusta esta URL
        type: 'POST',
        data: {dia: fecha},
        dataType: 'html',
        success: function (response) {
            $("#container-data-caja").html(response);
        },
        error: function (xhr, status, error) {
            mostrarError('Error al cargar los datos');
        },
        complete: function () {
            ocultarCarga();
        }
    });
}

// Función para mostrar carga
function mostrarCarga() {
    const contenedor = document.getElementById('container-data-caja');

    if (contenedor && !document.getElementById('carga-spinner')) {
        const cargaHTML = `
            <div id="carga-spinner" class="text-center p-5" style="position: relative; min-height: 200px;">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2 text-muted">Cargando estadísticas...</p>
            </div>
        `;
        // Si el contenedor tiene hijos, los reemplazamos
        if (contenedor.children.length > 0) {
            // Guardar el HTML original
            window.htmlOriginalEstadisticas = contenedor.innerHTML;
            contenedor.innerHTML = cargaHTML;
        } else {
            // Si está vacío, simplemente agregamos
            contenedor.innerHTML = cargaHTML;
        }
    }
}

// Función para ocultar carga
function ocultarCarga() {
    const spinner = document.getElementById('carga-spinner');
    if (spinner) {
        // Restaurar contenido original si existe
        if (window.htmlOriginalEstadisticas) {
            const contenedor = document.getElementById('container-data-caja');
            if (contenedor) {
                contenedor.innerHTML = window.htmlOriginalEstadisticas;
            }
        } else {
            spinner.remove();
        }
    }
}

// 🔴 NUEVA: Función para mostrar error
function mostrarError(mensaje) {
    const contenedor = document.getElementById('container-data-caja');
    if (contenedor) {
        contenedor.innerHTML = `
            <div class="text-center p-5">
                <i class="bi bi-exclamation-triangle fs-1 text-danger"></i>
                <p class="mt-2 text-danger">${mensaje}</p>
                <button class="btn btn-outline-primary mt-2" onclick="reintentarCarga()">
                    <i class="bi bi-arrow-repeat"></i> Reintentar
                </button>
            </div>
        `;
    }
}

// 🔴 NUEVA: Función para reintentar
function reintentarCarga() {
    const inputDia = document.getElementById('dia');
    if (inputDia) {
        cargarDatosPorFecha(inputDia.value);
    }
}

// Función para inicializar el control de fechas (MODIFICADA)
function inicializarControlFechas() {
    const inputDia = document.getElementById('dia');
    const btnAnterior = document.querySelector('.btn-success .bi-arrow-left').parentElement;
    const btnPosterior = document.querySelector('.btn-success .bi-arrow-right').parentElement;

    if (!inputDia || !btnAnterior || !btnPosterior) return;

    // 🔴 Verificar si hay fecha en la URL
    const urlParams = new URLSearchParams(window.location.search);
    const fechaUrl = urlParams.get('dia');

    let fechaInicial;
    if (fechaUrl && /^\d{4}-\d{2}-\d{2}$/.test(fechaUrl)) {
        // Usar fecha de la URL si es válida
        const [year, month, day] = fechaUrl.split('-');
        fechaInicial = new Date(year, month - 1, day);
    } else {
        // Usar fecha actual
        fechaInicial = new Date();
    }

    // Establecer fecha inicial
    inputDia.value = formatearFechaParaInput(fechaInicial);

    // Variable para almacenar la fecha actual seleccionada
    let fechaSeleccionada = new Date(fechaInicial);

    // 🔴 MODIFICADO: Función para actualizar el input y cargar datos
    function actualizarFecha(dias) {
        fechaSeleccionada.setDate(fechaSeleccionada.getDate() + dias);
        const nuevaFecha = formatearFechaParaInput(fechaSeleccionada);
        inputDia.value = nuevaFecha;

        // 🔴 Cargar datos automáticamente
        cargarDatosPorFecha(nuevaFecha);
    }

    // 🔴 NUEVO: Función para manejar cambio manual
    function manejarCambioManual() {
        if (inputDia.value) {
            const [year, month, day] = inputDia.value.split('-');
            fechaSeleccionada = new Date(year, month - 1, day);

            // 🔴 Cargar datos automáticamente
            cargarDatosPorFecha(inputDia.value);
        }
    }

    // Evento para el botón anterior (día anterior)
    btnAnterior.addEventListener('click', function (e) {
        e.preventDefault();
        actualizarFecha(-1);
    });

    // Evento para el botón posterior (día siguiente)
    btnPosterior.addEventListener('click', function (e) {
        e.preventDefault();
        actualizarFecha(1);
    });

    // Evento para cuando el usuario cambia la fecha manualmente
    inputDia.addEventListener('change', manejarCambioManual);

    // 🔴 NUEVO: También cargar al iniciar
    cargarDatosPorFecha(inputDia.value);
}

// Inicializar cuando el DOM esté listo
$(document).ready(function () {
    inicializarControlFechas();
});

document.addEventListener('click', e => {
    if (e.target.id === 'open_modal_montoinicial') {
        document.getElementById('modal_monto_inicial')?.classList.add('visible');
    }
    if (e.target.id === 'close_modal_montoinicial') {
        document.getElementById('modal_monto_inicial')?.classList.remove('visible');
    }
});