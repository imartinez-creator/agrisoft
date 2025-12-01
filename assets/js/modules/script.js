// ============================================
// VARIABLES GLOBALS
// ============================================
let parcel·les = [];
let cultius = [];
let collites = [];
let sectors = [];
let filesArbres = [];
let especiesFruita = {};

// Variables Dibuix
let modeDibuix = null;
let vèrtexsActuals = [];
let polygonActual = null;
let líniaActual = null;
let puntIntermediActual = null;
let idParcel·laSeleccionada = null;
let idSectorSeleccionat = null;
let idSectorPerFiles = null;

const colors = {
    parcel·la: { default: 'rgba(45, 80, 22, 0.5)', hover: 'rgba(74, 124, 44, 0.7)', selected: 'rgba(212, 175, 55, 0.7)', dibuix: 'rgba(139, 111, 71, 0.7)' },
    sector: { default: 'rgba(139, 111, 71, 0.5)', hover: 'rgba(212, 175, 55, 0.7)', selected: 'rgba(45, 80, 22, 0.7)', dibuix: 'rgba(212, 175, 55, 0.7)' },
    fila: { default: 'rgba(212, 175, 55, 0.6)', hover: 'rgba(45, 80, 22, 0.7)', selected: 'rgba(139, 111, 71, 0.7)', dibuix: 'rgba(45, 80, 22, 0.7)' }
};
const SVG_NS = "http://www.w3.org/2000/svg";

// ============================================
// INICIALITZACIÓ
// ============================================
function inicialitzar() {
    renderitzarTauler();
    renderitzarParcel·les();
    renderitzarSectors();
    renderitzarEspecies();
    renderitzarCultius();
    renderitzarHistoric();
    renderitzarMapaGeneral();
    
    actualitzarSelectorParcel·lesCultiu();
    actualitzarSelectorCultiusCollita();
    actualitzarSelectorsEspecie();

    inicialitzarMapaDibuix('svgMapaParcel·les', 'capaDibuix', gestionarClicMapaParcel·la, finalitzarDibuixParcel·la);
    inicialitzarMapaDibuix('svgMapaSectors', 'capaDibuixSector', gestionarClicMapaSector, finalitzarDibuixSector);
    inicialitzarMapaDibuix('svgMapaFiles', 'capaDibuixFila', gestionarClicMapaFila, finalitzarDibuixFila);
}

// ============================================
// NAVEGACIÓ AVANÇADA (HUBS)
// ============================================
function mostrarSeccio(idSeccio) {
    // 1. Amagar tot
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));

    // 2. Mostrar secció
    const seccio = document.getElementById(idSeccio);
    if (seccio) seccio.classList.add('active');
    else return console.warn('Secció no trobada:', idSeccio);

    // 3. Il·luminar menú superior (Mapeig Parent-Child)
    const mapaNavegacio = {
        'parcel·les': 'hub-explotacio', 'sectors': 'hub-explotacio', 'mapa': 'mapa',
        'cultius': 'hub-cultius', 'especie': 'hub-cultius', 'historic': 'hub-cultius',
        'calculadora': 'hub-sanitat', 'tractaments': 'hub-sanitat', 'magatzem': 'hub-sanitat', 'maquinaria': 'hub-sanitat',
        'collites': 'hub-produccio', 'qualitat': 'hub-produccio', 'tracabilitat': 'hub-produccio',
        'treballadors': 'hub-personal', 'fitxatges': 'hub-personal'
    };

    const idPare = mapaNavegacio[idSeccio] || idSeccio;
    const boto = document.querySelector(`.nav-tab[onclick="mostrarSeccio('${idPare}')"]`);
    if (boto) boto.classList.add('active');

    // 4. Executar renderitzats específics
    if (idSeccio === 'tauler') renderitzarTauler();
    else if (idSeccio === 'parcel·les') renderitzarParcel·les();
    else if (idSeccio === 'sectors') renderitzarSectors();
    else if (idSeccio === 'cultius') { actualitzarSelectorParcel·lesCultiu(); renderitzarCultius(); }
    else if (idSeccio === 'especie') renderitzarEspecies();
    else if (idSeccio === 'historic') { actualitzarSelectorCultiusCollita(); renderitzarHistoric(); }
    else if (idSeccio === 'mapa') renderitzarMapaGeneral();
}

// ============================================
// LOGICA DE NEGOCI (TAULER, PARCEL·LES, ETC)
// ============================================

