// =======================================================
// MÒDUL PRODUCCIÓ (Collita, Qualitat, Clients)
// =======================================================

let idEditantCollita = null;
let idEditantClient = null;
let idEditantQualitat = null;

// --- TAULER ---
function renderitzarTauler() {
    const area = parcel·les.reduce((a, b) => a + parseFloat(b.area), 0);
    const kg = collites.reduce((a, b) => a + parseFloat(b.kg), 0);
    document.getElementById('estadistiquesTauler').innerHTML = `
        <div class="stat-card"><div class="stat-label">Parcel·les</div><div class="stat-value">${parcel·les.length}</div></div>
        <div class="stat-card"><div class="stat-label">Àrea (ha)</div><div class="stat-value">${area.toFixed(2)}</div></div>
        <div class="stat-card"><div class="stat-label">Collita (kg)</div><div class="stat-value">${kg}</div></div>`;
    document.getElementById('resumTauler').innerHTML = parcel·les.slice(0,3).map(p => `<div class="card" style="margin:0"><strong>${p.nom}</strong><br>${p.area} ha</div>`).join('');
}

// --- COLLITES ---
function renderitzarCollitesTaula() {
    const tbody = document.getElementById('taulaCollites');
    if(collites.length === 0) tbody.innerHTML = '<tr><td colspan="6" style="text-align:center">Cap collita.</td></tr>';
    else {
        tbody.innerHTML = collites.map(c => {
            const cult = cultius.find(x=>x.id===c.cultiuId);
            const p = parcel·les.find(x=>x.id===cult?.parcela)?.nom || '?';
            const e = especiesFruita[cult?.especie]?.nom || '?';
            const cli = clients.find(x=>x.id===c.desti)?.nom || 'Magatzem';
            return `
            <tr>
                <td>${c.data}</td><td>${p}</td><td>${e}</td><td>${c.kg}</td><td>${cli}</td>
                <td>
                    <button class="btn btn-outline btn-sm" onclick="obrirModalCollita('${c.id}')">✏️</button>
                    <button class="btn btn-outline btn-sm" style="color:red" onclick="eliminarCollita('${c.id}')">🗑️</button>
                </td>
            </tr>`;
        }).join('');
    }
}

function obrirModalCollita(id = null) {
    const selC = document.getElementById('cultCollita');
    const selCli = document.getElementById('destiCollita');
    
    if(cultius.length===0) return alert("Sense cultius!");
    selC.innerHTML = cultius.map(c=>`<option value="${c.id}">${especiesFruita[c.especie]?.nom} a ${parcel·les.find(p=>p.id===c.parcela)?.nom}</option>`).join('');
    
    selCli.innerHTML = '<option value="">Magatzem</option>' + clients.map(c=>`<option value="${c.id}">${c.nom}</option>`).join('');
    
    idEditantCollita = id;
    if(id) {
        const c = collites.find(x => x.id === id);
        if(c) {
            selC.value = c.cultiuId;
            document.getElementById('dataCollita').value = c.data;
            document.getElementById('kgCollita').value = c.kg;
            selCli.value = c.desti;
            document.getElementById('tempCollita').value = c.temp;
            document.getElementById('humCollita').value = c.hum;
        }
    }
    document.getElementById('modalCollita').classList.add('active');
}

function desarCollita() {
    const obj = {
        id: idEditantCollita || generarUUID(),
        cultiuId: document.getElementById('cultCollita').value,
        data: document.getElementById('dataCollita').value,
        kg: document.getElementById('kgCollita').value,
        desti: document.getElementById('destiCollita').value,
        temp: document.getElementById('tempCollita').value,
        hum: document.getElementById('humCollita').value
    };

    if(idEditantCollita) collites[collites.findIndex(c => c.id === idEditantCollita)] = obj;
    else collites.push(obj);

    tancarModals(); renderitzarCollitesTaula(); renderitzarTauler();
}

function eliminarCollita(id) {
    if(confirm("Eliminar registre collita?")) {
        collites = collites.filter(c => c.id !== id);
        renderitzarCollitesTaula(); renderitzarTauler();
    }
}

// --- QUALITAT ---
function renderitzarQualitat() {
    const t = document.getElementById('taulaQualitat');
    if(controlsQualitat.length === 0) t.innerHTML = '<tr><td colspan="5" style="text-align:center">Cap registre</td></tr>';
    else {
        t.innerHTML = controlsQualitat.map(q => {
            const col = collites.find(c => c.id === q.collitaId);
            return `<tr>
                <td>Lot ${col?.data || '?'}</td><td>${q.brix}º</td><td>${q.calibre}mm</td><td>${q.defectes}%</td>
                <td><button class="btn btn-outline btn-sm" onclick="eliminarQualitat('${q.id}')">🗑️</button></td>
            </tr>`;
        }).join('');
    }
}

function obrirModalQualitat() {
    const sel = document.getElementById('lotQualitat');
    if(collites.length === 0) return alert("No hi ha collites!");
    sel.innerHTML = collites.map(c => `<option value="${c.id}">${c.data} (${c.kg}kg)</option>`).join('');
    document.getElementById('modalQualitat').classList.add('active');
}

function desarQualitat() {
    controlsQualitat.push({
        id: generarUUID(),
        collitaId: document.getElementById('lotQualitat').value,
        brix: document.getElementById('brixQualitat').value,
        calibre: document.getElementById('calibreQualitat').value,
        defectes: document.getElementById('defectesQualitat').value
    });
    tancarModals(); renderitzarQualitat();
}

function eliminarQualitat(id) {
    controlsQualitat = controlsQualitat.filter(q => q.id !== id);
    renderitzarQualitat();
}

// --- CLIENTS ---
function renderitzarClients() {
    const div = document.getElementById('graellaClients');
    if(clients.length===0) div.innerHTML = '<div class="empty-state">No hi ha clients.</div>';
    else {
        div.innerHTML = clients.map(c => `
            <div class="card">
                <div style="display:flex; justify-content:space-between">
                    <div class="card-title">${c.nom}</div>
                    <div>
                        <button class="btn btn-outline btn-sm" onclick="obrirModalClient('${c.id}')">✏️</button>
                        <button class="btn btn-outline btn-sm" style="color:red" onclick="eliminarClient('${c.id}')">🗑️</button>
                    </div>
                </div>
                <div>${c.tipus}</div>
            </div>`).join('');
    }
}

function obrirModalClient(id = null) {
    document.getElementById('modalClient').classList.add('active');
    document.getElementById('formClient').reset(); // Assumint que el formulari té ID formClient
    idEditantClient = id;
    if(id) {
        const c = clients.find(x => x.id === id);
        document.getElementById('nomClient').value = c.nom;
        document.getElementById('tipusClient').value = c.tipus;
        document.getElementById('reqClient').value = c.req;
    }
}

function desarClient() {
    const obj = {
        id: idEditantClient || generarUUID(),
        nom: document.getElementById('nomClient').value,
        tipus: document.getElementById('tipusClient').value,
        req: document.getElementById('reqClient').value
    };
    if(idEditantClient) clients[clients.findIndex(c => c.id === idEditantClient)] = obj;
    else clients.push(obj);
    
    tancarModals(); renderitzarClients();
}

function eliminarClient(id) {
    if(confirm("Eliminar client?")) {
        clients = clients.filter(c => c.id !== id);
        renderitzarClients();
    }
}