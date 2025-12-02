-- =========================================
-- MÒDUL 4 — GESTIÓ DE PERSONAL
-- =========================================

CREATE TABLE TREBALLADOR (
    idTreballador INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    fotografia VARCHAR(255),
    document_identitat VARCHAR(50) UNIQUE NOT NULL,
    data_naixement DATE,
    lloc_naixement VARCHAR(150),
    nacionalitat VARCHAR(100),
    residencia VARCHAR(150),
    telefon VARCHAR(30),
    email VARCHAR(100),
    adreca VARCHAR(255),
    contacte_emergencia VARCHAR(150),
    compte_bancari VARCHAR(34),
    categoria_professional VARCHAR(100),
    tipus_contracte VARCHAR(50),
    data_inici DATE,
    data_fi DATE,
    historial_laboral TEXT,
    formacio TEXT,
    habilitats TEXT,
    idiomes TEXT,
    num_seguretat_social VARCHAR(50),
    permis_treball VARCHAR(100)
) ENGINE=InnoDB;



CREATE TABLE DEPARTAMENTS (
    id_departament INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL
) ENGINE=InnoDB;



CREATE TABLE EQUIPS (
    id_equip INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    id_responsable INT,
    FOREIGN KEY (id_responsable) REFERENCES TREBALLADOR(idTreballador) ON DELETE SET NULL
) ENGINE=InnoDB;



CREATE TABLE DOCUMENTS_TREBALLADOR (
    id_document INT AUTO_INCREMENT PRIMARY KEY,
    idTreballador INT NOT NULL,
    tipus_document VARCHAR(100),
    fitxer VARCHAR(255),
    data_carrega DATE,
    data_caducitat DATE,
    observacions TEXT,
    FOREIGN KEY (idTreballador) REFERENCES TREBALLADOR(idTreballador) ON DELETE CASCADE
) ENGINE=InnoDB;



CREATE TABLE CERTIFICACIONS (
    id_cert INT AUTO_INCREMENT PRIMARY KEY,
    idTreballador INT NOT NULL,
    tipus_cert VARCHAR(150),
    entitat_emissora VARCHAR(150),
    data_obtencio DATE,
    data_caducitat DATE,
    document VARCHAR(255),
    FOREIGN KEY (idTreballador) REFERENCES TREBALLADOR(idTreballador) ON DELETE CASCADE
) ENGINE=InnoDB;



CREATE TABLE TASQUES (
    id_tasca INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    descripcio TEXT,
    tipus VARCHAR(100),
    zona VARCHAR(150),
    data_inici_prevista DATE,
    data_fi_prevista DATE,
    personal_requerit INT,
    qualificacions_requerides TEXT,
    equipament TEXT
) ENGINE=InnoDB;



CREATE TABLE TREBALLADORS_EQUIPS (
    idTreballador INT,
    idEquip INT,
    rol VARCHAR(100),
    PRIMARY KEY (idTreballador, idEquip),
    FOREIGN KEY (idTreballador) REFERENCES TREBALLADOR(idTreballador) ON DELETE CASCADE,
    FOREIGN KEY (idEquip) REFERENCES EQUIPS(id_equip) ON DELETE CASCADE
) ENGINE=InnoDB;



CREATE TABLE ASSIGNACIONS_TASCA (
    id_assignacio INT AUTO_INCREMENT PRIMARY KEY,
    id_tasca INT NOT NULL,
    idTreballador INT NOT NULL,
    data_assignacio DATE,
    FOREIGN KEY (id_tasca) REFERENCES TASQUES(id_tasca) ON DELETE CASCADE,
    FOREIGN KEY (idTreballador) REFERENCES TREBALLADOR(idTreballador) ON DELETE CASCADE
) ENGINE=InnoDB;



CREATE TABLE REGISTRE_HORES (
    id_registre INT AUTO_INCREMENT PRIMARY KEY,
    idTreballador INT NOT NULL,
    id_tasca INT,
    data DATE,
    hora_inici DATETIME,
    hora_fi DATETIME,
    pauses INT,
    ubicacio VARCHAR(150),
    incidencies TEXT,
    FOREIGN KEY (idTreballador) REFERENCES TREBALLADOR(idTreballador) ON DELETE CASCADE,
    FOREIGN KEY (id_tasca) REFERENCES TASQUES(id_tasca) ON DELETE SET NULL
) ENGINE=InnoDB;



