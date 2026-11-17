// =========================================================
// MusicTrack - interacción de la página
// =========================================================

const CSRF = document.querySelector('meta[name="csrf-token"]').content;


// ===== Aviso flotante (toast) =====
const toast = document.getElementById('toast');
let toastTimer;

function mostrarToast(mensaje, tipo = 'ok') {
    toast.textContent = mensaje;
    toast.className = 'toast ' + tipo;
    toast.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { toast.hidden = true; }, 3500);
}

// Si la página llegó con un aviso (?ok=...), se oculta solo y se quita "ok" de la URL
if (!toast.hidden) {
    toastTimer = setTimeout(() => { toast.hidden = true; }, 3500);
    const url = new URL(location.href);
    url.searchParams.delete('ok');
    history.replaceState(null, '', url);
}


// ===== Selects que se envían solos al cambiar (estado, orden, playlist...) =====
document.addEventListener('change', e => {
    const select = e.target;
    // En "＋ Playlist" la opción vacía es solo el texto de ayuda
    const vacioSinSentido = select.classList.contains('playlist-add') && select.value === '';
    if (select.matches('select[data-autosubmit]') && !vacioSinSentido) {
        e.target.form.requestSubmit();
    }
});


// ===== Filtros de la biblioteca (GET al servidor) =====
const formFiltros = document.getElementById('filtros');
const inputBuscar = document.getElementById('buscar');
let filtroTimer;

// Buscar mientras se escribe (espera a que deje de escribir)
inputBuscar.addEventListener('input', () => {
    clearTimeout(filtroTimer);
    filtroTimer = setTimeout(() => formFiltros.requestSubmit(), 600);
});

// No enviar campos vacíos para que la URL quede limpia
formFiltros.addEventListener('submit', () => {
    formFiltros.querySelectorAll('input, select').forEach(campo => {
        if (campo.value === '' || (campo.name === 'orden' && campo.value === 'recientes')) {
            campo.disabled = true;
        }
    });
});

// Si se llegó buscando algo, se deja el cursor al final del texto
if (inputBuscar.value && location.hash === '#biblioteca') {
    inputBuscar.focus();
    inputBuscar.setSelectionRange(inputBuscar.value.length, inputBuscar.value.length);
}

// El menú de usuario se cierra al hacer clic fuera
document.addEventListener('click', e => {
    const menu = document.querySelector('.user-menu[open]');
    if (menu && !menu.contains(e.target)) menu.open = false;
});


// ===== Modal "Editar" =====
const modal = document.getElementById('modalEditar');

document.addEventListener('click', e => {
    const boton = e.target.closest('[data-editar]');
    if (!boton) return;

    const c = JSON.parse(boton.dataset.editar);
    document.getElementById('editId').value = c.id;
    document.getElementById('editTitulo').value = c.titulo;
    document.getElementById('editArtista').value = c.artista;
    document.getElementById('editTipo').value = c.tipo;
    document.getElementById('editGenero').value = c.genero;
    document.getElementById('editPrioridad').value = c.prioridad;
    document.getElementById('editEstado').value = c.estado;
    document.getElementById('editCalificacion').value = c.calificacion || 0;
    document.getElementById('editNotas').value = c.notas || '';
    modal.showModal();
});

modal.querySelectorAll('[data-cerrar-modal]').forEach(b => b.addEventListener('click', () => modal.close()));
// Clic en el fondo oscuro también cierra
modal.addEventListener('click', e => { if (e.target === modal) modal.close(); });


// ===== Buscador de Spotify (formulario de registro) =====
const spotifyQ = document.getElementById('spotifyQ');
const spotifyResultados = document.getElementById('spotifyResultados');
const spotifyEstado = document.getElementById('spotifyEstado');
const spotifyElegida = document.getElementById('spotifyElegida');
let tipoBusqueda = 'track';
let temporizador;

function mostrarEstado(mensaje) {
    spotifyEstado.textContent = mensaje;
    spotifyEstado.hidden = !mensaje;
}

// Botones "Canciones / Álbumes"
document.querySelectorAll('[data-tipo-busqueda]').forEach(boton => {
    boton.addEventListener('click', () => {
        tipoBusqueda = boton.dataset.tipoBusqueda;
        document.querySelectorAll('[data-tipo-busqueda]').forEach(b => b.classList.toggle('active', b === boton));
        spotifyQ.placeholder = tipoBusqueda === 'album'
            ? 'Escribe el nombre de un álbum o pega su link...'
            : 'Escribe un nombre o pega un link de Spotify...';
        buscarEnSpotify();
    });
});

spotifyQ.addEventListener('input', () => {
    clearTimeout(temporizador);
    temporizador = setTimeout(buscarEnSpotify, 400); // espera a que deje de escribir
});

// Enter en el buscador no debe enviar el formulario
spotifyQ.addEventListener('keydown', e => {
    if (e.key === 'Enter') {
        e.preventDefault();
        clearTimeout(temporizador);
        buscarEnSpotify();
    }
});

async function pedirJSON(url) {
    const res = await fetch(url);
    const datos = await res.json();
    if (!res.ok) throw new Error(datos.error || 'Error al buscar');
    return datos;
}

