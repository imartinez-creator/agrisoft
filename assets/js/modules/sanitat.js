// =======================================================
// MÒDUL SANITAT (Tractaments, Estoc, Quadern)
// =======================================================

let idEditantEstoc = null;
let idEditantTractament = null;
let idEditantQuadern = null;

// --- CALCULADORA (Sense canvis) ---
function actualitzarCalculadora() {
    const sel = document.getElementById('calcParcel·la');
    if (sel.options.length <= 1) {
        sel.innerHTML = '<option value="">Selecciona...</option>' + parcel·les.map(p => `<option value="${p.id}">${p.nom}</option>`).join('');
    }
    const idParc = sel.value;
    const dosi = parseFloat(document.getElementById('calcDosi').value) || 0;
    const tanc = parseFloat(document.getElementById('calcTanc').value) || 2000;
    const divResultat = document.getElementById('resultatCalculadora');
    if(!idParc || dosi <= 0) return divResultat.innerHTML = "Introdueix dades.";
    const parc = parcel·les.find(p => p.id === idParc);
    if (!parc) return;
    const area = parseFloat(parc.area);
    const total = area * dosi;
    const cubes = (area * 1000) / tanc;
    divResultat.innerHTML = `Necessites <strong>${total.toFixed(2)} L/Kg</strong>. Aprox <strong>${cubes.toFixed(1)} cubes</strong>.`;
}

// --- ESTOC ---
function renderitzarEstoc() {
    const div = document.getElementById('graellaEstoc');
    if(estoc.length === 0) div.innerHTML = '<div class="empty-state">Magatzem buit.</div>';
    else {
        div.innerHTML = estoc.map(p => `
            <div class="card">
                <div style="display:flex; justify-content:space-between">
                    <div class="card-title">${p.nomComercial}</div>
                    <div>
                        <button class="btn btn-outline btn-sm" onclick="obrirModalProducte('${p.id}')">✏️</button>
                        <button class="btn btn-outline btn-sm" style="color:red; border-color:red;" onclick="eliminarProducte('${p.id}')">🗑️</button>
                    </div>
                </div>
                <div class="stat-value" style="font-size:1.5rem">${p.quant} <small>${p.unitat}</small></div>
                <div>Lot: ${p.numLot}</div>
            </div>`).join('');
    }
}

function obrirModalProducte(id = null) {
    document.getElementById('formProducte').reset();
    idEditantEstoc = id;
    if(id) {
        const p = estoc.find(x => x.id === id);
        if(p) {
            document.getElementById('nomProducte').value = p.nomComercial;
            document.getElementById('quantProducte').value = p.quant;
            document.getElementById('unitatProducte').value = p.unitat;
            document.getElementById('lotProducte').value = p.numLot;
            document.getElementById('caducitatProducte').value = p.caducitat;
            document.getElementById('maProducte').value = p.materiaActiva;
        }
    }
    document.getElementById('modalProducte').classList.add('active');
}

function desarProducte() {
    const obj = {
        id: idEditantEstoc || generarUUID(),
        nomComercial: document.getElementById('nomProducte').value,
        quant: parseFloat(document.getElementById('quantProducte').value),
        unitat: document.getElementById('unitatProducte').value,
        numLot: document.getElementById('lotProducte').value,
        caducitat: document.getElementById('caducitatProducte').value,
        materiaActiva: document.getElementById('maProducte').value
    };

    if(idEditantEstoc) estoc[estoc.findIndex(x => x.id === idEditantEstoc)] = obj;
    else estoc.push(obj);

    tancarModals(); renderitzarEstoc();
}

function eliminarProducte(id) {
    if(confirm("Eliminar producte?")) {
        estoc = estoc.filter(p => p.id !== id);
        renderitzarEstoc();
    }
}

// --- TRACTAMENTS ---
function renderitzarTractaments() {
    const tbody = document.getElementById('taulaTractaments');
    if(tractaments.length === 0) { tbody.innerHTML = '<tr><td colspan="6" style="text-align:center">Cap tractament.</td></tr>'; return; }
    
    tbody.innerHTML = tractaments.map(t => {
        const p = parcel·les.find(x=>x.id===t.sectorId)?.nom || '?';
        const m = maquinaria.find(x=>x.id===t.maqId)?.nom || '-';
        const op = treballadors.find(x=>x.idTreballador===t.opId)?.nom || '?';
        return `
        <tr>
            <td>${t.data}</td><td>${p}</td><td>${t.prodNom}</td><td>${m}</td><td>${op}</td>
            <td>
                <button class="btn btn-outline btn-sm" onclick="eliminarTractament('${t.id}')">🗑️</button>
            </td>
        </tr>`;
    }).join('');
}