function renderitzarTauler() {
    const areaTotal = parcel·les.reduce((s, p) => s + parseFloat(p.area), 0);
    const prodTotal = collites.reduce((s, c) => s + parseFloat(c.quantitat), 0);

    document.getElementById('estadistiquesTauler').innerHTML = `
        <div class="stat-card"><div class="stat-label">Parcel·les</div><div class="stat-value">${parcel·les.length}</div></div>
        <div class="stat-card"><div class="stat-label">Superfície (ha)</div><div class="stat-value">${areaTotal.toFixed(1)}</div></div>
        <div class="stat-card"><div class="stat-label">Producció (t)</div><div class="stat-value">${(prodTotal/1000).toFixed(1)}</div></div>
    `;

    const resumDiv = document.getElementById('resumParcel·les');
    if (parcel·les.length === 0) resumDiv.innerHTML = '<p>Sense dades.</p>';
    else resumDiv.innerHTML = parcel·les.slice(0, 4).map(p => `
        <div class="item-card"><div class="item-title">${p.nom}</div><div class="item-subtitle">${p.refCadastral} - ${p.area} ha</div></div>
    `).join('');
    
    document.getElementById('properesCollites').innerHTML = '<div class="alert alert-info">Sense alertes properes.</div>';
}

function renderitzarParcel·les() {
    const graella = document.getElementById('graellaParcel·les');
    if (parcel·les.length === 0) {
        graella.innerHTML = '<div class="empty-state">No hi ha parcel·les.</div>';
    } else {
        graella.innerHTML = parcel·les.map(p => `
            <div class="item-card">
                <div class="item-title">${p.nom}</div>
                <div class="item-subtitle">${p.area} ha | ${p.reg}</div>
                <div style="margin-top:1rem;"><button class="btn btn-outline btn-sm" onclick="obrirModalParcel·la('${p.id}')">Editar</button></div>
            </div>`).join('');
    }
    dibuixarCapaPoligons('capaParcel·les', parcel·les, colors.parcel·la.default, colors.parcel·la.hover, null);
}

function obrirModalParcel·la(id = null) {
    document.getElementById('formulariParcel·la').reset();
    document.getElementById('idParcel·la').value = '';
    if (id) {
        const p = parcel·les.find(x => x.id === id);
        document.getElementById('idParcel·la').value = p.id;
        document.getElementById('nomParcel·la').value = p.nom;
        document.getElementById('refCadastralParcel·la').value = p.refCadastral;
        document.getElementById('areaParcel·la').value = p.area;
        document.getElementById('coordenadesParcel·la').value = p.coordenades ? p.coordenades.map(c => c.join(',')).join('; ') : '';
        document.getElementById('titolModalParcel·la').innerText = 'Editar Parcel·la';
    } else {
        document.getElementById('titolModalParcel·la').innerText = 'Nova Parcel·la';
    }
    document.getElementById('modalParcel·la').classList.add('active');
}

function tancarModalParcel·la() {
    document.getElementById('modalParcel·la').classList.remove('active');
    netejarDibuix('capaDibuix');
    document.getElementById('btnDibuixarParcel·la').classList.remove('active');
    modeDibuix = null;
}

function desarParcel·la() {
    const id = document.getElementById('idParcel·la').value;
    const nom = document.getElementById('nomParcel·la').value;
    if (!nom) return alert('Nom obligatori');
    
    const nova = {
        id: id || generarUUID(),
        nom: nom,
        refCadastral: document.getElementById('refCadastralParcel·la').value,
        area: parseFloat(document.getElementById('areaParcel·la').value) || 0,
        coordenades: vèrtexsActuals.length > 2 ? vèrtexsActuals : [],
        reg: document.getElementById('regParcel·la').value,
        tipusSòl: document.getElementById('tipusSòlParcel·la').value
    };

    if (id) parcel·les[parcel·les.findIndex(x => x.id === id)] = nova;
    else parcel·les.push(nova);

    tancarModalParcel·la();
    renderitzarParcel·les();
}

function renderitzarSectors() {
    const graella = document.getElementById('graellaSectors');
    if (sectors.length === 0) graella.innerHTML = '<div class="empty-state">No hi ha sectors.</div>';
    else {
        graella.innerHTML = sectors.map(s => {
            const esp = especiesFruita[s.especie] || {nom: '?'};
            return `<div class="item-card"><div class="item-title">${s.nom}</div><div class="item-subtitle">${esp.nom} - ${s.estat}</div>
            <div style="margin-top:1rem;"><button class="btn btn-outline btn-sm" onclick="obrirModalSector('${s.id}')">Editar</button>
            <button class="btn btn-outline btn-sm" onclick="obrirModalFiles('${s.id}')">Files</button></div></div>`;
        }).join('');
    }
    dibuixarCapaPoligons('capaParcel·lesSectors', parcel·les, 'rgba(0,0,0,0.05)', null, null);
    dibuixarCapaPoligons('capaSectorsLlista', sectors, colors.sector.default, colors.sector.hover, null);
}

