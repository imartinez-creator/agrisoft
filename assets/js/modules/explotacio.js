// =======================================================
// MÒDUL EXPLOTACIÓ (Parcel·les, Sectors, Màquines, Sensors)
// =======================================================

let idEditantParcela = null;
let idEditantSector = null;
let idEditantMaquina = null;
let idEditantSensor = null;

// --- PARCEL·LES ---
function renderitzarParcel·les() {
    const div = document.getElementById('graellaParcel·les');
    if(parcel·les.length === 0) div.innerHTML = '<div class="empty-state">No hi ha parcel·les.</div>';
    else {
        div.innerHTML = parcel·les.map(p => `
            <div class="card">
                <div style="display:flex; justify-content:space-between">
                    <div class="card-title">${p.nom}</div>
                    <div>
                        <button class="btn btn-outline btn-sm" onclick="obrirModalParcel·la('${p.id}')">✏️</button>
                        <button class="btn btn-outline btn-sm" style="color:red; border-color:red" onclick="eliminarParcel·la('${p.id}')">🗑️</button>
                    </div>
                </div>
                <div>Ref: ${p.ref}</div>
                <div><strong>${p.area} ha</strong> - ${p.reg}</div>
            </div>
        `).join('');
    }
}

function obrirModalParcel·la(id = null) {
    document.getElementById('formParcel·la').reset();
    idEditantParcela = id;
    
    if(id) {
        const p = parcel·les.find(x => x.id === id);
        if(p) {
            document.getElementById('nomParcel·la').value = p.nom;
            document.getElementById('refParcel·la').value = p.ref;
            document.getElementById('areaParcel·la').value = p.area;
            document.getElementById('regParcel·la').value = p.reg;
        }
    }
    document.getElementById('modalParcel·la').classList.add('active');
}

function desarParcel·la() {
    const nom = document.getElementById('nomParcel·la').value;
    const area = document.getElementById('areaParcel·la').value;
    if(!nom || !area) return alert("Nom i Àrea obligatoris");

    const obj = {
        id: idEditantParcela || generarUUID(),
        nom: nom,
        ref: document.getElementById('refParcel·la').value,
        area: area,
        reg: document.getElementById('regParcel·la').value
    };

    if(idEditantParcela) {
        const index = parcel·les.findIndex(p => p.id === idEditantParcela);
        parcel·les[index] = obj;
    } else {
        parcel·les.push(obj);
    }

    tancarModals(); renderitzarParcel·les(); renderitzarTauler();
}

function eliminarParcel·la(id) {
    if(confirm("Eliminar parcel·la? Es perdran les dades associades.")) {
        parcel·les = parcel·les.filter(p => p.id !== id);
        renderitzarParcel·les();
        renderitzarTauler();
    }
}

// --- SECTORS ---
function renderitzarSectors() {
    const div = document.getElementById('graellaSectors');
    if(sectors.length === 0) div.innerHTML = '<div class="empty-state">No hi ha sectors.</div>';
    else {
        div.innerHTML = sectors.map(s => `
            <div class="card">
                <div style="display:flex; justify-content:space-between">
                    <div class="card-title">${s.nom}</div>
                    <div>
                        <button class="btn btn-outline btn-sm" onclick="obrirModalSector('${s.id}')">✏️</button>
                        <button class="btn btn-outline btn-sm" style="color:red; border-color:red" onclick="eliminarSector('${s.id}')">🗑️</button>
                    </div>
                </div>
                <div>${especiesFruita[s.especie]?.nom || 'Espècie desconeguda'}</div>
            </div>
        `).join('');
    }
}

function obrirModalSector(id = null) {
    const sel = document.getElementById('espSector');
    const claus = Object.keys(especiesFruita);
    if(claus.length === 0) return alert("Primer crea espècies.");
    sel.innerHTML = claus.map(k => `<option value="${k}">${especiesFruita[k].nom}</option>`).join('');
    
    document.getElementById('formSector').reset();
    idEditantSector = id;

    if(id) {
        const s = sectors.find(x => x.id === id);
        if(s) {
            document.getElementById('nomSector').value = s.nom;
            document.getElementById('espSector').value = s.especie;
        }
    }
    document.getElementById('modalSector').classList.add('active');
}

function desarSector() {
    const obj = {
        id: idEditantSector || generarUUID(),
        nom: document.getElementById('nomSector').value,
        especie: document.getElementById('espSector').value
    };

    if(idEditantSector) {
        const idx = sectors.findIndex(s => s.id === idEditantSector);
        sectors[idx] = obj;
    } else {
        sectors.push(obj);
    }
    tancarModals(); renderitzarSectors();
}

