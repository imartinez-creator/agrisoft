// ============================================
// VARIABLES GLOBALS - Emmagatzemen totes les dades
// ============================================

let parcel·les = [];  // Array amb totes les parcel·les
let cultius = [];     // Array amb tots els cultius
let collites = [];    // Array amb totes les collites
let sectors = [];     // Array amb tots els sectors
let filesArbres = []; // Array amb totes les files d'arbres

// Informació de les espècies de fruita
let especiesFruita = {};

// Variables per al dibuix al mapa
let modeDibuix = null; // 'parcel·la', 'sector', 'fila'
let vèrtexsActuals = [];
let polygonActual = null;
let líniaActual = null;
let puntIntermediActual = null;
let idParcel·laSeleccionada = null;
let idSectorSeleccionat = null;
let idSectorPerFiles = null; // Per al modal de files

// Mapeig de colors per a diferents elements
const colors = {
    parcel·la: {
        default: 'rgba(45, 80, 22, 0.5)', // Verd fosc semitransparent
        hover: 'rgba(74, 124, 44, 0.7)',  // Verd clar semitransparent
        selected: 'rgba(212, 175, 55, 0.7)', // Dorat semitransparent
        dibuix: 'rgba(139, 111, 71, 0.7)' // Marró semitransparent
    },
    sector: {
        default: 'rgba(139, 111, 71, 0.5)', // Marró semitransparent
        hover: 'rgba(212, 175, 55, 0.7)',   // Dorat semitransparent
        selected: 'rgba(45, 80, 22, 0.7)',  // Verd fosc semitransparent
        dibuix: 'rgba(212, 175, 55, 0.7)'  // Dorat semitransparent
    },
    fila: {
        default: 'rgba(212, 175, 55, 0.6)', // Dorat semitransparent
        hover: 'rgba(45, 80, 22, 0.7)',    // Verd fosc semitransparent
        selected: 'rgba(139, 111, 71, 0.7)',// Marró semitransparent
        dibuix: 'rgba(45, 80, 22, 0.7)'   // Verd fosc semitransparent
    },
    grid: '#e7e5e4' // Gris clar per a la reixeta
};
const SVG_NS = "http://www.w3.org/2000/svg";

// ============================================
// INICIALITZACIÓ - S'executa quan es carrega la pàgina
// ============================================

function inicialitzar() {
    carregarDades();      // Carreguem les dades (ara no fa res)
    
    // Renderitzem totes les seccions
    renderitzarTauler();
    renderitzarParcel·les();
    renderitzarSectors();
    renderitzarEspecies();
    renderitzarCultius();
    renderitzarHistoric();
    renderitzarMapaGeneral();
    
    // Actualitzem els selectors dinàmics
    actualitzarSelectorParcel·lesCultiu();
    actualitzarSelectorCultiusCollita();
    actualitzarSelectorsEspecie();

    // Inicialitzem els mapes de dibuix
    inicialitzarMapaDibuix('svgMapaParcel·les', 'capaDibuix', gestionarClicMapaParcel·la, finalitzarDibuixParcel·la);
    inicialitzarMapaDibuix('svgMapaSectors', 'capaDibuixSector', gestionarClicMapaSector, finalitzarDibuixSector);
    inicialitzarMapaDibuix('svgMapaFiles', 'capaDibuixFila', gestionarClicMapaFila, finalitzarDibuixFila);

    // Actualitzem els selectors de parcel·la/cultiu/sector quan canviem de secció
    document.querySelectorAll('nav .nav-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const idSeccio = tab.getAttribute('onclick').split("'")[1];
            if (idSeccio === 'tauler') renderitzarTauler();
            else if (idSeccio === 'parcel·les') renderitzarParcel·les();
            else if (idSeccio === 'sectors') renderitzarSectors();
            else if (idSeccio === 'cultius') {
                actualitzarSelectorParcel·lesCultiu();
                renderitzarCultius();
            }
            else if (idSeccio === 'especie') renderitzarEspecies();
            else if (idSeccio === 'historic') {
                actualitzarSelectorCultiusCollita();
                renderitzarHistoric();
            }
            else if (idSeccio === 'mapa') renderitzarMapaGeneral();
        });
    });
}

// ============================================
// GESTIÓ DE DADES - Carregar i desar al localStorage
// ============================================

function carregarDades() {
    // MODIFICAT: Tot comentat per assegurar que l'app sempre comenci buida.
    
    // // Intentem carregar les dades del localStorage
    // const parcel·lesDesades = localStorage.getItem('agrisoft_parcel·les');
    // const cultiusDesats = localStorage.getItem('agrisoft_cultius');
    // const collitesDesades = localStorage.getItem('agrisoft_collites');
    // const sectorsDesats = localStorage.getItem('agrisoft_sectors');
    // const filesArbresDesades = localStorage.getItem('agrisoft_filesArbres');
    // const especiesDesades = localStorage.getItem('agrisoft_especies');

    // // Si hi ha dades desades, les carreguem
    // if (parcel·lesDesades) parcel·les = JSON.parse(parcel·lesDesades);
    // if (cultiusDesats) cultius = JSON.parse(cultiusDesats);
    // if (collitesDesades) collites = JSON.parse(collitesDesades);
    // if (sectorsDesats) sectors = JSON.parse(sectorsDesats);
    // if (filesArbresDesades) filesArbres = JSON.parse(filesArbresDesades);
    
    // if (especiesDesades) {
    //     especiesFruita = JSON.parse(especiesDesades);
    // } else {
    //     especiesFruita = {}; // Assegurem que comenci buit si no hi ha res desat
    // }
}

function desarDades() {
    // MODIFICAT: Tot comentat per assegurar que l'app no desï res.

    // // Desem totes les dades al localStorage
    // localStorage.setItem('agrisoft_parcel·les', JSON.stringify(parcel·les));
    // localStorage.setItem('agrisoft_cultius', JSON.stringify(cultius));
    // localStorage.setItem('agrisoft_collites', JSON.stringify(collites));
    // localStorage.setItem('agrisoft_sectors', JSON.stringify(sectors));
    // localStorage.setItem('agrisoft_filesArbres', JSON.stringify(filesArbres));
    // localStorage.setItem('agrisoft_especies', JSON.stringify(especiesFruita));
}

// Aquesta funció ja no es crida, però es manté per si de cas.
function crearDadesExemple() {
    // ... (Codi d'exemple eliminat per brevetat, ja no s'utilitza) ...
}

// ============================================
// GESTIÓ DE DADES JSON - Importar (Exportar no implementat)
// ============================================

function obrirModalImportarGeoJSON() {
    document.getElementById('modalImportarGeoJSON').classList.add('active');
}

function tancarModalImportarGeoJSON() {
    document.getElementById('modalImportarGeoJSON').classList.remove('active');
}

function processarImportGeoJSON() {
    // Aquesta funció requeriria una llibreria per parsejar KML o GeoJSON.
    // És una funcionalitat avançada que no està implementada.
    alert('Funció d\'importació no implementada en aquesta demo.');
    tancarModalImportarGeoJSON();
}

// ============================================
// NAVEGACIÓ - Canviar entre seccions
// ============================================

function mostrarSeccio(idSeccio) {
    // Amaguem totes les seccions
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
    
    // Mostrem la secció seleccionada
    document.getElementById(idSeccio).classList.add('active');
    document.querySelector(`.nav-tab[onclick="mostrarSeccio('${idSeccio}')"]`).classList.add('active');

    // Actualitzem el contingut de la secció
    if (idSeccio === 'tauler') renderitzarTauler();
    else if (idSeccio === 'parcel·les') renderitzarParcel·les();
    else if (idSeccio === 'sectors') renderitzarSectors();
    else if (idSeccio === 'cultius') {
        actualitzarSelectorParcel·lesCultiu();
        renderitzarCultius();
    }
    else if (idSeccio === 'especie') renderitzarEspecies();
    else if (idSeccio === 'historic') {
        actualitzarSelectorCultiusCollita();
        renderitzarHistoric();
    }
    else if (idSeccio === 'mapa') renderitzarMapaGeneral();
}

// ============================================
// TAULER PRINCIPAL
// ============================================

