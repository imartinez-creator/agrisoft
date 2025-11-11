// ============================================
// AGRISOFT - Sistema de Gestió de Finques Fruiteres
// Gestió de Parcel·les Cadastrals i Sectors de Cultiu amb Polígons GPS
// ============================================

// ============================================
// VARIABLES GLOBALS
// ============================================

let parcel·les = [] // Parcel·les cadastrals amb polígons GPS
let sectors = [] // Sectors de cultiu (poden ocupar múltiples parcel·les)
let cultius = [] // Cultius (deprecated, migrat a sectors)
let collites = [] // Registres de collita

// Variables per al sistema de dibuix
let modeDibuix = null // 'parcel·la', 'sector', 'fila'
let puntsTemporals = []
let elementEnEdicio = null
let sectorActual = null // Per gestionar files

// Informació de les varietats de fruita
const varietatsFruita = {
  "poma-golden": { nom: "Poma Golden Delicious", tipus: "Poma", diesMaduració: 150, rendimentPerArbre: 45 },
  "poma-fuji": { nom: "Poma Fuji", tipus: "Poma", diesMaduració: 165, rendimentPerArbre: 50 },
  "pera-conference": { nom: "Pera Conference", tipus: "Pera", diesMaduració: 140, rendimentPerArbre: 40 },
  "pera-blanquilla": { nom: "Pera Blanquilla", tipus: "Pera", diesMaduració: 130, rendimentPerArbre: 38 },
  "préssec-groc": { nom: "Préssec Groc", tipus: "Préssec", diesMaduració: 120, rendimentPerArbre: 35 },
  "cirera-picota": { nom: "Cirera Picota", tipus: "Cirera", diesMaduració: 90, rendimentPerArbre: 25 },
  "pruna-claudia": { nom: "Pruna Claudia", tipus: "Pruna", diesMaduració: 110, rendimentPerArbre: 30 },
}

// ============================================
// INICIALITZACIÓ
// ============================================

function inicialitzar() {
  carregarDades()
  renderitzarTauler()
  configurarEventsDibuix()
}

function configurarEventsDibuix() {
  // Configurem els events per al mapa de parcel·les
  const svgParcel·les = document.getElementById("svgMapaParcel·les")
  if (svgParcel·les) {
    svgParcel·les.addEventListener("click", gestioClickMapa)
    svgParcel·les.addEventListener("dblclick", finalitzarDibuix)
  }

  // Configurem els events per al mapa de sectors
  const svgSectors = document.getElementById("svgMapaSectors")
  if (svgSectors) {
    svgSectors.addEventListener("click", gestioClickMapa)
    svgSectors.addEventListener("dblclick", finalitzarDibuix)
  }

  // Configurem els events per al mapa de files
  const svgFiles = document.getElementById("svgMapaFiles")
  if (svgFiles) {
    svgFiles.addEventListener("click", gestioClickMapa)
    svgFiles.addEventListener("dblclick", finalitzarDibuix)
  }
}

// ============================================
// GESTIÓ DE DADES
// ============================================

function carregarDades() {
  const parcel·lesDesades = localStorage.getItem("agrisoft_parcel·les")
  const sectorsDesats = localStorage.getItem("agrisoft_sectors")
  const cultiusDesats = localStorage.getItem("agrisoft_cultius")
  const collitesDesades = localStorage.getItem("agrisoft_collites")

  if (parcel·lesDesades) parcel·les = JSON.parse(parcel·lesDesades)
  if (sectorsDesats) sectors = JSON.parse(sectorsDesats)
  if (cultiusDesats) cultius = JSON.parse(cultiusDesats)
  if (collitesDesades) collites = JSON.parse(collitesDesades)

  if (parcel·les.length === 0) {
    crearDadesExemple()
  }
}

function desarDades() {
  localStorage.setItem("agrisoft_parcel·les", JSON.stringify(parcel·les))
  localStorage.setItem("agrisoft_sectors", JSON.stringify(sectors))
  localStorage.setItem("agrisoft_cultius", JSON.stringify(cultius))
  localStorage.setItem("agrisoft_collites", JSON.stringify(collites))
}

function crearDadesExemple() {
  // Creem parcel·les cadastrals d'exemple amb polígons GPS
  parcel·les = [
    {
      id: "1",
      nom: "Parcel·la Nord",
      refCadastral: "CAT-001-2024",
      area: 2.5,
      ubicació: "Zona Nord",
      tipusSòl: "franc",
      reg: "goteig",
      notes: "",
      poligon: [
        [100, 100],
        [350, 100],
        [350, 280],
        [100, 280],
      ], // Coordenades del polígon
    },
    {
      id: "2",
      nom: "Parcel·la Sud",
      refCadastral: "CAT-002-2024",
      area: 3.2,
      ubicació: "Zona Sud",
      tipusSòl: "argilós",
      reg: "aspersió",
      notes: "",
      poligon: [
        [400, 150],
        [700, 150],
        [700, 400],
        [400, 400],
      ],
    },
    {
      id: "3",
      nom: "Parcel·la Est",
      refCadastral: "CAT-003-2024",
      area: 1.8,
      ubicació: "Zona Est",
      tipusSòl: "llimós",
      reg: "goteig",
      notes: "",
      poligon: [
        [750, 100],
        [950, 100],
        [950, 350],
        [750, 350],
      ],
    },
  ]

  // Creem sectors de cultiu d'exemple
  sectors = [
    {
      id: "1",
      nom: "Sector A - Pomeres Golden",
      varietat: "poma-golden",
      superficie: 2.0,
      dataPlantacio: "2024-03-15",
      nombreFiles: 25,
      arbresPerFila: 40,
      estat: "actiu",
      notes: "",
      poligon: [
        [120, 120],
        [330, 120],
        [330, 260],
        [120, 260],
      ], // Dins de parcel·la 1
      files: [], // Files d'arbres
    },
    {
      id: "2",
      nom: "Sector B - Pereres Conference",
      varietat: "pera-conference",
      superficie: 2.5,
      dataPlantacio: "2024-02-20",
      nombreFiles: 30,
      arbresPerFila: 35,
      estat: "collita",
      notes: "",
      poligon: [
        [420, 170],
        [680, 170],
        [680, 380],
        [420, 380],
      ], // Dins de parcel·la 2
      files: [],
    },
  ]

  cultius = []
  collites = []

  desarDades()
}

// ============================================
// GESTIÓ DE DADES JSON
// ============================================

function descarregarDades() {
  const dades = {
    parcel·les: parcel·les,
    sectors: sectors,
    cultius: cultius,
    collites: collites,
    dataExportació: new Date().toLocaleString("ca-ES"),
  }

  const jsonString = JSON.stringify(dades, null, 2)
  const blob = new Blob([jsonString], { type: "application/json" })
  const url = URL.createObjectURL(blob)

  const link = document.createElement("a")
  link.href = url
  link.download = `agrisoft_dades_${new Date().toISOString().split("T")[0]}.json`
  link.click()

  URL.revokeObjectURL(url)
  alert("✅ Dades descarregades correctament!")
}

function carregarArxiu() {
  document.getElementById("inputCarregarArxiu").click()
}