function obrirModalTractament() {
    const selS = document.getElementById('sectorTractament');
    const selP = document.getElementById('prodTractament');
    const selM = document.getElementById('maqTractament');
    const selO = document.getElementById('opTractament');

    if(parcel·les.length===0 || estoc.length===0 || treballadors.length===0) return alert("Falten dades base.");

    selS.innerHTML = parcel·les.map(p=>`<option value="${p.id}">${p.nom}</option>`).join('');
    selP.innerHTML = estoc.map(p=>`<option value="${p.id}">${p.nomComercial} (Disp: ${p.quant})</option>`).join('');
    selM.innerHTML = maquinaria.map(m=>`<option value="${m.id}">${m.nom}</option>`).join('');
    selO.innerHTML = treballadors.map(t=>`<option value="${t.idTreballador}">${t.nom}</option>`).join('');
    
    document.getElementById('dataTractament').value = new Date().toISOString().split('T')[0];
    document.getElementById('modalTractament').classList.add('active');
}

function desarTractament() {
    const prodId = document.getElementById('prodTractament').value;
    const dosi = parseFloat(document.getElementById('dosiTractament').value);
    
    const prodIdx = estoc.findIndex(p=>p.id===prodId);
    if(estoc[prodIdx].quant < dosi) return alert("Estoc insuficient!");
    
    // Restem estoc (Només si creem nou, per simplificar)
    estoc[prodIdx].quant -= dosi;

    tractaments.push({
        id: generarUUID(),
        data: document.getElementById('dataTractament').value,
        sectorId: document.getElementById('sectorTractament').value,
        prodNom: estoc[prodIdx].nomComercial,
        maqId: document.getElementById('maqTractament').value,
        opId: document.getElementById('opTractament').value,
        temp: document.getElementById('tempTractament').value,
        vent: document.getElementById('ventTractament').value,
        dosi: dosi
    });
    tancarModals(); renderitzarTractaments(); renderitzarEstoc();
}

function eliminarTractament(id) {
    if(confirm("Esborrar registre? L'estoc no es tornarà automàticament.")) {
        tractaments = tractaments.filter(t => t.id !== id);
        renderitzarTractaments();
    }
}

// --- QUADERN ---
function renderitzarQuadern() {
    const tbody = document.getElementById('taulaQuadern');
    if(quadern.length === 0) tbody.innerHTML = '<tr><td colspan="4" style="text-align:center">Buit.</td></tr>';
    else {
        tbody.innerHTML = quadern.map(q => {
            const nomP = parcel·les.find(p => p.id === q.parcelaId)?.nom || 'General';
            return `<tr>
                <td>${q.data}</td><td>${nomP}</td><td>${q.obs}</td>
                <td><button class="btn btn-outline btn-sm" onclick="eliminarNota('${q.id}')">🗑️</button></td>
            </tr>`;
        }).join('');
    }
}

function obrirModalQuadern() {
    const sel = document.getElementById('parcQuadern');
    sel.innerHTML = '<option value="">General</option>' + parcel·les.map(p => `<option value="${p.id}">${p.nom}</option>`).join('');
    document.getElementById('dataQuadern').value = new Date().toISOString().split('T')[0];
    document.getElementById('modalQuadern').classList.add('active');
}

function desarRegistreQuadern() {
    quadern.push({
        id: generarUUID(),
        data: document.getElementById('dataQuadern').value,
        parcelaId: document.getElementById('parcQuadern').value,
        obs: document.getElementById('obsQuadern').value
    });
    tancarModals(); renderitzarQuadern();
}

function eliminarNota(id) {
    quadern = quadern.filter(q => q.id !== id);
    renderitzarQuadern();
}

if(typeof renderitzarQuadern === 'function') setTimeout(renderitzarQuadern, 100);