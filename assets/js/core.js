function inicialitzar() {
    console.log("Sistema AgriSoft carregat.");
    
    if(typeof renderitzarTauler === 'function') renderitzarTauler();
    if(typeof renderitzarParcel·les === 'function') renderitzarParcel·les();
    if(typeof renderitzarSectors === 'function') renderitzarSectors();
    if(typeof renderitzarMaquinaria === 'function') renderitzarMaquinaria();
    if(typeof renderitzarCultius === 'function') renderitzarCultius();
    if(typeof renderitzarEspecies === 'function') renderitzarEspecies();
    if(typeof renderitzarTreballadors === 'function') renderitzarTreballadors();
    if(typeof renderitzarEstoc === 'function') renderitzarEstoc();
    if(typeof renderitzarTractaments === 'function') renderitzarTractaments();
    if(typeof renderitzarControlHorari === 'function') renderitzarControlHorari();
}

function mostrarSeccio(idSeccio) {
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));

    const seccio = document.getElementById(idSeccio);
    if (seccio) seccio.classList.add('active');

    const mapa = {
        'parcel·les': 'hub-explotacio', 'sectors': 'hub-explotacio', 'maquinaria': 'hub-explotacio',
        'cultius': 'hub-cultius', 'especie': 'hub-cultius',
        'calculadora': 'hub-sanitat', 'tractaments': 'hub-sanitat', 'estoc': 'hub-sanitat',
        'collites': 'hub-produccio', 'qualitat': 'hub-produccio',
        'treballadors': 'hub-personal', 'control-horari': 'hub-personal'
    };
    
    const pare = mapa[idSeccio] || idSeccio;
    const btn = document.querySelector(`.nav-tab[onclick="mostrarSeccio('${pare}')"]`);
    if(btn) btn.classList.add('active');
    
    if(idSeccio === 'tauler') renderitzarTauler();
    if(idSeccio === 'control-horari' && typeof renderitzarControlHorari === 'function') renderitzarControlHorari();
}

// Helper per gestionar pestanyes dins dels modals
function canviarPestanya(idTab) {
    const modalActiu = document.querySelector('.modal.active');
    if(!modalActiu) return;

    // Desactivar tots
    modalActiu.querySelectorAll('.modal-tab-btn').forEach(btn => btn.classList.remove('active'));
    modalActiu.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

    // Activar el botó clicat
    const boto = modalActiu.querySelector(`button[onclick="canviarPestanya('${idTab}')"]`);
    if(boto) boto.classList.add('active');

    // Activar el contingut
    const contingut = document.getElementById(idTab);
    if(contingut) contingut.classList.add('active');
}

window.addEventListener('DOMContentLoaded', inicialitzar);