function renderitzarTauler() {
    // Calculem les estadístiques
    const areaTotal = parcel·les.reduce((suma, p) => suma + parseFloat(p.area), 0);
    const cultiusActius = cultius.filter(c => c.estat === 'actiu' || c.estat === 'collita').length;
    const produccióTotal = collites.reduce((suma, c) => suma + parseFloat(c.quantitat), 0);
    const sectorsActius = sectors.filter(s => s.estat === 'actiu' || s.estat === 'collita').length;

    // Mostrem les estadístiques
    document.getElementById('estadistiquesTauler').innerHTML = `
        <div class="stat-card">
            <div class="stat-label">Total Parcel·les</div>
            <div class="stat-value">${parcel·les.length}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Superfície Total</div>
            <div class="stat-value">${areaTotal.toFixed(1)} <span style="font-size: 1rem; color: var(--text-gris);">ha</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Sectors Actius</div>
            <div class="stat-value">${sectorsActius}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Cultius (Llaurats) Actius</div>
            <div class="stat-value">${cultiusActius}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Producció Total (registrada)</div>
            <div class="stat-value">${(produccióTotal / 1000).toFixed(1)} <span style="font-size: 1rem; color: var(--text-gris);">t</span></div>
        </div>
    `;

    // Mostrem les properes collites
    const avui = new Date();
    avui.setHours(0, 0, 0, 0); // Per comparar només dates
    
    const properes = cultius.filter(c => {
        if (!c.dataCollita) return false;
        const dataCollita = new Date(c.dataCollita);
        const diesFins = Math.ceil((dataCollita.getTime() - avui.getTime()) / (1000 * 60 * 60 * 24));
        return diesFins >= 0 && diesFins <= 30 && c.estat !== 'completat';
    }).sort((a, b) => new Date(a.dataCollita) - new Date(b.dataCollita));

    if (properes.length > 0) {
        document.getElementById('properesCollites').innerHTML = properes.map(c => {
            const parcel·la = parcel·les.find(p => p.id === c.idParcel·la);
            const especie = especiesFruita[c.especie];
            
            // Comprovació per si l'espècie s'ha eliminat
            if (!parcel·la || !especie) return '<div class="alert alert-warning">ℹ️ Hi ha una collita propera amb dades incompletes (parcel·la o espècie eliminada).</div>';

            const dataCollita = new Date(c.dataCollita);
            const diesFins = Math.ceil((dataCollita - avui) / (1000 * 60 * 60 * 24));
            
            return `
                <div class="alert alert-warning">
                    <div>⚠️</div>
                    <div>
                        <strong>${especie.nom}</strong> a ${parcel·la.nom} - 
                        Collita estimada: ${formatarData(c.dataCollita)} 
                        (${diesFins} dies)
                    </div>
                </div>
            `;
        }).join('');
    } else {
        document.getElementById('properesCollites').innerHTML = `
            <div class="alert alert-info">
                <div>ℹ️</div>
                <div>No hi ha collites programades per als propers 30 dies</div>
            </div>
        `;
    }

    // Mostrem el resum de parcel·les
    const resumDiv = document.getElementById('resumParcel·les');
    if (parcel·les.length === 0) {
        resumDiv.innerHTML = '<p>No hi ha parcel·les per mostrar.</p>';
    } else {
        resumDiv.innerHTML = parcel·les.slice(0, 6).map(p => {
            const cultiusParcel·la = cultius.filter(c => c.idParcel·la === p.id && c.estat !== 'completat');
            return `
                <div class="item-card">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                        <div>
                            <div class="item-title">${p.nom}</div>
                            <div class="item-subtitle">${p.refCadastral}</div>
                        </div>
                        <span class="badge badge-info">${p.area} ha</span>
                    </div>
                    <div class="item-info">
                        <div class="info-row">
                            <span class="info-label">Cultius actius:</span>
                            <span>${cultiusParcel·la.length}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Reg:</span>
                            <span>${p.reg}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }
}

// ============================================
// GESTIÓ DE PARCEL·LES
// ============================================

function renderitzarParcel·les() {
    const cerca = document.getElementById('cercaParcel·les')?.value.toLowerCase() || '';
    const filtrades = parcel·les.filter(p => 
        p.nom.toLowerCase().includes(cerca) ||
        p.refCadastral.toLowerCase().includes(cerca) ||
        (p.ubicació && p.ubicació.toLowerCase().includes(cerca))
    );
    
    const graella = document.getElementById('graellaParcel·les');
    if (!graella) return;

    if (filtrades.length === 0) {
        graella.innerHTML = `
            <div class="empty-state">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🗺️</div>
                <div style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--text);">No hi ha parcel·les</div>
                <p>Comença afegint la teva primera parcel·la</p>
            </div>
        `;
    } else {
        graella.innerHTML = filtrades.map(p => {
            const cultiusParcel·la = cultius.filter(c => c.idParcel·la === p.id && c.estat !== 'completat');
            return `
                <div class="item-card">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                        <div>
                            <div class="item-title">${p.nom}</div>
                            <div class="item-subtitle">${p.refCadastral}</div>
                        </div>
                        <span class="badge badge-info">${p.area} ha</span>
                    </div>
                    <div class="item-info">
                        <div class="info-row">
                            <span class="info-label">Ubicació:</span>
                            <span>${p.ubicació || '-'}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Tipus de sòl:</span>
                            <span>${p.tipusSòl}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Reg:</span>
                            <span>${p.reg}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Cultius actius:</span>
                            <span>${cultiusParcel·la.length}</span>
                        </div>
                    </div>
                    <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                        <button class="btn btn-outline btn-sm" onclick="editarParcel·la('${p.id}')">Editar</button>
                        <button class="btn btn-outline btn-sm" onclick="eliminarParcel·la('${p.id}')" style="margin-left: auto; color: #dc2626;">Eliminar</button>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    // Dibuixem les parcel·les al mapa de la secció
    dibuixarCapaPoligons('capaParcel·les', parcel·les, colors.parcel·la.default, colors.parcel·la.hover, seleccionarParcel·la);
}

function filtrarParcel·les() {
    renderitzarParcel·les();
}

function obrirModalParcel·la(idParcel·la = null) {
    const modal = document.getElementById('modalParcel·la');
    const formulari = document.getElementById('formulariParcel·la');
    formulari.reset();
    document.getElementById('idParcel·la').value = '';
    document.getElementById('coordenadesParcel·la').value = '';

    if (idParcel·la) {
        // Editar parcel·la existent
        const parcel·la = parcel·les.find(p => p.id === idParcel·la);
        document.getElementById('titolModalParcel·la').textContent = 'Editar Parcel·la Cadastral';
        document.getElementById('idParcel·la').value = parcel·la.id;
        document.getElementById('nomParcel·la').value = parcel·la.nom;
        document.getElementById('refCadastralParcel·la').value = parcel·la.refCadastral;
        document.getElementById('areaParcel·la').value = parcel·la.area;
        document.getElementById('coordenadesParcel·la').value = parcel·la.coordenades ? parcel·la.coordenades.map(c => c.join(',')).join('; ') : '';
        document.getElementById('ubicacióParcel·la').value = parcel·la.ubicació || '';
        document.getElementById('tipusSòlParcel·la').value = parcel·la.tipusSòl;
        document.getElementById('regParcel·la').value = parcel·la.reg;
        document.getElementById('notesParcel·la').value = parcel·la.notes || '';
    } else {
        // Nova parcel·la
        document.getElementById('titolModalParcel·la').textContent = 'Nova Parcel·la Cadastral';
    }

    modal.classList.add('active');
}

function tancarModalParcel·la() {
    document.getElementById('modalParcel·la').classList.remove('active');
    netejarDibuix('capaDibuix');
    modeDibuix = null;
    document.getElementById('btnDibuixarParcel·la').classList.remove('active');
}

function editarParcel·la(id) {
    obrirModalParcel·la(id);
}

function eliminarParcel·la(id) {
    if (confirm('Estàs segur que vols eliminar aquesta parcel·la? Això NO eliminarà els cultius o sectors associats, però quedaran orfes.')) {
        parcel·les = parcel·les.filter(p => p.id !== id);
        desarDades();
        renderitzarParcel·les();
        renderitzarMapaGeneral();
        renderitzarTauler();
    }
}

function desarParcel·la() {
    const id = document.getElementById('idParcel·la').value;
    const nom = document.getElementById('nomParcel·la').value;
    const refCadastral = document.getElementById('refCadastralParcel·la').value;
    const area = parseFloat(document.getElementById('areaParcel·la').value);
    const coordenadesText = document.getElementById('coordenadesParcel·la').value;
    const ubicació = document.getElementById('ubicacióParcel·la').value;
    const tipusSòl = document.getElementById('tipusSòlParcel·la').value;
    const reg = document.getElementById('regParcel·la').value;
    const notes = document.getElementById('notesParcel·la').value;

    if (!nom || !refCadastral || isNaN(area) || area <= 0) {
        alert('Si us plau, omple tots els camps obligatoris (*).');
        return;
    }

    let coordenades = [];
    if (vèrtexsActuals.length >= 3) {
        coordenades = vèrtexsActuals; // Agafem les del dibuix
    } else if (coordenadesText) {
        try {
            coordenades = coordenadesText.split(';').map(pair => 
                pair.trim().split(',').map(num => parseFloat(num.trim()))
            );
            if (coordenades.some(pair => pair.length !== 2 || isNaN(pair[0]) || isNaN(pair[1]))) {
                throw new Error('Format de coordenades invàlid.');
            }
        } catch (e) {
            alert('Format de coordenades invàlid. Ha de ser "x1,y1; x2,y2; ..."');
            return;
        }
    }

    const parcel·la = {
        id: id || generarUUID(),
        nom: nom,
        refCadastral: refCadastral,
        area: area,
        coordenades: coordenades,
        ubicació: ubicació,
        tipusSòl: tipusSòl,
        reg: reg,
        notes: notes
    };

    if (id) {
        const index = parcel·les.findIndex(p => p.id === id);
        parcel·les[index] = parcel·la;
    } else {
        parcel·les.push(parcel·la);
    }

    desarDades();
    tancarModalParcel·la();
    renderitzarParcel·les();
    renderitzarMapaGeneral();
    renderitzarTauler();
    actualitzarSelectorParcel·lesCultiu();
    netejarDibuix('capaDibuix');
}

function seleccionarParcel·la(id) {
    idParcel·laSeleccionada = id;
    // Ressaltem la parcel·la seleccionada al mapa
    dibuixarCapaPoligons('capaParcel·les', parcel·les, colors.parcel·la.default, colors.parcel·la.hover, seleccionarParcel·la, id);
    
    // Aquí podries mostrar informació addicional de la parcel·la si volguessis
    console.log("Parcel·la seleccionada:", id);
}

// ============================================
// GESTIÓ DE SECTORS
// ============================================

function renderitzarSectors() {
    const graella = document.getElementById('graellaSectors');
    if (!graella) return;

    if (sectors.length === 0) {
        graella.innerHTML = `
            <div class="empty-state">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🌳</div>
                <div style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--text);">No hi ha sectors de cultiu</div>
                <p>Crea sectors per organitzar les teves parcel·les agronòmicament.</p>
            </div>
        `;
    } else {
        graella.innerHTML = sectors.map(s => {
            const especie = especiesFruita[s.especie] || { nom: 'Espècie Eliminada' };
            const totalArbres = (s.nombreFiles || 0) * (s.arbresPerFila || 0);
            return `
                <div class="item-card">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                        <div>
                            <div class="item-title">${s.nom}</div>
                            <div class="item-subtitle">${especie.nom}</div>
                        </div>
                        <span class="badge badge-info">${s.superficie.toFixed(2)} ha</span>
                    </div>
                    <div class="item-info">
                        <div class="info-row">
                            <span class="info-label">Estat:</span>
                            <span class="badge ${s.estat === 'actiu' ? 'badge-success' : (s.estat === 'collita' ? 'badge-warning' : 'badge-info')}">${s.estat}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Files:</span>
                            <span>${s.nombreFiles || '-'}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Arbres totals (est.):</span>
                            <span>${totalArbres > 0 ? totalArbres : '-'}</span>
                        </div>
                    </div>
                    <div style="margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <button class="btn btn-outline btn-sm" onclick="obrirModalSector('${s.id}')">Editar</button>
                        <button class="btn btn-outline btn-sm" onclick="obrirModalFiles('${s.id}')">Gestionar Files</button>
                        <button class="btn btn-outline btn-sm" onclick="eliminarSector('${s.id}')" style="margin-left: auto; color: #dc2626;">Eliminar</button>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Dibuixem parcel·les (fons) i sectors (davant)
    dibuixarCapaPoligons('capaParcel·lesSectors', parcel·les, colors.parcel·la.default, colors.parcel·la.hover, null);
    dibuixarCapaPoligons('capaSectorsLlista', sectors, colors.sector.default, colors.sector.hover, seleccionarSector, idSectorSeleccionat);
}

function obrirModalSector(idSector = null) {
    const modal = document.getElementById('modalSector');
    const formulari = document.getElementById('formulariSector');
    formulari.reset();
    document.getElementById('idSector').value = '';
    idSectorSeleccionat = null;
    
    // Actualitzem el selector d'espècies
    actualitzarSelectorsEspecie();

    if (idSector) {
        // Editar sector
        const sector = sectors.find(s => s.id === idSector);
        idSectorSeleccionat = idSector;
        document.getElementById('titolModalSector').textContent = 'Editar Sector de Cultiu';
        document.getElementById('idSector').value = sector.id;
        document.getElementById('nomSector').value = sector.nom;
        document.getElementById('especieSector').value = sector.especie;
        document.getElementById('superficieSector').value = sector.superficie;
        document.getElementById('dataPlantacioSector').value = sector.dataPlantacio;
        document.getElementById('nombreFilesSector').value = sector.nombreFiles;
        document.getElementById('arbresPerFilaSector').value = sector.arbresPerFila;
        document.getElementById('estatSector').value = sector.estat;
        document.getElementById('notesSector').value = sector.notes;
        // TODO: Calcular parcel·les que ocupa
    } else {
        // Nou sector
        document.getElementById('titolModalSector').textContent = 'Nou Sector de Cultiu';
    }
    
    modal.classList.add('active');
    actualitzarInfoEspecieSector(); // Cridem per si hi ha valor preseleccionat
}

function tancarModalSector() {
    document.getElementById('modalSector').classList.remove('active');
    netejarDibuix('capaDibuixSector');
    modeDibuix = null;
    idSectorSeleccionat = null;
    document.getElementById('btnDibuixarSector').classList.remove('active');
}

function desarSector() {
    const id = document.getElementById('idSector').value;
    const nom = document.getElementById('nomSector').value;
    const especie = document.getElementById('especieSector').value;
    const superficie = parseFloat(document.getElementById('superficieSector').value);
    const dataPlantacio = document.getElementById('dataPlantacioSector').value;
    const nombreFiles = parseInt(document.getElementById('nombreFilesSector').value);
    const arbresPerFila = parseInt(document.getElementById('arbresPerFilaSector').value) || 0;
    const estat = document.getElementById('estatSector').value;
    const notes = document.getElementById('notesSector').value;

    if (!nom || !especie || isNaN(superficie) || superficie <= 0 || isNaN(nombreFiles) || nombreFiles <= 0) {
        alert('Si us plau, omple tots els camps obligatoris (*).');
        return;
    }

    let coordenades = [];
    const sectorExistent = id ? sectors.find(s => s.id === id) : null;

    if (vèrtexsActuals.length >= 3) {
        coordenades = vèrtexsActuals; // Agafem les del dibuix
    } else if (sectorExistent && sectorExistent.coordenades) {
        coordenades = sectorExistent.coordenades; // Mantenim les coordenades existents
    }

    const sector = {
        id: id || generarUUID(),
        nom, especie, superficie, dataPlantacio, nombreFiles, arbresPerFila, estat, notes,
        coordenades: coordenades
    };

    if (id) {
        const index = sectors.findIndex(s => s.id === id);
        sectors[index] = sector;
    } else {
        sectors.push(sector);
    }

    desarDades();
    tancarModalSector();
    renderitzarSectors();
    renderitzarMapaGeneral();
    renderitzarTauler();
    netejarDibuix('capaDibuixSector');
}

function eliminarSector(id) {
    if (confirm('Estàs segur que vols eliminar aquest sector? Això també eliminarà totes les files d\'arbres associades.')) {
        sectors = sectors.filter(s => s.id !== id);
        // Eliminar files associades
        filesArbres = filesArbres.filter(f => f.idSector !== id);
        desarDades();
        renderitzarSectors();
        renderitzarMapaGeneral();
        renderitzarTauler();
    }
}

function seleccionarSector(id) {
    idSectorSeleccionat = id;
    dibuixarCapaPoligons('capaSectorsLlista', sectors, colors.sector.default, colors.sector.hover, seleccionarSector, id);
    console.log("Sector seleccionat:", id);
}

function actualitzarInfoEspecieSector() {
    const especieId = document.getElementById('especieSector').value;
    const infoDiv = document.getElementById('infoEspecieSector');
    if (especieId && especiesFruita[especieId]) {
        const v = especiesFruita[especieId];
        infoDiv.textContent = `Tipus: ${v.tipus}, Maduració: ${v.diesMaduració} dies, Rendiment: ${v.rendimentPerArbre} kg/arbre`;
    } else {
        infoDiv.textContent = '';
    }
}

// ============================================
// GESTIÓ DE FILES D'ARBRES (Modal)
// ============================================

function obrirModalFiles(idSector) {
    idSectorPerFiles = idSector; // Guardem l'ID del sector pel qual gestionem files
    const sector = sectors.find(s => s.id === idSector);
    if (!sector) return;
    
    // Comprovem si l'espècie existeix
    const especie = especiesFruita[sector.especie] || { nom: 'Espècie Eliminada' };

    document.getElementById('titolModalFiles').textContent = `Gestió de Files - ${sector.nom}`;
    document.getElementById('infoSectorFiles').innerHTML = `
        <strong>Sector:</strong> ${sector.nom} (${sector.superficie} ha) <br>
        <strong>Espècie:</strong> ${especie.nom} <br>
        <strong>Files totals definides:</strong> ${sector.nombreFiles}
    `;

    renderitzarTaulaFiles(idSector);
    
    // Dibuixem el polígon del sector com a fons
    const capaPoligon = document.getElementById('capaPoligonSectorFiles');
    capaPoligon.innerHTML = ''; // Netegem
    if (sector.coordenades && sector.coordenades.length > 0) {
        const poligon = crearElementSVG('polygon', {
            points: sector.coordenades.map(p => p.join(',')).join(' '),
            fill: colors.sector.default,
            stroke: colors.sector.dibuix,
            'stroke-width': 2
        });
        capaPoligon.appendChild(poligon);
    }

    document.getElementById('modalFiles').classList.add('active');
}

function tancarModalFiles() {
    document.getElementById('modalFiles').classList.remove('active');
    netejarDibuix('capaDibuixFila');
    modeDibuix = null;
    idSectorPerFiles = null;
    document.getElementById('btnDibuixarFila').classList.remove('active');
}

function renderitzarTaulaFiles(idSector) {
    const filesDelSector = filesArbres.filter(f => f.idSector === idSector);
    const tbody = document.getElementById('taulaFiles');
    
    if (filesDelSector.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">No hi ha files registrades per a aquest sector.</td></tr>';
    } else {
        tbody.innerHTML = filesDelSector.map(f => `
            <tr>
                <td>${f.numero}</td>
                <td>${f.arbres}</td>
                <td>${f.longitud.toFixed(1)} m</td>
                <td>
                    <button class="btn btn-outline btn-sm" onclick="eliminarFila('${f.id}')">Eliminar</button>
                </td>
            </tr>
        `).join('');
    }

    // Dibuixem les línies de les files al mapa del modal
    dibuixarCapaLinies('capaFilesLlista', filesDelSector, colors.fila.default, colors.fila.hover, null);
}

function desarFila(coordenades, longitud) {
    if (!idSectorPerFiles) return;
    const sector = sectors.find(s => s.id === idSectorPerFiles);
    const filesExistents = filesArbres.filter(f => f.idSector === idSectorPerFiles);
    
    const novaFila = {
        id: generarUUID(),
        idSector: idSectorPerFiles,
        numero: filesExistents.length + 1,
        arbres: sector.arbresPerFila || 0, // Agafem la mitjana del sector
        longitud: longitud,
        coordenades: coordenades
    };
    
    filesArbres.push(novaFila);
    desarDades();
    renderitzarTaulaFiles(idSectorPerFiles);
    renderitzarMapaGeneral(); // Actualitzem el mapa general
}

function eliminarFila(idFila) {
    if (confirm('Estàs segur que vols eliminar aquesta fila?')) {
        filesArbres = filesArbres.filter(f => f.id !== idFila);
        desarDades();
        renderitzarTaulaFiles(idSectorPerFiles); // idSectorPerFiles encara és actiu
        renderitzarMapaGeneral();
    }
}

function generarFilesAutomatiques() {
    alert('Funció de generació automàtica de files no implementada.');
}

// ============================================
// GESTIÓ DE CULTIUS (LLURATS)
// ============================================

function renderitzarCultius() {
    const cerca = document.getElementById('cercaCultius')?.value.toLowerCase() || '';
    const estat = document.getElementById('filtreEstatCultius')?.value || '';
    
    const filtrats = cultius.filter(c => {
        const parcel·la = parcel·les.find(p => p.id === c.idParcel·la);
        const especie = especiesFruita[c.especie];
        
        // Si la parcel·la o l'espècie s'han eliminat, no podem filtrar
        if (!parcel·la || !especie) return false;

        const textCerca = (parcel·la.nom + especie.nom + especie.tipus).toLowerCase();
        const coincideixCerca = textCerca.includes(cerca);
        const coincideixEstat = estat ? c.estat === estat : true;
        
        return coincideixCerca && coincideixEstat;
    });

    const graella = document.getElementById('graellaCultius');
    if (!graella) return;

    if (filtrats.length === 0) {
        graella.innerHTML = `
            <div class="empty-state">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🍎</div>
                <div style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--text);">No hi ha cultius</div>
                <p>Registra els cultius (llaürats) de les teves parcel·les.</p>
            </div>
        `;
    } else {
        graella.innerHTML = filtrats.map(c => {
            const parcel·la = parcel·les.find(p => p.id === c.idParcel·la);
            const especie = especiesFruita[c.especie];
            // Comprovació extra per si s'han eliminat
            const nomEspecie = especie ? especie.nom : 'Espècie Eliminada';
            const nomParcel·la = parcel·la ? parcel·la.nom : 'Parcel·la Eliminada';

            return `
                <div class="item-card">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                        <div>
                            <div class="item-title">${nomEspecie}</div>
                            <div class="item-subtitle">A: <strong>${nomParcel·la}</strong></div>
                        </div>
                        <span class="badge ${c.estat === 'actiu' ? 'badge-success' : (c.estat === 'collita' ? 'badge-warning' : 'badge-info')}">${c.estat}</span>
                    </div>
                    <div class="item-info">
                        <div class="info-row">
                            <span class="info-label">Data Plantació:</span>
                            <span>${formatarData(c.dataPlantació)}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Collita Estimada:</span>
                            <span>${formatarData(c.dataCollita)}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Nombre d'Arbres:</span>
                            <span>${c.nombreArbres || '-'}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Densitat:</span>
                            <span>${c.densitat} arbres/ha</span>
                        </div>
                    </div>
                    <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                        <button class="btn btn-outline btn-sm" onclick="editarCultiu('${c.id}')">Editar</button>
                        <button class="btn btn-outline btn-sm" onclick="eliminarCultiu('${c.id}')" style="margin-left: auto; color: #dc2626;">Eliminar</button>
                    </div>
                </div>
            `;
        }).join('');
    }
}

function filtrarCultius() {
    renderitzarCultius();
}

function obrirModalCultiu(idCultiu = null) {
    const modal = document.getElementById('modalCultiu');
    const formulari = document.getElementById('formulariCultiu');
    formulari.reset();
    document.getElementById('idCultiu').value = '';
    
    // Assegurem que els selectors estan plens
    actualitzarSelectorParcel·lesCultiu();
    actualitzarSelectorsEspecie();
    
    // Comprovem si hi ha espècies o parcel·les
    if (Object.keys(especiesFruita).length === 0) {
        alert('No hi ha cap espècie definida. Si us plau, crea una espècie a la secció "Espècie" abans de crear un cultiu.');
        return;
    }
    if (parcel·les.length === 0) {
        alert('No hi ha cap parcel·la definida. Si us plau, crea una parcel·la abans de crear un cultiu.');
        return;
    }

    if (idCultiu) {
        // Editar
        const cultiu = cultius.find(c => c.id === idCultiu);
        document.getElementById('titolModalCultiu').textContent = 'Editar Cultiu';
        document.getElementById('idCultiu').value = cultiu.id;
        document.getElementById('idParcel·laCultiu').value = cultiu.idParcel·la;
        document.getElementById('especieCultiu').value = cultiu.especie;
        document.getElementById('dataPlantació').value = cultiu.dataPlantació;
        document.getElementById('dataCollita').value = cultiu.dataCollita;
        document.getElementById('nombreArbres').value = cultiu.nombreArbres;
        document.getElementById('densitat').value = cultiu.densitat;
        document.getElementById('estatCultiu').value = cultiu.estat;
        document.getElementById('notesCultiu').value = cultiu.notes;
    } else {
        // Nou
        document.getElementById('titolModalCultiu').textContent = 'Nou Cultiu';
    }
    
    // Actualitzem infos
    actualitzarInfoParcel·laCultiu();
    actualitzarInfoEspecie();
    
    modal.classList.add('active');
}

function tancarModalCultiu() {
    document.getElementById('modalCultiu').classList.remove('active');
}

function desarCultiu() {
    const id = document.getElementById('idCultiu').value;
    const idParcel·la = document.getElementById('idParcel·laCultiu').value;
    const especie = document.getElementById('especieCultiu').value;
    const dataPlantació = document.getElementById('dataPlantació').value;
    const dataCollita = document.getElementById('dataCollita').value;
    const nombreArbres = parseInt(document.getElementById('nombreArbres').value) || 0;
    const densitat = parseInt(document.getElementById('densitat').value) || 400;
    const estat = document.getElementById('estatCultiu').value;
    const notes = document.getElementById('notesCultiu').value;

    if (!idParcel·la || !especie || !dataPlantació) {
        alert('Si us plau, omple tots els camps obligatoris (*).');
        return;
    }

    const cultiu = {
        id: id || generarUUID(),
        idParcel·la, especie, dataPlantació, dataCollita, nombreArbres, densitat, estat, notes
    };

    if (id) {
        const index = cultius.findIndex(c => c.id === id);
        cultius[index] = cultiu;
    } else {
        cultius.push(cultiu);
    }

    desarDades();
    tancarModalCultiu();
    renderitzarCultius();
    renderitzarTauler();
    actualitzarSelectorCultiusCollita(); // Per al modal de collites
}

function eliminarCultiu(id) {
    if (confirm('Estàs segur que vols eliminar aquest cultiu? Això NO eliminarà els registres de collita associats.')) {
        cultius = cultius.filter(c => c.id !== id);
        desarDades();
        renderitzarCultius();
        renderitzarTauler();
        actualitzarSelectorCultiusCollita();
    }
}

// Funcions auxiliars per al modal de cultiu
function actualitzarSelectorParcel·lesCultiu() {
    const select = document.getElementById('idParcel·laCultiu');
    if (!select) return;
    const valorActual = select.value;
    select.innerHTML = '<option value="">Seleccionar parcel·la</option>';
    select.innerHTML += parcel·les.map(p => 
        `<option value="${p.id}">${p.nom} (${p.refCadastral})</option>`
    ).join('');
    select.value = valorActual;
}

function actualitzarInfoParcel·laCultiu() {
    const idParcel·la = document.getElementById('idParcel·laCultiu').value;
    const infoDiv = document.getElementById('infoParcel·laCultiu');
    if (idParcel·la) {
        const p = parcel·les.find(p => p.id === idParcel·la);
        infoDiv.textContent = `Superfície: ${p.area} ha, Reg: ${p.reg}, Sòl: ${p.tipusSòl}`;
    } else {
        infoDiv.textContent = '';
    }
    calcularNombreArbres();
}

function actualitzarInfoEspecie() {
    const especieId = document.getElementById('especieCultiu').value;
    const infoDiv = document.getElementById('infoEspecie');
    if (especieId && especiesFruita[especieId]) {
        const v = especiesFruita[especieId];
        infoDiv.textContent = `Tipus: ${v.tipus}, Maduració: ${v.diesMaduració} dies, Rendiment: ${v.rendimentPerArbre} kg/arbre`;
    } else {
        infoDiv.textContent = '';
    }
    calcularDataCollita();
}

function calcularDataCollita() {
    const dataPlantació = document.getElementById('dataPlantació').value;
    const especieId = document.getElementById('especieCultiu').value;
    
    if (dataPlantació && especieId && especiesFruita[especieId]) {
        const dies = especiesFruita[especieId].diesMaduració;
        if (dies) {
            const data = new Date(dataPlantació);
            data.setDate(data.getDate() + dies);
            document.getElementById('dataCollita').value = data.toISOString().split('T')[0];
        } else {
             document.getElementById('dataCollita').value = '';
        }
    } else {
        document.getElementById('dataCollita').value = '';
    }
}

function calcularNombreArbres() {
    const idParcel·la = document.getElementById('idParcel·laCultiu').value;
    const densitat = parseInt(document.getElementById('densitat').value);
    
    if (idParcel·la && !isNaN(densitat)) {
        const p = parcel·les.find(p => p.id === idParcel·la);
        const arbres = p.area * densitat;
        document.getElementById('nombreArbres').value = Math.round(arbres);
    }
}


// ============================================
// GESTIÓ DE ESPÈCIES
// ============================================

function renderitzarEspecies() {
    const graella = document.getElementById('graellaEspecies');
    if (!graella) return;

    const claus = Object.keys(especiesFruita);

    if (claus.length === 0) {
        graella.innerHTML = `
            <div class="empty-state">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🍎</div>
                <div style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--text);">No hi ha espècies definides</div>
                <p>Afegeix les espècies de fruita que gestiones.</p>
            </div>
        `;
        return;
    }

    graella.innerHTML = claus.map(clau => {
        const v = especiesFruita[clau];
        return `
            <div class="item-card">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                    <div>
                        <div class="item-title">${v.nom}</div>
                        <div class="item-subtitle">ID: <strong>${clau}</strong></div>
                    </div>
                    <span class="badge" style="background-color: ${getColorForString(v.tipus, 0.2)}; color: ${getColorForString(v.tipus, 1)};">${v.tipus}</span>
                </div>
                
                <div class="item-info">
                    <div class="info-row">
                        <span class="info-label">Dies Maduració:</span>
                        <span>${v.diesMaduració || '-'} dies</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Rendiment Est.:</span>
                        <span>${v.rendimentPerArbre || '-'} kg/arbre</span>
                    </div>
                </div>
                <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                    <button class="btn btn-outline btn-sm" onclick="obrirModalEspecie('${clau}')">Editar</button>
                    <button class="btn btn-outline btn-sm" onclick="eliminarEspecie('${clau}')" style="margin-left: auto; color: #dc2626;">Eliminar</button>
                </div>
            </div>
        `;
    }).join('');
}

function obrirModalEspecie(idEspecie = null) {
    const modal = document.getElementById('modalEspecie');
    const formulari = document.getElementById('formulariEspecie');
    formulari.reset();
    
    const idInput = document.getElementById('idEspecie');
    idInput.disabled = false;

    if (idEspecie) {
        // Editar espècie existent
        const especie = especiesFruita[idEspecie];
        document.getElementById('titolModalEspecie').textContent = 'Editar Espècie';
        idInput.value = idEspecie;
        idInput.disabled = true; // No es pot canviar la clau
        document.getElementById('nomEspecie').value = especie.nom;
        document.getElementById('tipusEspecie').value = especie.tipus;
        document.getElementById('diesMaduracióEspecie').value = especie.diesMaduració;
        document.getElementById('rendimentEspecie').value = especie.rendimentPerArbre;
    } else {
        // Nova espècie
        document.getElementById('titolModalEspecie').textContent = 'Nova Espècie';
    }
    modal.classList.add('active');
}

function tancarModalEspecie() {
    document.getElementById('modalEspecie').classList.remove('active');
}

function desarEspecie() {
    const id = document.getElementById('idEspecie').value.trim();
    const nom = document.getElementById('nomEspecie').value.trim();
    const tipus = document.getElementById('tipusEspecie').value.trim();
    const dies = parseInt(document.getElementById('diesMaduracióEspecie').value) || 0;
    const rendiment = parseInt(document.getElementById('rendimentEspecie').value) || 0;

    if (!id || !nom || !tipus) {
        alert('Si us plau, omple els camps Identificador, Nom i Tipus.');
        return;
    }
    
    // Mirem si la clau ja existeix (només si és nova)
    if (!document.getElementById('idEspecie').disabled && especiesFruita[id]) {
        alert('Error: L\'identificador (clau) ja existeix. Tria\'n un altre.');
        return;
    }

    especiesFruita[id] = {
        nom: nom,
        tipus: tipus,
        diesMaduració: dies,
        rendimentPerArbre: rendiment
    };

    desarDades(); // Desem tot, incloent el canvi a especiesFruita
    tancarModalEspecie();
    renderitzarEspecies();
    actualitzarSelectorsEspecie(); // Actualitzem els selectors en altres formularis
}

function eliminarEspecie(idEspecie) {
    // Comprovar si s'està utilitzant
    const enUsCultius = cultius.some(c => c.especie === idEspecie);
    const enUsSectors = sectors.some(s => s.especie === idEspecie);
    
    if (enUsCultius || enUsSectors) {
        alert('No es pot eliminar aquesta espècie perquè està sent utilitzada en cultius o sectors existents.');
        return;
    }

    if (confirm(`Estàs segur que vols eliminar l'espècie "${especiesFruita[idEspecie].nom}"?`)) {
        delete especiesFruita[idEspecie];
        desarDades();
        renderitzarEspecies();
        actualitzarSelectorsEspecie();
    }
}

// Funció per actualitzar tots els selectors d'espècie als modals
function actualitzarSelectorsEspecie() {
    const selectors = [
        document.getElementById('especieSector'), 
        document.getElementById('especieCultiu')
    ];
    
    const opcions = Object.keys(especiesFruita).sort((a,b) => especiesFruita[a].nom.localeCompare(especiesFruita[b].nom)).map(clau => 
        `<option value="${clau}">${especiesFruita[clau].nom}</option>`
    ).join('');

    selectors.forEach(select => {
        if (select) {
            const valorActual = select.value;
            select.innerHTML = `<option value="">Seleccionar espècie</option>` + opcions;
            if (valorActual && especiesFruita[valorActual]) {
                select.value = valorActual; // Intentem preservar la selecció
            }
        }
    });
}

// ============================================
// GESTIÓ HISTÒRIC I COLLITES
// ============================================

function renderitzarHistoric() {
    // Estadístiques
    const produccióTotal = collites.reduce((suma, c) => suma + parseFloat(c.quantitat), 0);
    const registres = collites.length;
    const qualitatMitjana = calcularQualitatMitjana();

    document.getElementById('estadistiquesHistoric').innerHTML = `
        <div class="stat-card">
            <div class="stat-label">Producció Total Registrada</div>
            <div class="stat-value">${(produccióTotal / 1000).toFixed(1)} <span style="font-size: 1rem; color: var(--text-gris);">t</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Nombre de Registres</div>
            <div class="stat-value">${registres}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Qualitat Mitjana</div>
            <div class="stat-value" style="color: var(--marró);">${qualitatMitjana}</div>
        </div>
    `;

    renderitzarGraficProduccio();
    renderitzarTaulaCollites();
}

function renderitzarGraficProduccio() {
    // Dades d'exemple per al gràfic (agrupades per mes)
    const produccioPerMes = {};
    
    collites.forEach(c => {
        const mes = new Date(c.data).toISOString().slice(0, 7); // "2024-07"
        if (!produccioPerMes[mes]) {
            produccioPerMes[mes] = 0;
        }
        produccioPerMes[mes] += parseFloat(c.quantitat);
    });

    const labels = Object.keys(produccioPerMes).sort();
    const dades = labels.map(mes => produccioPerMes[mes]);
    
    const maxVal = Math.max(...dades, 1); // Evitem divisió per zero

    const graficDiv = document.getElementById('graficProducció');
    if (dades.length === 0) {
        graficDiv.innerHTML = '<p>No hi ha dades de producció per mostrar al gràfic.</p>';
        return;
    }

    graficDiv.innerHTML = `
        <div class="chart-bars">
            ${dades.map(d => `
                <div class="chart-bar" style="height: ${(d / maxVal) * 100}%;">
                    <span class="chart-bar-label">${(d / 1000).toFixed(1)} t</span>
                </div>
            `).join('')}
        </div>
        <div class="chart-labels">
            ${labels.map(l => `<div class="chart-label">${l}</div>`).join('')}
        </div>
    `;
}

function renderitzarTaulaCollites() {
    const tbody = document.getElementById('taulaCollites');
    if (!tbody) return;

    if (collites.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No hi ha registres de collita.</td></tr>';
    } else {
        // Ordenem per data, més recent primer
        const collitesOrdenades = [...collites].sort((a, b) => new Date(b.data) - new Date(a.data));
        
        tbody.innerHTML = collitesOrdenades.map(c => {
            const cultiu = cultius.find(cu => cu.id === c.idCultiu);
            let nomCultiu = 'Cultiu eliminat';
            let nomParcel·la = '-';
            if (cultiu) {
                const especie = especiesFruita[cultiu.especie];
                const parcel·la = parcel·les.find(p => p.id === cultiu.idParcel·la);
                nomCultiu = especie ? especie.nom : 'Espècie Eliminada';
                nomParcel·la = parcel·la ? parcel·la.nom : 'Parcel·la Eliminada';
            }
            
            return `
                <tr>
                    <td>${formatarData(c.data)}</td>
                    <td>${nomParcel·la}</td>
                    <td>${nomCultiu}</td>
                    <td>${c.quantitat.toLocaleString('es-ES')} kg</td>
                    <td>${c.qualitat}</td>
                </tr>
            `;
        }).join('');
    }
}

function calcularQualitatMitjana() {
    if (collites.length === 0) return '-';
    const valors = { 'excel·lent': 4, 'bona': 3, 'mitjana': 2, 'baixa': 1 };
    const inversa = { 4: 'Excel·lent', 3: 'Bona', 2: 'Mitjana', 1: 'Baixa' };
    
    const suma = collites.reduce((acc, c) => acc + (valors[c.qualitat] || 0), 0);
    const mitjanaVal = Math.round(suma / collites.length);
    return inversa[mitjanaVal] || '-';
}

function obrirModalCollita() {
    const modal = document.getElementById('modalCollita');
    const formulari = document.getElementById('formulariCollita');
    formulari.reset();
    
    actualitzarSelectorCultiusCollita();
    
    // Comprovem si hi ha cultius
    if (cultius.filter(c => c.estat === 'actiu' || c.estat === 'collita').length === 0) {
        alert('No hi ha cultius actius o en collita. Si us plau, crea un cultiu abans de registrar una collita.');
        return;
    }

    // Posem la data d'avui per defecte
    document.getElementById('dataCollitaRegistre').value = new Date().toISOString().split('T')[0];
    
    modal.classList.add('active');
}

function tancarModalCollita() {
    document.getElementById('modalCollita').classList.remove('active');
}

function desarCollita() {
    const idCultiu = document.getElementById('idCultiuCollita').value;
    const data = document.getElementById('dataCollitaRegistre').value;
    const quantitat = parseFloat(document.getElementById('quantitatCollita').value);
    const qualitat = document.getElementById('qualitatCollita').value;
    const notes = document.getElementById('notesCollita').value;

    if (!idCultiu || !data || isNaN(quantitat) || quantitat <= 0) {
        alert('Si us plau, omple tots els camps obligatoris (*).');
        return;
    }

    const collita = {
        id: generarUUID(),
        idCultiu, data, quantitat, qualitat, notes
    };

    collites.push(collita);
    desarDades();
    tancarModalCollita();
    renderitzarHistoric();
    renderitzarTauler(); // Actualitzem estadístiques
}

function actualitzarSelectorCultiusCollita() {
    const select = document.getElementById('idCultiuCollita');
    if (!select) return;
    
    select.innerHTML = '<option value="">Seleccionar cultiu</option>';
    
    // Llistem cultius actius o en collita
    const cultiusDisponibles = cultius.filter(c => c.estat === 'actiu' || c.estat === 'collita');
    
    select.innerHTML += cultiusDisponibles.map(c => {
        const parcel·la = parcel·les.find(p => p.id === c.idParcel·la);
        const especie = especiesFruita[c.especie];
        
        // Comprovació per si s'han eliminat
        const nomEspecie = especie ? especie.nom : 'Espècie Eliminada';
        const nomParcel·la = parcel·la ? parcel·la.nom : 'Parcel·la Eliminada';

        return `<option value="${c.id}">${nomEspecie} (a ${nomParcel·la})</option>`;
    }).join('');
}

// ============================================
// MAPA GENERAL
// ============================================

function renderitzarMapaGeneral() {
    canviarCapaMapa(); // Dibuixa la capa seleccionada per defecte
}

function canviarCapaMapa() {
    const capa = document.getElementById('vistaCapaMapa').value;
    const llegendaDiv = document.getElementById('llegendaMapa');
    const capaMapa = document.getElementById('capaMapaGeneral');
    capaMapa.innerHTML = ''; // Netegem capa
    llegendaDiv.innerHTML = ''; // Netegem llegenda
    
    if (capa === 'parcel·les') {
        dibuixarCapaPoligons('capaMapaGeneral', parcel·les, colors.parcel·la.default, colors.parcel·la.hover, seleccionarParcel·laInfo);
        llegendaDiv.innerHTML = `
            <div class="legend-item">
                <div class="legend-color" style="background-color: ${colors.parcel·la.default};"></div>
                <span>Parcel·la Cadastral</span>
            </div>
        `;
    } else if (capa === 'sectors') {
        // Dibuixem parcel·les de fons
        dibuixarCapaPoligons('capaMapaGeneral', parcel·les, 'rgba(0,0,0,0.05)', 'rgba(0,0,0,0.05)', null);
        // Dibuixem sectors
        dibuixarCapaPoligons('capaMapaGeneral', sectors, colors.sector.default, colors.sector.hover, seleccionarSectorInfo, null, true);
        
        // Creem llegenda per espècies
        const especiesUsades = [...new Set(sectors.map(s => s.especie))];
        llegendaDiv.innerHTML = especiesUsades.map(eId => {
            const especie = especiesFruita[eId];
            // Si l'espècie s'ha eliminat, no la mostrem a la llegenda
            if (!especie) return '';
            
            const color = getColorForString(eId, 0.7);
            return `
                <div class="legend-item">
                    <div class="legend-color" style="background-color: ${color};"></div>
                    <span>${especie.nom}</span>
                </div>
            `;
        }).join('');

    } else if (capa === 'files') {
        // Dibuixem sectors de fons
        dibuixarCapaPoligons('capaMapaGeneral', sectors, 'rgba(0,0,0,0.05)', 'rgba(0,0,0,0.05)', null);
        // Dibuixem files
        dibuixarCapaLinies('capaMapaGeneral', filesArbres, colors.fila.default, colors.fila.hover, null);
        llegendaDiv.innerHTML = `
            <div class="legend-item">
                <div class="legend-color" style="background-color: ${colors.fila.default}; border: 1px solid ${colors.fila.hover};"></div>
                <span>Fila d'Arbres</span>
            </div>
        `;
    }
}

function seleccionarParcel·laInfo(id) {
    const p = parcel·les.find(p => p.id === id);
    if (!p) return;
    
    const cultiusParcel·la = cultius.filter(c => c.idParcel·la === p.id && c.estat !== 'completat');
    
    document.getElementById('detallsParcel·laSeleccionada').innerHTML = `
        <h3>${p.nom}</h3>
        <p><strong>Ref. Cadastral:</strong> ${p.refCadastral}</p>
        <p><strong>Superfície:</strong> ${p.area} ha</p>
        <p><strong>Reg:</strong> ${p.reg}</p>
        <p><strong>Sòl:</strong> ${p.tipusSòl}</p>
        <hr style="margin: 1rem 0;">
        <h4>Cultius Actius (${cultiusParcel·la.length})</h4>
        ${cultiusParcel·la.length > 0 ? 
            `<ul>${cultiusParcel·la.map(c => {
                const especie = especiesFruita[c.especie];
                const nomEspecie = especie ? especie.nom : 'Espècie Eliminada';
                return `<li>${nomEspecie} (${c.nombreArbres} arbres)</li>`
            }).join('')}</ul>` : 
            '<p>No hi ha cultius actius en aquesta parcel·la.</p>'
        }
    `;
    document.getElementById('infoParcel·laSeleccionada').style.display = 'block';
}

function seleccionarSectorInfo(id) {
    const s = sectors.find(s => s.id === id);
    if (!s) return;
    
    const especie = especiesFruita[s.especie];
    const nomEspecie = especie ? especie.nom : 'Espècie Eliminada';
    const totalArbres = (s.nombreFiles || 0) * (s.arbresPerFila || 0);

    document.getElementById('detallsParcel·laSeleccionada').innerHTML = `
        <h3>${s.nom}</h3>
        <p><strong>Espècie:</strong> ${nomEspecie}</p>
        <p><strong>Superfície:</strong> ${s.superficie} ha</p>
        <p><strong>Estat:</strong> ${s.estat}</p>
        <p><strong>Data Plantació:</strong> ${formatarData(s.dataPlantacio)}</p>
        <p><strong>Files:</strong> ${s.nombreFiles}</p>
        <p><strong>Arbres (Est.):</strong> ${totalArbres}</p>
    `;
    document.getElementById('infoParcel·laSeleccionada').style.display = 'block';
}

function netejarSeleccioParcel·la() {
    document.getElementById('infoParcel·laSeleccionada').style.display = 'none';
}


// ============================================
// FUNCIONS DE DIBUIX SVG
// ============================================

function inicialitzarMapaDibuix(idSvg, idCapaDibuix, funcioClic, funcioDobleClic) {
    const svg = document.getElementById(idSvg);
    const capaDibuix = document.getElementById(idCapaDibuix);
    if (!svg || !capaDibuix) return;

    svg.addEventListener('click', (e) => {
        if (!modeDibuix) return;
        if (e.target.tagName !== 'rect' && e.target.tagName !== 'svg') return; // Evitem clics sobre altres polígons

        const rect = svg.getBoundingClientRect();
        const x = (e.clientX - rect.left) / rect.width * 1000; // ViewBox 0-1000
        const y = (e.clientY - rect.top) / rect.height * 700; // ViewBox 0-700
        
        funcioClic(svg, capaDibuix, x, y);
    });

    svg.addEventListener('dblclick', (e) => {
        if (!modeDibuix) return;
        funcioDobleClic(svg, capaDibuix);
    });
    
    svg.addEventListener('mousemove', (e) => {
        if (!modeDibuix || vèrtexsActuals.length === 0) return;

        const rect = svg.getBoundingClientRect();
        const x = (e.clientX - rect.left) / rect.width * 1000;
        const y = (e.clientY - rect.top) / rect.height * 700;
        
        actualitzarPuntIntermedi(capaDibuix, x, y);
    });
}

// --- Dibuix Parcel·la / Sector (Polígons) ---

function iniciarDibuix(tipus, idBoto, idCapa) {
    if (modeDibuix) { // Si ja estàvem dibuixant, cancelem
        netejarDibuix(idCapa);
        document.getElementById(idBoto).classList.remove('active');
        if (modeDibuix === tipus) {
            modeDibuix = null;
            return;
        }
    }
    
    modeDibuix = tipus;
    vèrtexsActuals = [];
    document.getElementById(idBoto).classList.add('active');
    document.getElementById(idBoto).textContent = 'Clica al mapa (doble clic per finalitzar)';
}

function iniciarDibuixParcel·la() {
    iniciarDibuix('parcel·la', 'btnDibuixarParcel·la', 'capaDibuix');
}

function iniciarDibuixSector() {
    iniciarDibuix('sector', 'btnDibuixarSector', 'capaDibuixSector');
}

function gestionarClicMapaPoligon(svg, capa, x, y, color) {
    vèrtexsActuals.push([x, y]);

    // Dibuixem vèrtex
    const cercle = crearElementSVG('circle', {
        cx: x, cy: y, r: 5, fill: color, stroke: '#000', 'stroke-width': 1
    });
    capa.appendChild(cercle);

    if (vèrtexsActuals.length > 1) {
        if (!polygonActual) {
            polygonActual = crearElementSVG('polygon', {
                points: vèrtexsActuals.map(p => p.join(',')).join(' '),
                fill: color,
                opacity: 0.5,
                stroke: color,
                'stroke-width': 2
            });
            capa.appendChild(polygonActual);
        } else {
            polygonActual.setAttribute('points', vèrtexsActuals.map(p => p.join(',')).join(' '));
        }
    }
    
    // Creem el punt intermedi (línia discontínua)
    if (!puntIntermediActual) {
        puntIntermediActual = crearElementSVG('line', {
            'stroke-dasharray': '5, 5',
            stroke: color,
            'stroke-width': 2
        });
        capa.appendChild(puntIntermediActual);
    }
}

function finalitzarDibuixPoligon(svg, capa, idBoto, campCoordenades = null) {
    if (vèrtexsActuals.length < 3) {
        alert('Calen almenys 3 punts per a un polígon.');
        netejarDibuix(capa);
    } else {
        // Tanquem el polígon
        polygonActual.setAttribute('points', vèrtexsActuals.map(p => p.join(',')).join(' '));
        
        // Si hi ha un camp de coordenades, l'omplim
        if (campCoordenades) {
            document.getElementById(campCoordenades).value = vèrtexsActuals.map(c => c.join(',')).join('; ');
        }
        
        // Deixem el polígon final visible (eliminem cercles i línia intermèdia)
        capa.innerHTML = '';
        capa.appendChild(polygonActual);
        polygonActual = null;
    }
    
    modeDibuix = null;
    const btn = document.getElementById(idBoto);
    btn.classList.remove('active');
    btn.textContent = `✏️ Dibuixar ${idBoto.includes('Parcel·la') ? 'Parcel·la' : 'Sector'}`;
    // Els vèrtexs es queden a vèrtexsActuals fins que es desin
}

function gestionarClicMapaParcel·la(svg, capa, x, y) {
    gestionarClicMapaPoligon(svg, capa, x, y, colors.parcel·la.dibuix);
}

function finalitzarDibuixParcel·la(svg, capa) {
    finalitzarDibuixPoligon(svg, capa, 'btnDibuixarParcel·la', 'coordenadesParcel·la');
    
    // Si estem creant una nova parcel·la, intentem calcular l'àrea (molt aprox)
    if (!document.getElementById('idParcel·la').value && vèrtexsActuals.length > 0) {
        // Aquesta és una conversió inventada (píxels -> hectàrees)
        const areaPixels = calcularAreaPoligon(vèrtexsActuals);
        const areaHa = areaPixels / 10000; // Suposem 10000px^2 = 1ha
        document.getElementById('areaParcel·la').value = areaHa.toFixed(2);
    }
}

function gestionarClicMapaSector(svg, capa, x, y) {
    gestionarClicMapaPoligon(svg, capa, x, y, colors.sector.dibuix);
}

function finalitzarDibuixSector(svg, capa) {
    finalitzarDibuixPoligon(svg, capa, 'btnDibuixarSector');
}

// --- Dibuix Fila (Línia) ---

function iniciarDibuixFila() {
    if (modeDibuix) {
        netejarDibuix('capaDibuixFila');
        if (modeDibuix === 'fila') {
            modeDibuix = null;
            document.getElementById('btnDibuixarFila').classList.remove('active');
            document.getElementById('btnDibuixarFila').textContent = '✏️ Dibuixar Fila';
            return;
        }
    }
    
    modeDibuix = 'fila';
    vèrtexsActuals = []; // Per a la fila, només 2 vèrtexs
    document.getElementById('btnDibuixarFila').classList.add('active');
    document.getElementById('btnDibuixarFila').textContent = 'Clica punt d\'inici';
}

function gestionarClicMapaFila(svg, capa, x, y) {
    if (vèrtexsActuals.length === 0) {
        // Primer clic (inici)
        vèrtexsActuals.push([x, y]);
        
        // Dibuixem vèrtex inici
        const cercle = crearElementSVG('circle', {
            cx: x, cy: y, r: 5, fill: colors.fila.dibuix
        });
        capa.appendChild(cercle);

        // Creem la línia
        líniaActual = crearElementSVG('line', {
            x1: x, y1: y, x2: x, y2: y,
            stroke: colors.fila.dibuix,
            'stroke-width': 3
        });
        capa.appendChild(líniaActual);
        
        document.getElementById('btnDibuixarFila').textContent = 'Clica punt final';
        
    } else if (vèrtexsActuals.length === 1) {
        // Segon clic (final)
        vèrtexsActuals.push([x, y]);
        
        // Dibuixem vèrtex final
        const cercle = crearElementSVG('circle', {
            cx: x, cy: y, r: 5, fill: colors.fila.dibuix
        });
        capa.appendChild(cercle);
        
        // Actualitzem línia
        líniaActual.setAttribute('x2', x);
        líniaActual.setAttribute('y2', y);
        
        // Finalitzem
        finalitzarDibuixFila(svg, capa);
    }
}

function finalitzarDibuixFila(svg, capa) {
    if (vèrtexsActuals.length !== 2) {
        // No s'ha completat la línia
        return; 
    }
    
    const [p1, p2] = vèrtexsActuals;
    const longitud = Math.sqrt(Math.pow(p2[0] - p1[0], 2) + Math.pow(p2[1] - p1[1], 2));
    
    // Suposem una escala: 1px = 1m (a ajustar)
    const longitudMetres = longitud; 
    
    // Desem la fila
    desarFila(vèrtexsActuals, longitudMetres);
    
    // Resetejem
    netejarDibuix(capa);
    modeDibuix = null;
    vèrtexsActuals = [];
    líniaActual = null;
    document.getElementById('btnDibuixarFila').classList.remove('active');
    document.getElementById('btnDibuixarFila').textContent = '✏️ Dibuixar Fila';
}


// --- Funcions de dibuix genèriques ---

function netejarDibuix(idCapa) {
    document.getElementById(idCapa).innerHTML = '';
    vèrtexsActuals = [];
    polygonActual = null;
    líniaActual = null;
    puntIntermediActual = null;
}

function actualitzarPuntIntermedi(capa, x, y) {
    if (modeDibuix === 'fila' && vèrtexsActuals.length === 1) {
        // Dibuixant línia
        líniaActual.setAttribute('x2', x);
        líniaActual.setAttribute('y2', y);
    } else if (polygonActual) {
        // Dibuixant polígon
        const punts = [...vèrtexsActuals, [x, y]];
        polygonActual.setAttribute('points', punts.map(p => p.join(',')).join(' '));
    }
}

function crearElementSVG(tag, attrs) {
    const el = document.createElementNS(SVG_NS, tag);
    for (let k in attrs) {
        el.setAttribute(k, attrs[k]);
    }
    return el;
}

function dibuixarCapaPoligons(idCapa, dades, colorDef, colorHover, funcioClic, idSeleccionat = null, colorPerVarietat = false) {
    const capa = document.getElementById(idCapa);
    if (!capa) return;
    capa.innerHTML = ''; // Netegem

    dades.forEach(item => {
        if (!item.coordenades || item.coordenades.length < 3) return;

        let color = colorDef;
        if (colorPerVarietat) {
            // Canviat a item.especie
            color = getColorForString(item.especie, 0.7);
        } else if (item.id === idSeleccionat) {
            color = colors.parcel·la.selected; // Un color de selecció genèric
        }

        const poligon = crearElementSVG('polygon', {
            points: item.coordenades.map(p => p.join(',')).join(' '),
            fill: color,
            stroke: color.replace('0.5', '1').replace('0.7', '1'),
            'stroke-width': (item.id === idSeleccionat) ? 3 : 1.5,
            class: 'plot-polygon',
            'data-id': item.id
        });

        if (funcioClic) {
            poligon.addEventListener('click', (e) => {
                e.stopPropagation(); // Evitem que el clic arribi al fons del mapa
                funcioClic(item.id);
            });
            poligon.addEventListener('mouseover', () => {
                if (item.id !== idSeleccionat) poligon.style.fill = colorHover;
            });
            poligon.addEventListener('mouseout', () => {
                if (item.id !== idSeleccionat) poligon.style.fill = color;
            });
        }
        capa.appendChild(poligon);
    });
}

function dibuixarCapaLinies(idCapa, dades, colorDef, colorHover, funcioClic, idSeleccionat = null) {
    const capa = document.getElementById(idCapa);
    if (!capa) return;
    capa.innerHTML = ''; // Netegem

    dades.forEach(item => {
        if (!item.coordenades || item.coordenades.length < 2) return;

        const [p1, p2] = item.coordenades;
        const color = (item.id === idSeleccionat) ? colors.fila.selected : colorDef;

        const linia = crearElementSVG('line', {
            x1: p1[0], y1: p1[1],
            x2: p2[0], y2: p2[1],
            stroke: color,
            'stroke-width': 3,
            class: 'plot-polygon', // Reutilitzem classe per al cursor
            'data-id': item.id
        });

        if (funcioClic) {
            linia.addEventListener('click', (e) => {
                e.stopPropagation();
                funcioClic(item.id);
            });
            linia.addEventListener('mouseover', () => {
                if (item.id !== idSeleccionat) linia.style.stroke = colorHover;
            });
            linia.addEventListener('mouseout', () => {
                if (item.id !== idSeleccionat) linia.style.stroke = color;
            });
        }
        capa.appendChild(linia);
    });
}

// ============================================
// FUNCIONS UTILITÀRIES
// ============================================

function formatarData(dataISO) {
    if (!dataISO) return '-';
    try {
        const data = new Date(dataISO);
        // Assegurem que agafem la data en UTC per evitar problemes de zona horària
        return new Date(data.getTime() + data.getTimezoneOffset() * 60000).toLocaleDateString('ca-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    } catch (e) {
        return dataISO; // Retornem l'string original si falla
    }
}

function generarUUID() {
    return 'id-' + Math.random().toString(36).substr(2, 9) + '-' + Date.now().toString(36);
}

// Funció per calcular l'àrea d'un polígon (Shoelace formula)
function calcularAreaPoligon(vèrtexs) {
    let area = 0;
    let j = vèrtexs.length - 1;
    for (let i = 0; i < vèrtexs.length; i++) {
        area += (vèrtexs[j][0] + vèrtexs[i][0]) * (vèrtexs[j][1] - vèrtexs[i][1]);
        j = i;
    }
    return Math.abs(area / 2);
}

// Funció per generar un color consistent basat en un string (ex: "poma-golden")
function getColorForString(str, opacity = 0.5) {
    if (!str) return `rgba(128, 128, 128, ${opacity})`; // Color gris si no hi ha string

    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        hash = str.charCodeAt(i) + ((hash << 5) - hash);
    }
    let color = (hash & 0x00FFFFFF).toString(16).toUpperCase();
    let rgb = parseInt(color, 16);
    let r = (rgb >> 16) & 0xFF;
    let g = (rgb >> 8) & 0xFF;
    let b = rgb & 0xFF;
    
    // Ajustem la lluminositat per evitar colors massa foscos o clars
    r = Math.floor((r + 200) / 2);
    g = Math.floor((g + 200) / 2);
    b = Math.floor((b + 200) / 2);
    
    return `rgba(${r}, ${g}, ${b}, ${opacity})`;
}

// ============================================
// INICIAR L'APLICACIÓ
// ============================================

window.addEventListener('DOMContentLoaded', inicialitzar);