CREATE TABLE VACANCES_PERMISOS (
    id_perm INT AUTO_INCREMENT PRIMARY KEY,
    idTreballador INT NOT NULL,
    tipus VARCHAR(100),
    data_inici DATE,
    data_fi DATE,
    estat VARCHAR(50),
    FOREIGN KEY (idTreballador) REFERENCES TREBALLADOR(idTreballador) ON DELETE CASCADE
) ENGINE=InnoDB;




-- =========================================
-- MÒDUL 1 — Parcel·les i Cultiu
-- =========================================

CREATE TABLE PARCELA (
    idParcela INT PRIMARY KEY,
    Nom VARCHAR(100),
    Superficie DECIMAL(10,2),
    CoordenadesGeo VARCHAR(255),
    TipusSol VARCHAR(100),
    PH DECIMAL(4,2),
    MaterialOrganic VARCHAR(100),
    Pendent VARCHAR(50),
    Orientacio VARCHAR(50),
    Infraestructures TEXT,
    Documentacio TEXT,
    EstatActual VARCHAR(100)
) ENGINE=InnoDB;



CREATE TABLE SECTOR_CULTIU (
    IdSector INT PRIMARY KEY,
    NomSector VARCHAR(100),
    DataPlantacio DATE,
    MarcPlantacio VARCHAR(100),
    NumArbres INT,
    OrigenMaterial VARCHAR(255),
    Superficie DECIMAL(10,2),
    PrevisioProduccio DECIMAL(10,2),
    SistemaFormacio VARCHAR(100),
    IdCultiu INT,
    EstatActual VARCHAR(100),
    InversioInicial DECIMAL(12,2),
    FOREIGN KEY (IdCultiu) REFERENCES PARCELA(idParcela)
) ENGINE=InnoDB;

CREATE TABLE ESPECIE (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Nom VARCHAR(100) NOT NULL,
    tipus VARCHAR(50)
) ENGINE=InnoDB;

CREATE TABLE VARIETAT (
    idVarietat INT PRIMARY KEY,
    NomComu VARCHAR(100),
    NomCientific VARCHAR(100),
    Varietat VARCHAR(100),
    NecessitatsHidriques VARCHAR(255),
    QualitatsComercials VARCHAR(255),
    Resistencies VARCHAR(255),
    RutaFoto VARCHAR(255),
    RendimentEsperat DECIMAL(10,2),
    Especie VARCHAR(100),
    ProductivitatMitjana DECIMAL(10,2),
    Pol·linitzacio VARCHAR(100),
    HoresFred INT,
    CicleVegetatiu VARCHAR(50),
    Cicle VARCHAR(50),
    IdCultiu INT,
    FOREIGN KEY (IdCultiu) REFERENCES SECTOR_CULTIU(IdSector)
) ENGINE=InnoDB;



CREATE TABLE PLANTADA (
    idPlantada INT PRIMARY KEY,
    CondicionsClimàtiques TEXT,
    Incidències TEXT,
    RendimentObtingut DECIMAL(10,2),
    DataInici DATE,
    DataFi DATE,
    idFila INT,
    idVarietat INT,
    idSector INT,
    FOREIGN KEY (idVarietat) REFERENCES VARIETAT(idVarietat),
    FOREIGN KEY (idSector) REFERENCES SECTOR_CULTIU(IdSector)
) ENGINE=InnoDB;



CREATE TABLE FILA_ARBRES (
    idFila INT PRIMARY KEY,
    NumFila INT,
    Longitud DECIMAL(10,2),
    CoordenadesGeo VARCHAR(255),
    idPlantada INT,
    idSector INT,
    FOREIGN KEY (idPlantada) REFERENCES PLANTADA(idPlantada),
    FOREIGN KEY (idSector) REFERENCES SECTOR_CULTIU(IdSector)
) ENGINE=InnoDB;



CREATE TABLE FOTO (
    idFoto INT PRIMARY KEY,
    UrlFoto VARCHAR(255),
    Data DATE,
    Descripcio TEXT,
    IdSector INT,
    FOREIGN KEY (IdSector) REFERENCES SECTOR_CULTIU(IdSector)
) ENGINE=InnoDB;