function processarArxiu() {
  const input = document.getElementById("inputCarregarArxiu")
  const arxiu = input.files[0]

  if (!arxiu) return

  const lector = new FileReader()

  lector.onload = (event) => {
    try {
      const contingut = event.target.result
      const dades = JSON.parse(contingut)

      if (!dades.parcel·les) {
        throw new Error("L'arxiu no té el format correcte")
      }

      parcel·les = dades.parcel·les
      sectors = dades.sectors || []
      cultius = dades.cultius || []
      collites = dades.collites || []

      desarDades()
      alert("✅ Dades carregades correctament!")

      renderitzarTauler()
      renderitzarParcel·les()
      renderitzarSectors()
      renderitzarCultius()
      renderitzarHistoric()
      renderitzarMapa()
    } catch (error) {
      alert("❌ Error al carregar l'arxiu: " + error.message)
    }
  }

  lector.readAsText(arxiu)
  input.value = ""
}

// ============================================
// EXPORTACIÓ/IMPORTACIÓ GeoJSON
// ============================================

function exportarGeoJSON() {
  const features = parcel·les.map((p) => ({
    type: "Feature",
    properties: {
      id: p.id,
      nom: p.nom,
      refCadastral: p.refCadastral,
      area: p.area,
      ubicacio: p.ubicació,
      tipusSol: p.tipusSòl,
      reg: p.reg,
      notes: p.notes,
    },
    geometry: {
      type: "Polygon",
      coordinates: [p.poligon.map((coord) => [coord[0], coord[1]])],
    },
  }))

  const geoJSON = {
    type: "FeatureCollection",
    features: features,
  }

  const jsonString = JSON.stringify(geoJSON, null, 2)
  const blob = new Blob([jsonString], { type: "application/json" })
  const url = URL.createObjectURL(blob)

  const link = document.createElement("a")
  link.href = url
  link.download = `parcel·les_${new Date().toISOString().split("T")[0]}.geojson`
  link.click()

  URL.revokeObjectURL(url)
  alert("✅ GeoJSON exportat correctament!")
}

function obrirModalImportarGeoJSON() {
  document.getElementById("modalImportarGeoJSON").classList.add("active")
}

function tancarModalImportarGeoJSON() {
  document.getElementById("modalImportarGeoJSON").classList.remove("active")
}

function processarImportGeoJSON() {
  const input = document.getElementById("arxiuGeoJSON")
  const arxiu = input.files[0]

  if (!arxiu) {
    alert("Si us plau, selecciona un arxiu")
    return
  }

  const lector = new FileReader()

  lector.onload = (event) => {
    try {
      const contingut = event.target.result
      let geoJSON

      // Intentem parsejar com JSON
      if (arxiu.name.endsWith(".geojson") || arxiu.name.endsWith(".json")) {
        geoJSON = JSON.parse(contingut)
      } else if (arxiu.name.endsWith(".kml")) {
        // Per KML caldria un parser específic
        alert("⚠️ Format KML no implementat encara. Si us plau, converteix-lo a GeoJSON.")
        return
      }

      // Validem que sigui un GeoJSON vàlid
      if (!geoJSON.type || (geoJSON.type !== "FeatureCollection" && geoJSON.type !== "Feature")) {
        throw new Error("Format GeoJSON no vàlid")
      }

      const features = geoJSON.type === "FeatureCollection" ? geoJSON.features : [geoJSON]
      let importades = 0

      features.forEach((feature) => {
        if (feature.geometry && feature.geometry.type === "Polygon") {
          const coords = feature.geometry.coordinates[0]
          const poligon = coords.map((c) => [c[0], c[1]])

          const parcel·la = {
            id: Date.now().toString() + Math.random(),
            nom: feature.properties.nom || feature.properties.name || "Parcel·la Importada",
            refCadastral: feature.properties.refCadastral || feature.properties.ref || "IMP-" + Date.now(),
            area: feature.properties.area || calcularAreaPoligon(poligon),
            ubicació: feature.properties.ubicacio || feature.properties.location || "",
            tipusSòl: feature.properties.tipusSol || "franc",
            reg: feature.properties.reg || "goteig",
            notes: feature.properties.notes || "Importada de GeoJSON",
            poligon: normalitzarCoordenades(poligon),
          }

          parcel·les.push(parcel·la)
          importades++
        }
      })

      desarDades()
      tancarModalImportarGeoJSON()
      renderitzarParcel·les()
      alert(`✅ S'han importat ${importades} parcel·les correctament!`)
    } catch (error) {
      alert("❌ Error al processar l'arxiu: " + error.message)
    }
  }

  lector.readAsText(arxiu)
}

function normalitzarCoordenades(coords) {
  // Converteix coordenades GPS a coordenades del mapa SVG
  // Això és una simplificació - en producció caldria una projecció real
  return coords.map((c) => [
    (c[0] % 1) * 1000, // Longitud
    (c[1] % 1) * 700, // Latitud
  ])
}

// ============================================
// SISTEMA DE DIBUIX DE POLÍGONS
// ============================================

function iniciarDibuixParcel·la() {
  modeDibuix = "parcel·la"
  puntsTemporals = []
  document.getElementById("btnDibuixarParcel·la").textContent = "⏹️ Cancel·lar Dibuix"
  document.getElementById("btnDibuixarParcel·la").onclick = cancel·larDibuix
  alert("Fes clic al mapa per afegir vèrtexs al polígon. Doble clic per finalitzar.")
}

function iniciarDibuixSector() {
  modeDibuix = "sector"
  puntsTemporals = []
  document.getElementById("btnDibuixarSector").textContent = "⏹️ Cancel·lar Dibuix"
  document.getElementById("btnDibuixarSector").onclick = cancel·larDibuix
  alert("Fes clic al mapa per afegir vèrtexs al polígon del sector. Doble clic per finalitzar.")
}

function iniciarDibuixFila() {
  modeDibuix = "fila"
  puntsTemporals = []
  document.getElementById("btnDibuixarFila").textContent = "⏹️ Cancel·lar Dibuix"
  document.getElementById("btnDibuixarFila").onclick = cancel·larDibuix
  alert("Fes clic per marcar l'inici i el final de la fila.")
}

function cancel·larDibuix() {
  modeDibuix = null
  puntsTemporals = []

  // Netejem la capa de dibuix
  const capaDibuix = document.getElementById("capaDibuix")
  if (capaDibuix) capaDibuix.innerHTML = ""

  const capaDibuixSector = document.getElementById("capaDibuixSector")
  if (capaDibuixSector) capaDibuixSector.innerHTML = ""

  const capaDibuixFila = document.getElementById("capaDibuixFila")
  if (capaDibuixFila) capaDibuixFila.innerHTML = ""

  // Restaurem els botons
  const btnParcel·la = document.getElementById("btnDibuixarParcel·la")
  if (btnParcel·la) {
    btnParcel·la.textContent = "✏️ Dibuixar Parcel·la"
    btnParcel·la.onclick = iniciarDibuixParcel·la
  }

  const btnSector = document.getElementById("btnDibuixarSector")
  if (btnSector) {
    btnSector.textContent = "✏️ Dibuixar Sector"
    btnSector.onclick = iniciarDibuixSector
  }

  const btnFila = document.getElementById("btnDibuixarFila")
  if (btnFila) {
    btnFila.textContent = "✏️ Dibuixar Fila"
    btnFila.onclick = iniciarDibuixFila
  }
}

