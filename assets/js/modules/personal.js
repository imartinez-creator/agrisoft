// =======================================================
// MÒDUL PERSONAL (Treballadors, Tasques, Horaris)
// =======================================================

let idEditantTreballador = null;

// 1. RENDERITZAR TARGETES DE TREBALLADORS
function renderitzarTreballadors() {
    const div = document.getElementById('graellaTreballadors');
    
    if(treballadors.length === 0) {
        div.innerHTML = '<div class="empty-state">📭 No hi ha treballadors a la plantilla.</div>';
        return;
    }

    div.innerHTML = treballadors.map(t => `
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:start;">
                <div>
                    <div class="card-title">👷 ${t.nom}</div>
                    <div class="item-subtitle">${t.categoria_professional || 'Sense categoria'}</div>
                    <div style="font-size:0.85rem; margin-top:0.5rem; color:#666;">
                        🆔 DNI: ${t.document_identitat}<br>
                        📞 ${t.telefon || '-'}
                    </div>
                </div>
                <div style="display:flex; gap:0.5rem;">
                    <button class="btn btn-outline btn-sm" title="Editar" onclick="obrirModalTreballador('${t.idTreballador}')">✏️</button>
                    <button class="btn btn-outline btn-sm" style="border-color:red; color:red;" title="Eliminar" onclick="eliminarTreballador('${t.idTreballador}')">🗑️</button>
                </div>
            </div>
            <div style="margin-top:1rem; padding-top:0.5rem; border-top:1px solid #eee; display:flex; justify-content:space-between; font-size:0.8rem;">
                <span>📅 Inici: ${t.data_inici || '-'}</span>
                <span class="badge badge-info">${t.tipus_contracte || 'Contracte'}</span>
            </div>
        </div>
    `).join('');
}

// 2. CONTROL DE PESTANYES DEL MODAL
function canviarPestanya(tabId) {
    // Amagar tot el contingut
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    // Desactivar tots els botons
    document.querySelectorAll('.modal-tab-btn').forEach(btn => btn.classList.remove('active'));
    
    // Mostrar contingut seleccionat
    document.getElementById(tabId).classList.add('active');
    
    // Activar botó corresponent
    const buttons = document.querySelectorAll('.modal-tab-btn');
    buttons.forEach(btn => {
        if(btn.getAttribute('onclick').includes(tabId)) {
            btn.classList.add('active');
        }
    });
}

// 3. OBRIR MODAL (CREAR O EDITAR)
function obrirModalTreballador(id = null) {
    document.getElementById('formTreballador').reset();
    canviarPestanya('tab-identitat'); // Sempre comença a la primera pestanya
    idEditantTreballador = id;

    if(id) {
        // MODE EDICIÓ: Busquem el treballador i omplim TOTS els camps
        const t = treballadors.find(x => x.idTreballador === id);
        if(t) {
            // Pestanya Identitat
            document.getElementById('nom').value = t.nom;
            document.getElementById('document_identitat').value = t.document_identitat;
            document.getElementById('data_naixement').value = t.data_naixement;
            document.getElementById('lloc_naixement').value = t.lloc_naixement;
            document.getElementById('nacionalitat').value = t.nacionalitat;
            document.getElementById('fotografia').value = t.fotografia;
            
            // Pestanya Contacte
            document.getElementById('telefon').value = t.telefon;
            document.getElementById('email').value = t.email;
            document.getElementById('adreca').value = t.adreca;
            document.getElementById('residencia').value = t.residencia;
            document.getElementById('contacte_emergencia').value = t.contacte_emergencia;
            
            // Pestanya Laboral
            document.getElementById('categoria_professional').value = t.categoria_professional;
            document.getElementById('tipus_contracte').value = t.tipus_contracte;
            document.getElementById('data_inici').value = t.data_inici;
            document.getElementById('data_fi').value = t.data_fi;
            document.getElementById('num_seguretat_social').value = t.num_seguretat_social;
            document.getElementById('compte_bancari').value = t.compte_bancari;
            document.getElementById('permis_treball').value = t.permis_treball;
            
            // Pestanya CV
            document.getElementById('historial_laboral').value = t.historial_laboral;
            document.getElementById('formacio').value = t.formacio;
            document.getElementById('habilitats').value = t.habilitats;
            document.getElementById('idiomes').value = t.idiomes;
        }
    }
    document.getElementById('modalTreballador').classList.add('active');
}

// 4. GUARDAR DADES
function desarTreballador() {
    const nom = document.getElementById('nom').value;
    const doc = document.getElementById('document_identitat').value;

    if(!nom || !doc) return alert("Error: El Nom i el DNI són obligatoris.");

    // Creem l'objecte complet segons l'esquema SQL
    const obj = {
        idTreballador: idEditantTreballador || generarUUID(),
        nom: nom,
        document_identitat: doc,
        data_naixement: document.getElementById('data_naixement').value,
        lloc_naixement: document.getElementById('lloc_naixement').value,
        nacionalitat: document.getElementById('nacionalitat').value,
        fotografia: document.getElementById('fotografia').value,
        
        telefon: document.getElementById('telefon').value,
        email: document.getElementById('email').value,
        adreca: document.getElementById('adreca').value,
        residencia: document.getElementById('residencia').value,
        contacte_emergencia: document.getElementById('contacte_emergencia').value,
        
        categoria_professional: document.getElementById('categoria_professional').value,
        tipus_contracte: document.getElementById('tipus_contracte').value,
        data_inici: document.getElementById('data_inici').value,
        data_fi: document.getElementById('data_fi').value,
        num_seguretat_social: document.getElementById('num_seguretat_social').value,
        compte_bancari: document.getElementById('compte_bancari').value,
        permis_treball: document.getElementById('permis_treball').value,
        
        historial_laboral: document.getElementById('historial_laboral').value,
        formacio: document.getElementById('formacio').value,
        habilitats: document.getElementById('habilitats').value,
        idiomes: document.getElementById('idiomes').value
    };

    if(idEditantTreballador) {
        // Actualitzar existent
        const idx = treballadors.findIndex(t => t.idTreballador === idEditantTreballador);
        treballadors[idx] = obj;
    } else {
        // Crear nou
        treballadors.push(obj);
    }

    tancarModals();
    renderitzarTreballadors();
    
    // Si hi ha fitxatges actius, refrescar la vista per si hem canviat el nom
    if(typeof renderitzarControlHorari === 'function') renderitzarControlHorari();
}

