SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM `treballadors`;
DELETE FROM `certificacions_treballadors`;
DELETE FROM `maquinaria`;
DELETE FROM `cultius`;
DELETE FROM `varietats`;
DELETE FROM `fito_productes`;
DELETE FROM `parcela`;
DELETE FROM `parcela_punt`;
DELETE FROM `sectors`;
DELETE FROM `tasques`;
DELETE FROM `observacio_plagues`;
DELETE FROM `plans_tractament`;
DELETE FROM `tractaments`;
DELETE FROM `alerta`;
DELETE FROM `analisis`;
DELETE FROM `collites`;
DELETE FROM `collites_v2`;
DELETE FROM `lots`;
DELETE FROM `lot_tractaments`;
DELETE FROM `registre_hores`;
DELETE FROM `resgistres_treball`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Treballadors
INSERT INTO `treballadors` (`id`, `nom_complet`, `telefon`, `rol_de_treball`, `cost_hora`) VALUES
(1, 'Joan Pagès', '600111222', 'Operari Agrícola', 12.50),
(2, 'Maria Roca', '600333444', 'Tècnica Fitosanitària', 15.00),
(3, 'Pere Coll', '600555666', 'Capatàs', 18.00),
(4, 'Anna Serra', '600777888', 'Operari Agrícola', 12.50),
(5, 'Marc Torres', '600999000', 'Tractorista', 14.00);


-- 2. Certificacions
INSERT INTO `certificacions_treballadors` (`id_treballador`, `cert_name`, `valid_until`) VALUES
(1, 'Manipulador Aliments', '2026-12-31'),
(2, 'Carnet Fitosanitari Qualificat', '2026-04-20'),
(3, 'Prevenció Riscos Laborals', '2026-03-15'),
(5, 'Carnet de Conducció Maquinària Pesada', '2027-01-10');

-- 3. Maquinaria
INSERT INTO `maquinaria` (`nom`, `tipus`, `matricula`, `tipusCombustible`, `cavalls`) VALUES
('Tractor John Deere', 'Tractor', 'E-1234-BGF', 'Dièsel', 120),
('Atomitzador Mañez', 'Atomitzador', 'E-5678-CGT', 'N/A', 0),
('Tractor Fendt', 'Tractor', 'E-9012-DHY', 'Dièsel', 150),
('Recollidora', 'Maquinària especial de collita', 'E-4567-LKJ', 'Dièsel', 80);

-- 4. Cultius & Varietats
INSERT INTO `cultius` (`id`, `name`) VALUES
(1, 'Pomera'),
(2, 'Perera'),
(3, 'Presseguer');

INSERT INTO `varietats` (`id`, `cultiu_id`, `name`, `informacio_agronomica`) VALUES
(1, 1, 'Golden Delicious', 'Collita setembre'),
(2, 1, 'Gala', 'Collita agost'),
(3, 2, 'Conference', 'Collita setembre-octubre'),
(4, 3, 'Roig d''Albesa', 'Collita juliol');

-- 5. Fito_productes
INSERT INTO `fito_productes` (`id`, `name`, `tipus`, `substancia_activa`, `unitat`, `stock`, `stock_baix`, `dosi_maxima`, `expiry_date`) VALUES
(1, 'Cobre Azul', 'fitosanitari', 'Oxiclorur de coure', 'kg', 50.00, 20.00, 3.00, '2028-12-31'),
(2, 'Insecticida Total', 'fitosanitari', 'Clorpirifòs', 'l', 5.00, 10.00, 1.50, '2026-04-25'),
(3, 'Fertilitzant NPK', 'fertilitzant', 'Nitrogen-Fòsfor-Potassi', 'kg', 2.00, 50.00, 10.00, '2027-06-15'),
(4, 'Fungicida Top', 'fitosanitari', 'Tebuconazol', 'l', 100.00, 15.00, 2.00, '2025-11-20'),
(5, 'Acaricida Max', 'fitosanitari', 'Abamectina', 'l', 25.00, 5.00, 1.00, '2029-01-01');

