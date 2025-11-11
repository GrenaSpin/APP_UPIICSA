// Resaltar el enlace activo en el sidebar
const links = document.querySelectorAll('.sidebar-menu a');
const currentPath = window.location.pathname;

links.forEach(link => {
  if (link.href.includes(currentPath)) {
    link.classList.add('enlace-activo');
  }
});


// Boton para desplegar/ocultar sidebar
const toggleButton = document.getElementById('toggleSidebar');
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');

// 2. Añadir un "escuchador de eventos" (event listener) al botón para el clic
toggleButton.addEventListener('click', function() {
    sidebar.classList.toggle('hidden');
    mainContent.classList.toggle('expanded');
});