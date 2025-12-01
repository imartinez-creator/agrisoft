// ============================================
// DADES EN MEMÒRIA
// ============================================

let parcel·les = [];
let sectors = [];
let maquinaria = []; // NOU: Array per a tractors, atomitzadors, etc.
let cultius = [];
let collites = [];
let treballadors = [];
let estoc = [];
let tractaments = [];
let controlsQualitat = [];
let registresHoraris = [];
let quadern = [];

let especiesFruita = {}; 

function generarUUID() {
    return 'id-' + Date.now().toString(36) + '-' + Math.random().toString(36).substr(2, 9);
}

function tancarModals() {
    document.querySelectorAll('.modal').forEach(m => m.classList.remove('active'));
}