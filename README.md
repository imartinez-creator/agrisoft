# AGRISOFT

**Agrisoft** és una plataforma web integral dissenyada per a la gestió, planificació i seguiment d'explotacions agrícoles. Desenvolupada amb una arquitectura basada en PHP i MySQL, la present solució tecnològica possibilita la digitalització objectiva de la unitat productiva, garantint de forma rigorosa la traçabilitat agroalimentària, el compliment normatiu i la facilitació de futures auditories de qualitat.

Aquest sistema s'ha concebut seguint els estàndards actuals de desenvolupament orientat a la logística i l'administració agrària.

## Especificacions Funcionals

- **Mòdul de Control Geogràfic (GIS):** Entorn cartogràfic basat en Leaflet. Permet l'assignació espacial de parcel·les mitjançant coordenades (Latitud / Longitud) i delimita poligonals dinàmiques per al reconeixement de superfícies per hectàrea.
- **Gestió de la Classificació Productiva:** Segmentació jeràrquica a través de Parcel·les i Sectors. Cadascun disposa d’assignacions individuals per Varietats i Cultius per facilitar mapes de rendiment separats.
- **Inventari d'Equipament (Maquinària):** Monitoratge tècnic del parc mòbil (especificacions tècniques i controls obligatoris com a revisions/ITV).
- **Quadre de Personal i Registre Horari:** Llibre de registre per al control d'hores, gestió de jornades treballades per empleat i assignació de responsabilitats. 
- **Control Integrat de Plagues (Sanitat Vegetal):** Panell de supervisió per al registre d'incidències, avaluació in situ de gravetat i planificació d'actuació ràpida.
- **Registre Oficial Fitosanitari:** Generació del llibre del camp per control administratiu de tot el procés: assignació del Lot tractat, mètodes i franges temporals aplicades, unitats de producte i còmput de terminis de seguretat pertinents segons la legislació vigent.
- **Tancament Cíclic i Generació d'Informes:** Emissió d'estadístiques o documents en format imprès (PDF) utilitzant el control de dades relacionals dels mòduls d'explotació.
- **Sistema de Controls d'Accés:** Arquitectura protegida amb rutes d'autenticació de sessió, bloquejant atacs de tipus CSRF, i separant entorns visuals segons si l'usuari disposa de capacitats d'escriptura o simplement en mode de consulta.

## Aspectes Tècnics

* **Llenguatge de Backend:** PHP 8+ amb models estructurats procedimentalment mitjançant una capa central d'accés a dades (`PDO`) totalment preparada contra injecció SQL.
* **Sistema Gestor de Base de Dades:** MariaDB / MySQL. El conjunt respecta les formes normals base de relació de taules (amb els seus Foreign Keys i esborrats parametritzats en cascada contemplats a `schema.sql`).
* **Frontend:** HTML5, CSS seguint pautes pures, unificat amb Javascript ES6 (inclou implementacions de Leaflet per als mapes de geoposicionament bidimensional).

## Instruccions de Desplegament Global

1. Transferir els arxius empaquetats de l'aplicatiu a la ruta directori assignada per un servidor de tipus Apache o equivalent (per exemple: `htdocs/agrisoft`).
2. Configurar la connexió del servei de MySQL des d'un entorn de gestió i crear una nova entitat anomenada `agrisoft`.
3. Executar o importar el fitxer de construcció de la base de dades localitzat a `/database/schema.sql`. Això generarà taules estructurades correctament.
4. Definir, si s'escau, modificacions als paràmetres relatius al `db.php` dins de `/app/config/`.
5. Accediu per la ruta principal des d'un client de navegador per procedir a l'inici d'activitat.

## Criteris i Convencions del Codi

En la versió entregada, el codi s'ha examinat sobre estrictes criteris d'unificació de documentació i rendiment:
- La totalitat de les iteracions complexes, validacions i consultes es troben àmpliament comentades pas a pas en idioma local (Català).
- Manteniment del `Clean Code`: Prevenció d'errors de variables no definides i establiment de validacions formals internes per a evitar trencaments (Warnings per nulls o crides externes incorrectes).
- Estandardització en l'anomenament de les columnes del projecte a llenguatge comprensible dins les capacitats.