function gestioClickMapa(event) {
  if (!modeDibuix) return

  // Obtenim les coordenades relatives al SVG
  const svg = event.currentTarget
  const pt = svg.createSVGPoint()
  pt.x = event.clientX
  pt.y = event.clientY
  const coordenades = pt.matrixTransform(svg.getScreenCTM().inverse())

  puntsTemporals.push([coordenades.x, coordenades.y])

  actualitzarVistaPrevia()
}

function actualitzarVistaPrevia() {
  if (puntsTemporals.length === 0) return

  let capaDibuix
  if (modeDibuix === "parcel·la") {
    capaDibuix = document.getElementById("capaDibuix")
  } else if (modeDibuix === "sector") {
    capaDibuix = document.getElementById("capaDibuixSector")
  } else if (modeDibuix === "fila") {
    capaDibuix = document.getElementById("capaDibuixFila")
  }

  if (!capaDibuix) return

  capaDibuix.innerHTML = ""

  if (modeDibuix === "fila") {
    // Per files, dibuixem una línia
    if (puntsTemporals.length === 1) {
      // Punt inicial
      capaDibuix.innerHTML = `<circle cx="${puntsTemporals[0][0]}" cy="${puntsTemporals[0][1]}" r="5" fill="#4a7c2c" />`
    } else if (puntsTemporals.length >= 2) {
      // Línia completa
      capaDibuix.innerHTML = `
                <line x1="${puntsTemporals[0][0]}" y1="${puntsTemporals[0][1]}" 
                      x2="${puntsTemporals[1][0]}" y2="${puntsTemporals[1][1]}" 
                      stroke="#4a7c2c" stroke-width="3" />
                <circle cx="${puntsTemporals[0][0]}" cy="${puntsTemporals[0][1]}" r="5" fill="#4a7c2c" />
                <circle cx="${puntsTemporals[1][0]}" cy="${puntsTemporals[1][1]}" r="5" fill="#4a7c2c" />
            `
    }
  } else {
    // Per parcel·les i sectors, dibuixem polígons
    const punts = puntsTemporals.map((p) => p.join(",")).join(" ")

    capaDibuix.innerHTML = `
            <polyline points="${punts}" fill="rgba(74, 124, 44, 0.3)" 
                     stroke="#4a7c2c" stroke-width="2" />
            ${puntsTemporals.map((p) => `<circle cx="${p[0]}" cy="${p[1]}" r="5" fill="#4a7c2c" />`).join("")}
        `
  }
}

function finalitzarDibuix(event) {
  if (!modeDibuix) return

  event.preventDefault()
  event.stopPropagation()

  if (modeDibuix === "fila") {
    if (puntsTemporals.length < 2) {
      alert("Necessites marcar 2 punts per definir una fila")
      return
    }

    // Afegim la fila al sector actual
    if (sectorActual) {
      const fila = {
        id: Date.now().toString(),
        numero: sectorActual.files.length + 1,
        coordenades: [puntsTemporals[0], puntsTemporals[1]],
        arbres: sectorActual.arbresPerFila || 0,
        longitud: calcularDistancia(puntsTemporals[0], puntsTemporals[1]),
      }

      sectorActual.files.push(fila)
      desarDades()
      renderitzarFilesModal(sectorActual.id)
    }
  } else if (puntsTemporals.length >= 3) {
    // Tanquem el polígon
    const poligon = [...puntsTemporals]

    if (modeDibuix === "parcel·la") {
      // Obrim el modal per completar les dades de la parcel·la
      obrirModalParcel·la()
      document.getElementById("coordenadesParcel·la").value = poligon
        .map((p) => `${p[0].toFixed(2)},${p[1].toFixed(2)}`)
        .join("; ")
      elementEnEdicio = { poligon: poligon }
    } else if (modeDibuix === "sector") {
      // Obrim el modal per completar les dades del sector
      obrirModalSector()
      elementEnEdicio = { poligon: poligon }

      // Calculem quines parcel·les ocupa
      const parcel·lesOcupades = trobarParcel·lesInterseccio(poligon)
      document.getElementById("parcel·lesSector").value =
        parcel·lesOcupades.map((p) => p.nom).join(", ") || "Cap parcel·la"

      // Calculem la superfície aproximada
      const area = calcularAreaPoligon(poligon)
      document.getElementById("superficieSector").value = area.toFixed(2)
    }
  } else {
    alert("Necessites almenys 3 punts per crear un polígon")
    return
  }

  cancel·larDibuix()
}

// ============================================
// UTILITATS GEOMÈTRIQUES
// ============================================

function calcularAreaPoligon(poligon) {
  // Fórmula de Shoelace per calcular l'àrea d'un polígon
  let area = 0
  const n = poligon.length

  for (let i = 0; i < n; i++) {
    const j = (i + 1) % n
    area += poligon[i][0] * poligon[j][1]
    area -= poligon[j][0] * poligon[i][1]
  }

  area = Math.abs(area) / 2

  // Convertim de píxels a hectàrees (escala aproximada)
  // 1000x700 píxels = 10 hectàrees (exemple)
  return (area / 70000) * 10
}

function calcularDistancia(p1, p2) {
  const dx = p2[0] - p1[0]
  const dy = p2[1] - p1[1]
  const pixels = Math.sqrt(dx * dx + dy * dy)

  // Convertim píxels a metres (escala aproximada)
  // 100 píxels = 50 metres (exemple)
  return (pixels / 100) * 50
}

function puntDinsPoligon(punt, poligon) {
  // Ray casting algorithm
  let dins = false
  const x = punt[0],
    y = punt[1]

  for (let i = 0, j = poligon.length - 1; i < poligon.length; j = i++) {
    const xi = poligon[i][0],
      yi = poligon[i][1]
    const xj = poligon[j][0],
      yj = poligon[j][1]

    const intersecta = yi > y !== yj > y && x < ((xj - xi) * (y - yi)) / (yj - yi) + xi

    if (intersecta) dins = !dins
  }

  return dins
}

function poligonsInterseccionen(poligon1, poligon2) {
  // Comprovem si algun vèrtex d'un polígon està dins de l'altre
  for (const punt of poligon1) {
    if (puntDinsPoligon(punt, poligon2)) return true
  }
  for (const punt of poligon2) {
    if (puntDinsPoligon(punt, poligon1)) return true
  }
  return false
}

function trobarParcel·lesInterseccio(poligonSector) {
  return parcel·les.filter((p) => poligonsInterseccionen(poligonSector, p.poligon))
}

// ============================================
// NAVEGACIÓ
// ============================================

function mostrarSeccio(idSeccio) {
  document.querySelectorAll(".section").forEach((s) => s.classList.remove("active"))
  document.querySelectorAll(".nav-tab").forEach((t) => t.classList.remove("active"))

  document.getElementById(idSeccio).classList.add("active")
  event.target.classList.add("active")

  if (idSeccio === "tauler") renderitzarTauler()
  else if (idSeccio === "parcel·les") renderitzarParcel·les()
  else if (idSeccio === "sectors") renderitzarSectors()
  else if (idSeccio === "cultius") renderitzarCultius()
  else if (idSeccio === "historic") renderitzarHistoric()
  else if (idSeccio === "mapa") renderitzarMapa()
}

