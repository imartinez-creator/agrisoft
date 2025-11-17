// ============================================
// VARIABLES GLOBALS - Emmagatzemen totes les dades
// ============================================

let parcel·les = [];  // Array amb totes les parcel·les
let cultius = [];     // Array amb tots els cultius
let collites = [];    // Array amb totes les collites
let sectors = [];     // Array amb tots els sectors
let filesArbres = []; // Array amb totes les files d'arbres

// Informació de les varietats de fruita
let varietatsFruita = {
    'poma-golden': { nom: 'Poma Golden Delicious', tipus: 'Poma', diesMaduració: 150, rendimentPerArbre: 45 },
    'poma-fuji': { nom: 'Poma Fuji', tipus: 'Poma', diesMaduració: 165, rendimentPerArbre: 50 },
    'pera-conference': { nom: 'Pera Conference', tipus: 'Pera', diesMaduració: 140, rendimentPerArbre: 40 },
    'pera-blanquilla': { nom: 'Pera Blanquilla', tipus: 'Pera', diesMaduració: 130, rendimentPerArbre: 38 },
    'préssec-groc': { nom: 'Préssec Groc', tipus: 'Préssec', diesMaduració: 120, rendimentPerArbre: 35 },
    'cirera-picota': { nom: 'Cirera Picota', tipus: 'Cirera', diesMaduració: 90, rendimentPerArbre: 25 },
    'pruna-claudia': { nom: 'Pruna Claudia', tipus: 'Pruna', diesMaduració: 110, rendimentPerArbre: 30 }
};

// Variables per al dibuix al mapa
let modeDibuix = null; // 'parcel·la', 'sector', 'fila'
let vèrtexsActuals = [];
let polygonActual = null;
let puntIntermediActual = null;
let llistaCoordsActual = null;
let idParcel·laSeleccionada = null;
let idSectorSeleccionat = null;
let idFilaSeleccionada = null;
let rectFonsMapa = null; // Rect de fons per a totes les capes del mapa

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

// ============================================
// INICIALITZACIÓ - S'executa quan es carrega la pàgina
// ============================================

function inicialitzar() {
    carregarDades();      // Carreguem les dades del localStorage
    renderitzarTauler();  // Mostrem el tauler principal
    renderitzarParcel·les();
    renderitzarSectors();
    renderitzarCultius();
    renderitzarHistoric();
    renderitzarMapaGeneral();
    inicialitzarMapaDibuix();

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
            } else if (idSeccio === 'historic') renderitzarHistoric();
            else if (idSeccio === 'mapa') renderitzarMapaGeneral();
        });
    });
}

// ============================================
// GESTIÓ DE DADES - Carregar i desar al localStorage
// ============================================

function carregarDades() {
    // Intentem carregar les dades del localStorage
    const parcel·lesDesades = localStorage.getItem('agrisoft_parcel·les');
    const cultiusDesats = localStorage.getItem('agrisoft_cultius');
    const collitesDesades = localStorage.getItem('agrisoft_collites');
    const sectorsDesats = localStorage.getItem('agrisoft_sectors');
    const filesArbresDesades = localStorage.getItem('agrisoft_filesArbres');

    // Si hi ha dades desades, les carreguem
    if (parcel·lesDesades) parcel·les = JSON.parse(parcel·lesDesades);
    if (cultiusDesats) cultius = JSON.parse(cultiusDesats);
    if (collitesDesades) collites = JSON.parse(collitesDesades);
    if (sectorsDesats) sectors = JSON.parse(sectorsDesats);
    if (filesArbresDesades) filesArbres = JSON.parse(filesArbresDesades);

    // Si no hi ha dades, creem dades d'exemple
    if (parcel·les.length === 0) {
        crearDadesExemple();
    }
}

function desarDades() {
    // Desem totes les dades al localStorage
    localStorage.setItem('agrisoft_parcel·les', JSON.stringify(parcel·les));
    localStorage.setItem('agrisoft_cultius', JSON.stringify(cultius));
    localStorage.setItem('agrisoft_collites', JSON.stringify(collites));
    localStorage.setItem('agrisoft_sectors', JSON.stringify(sectors));
    localStorage.setItem('agrisoft_filesArbres', JSON.stringify(filesArbres));
}