async function buscarEnSpotify() {
    const q = spotifyQ.value.trim();
    spotifyResultados.innerHTML = '';
    mostrarEstado('');
    if (q.length < 2) return;

    // Un link pegado (open.spotify.com/... o spotify:track:...) se resuelve directo
    const esLink = /open\.spotify\.com\/|^spotify:/.test(q);
    mostrarEstado(esLink ? 'Leyendo el link...' : 'Buscando en Spotify...');

    try {
        const datos = esLink
            ? [await pedirJSON('api/link.php?url=' + encodeURIComponent(q))]
            : await pedirJSON('api/search.php?' + new URLSearchParams({ q, tipo: tipoBusqueda }));

        if (q !== spotifyQ.value.trim()) return; // llegó una búsqueda vieja

        mostrarEstado(datos.length ? '' : 'Sin resultados en Spotify.');
        datos.forEach(t => spotifyResultados.append(crearResultado(t)));
    } catch (err) {
        mostrarEstado(err.message + '.');
    }
}

// Se usa textContent (no innerHTML) para evitar XSS con los datos de Spotify
function crearResultado(t) {
    const li = document.createElement('li');

    const img = document.createElement('img');
    img.src = t.image || '';
    img.alt = '';

    const texto = document.createElement('span');
    const nombre = document.createElement('strong');
    nombre.textContent = t.name;
    const artista = document.createElement('small');
    artista.textContent = (t.tipo === 'album' ? 'Álbum · ' : '') + (t.artist || 'Artista desconocido');
    texto.append(nombre, artista);

    const usar = document.createElement('button');
    usar.type = 'button';
    usar.textContent = 'Usar';
    usar.title = 'Llenar el formulario con esto';
    usar.onclick = () => elegirCancion(t);
    li.append(img, texto, usar);

    // El guardado rápido necesita el artista (el oEmbed de un link no lo trae)
    if (t.artist) {
        const guardar = document.createElement('button');
        guardar.type = 'button';
        guardar.className = 'btn-save';
        guardar.textContent = 'Guardar';
        guardar.title = 'Guardar ya con prioridad media, estado pendiente y el género detectado';
        guardar.onclick = () => guardarRapido(t, guardar);
        li.append(guardar);
    }

    return li;
}

function elegirCancion(t) {
    document.getElementById('titulo').value = t.name;
    document.getElementById('artista').value = t.artist || '';
    document.getElementById('tipo').value = t.tipo === 'album' ? 'album' : 'cancion';
    document.getElementById('spotify_id').value = t.id;
    document.getElementById('spotify_tipo').value = t.tipo || 'track';
    document.getElementById('portada_url').value = t.image || '';

    document.getElementById('spotifyElegidaImg').src = t.image || '';
    document.getElementById('spotifyElegidaTxt').textContent = t.artist ? `${t.name} — ${t.artist}` : t.name;
    spotifyElegida.hidden = false;

    spotifyResultados.innerHTML = '';
    spotifyQ.value = '';

    if (t.artist) {
        sugerirGenero(t);
    } else {
        mostrarEstado('El link no trae el artista: escríbelo y elige el género.');
        document.getElementById('artista').focus();
    }
}

// Spotify no da el género: se pide a api/genre.php (que consulta Deezer) y se llena el select
async function sugerirGenero(t) {
    const selectGenero = document.getElementById('genero');
    selectGenero.value = '';
    mostrarEstado('Buscando el género...');

    try {
        const res = await fetch('api/genre.php?' + new URLSearchParams({ titulo: t.name, artista: t.artist, tipo: t.tipo || 'track' }));
        const datos = await res.json();

        // Si mientras tanto se eligió otra canción o se quitó, no se toca el formulario
        if (document.getElementById('spotify_id').value !== t.id) return;

        if (res.ok && datos.genero) {
            selectGenero.value = datos.genero;
            const nombre = selectGenero.options[selectGenero.selectedIndex].text;
            mostrarEstado(`Género sugerido: ${nombre} (Deezer: ${datos.fuente}). Puedes cambiarlo.`);
            document.getElementById('prioridad').focus();
        } else {
            mostrarEstado('No se encontró el género; elígelo en la lista.');
            selectGenero.focus();
        }
    } catch {
        mostrarEstado('No se pudo obtener el género; elígelo en la lista.');
        selectGenero.focus();
    }
}

// Quitar la canción elegida vacía todo el formulario
document.getElementById('spotifyQuitar').addEventListener('click', () => {
    document.querySelector('.music-form').reset();

    // reset() no limpia los campos ocultos (su valor cuenta como el valor por defecto)
    ['spotify_id', 'spotify_tipo', 'portada_url'].forEach(id => { document.getElementById(id).value = ''; });

    spotifyElegida.hidden = true;
    mostrarEstado('');
    spotifyQ.focus();
});

async function guardarRapido(t, boton) {
    boton.disabled = true;
    boton.textContent = 'Guardando...';
    try {
        const res = await fetch('api/save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
            body: JSON.stringify(t),
        });
        if (!res.ok) throw new Error();
        location.href = 'index.php?ok=registrado#biblioteca';
    } catch {
        boton.disabled = false;
        boton.textContent = 'Guardar';
        mostrarToast('No se pudo guardar', 'error');
    }
}