// ============================================
// RENDER TAULER PRINCIPAL
// ============================================

function renderitzarTauler() {
  const areaTotal = parcel·les.reduce((suma, p) => suma + Number.parseFloat(p.area), 0)
  const sectorsActius = sectors.filter((s) => s.estat === "actiu" || s.estat === "collita").length
  const produccióTotal = sectors.reduce((suma, s) => {
    const varietat = varietatsFruita[s.varietat]
    const totalArbres = s.nombreFiles * (s.arbresPerFila || 0)
    return suma + totalArbres * (varietat?.rendimentPerArbre || 0)
  }, 0)

  document.getElementById("estadistiquesTauler").innerHTML = `
        <div class="stat-card">
            <div class="stat-label">Parcel·les Cadastrals</div>
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
            <div class="stat-label">Producció Estimada</div>
            <div class="stat-value">${(produccióTotal / 1000).toFixed(1)} <span style="font-size: 1rem; color: var(--text-gris);">t</span></div>
        </div>
    `

  // Properes collites (basades en sectors)
  const avui = new Date()
  const properes = sectors.filter((s) => {
    if (!s.dataPlantacio || s.estat === "completat") return false
    const varietat = varietatsFruita[s.varietat]
    if (!varietat) return false

    const plantacio = new Date(s.dataPlantacio)
    const dataCollita = new Date(plantacio)
    dataCollita.setDate(dataCollita.getDate() + varietat.diesMaduració)

    const diesFins = Math.ceil((dataCollita - avui) / (1000 * 60 * 60 * 24))
    return diesFins >= 0 && diesFins <= 30
  })

  if (properes.length > 0) {
    document.getElementById("properesCollites").innerHTML = properes
      .map((s) => {
        const varietat = varietatsFruita[s.varietat]
        const plantacio = new Date(s.dataPlantacio)
        const dataCollita = new Date(plantacio)
        dataCollita.setDate(dataCollita.getDate() + varietat.diesMaduració)
        const diesFins = Math.ceil((dataCollita - avui) / (1000 * 60 * 60 * 24))

        return `
                <div class="alert alert-warning">
                    <div>⚠️</div>
                    <div>
                        <strong>${varietat.nom}</strong> - ${s.nom} - 
                        Collita estimada: ${formatarData(dataCollita.toISOString().split("T")[0])} 
                        (${diesFins} dies)
                    </div>
                </div>
            `
      })
      .join("")
  } else {
    document.getElementById("properesCollites").innerHTML = `
            <div class="alert alert-info">
                <div>ℹ️</div>
                <div>No hi ha collites programades per als propers 30 dies</div>
            </div>
        `
  }

  // Resum de parcel·les
  document.getElementById("resumParcel·les").innerHTML = parcel·les
    .slice(0, 6)
    .map((p) => {
      const sectorsParcel·la = sectors.filter((s) => {
        if (!s.poligon) return false
        return poligonsInterseccionen(s.poligon, p.poligon)
      })

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
                        <span class="info-label">Sectors:</span>
                        <span>${sectorsParcel·la.length}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Reg:</span>
                        <span>${p.reg}</span>
                    </div>
                </div>
            </div>
        `
    })
    .join("")
}

// ============================================
// RENDER PARCEL·LES
// ============================================

function renderitzarParcel·les() {
  // Renderitzem el mapa de parcel·les
  const capaParcel·les = document.getElementById("capaParcel·les")
  if (capaParcel·les) {
    capaParcel·les.innerHTML = parcel·les
      .map((p) => {
        const punts = p.poligon.map((coord) => coord.join(",")).join(" ")
        const centreX = p.poligon.reduce((suma, c) => suma + c[0], 0) / p.poligon.length
        const centreY = p.poligon.reduce((suma, c) => suma + c[1], 0) / p.poligon.length

        return `
                <g onclick="seleccionarParcel·laEditar('${p.id}')" style="cursor: pointer;">
                    <polygon points="${punts}" fill="rgba(212, 165, 116, 0.3)" 
                             stroke="#8b6f47" stroke-width="2" />
                    <text x="${centreX}" y="${centreY}" text-anchor="middle" 
                          font-size="14" font-weight="bold" fill="#2d5016">${p.nom}</text>
                    <text x="${centreX}" y="${centreY + 20}" text-anchor="middle" 
                          font-size="11" fill="#78716c">${p.area} ha</text>
                </g>
            `
      })
      .join("")
  }

  // Renderitzem la graella de parcel·les
  const cerca = document.getElementById("cercaParcel·les")?.value.toLowerCase() || ""
  const filtrades = parcel·les.filter(
    (p) =>
      p.nom.toLowerCase().includes(cerca) ||
      p.refCadastral.toLowerCase().includes(cerca) ||
      (p.ubicació && p.ubicació.toLowerCase().includes(cerca)),
  )

  if (filtrades.length === 0) {
    document.getElementById("graellaParcel·les").innerHTML = `
            <div class="empty-state">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🗺️</div>
                <div style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--text);">No hi ha parcel·les</div>
                <p>Comença afegint la teva primera parcel·la cadastral</p>
            </div>
        `
    return
  }

  document.getElementById("graellaParcel·les").innerHTML = filtrades
    .map((p) => {
      const sectorsParcel·la = sectors.filter((s) => {
        if (!s.poligon) return false
        return poligonsInterseccionen(s.poligon, p.poligon)
      })

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
                        <span>${p.ubicació || "-"}</span>
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
                        <span class="info-label">Sectors:</span>
                        <span>${sectorsParcel·la.length}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Vèrtexs:</span>
                        <span>${p.poligon.length}</span>
                    </div>
                </div>
                <div style="margin-top: 1rem;">
                    <button class="btn btn-outline btn-sm" onclick="editarParcel·la('${p.id}')">Editar</button>
                    <button class="btn btn-outline btn-sm" onclick="eliminarParcel·la('${p.id}')">Eliminar</button>
                </div>
            </div>
        `
    })
    .join("")
}

function filtrarParcel·les() {
  renderitzarParcel·les()
}

function seleccionarParcel·laEditar(idParcel·la) {
  editarParcel·la(idParcel·la)
}

function obrirModalParcel·la(idParcel·la = null) {
  const modal = document.getElementById("modalParcel·la")
  const formulari = document.getElementById("formulariParcel·la")
  formulari.reset()

  if (idParcel·la) {
    const parcel·la = parcel·les.find((p) => p.id === idParcel·la)
    document.getElementById("titolModalParcel·la").textContent = "Editar Parcel·la Cadastral"
    document.getElementById("idParcel·la").value = parcel·la.id
    document.getElementById("nomParcel·la").value = parcel·la.nom
    document.getElementById("refCadastralParcel·la").value = parcel·la.refCadastral
    document.getElementById("areaParcel·la").value = parcel·la.area
    document.getElementById("ubicacióParcel·la").value = parcel·la.ubicació || ""
    document.getElementById("tipusSòlParcel·la").value = parcel·la.tipusSòl
    document.getElementById("regParcel·la").value = parcel·la.reg
    document.getElementById("notesParcel·la").value = parcel·la.notes || ""
    document.getElementById("coordenadesParcel·la").value = parcel·la.poligon
      .map((p) => `${p[0].toFixed(2)},${p[1].toFixed(2)}`)
      .join("; ")
    elementEnEdicio = parcel·la
  } else {
    document.getElementById("titolModalParcel·la").textContent = "Nova Parcel·la Cadastral"
  }

  modal.classList.add("active")
}