-- 6. Parceles
INSERT INTO `parcela` (`id`, `name`, `area_ha`, `tipus_sòl`, `gps_lat`, `gps_lng`) VALUES
(1, 'Finca La Volta', 12.5, 'Argilós', 41.6167, 0.6167),
(2, 'Tros del Riu', 8.2, 'Llimós', 41.6200, 0.6300),
(3, 'Camp de dalt', 5.0, 'Calcari', 41.6300, 0.6400);

-- 7. Sectors
INSERT INTO `sectors` (`id`, `parcela_id`, `nom`, `cultiu_id`, `varietat`, `superficie`, `num_arbres`, `data_plantacio`) VALUES
(1, 1, 'Sector Nord', 1, 'Golden Delicious', 6.0, 1500, '2015-02-15'),
(2, 1, 'Sector Sud', 1, 'Gala', 6.5, 1600, '2018-03-10'),
(3, 2, 'Sector Riu 1', 2, 'Conference', 8.2, 2000, '2012-11-20'),
(4, 3, 'Sector Muntanya', 3, 'Roig d''Albesa', 5.0, 1200, '2020-01-15');

-- 8. Tasques i Reporting extens
INSERT INTO `tasques` (`id`, `title`, `description`, `assigned_to_id_treballador`, `parcela_id`, `sector_id`, `due_date`, `status`, `creat`) VALUES
(1, 'Poda en verd', 'Podar branques xucladores', 1, 1, 1, '2026-04-10', 'pendent', '2026-04-01 08:00:00'),
(2, 'Aplicar coure', 'Tractament preventiu per fongs', 2, 2, 3, '2026-04-17', 'en_curs', '2026-04-15 09:30:00'),
(3, 'Revisió reg', 'Comprovar goters', 3, 3, 4, '2026-04-25', 'pendent', '2026-04-16 10:00:00'),
(4, 'Recollida pales i restes', 'Netejar el camp', 1, 1, 2, '2026-03-30', 'fet', '2026-03-25 11:00:00'),
(5, 'Desbrossar marges', 'Tallar la mala herba dels voltants', 4, 1, 1, '2026-04-05', 'fet', '2026-04-01 09:00:00'),
(6, 'Neteja filtre estany', 'Neteja intensiva', 5, 2, 3, '2026-04-14', 'fet', '2026-04-12 08:00:00'),
(7, 'Substitució aspersors', 'Canviar les peces danyades', 3, 3, 4, '2026-04-02', 'fet', '2026-03-28 10:00:00'),
(8, 'Reparació tractor', 'Revisar bomba gasoil', 5, NULL, NULL, '2026-04-16', 'en_curs', '2026-04-15 15:00:00');

-- 9. Observacio Plagues
INSERT INTO `observacio_plagues` (`observat`, `parcela_id`, `sector_id`, `nom_plaga`, `gravetat`, `notes`, `creat`) VALUES
('2026-04-12', 1, 1, 'Pugó de la pomera', 'alta', 'Molts brots afectats al sector nord', 50),
('2026-04-15', 2, 3, 'Carpocapsa', 'mitjana', 'Es comencen a veure algunes pomes picades', 50),
('2026-03-20', 3, 4, 'Mosca de la fruita', 'baixa', 'Captures puntuals a les trampes cromàtiques', 50);

-- 10. Plans Tractament (⚠️ Molts plans endarrerits/fora de termini)
INSERT INTO `plans_tractament` (`id`, `title`, `planned_on`, `parcela_id`, `sector_id`, `notes`, `status`, `creat`) VALUES
(1, 'Tractament Pugó d''Urgència', '2026-04-18', 1, 1, 'Aplicar insecticida autoritzat urgent per gravetat alta', 'pendent', 50),
(2, 'Abonat de fons previst per primavera', '2026-05-01', 2, 3, '200kg/ha NPK de cobertera', 'pendent', 50),
(3, 'Aplicació de sofre preventiva', '2026-03-25', 1, 2, '⚠️ FORA DE TERMINI! S''havia de fer a finals de març.', 'pendent', 50),
(4, 'Tractament acaricida a la pera', '2026-04-05', 2, 3, '⚠️ Endarrerit 2 setmanes. Alt risc d''aranya.', 'pendent', 50),
(5, 'Desinfecció d''eines i caixes', '2026-04-10', 3, 4, '⚠️ FORA DE TERMINI.', 'pendent', 50);