function crearDadesExemple() {
    // Creem 3 parcel·les d'exemple
    parcel·les = [
        {
            id: '1',
            nom: 'Parcel·la Nord',
            refCadastral: 'CAT-001-2024',
            area: 2.5,
            ubicació: 'Zona Nord',
            tipusSòl: 'franc',
            reg: 'goteig',
            notes: '',
            coordenades: [[100, 100], [300, 100], [300, 250], [100, 250]]
        },
        {
            id: '2',
            nom: 'Parcel·la Sud',
            refCadastral: 'CAT-002-2024',
            area: 3.2,
            ubicació: 'Zona Sud',
            tipusSòl: 'argilós',
            reg: 'aspersió',
            notes: '',
            coordenades: [[350, 150], [550, 150], [550, 350], [350, 350]]
        },
        {
            id: '3',
            nom: 'Parcel·la Est',
            refCadastral: 'CAT-003-2024',
            area: 1.8,
            ubicació: 'Zona Est',
            tipusSòl: 'llimós',
            reg: 'goteig',
            notes: '',
            coordenades: [[600, 100], [750, 100], [750, 280], [600, 280]]
        }
    ];

    // Creem 2 cultius d'exemple
    cultius = [
        {
            id: '1',
            idParcel·la: '1',
            varietat: 'poma-golden',
            dataPlantació: '2024-03-15',
            dataCollita: '2024-08-12',
            nombreArbres: 1000,
            densitat: 400,
            estat: 'actiu',
            notes: ''
        },
        {
            id: '2',
            idParcel·la: '2',
            varietat: 'pera-conference',
            dataPlantació: '2024-02-20',
            dataCollita: '2024-07-10',
            nombreArbres: 1280,
            densitat: 400,
            estat: 'collita',
            notes: ''
        }
    ];

    // Creem 1 collita d'exemple
    collites = [
        {
            id: '1',
            idCultiu: '2',
            data: '2024-07-15',
            quantitat: 15000,
            qualitat: 'excel·lent',
            notes: 'Primera collita de la temporada'
        }
    ];

    // Creem 1 sector d'exemple
    sectors = [
        {
            id: '1',
            nom: 'Sector A - Pomeres Golden',
            varietat: 'poma-golden',
            superficie: 1.0,
            dataPlantacio: '2023-03-10',
            nombreFiles: 50,
            arbresPerFila: 20,
            estat: 'actiu',
            notes: '',
            coordenades: [[110, 110], [290, 110], [290, 180], [110, 180]]
        }
    ];
    
    // Creem 2 files d'arbres d'exemple
    filesArbres = [
        {
            id: '1',
            idSector: '1',
            numero: 1,
            arbres: 20,
            longitud: 180, // metres
            coordenades: [[110, 115], [290, 115]]
        },
        {
            id: '2',
            idSector: '1',
            numero: 2,
            arbres: 20,
            longitud: 180,
            coordenades: [[110, 135], [290, 135]]
        }
    ];

    desarDades();
}

// ============================================
// GESTIÓ DE DADES JSON - Descarregar i carregar
// ============================================

// Removed functions: descarregarDades, carregarArxiu, processarArxiu

function exportarGeoJSON() {
    // This function would be implemented here to export selected data as GeoJSON
    // For now, it's a placeholder.
    alert('Funcionalitat d\'exportació GeoJSON no implementada encara.');
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
    // event.target refers to the button clicked
    document.querySelector(`.nav-tab[onclick="mostrarSeccio('${idSeccio}')"]`).classList.add('active');


    // Actualitzem el contingut de la secció
    if (idSeccio === 'tauler') renderitzarTauler();
    else if (idSeccio === 'parcel·les') renderitzarParcel·les();
    else if (idSeccio === 'sectors') renderitzarSectors();
    else if (idSeccio === 'cultius') {
        actualitzarSelectorParcel·lesCultiu();
        renderitzarCultius();
    } else if (idSeccio === 'historic') renderitzarHistoric();
    else if (idSeccio === 'mapa') renderitzarMapaGeneral();
}

// ============================================
// TAULER PRINCIPAL
// ============================================

