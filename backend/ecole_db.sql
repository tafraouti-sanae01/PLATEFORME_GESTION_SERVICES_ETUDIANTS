-- phpMyAdmin SQL Dump
-- Base de données : `ecole_db`
-- Structure ENSA Tétouan
-- 
-- IMPORTANT: Structure selon l'ENSA Tétouan (5 ans)
-- - 2AP1 : Première année classe préparatoire (S1, S2)
-- - 2AP2 : Deuxième année classe préparatoire (S3, S4)
-- - Génie Informatique 1 : Première année cycle d'ingénieurs (S5, S6)
-- - Génie Informatique 2 : Deuxième année cycle d'ingénieurs (S7, S8)
-- - Génie Informatique 3 : Troisième année cycle d'ingénieurs (S9, S10) - S10 contient le PFE
--
-- Parcours possibles :
-- 1. 2AP1 (S1-S2) → 2AP2 (S3-S4) → Génie Informatique 1 (S5-S6) → Génie Informatique 2 (S7-S8) → Génie Informatique 3 (S9-S10)
-- 2. Admission directe en cycle d'ingénieurs (Génie Informatique 1 - S5-S6)

-- Utiliser la base de données (décommentez si nécessaire)
-- USE `ecole_db`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Désactiver temporairement la vérification des clés étrangères
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Supprimer les tables existantes (dans l'ordre inverse des dépendances)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `releves_notes`;
DROP TABLE IF EXISTS `reclamations`;
DROP TABLE IF EXISTS `module_prof`;
DROP TABLE IF EXISTS `module_filiere`;
DROP TABLE IF EXISTS `inscrit_module`;
DROP TABLE IF EXISTS `inscription_etudiant`;
DROP TABLE IF EXISTS `conventions_stage`;
DROP TABLE IF EXISTS `attestations_reussite`;
DROP TABLE IF EXISTS `demandes`;
DROP TABLE IF EXISTS `etudiants`;
DROP TABLE IF EXISTS `professeur`;
DROP TABLE IF EXISTS `module`;
DROP TABLE IF EXISTS `filiere`;
DROP TABLE IF EXISTS `annee_universitaire`;
DROP TABLE IF EXISTS `administrateurs`;

-- Réactiver la vérification des clés étrangères
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Création des tables (dans l'ordre des dépendances)
-- --------------------------------------------------------

--
-- Structure de la table `administrateurs`
--

CREATE TABLE `administrateurs` (
  `id_administrateur` varchar(10) NOT NULL,
  `email` varchar(100) NOT NULL,
  `login` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Structure de la table `annee_universitaire`
--

CREATE TABLE `annee_universitaire` (
  `id_annee` varchar(10) NOT NULL,
  `annee_debut` int(11) NOT NULL,
  `annee_fin` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Structure de la table `etudiants`
--

CREATE TABLE `etudiants` (
  `id_etudiant` varchar(10) NOT NULL,
  `cin` varchar(20) NOT NULL,
  `numero_apogee` varchar(20) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `date_naissance` date DEFAULT NULL,
  `lieu_naissance` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
  -- NOTE: niveau_scolaire a été supprimé car il peut être déduit de la filière actuelle
  -- dans inscription_etudiant (2AP1=1ère année, 2AP2=2ème année, GI1=3ème année, etc.)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Structure de la table `filiere`
--

CREATE TABLE `filiere` (
  `id_filiere` varchar(10) NOT NULL,
  `nom_filiere` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Structure de la table `module`
--

CREATE TABLE `module` (
  `id_module` varchar(10) NOT NULL,
  `nom_module` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Structure de la table `professeur`
--

CREATE TABLE `professeur` (
  `id_prof` varchar(10) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telephone` varchar(15) DEFAULT NULL,
  `departement` varchar(50) DEFAULT NULL,
  `est_encadrant` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Structure de la table `demandes`
--

CREATE TABLE `demandes` (
  `id_demande` varchar(10) NOT NULL,
  `numero_reference` varchar(50) NOT NULL,
  `type_document` enum('conventions_stage','attestations_reussite','releves_notes','attestations_scolarite') NOT NULL,
  `statut` enum('en attente','traite','refuse') DEFAULT 'en attente',
  `date_demande` date NOT NULL,
  `id_etudiant` varchar(10) NOT NULL,
  `id_administrateur` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Structure de la table `attestations_reussite`
--

CREATE TABLE `attestations_reussite` (
  `id_attestation` varchar(10) NOT NULL,
  `annee_universitaire` varchar(10) DEFAULT NULL,
  `id_demande` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Structure de la table `conventions_stage`
--

CREATE TABLE `conventions_stage` (
  `id_convention` varchar(10) NOT NULL,
  `sujet_stage` varchar(255) DEFAULT NULL,
  `date_fin_stage` date DEFAULT NULL,
  `date_debut_stage` date DEFAULT NULL,
  `nom_entreprise` varchar(100) DEFAULT NULL,
  `email_responsable_entreprise` varchar(100) DEFAULT NULL,
  `nom_responsable_entreprise` varchar(100) DEFAULT NULL,
  `telephone_responsable_entreprise` varchar(100) DEFAULT NULL,
  `adresse_entreprise` varchar(100) DEFAULT NULL,
  `id_demande` varchar(10) NOT NULL,
  `id_prof_encadrant` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Structure de la table `releves_notes`
--

CREATE TABLE `releves_notes` (
  `id_releve` varchar(10) NOT NULL,
  `annee_universitaire` varchar(10) DEFAULT NULL,
  `semestre` varchar(20) DEFAULT NULL,
  `id_demande` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Structure de la table `inscription_etudiant`
--

CREATE TABLE `inscription_etudiant` (
  `id_etudiant` varchar(10) NOT NULL,
  `id_filiere` varchar(10) NOT NULL,
  `id_annee` varchar(10) NOT NULL
  -- NOTE: moyenne, mention et est_admis ont été supprimés car ils peuvent être calculés dynamiquement
  -- à partir des notes dans inscrit_module
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Structure de la table `inscrit_module`
--

CREATE TABLE `inscrit_module` (
  `id_etudiant` varchar(10) NOT NULL,
  `id_module` varchar(10) NOT NULL,
  `session` varchar(20) NOT NULL,
  `note` decimal(4,2) DEFAULT NULL
  -- NOTE: est_valide a été supprimé car il peut être calculé dynamiquement
  -- à partir de la note et de la filière (seuil: 10/20 pour 2AP1/2AP2, 12/20 pour Génie Informatique)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Structure de la table `module_filiere`
--

CREATE TABLE `module_filiere` (
  `id_filiere` varchar(10) NOT NULL,
  `id_module` varchar(10) NOT NULL,
  `semestre` int(2) DEFAULT NULL COMMENT 'Semestre (1 à 10) où le module est enseigné'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Structure de la table `module_prof`
--

CREATE TABLE `module_prof` (
  `id_prof` varchar(10) NOT NULL,
  `id_module` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Structure de la table `reclamations`
--

CREATE TABLE `reclamations` (
  `id_reclamation` varchar(10) NOT NULL,
  `numero_reference` varchar(50) NOT NULL,
  `date_reclamation` date NOT NULL,
  `description` text DEFAULT NULL,
  `objet` varchar(100) DEFAULT NULL,
  `statut` enum('en attente','resolu') DEFAULT 'en attente',
  `reponse` text DEFAULT NULL,
  `date_reponse` datetime DEFAULT NULL,
  `id_etudiant` varchar(10) NOT NULL,
  `id_administrateur` varchar(10) DEFAULT NULL,
  `id_demande` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Index pour les tables
-- --------------------------------------------------------

ALTER TABLE `administrateurs`
  ADD PRIMARY KEY (`id_administrateur`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `login` (`login`);

ALTER TABLE `annee_universitaire`
  ADD PRIMARY KEY (`id_annee`);

ALTER TABLE `attestations_reussite`
  ADD PRIMARY KEY (`id_attestation`),
  ADD UNIQUE KEY `id_demande` (`id_demande`);

ALTER TABLE `conventions_stage`
  ADD PRIMARY KEY (`id_convention`),
  ADD UNIQUE KEY `id_demande` (`id_demande`),
  ADD KEY `id_prof_encadrant` (`id_prof_encadrant`);

ALTER TABLE `demandes`
  ADD PRIMARY KEY (`id_demande`),
  ADD UNIQUE KEY `numero_reference` (`numero_reference`),
  ADD KEY `id_etudiant` (`id_etudiant`),
  ADD KEY `id_administrateur` (`id_administrateur`);

ALTER TABLE `etudiants`
  ADD PRIMARY KEY (`id_etudiant`),
  ADD UNIQUE KEY `cin` (`cin`),
  ADD UNIQUE KEY `numero_apogee` (`numero_apogee`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `filiere`
  ADD PRIMARY KEY (`id_filiere`);

ALTER TABLE `inscription_etudiant`
  ADD PRIMARY KEY (`id_etudiant`,`id_filiere`,`id_annee`),
  ADD KEY `id_filiere` (`id_filiere`),
  ADD KEY `id_annee` (`id_annee`);

ALTER TABLE `inscrit_module`
  ADD PRIMARY KEY (`id_etudiant`,`id_module`,`session`),
  ADD KEY `id_module` (`id_module`);

ALTER TABLE `module`
  ADD PRIMARY KEY (`id_module`);

ALTER TABLE `module_filiere`
  ADD PRIMARY KEY (`id_filiere`,`id_module`),
  ADD KEY `id_module` (`id_module`);

ALTER TABLE `module_prof`
  ADD PRIMARY KEY (`id_prof`,`id_module`),
  ADD KEY `id_module` (`id_module`);

ALTER TABLE `professeur`
  ADD PRIMARY KEY (`id_prof`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `reclamations`
  ADD PRIMARY KEY (`id_reclamation`),
  ADD UNIQUE KEY `numero_reference` (`numero_reference`),
  ADD KEY `id_demande` (`id_demande`),
  ADD KEY `id_etudiant` (`id_etudiant`),
  ADD KEY `id_administrateur` (`id_administrateur`);

ALTER TABLE `releves_notes`
  ADD PRIMARY KEY (`id_releve`),
  ADD UNIQUE KEY `id_demande` (`id_demande`);

-- --------------------------------------------------------
-- Contraintes pour les tables
-- --------------------------------------------------------

ALTER TABLE `attestations_reussite`
  ADD CONSTRAINT `attestations_reussite_ibfk_1` FOREIGN KEY (`id_demande`) REFERENCES `demandes` (`id_demande`) ON DELETE CASCADE;

ALTER TABLE `conventions_stage`
  ADD CONSTRAINT `conventions_stage_ibfk_1` FOREIGN KEY (`id_demande`) REFERENCES `demandes` (`id_demande`) ON DELETE CASCADE,
  ADD CONSTRAINT `conventions_stage_ibfk_2` FOREIGN KEY (`id_prof_encadrant`) REFERENCES `professeur` (`id_prof`) ON DELETE CASCADE;

ALTER TABLE `demandes`
  ADD CONSTRAINT `demandes_ibfk_1` FOREIGN KEY (`id_etudiant`) REFERENCES `etudiants` (`id_etudiant`) ON DELETE CASCADE,
  ADD CONSTRAINT `demandes_ibfk_2` FOREIGN KEY (`id_administrateur`) REFERENCES `administrateurs` (`id_administrateur`) ON DELETE SET NULL;

ALTER TABLE `inscription_etudiant`
  ADD CONSTRAINT `inscription_etudiant_ibfk_1` FOREIGN KEY (`id_etudiant`) REFERENCES `etudiants` (`id_etudiant`) ON DELETE CASCADE,
  ADD CONSTRAINT `inscription_etudiant_ibfk_2` FOREIGN KEY (`id_filiere`) REFERENCES `filiere` (`id_filiere`) ON DELETE CASCADE,
  ADD CONSTRAINT `inscription_etudiant_ibfk_3` FOREIGN KEY (`id_annee`) REFERENCES `annee_universitaire` (`id_annee`);

ALTER TABLE `inscrit_module`
  ADD CONSTRAINT `inscrit_module_ibfk_1` FOREIGN KEY (`id_etudiant`) REFERENCES `etudiants` (`id_etudiant`) ON DELETE CASCADE,
  ADD CONSTRAINT `inscrit_module_ibfk_2` FOREIGN KEY (`id_module`) REFERENCES `module` (`id_module`) ON DELETE CASCADE;

ALTER TABLE `module_filiere`
  ADD CONSTRAINT `module_filiere_ibfk_1` FOREIGN KEY (`id_filiere`) REFERENCES `filiere` (`id_filiere`) ON DELETE CASCADE,
  ADD CONSTRAINT `module_filiere_ibfk_2` FOREIGN KEY (`id_module`) REFERENCES `module` (`id_module`) ON DELETE CASCADE;

ALTER TABLE `module_prof`
  ADD CONSTRAINT `module_prof_ibfk_1` FOREIGN KEY (`id_prof`) REFERENCES `professeur` (`id_prof`) ON DELETE CASCADE,
  ADD CONSTRAINT `module_prof_ibfk_2` FOREIGN KEY (`id_module`) REFERENCES `module` (`id_module`) ON DELETE CASCADE;

ALTER TABLE `reclamations`
  ADD CONSTRAINT `reclamations_ibfk_1` FOREIGN KEY (`id_demande`) REFERENCES `demandes` (`id_demande`) ON DELETE CASCADE,
  ADD CONSTRAINT `reclamations_ibfk_2` FOREIGN KEY (`id_etudiant`) REFERENCES `etudiants` (`id_etudiant`) ON DELETE CASCADE,
  ADD CONSTRAINT `reclamations_ibfk_3` FOREIGN KEY (`id_administrateur`) REFERENCES `administrateurs` (`id_administrateur`) ON DELETE SET NULL;

ALTER TABLE `releves_notes`
  ADD CONSTRAINT `releves_notes_ibfk_1` FOREIGN KEY (`id_demande`) REFERENCES `demandes` (`id_demande`) ON DELETE CASCADE;

-- --------------------------------------------------------
-- Insertion des données
-- --------------------------------------------------------

-- Données pour la table `administrateurs`
INSERT INTO `administrateurs` (`id_administrateur`, `email`, `login`, `password`) VALUES
('ADM001', 'admin@uae.ac.ma', 'admin', '$2y$10$vs9C9FuukYO.Y9RcEmPJ9eUiyrm2FbtliqYDWpHgaT3qxs6M/uXiy'),
('ADM002', 'scolarite@uae.ac.ma', 'scolarite', '$2y$10$vs9C9FuukYO.Y9RcEmPJ9eUiyrm2FbtliqYDWpHgaT3qxs6M/uXiy'),
('ADM003', 'secretariat@uae.ac.ma', 'secretariat', '$2y$10$vs9C9FuukYO.Y9RcEmPJ9eUiyrm2FbtliqYDWpHgaT3qxs6M/uXiy'),
('ADM004', 'directeur@uae.ac.ma', 'directeur', '$2y$10$vs9C9FuukYO.Y9RcEmPJ9eUiyrm2FbtliqYDWpHgaT3qxs6M/uXiy'),
('ADM005', 'stage@uae.ac.ma', 'stage', '$2y$10$vs9C9FuukYO.Y9RcEmPJ9eUiyrm2FbtliqYDWpHgaT3qxs6M/uXiy');

-- Données pour la table `annee_universitaire`
INSERT INTO `annee_universitaire` (`id_annee`, `annee_debut`, `annee_fin`) VALUES
('AN2021', 2021, 2022),
('AN2022', 2022, 2023),
('AN2023', 2023, 2024),
('AN2024', 2024, 2025),
('AN2025', 2025, 2026);

-- Données pour la table `etudiants`
-- NOTE: niveau_scolaire est maintenant calculé dynamiquement à partir de la filière actuelle
INSERT INTO `etudiants` (`id_etudiant`, `cin`, `numero_apogee`, `nom`, `prenom`, `date_naissance`, `lieu_naissance`, `email`) VALUES
('E001', 'AB123456', '20230001', 'Benali', 'Ahmed', '2003-05-15', 'Tétouan', 'ahmed.benali@etu.uae.ac.ma'),
('E002', 'CD789012', '20220001', 'Alami', 'Fatima', '2002-08-20', 'Tanger', 'fatima.alami@etu.uae.ac.ma'),
('E003', 'EF345678', '20210001', 'El Mansouri', 'Youssef', '2001-03-10', 'Tétouan', 'youssef.el-mansouri@etu.uae.ac.ma'),
('E004', 'GH901234', '20200001', 'Idrissi', 'Sara', '2000-11-25', 'Tanger', 'sara.idrissi@etu.uae.ac.ma');

-- Données pour la table `filiere`
INSERT INTO `filiere` (`id_filiere`, `nom_filiere`) VALUES
('FIL001', '2AP1'),
('FIL002', '2AP2'),
('FIL003', 'Génie Informatique 1'),
('FIL004', 'Génie Informatique 2'),
('FIL005', 'Génie Informatique 3');

-- Données pour la table `module`
INSERT INTO `module` (`id_module`, `nom_module`) VALUES
-- 2AP1 - S1
('MOD001', 'Algèbre 1'),
('MOD002', 'Analyse 1'),
('MOD003', 'Physique 1'),
('MOD004', 'Mécanique du point'),
('MOD005', 'Informatique 1'),
('MOD006', 'Langues 1 (Français / MTU)'),
-- 2AP1 - S2
('MOD007', 'Algèbre 2'),
('MOD008', 'Analyse 2'),
('MOD009', 'Physique 2'),
('MOD010', 'Chimie'),
('MOD011', 'Maple/Matlab'),
('MOD012', 'Langues 2 (Français / Anglais)'),
-- 2AP2 - S3
('MOD013', 'Algèbre 3'),
('MOD014', 'Analyse 3'),
('MOD015', 'Mécanique du solide'),
('MOD016', 'Thermodynamique'),
('MOD017', 'Électronique'),
('MOD018', 'Informatique 2'),
('MOD019', 'Langues 3 (Français / Anglais)'),
-- 2AP2 - S4
('MOD020', 'Analyse 4'),
('MOD021', 'Physique 3'),
('MOD022', 'Physique 4'),
('MOD023', 'Math appliquées'),
('MOD024', 'Langues 4 (Activité d\'ouverture / Anglais)'),
('MOD025', 'Probabilités et statistiques avancées'),
-- Génie Informatique 1 - S5
('MOD026', 'Théorie des Graphes et Recherche Opérationnelle'),
('MOD027', 'Architecture des Ordinateurs & Assembleur'),
('MOD028', 'Bases de Données Relationnelles'),
('MOD029', 'Réseaux Informatiques'),
('MOD030', 'Structure de Données en C'),
('MOD031', 'Langues étrangères 1'),
('MOD032', 'Digital Skills'),
-- Génie Informatique 1 - S6
('MOD033', 'Systèmes d\'Exploitation et Linux'),
('MOD034', 'Modélisation Orientée Objet'),
('MOD035', 'Technologie Enregistrement et Compilation'),
('MOD036', 'Développement Web'),
('MOD037', 'Programmation Orientée Objet Java'),
('MOD038', 'Langues étrangères 2'),
('MOD039', 'Culture & Arts & Sport Skills'),
-- Génie Informatique 2 - S7
('MOD040', 'Administration des Bases de Données Relationnelles'),
('MOD041', 'Développement Web Avancé'),
('MOD042', 'Réseaux Informatiques Avancés'),
('MOD043', 'Méthodologies et Génie Logiciel'),
('MOD044', 'Technologie DotNet'),
('MOD045', 'Langues étrangères 3'),
('MOD046', 'Power Skills : IA et éthique'),
-- Génie Informatique 2 - S8
('MOD047', 'Sécurité Informatique'),
('MOD048', 'Machine Learning'),
('MOD049', 'Administration Systèmes, Services et Sécurité Réseaux'),
('MOD050', 'Java Entreprise Edition'),
('MOD051', 'Microservices et Développement Mobile'),
('MOD052', 'Langues étrangères 4'),
('MOD053', 'Gestion de projet et entreprise'),
-- Génie Informatique 3 - S9
('MOD054', 'Frameworks Technologies Web'),
('MOD055', 'Big Data & Analytics'),
('MOD056', 'Systèmes de Planification des Ressources d\'Entreprise (ERP)'),
('MOD057', 'Urbanisme des Systèmes d\'Information'),
('MOD058', 'Deep Learning'),
('MOD059', 'Langues étrangères 5'),
('MOD060', 'Employment Skills'),
-- Génie Informatique 3 - S10 (PFE uniquement)
('MOD061', 'Stage PFE - Projet de Fin d\'Études');

-- Données pour la table `professeur`
INSERT INTO `professeur` (`id_prof`, `nom`, `prenom`, `email`, `telephone`, `departement`, `est_encadrant`) VALUES
('P001', 'Alami', 'Hassan', 'h.alami@uae.ac.ma', '0612345678', 'Mathématiques', 1),
('P002', 'Benkirane', 'Leila', 'l.benkirane@uae.ac.ma', '0612345679', 'Physique', 1),
('P003', 'Tazi', 'Mohammed', 'm.tazi@uae.ac.ma', '0612345680', 'Mathématiques', 1),
('P004', 'El Ouafi', 'Amina', 'a.elouafi@uae.ac.ma', '0612345681', 'Physique', 1),
('P005', 'Bennani', 'Rachid', 'r.bennani@uae.ac.ma', '0612345682', 'Informatique', 1),
('P006', 'Moussaoui', 'Fatima', 'f.moussaoui@uae.ac.ma', '0612345683', 'Chimie', 1),
('P007', 'Idrissi', 'Ahmed', 'a.idrissi@uae.ac.ma', '0612345684', 'Mécanique', 1),
('P008', 'El Fassi', 'Karim', 'k.elfassi@uae.ac.ma', '0612345685', 'Informatique', 1),
('P009', 'Bouazza', 'Said', 's.bouazza@uae.ac.ma', '0612345686', 'Langues', 0),
('P010', 'Ouazzani', 'Nadia', 'n.ouazzani@uae.ac.ma', '0612345687', 'Langues', 0),
('P011', 'Cherkaoui', 'Youssef', 'y.cherkaoui@uae.ac.ma', '0612345688', 'Informatique', 1),
('P012', 'Hamidi', 'Samira', 's.hamidi@uae.ac.ma', '0612345689', 'Informatique', 1),
('P013', 'Rhouli', 'Mehdi', 'm.rhouli@uae.ac.ma', '0612345690', 'Réseaux', 1),
('P014', 'Bouzidi', 'Kawtar', 'k.bouzidi@uae.ac.ma', '0612345691', 'Base de Données', 1);

-- Données pour la table `inscription_etudiant`
-- NOTE: moyenne, mention et est_admis sont maintenant calculés dynamiquement à partir des notes
INSERT INTO `inscription_etudiant` (`id_etudiant`, `id_filiere`, `id_annee`) VALUES
('E001', 'FIL001', 'AN2023'),
('E002', 'FIL001', 'AN2022'),
('E002', 'FIL002', 'AN2023'),
('E002', 'FIL003', 'AN2024'),
('E003', 'FIL001', 'AN2021'),
('E003', 'FIL002', 'AN2022'),
('E003', 'FIL003', 'AN2023'),
('E003', 'FIL004', 'AN2024'),
('E003', 'FIL005', 'AN2025'),
('E004', 'FIL003', 'AN2023');

-- Données pour la table `inscrit_module`
-- Note: La colonne 'session' indique la session d'examen: 'Normal' ou 'Rattrapage'
-- L'année universitaire est stockée dans inscription_etudiant via id_annee
-- NOTE: est_valide est maintenant calculé dynamiquement selon la filière (10/20 pour 2AP1/2AP2, 12/20 pour Génie Informatique)
-- E001 : 2AP1 - 2023-2024 (AN2023)
INSERT INTO `inscrit_module` (`id_etudiant`, `id_module`, `session`, `note`) VALUES
('E001', 'MOD001', 'Normal', 16.00),
('E001', 'MOD002', 'Normal', 15.00),
('E001', 'MOD003', 'Normal', 14.50),
('E001', 'MOD004', 'Normal', 17.00),
('E001', 'MOD005', 'Normal', 15.50),
('E001', 'MOD006', 'Normal', 16.00),
('E001', 'MOD007', 'Normal', 16.50),
('E001', 'MOD008', 'Normal', 15.75),
('E001', 'MOD009', 'Normal', 17.00),
('E001', 'MOD010', 'Normal', 14.50),
('E001', 'MOD011', 'Normal', 16.00),
('E001', 'MOD012', 'Normal', 15.25);

-- E002 : 2AP1 (AN2022), 2AP2 (AN2023), GI1 (AN2024)
-- 2AP1 - Modules S1-S2 (MOD001 à MOD012)
INSERT INTO `inscrit_module` (`id_etudiant`, `id_module`, `session`, `note`) VALUES
('E002', 'MOD001', 'Normal', 14.00),
('E002', 'MOD002', 'Normal', 13.50),
('E002', 'MOD003', 'Normal', 15.00),
('E002', 'MOD004', 'Normal', 14.00),
('E002', 'MOD005', 'Normal', 14.50),
('E002', 'MOD006', 'Rattrapage', 10.00),
('E002', 'MOD007', 'Normal', 15.00),
('E002', 'MOD008', 'Normal', 9.50),
('E002', 'MOD009', 'Normal', 16.00),
('E002', 'MOD010', 'Normal', 15.25),
('E002', 'MOD011', 'Normal', 14.75),
('E002', 'MOD012', 'Normal', 15.50),
-- 2AP2 - Modules S3-S4 (MOD013 à MOD025)
('E002', 'MOD013', 'Normal', 16.50),
('E002', 'MOD014', 'Normal', 15.75),
('E002', 'MOD015', 'Normal', 17.00),
('E002', 'MOD016', 'Normal', 16.25),
('E002', 'MOD017', 'Normal', 15.50),
('E002', 'MOD018', 'Normal', 16.00),
('E002', 'MOD019', 'Rattrapage', 10),
('E002', 'MOD020', 'Normal', 17.50),
('E002', 'MOD021', 'Normal', 16.00),
('E002', 'MOD022', 'Normal', 15.75),
('E002', 'MOD023', 'Normal', 16.25),
('E002', 'MOD024', 'Normal', 15.50),
('E002', 'MOD025', 'Rattrapage', 10.00),
-- GI1 - Modules S5-S6 (MOD026 à MOD039)
('E002', 'MOD026', 'Normal', 16.50),
('E002', 'MOD027', 'Normal', 11.00),
('E002', 'MOD028', 'Normal', 16.75),
('E002', 'MOD029', 'Normal', 17.25),
('E002', 'MOD030', 'Normal', 16.00),
('E002', 'MOD031', 'Normal', 17.50),
('E002', 'MOD032', 'Normal', 16.25),
('E002', 'MOD033', 'Normal', 17.00),
('E002', 'MOD034', 'Normal', 16.75),
('E002', 'MOD035', 'Normal', 17.25),
('E002', 'MOD036', 'Rattrapage', 12.00),
('E002', 'MOD037', 'Normal', 17.00),
('E002', 'MOD038', 'Normal', 16.75),
('E002', 'MOD039', 'Normal', 17.50);

-- E003 : 2AP1 (AN2021), 2AP2 (AN2022), GI1 (AN2023), GI2 (AN2024), GI3 (AN2025)
-- 2AP1 - Modules S1-S2 (MOD001 à MOD012) - Note: certains modules en rattrapage pour montrer la variété
INSERT INTO `inscrit_module` (`id_etudiant`, `id_module`, `session`, `note`) VALUES
('E003', 'MOD001', 'Normal', 12.50),
('E003', 'MOD002', 'Normal', 13.00),
('E003', 'MOD003', 'Rattrapage', 10.00),
('E003', 'MOD004', 'Normal', 14.00),
('E003', 'MOD005', 'Normal', 13.50),
('E003', 'MOD006', 'Normal', 12.00),
('E003', 'MOD007', 'Normal', 14.00),
('E003', 'MOD008', 'Normal', 13.50),
('E003', 'MOD009', 'Normal', 15.00),
('E003', 'MOD010', 'Normal', 14.25),
('E003', 'MOD011', 'Normal', 13.75),
('E003', 'MOD012', 'Normal', 14.50),
-- 2AP2 - Modules S3-S4 (MOD013 à MOD025)
('E003', 'MOD013', 'Normal', 15.50),
('E003', 'MOD014', 'Normal', 14.75),
('E003', 'MOD015', 'Normal', 16.00),
('E003', 'MOD016', 'Normal', 15.25),
('E003', 'MOD017', 'Normal', 14.50),
('E003', 'MOD018', 'Normal', 15.00),
('E003', 'MOD019', 'Rattrapage', 10.00),
('E003', 'MOD020', 'Normal', 16.50),
('E003', 'MOD021', 'Normal', 15.00),
('E003', 'MOD022', 'Normal', 15.75),
('E003', 'MOD023', 'Normal', 7.25),
('E003', 'MOD024', 'Normal', 15.50),
('E003', 'MOD025', 'Rattrapage', 10.00),
-- GI1 - Modules S5-S6 (MOD026 à MOD039)
('E003', 'MOD026', 'Normal', 17.50),
('E003', 'MOD027', 'Normal', 18.00),
('E003', 'MOD028', 'Normal', 17.25),
('E003', 'MOD029', 'Normal', 18.50),
('E003', 'MOD030', 'Rattrapage', 12.00),
('E003', 'MOD031', 'Normal', 18.25),
('E003', 'MOD032', 'Normal', 17.75),
('E003', 'MOD033', 'Normal', 18.00),
('E003', 'MOD034', 'Normal', 17.50),
('E003', 'MOD035', 'Normal', 18.25),
('E003', 'MOD036', 'Rattrapage', 9.75),
('E003', 'MOD037', 'Normal', 18.50),
('E003', 'MOD038', 'Normal', 17.25),
('E003', 'MOD039', 'Normal', 18.00),
-- GI2 - Modules S7-S8 (MOD040 à MOD053)
('E003', 'MOD040', 'Normal', 17.50),
('E003', 'MOD041', 'Normal', 18.25),
('E003', 'MOD042', 'Normal', 18.50),
('E003', 'MOD043', 'Normal', 17.75),
('E003', 'MOD044', 'Normal', 8.25),
('E003', 'MOD045', 'Normal', 17.50),
('E003', 'MOD046', 'Normal', 18.00),
('E003', 'MOD047', 'Normal', 18.75),
('E003', 'MOD048', 'Normal', 19.00),
('E003', 'MOD049', 'Normal', 18.50),
('E003', 'MOD050', 'Rattrapage', 11.25),
('E003', 'MOD051', 'Normal', 18.75),
('E003', 'MOD052', 'Normal', 17.50),
('E003', 'MOD053', 'Rattrapage', 12.00),
-- GI3 - Modules S9-S10 (MOD054 à MOD061)
-- Note: MOD058 avec note 11.75 sera non validé pour GI3 (seuil 12/20)
('E003', 'MOD054', 'Normal', 15.00),
('E003', 'MOD055', 'Normal', 14.25),
('E003', 'MOD056', 'Normal', 13.77),
('E003', 'MOD057', 'Normal', 12.00),
('E003', 'MOD058', 'Normal', 11.75),
('E003', 'MOD059', 'Normal', 12.25),
('E003', 'MOD060', 'Normal', 16.75),
('E003', 'MOD061', 'Normal', 17.25);

-- E004 : GI1 (admission directe) - AN2023
INSERT INTO `inscrit_module` (`id_etudiant`, `id_module`, `session`, `note`) VALUES
('E004', 'MOD026', 'Normal', 16.75),
('E004', 'MOD027', 'Normal', 17.25),
('E004', 'MOD028', 'Normal', 17.00),
('E004', 'MOD029', 'Normal', 17.50),
('E004', 'MOD030', 'Normal', 16.50),
('E004', 'MOD031', 'Normal', 17.75),
('E004', 'MOD032', 'Normal', 17.00),
('E004', 'MOD033', 'Normal', 17.25),
('E004', 'MOD034', 'Normal', 16.75),
('E004', 'MOD035', 'Normal', 17.50),
('E004', 'MOD036', 'Normal', 17.00),
('E004', 'MOD037', 'Normal', 17.75),
('E004', 'MOD038', 'Normal', 16.50),
('E004', 'MOD039', 'Normal', 17.25);

-- Données pour la table `module_filiere`
INSERT INTO `module_filiere` (`id_filiere`, `id_module`, `semestre`) VALUES
('FIL001', 'MOD001', 1),
('FIL001', 'MOD002', 1),
('FIL001', 'MOD003', 1),
('FIL001', 'MOD004', 1),
('FIL001', 'MOD005', 1),
('FIL001', 'MOD006', 1),
('FIL001', 'MOD007', 2),
('FIL001', 'MOD008', 2),
('FIL001', 'MOD009', 2),
('FIL001', 'MOD010', 2),
('FIL001', 'MOD011', 2),
('FIL001', 'MOD012', 2),
('FIL002', 'MOD013', 3),
('FIL002', 'MOD014', 3),
('FIL002', 'MOD015', 3),
('FIL002', 'MOD016', 3),
('FIL002', 'MOD017', 3),
('FIL002', 'MOD018', 3),
('FIL002', 'MOD019', 3),
('FIL002', 'MOD020', 4),
('FIL002', 'MOD021', 4),
('FIL002', 'MOD022', 4),
('FIL002', 'MOD023', 4),
('FIL002', 'MOD024', 4),
('FIL002', 'MOD025', 4),
('FIL003', 'MOD026', 5),
('FIL003', 'MOD027', 5),
('FIL003', 'MOD028', 5),
('FIL003', 'MOD029', 5),
('FIL003', 'MOD030', 5),
('FIL003', 'MOD031', 5),
('FIL003', 'MOD032', 5),
('FIL003', 'MOD033', 6),
('FIL003', 'MOD034', 6),
('FIL003', 'MOD035', 6),
('FIL003', 'MOD036', 6),
('FIL003', 'MOD037', 6),
('FIL003', 'MOD038', 6),
('FIL003', 'MOD039', 6),
('FIL004', 'MOD040', 7),
('FIL004', 'MOD041', 7),
('FIL004', 'MOD042', 7),
('FIL004', 'MOD043', 7),
('FIL004', 'MOD044', 7),
('FIL004', 'MOD045', 7),
('FIL004', 'MOD046', 7),
('FIL004', 'MOD047', 8),
('FIL004', 'MOD048', 8),
('FIL004', 'MOD049', 8),
('FIL004', 'MOD050', 8),
('FIL004', 'MOD051', 8),
('FIL004', 'MOD052', 8),
('FIL004', 'MOD053', 8),
('FIL005', 'MOD054', 9),
('FIL005', 'MOD055', 9),
('FIL005', 'MOD056', 9),
('FIL005', 'MOD057', 9),
('FIL005', 'MOD058', 9),
('FIL005', 'MOD059', 9),
('FIL005', 'MOD060', 9),
('FIL005', 'MOD061', 10);

-- Données pour la table `module_prof`
INSERT INTO `module_prof` (`id_prof`, `id_module`) VALUES
('P001', 'MOD001'),
('P001', 'MOD002'),
('P001', 'MOD007'),
('P001', 'MOD008'),
('P002', 'MOD003'),
('P002', 'MOD009'),
('P006', 'MOD010'),
('P007', 'MOD004'),
('P005', 'MOD005'),
('P009', 'MOD006'),
('P009', 'MOD012'),
('P003', 'MOD013'),
('P003', 'MOD014'),
('P003', 'MOD020'),
('P003', 'MOD023'),
('P004', 'MOD015'),
('P004', 'MOD016'),
('P004', 'MOD022'),
('P002', 'MOD021'),
('P007', 'MOD015'),
('P007', 'MOD017'),
('P005', 'MOD018'),
('P009', 'MOD019'),
('P009', 'MOD024'),
('P003', 'MOD026'),
('P005', 'MOD027'),
('P005', 'MOD030'),
('P005', 'MOD032'),
('P011', 'MOD028'),
('P011', 'MOD040'),
('P013', 'MOD029'),
('P013', 'MOD042'),
('P010', 'MOD031'),
('P010', 'MOD038'),
('P010', 'MOD045'),
('P010', 'MOD052'),
('P008', 'MOD033'),
('P008', 'MOD034'),
('P008', 'MOD037'),
('P008', 'MOD039'),
('P008', 'MOD048'),
('P008', 'MOD050'),
('P012', 'MOD035'),
('P012', 'MOD036'),
('P012', 'MOD041'),
('P012', 'MOD046'),
('P012', 'MOD054'),
('P011', 'MOD044'),
('P011', 'MOD055'),
('P011', 'MOD058'),
('P011', 'MOD061'),
('P013', 'MOD047'),
('P013', 'MOD049'),
('P013', 'MOD053'),
('P014', 'MOD043'),
('P014', 'MOD056'),
('P014', 'MOD060');

-- Données pour la table `demandes`
INSERT INTO `demandes` (`id_demande`, `numero_reference`, `type_document`, `statut`, `date_demande`, `id_etudiant`, `id_administrateur`) VALUES
('D001', 'REQ-2024-001', 'attestations_scolarite', 'traite', '2024-09-15', 'E001', 'ADM002'),
('D002', 'REQ-2024-002', 'releves_notes', 'traite', '2024-09-16', 'E001', 'ADM001'),
('D003', 'REQ-2024-003', 'attestations_scolarite', 'en attente', '2024-09-18', 'E002', NULL),
('D004', 'REQ-2024-004', 'releves_notes', 'traite', '2024-09-20', 'E002', 'ADM002'),
('D005', 'REQ-2024-005', 'attestations_reussite', 'traite', '2024-09-22', 'E002', 'ADM001'),
('D006', 'REQ-2024-006', 'releves_notes', 'traite', '2024-09-14', 'E003', 'ADM002'),
('D007', 'REQ-2024-007', 'conventions_stage', 'traite', '2024-09-10', 'E003', 'ADM005'),
('D008', 'REQ-2024-008', 'attestations_reussite', 'traite', '2024-09-12', 'E003', 'ADM001'),
('D009', 'REQ-2024-009', 'attestations_scolarite', 'traite', '2024-09-17', 'E004', 'ADM002'),
('D010', 'REQ-2024-010', 'releves_notes', 'traite', '2024-09-19', 'E004', 'ADM001'),
('D011', 'REQ-2024-011', 'conventions_stage', 'en attente', '2024-10-02', 'E001', NULL),
('D012', 'REQ-2024-012', 'attestations_scolarite', 'refuse', '2024-09-25', 'E001', 'ADM002'),
('D013', 'REQ-2024-013', 'releves_notes', 'en attente', '2024-10-05', 'E002', NULL),
('D014', 'REQ-2024-014', 'attestations_reussite', 'traite', '2024-09-28', 'E002', 'ADM001'),
('D015', 'REQ-2024-015', 'conventions_stage', 'traite', '2024-10-01', 'E002', 'ADM005'),
('D016', 'REQ-2024-016', 'attestations_scolarite', 'en attente', '2024-10-08', 'E003', NULL),
('D017', 'REQ-2024-017', 'releves_notes', 'refuse', '2024-09-30', 'E003', 'ADM002'),
('D018', 'REQ-2024-018', 'conventions_stage', 'en attente', '2024-10-10', 'E003', NULL),
('D019', 'REQ-2024-019', 'attestations_reussite', 'traite', '2024-10-03', 'E003', 'ADM001'),
('D020', 'REQ-2024-020', 'releves_notes', 'traite', '2024-10-07', 'E003', 'ADM002'),
('D021', 'REQ-2024-021', 'attestations_scolarite', 'traite', '2024-09-29', 'E004', 'ADM002'),
('D022', 'REQ-2024-022', 'conventions_stage', 'refuse', '2024-09-26', 'E004', 'ADM005'),
('D023', 'REQ-2024-023', 'releves_notes', 'en attente', '2024-10-12', 'E001', NULL),
('D024', 'REQ-2024-024', 'attestations_reussite', 'en attente', '2024-10-09', 'E002', NULL),
('D025', 'REQ-2024-025', 'attestations_scolarite', 'traite', '2024-10-04', 'E004', 'ADM002'),
('D026', 'REQ-2024-026', 'releves_notes', 'traite', '2024-10-06', 'E004', 'ADM001'),
('D027', 'REQ-2024-027', 'conventions_stage', 'traite', '2024-09-27', 'E001', 'ADM005'),
('D028', 'REQ-2024-028', 'attestations_scolarite', 'refuse', '2024-10-11', 'E003', 'ADM002'),
('D029', 'REQ-2024-029', 'releves_notes', 'en attente', '2024-10-14', 'E002', NULL),
('D030', 'REQ-2024-030', 'attestations_reussite', 'traite', '2024-10-13', 'E004', 'ADM001');

-- Données pour la table `attestations_reussite`
INSERT INTO `attestations_reussite` (`id_attestation`, `annee_universitaire`, `id_demande`) VALUES
('AR001', 'AN2022', 'D005'),
('AR002', 'AN2022', 'D008'),
('AR003', 'AN2023', 'D014'),
('AR004', 'AN2023', 'D019'),
('AR005', 'AN2023', 'D030');

-- Données pour la table `releves_notes`
INSERT INTO `releves_notes` (`id_releve`, `annee_universitaire`, `semestre`, `id_demande`) VALUES
('RN001', 'AN2023', 'S1', 'D002'),
('RN002', 'AN2023', 'S3', 'D004'),
('RN003', 'AN2023', 'S5', 'D006'),
('RN004', 'AN2023', 'S5', 'D010'),
('RN005', 'AN2023', 'S2', 'D013'),
('RN006', 'AN2023', 'S4', 'D020'),
('RN007', 'AN2023', 'S6', 'D023'),
('RN008', 'AN2023', 'S5', 'D026'),
('RN009', 'AN2023', 'S7', 'D029');

-- Données pour la table `conventions_stage`
INSERT INTO `conventions_stage` (`id_convention`, `sujet_stage`, `date_fin_stage`, `date_debut_stage`, `nom_entreprise`, `email_responsable_entreprise`, `nom_responsable_entreprise`, `telephone_responsable_entreprise`, `adresse_entreprise`, `id_demande`, `id_prof_encadrant`) VALUES
('CS001', 'Développement d\'une plateforme de gestion scolaire avec React et Node.js', '2025-05-31', '2025-02-01', 'Tech Solutions Maroc', 'contact@techsolutions.ma', 'Mohammed El Amrani', '0522-123456', '123 Avenue Hassan II, Tétouan', 'D007', 'P011'),
('CS002', 'Analyse et développement d\'une application mobile de gestion des ressources humaines', '2024-12-20', '2024-11-01', 'Digital Innovation Morocco', 'rh@digitalinnovation.ma', 'Fatima Alami', '0539-987654', '45 Boulevard Mohammed V, Casablanca', 'D011', 'P008'),
('CS003', 'Mise en place d\'un système de cybersécurité pour une banque', '2025-03-15', '2025-01-15', 'Banque Marocaine', 'stage@bm.ma', 'Karim Bennani', '0522-456789', '78 Rue Allal Ben Abdellah, Rabat', 'D015', 'P013'),
('CS004', 'Développement d\'une solution IoT pour l\'agriculture intelligente', '2025-04-30', '2025-02-10', 'AgriTech Solutions', 'contact@agritech.ma', 'Said Ouazzani', '0536-789123', '12 Zone Industrielle, Tanger', 'D018', 'P012'),
('CS005', 'Stage en développement web full-stack avec Angular et Spring Boot', '2024-11-30', '2024-10-15', 'WebDev Pro', 'stages@webdevpro.ma', 'Nadia Hamidi', '0537-321654', '89 Avenue Mohamed VI, Fès', 'D027', 'P008');

-- Données pour la table `reclamations`
INSERT INTO `reclamations` (`id_reclamation`, `numero_reference`, `date_reclamation`, `description`, `objet`, `statut`, `reponse`, `date_reponse`, `id_etudiant`, `id_administrateur`, `id_demande`) VALUES
('R001', 'REC-2024-001', '2024-09-18', 'Ma demande de relevé de notes n\'a pas été traitée depuis plus de 10 jours. J\'en ai besoin pour une bourse.', 'Retard traitement relevé de notes', 'resolu', 'Votre relevé de notes a été traité et envoyé par email. Veuillez vérifier votre boîte de réception.', '2024-09-20 10:30:00', 'E003', 'ADM002', 'D006'),
('R002', 'REC-2024-002', '2024-09-25', 'Le relevé de notes que j\'ai reçu contient des erreurs. La note du module MOD026 (Théorie des Graphes) est incorrecte. J\'ai eu 17.50 et non 15.50.', 'Erreur dans les notes du relevé', 'resolu', 'Nous avons vérifié et corrigé la note. Un nouveau relevé a été généré et envoyé.', '2024-09-26 14:15:00', 'E003', 'ADM002', 'D006'),
('R003', 'REC-2024-003', '2024-09-28', 'Mon nom est écrit incorrectement sur le relevé. Il est écrit "El Mansouri" au lieu de "El Mansouri" (avec majuscule).', 'Erreur dans les informations personnelles', 'en attente', NULL, NULL, 'E003', NULL, 'D006'),
('R004', 'REC-2024-004', '2024-09-12', 'Ma demande de convention de stage est en attente alors que mon stage commence dans 2 semaines. J\'ai besoin de la convention rapidement.', 'Urgence convention de stage', 'resolu', 'Votre convention de stage a été traitée en priorité et envoyée par email.', '2024-09-13 09:00:00', 'E003', 'ADM005', 'D007'),
('R005', 'REC-2024-005', '2024-09-20', 'Les informations de l\'entreprise sur la convention sont incorrectes. L\'adresse est erronée. L\'adresse correcte est "125 Avenue Hassan II, Tétouan" et non "123 Avenue Hassan II".', 'Erreur dans les informations de l\'entreprise', 'en attente', NULL, NULL, 'E003', NULL, 'D007'),
('R006', 'REC-2024-006', '2024-09-25', 'Ma demande d\'attestation de scolarité est en attente depuis plus d\'une semaine. J\'en ai besoin pour une inscription.', 'Retard traitement attestation', 'en attente', NULL, NULL, 'E002', NULL, 'D003'),
('R007', 'REC-2024-007', '2024-09-27', 'Pourquoi ma demande d\'attestation de scolarité a été refusée ? J\'ai besoin d\'explications.', 'Demande d\'attestation refusée sans justification', 'resolu', 'Votre demande a été refusée car vous avez des dettes administratives. Veuillez régulariser votre situation auprès du secrétariat.', '2024-09-28 11:20:00', 'E001', 'ADM002', 'D012'),
('R008', 'REC-2024-008', '2024-10-03', 'Ma demande d\'attestation de réussite pour l\'année 2022-2023 contient une erreur dans l\'année universitaire mentionnée.', 'Erreur dans l\'attestation de réussite', 'resolu', 'L\'erreur a été corrigée. Une nouvelle attestation vous sera envoyée sous 48h.', '2024-10-04 08:45:00', 'E002', 'ADM001', 'D014'),
('R009', 'REC-2024-009', '2024-10-06', 'Le relevé de notes que j\'ai reçu manque le semestre S4. Je n\'ai que les notes du semestre S3.', 'Relevé de notes incomplet', 'en attente', NULL, NULL, 'E002', NULL, 'D013'),
('R010', 'REC-2024-010', '2024-10-01', 'Ma convention de stage mentionne un professeur encadrant qui n\'est plus disponible. Je souhaite changer d\'encadrant.', 'Changement de professeur encadrant', 'resolu', 'Un nouveau professeur encadrant vous a été assigné. Une convention modifiée vous sera envoyée.', '2024-10-02 15:30:00', 'E002', 'ADM005', 'D015'),
('R011', 'REC-2024-011', '2024-10-08', 'Pourquoi mon relevé de notes a été refusé ? Je suis à jour dans mes paiements.', 'Refus de relevé de notes injustifié', 'en attente', NULL, NULL, 'E003', NULL, 'D017'),
('R012', 'REC-2024-012', '2024-10-10', 'Ma demande de convention de stage est en attente depuis plus de 15 jours. Mon stage commence bientôt.', 'Retard traitement convention de stage', 'en attente', NULL, NULL, 'E003', NULL, 'D018'),
('R013', 'REC-2024-013', '2024-10-04', 'L\'attestation de réussite que j\'ai reçue mentionne la mauvaise filière. Je suis en Génie Informatique et non en 2AP2.', 'Erreur de filière dans l\'attestation', 'resolu', 'Nous nous excusons pour l\'erreur. Une attestation corrigée vous sera envoyée immédiatement.', '2024-10-05 10:00:00', 'E003', 'ADM001', 'D019'),
('R014', 'REC-2024-014', '2024-10-09', 'Mon relevé de notes du semestre S5 contient des notes qui ne correspondent pas à mes résultats réels. Plusieurs modules ont des notes différentes.', 'Plusieurs erreurs dans le relevé de notes', 'en attente', NULL, NULL, 'E003', NULL, 'D020'),
('R015', 'REC-2024-015', '2024-09-30', 'Ma demande d\'attestation de scolarité a été traitée mais je ne l\'ai jamais reçue par email. Pouvez-vous la renvoyer ?', 'Attestation non reçue par email', 'resolu', 'L\'attestation vous a été renvoyée à l\'adresse email enregistrée dans votre dossier.', '2024-10-01 13:15:00', 'E004', 'ADM002', 'D021'),
('R016', 'REC-2024-016', '2024-09-27', 'Pourquoi ma convention de stage a été refusée ? J\'ai fourni tous les documents nécessaires.', 'Refus de convention de stage', 'resolu', 'Votre convention a été refusée car l\'entreprise n\'est pas reconnue par l\'école. Veuillez choisir une entreprise agréée.', '2024-09-28 16:45:00', 'E004', 'ADM005', 'D022'),
('R017', 'REC-2024-017', '2024-10-12', 'J\'ai besoin de mon relevé de notes rapidement pour postuler à une bourse. Ma demande est en attente depuis une semaine.', 'Urgence relevé de notes pour bourse', 'en attente', NULL, NULL, 'E001', NULL, 'D023'),
('R018', 'REC-2024-018', '2024-10-11', 'Ma demande d\'attestation de scolarité a été refusée sans raison précise. J\'ai besoin de cette attestation pour mon inscription.', 'Refus d\'attestation sans explication', 'en attente', NULL, NULL, 'E003', NULL, 'D028'),
('R019', 'REC-2024-019', '2024-10-14', 'L\'attestation de réussite que j\'ai reçue ne mentionne pas ma mention (Bien). Pouvez-vous la corriger ?', 'Mention manquante dans l\'attestation', 'en attente', NULL, NULL, 'E002', NULL, 'D024'),
('R020', 'REC-2024-020', '2024-10-07', 'Le relevé de notes que j\'ai reçu est illisible, les notes sont floues. Je souhaite recevoir une version claire.', 'Relevé de notes illisible', 'resolu', 'Un nouveau relevé de meilleure qualité vous a été envoyé par email.', '2024-10-08 09:30:00', 'E004', 'ADM001', 'D026'),
('R021', 'REC-2024-021', '2024-09-29', 'Ma convention de stage mentionne une date de début incorrecte. Le stage commence le 15 octobre et non le 1er novembre.', 'Erreur de date dans la convention', 'resolu', 'La date a été corrigée et une nouvelle convention vous sera envoyée.', '2024-09-30 11:00:00', 'E001', 'ADM005', 'D027'),
('R022', 'REC-2024-022', '2024-10-13', 'L\'attestation de réussite que j\'ai reçue pour l\'année 2023-2024 contient mon ancien numéro d\'apogée.', 'Erreur de numéro Apogée', 'en attente', NULL, NULL, 'E004', NULL, 'D030'),
('R023', 'REC-2024-023', '2024-10-15', 'Ma demande de relevé de notes pour le semestre S7 est en attente. J\'en ai besoin pour mon dossier de candidature en master.', 'Besoin urgent de relevé pour master', 'en attente', NULL, NULL, 'E002', NULL, 'D029');

COMMIT;