function eliminarSector(id) {
    if(confirm("Eliminar sector?")) {
        sectors = sectors.filter(s => s.id !== id);
        renderitzarSectors();
    }
}

// --- MAQUINÀRIA ---
function renderitzarMaquinaria() {
    const div = document.getElementById('graellaMaquinaria');
    if(maquinaria.length === 0) div.innerHTML = '<div class="empty-state">No hi ha maquinària.</div>';
    else {
        div.innerHTML = maquinaria.map(m => `
            <div class="card">
                <div style="display:flex; justify-content:space-between">
                    <div class="card-title">${m.nom}</div>
                    <div>
                        <button class="btn btn-outline btn-sm" onclick="obrirModalMaquinaria('${m.id}')">✏️</button>
                        <button class="btn btn-outline btn-sm" style="color:red; border-color:red" onclick="eliminarMaquinaria('${m.id}')">🗑️</button>
                    </div>
                </div>
                <div class="badge badge-info">${m.tipus}</div>
                <small>${m.matricula}</small>
            </div>
        `).join('');
    }
}

function obrirModalMaquinaria(id = null) {
    document.getElementById('formMaquinaria').reset();
    idEditantMaquina = id;
    if(id) {
        const m = maquinaria.find(x => x.id === id);
        document.getElementById('nomMaquina').value = m.nom;
        document.getElementById('tipusMaquina').value = m.tipus;
        document.getElementById('idMaquina').value = m.matricula;
    }
    document.getElementById('modalMaquinaria').classList.add('active');
}

function desarMaquinaria() {
    const obj = {
        id: idEditantMaquina || generarUUID(),
        nom: document.getElementById('nomMaquina').value,
        tipus: document.getElementById('tipusMaquina').value,
        matricula: document.getElementById('idMaquina').value
    };
    if(idEditantMaquina) maquinaria[maquinaria.findIndex(m=>m.id===idEditantMaquina)] = obj;
    else maquinaria.push(obj);
    
    tancarModals(); renderitzarMaquinaria();
}

function eliminarMaquinaria(id) {
    if(confirm("Eliminar màquina?")) {
        maquinaria = maquinaria.filter(m => m.id !== id);
        renderitzarMaquinaria();
    }
}

// --- SENSORS ---
function renderitzarSensors() {
    const div = document.getElementById('graellaSensors');
    if(sensors.length === 0) div.innerHTML = '<div class="empty-state">No hi ha sensors.</div>';
    else {
        div.innerHTML = sensors.map(s => {
            let lectura = s.tipus === 'Humitat Sòl' ? (Math.random()*100).toFixed(1)+'%' : (Math.random()*30).toFixed(1)+'ºC';
            return `
            <div class="card">
                <div style="display:flex; justify-content:space-between">
                    <div class="card-title">${s.tipus}</div>
                    <div>
                        <button class="btn btn-outline btn-sm" onclick="obrirModalSensor('${s.id}')">✏️</button>
                        <button class="btn btn-outline btn-sm" style="color:red; border-color:red" onclick="eliminarSensor('${s.id}')">🗑️</button>
                    </div>
                </div>
                <div>ID: ${s.idDev}</div>
                <div class="stat-value" style="font-size:1.2rem; margin-top:0.5rem">📡 ${lectura}</div>
            </div>`;
        }).join('');
    }
}

function obrirModalSensor(id = null) {
    const sel = document.getElementById('parcSensor');
    sel.innerHTML = parcel·les.map(p=>`<option value="${p.id}">${p.nom}</option>`).join('');
    document.getElementById('modalSensor').classList.add('active');
    idEditantSensor = id;
    // Si volguessim editar, aquí ompliríem els camps (completar si cal)
}

function desarSensor() {
    const obj = {
        id: idEditantSensor || generarUUID(),
        idDev: document.getElementById('idSensorDev').value,
        tipus: document.getElementById('tipusSensor').value,
        parcela: document.getElementById('parcSensor').value
    };
    if(idEditantSensor) sensors[sensors.findIndex(s=>s.id===idEditantSensor)] = obj;
    else sensors.push(obj);
    tancarModals(); renderitzarSensors();
}

function eliminarSensor(id) {
    if(confirm("Eliminar sensor?")) {
        sensors = sensors.filter(s => s.id !== id);
        renderitzarSensors();
    }
}