// 5. ELIMINAR TREBALLADOR
function eliminarTreballador(id) {
    if(confirm("⚠️ Estàs segur que vols eliminar aquest treballador? Aquesta acció no es pot desfer.")) {
        treballadors = treballadors.filter(t => t.idTreballador !== id);
        renderitzarTreballadors();
        if(typeof renderitzarControlHorari === 'function') renderitzarControlHorari();
    }
}

// --- LOGICA DE CONTROL HORARI (FITXATGES) ---
function renderitzarControlHorari() {
    const div = document.getElementById('graellaFitxatges');
    
    if(treballadors.length === 0) {
        div.innerHTML = '<div class="empty-state">No hi ha treballadors per fitxar.</div>';
        return;
    }

    div.innerHTML = treballadors.map(t => {
        const idT = t.idTreballador; 
        // Busquem l'últim fitxatge d'aquest treballador
        const registresTreb = registresHoraris.filter(r => r.treballadorId === idT);
        const ultim = registresTreb.sort((a,b) => new Date(b.entrada) - new Date(a.entrada))[0];
        
        // Si té una entrada sense sortida, està treballant
        const actiu = ultim && !ultim.sortida;
        
        return `
            <div class="card" style="display:flex; justify-content:space-between; align-items:center; background-color:${actiu ? '#ecfdf5' : '#fff'}; border-color:${actiu ? '#10b981' : '#e5e7eb'}">
                <div>
                    <div class="card-title" style="margin:0">👷 ${t.nom}</div>
                    <div class="item-subtitle">
                        ${actiu ? '🟢 Treballant des de ' + new Date(ultim.entrada).toLocaleTimeString() : '🔴 Fora de servei'}
                    </div>
                </div>
                <button class="btn ${actiu ? 'btn-outline' : 'btn-primary'}" onclick="toggleFitxatge('${idT}', ${actiu})">
                    ${actiu ? 'Sortir' : 'Entrar'}
                </button>
            </div>
        `;
    }).join('');
}

function toggleFitxatge(id, actiu) {
    const ara = new Date().toISOString(); // Guardem format ISO complet
    
    if(actiu) {
        // Tancar fitxatge: busquem el registre obert
        const r = registresHoraris.find(x => x.treballadorId === id && !x.sortida);
        if(r) r.sortida = ara;
    } else {
        // Nou fitxatge
        registresHoraris.push({ 
            id: generarUUID(), 
            treballadorId: id, 
            entrada: ara, 
            sortida: null 
        });
    }
    renderitzarControlHorari();
}

// --- LOGICA DE TASQUES (NOU) ---
function renderitzarTasques() {
    const div = document.getElementById('graellaTasques');
    if(tasques.length === 0) {
        div.innerHTML = '<div class="empty-state">📭 No hi ha tasques planificades.</div>';
        return;
    }
    
    div.innerHTML = tasques.map(t => {
        const tr = treballadors.find(x => x.idTreballador === t.trebId)?.nom || 'Desconegut';
        const sec = sectors.find(x => x.id === t.sectorId)?.nom || 'General';
        
        return `
            <div class="card">
                <div style="display:flex; justify-content:space-between">
                    <div class="card-title">${t.tipus}</div>
                    <button class="btn btn-outline btn-sm" style="color:red; border-color:red;" onclick="eliminarTasca('${t.id}')">🗑️</button>
                </div>
                <div>👷 ${tr}</div>
                <div>📍 ${sec}</div>
                <div style="margin-top:0.5rem; font-size:0.9rem; color:gray;">📅 ${t.data}</div>
            </div>`;
    }).join('');
}

function obrirModalTasca() {
    const selS = document.getElementById('sectorTasca');
    const selT = document.getElementById('trebTasca');
    
    if(sectors.length === 0) return alert("No hi ha sectors creats.");
    if(treballadors.length === 0) return alert("No hi ha treballadors.");
    
    selS.innerHTML = sectors.map(s => `<option value="${s.id}">${s.nom}</option>`).join('');
    selT.innerHTML = treballadors.map(t => `<option value="${t.idTreballador}">${t.nom}</option>`).join('');
    
    document.getElementById('dataTasca').value = new Date().toISOString().split('T')[0];
    document.getElementById('modalTasca').classList.add('active');
}

function desarTasca() {
    tasques.push({
        id: generarUUID(),
        tipus: document.getElementById('nomTasca').value,
        sectorId: document.getElementById('sectorTasca').value,
        trebId: document.getElementById('trebTasca').value,
        data: document.getElementById('dataTasca').value
    });
    tancarModals();
    renderitzarTasques();
}

function eliminarTasca(id) {
    if(confirm("Eliminar tasca?")) {
        tasques = tasques.filter(t => t.id !== id);
        renderitzarTasques();
    }
}