function obrirModalSector(id = null) {
    document.getElementById('formulariSector').reset();
    actualitzarSelectorsEspecie();
    document.getElementById('idSector').value = '';
    if (id) {
        const s = sectors.find(x => x.id === id);
        document.getElementById('idSector').value = s.id;
        document.getElementById('nomSector').value = s.nom;
        document.getElementById('especieSector').value = s.especie;
        document.getElementById('titolModalSector').innerText = 'Editar Sector';
    } else {
        document.getElementById('titolModalSector').innerText = 'Nou Sector';
    }
    document.getElementById('modalSector').classList.add('active');
}
function tancarModalSector() { document.getElementById('modalSector').classList.remove('active'); netejarDibuix('capaDibuixSector'); modeDibuix = null; }

function desarSector() {
    const id = document.getElementById('idSector').value;
    const nom = document.getElementById('nomSector').value;
    if (!nom) return alert('Nom obligatori');

    const nou = {
        id: id || generarUUID(),
        nom, especie: document.getElementById('especieSector').value,
        superficie: parseFloat(document.getElementById('superficieSector').value) || 0,
        nombreFiles: parseInt(document.getElementById('nombreFilesSector').value) || 0,
        coordenades: vèrtexsActuals.length > 2 ? vèrtexsActuals : [],
        estat: document.getElementById('estatSector').value
    };
    if (id) sectors[sectors.findIndex(x => x.id === id)] = nou;
    else sectors.push(nou);
    tancarModalSector(); renderitzarSectors();
}

function renderitzarCultius() {
    const graella = document.getElementById('graellaCultius');
    if (cultius.length === 0) graella.innerHTML = '<div class="empty-state">No hi ha cultius.</div>';
    else {
        graella.innerHTML = cultius.map(c => {
            const p = parcel·les.find(x => x.id === c.idParcel·la) || {nom: '?'};
            const e = especiesFruita[c.especie] || {nom: '?'};
            return `<div class="item-card"><div class="item-title">${e.nom}</div><div class="item-subtitle">a ${p.nom} - ${c.estat}</div></div>`;
        }).join('');
    }
}
function obrirModalCultiu() { document.getElementById('formulariCultiu').reset(); actualitzarSelectorsEspecie(); actualitzarSelectorParcel·lesCultiu(); document.getElementById('modalCultiu').classList.add('active'); }
function tancarModalCultiu() { document.getElementById('modalCultiu').classList.remove('active'); }
function desarCultiu() {
    const c = {
        id: generarUUID(), idParcel·la: document.getElementById('idParcel·laCultiu').value,
        especie: document.getElementById('especieCultiu').value, estat: document.getElementById('estatCultiu').value,
        dataPlantació: document.getElementById('dataPlantació').value
    };
    cultius.push(c); tancarModalCultiu(); renderitzarCultius();
}

function renderitzarEspecies() {
    const g = document.getElementById('graellaEspecies');
    const keys = Object.keys(especiesFruita);
    if(keys.length === 0) g.innerHTML = '<div class="empty-state">Cap espècie.</div>';
    else g.innerHTML = keys.map(k => `<div class="item-card"><div class="item-title">${especiesFruita[k].nom}</div><div class="item-subtitle">${especiesFruita[k].tipus}</div></div>`).join('');
}
function obrirModalEspecie() { document.getElementById('formulariEspecie').reset(); document.getElementById('modalEspecie').classList.add('active'); }
function tancarModalEspecie() { document.getElementById('modalEspecie').classList.remove('active'); }
function desarEspecie() {
    const id = document.getElementById('idEspecie').value;
    if(!id) return alert('ID obligatori');
    especiesFruita[id] = {
        nom: document.getElementById('nomEspecie').value,
        tipus: document.getElementById('tipusEspecie').value,
        diesMaduració: document.getElementById('diesMaduracióEspecie').value
    };
    tancarModalEspecie(); renderitzarEspecies();
}

function renderitzarHistoric() {
    const t = document.getElementById('taulaCollites');
    if (collites.length === 0) t.innerHTML = '<tr><td colspan="5">Cap registre.</td></tr>';
    else t.innerHTML = collites.map(c => `<tr><td>${c.data}</td><td>-</td><td>-</td><td>${c.quantitat}</td><td>${c.qualitat}</td></tr>`).join('');
}
function obrirModalCollita() { document.getElementById('formulariCollita').reset(); actualitzarSelectorCultiusCollita(); document.getElementById('modalCollita').classList.add('active'); }
function tancarModalCollita() { document.getElementById('modalCollita').classList.remove('active'); }
function desarCollita() {
    collites.push({id:generarUUID(), idCultiu:document.getElementById('idCultiuCollita').value, data:document.getElementById('dataCollitaRegistre').value, quantitat:document.getElementById('quantitatCollita').value, qualitat:document.getElementById('qualitatCollita').value});
    tancarModalCollita(); renderitzarHistoric();
}

