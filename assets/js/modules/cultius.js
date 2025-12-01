// =======================================================
// MÒDUL CULTIUS (Plantacions i Varietats)
// =======================================================

let idEditantCultiu = null;
let idEditantEspecie = null;

// --- CULTIUS ---
function renderitzarCultius() {
    const div = document.getElementById('graellaCultius');
    if(cultius.length === 0) {
        div.innerHTML = '<div class="empty-state">No hi ha cultius assignats.</div>';
        return;
    }
    div.innerHTML = cultius.map(c => {
        const parc = parcel·les.find(p => p.id === c.parcela);
        const parcNom = parc ? parc.nom : 'Desconeguda';
        const esp = especiesFruita[c.especie]?.nom || 'Desconeguda';
        return `
            <div class="card">
                <div style="display:flex; justify-content:space-between">
                    <div class="card-title">${esp}</div>
                    <div>
                        <button class="btn btn-outline btn-sm" onclick="obrirModalCultiu('${c.id}')">✏️</button>
                        <button class="btn btn-outline btn-sm" style="color:red; border-color:red" onclick="eliminarCultiu('${c.id}')">🗑️</button>
                    </div>
                </div>
                <div>📍 ${parcNom}</div>
                <div class="badge badge-info">${c.estat}</div>
            </div>
        `;
    }).join('');
}

function obrirModalCultiu(id = null) {
    const selP = document.getElementById('parcCultiu');
    const selE = document.getElementById('espCultiu');
    if(parcel·les.length === 0) return alert("Crea parcel·les primer!");
    if(Object.keys(especiesFruita).length === 0) return alert("Crea espècies primer!");
    
    selP.innerHTML = parcel·les.map(p => `<option value="${p.id}">${p.nom}</option>`).join('');
    selE.innerHTML = Object.keys(especiesFruita).map(k => `<option value="${k}">${especiesFruita[k].nom}</option>`).join('');
    
    idEditantCultiu = id;
    if(id) {
        const c = cultius.find(x => x.id === id);
        if(c) {
            selP.value = c.parcela;
            selE.value = c.especie;
            document.getElementById('estatCultiu').value = c.estat;
        }
    }
    document.getElementById('modalCultiu').classList.add('active');
}

function desarCultiu() {
    const obj = {
        id: idEditantCultiu || generarUUID(),
        parcela: document.getElementById('parcCultiu').value,
        especie: document.getElementById('espCultiu').value,
        estat: document.getElementById('estatCultiu').value
    };

    if(idEditantCultiu) cultius[cultius.findIndex(c => c.id === idEditantCultiu)] = obj;
    else cultius.push(obj);

    tancarModals(); renderitzarCultius();
}

function eliminarCultiu(id) {
    if(confirm("Eliminar cultiu?")) {
        cultius = cultius.filter(c => c.id !== id);
        renderitzarCultius();
    }
}

// --- ESPÈCIES ---
function renderitzarEspecies() {
    const div = document.getElementById('graellaEspecies');
    const keys = Object.keys(especiesFruita);
    if(keys.length === 0) {
        div.innerHTML = '<div class="empty-state">No hi ha espècies al catàleg.</div>';
        return;
    }
    div.innerHTML = keys.map(k => `
        <div class="card">
            <div style="display:flex; justify-content:space-between">
                <div class="card-title">${especiesFruita[k].nom}</div>
                <button class="btn btn-outline btn-sm" style="color:red; border-color:red" onclick="eliminarEspecie('${k}')">🗑️</button>
            </div>
            <div>${especiesFruita[k].tipus}</div>
        </div>`).join('');
}

function obrirModalEspecie() { document.getElementById('modalEspecie').classList.add('active'); }

function desarEspecie() {
    const id = document.getElementById('idEspecie').value;
    const nom = document.getElementById('nomEspecie').value;
    if(!id || !nom) return alert("Dades incompletes");
    
    if(especiesFruita[id] && !confirm("Aquest ID ja existeix. Vols sobreescriure'l?")) return;
    
    especiesFruita[id] = { nom: nom, tipus: document.getElementById('tipusEspecie').value };
    tancarModals(); renderitzarEspecies();
}

function eliminarEspecie(id) {
    if(confirm("Segur? Això podria afectar sectors o cultius.")) {
        delete especiesFruita[id];
        renderitzarEspecies();
    }
}