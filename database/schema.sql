-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 09-02-2026 a las 22:05:37
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12 

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `agrisoft`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alerta`
--

CREATE TABLE `alerta` (
  `id` int(11) NOT NULL,
  `type` enum('stock_baix','caducitat','tasca','venciment','plaga','clima','risc','altres') NOT NULL,
  `title` varchar(160) NOT NULL,
  `body` text DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `creat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `analisis`
--

CREATE TABLE `analisis` (
  `id` int(11) NOT NULL,
  `analitzat` date NOT NULL,
  `parcela_id` int(11) DEFAULT NULL,
  `sector_id` int(11) DEFAULT NULL,
  `tipus_anàlisi` enum('sol','fulla') NOT NULL,
  `resum` text DEFAULT NULL,
  `ruta_fitxer` varchar(255) DEFAULT NULL,
  `creat` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `certificacions_treballadors`
--

CREATE TABLE `certificacions_treballadors` (
  `id` int(11) NOT NULL,
  `id_treballador` int(11) NOT NULL,
  `cert_name` varchar(160) NOT NULL,
  `valid_until` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `collites`
--

CREATE TABLE `collites` (
  `id` int(11) NOT NULL,
  `parcela_id` int(11) DEFAULT NULL,
  `sector_id` int(11) DEFAULT NULL,
  `varietat_id` int(11) DEFAULT NULL,
  `any_campanya` int(11) NOT NULL,
  `recollit` date NOT NULL,
  `kg` decimal(12,2) NOT NULL,
  `grau_qualitat` varchar(50) DEFAULT NULL,
  `protocol_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `collites_v2`
--

CREATE TABLE `collites_v2` (
  `id` int(11) NOT NULL,
  `parcela_id` int(11) DEFAULT NULL,
  `sector_id` int(11) DEFAULT NULL,
  `cultiu_id` int(11) NOT NULL,
  `varietat_id` int(11) DEFAULT NULL,
  `data_collita` date NOT NULL,
  `quantitat_kg` decimal(12,2) NOT NULL,
  `qualitat` varchar(120) DEFAULT NULL,
  `humitat_pct` decimal(5,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `creat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cultius`
--

CREATE TABLE `cultius` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cultius_parceles`
--

CREATE TABLE `cultius_parceles` (
  `id` int(11) NOT NULL,
  `parcela_id` int(11) DEFAULT NULL,
  `sector_id` int(11) DEFAULT NULL,
  `cultiu_id` int(11) NOT NULL,
  `varietat_id` int(11) DEFAULT NULL,
  `start_date` date NOT NULL,
  `data_final` date DEFAULT NULL,
  `densitat_de_arbres_per_ha` decimal(10,2) DEFAULT NULL,
  `data_esperada_de_collita` date DEFAULT NULL,
  `problemes` text DEFAULT NULL,
  `rendiment_kg` decimal(12,2) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documents_treballadors`
--

CREATE TABLE `documents_treballadors` (
  `id` int(11) NOT NULL,
  `id_treballador` int(11) NOT NULL,
  `document_tipus` varchar(120) NOT NULL,
  `ruta_fitxer` varchar(255) DEFAULT NULL,
  `expire` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `files_arbres`
--

CREATE TABLE `files_arbres` (
  `id` int(11) NOT NULL,
  `sector_id` int(11) NOT NULL,
  `codi_fila` varchar(40) NOT NULL,
  `recompte_de_arbres` int(11) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `files_parceles`
--

CREATE TABLE `files_parceles` (
  `id` int(11) NOT NULL,
  `parcela_id` int(11) NOT NULL,
  `ruta_fitxer` varchar(255) NOT NULL,
  `tipus_fitxer` enum('photo','document') NOT NULL,
  `pujat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fito_productes`
--

CREATE TABLE `fito_productes` (
  `id` int(11) NOT NULL,
  `name` varchar(160) NOT NULL,
  `substancia_activa` varchar(160) DEFAULT NULL,
  `unitat` enum('l','kg','u') NOT NULL DEFAULT 'l',
  `stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock_baix` decimal(10,2) NOT NULL DEFAULT 5.00,
  `expiry_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lots`
--

CREATE TABLE `lots` (
  `id` int(11) NOT NULL,
  `collita_id` int(11) NOT NULL,
  `codi_lot` varchar(40) NOT NULL,
  `nom_client` varchar(160) DEFAULT NULL,
  `creat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `maquinaria`
--

CREATE TABLE `maquinaria` (
  `idMaquina` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `tipus` varchar(100) DEFAULT NULL,
  `matricula` varchar(20) DEFAULT NULL,
  `tipusCombustible` varchar(50) DEFAULT NULL,
  `cavalls` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `observacio_plagues`
--

CREATE TABLE `observacio_plagues` (
  `id` int(11) NOT NULL,
  `observat` date NOT NULL,
  `parcela_id` int(11) DEFAULT NULL,
  `sector_id` int(11) DEFAULT NULL,
  `nom_plaga` varchar(160) NOT NULL,
  `gravetat` enum('baixa','mitjana','alta') NOT NULL DEFAULT 'baixa',
  `notes` text DEFAULT NULL,
  `creat` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `parcela`
--

CREATE TABLE `parcela` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `gps_lat` double DEFAULT NULL,
  `gps_lng` double DEFAULT NULL,
  `area_ha` double DEFAULT NULL,
  `tipus_sòl` varchar(255) DEFAULT NULL,
  `pendent_pct` double DEFAULT NULL,
  `infraestructures` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `creat` timestamp NOT NULL DEFAULT current_timestamp(),
  `polygon_geojson` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `parcela_punt`
--

CREATE TABLE `parcela_punt` (
  `id` int(11) NOT NULL,
  `parcela_id` int(11) NOT NULL,
  `idx` int(11) NOT NULL,
  `lat` double NOT NULL,
  `lng` double NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `plans_tractament`
--

CREATE TABLE `plans_tractament` (
  `id` int(11) NOT NULL,
  `title` varchar(160) NOT NULL,
  `planned_on` date NOT NULL,
  `parcela_id` int(11) DEFAULT NULL,
  `sector_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pendent','fet','cancel·lat') NOT NULL DEFAULT 'pendent',
  `creat` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registre_hores`
--

CREATE TABLE `registre_hores` (
  `id_registre` int(11) NOT NULL,
  `idTreballador` int(11) NOT NULL,
  `data` date NOT NULL,
  `hora_inici` datetime DEFAULT NULL,
  `hora_fi` datetime DEFAULT NULL,
  `pauses` int(11) DEFAULT 0,
  `estat` enum('pendent','treballant','pausat','finalitzat') NOT NULL DEFAULT 'pendent',
  `ubicacio` varchar(150) DEFAULT NULL,
  `incidencies` text DEFAULT NULL,
  `creat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `resgistres_treball`
--

CREATE TABLE `resgistres_treball` (
  `id` int(11) NOT NULL,
  `id_treballador` int(11) NOT NULL,
  `parcela_id` int(11) DEFAULT NULL,
  `sector_id` int(11) DEFAULT NULL,
  `work_date` date NOT NULL,
  `hours` decimal(6,2) NOT NULL,
  `task` varchar(160) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sectors`
--

CREATE TABLE `sectors` (
  `id` int(11) NOT NULL,
  `parcela_id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `area_ha` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sector_cultiu`
--

CREATE TABLE `sector_cultiu` (
  `id` int(11) NOT NULL,
  `parcela_id` int(11) NOT NULL,
  `nom_sector` varchar(100) NOT NULL,
  `data_plantacio` date DEFAULT NULL,
  `marc_plantacio` varchar(100) DEFAULT NULL,
  `num_arbres` int(11) DEFAULT NULL,
  `origen_material` varchar(255) DEFAULT NULL,
  `superficie` decimal(10,2) DEFAULT NULL,
  `previsio_produccio` decimal(10,2) DEFAULT NULL,
  `sistema_formacio` varchar(100) DEFAULT NULL,
  `cultiu_id` int(11) DEFAULT NULL,
  `estat_actual` varchar(100) DEFAULT NULL,
  `inversio_inicial` decimal(12,2) DEFAULT NULL,
  `creat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tasques`
--

CREATE TABLE `tasques` (
  `id` int(11) NOT NULL,
  `title` varchar(160) NOT NULL,
  `description` text DEFAULT NULL,
  `assigned_to_id_treballador` int(11) DEFAULT NULL,
  `parcela_id` int(11) DEFAULT NULL,
  `sector_id` int(11) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('pendent','en_curs','fet') NOT NULL DEFAULT 'pendent',
  `creat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tractaments`
--

CREATE TABLE `tractaments` (
  `id` int(11) NOT NULL,
  `parcela_id` int(11) DEFAULT NULL,
  `sector_id` int(11) DEFAULT NULL,
  `fila_id` int(11) DEFAULT NULL,
  `producte_id` int(11) NOT NULL,
  `aplicat` date NOT NULL,
  `dosis_hectarea` decimal(10,2) NOT NULL,
  `dosis_total` decimal(10,2) NOT NULL,
  `temps` varchar(160) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `treballadors`
--

CREATE TABLE `treballadors` (
  `id` int(11) NOT NULL,
  `nom_complet` varchar(160) NOT NULL,
  `telefon` varchar(40) DEFAULT NULL,
  `rol_de_treball` varchar(80) DEFAULT NULL,
  `cost_hora` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuaris`
--

CREATE TABLE `usuaris` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `contrasenya_enciptada` varchar(255) NOT NULL,
  `role` enum('admin','manager','treballador') NOT NULL DEFAULT 'manager',
  `creat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuaris`
--

INSERT INTO `usuaris` (`id`, `name`, `email`, `contrasenya_enciptada`, `role`, `creat`) VALUES
(50, 'admin', 'admin@agrisoft.com', '$2y$10$JmHRsba5rUqGgDx/kOw9z.IHv1HrfJG7GnY6z2fFdxq/XzGtxpS1O', 'admin', '2026-02-09 21:02:20'),
(51, 'treballador', 'treballador@agrisoft.com', '$2y$10$DwHoPYw1ZDIDu/PAmmYfhuyPOqVuNF/o/.KK9wf1iGH7NtRNGjLO6', 'treballador', '2026-02-09 21:03:34'),
(52, 'manager', 'manager@agrisoft.com', '$2y$10$7eVmmoU9sU0/3dXx2KO1y.VlpzXiNAjY5GOkEPfINoDdP0yLkj4HS', 'manager', '2026-02-09 21:03:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `varietats`
--

CREATE TABLE `varietats` (
  `id` int(11) NOT NULL,
  `cultiu_id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `informacio_agronomica` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alerta`
--
ALTER TABLE `alerta`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `analisis`
--
ALTER TABLE `analisis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parcela_id` (`parcela_id`),
  ADD KEY `sector_id` (`sector_id`),
  ADD KEY `creat` (`creat`);

--
-- Indices de la tabla `certificacions_treballadors`
--
ALTER TABLE `certificacions_treballadors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_treballador` (`id_treballador`);

--
-- Indices de la tabla `collites`
--
ALTER TABLE `collites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parcela_id` (`parcela_id`),
  ADD KEY `sector_id` (`sector_id`),
  ADD KEY `varietat_id` (`varietat_id`);

--
-- Indices de la tabla `collites_v2`
--
ALTER TABLE `collites_v2`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parcela_id` (`parcela_id`),
  ADD KEY `sector_id` (`sector_id`),
  ADD KEY `cultiu_id` (`cultiu_id`),
  ADD KEY `varietat_id` (`varietat_id`);

--
-- Indices de la tabla `cultius`
--
ALTER TABLE `cultius`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cultius_parceles`
--
ALTER TABLE `cultius_parceles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parcela_id` (`parcela_id`),
  ADD KEY `sector_id` (`sector_id`),
  ADD KEY `cultiu_id` (`cultiu_id`),
  ADD KEY `varietat_id` (`varietat_id`);

--
-- Indices de la tabla `documents_treballadors`
--
ALTER TABLE `documents_treballadors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_treballador` (`id_treballador`);

--
-- Indices de la tabla `files_arbres`
--
ALTER TABLE `files_arbres`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sector_id` (`sector_id`);

--
-- Indices de la tabla `files_parceles`
--
ALTER TABLE `files_parceles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parcela_id` (`parcela_id`);

--
-- Indices de la tabla `fito_productes`
--
ALTER TABLE `fito_productes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `lots`
--
ALTER TABLE `lots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codi_lot` (`codi_lot`),
  ADD KEY `collita_id` (`collita_id`);

--
-- Indices de la tabla `maquinaria`
--
ALTER TABLE `maquinaria`
  ADD PRIMARY KEY (`idMaquina`);

--
-- Indices de la tabla `observacio_plagues`
--
ALTER TABLE `observacio_plagues`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parcela_id` (`parcela_id`),
  ADD KEY `sector_id` (`sector_id`),
  ADD KEY `creat` (`creat`);

--
-- Indices de la tabla `parcela`
--
ALTER TABLE `parcela`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `parcela_punt`
--
ALTER TABLE `parcela_punt`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_parcela_vertex` (`parcela_id`,`idx`);

--
-- Indices de la tabla `plans_tractament`
--
ALTER TABLE `plans_tractament`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parcela_id` (`parcela_id`),
  ADD KEY `sector_id` (`sector_id`),
  ADD KEY `creat` (`creat`);

--
-- Indices de la tabla `registre_hores`
--
ALTER TABLE `registre_hores`
  ADD PRIMARY KEY (`id_registre`),
  ADD KEY `idTreballador` (`idTreballador`);

--
-- Indices de la tabla `resgistres_treball`
--
ALTER TABLE `resgistres_treball`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_treballador` (`id_treballador`),
  ADD KEY `parcela_id` (`parcela_id`),
  ADD KEY `sector_id` (`sector_id`);

--
-- Indices de la tabla `sectors`
--
ALTER TABLE `sectors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parcela_id` (`parcela_id`);

--
-- Indices de la tabla `sector_cultiu`
--
ALTER TABLE `sector_cultiu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sector_parcela` (`parcela_id`),
  ADD KEY `idx_sector_cultiu` (`cultiu_id`);

--
-- Indices de la tabla `tasques`
--
ALTER TABLE `tasques`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_to_id_treballador` (`assigned_to_id_treballador`),
  ADD KEY `parcela_id` (`parcela_id`),
  ADD KEY `sector_id` (`sector_id`);

--
-- Indices de la tabla `tractaments`
--
ALTER TABLE `tractaments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parcela_id` (`parcela_id`),
  ADD KEY `sector_id` (`sector_id`),
  ADD KEY `fila_id` (`fila_id`),
  ADD KEY `producte_id` (`producte_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indices de la tabla `treballadors`
--
ALTER TABLE `treballadors`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuaris`
--
ALTER TABLE `usuaris`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `varietats`
--
ALTER TABLE `varietats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cultiu_id` (`cultiu_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `alerta`
--
ALTER TABLE `alerta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `analisis`
--
ALTER TABLE `analisis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `certificacions_treballadors`
--
ALTER TABLE `certificacions_treballadors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `collites`
--
ALTER TABLE `collites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `collites_v2`
--
ALTER TABLE `collites_v2`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cultius`
--
ALTER TABLE `cultius`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cultius_parceles`
--
ALTER TABLE `cultius_parceles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `documents_treballadors`
--
ALTER TABLE `documents_treballadors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `files_arbres`
--
ALTER TABLE `files_arbres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `files_parceles`
--
ALTER TABLE `files_parceles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `fito_productes`
--
ALTER TABLE `fito_productes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `lots`
--
ALTER TABLE `lots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `maquinaria`
--
ALTER TABLE `maquinaria`
  MODIFY `idMaquina` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `observacio_plagues`
--
ALTER TABLE `observacio_plagues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `parcela`
--
ALTER TABLE `parcela`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `parcela_punt`
--
ALTER TABLE `parcela_punt`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `plans_tractament`
--
ALTER TABLE `plans_tractament`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `registre_hores`
--
ALTER TABLE `registre_hores`
  MODIFY `id_registre` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `resgistres_treball`
--
ALTER TABLE `resgistres_treball`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sectors`
--
ALTER TABLE `sectors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sector_cultiu`
--
ALTER TABLE `sector_cultiu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tasques`
--
ALTER TABLE `tasques`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tractaments`
--
ALTER TABLE `tractaments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `treballadors`
--
ALTER TABLE `treballadors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuaris`
--
ALTER TABLE `usuaris`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT de la tabla `varietats`
--
ALTER TABLE `varietats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `analisis`
--
ALTER TABLE `analisis`
  ADD CONSTRAINT `analisis_ibfk_1` FOREIGN KEY (`parcela_id`) REFERENCES `parcela` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `analisis_ibfk_2` FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `analisis_ibfk_3` FOREIGN KEY (`creat`) REFERENCES `usuaris` (`id`);

--
-- Filtros para la tabla `certificacions_treballadors`
--
ALTER TABLE `certificacions_treballadors`
  ADD CONSTRAINT `certificacions_treballadors_ibfk_1` FOREIGN KEY (`id_treballador`) REFERENCES `treballadors` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `collites`
--
ALTER TABLE `collites`
  ADD CONSTRAINT `collites_ibfk_1` FOREIGN KEY (`parcela_id`) REFERENCES `parcela` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `collites_ibfk_2` FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `collites_ibfk_3` FOREIGN KEY (`varietat_id`) REFERENCES `varietats` (`id`);

--
-- Filtros para la tabla `collites_v2`
--
ALTER TABLE `collites_v2`
  ADD CONSTRAINT `collites_v2_ibfk_1` FOREIGN KEY (`parcela_id`) REFERENCES `parcela` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `collites_v2_ibfk_2` FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `collites_v2_ibfk_3` FOREIGN KEY (`cultiu_id`) REFERENCES `cultius` (`id`),
  ADD CONSTRAINT `collites_v2_ibfk_4` FOREIGN KEY (`varietat_id`) REFERENCES `varietats` (`id`);

--
-- Filtros para la tabla `cultius_parceles`
--
ALTER TABLE `cultius_parceles`
  ADD CONSTRAINT `cultius_parceles_ibfk_1` FOREIGN KEY (`parcela_id`) REFERENCES `parcela` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cultius_parceles_ibfk_2` FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cultius_parceles_ibfk_3` FOREIGN KEY (`cultiu_id`) REFERENCES `cultius` (`id`),
  ADD CONSTRAINT `cultius_parceles_ibfk_4` FOREIGN KEY (`varietat_id`) REFERENCES `varietats` (`id`);

--
-- Filtros para la tabla `documents_treballadors`
--
ALTER TABLE `documents_treballadors`
  ADD CONSTRAINT `documents_treballadors_ibfk_1` FOREIGN KEY (`id_treballador`) REFERENCES `treballadors` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `files_arbres`
--
ALTER TABLE `files_arbres`
  ADD CONSTRAINT `files_arbres_ibfk_1` FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `files_parceles`
--
ALTER TABLE `files_parceles`
  ADD CONSTRAINT `files_parceles_ibfk_1` FOREIGN KEY (`parcela_id`) REFERENCES `parcela` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `lots`
--
ALTER TABLE `lots`
  ADD CONSTRAINT `lots_ibfk_1` FOREIGN KEY (`collita_id`) REFERENCES `collites` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `observacio_plagues`
--
ALTER TABLE `observacio_plagues`
  ADD CONSTRAINT `observacio_plagues_ibfk_1` FOREIGN KEY (`parcela_id`) REFERENCES `parcela` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `observacio_plagues_ibfk_2` FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `observacio_plagues_ibfk_3` FOREIGN KEY (`creat`) REFERENCES `usuaris` (`id`);

--
-- Filtros para la tabla `parcela_punt`
--
ALTER TABLE `parcela_punt`
  ADD CONSTRAINT `fk_parcela_punt_parcela` FOREIGN KEY (`parcela_id`) REFERENCES `parcela` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `plans_tractament`
--
ALTER TABLE `plans_tractament`
  ADD CONSTRAINT `plans_tractament_ibfk_1` FOREIGN KEY (`parcela_id`) REFERENCES `parcela` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `plans_tractament_ibfk_2` FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `plans_tractament_ibfk_3` FOREIGN KEY (`creat`) REFERENCES `usuaris` (`id`);

--
-- Filtros para la tabla `registre_hores`
--
ALTER TABLE `registre_hores`
  ADD CONSTRAINT `registre_hores_ibfk_1` FOREIGN KEY (`idTreballador`) REFERENCES `treballadors` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `resgistres_treball`
--
ALTER TABLE `resgistres_treball`
  ADD CONSTRAINT `resgistres_treball_ibfk_1` FOREIGN KEY (`id_treballador`) REFERENCES `treballadors` (`id`),
  ADD CONSTRAINT `resgistres_treball_ibfk_2` FOREIGN KEY (`parcela_id`) REFERENCES `parcela` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `resgistres_treball_ibfk_3` FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `sectors`
--
ALTER TABLE `sectors`
  ADD CONSTRAINT `sectors_ibfk_1` FOREIGN KEY (`parcela_id`) REFERENCES `parcela` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `sector_cultiu`
--
ALTER TABLE `sector_cultiu`
  ADD CONSTRAINT `fk_sector_cultiu_cultiu` FOREIGN KEY (`cultiu_id`) REFERENCES `cultius` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sector_cultiu_parcela` FOREIGN KEY (`parcela_id`) REFERENCES `parcela` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tasques`
--
ALTER TABLE `tasques`
  ADD CONSTRAINT `tasques_ibfk_1` FOREIGN KEY (`assigned_to_id_treballador`) REFERENCES `treballadors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tasques_ibfk_2` FOREIGN KEY (`parcela_id`) REFERENCES `parcela` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tasques_ibfk_3` FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `tractaments`
--
ALTER TABLE `tractaments`
  ADD CONSTRAINT `tractaments_ibfk_1` FOREIGN KEY (`parcela_id`) REFERENCES `parcela` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tractaments_ibfk_2` FOREIGN KEY (`sector_id`) REFERENCES `sector_cultiu` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tractaments_ibfk_3` FOREIGN KEY (`fila_id`) REFERENCES `files_arbres` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tractaments_ibfk_4` FOREIGN KEY (`producte_id`) REFERENCES `fito_productes` (`id`),
  ADD CONSTRAINT `tractaments_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `usuaris` (`id`);

--
-- Filtros para la tabla `varietats`
--
ALTER TABLE `varietats`
  ADD CONSTRAINT `varietats_ibfk_1` FOREIGN KEY (`cultiu_id`) REFERENCES `cultius` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