function tancarModalParcel·la() {
  document.getElementById("modalParcel·la").classList.remove("active")
  elementEnEdicio = null
}

function editarParcel·la(id) {
  obrirModalParcel·la(id)
}

function eliminarParcel·la(id) {
  if (confirm("Estàs segur que vols eliminar aquesta parcel·la?")) {
    parcel·les = parcel·les.filter((p) => p.id !== id)
    desarDades()
    renderitzarParcel·les()
    renderitzarTauler()
  }
}

function desarParcel·la() {
  const id = document.getElementById("idParcel·la").value

  // Obtenim el polígon de les coordenades o de l'element en edició
  let poligon
  const coordenadesText = document.getElementById("coordenadesParcel·la").value

  if (coordenadesText.trim()) {
    // Parsejem les coordenades del camp de text
    poligon = coordenadesText.split(";").map((pair) => {
      const [x, y] = pair.trim().split(",").map(Number)
      return [x, y]
    })
  } else if (elementEnEdicio && elementEnEdicio.poligon) {
    poligon = elementEnEdicio.poligon
  } else if (id) {
    // Mantenim el polígon existent
    const parcel·laExistent = parcel·les.find((p) => p.id === id)
    poligon = parcel·laExistent.poligon
  } else {
    alert("Si us plau, defineix el polígon de la parcel·la")
    return
  }

  const parcel·la = {
    id: id || Date.now().toString(),
    nom: document.getElementById("nomParcel·la").value,
    refCadastral: document.getElementById("refCadastralParcel·la").value,
    area: Number.parseFloat(document.getElementById("areaParcel·la").value),
    ubicació: document.getElementById("ubicacióParcel·la").value,
    tipusSòl: document.getElementById("tipusSòlParcel·la").value,
    reg: document.getElementById("regParcel·la").value,
    notes: document.getElementById("notesParcel·la").value,
    poligon: poligon,
  }

  if (id) {
    const index = parcel·les.findIndex((p) => p.id === id)
    parcel·les[index] = parcel·la
  } else {
    parcel·les.push(parcel·la)
  }

  desarDades()
  tancarModalParcel·la()
  renderitzarParcel·les()
  renderitzarTauler()
}

// ============================================
// RENDER SECTORS
// ============================================

function renderitzarSectors() {
  // Renderitzem el mapa de parcel·les (fons)
  const capaParcel·lesSectors = document.getElementById("capaParcel·lesSectors")
  if (capaParcel·lesSectors) {
    capaParcel·lesSectors.innerHTML = parcel·les
      .map((p) => {
        const punts = p.poligon.map((coord) => coord.join(",")).join(" ")
        return `
                <polygon points="${punts}" fill="rgba(231, 229, 228, 0.5)" 
                         stroke="#a8a29e" stroke-width="1" stroke-dasharray="5,5" />
            `
      })
      .join("")
  }

  // Renderitzem els sectors
  const capaSectorsLlista = document.getElementById("capaSectorsLlista")
  if (capaSectorsLlista) {
    const colors = ["#dcfce7", "#fef9c3", "#dbeafe", "#fce7f3", "#e0e7ff"]

    capaSectorsLlista.innerHTML = sectors
      .map((s, index) => {
        if (!s.poligon) return ""

        const punts = s.poligon.map((coord) => coord.join(",")).join(" ")
        const centreX = s.poligon.reduce((suma, c) => suma + c[0], 0) / s.poligon.length
        const centreY = s.poligon.reduce((suma, c) => suma + c[1], 0) / s.poligon.length
        const color = colors[index % colors.length]

        return `
                <g onclick="seleccionarSector('${s.id}')" style="cursor: pointer;">
                    <polygon points="${punts}" fill="${color}" 
                             stroke="#2d5016" stroke-width="2" />
                    <text x="${centreX}" y="${centreY}" text-anchor="middle" 
                          font-size="13" font-weight="bold" fill="#2d5016">${s.nom}</text>
                    <text x="${centreX}" y="${centreY + 18}" text-anchor="middle" 
                          font-size="10" fill="#78716c">${s.nombreFiles} files</text>
                </g>
            `
      })
      .join("")
  }

  // Renderitzem la graella de sectors
  if (sectors.length === 0) {
    document.getElementById("graellaSectors").innerHTML = `
            <div class="empty-state">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📐</div>
                <div style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--text);">No hi ha sectors</div>
                <p>Comença afegint el teu primer sector de cultiu</p>
            </div>
        `
    return
  }

  document.getElementById("graellaSectors").innerHTML = sectors
    .map((s) => {
      const varietat = varietatsFruita[s.varietat]
      const totalArbres = s.nombreFiles * (s.arbresPerFila || 0)
      const produccióEstimada = totalArbres * (varietat?.rendimentPerArbre || 0)

      let etiquetaEstat = ""
      if (s.estat === "actiu") etiquetaEstat = '<span class="badge badge-success">Actiu</span>'
      else if (s.estat === "collita") etiquetaEstat = '<span class="badge badge-warning">En collita</span>'
      else etiquetaEstat = '<span class="badge badge-info">Completat</span>'

      const parcel·lesOcupades = s.poligon ? trobarParcel·lesInterseccio(s.poligon) : []

      return `
            <div class="item-card">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                    <div>
                        <div class="item-title">${s.nom}</div>
                        <div class="item-subtitle">${varietat?.nom || "Varietat desconeguda"}</div>
                    </div>
                    ${etiquetaEstat}
                </div>
                <div class="item-info">
                    <div class="info-row">
                        <span class="info-label">Superfície:</span>
                        <span>${s.superficie} ha</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Files / Arbres:</span>
                        <span>${s.nombreFiles} files × ${s.arbresPerFila || 0} = ${totalArbres} arbres</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Producció estimada:</span>
                        <span>${(produccióEstimada / 1000).toFixed(1)} t</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Parcel·les:</span>
                        <span>${parcel·lesOcupades.map((p) => p.nom).join(", ") || "-"}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Files registrades:</span>
                        <span>${s.files?.length || 0} / ${s.nombreFiles}</span>
                    </div>
                </div>
                <div style="margin-top: 1rem;">
                    <button class="btn btn-outline btn-sm" onclick="editarSector('${s.id}')">Editar</button>
                    <button class="btn btn-outline btn-sm" onclick="gestionarFiles('${s.id}')">Files</button>
                    <button class="btn btn-outline btn-sm" onclick="eliminarSector('${s.id}')">Eliminar</button>
                </div>
            </div>
        `
    })
    .join("")
}

function seleccionarSector(idSector) {
  gestionarFiles(idSector)
}

