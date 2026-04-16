/* global L, turf */
(function () {
  const fc = window.AGRISOFT_PARCELLES || { type: 'FeatureCollection', features: [] };
  const CAN_MANAGE = !!window.AGRISOFT_CAN_MANAGE;

  // Definim helpers encara que estiguem en mode lectura (evita errors a popups)
  window.editParcela = function () {};
  window.deleteParcela = function () {};

  // Wrappers perquè els handlers interns puguin cridar sempre les funcions
  function editParcela(id) { window.editParcela(id); }
  function deleteParcela(id) { window.deleteParcela(id); }

  // -------- Helpers --------
  const esc = (s) => (s == null ? '' : String(s)).replaceAll('<', '&lt;').replaceAll('>', '&gt;');
  const $ = (id) => document.getElementById(id);

  const $polygon = $('polygon');
  const $areaHa = $('area_ha');
  const $areaHaView = $('area_ha_view');
  const $btnSave = $('btnSave');
  const $btnClear = $('btnClear');
  const $btnCancel = $('btnCancel');
  const $form = $('parcelaForm');
  const $formAction = $('form_action');
  const $parcelaId = $('parcela_id');
  const $name = $('name');
  const $notes = $('notes');
  const $formMode = $('form_mode');
  const $lat = $('lat'); // Nou camp lat
  const $lng = $('lng'); // Nou camp lng

  // Si l'usuari és "treballador" (lectura), amaguem els controls d'edició
  if (!CAN_MANAGE) {
    if ($btnSave) $btnSave.style.display = 'none';
    if ($btnClear) $btnClear.style.display = 'none';
    if ($btnCancel) $btnCancel.style.display = 'none';
    if ($form) {
      $form.addEventListener('submit', (e) => {
        e.preventDefault();
      });
    }
  }

  // -------- Map --------
  const defaultCenter = [41.3851, 2.1734];
  const map = L.map('map', {
    zoomControl: true,
    preferCanvas: true
  }).setView(defaultCenter, 13);

  const tilesEsriSat = L.tileLayer(
    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
    {
      maxZoom: 20,
      attribution: 'Tiles &copy; Esri'
    }
  );

  // Només satèl·lit (tal com has demanat)
  tilesEsriSat.addTo(map);

  // Controls
  L.control.scale({ imperial: false }).addTo(map);
  L.control.fullscreen({ position: 'topleft' }).addTo(map);

  // Locate (GPS)
  L.control.locate({
    position: 'topleft',
    flyTo: true,
    showPopup: false,
    locateOptions: { enableHighAccuracy: true, timeout: 8000 }
  }).addTo(map);

  // Geocoder
  if (L.Control && L.Control.Geocoder) {
    L.Control.geocoder({
      defaultMarkGeocode: true,
      placeholder: 'Cerca adreça o lloc...'
    }).addTo(map);
  }

  // MiniMap (també satèl·lit)
  try {
    const miniSat = L.tileLayer(
      'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
      { maxZoom: 20, attribution: 'Tiles &copy; Esri' }
    );
    new L.Control.MiniMap(miniSat, { toggleDisplay: true }).addTo(map);
  } catch (e) {}

  // Measure
  try {
    L.control.measure({
      primaryLengthUnit: 'meters',
      secondaryLengthUnit: 'kilometers',
      primaryAreaUnit: 'sqmeters',
      secondaryAreaUnit: 'hectares'
    }).addTo(map);
  } catch (e) {}

  // Sense selector de capes: només satèl·lit

  // -------- Existing parcels layer --------
  const layersById = {};

  function popupHtml(p) {
    return `
      <strong>${esc(p.name || '')}</strong><br>
      <span class="small">ID: ${esc(p.id)} · Àrea (ha): ${esc(p.area_ha || '')}</span>
      ${p.notes ? `<div style="margin-top:6px;"><em>${esc(p.notes)}</em></div>` : ''}
      <div class="parcelles-popup-actions">
        <button type="button" class="parcelles-btn parcelles-btn--edit" data-action="edit" data-id="${esc(p.id)}">✏️ Editar</button>
        <button type="button" class="parcelles-btn parcelles-btn--delete" data-action="delete" data-id="${esc(p.id)}">🗑️ Eliminar</button>
      </div>
    `;
  }

  const existingLayer = L.geoJSON(fc, {
    style: () => ({ weight: 2, opacity: 1, fillOpacity: 0.12 }),
    onEachFeature: (feature, layer) => {
      const p = feature.properties || {};
      if (p.id != null) layersById[p.id] = layer;
      layer.bindPopup(popupHtml(p), { maxWidth: 360 });

      layer.on('click', () => {
        // Ajuda: en clicar, preparem edició directa
        editParcela(p.id);
      });

      layer.on('popupopen', (e) => {
        const el = e.popup.getElement();
        if (!el) return;
        el.querySelectorAll('button[data-action]')?.forEach((btn) => {
          btn.addEventListener('click', (ev) => {
            ev.preventDefault();
            ev.stopPropagation();
            const id = Number(btn.getAttribute('data-id'));
            const action = btn.getAttribute('data-action');
            if (action === 'edit') editParcela(id);
            if (action === 'delete') deleteParcela(id);
          }, { once: true });
        });
      });
    }
  }).addTo(map);

  // Fit bounds if we have parcels
  try {
    const b = existingLayer.getBounds();
    if (b.isValid()) map.fitBounds(b.pad(0.12));
  } catch (e) {}

  // -------- Public helpers (table + popups) --------
  window.zoomToParcela = function (id) {
    const layer = layersById[id];
    if (!layer) return;
    try {
      map.fitBounds(layer.getBounds().pad(0.25));
      layer.openPopup();
    } catch (e) {}
  };

  // -------- Drawing / Editing --------
  const drawnItems = new L.FeatureGroup();
  map.addLayer(drawnItems);

  if (CAN_MANAGE) {
  const drawControl = new L.Control.Draw({
    position: 'topleft',
    draw: {
      polygon: { allowIntersection: false, showArea: true, metric: true },
      polyline: false,
      rectangle: false,
      circle: false,
      circlemarker: false,
      marker: false
    },
    edit: {
      featureGroup: drawnItems,
      remove: true
    }
  });
  map.addControl(drawControl);

  function setModeCreate() {
    $formAction.value = 'create_parcela';
    $parcelaId.value = '';
    $formMode.textContent = 'Mode: crear';
    $btnCancel.style.display = 'none';
  }

  function setModeEdit(id) {
    $formAction.value = 'update_parcela';
    $parcelaId.value = String(id);
    $formMode.textContent = `Mode: editar (ID ${id})`;
    $btnCancel.style.display = '';
  }

  function clearDrawing(keepFields = false) {
    drawnItems.clearLayers();
    $polygon.value = '';
    $areaHa.value = '0';
    $areaHaView.value = '0';
    // No disable save button anymore automatically, just let HTML validation and JS submit handler do it
    $btnSave.disabled = false;
    if (!keepFields) {
      $name.value = '';
      $notes.value = '';
      if ($lat) $lat.value = '';
      if ($lng) $lng.value = '';
      setModeCreate();
    }
  }

  function setFormFromLayer(layer) {
    drawnItems.clearLayers();
    drawnItems.addLayer(layer);

    const latlngs = layer.getLatLngs();
    if (!latlngs || !latlngs[0] || latlngs[0].length < 3) return;

    const pts = latlngs[0].map((ll) => [Number(ll.lat.toFixed(7)), Number(ll.lng.toFixed(7))]);
    $polygon.value = JSON.stringify(pts);

    // Turf needs [lng,lat]
    const ring = [...pts.map((p) => [p[1], p[0]]), [pts[0][1], pts[0][0]]];
    const areaM2 = turf.area(turf.polygon([ring]));
    const areaHa = areaM2 / 10000;

    $areaHa.value = areaHa.toFixed(6);
    $areaHaView.value = areaHa.toFixed(6);
    $btnSave.disabled = false;
    
    // Auto-fill marcadores si estan buits al dibuixar
    if ($lat && !$lat.value) {
       const center = layer.getBounds().getCenter();
       $lat.value = center.lat.toFixed(6);
       $lng.value = center.lng.toFixed(6);
    }
  }

  map.on(L.Draw.Event.CREATED, (e) => {
    setModeCreate();
    setFormFromLayer(e.layer);
  });

  map.on('draw:edited', (e) => {
    e.layers.eachLayer((layer) => setFormFromLayer(layer));
  });

  map.on('draw:deleted', () => {
    // Si estàvem editant una parcel·la, mantenim nom/notes però desactivem guardat
    $polygon.value = '';
    $areaHa.value = '0';
    $areaHaView.value = '0';
  });

  $btnClear && $btnClear.addEventListener('click', () => clearDrawing(true));
  $btnCancel && $btnCancel.addEventListener('click', () => {
    clearDrawing(false);
    setModeCreate();
  });

  window.editParcela = function (id) {
    const rawParcels = window.AGRISOFT_RAW_PARCELLES || [];
    const pData = rawParcels.find(p => String(p.id) === String(id));
    
    if (!pData) return;

    // Omple formulari amb dades existents
    $name.value = pData.name || '';
    $notes.value = pData.notes || '';
    if ($lat) $lat.value = pData.lat || '';
    if ($lng) $lng.value = pData.lng || '';
    $areaHa.value = pData.area_ha || '0';
    $areaHaView.value = pData.area_ha || '0';
    $btnSave.disabled = false;
    
    setModeEdit(id);

    // Copia el polygon a la capa editable si existeix
    const layer = layersById[id];
    let editable;
    try {
      if (layer && layer.getLatLngs) {
        editable = L.polygon(layer.getLatLngs(), { weight: 2, fillOpacity: 0.18 });
      }
    } catch (e) {}

    if (editable) {
      drawnItems.clearLayers();
      drawnItems.addLayer(editable);
      
      const latlngs = editable.getLatLngs();
      if (latlngs && latlngs[0] && latlngs[0].length >= 3) {
         const pts = latlngs[0].map((ll) => [Number(ll.lat.toFixed(7)), Number(ll.lng.toFixed(7))]);
         $polygon.value = JSON.stringify(pts);
      }
      
      try { map.fitBounds(editable.getBounds().pad(0.25)); } catch (e) {}
    } else {
       // Nomes te lat lng manual
       drawnItems.clearLayers();
       $polygon.value = '';
       if (pData.lat && pData.lng) {
          map.setView([pData.lat, pData.lng], 16);
       }
    }
  };

  window.deleteParcela = function (id) {
    if (!confirm('Segur que vols eliminar aquesta parcel·la?')) return;

    // Fem submit POST al mateix endpoint
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '';

    const a = document.createElement('input');
    a.type = 'hidden';
    a.name = 'action';
    a.value = 'delete_parcela';

    const b = document.createElement('input');
    b.type = 'hidden';
    b.name = 'parcela_id';
    b.value = String(id);

    form.appendChild(a);
    form.appendChild(b);
    document.body.appendChild(form);
    form.submit();
  };

  } // end CAN_MANAGE

  // -------- Time + Weather (Open-Meteo) --------
  const $nowLocal = $('nowLocal');
  const $meteoNow = $('meteoNow');

  function updateClock() {
    const d = new Date();
    $nowLocal.textContent = d.toLocaleString(undefined, {
      weekday: 'short',
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit'
    });
  }
  updateClock();
  setInterval(updateClock, 1000);

  let meteoTimer = null;
  let meteoInflight = false;

  async function fetchMeteo() {
    if (meteoInflight) return;
    meteoInflight = true;

    const c = map.getCenter();
    const lat = c.lat.toFixed(4);
    const lng = c.lng.toFixed(4);

    try {
      const url = `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lng}&current=temperature_2m,apparent_temperature,precipitation,wind_speed_10m,wind_direction_10m&timezone=auto`;
      const res = await fetch(url, { cache: 'no-store' });
      const data = await res.json();
      const cur = data && data.current;
      if (!cur) throw new Error('Resposta meteo invàlida');

      const t = cur.temperature_2m;
      const ta = cur.apparent_temperature;
      const pr = cur.precipitation;
      const ws = cur.wind_speed_10m;
      const wd = cur.wind_direction_10m;
      $meteoNow.textContent = `${t}°C (sensació ${ta}°C) · pluja ${pr} mm · vent ${ws} km/h (${wd}°)`;
    } catch (e) {
      $meteoNow.textContent = 'No s’ha pogut carregar la meteo.';
    } finally {
      meteoInflight = false;
    }
  }

  function scheduleMeteoRefresh() {
    if (meteoTimer) clearTimeout(meteoTimer);
    meteoTimer = setTimeout(fetchMeteo, 1200);
  }

  map.on('moveend', scheduleMeteoRefresh);
  fetchMeteo();
  setInterval(fetchMeteo, 10 * 60 * 1000);

  // Center on user if possible
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (pos) => map.setView([pos.coords.latitude, pos.coords.longitude], 16),
      () => {},
      { enableHighAccuracy: true, timeout: 8000 }
    );
  }

  // Safety: enable save by default so users can fill just lat/lng
  if ($btnSave) $btnSave.disabled = false;
  
  $form.addEventListener('submit', (e) => {
    const hasPoly = ($polygon.value && $polygon.value.length >= 10);
    const hasLatLng = ($lat && $lat.value && $lng && $lng.value);
    if (!hasPoly && !hasLatLng) {
      e.preventDefault();
      alert('Dibuixa un polígon, O BÉ introdueix una Latitud / Longitud manual abans de guardar.');
    }
  });
})();
