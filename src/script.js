// Mantener la opción del menú seleccionada y cargar contenido dinámico en menu predeterminado
document.querySelectorAll('.navbar_option').forEach(button => {
    button.addEventListener('click', function () {
        // Remover la clase 'option-selected' de todos los botones
        document.querySelectorAll('.navbar_option').forEach(btn => btn.classList.remove('option-selected'));
        // Agregar la clase 'option-selected' al botón clickeado
        this.classList.add('option-selected');

        // Cargar contenido dinámico según el atributo data-mdl
        let modulo = this.dataset.modulo;

        $('#container_main_home').html('<div class="preloader-view">Cargando...</div>');
        setTimeout(() => {
            $('#container_main_home').load(modulo+'.php');
        }, 300)
    });
});


// Mantener la opción del menú seleccionada y cargar contenido dinámico en menu responsive
document.querySelectorAll('.option_responsive').forEach(button => {
    button.addEventListener('click', function () {
        // Remover la clase 'option-selected' de todos los botones
        document.querySelectorAll('.option_responsive').forEach(btn => btn.classList.remove('option-selected'));
        // Agregar la clase 'option-selected' al botón clickeado
        this.classList.add('option-selected');

        // Cargar contenido dinámico según el atributo data-mdl
        let modulo = this.dataset.modulo;

        $('#container_main_home').html('<div class="preloader-view">Cargando...</div>');
        setTimeout(() => {
            $('#container_main_home').load(modulo+'.php');
        }, 300)
    });
});