function obrirModalSector(idSector = null) {
  const modal = document.getElementById("modalSector")
  const formulari = document.getElementById("formulariSector")
  formulari.reset()

  if (idSector) {
    const sector = sectors.find((s) => s.id === idSector)
    document.getElementById("titolModalSector").textContent = "Editar Sector de Cultiu"
    document.getElementById("idSector").value = sector.id
    document.getElementById("nomSector").value = sector.nom
    document.getElementById("varietatSector").value = sector.varietat
    document.getElementById("superficieSector").value = sector.superficie
    document.getElementById("dataPlantacioSector").value = sector.dataPlantacio || ""
    document.getElementById("nombreFilesSector").value = sector.nombreFiles
    document.getElementById("arbresPerFilaSector").value = sector.arbresPerFila || ""
    document.getElementById("estatSector").value = sector.estat
    document.getElementById("notesSector").value = sector.notes || ""

    if (sector.poligon) {
      const parcel·lesOcupades = trobarParcel·lesInterseccio(sector.poligon)
      document.getElementById("parcel·lesSector").value =
        parcel·lesOcupades.map((p) => p.nom).join(", ") || "Cap parcel·la"
    }

    elementEnEdicio = sector
  } else {
    document.getElementById("titolModalSector").textContent = "Nou Sector de Cultiu"
  }

  modal.classList.add("active")
}

function tancarModalSector() {
  document.getElementById("modalSector").classList.remove("active")
  elementEnEdicio = null
}

function editarSector(id) {
  obrirModalSector(id)
}

function eliminarSector(id) {
  if (confirm("Estàs segur que vols eliminar aquest sector?")) {
    sectors = sectors.filter((s) => s.id !== id)
    desarDades()
    renderitzarSectors()
    renderitzarTauler()
  }
}

function actualitzarInfoVarietatSector() {
  const idVarietat = document.getElementById("varietatSector").value
  const divInfo = document.getElementById("infoVarietatSector")

  if (idVarietat) {
    const varietat = varietatsFruita[idVarietat]
    divInfo.textContent = `Maduració: ${varietat.diesMaduració} dies | Rendiment: ${varietat.rendimentPerArbre} kg/arbre`
  } else {
    divInfo.textContent = ""
  }
}

function desarSector() {
  const id = document.getElementById("idSector").value

  // Obtenim el polígon de l'element en edició
  let poligon
  if (elementEnEdicio && elementEnEdicio.poligon) {
    poligon = elementEnEdicio.poligon
  } else if (id) {
    const sectorExistent = sectors.find((s) => s.id === id)
    poligon = sectorExistent.poligon
  } else {
    alert("Si us plau, dibuixa el polígon del sector al mapa")
    return
  }

  const sector = {
    id: id || Date.now().toString(),
    nom: document.getElementById("nomSector").value,
    varietat: document.getElementById("varietatSector").value,
    superficie: Number.parseFloat(document.getElementById("superficieSector").value),
    dataPlantacio: document.getElementById("dataPlantacioSector").value,
    nombreFiles: Number.parseInt(document.getElementById("nombreFilesSector").value),
    arbresPerFila: Number.parseInt(document.getElementById("arbresPerFilaSector").value) || 0,
    estat: document.getElementById("estatSector").value,
    notes: document.getElementById("notesSector").value,
    poligon: poligon,
    files: id ? sectors.find((s) => s.id === id)?.files || [] : [],
  }

  if (id) {
    const index = sectors.findIndex((s) => s.id === id)
    sectors[index] = sector
  } else {
    sectors.push(sector)
  }

  desarDades()
  tancarModalSector()
  renderitzarSectors()
  renderitzarTauler()
}

// ============================================
// GESTIÓ DE FILES D'ARBRES
// ============================================

function gestionarFiles(idSector) {
  const sector = sectors.find((s) => s.id === idSector)
  if (!sector) return

  sectorActual = sector

  const modal = document.getElementById("modalFiles")
  document.getElementById("titolModalFiles").textContent = `Files d'Arbres - ${sector.nom}`

  // Informació del sector
  const varietat = varietatsFruita[sector.varietat]
  const totalArbres = sector.nombreFiles * (sector.arbresPerFila || 0)
  document.getElementById("infoSectorFiles").innerHTML = `
        <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div>
                <div style="font-size: 0.875rem; color: var(--text-gris);">Varietat</div>
                <div style="font-weight: 600;">${varietat.nom}</div>
            </div>
            <div>
                <div style="font-size: 0.875rem; color: var(--text-gris);">Files Totals</div>
                <div style="font-weight: 600;">${sector.nombreFiles}</div>
            </div>
            <div>
                <div style="font-size: 0.875rem; color: var(--text-gris);">Arbres per Fila</div>
                <div style="font-weight: 600;">${sector.arbresPerFila || 0}</div>
            </div>
            <div>
                <div style="font-size: 0.875rem; color: var(--text-gris);">Total Arbres</div>
                <div style="font-weight: 600;">${totalArbres}</div>
            </div>
        </div>
    `

  // Dibuixem el polígon del sector
  const capaPoligon = document.getElementById("capaPoligonSectorFiles")
  if (capaPoligon && sector.poligon) {
    // Normalitzem el polígon a l'espai del mapa de files (800x600)
    const minX = Math.min(...sector.poligon.map((p) => p[0]))
    const maxX = Math.max(...sector.poligon.map((p) => p[0]))
    const minY = Math.min(...sector.poligon.map((p) => p[1]))
    const maxY = Math.max(...sector.poligon.map((p) => p[1]))

    const escalaX = 700 / (maxX - minX)
    const escalaY = 500 / (maxY - minY)
    const escala = Math.min(escalaX, escalaY) * 0.9

    const poligonNormalitzat = sector.poligon.map((p) => [50 + (p[0] - minX) * escala, 50 + (p[1] - minY) * escala])

    const punts = poligonNormalitzat.map((coord) => coord.join(",")).join(" ")
    capaPoligon.innerHTML = `
            <polygon points="${punts}" fill="rgba(74, 124, 44, 0.1)" 
                     stroke="#4a7c2c" stroke-width="2" />
        `

    // Guardem la transformació per utilitzar-la en el dibuix de files
    sectorActual.transformacio = { minX, minY, escala, offsetX: 50, offsetY: 50 }
  }

  renderitzarFilesModal(idSector)
  modal.classList.add("active")
}

function tancarModalFiles() {
  document.getElementById("modalFiles").classList.remove("active")
  sectorActual = null
  cancel·larDibuix()
}