CREATE TABLE SEGUIMENT_SECTOR (
    idSeguiment INT PRIMARY KEY,
    Data DATE,
    EstatFenologic VARCHAR(255),
    Creixement TEXT,
    Incidencies TEXT,
    Intervencions TEXT,
    EstimacioCollita DECIMAL(10,2),
    idPlantada INT,
    idSector INT,
    idTreballador INT,
    FOREIGN KEY (idPlantada) REFERENCES PLANTADA(idPlantada),
    FOREIGN KEY (idSector) REFERENCES SECTOR_CULTIU(IdSector),
    FOREIGN KEY (idTreballador) REFERENCES TREBALLADOR(idTreballador)
) ENGINE=InnoDB;



CREATE TABLE CONTE (
    IdParcela INT,
    IdSector INT,
    PRIMARY KEY (IdParcela, IdSector),
    FOREIGN KEY (IdParcela) REFERENCES PARCELA(idParcela),
    FOREIGN KEY (IdSector) REFERENCES SECTOR_CULTIU(IdSector)
) ENGINE=InnoDB;




-- =========================================
-- MÒDUL 2 — Gestió agrícola
-- =========================================

CREATE TABLE PRODUCTE (
    idProducte INT PRIMARY KEY,
    nomComercial VARCHAR(100),
    tipus VARCHAR(100),
    materiaActiva VARCHAR(100),
    concentracio VARCHAR(100),
    espectreAccio VARCHAR(255),
    cultiusAutoritzats TEXT,
    dosisRecomanada VARCHAR(50),
    dosisMaxima VARCHAR(50),
    terminiSeguretat VARCHAR(50),
    classificacioTox VARCHAR(100),
    restriccions TEXT,
    compatibilitat TEXT,
    numRegistre VARCHAR(50),
    fabricant VARCHAR(100)
) ENGINE=InnoDB;



CREATE TABLE ESTOC_PRODUCTE (
    idEstoc INT PRIMARY KEY,
    idProducte INT,
    quantitatDisponible DECIMAL(10,2),
    unitatMesura VARCHAR(20),
    dataCompra DATE,
    proveidor VARCHAR(255),
    numLot VARCHAR(100),
    dataCaducitat DATE,
    ubicacioMagatzem VARCHAR(100),
    preuUnitari DECIMAL(10,2),
    FOREIGN KEY (idProducte) REFERENCES PRODUCTE(idProducte)
) ENGINE=InnoDB;



CREATE TABLE TRACTAMENT (
    idTractament INT PRIMARY KEY,
    idSector INT,
    dataAplicacio DATE,
    metodeAplicacio VARCHAR(100),
    condicionsAmbientals TEXT,
    operari VARCHAR(100),
    observacions TEXT,
    idTreballador INT,
    FOREIGN KEY (idSector) REFERENCES SECTOR_CULTIU(IdSector),
    FOREIGN KEY (idTreballador) REFERENCES TREBALLADOR(idTreballador)
) ENGINE=InnoDB;



CREATE TABLE TRACTAMENT_PRODUCTE (
    idTractament INT,
    idProducte INT,
    quantitatAplicada DECIMAL(10,2),
    concentracioUsada VARCHAR(50),
    PRIMARY KEY (idTractament, idProducte),
    FOREIGN KEY (idTractament) REFERENCES TRACTAMENT(idTractament),
    FOREIGN KEY (idProducte) REFERENCES PRODUCTE(idProducte)
) ENGINE=InnoDB;



CREATE TABLE FERTILITZACIO (
    idFertilitzacio INT PRIMARY KEY,
    IdSector INT,
    dataAplicacio DATE,
    metodeAplicacio VARCHAR(100),
    condicionsAmbientals TEXT,
    operari VARCHAR(100),
    observacions TEXT,
    idTreballador INT,
    FOREIGN KEY (IdSector) REFERENCES SECTOR_CULTIU(IdSector),
    FOREIGN KEY (idTreballador) REFERENCES TREBALLADOR(idTreballador)
) ENGINE=InnoDB;