function renderitzarTauler() {
    // Calculem les estadístiques
    const areaTotal = parcel·les.reduce((suma, p) => suma + parseFloat(p.area), 0);
    const cultiusActius = cultius.filter(c => c.estat === 'actiu' || c.estat === 'collita').length;
    const produccióTotal = collites.reduce((suma, c) => suma + c.quantitat, 0);
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
            <div class="stat-label">Cultius Actius</div>
            <div class="stat-value">${cultiusActius}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Producció Total (registrada)</div>
            <div class="stat-value">${(produccióTotal / 1000).toFixed(1)} <span style="font-size: 1rem; color: var(--text-gris);">t</span></div>
        </div>
    `;

    // Mostrem les properes collites
    const avui = new Date();
    const properes = cultius.filter(c => {
        const dataCollita = new Date(c.dataCollita);
        const diesFins = Math.ceil((dataCollita - avui) / (1000 * 60 * 60 * 24));
        return diesFins >= 0 && diesFins <= 30 && c.estat !== 'completat';
    }).sort((a, b) => new Date(a.dataCollita) - new Date(b.dataCollita));

    if (properes.length > 0) {
        document.getElementById('properesCollites').innerHTML = properes.map(c => {
            const parcel·la = parcel·les.find(p => p.id === c.idParcel·la);
            const varietat = varietatsFruita[c.varietat];
            const dataCollita = new Date(c.dataCollita);
            const diesFins = Math.ceil((dataCollita - avui) / (1000 * 60 * 60 * 24));
            
            return `
                <div class="alert alert-warning">
                    <div>⚠️</div>
                    <div>
                        <strong>${varietat.nom}</strong> a ${parcel·la.nom} - 
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
    document.getElementById('resumParcel·les').innerHTML = parcel·les.slice(0, 6).map(p => {
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

// ============================================
// GESTIÓ DE PARCEL·LES
// ============================================

function renderitzarParcel·les() {
    const cerca = document.getElementById('cercaParcel·les')?.value.toLowerCase() || '';
    const filtrades = parcel·les.filter(p => 
        p.nom.toLowerCase().includes(cerca) ||
        p.refCadastral.toLowerCase().includes(cerca) ||
        p.ubicació.toLowerCase().includes(cerca)
    );

    if (filtrades.length === 0) {
        document.getElementById('graellaParcel·les').innerHTML = `
            <div class="empty-state">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🗺️</div>
                <div style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--text);">No hi ha parcel·les</div>
                <p>Comença afegint la teva primera parcel·la</p>
            </div>
        `;
        return;
    }

    document.getElementById('graellaParcel·les').innerHTML = filtrades.map(p => {
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
                <div style="margin-top: 1rem;">
                    <button class="btn btn-outline btn-sm" onclick="editarParcel·la('${p.id}')">Editar</button>
                    <button class="btn btn-outline btn-sm" onclick="eliminarParcel·la('${p.id}')">Eliminar</button>
                </div>
            </div>
        `;
    }).join('');
}

function filtrarParcel·les() {
    renderitzarParcel·les();
}

function obrirModalParcel·la(idParcel·la = null) {
    const modal = document.getElementById('modalParcel·la');
    const formulari = document.getElementById('formulariParcel·la');
    formulari.reset();
    document.getElementById('coordenadesParcel·la').value = ''; // Netegem les coordenades

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
    netejarDibuix('capaDibuix'); // Netejem qualsevol dibuix pendent
    netejarSeleccio('parcel·la');
}

function editarParcel·la(id) {
    obrirModalParcel·la(id);
}

function eliminarParcel·la(id) {
    if (confirm('Estàs segur que vols eliminar aquesta parcel·la? Això també eliminarà els sectors i files associades.')) {
        // Eliminar sectors associats
        sectors = sectors.filter(s => s.coordenades.length === 0 || !s.coordenades.some(coord => pointInPolygon(coord, parcel·les.find(p => p.id === id).coordenades)));
        // Eliminar files d'arbres associades
        filesArbres = filesArbres.filter(f => !sectors.some(s => s.id === f.idSector));
        
        parcel·les = parcel·les.filter(p => p.id !== id);
        desarDades();
        renderitzarParcel·les();
        renderitzarSectors(); // Potser cal actualitzar la visualització dels sectors
        renderitzarMapaGeneral();
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

    // Validació bàsica
    if (!nom || !refCadastral || isNaN(area) || area <= 0) {
        alert('Si us plau, omple tots els camps obligatoris amb valors vàlids.');
        return;
    }

    let coordenades = [];
    if (coordenadesText) {
        coordenades = coordenadesText.split(';').map(coord => coord.trim().split(',').map(parseFloat));
    } else if (vèrtexsActuals.length >= 3) {
        coordenades = vèrtexsActuals;
    }

    const parcel·la = {
        id: id || Date.now().toString(),
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
        // Actualitzar parcel·la existent
        const index = parcel·les.findIndex(p => p.id === id);
        parcel·les[index] = parcel·la;
    } else {
        // Afegir nova parcel·la
        parcel·les.push(parcel·la);
    }

    desarDades();
    tancarModalParcel·la();
    renderitzarParcel·les();
    renderitzarMapaGeneral();
    netejarDibuix('capaDibuix');
    modeDibuix = null;
}

// ============================================
// GESTIÓ DE SECTORS
// ============================================

function renderitzarSectors() {
    // Implementació per renderitzar sectors en la graella i mapa
    // ... (codi truncat en l'original, però pots afegir aquí)
}

// ============================================
// GESTIÓ DE CULTIUS
// ============================================

// ... (resta del codi JS, incloent funcions per cultius, historic, mapa, dibuix, etc.)

// Nota: El codi JS complet és molt llarg (truncat en l'original), però l'he extret tal qual. Si necessites la part completa, confirma.

// ============================================
// INICIAR L'APLICACIÓ
// ============================================

window.addEventListener('DOMContentLoaded', inicialitzar);