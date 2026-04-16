<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';

require_login();

// Dades per als selects
$parceles = db()->query("SELECT id, name, area_ha FROM parcela ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$sectors  = db()->query("SELECT id, nom_sector AS name, parcela_id, superficie FROM sector_cultiu ORDER BY nom_sector")->fetchAll(PDO::FETCH_ASSOC);
$productes = db()->query("SELECT id, name, unitat FROM fito_productes ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$titol = "Calculadora · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="grid">
  <div class="card span6">
    <h2>Calculadora de Tractaments</h2>
    <p class="small">Calcula la quantitat de producte necessària segons la superfície i la dosi per hectàrea.</p>

    <form id="calcForm">
      <label>Parcel·la</label>
      <select id="select_parcela">
        <option value="">— Selecciona parcel·la —</option>
        <?php foreach ($parceles as $p): ?>
          <option value="<?= $p['id'] ?>" data-area="<?= $p['area_ha'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= $p['area_ha'] ?> ha)</option>
        <?php endforeach; ?>
      </select>

      <label>Sector (Opcional)</label>
      <select id="select_sector">
        <option value="">— Tota la parcel·la —</option>
        <?php foreach ($sectors as $s): ?>
          <option value="<?= $s['id'] ?>" data-parcela="<?= $s['parcela_id'] ?>" data-area="<?= $s['superficie'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= $s['superficie'] ?> ha)</option>
        <?php endforeach; ?>
      </select>

      <label>Producte</label>
      <select id="select_producte">
        <option value="">— Selecciona producte —</option>
        <?php foreach ($productes as $p): ?>
          <option value="<?= $p['id'] ?>" data-unitat="<?= $p['unitat'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= $p['unitat'] ?>)</option>
        <?php endforeach; ?>
      </select>

      <label>Superfície a tractar (ha)</label>
      <input type="number" step="0.0001" id="superficie" placeholder="Ex: 1.5">

      <label>Dosi per hectàrea</label>
      <div style="display: flex; gap: 10px; align-items: center;">
        <input type="number" step="0.01" id="dosi_ha" placeholder="Ex: 2.5">
        <span id="unitat_label" class="small">L o Kg / ha</span>
      </div>

      <div style="margin-top: 20px; padding: 15px; background: rgba(22, 163, 74, 0.1); border-radius: 12px; border: 1px solid rgba(22, 163, 74, 0.3);">
        <h3 style="margin-top: 0;">Resultat:</h3>
        <div style="font-size: 2rem; font-weight: 800; color: var(--accent);">
          <span id="resultat">0.00</span> <span id="unitat_resultat">unitats</span>
        </div>
      </div>

      <div style="margin-top: 20px;">
        <a href="tractaments.php" id="btn_anar_tractament" class="btn secondary" style="display: none;">Registrar Tractament</a>
      </div>
    </form>
  </div>

  <div class="card span6">
    <h2>Informació del Càlcul</h2>
    <p>Aquesta eina t'ajuda a determinar el volum total necessari per a una aplicació fitosanitària.</p>
    <ul class="small">
      <li><strong>Superfície:</strong> S'agafa automàticament de la base de dades segons la parcel·la o sector triat, però la pots modificar manualment.</li>
      <li><strong>Fórmula:</strong> Superfície (ha) × Dosi per hectàrea = Quantitat Total.</li>
      <li><strong>Unitats:</strong> S'adapten al producte seleccionat (Litres o Kilograms).</li>
    </ul>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const selectParcela = document.getElementById('select_parcela');
  const selectSector = document.getElementById('select_sector');
  const selectProducte = document.getElementById('select_producte');
  const inputSuperficie = document.getElementById('superficie');
  const inputDosiHa = document.getElementById('dosi_ha');
  const spanResultat = document.getElementById('resultat');
  const spanUnitatLabel = document.getElementById('unitat_label');
  const spanUnitatResultat = document.getElementById('unitat_resultat');
  const btnAnarTractament = document.getElementById('btn_anar_tractament');

  function calcular() {
    const area = parseFloat(inputSuperficie.value) || 0;
    const dosi = parseFloat(inputDosiHa.value) || 0;
    const total = area * dosi;
    spanResultat.textContent = total.toFixed(2);
    
    if (total > 0 && selectProducte.value) {
        btnAnarTractament.style.display = 'inline-flex';
        const url = `tractaments.php?parcela_id=${selectParcela.value}&sector_id=${selectSector.value}&producte_id=${selectProducte.value}&dosi_ha=${dosi}&dosi_tot=${total.toFixed(2)}`;
        btnAnarTractament.href = url;
    } else {
        btnAnarTractament.style.display = 'none';
    }
  }

  selectParcela.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const area = opt.getAttribute('data-area');
    inputSuperficie.value = area || '';
    
    // Filtrar sectors
    const pId = this.value;
    const sectors = selectSector.querySelectorAll('option');
    sectors.forEach(o => {
      if (o.value === "") { o.style.display = "block"; return; }
      o.style.display = (pId === "" || o.getAttribute('data-parcela') === pId) ? "block" : "none";
    });
    selectSector.value = "";
    calcular();
  });

  selectSector.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (opt.value !== "") {
        inputSuperficie.value = opt.getAttribute('data-area') || '';
    } else {
        const pOpt = selectParcela.options[selectParcela.selectedIndex];
        inputSuperficie.value = pOpt.getAttribute('data-area') || '';
    }
    calcular();
  });

  selectProducte.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const unitat = opt.getAttribute('data-unitat') || 'unitats';
    spanUnitatLabel.textContent = unitat + ' / ha';
    spanUnitatResultat.textContent = unitat;
    calcular();
  });

  inputSuperficie.addEventListener('input', calcular);
  inputDosiHa.addEventListener('input', calcular);
});
</script>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