CREATE TABLE FERTILITZACIO_PRODUCTE (
    idFertilitzacio INT,
    idProducte INT,
    quantitatAplicada DECIMAL(10,2),
    concentracioN DECIMAL(5,2),
    concentracioP DECIMAL(5,2),
    concentracioK DECIMAL(5,2),
    PRIMARY KEY (idFertilitzacio, idProducte),
    FOREIGN KEY (idFertilitzacio) REFERENCES FERTILITZACIO(idFertilitzacio),
    FOREIGN KEY (idProducte) REFERENCES PRODUCTE(idProducte)
) ENGINE=InnoDB;



CREATE TABLE SENSOR (
    idSensor INT PRIMARY KEY,
    tipus VARCHAR(100),
    ubicacio VARCHAR(100),
    IdSector INT,
    dataInstalacio DATE,
    idTreballador INT,
    FOREIGN KEY (IdSector) REFERENCES SECTOR_CULTIU(IdSector),
    FOREIGN KEY (idTreballador) REFERENCES TREBALLADOR(idTreballador)
) ENGINE=InnoDB;



CREATE TABLE LECTURA_SENSOR (
    idLectura INT PRIMARY KEY,
    idSensor INT,
    dataLectura DATE,
    valor DECIMAL(10,2),
    unitat VARCHAR(50),
    FOREIGN KEY (idSensor) REFERENCES SENSOR(idSensor)
) ENGINE=InnoDB;



CREATE TABLE ANALISI_NUTRICIONAL (
    idAnalisi INT PRIMARY KEY,
    IdParcela INT,
    tipus VARCHAR(100),
    dataAnalisi DATE,
    resultatN DECIMAL(10,2),
    resultatP DECIMAL(10,2),
    resultatK DECIMAL(10,2),
    altresResultats TEXT,
    FOREIGN KEY (IdParcela) REFERENCES PARCELA(idParcela)
) ENGINE=InnoDB;



CREATE TABLE PLANIFICACIO_TRACTAMENT (
    idPlanificacio INT PRIMARY KEY,
    idSector INT,
    dataPrevista DATE,
    plagaObjectiu VARCHAR(100),
    estatFenologic VARCHAR(100),
    tipusTractament VARCHAR(100),
    observacions TEXT,
    responsable VARCHAR(100),
    idTreballador INT,
    FOREIGN KEY (idSector) REFERENCES SECTOR_CULTIU(IdSector),
    FOREIGN KEY (idTreballador) REFERENCES TREBALLADOR(idTreballador)
) ENGINE=InnoDB;



CREATE TABLE PLANIFICACIO_FERTILITZACIO (
    idPlanificacio INT PRIMARY KEY,
    idSector INT,
    dataPrevista DATE,
    objectiu VARCHAR(100),
    tipusFertilitzacio VARCHAR(100),
    nutrientsPrevistos TEXT,
    observacions TEXT,
    responsable VARCHAR(100),
    idTreballador INT,
    FOREIGN KEY (idSector) REFERENCES SECTOR_CULTIU(IdSector),
    FOREIGN KEY (idTreballador) REFERENCES TREBALLADOR(idTreballador)
) ENGINE=InnoDB;



CREATE TABLE QUADERN_EXPLOTACIO (
    idRegistre INT PRIMARY KEY,
    IdParcela INT,
    IdSector INT,
    IdProducte INT,
    tipusAplicacio VARCHAR(100),
    plagaMalaltia VARCHAR(100),
    cultiu VARCHAR(100),
    estatFenologic VARCHAR(100),
    dosisAplicada VARCHAR(50),
    volumCaldo VARCHAR(50),
    dataAplicacio DATE,
    terminiSeguretat VARCHAR(50),
    observacions TEXT,
    responsable VARCHAR(100),
    idTreballador INT,
    FOREIGN KEY (IdParcela) REFERENCES PARCELA(idParcela),
    FOREIGN KEY (IdSector) REFERENCES SECTOR_CULTIU(IdSector),
    FOREIGN KEY (idProducte) REFERENCES PRODUCTE(idProducte),
    FOREIGN KEY (idTreballador) REFERENCES TREBALLADOR(idTreballador)
) ENGINE=InnoDB;



CREATE TABLE HERBICIDA (
    idHerbicida INT PRIMARY KEY,
    nomComercial VARCHAR(100),
    materiaActiva VARCHAR(100),
    tipusHerba VARCHAR(100),
    modeAccio VARCHAR(100),
    dosisMaxima VARCHAR(50),
    registreLegal VARCHAR(50),
    fabricant VARCHAR(100)
) ENGINE=InnoDB;