function renderitzarFilesModal(idSector) {
  const sector = sectors.find((s) => s.id === idSector)
  if (!sector) return

  // Renderitzem les files al mapa
  const capaFiles = document.getElementById("capaFilesLlista")
  if (capaFiles && sector.files && sector.files.length > 0 && sector.transformacio) {
    const { minX, minY, escala, offsetX, offsetY } = sector.transformacio

    capaFiles.innerHTML = sector.files
      .map((fila, index) => {
        const colors = ["#ef4444", "#f97316", "#eab308", "#22c55e", "#3b82f6", "#8b5cf6", "#ec4899"]
        const color = colors[index % colors.length]

        // Normalitzem les coordenades de la fila
        const x1 = offsetX + (fila.coordenades[0][0] - minX) * escala
        const y1 = offsetY + (fila.coordenades[0][1] - minY) * escala
        const x2 = offsetX + (fila.coordenades[1][0] - minX) * escala
        const y2 = offsetY + (fila.coordenades[1][1] - minY) * escala

        return `
                <g>
                    <line x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}" 
                          stroke="${color}" stroke-width="3" />
                    <circle cx="${x1}" cy="${y1}" r="4" fill="${color}" />
                    <circle cx="${x2}" cy="${y2}" r="4" fill="${color}" />
                    <text x="${(x1 + x2) / 2}" y="${(y1 + y2) / 2 - 5}" 
                          text-anchor="middle" font-size="11" font-weight="bold" fill="${color}">
                        Fila ${fila.numero}
                    </text>
                </g>
            `
      })
      .join("")
  } else if (capaFiles) {
    capaFiles.innerHTML = ""
  }

  // Renderitzem la taula de files
  const tbody = document.getElementById("taulaFiles")
  if (!sector.files || sector.files.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="4" style="text-align: center; padding: 2rem; color: var(--text-gris);">No hi ha files registrades</td></tr>'
  } else {
    tbody.innerHTML = sector.files
      .map(
        (fila) => `
            <tr>
                <td><strong>Fila ${fila.numero}</strong></td>
                <td>${fila.arbres}</td>
                <td>${fila.longitud.toFixed(1)} m</td>
                <td>
                    <button class="btn-icon" onclick="eliminarFila('${sector.id}', '${fila.id}')" title="Eliminar">🗑️</button>
                </td>
            </tr>
        `,
      )
      .join("")
  }
}

function generarFilesAutomatiques() {
  if (!sectorActual) return

  const nombreFiles = sectorActual.nombreFiles
  const arbresPerFila = sectorActual.arbresPerFila || 0

  if (!sectorActual.poligon || sectorActual.poligon.length < 3) {
    alert("El sector necessita un polígon definit per generar files automàtiques")
    return
  }

  // Trobem els límits del polígon
  const minY = Math.min(...sectorActual.poligon.map((p) => p[1]))
  const maxY = Math.max(...sectorActual.poligon.map((p) => p[1]))
  const minX = Math.min(...sectorActual.poligon.map((p) => p[0]))
  const maxX = Math.max(...sectorActual.poligon.map((p) => p[0]))

  // Generem files horitzontals equidistants
  const distanciaFiles = (maxY - minY) / (nombreFiles + 1)
  const files = []

  for (let i = 1; i <= nombreFiles; i++) {
    const y = minY + distanciaFiles * i
    const fila = {
      id: Date.now().toString() + i,
      numero: i,
      coordenades: [
        [minX + 20, y],
        [maxX - 20, y],
      ],
      arbres: arbresPerFila,
      longitud: calcularDistancia([minX + 20, y], [maxX - 20, y]),
    }
    files.push(fila)
  }

  sectorActual.files = files
  desarDades()
  renderitzarFilesModal(sectorActual.id)
  alert(`✅ S'han generat ${nombreFiles} files automàticament`)
}

function eliminarFila(idSector, idFila) {
  const sector = sectors.find((s) => s.id === idSector)
  if (!sector) return

  sector.files = sector.files.filter((f) => f.id !== idFila)

  // Renum erem les files
  sector.files.forEach((f, index) => {
    f.numero = index + 1
  })

  desarDades()
  renderitzarFilesModal(idSector)
}

// ============================================
// ALTRES FUNCIONS (Cultius, Històric, Mapa)
// ============================================

// Aquestes funcions es mantenen igual però ara treballen amb sectors
function renderitzarCultius() {
  // Per compatibilitat, mostrem els sectors com a cultius
  renderitzarSectors()
}

function renderitzarHistoric() {
  const totalCollit = collites.reduce((suma, h) => suma + h.quantitat, 0)
  const qualitatMitjana =
    collites.length > 0
      ? (collites.filter((h) => h.qualitat === "excel·lent" || h.qualitat === "bona").length / collites.length) * 100
      : 0

  document.getElementById("estadistiquesHistoric").innerHTML = `
        <div class="stat-card">
            <div class="stat-label">Total Collit</div>
            <div class="stat-value">${(totalCollit / 1000).toFixed(1)} <span style="font-size: 1rem; color: var(--text-gris);">t</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Registres de Collita</div>
            <div class="stat-value">${collites.length}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Qualitat Mitjana</div>
            <div class="stat-value">${qualitatMitjana.toFixed(0)} <span style="font-size: 1rem; color: var(--text-gris);">%</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Sectors Productius</div>
            <div class="stat-value">${new Set(collites.map((h) => h.idCultiu)).size}</div>
        </div>
    `

  renderitzarGraficProducció()

  const tbody = document.getElementById("taulaCollites")
  if (collites.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-gris);">No hi ha registres de collita</td></tr>'
  } else {
    tbody.innerHTML = collites
      .map((h) => {
        const sector = sectors.find((s) => s.id === h.idCultiu)
        const varietat = sector ? varietatsFruita[sector.varietat] : null

        let etiquetaQualitat = ""
        if (h.qualitat === "excel·lent") etiquetaQualitat = '<span class="badge badge-success">Excel·lent</span>'
        else if (h.qualitat === "bona") etiquetaQualitat = '<span class="badge badge-info">Bona</span>'
        else if (h.qualitat === "mitjana") etiquetaQualitat = '<span class="badge badge-warning">Mitjana</span>'
        else etiquetaQualitat = '<span class="badge">Baixa</span>'

        return `
                <tr>
                    <td>${formatarData(h.data)}</td>
                    <td>${sector ? sector.nom : "-"}</td>
                    <td>${varietat ? varietat.nom : "-"}</td>
                    <td>${h.quantitat.toLocaleString()} kg</td>
                    <td>${etiquetaQualitat}</td>
                </tr>
            `
      })
      .join("")
  }

  const selectorSector = document.getElementById("idCultiuCollita")
  if (selectorSector) {
    const sectorsActius = sectors.filter((s) => s.estat !== "completat")
    selectorSector.innerHTML =
      '<option value="">Seleccionar sector</option>' +
      sectorsActius
        .map((s) => {
          const varietat = varietatsFruita[s.varietat]
          return `<option value="${s.id}">${s.nom} - ${varietat?.nom || ""}</option>`
        })
        .join("")
  }
}

function renderitzarGraficProducció() {
  const dadesMensuals = {}

  collites.forEach((h) => {
    const mes = h.data.substring(0, 7)
    dadesMensuals[mes] = (dadesMensuals[mes] || 0) + h.quantitat
  })

  const mesos = Object.keys(dadesMensuals).sort()
  const valorMaxim = Math.max(...Object.values(dadesMensuals))

  if (mesos.length === 0) {
    document.getElementById("graficProducció").innerHTML =
      '<p style="text-align: center; color: var(--text-gris); padding: 2rem;">No hi ha dades de producció</p>'
    return
  }

  const barresHtml = mesos
    .map((mes) => {
      const valor = dadesMensuals[mes]
      const alçada = (valor / valorMaxim) * 100
      return `
            <div class="chart-bar" style="height: ${alçada}%">
                <div class="chart-bar-label">${(valor / 1000).toFixed(1)}t</div>
            </div>
        `
    })
    .join("")

  const etiquetesHtml = mesos
    .map((mes) => {
      const [any, m] = mes.split("-")
      const nomsMesos = ["Gen", "Feb", "Mar", "Abr", "Mai", "Jun", "Jul", "Ago", "Set", "Oct", "Nov", "Des"]
      return `<div class="chart-label">${nomsMesos[Number.parseInt(m) - 1]} ${any}</div>`
    })
    .join("")

  document.getElementById("graficProducció").innerHTML = `
        <div class="chart-bars">${barresHtml}</div>
        <div class="chart-labels">${etiquetesHtml}</div>
    `
}