-- 11. Tractaments realitzats (Reporting complet i Traçabilitat 2025/2026)
INSERT INTO `tractaments` (`id`, `parcela_id`, `sector_id`, `producte_id`, `operari_id`, `aplicat`, `dosis_hectarea`, `dosis_total`, `created_by`) VALUES
-- Tractaments recents 2026
(1, 1, 2, 1, 2, '2026-04-05', 2.50, 16.25, 50),
(2, 2, 3, 3, 3, '2026-04-10', 10.00, 82.00, 50),
(3, 3, 4, 1, 2, '2026-04-12', 2.00, 10.00, 50),
-- Tractaments 2025 (Crítics per a la TRAÇABILITAT de lots collits 2025)
(4, 1, 1, 1, 2, '2025-05-15', 2.50, 15.00, 50),
(5, 1, 1, 4, 2, '2025-06-20', 1.00, 6.00, 50),
(6, 1, 2, 1, 2, '2025-05-18', 2.50, 16.25, 50),
(7, 2, 3, 2, 3, '2025-07-05', 1.50, 12.30, 50);

-- 12. Alertes
INSERT INTO `alerta` (`type`, `title`, `body`, `is_read`, `creat`) VALUES
('stock_baix', 'Estoc baix: Insecticida Total', 'L''estoc actual és de 5.00 l, inferior al mínim establert de 10.00 l', 0, '2026-04-16 10:00:00'),
('caducitat', 'Producte caducat: Fungicida Top', 'El fitosanitari Fungicida Top va caducar el 2025-11-20', 0, '2026-04-16 10:05:00'),
('venciment', 'Certificat gairebé caducat: Carnet Fito (Maria Roca)', 'El certificat caduca el proper 2026-04-20', 0, '2026-04-16 10:10:00'),
('tasca', 'Tasca fora de termini (Poda)', 'La tasca Poda en verd assignada a Joan Pagès no s''ha completat', 0, '2026-04-16 10:15:00'),
('risc', 'Plans de tractament fora de termini (RISC ALT)', 'Hi ha múltiples plans pendents assignats del mes passat que posen la collita en risc!', 0, '2026-04-17 08:00:00'),
('plaga', 'Alerta de Plaga: Pugó de la pomera', 'Gravetat alta a Sector Nord detectada recentment.', 1, '2026-04-12 14:00:00');

-- 13. Analisis
INSERT INTO `analisis` (`analitzat`, `parcela_id`, `sector_id`, `tipus_anàlisi`, `resum`, `creat`) VALUES
('2026-02-15', 1, NULL, 'sol', 'Mancança de magnesi, pH 7.8', 50),
('2026-03-10', 2, 3, 'fulla', 'Nivells correctes de NPK', 50);

-- ==========================================
-- COLLITES I TRAÇABILITAT OMPLERT 
-- ==========================================

-- 14. Collites & Collites V2 
-- Cal omplir "collites" perquè la taula LOTS hi té una foreign key cap a collites(id).
INSERT INTO `collites` (`id`, `parcela_id`, `sector_id`, `varietat_id`, `varietat_text`, `any_campanya`, `recollit`, `kg`, `grau_qualitat`, `protocol_notes`) VALUES
(1, 1, 1, 1, 'Golden Delicious', 2025, '2025-09-15', 45000.00, 'Primera', 'Collita matinal en perfecte estat'),
(2, 1, 2, 2, 'Gala', 2025, '2025-08-20', 40000.00, 'Segona', 'Calibre una mica just per manca de pluja'),
(3, 2, 3, 3, 'Conference', 2025, '2025-10-05', 38000.00, 'Primera', 'Estat sanitari excel·lent'),
(4, 3, 4, 4, 'Roig d''Albesa', 2025, '2025-07-25', 18000.00, 'Estàndard', 'S''avança la collita per onada de calor');

