<style>
    .dropdown-menu {
        /* max-height: 450px; */
        overflow-y: auto;
        width: 100%;
        display: none;
        position: absolute;
        z-index: 1000;
        left: 0;
        border: 1px solid #ccc;
    }
    .dropdown-item {
        white-space: nowrap;
        padding: 5px 10px;
        display: block;
        cursor: pointer; /* Cambia el cursor para indicar que es clickeable */
    }
    .item-Title {
        font-weight: bold;
    }
    .item-Description {
        font-size: 0.85em;
        color: #666;
        white-space: normal;
    }
    .loading {
        text-align: center; /* Centra el texto */
        padding: 10px; /* Padding para que se vea mejor */
        color: #999; /* Color tenue para el indicador de carga */
    }
    .navbar .dropdown-menu {
      left: 0;
      overflow: scroll;
    }
</style>

<div class="col-md-6" style="position: relative;">
    <input type="text" id="searchInput" class="form-control" placeholder="Buscar..." autocomplete="off">
    <div class="dropdown-menu" id="searchResults"></div>
</div>
<script>
const searchInput = document.getElementById('searchInput');
const searchResults = document.getElementById('searchResults');

// Función para buscar elementos
function searchItems() {
    const query = searchInput.value;

    if (query.length < 1) {
        searchResults.style.display = 'none'; // Ocultar si no hay consulta
        return;
    }

    // Mostrar indicador de carga
    searchResults.innerHTML = '<div class="loading">Cargando...</div>';
    searchResults.style.display = 'block';

    // Hacer la solicitud AJAX

// Usar new URL() para crear una URL dinámica
const url = new URL('controllers/searchbox.php', window.location.href);
url.searchParams.append('query', query); // Añadir parámetros de búsqueda

fetch(url)

        .then(response => response.json())
        .then(results => {
            displayResults(results);
        })
        .catch(error => {
            console.error('Error fetching results:', error);
            searchResults.innerHTML = '<div class="loading">Error al cargar resultados.</div>';
        });
}

// Función para mostrar resultados
function displayResults(results) {
    searchResults.innerHTML = ''; // Limpiar resultados anteriores

    if (results.length > 0) {
        results.forEach(item => {
            const itemLink = document.createElement('a');
            itemLink.className = 'dropdown-item';
            itemLink.href = `reports.php?Id=${item.ReportsId}`;

            itemLink.innerHTML = `
                <div class="item-Title">${item.ReportsId}. ${item.Title}</div>
                <div class="item-Description"><b>${item.Category}: </b> ${item.Description} <b>PO: ${item.FullName}</b></div>
            `;

            itemLink.onclick = function() {
                window.location.href = `reports.php?Id=${item.ReportsId}`;
            };

            searchResults.appendChild(itemLink);
        });
    } else {
        searchResults.innerHTML = '<div class="loading">No se encontraron resultados.</div>';
    }
}

// Implementar debounce en la búsqueda
let debounceTimer;
searchInput.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(searchItems, 300); // Espera 300ms antes de buscar
});

// Cerrar el menú al hacer clic fuera
document.addEventListener('click', function(e) {
    if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
        searchResults.style.display = 'none';
    }
});
</script>