function obrirModalCollita() {
  const modal = document.getElementById("modalCollita")
  document.getElementById("formulariCollita").reset()
  document.getElementById("dataCollitaRegistre").valueAsDate = new Date()
  modal.classList.add("active")
}

function tancarModalCollita() {
  document.getElementById("modalCollita").classList.remove("active")
}

function desarCollita() {
  const collita = {
    id: Date.now().toString(),
    idCultiu: document.getElementById("idCultiuCollita").value,
    data: document.getElementById("dataCollitaRegistre").value,
    quantitat: Number.parseFloat(document.getElementById("quantitatCollita").value),
    qualitat: document.getElementById("qualitatCollita").value,
    notes: document.getElementById("notesCollita").value,
  }

  collites.push(collita)
  desarDades()
  tancarModalCollita()
  renderitzarHistoric()
  renderitzarTauler()
}

function renderitzarMapa() {
  const vista = document.getElementById("vistaCapaMapa")?.value || "parcel·les"
  const capaMapaGeneral = document.getElementById("capaMapaGeneral")

  if (!capaMapaGeneral) return

  if (vista === "parcel·les") {
    capaMapaGeneral.innerHTML = parcel·les
      .map((p) => {
        const punts = p.poligon.map((coord) => coord.join(",")).join(" ")
        const centreX = p.poligon.reduce((suma, c) => suma + c[0], 0) / p.poligon.length
        const centreY = p.poligon.reduce((suma, c) => suma + c[1], 0) / p.poligon.length

        return `
                <g onclick="seleccionarParcel·laMapa('${p.id}')" style="cursor: pointer;">
                    <polygon points="${punts}" fill="rgba(212, 165, 116, 0.3)" 
                             stroke="#8b6f47" stroke-width="2" />
                    <text x="${centreX}" y="${centreY}" text-anchor="middle" 
                          font-size="14" font-weight="bold" fill="#2d5016">${p.nom}</text>
                </g>
            `
      })
      .join("")

    document.getElementById("llegendaMapa").innerHTML = `
            <div class="legend-item">
                <div class="legend-color" style="background: rgba(212, 165, 116, 0.5); border: 2px solid #8b6f47;"></div>
                <span>Parcel·les Cadastrals</span>
            </div>
        `
  } else if (vista === "sectors") {
    const colors = ["#dcfce7", "#fef9c3", "#dbeafe", "#fce7f3", "#e0e7ff"]

    capaMapaGeneral.innerHTML = sectors
      .map((s, index) => {
        if (!s.poligon) return ""

        const punts = s.poligon.map((coord) => coord.join(",")).join(" ")
        const centreX = s.poligon.reduce((suma, c) => suma + c[0], 0) / s.poligon.length
        const centreY = s.poligon.reduce((suma, c) => suma + c[1], 0) / s.poligon.length
        const color = colors[index % colors.length]

        return `
                <g onclick="seleccionarSectorMapa('${s.id}')" style="cursor: pointer;">
                    <polygon points="${punts}" fill="${color}" 
                             stroke="#2d5016" stroke-width="2" />
                    <text x="${centreX}" y="${centreY}" text-anchor="middle" 
                          font-size="13" font-weight="bold" fill="#2d5016">${s.nom}</text>
                </g>
            `
      })
      .join("")

    document.getElementById("llegendaMapa").innerHTML = `
            <div class="legend-item">
                <div class="legend-color" style="background: #dcfce7; border: 2px solid #2d5016;"></div>
                <span>Sectors de Cultiu</span>
            </div>
        `
  }
}

function canviarCapaMapa() {
  renderitzarMapa()
}

function seleccionarParcel·laMapa(idParcel·la) {
  const parcel·la = parcel·les.find((p) => p.id === idParcel·la)
  const sectorsParcel·la = sectors.filter((s) => {
    if (!s.poligon) return false
    return poligonsInterseccionen(s.poligon, parcel·la.poligon)
  })

  const sectorsHtml =
    sectorsParcel·la.length > 0
      ? sectorsParcel·la
          .map((s) => {
            const varietat = varietatsFruita[s.varietat]
            let etiquetaEstat = ""
            if (s.estat === "actiu") etiquetaEstat = '<span class="badge badge-success">Actiu</span>'
            else if (s.estat === "collita") etiquetaEstat = '<span class="badge badge-warning">En collita</span>'
            else etiquetaEstat = '<span class="badge badge-info">Completat</span>'

            return `
            <div style="padding: 0.75rem; background: var(--fons); border-radius: 6px; margin-bottom: 0.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <strong>${s.nom}</strong>
                    ${etiquetaEstat}
                </div>
                <div style="font-size: 0.875rem; color: var(--text-gris);">
                    ${varietat?.nom || "Varietat desconeguda"} | ${s.nombreFiles} files | ${s.superficie} ha
                </div>
            </div>
        `
          })
          .join("")
      : '<p style="color: var(--text-gris);">No hi ha sectors en aquesta parcel·la</p>'

  document.getElementById("detallsParcel·laSeleccionada").innerHTML = `
        <div class="item-info">
            <div class="info-row">
                <span class="info-label">Referència cadastral:</span>
                <span>${parcel·la.refCadastral}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Superfície:</span>
                <span>${parcel·la.area} ha</span>
            </div>
            <div class="info-row">
                <span class="info-label">Ubicació:</span>
                <span>${parcel·la.ubicació || "-"}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Tipus de sòl:</span>
                <span>${parcel·la.tipusSòl}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Reg:</span>
                <span>${parcel·la.reg}</span>
            </div>
        </div>
        <div style="margin-top: 1rem;">
            <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem;">Sectors</h3>
            ${sectorsHtml}
        </div>
    `

  document.getElementById("infoParcel·laSeleccionada").style.display = "block"
}

function seleccionarSectorMapa(idSector) {
  gestionarFiles(idSector)
}

function netejarSeleccióParcel·la() {
  document.getElementById("infoParcel·laSeleccionada").style.display = "none"
}

// ============================================
// UTILITATS
// ============================================

function formatarData(dataString) {
  const data = new Date(dataString)
  const mesos = ["gen", "feb", "mar", "abr", "mai", "jun", "jul", "ago", "set", "oct", "nov", "des"]
  return `${data.getDate()} ${mesos[data.getMonth()]} ${data.getFullYear()}`
}

// ============================================
// INICIAR L'APLICACIÓ
// ============================================

window.addEventListener("DOMContentLoaded", inicialitzar)