INSERT INTO `collites_v2` (`id`, `parcela_id`, `sector_id`, `cultiu_id`, `varietat_id`, `data_collita`, `quantitat_kg`, `qualitat`) VALUES
(1, 1, 1, 1, 1, '2025-09-15', 45000.00, 'Primera'),
(2, 1, 2, 1, 2, '2025-08-20', 40000.00, 'Segona'),
(3, 2, 3, 2, 3, '2025-10-05', 38000.00, 'Primera'),
(4, 3, 4, 3, 4, '2025-07-25', 18000.00, 'Estàndard');

-- 15. Lots (Entrades directes de la fruita que va al magatzem per la Traçabilitat)
-- fk_lots_collita s'enllaça a collita_id
INSERT INTO `lots` (`id`, `codi_lot`, `parcela_id`, `sector_id`, `collita_id`, `data_collita`, `quantitat`, `qualitat`, `observacions`) VALUES
(1, 'LOT-POM-GLD-25-01', 1, 1, 1, '2025-09-15', 20000.00, 'Primera', 'Poma de primera recollida del matí'),
(2, 'LOT-POM-GLD-25-02', 1, 1, 1, '2025-09-16', 25000.00, 'Primera', 'Segon lot de Golden, excel·lent mida i sucre'),
(3, 'LOT-POM-GAL-25-01', 1, 2, 2, '2025-08-20', 40000.00, 'Segona', 'Gala de la campanya 25. Algunes petites per manca de pluja'),
(4, 'LOT-PER-CON-25-01', 2, 3, 3, '2025-10-05', 38000.00, 'Primera', 'Pera conference enviada a cambra directa');

-- 16. Lot_Tractaments (El nucli de la Traçabilitat)
INSERT INTO `lot_tractaments` (`lot_id`, `tractament_id`) VALUES
(1, 4), 
(1, 5), 
(2, 4), 
(2, 5), 
(3, 6), 
(4, 7); 


-- 17. Registres d'Hores (Volum ampli d'hores treballades per al reporting)
INSERT INTO `registre_hores` (`id_registre`, `idTreballador`, `data`, `hora_inici`, `hora_fi`, `estat`) VALUES
(1, 1, '2026-04-12', '2026-04-12 08:00:00', '2026-04-12 16:00:00', 'finalitzat'),
(2, 2, '2026-04-12', '2026-04-12 07:30:00', '2026-04-12 15:30:00', 'finalitzat'),
(3, 4, '2026-04-13', '2026-04-13 08:00:00', '2026-04-13 14:00:00', 'finalitzat'),
(4, 5, '2026-04-14', '2026-04-14 06:00:00', '2026-04-14 14:00:00', 'finalitzat'),
(5, 3, '2026-04-15', '2026-04-15 08:00:00', '2026-04-15 17:00:00', 'finalitzat'),
(6, 1, '2026-04-16', '2026-04-16 08:00:00', '2026-04-16 14:00:00', 'finalitzat'),
(7, 2, '2026-04-16', '2026-04-16 07:30:00', '2026-04-16 15:30:00', 'finalitzat'),
(8, 1, '2026-04-17', '2026-04-17 08:00:00', NULL, 'treballant');

-- 18. Registres de Treball diari al camp (Reporting)
INSERT INTO `resgistres_treball` (`id_treballador`, `parcela_id`, `sector_id`, `work_date`, `hours`, `task`) VALUES
(1, 1, 1, '2026-04-12', 8.00, 'Revisió i poda del sector nord'),
(2, 3, 4, '2026-04-12', 8.00, 'Tractament i revisió de plagues general'),
(4, 1, 2, '2026-04-13', 6.00, 'Col·locació de trampes'),
(5, 2, 3, '2026-04-14', 8.00, 'Passar trituradora de camp al sector Riu'),
(3, 1, 1, '2026-04-15', 8.00, 'Coordinació i supervisió'),
(1, 1, 1, '2026-04-16', 6.00, 'Poda de manteniment sector nord'),
(2, 2, 3, '2026-04-16', 8.00, 'Aplicació de tractament preventiu'),
(4, 3, 4, '2026-04-15', 7.00, 'Reparació general del reg per degoteig');