// ============================================
// MAPA I DIBUIX
// ============================================
function inicialitzarMapaDibuix(idSvg, idCapa, funcClic, funcFinal) {
    const svg = document.getElementById(idSvg);
    if(!svg) return;
    svg.addEventListener('click', e => {
        if(!modeDibuix) return;
        const r = svg.getBoundingClientRect();
        funcClic(svg, document.getElementById(idCapa), (e.clientX - r.left)/r.width*1000, (e.clientY - r.top)/r.height*700);
    });
    svg.addEventListener('dblclick', () => { if(modeDibuix) funcFinal(svg, document.getElementById(idCapa)); });
}

function gestionarClicMapaParcel·la(svg, capa, x, y) {
    vèrtexsActuals.push([x,y]);
    if(vèrtexsActuals.length > 1) {
        if(!polygonActual) { polygonActual = crearSVG('polygon', {points:vèrtexsActuals.join(' '), fill:colors.parcel·la.dibuix, opacity:0.5}); capa.appendChild(polygonActual); }
        else polygonActual.setAttribute('points', vèrtexsActuals.map(p=>p.join(',')).join(' '));
    } else {
        capa.appendChild(crearSVG('circle', {cx:x, cy:y, r:5, fill:colors.parcel·la.dibuix}));
    }
}
function finalitzarDibuixParcel·la(svg, capa) {
    if(vèrtexsActuals.length>2) document.getElementById('coordenadesParcel·la').value = vèrtexsActuals.map(c=>c.join(',')).join('; ');
    modeDibuix=null; document.getElementById('btnDibuixarParcel·la').classList.remove('active'); capa.innerHTML='';
}
function gestionarClicMapaSector(svg, capa, x, y) { gestionarClicMapaParcel·la(svg, capa, x, y); } // Reutilitzem lògica bàsica
function finalitzarDibuixSector(svg, capa) { modeDibuix=null; document.getElementById('btnDibuixarSector').classList.remove('active'); capa.innerHTML=''; }

function iniciarDibuixParcel·la() { modeDibuix='parcel·la'; vèrtexsActuals=[]; document.getElementById('btnDibuixarParcel·la').classList.add('active'); }
function iniciarDibuixSector() { modeDibuix='sector'; vèrtexsActuals=[]; document.getElementById('btnDibuixarSector').classList.add('active'); }
function iniciarDibuixFila() { modeDibuix='fila'; vèrtexsActuals=[]; } // Simplificat

function dibuixarCapaPoligons(id, dades, colDef, colHov, func) {
    const c = document.getElementById(id);
    if(!c) return; c.innerHTML = '';
    dades.forEach(d => {
        if(!d.coordenades || d.coordenades.length<3) return;
        const p = crearSVG('polygon', {points:d.coordenades.map(x=>x.join(',')).join(' '), fill:colDef, stroke:'rgba(0,0,0,0.5)', 'stroke-width':1});
        c.appendChild(p);
    });
}

function crearSVG(tag, attrs) {
    const el = document.createElementNS(SVG_NS, tag);
    for(let k in attrs) el.setAttribute(k, attrs[k]);
    return el;
}
function netejarDibuix(id) { document.getElementById(id).innerHTML = ''; vèrtexsActuals=[]; polygonActual=null; }

// ============================================
// HELPERS
// ============================================
function generarUUID() { return 'id-'+Date.now(); }
function actualitzarSelectorsEspecie() {
    const opts = Object.keys(especiesFruita).map(k => `<option value="${k}">${especiesFruita[k].nom}</option>`).join('');
    ['especieSector','especieCultiu'].forEach(id => {
        const el = document.getElementById(id);
        if(el) el.innerHTML = '<option value="">Seleccionar</option>' + opts;
    });
}
function actualitzarSelectorParcel·lesCultiu() {
    document.getElementById('idParcel·laCultiu').innerHTML = '<option value="">Sel</option>' + parcel·les.map(p=>`<option value="${p.id}">${p.nom}</option>`).join('');
}
function actualitzarSelectorCultiusCollita() {
    document.getElementById('idCultiuCollita').innerHTML = '<option value="">Sel</option>' + cultius.map(c=>`<option value="${c.id}">${especiesFruita[c.especie]?.nom}</option>`).join('');
}

// INICI
window.addEventListener('DOMContentLoaded', inicialitzar);