CREATE TABLE APLICACIO_HERBICIDA (
    idAplicacio INT PRIMARY KEY,
    idSector INT,
    idHerbicida INT,
    dataAplicacio DATE,
    dosisAplicada VARCHAR(50),
    condicionsAmbientals TEXT,
    temperatura VARCHAR(50),
    vent VARCHAR(50),
    observacions TEXT,
    responsable VARCHAR(100),
    idTreballador INT,
    FOREIGN KEY (idSector) REFERENCES SECTOR_CULTIU(IdSector),
    FOREIGN KEY (idHerbicida) REFERENCES HERBICIDA(idHerbicida),
    FOREIGN KEY (idTreballador) REFERENCES TREBALLADOR(idTreballador)
) ENGINE=InnoDB;




-- =========================================
-- MÒDUL 3 — Producció i Comercialització
-- =========================================

CREATE TABLE COLLITA (
    idCollita INT PRIMARY KEY,
    IdSector INT,
    idVarietat INT,
    dataInici DATE,
    dataFi DATE,
    quantitat DECIMAL(10,2),
    unitat VARCHAR(20),
    condicionsAmbientals TEXT,
    estatFenologic VARCHAR(100),
    maduresa VARCHAR(100),
    incidencies TEXT,
    observacions TEXT,
    idTreballador INT,
    FOREIGN KEY (IdSector) REFERENCES SECTOR_CULTIU(IdSector),
    FOREIGN KEY (idVarietat) REFERENCES VARIETAT(idVarietat),
    FOREIGN KEY (idTreballador) REFERENCES TREBALLADOR(idTreballador)
) ENGINE=InnoDB;



CREATE TABLE LOT_PRODUCCIO (
    idLot INT PRIMARY KEY,
    codiLot VARCHAR(100),
    idCollita INT,
    dataCreacio DATE,
    quantitat DECIMAL(10,2),
    unitat VARCHAR(20),
    estat VARCHAR(100),
    observacions TEXT,
    FOREIGN KEY (idCollita) REFERENCES COLLITA(idCollita)
) ENGINE=InnoDB;



CREATE TABLE CONTROL_QUALITAT (
    idControl INT PRIMARY KEY,
    idLot INT,
    dataControl DATE,
    calibreMin DECIMAL(5,2),
    calibreMax DECIMAL(5,2),
    colorScore DECIMAL(5,2),
    fermesaScore DECIMAL(5,2),
    percentatgeDefectes DECIMAL(5,2),
    organolepticScore DECIMAL(5,2),
    observacions TEXT,
    FOREIGN KEY (idLot) REFERENCES LOT_PRODUCCIO(idLot)
) ENGINE=InnoDB;



CREATE TABLE CLIENT (
    idClient INT PRIMARY KEY,
    nom VARCHAR(100),
    tipus VARCHAR(50),
    contacte VARCHAR(100),
    direccio VARCHAR(255),
    requisits TEXT
) ENGINE=InnoDB;



CREATE TABLE MAQUINARIA (
    idMaquina INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    tipus VARCHAR(100),
    matricula VARCHAR(20),
    tipusCombustible VARCHAR(50),
    cavalls INT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS USUARIS (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


CREATE TABLE LOT_CLIENT (
    idLot INT,
    idClient INT,
    dataAssignacio DATE,
    quantitat DECIMAL(10,2),
    unitat VARCHAR(20),
    PRIMARY KEY (idLot, idClient),
    FOREIGN KEY (idLot) REFERENCES LOT_PRODUCCIO(idLot),
    FOREIGN KEY (idClient) REFERENCES CLIENT(idClient)
) ENGINE=InnoDB;



CREATE TABLE PREVISIO_COLLITA (
    idPrevisio INT PRIMARY KEY,
    idSector INT,
    idVarietat INT,
    campanya VARCHAR(50),
    dataPrevisio DATE,
    quantitatPrevista DECIMAL(10,2),
    unitat VARCHAR(20),
    observacions TEXT,
    FOREIGN KEY (idSector) REFERENCES SECTOR_CULTIU(IdSector),
    FOREIGN KEY (idVarietat) REFERENCES VARIETAT(idVarietat)
) ENGINE=InnoDB;