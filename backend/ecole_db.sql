-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : sam. 13 déc. 2025 à 00:04
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `ecole_db`
--

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
-- Déchargement des données de la table `administrateurs`
--

INSERT INTO `administrateurs` (`id_administrateur`, `email`, `login`, `password`) VALUES
('ADM001', 'admin@uae.ac.ma', 'admin', '$2y$10$vs9C9FuukYO.Y9RcEmPJ9eUiyrm2FbtliqYDWpHgaT3qxs6M/uXiy'),
('ADM002', 'scolarite@uae.ac.ma', 'scolarite', '$2y$10$vs9C9FuukYO.Y9RcEmPJ9eUiyrm2FbtliqYDWpHgaT3qxs6M/uXiy'),
('ADM003', 'secretariat@uae.ac.ma', 'secretariat', '$2y$10$vs9C9FuukYO.Y9RcEmPJ9eUiyrm2FbtliqYDWpHgaT3qxs6M/uXiy'),
('ADM004', 'directeur@uae.ac.ma', 'directeur', '$2y$10$vs9C9FuukYO.Y9RcEmPJ9eUiyrm2FbtliqYDWpHgaT3qxs6M/uXiy'),
('ADM005', 'stage@uae.ac.ma', 'stage', '$2y$10$vs9C9FuukYO.Y9RcEmPJ9eUiyrm2FbtliqYDWpHgaT3qxs6M/uXiy');

-- --------------------------------------------------------

--
-- Structure de la table `annee_universitaire`
--

CREATE TABLE `annee_universitaire` (
  `id_annee` varchar(10) NOT NULL,
  `annee_debut` int(11) NOT NULL,
  `annee_fin` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `annee_universitaire`
--

INSERT INTO `annee_universitaire` (`id_annee`, `annee_debut`, `annee_fin`) VALUES
('AN2020', 2020, 2021),
('AN2021', 2021, 2022),
('AN2022', 2022, 2023),
('AN2023', 2023, 2024),
('AN2024', 2024, 2025);

-- --------------------------------------------------------

--
-- Structure de la table `attestations_reussite`
--

CREATE TABLE `attestations_reussite` (
  `id_attestation` varchar(10) NOT NULL,
  `annee_universitaire` varchar(10) DEFAULT NULL,
  `id_demande` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `attestations_reussite`
--

INSERT INTO `attestations_reussite` (`id_attestation`, `annee_universitaire`, `id_demande`) VALUES
('AR001', '2023-2024', 'D002'),
('AR002', '2023-2024', 'D006');

-- --------------------------------------------------------

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
-- Déchargement des données de la table `conventions_stage`
--

INSERT INTO `conventions_stage` (`id_convention`, `sujet_stage`, `date_fin_stage`, `date_debut_stage`, `nom_entreprise`, `email_responsable_entreprise`, `nom_responsable_entreprise`, `telephone_responsable_entreprise`, `adresse_entreprise`, `id_demande`, `id_prof_encadrant`) VALUES
('CS001', 'Développement d\'une application web pour la gestion scolaire', '2025-05-31', '2025-02-01', 'Tech Solutions Maroc', 'contact@techsolutions.ma', 'Mohammed El Amrani', '0522-123456', '123 Avenue Hassan II, Tétouan', 'D004', 'P008'),
('CS002', 'Système de recommandation basé sur le Machine Learning', '2025-06-30', '2025-03-01', 'Data Intelligence SARL', 'info@dataintelligence.ma', 'Fatima Zahra Alaoui', '0539-987654', '45 Boulevard Mohammed V, Tanger', 'D008', 'P011');

-- --------------------------------------------------------

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
-- Déchargement des données de la table `demandes`
--

INSERT INTO `demandes` (`id_demande`, `numero_reference`, `type_document`, `statut`, `date_demande`, `id_etudiant`, `id_administrateur`) VALUES
('D001', 'REQ-2024-001', 'attestations_scolarite', 'traite', '2024-09-15', 'E001', 'ADM002'),
('D002', 'REQ-2024-002', 'attestations_reussite', 'traite', '2024-09-12', 'E005', 'ADM002'),
('D003', 'REQ-2024-003', 'releves_notes', 'traite', '2024-09-16', 'E003', 'ADM001'),
('D004', 'REQ-2024-004', 'conventions_stage', 'traite', '2024-09-10', 'E006', 'ADM005'),
('D005', 'REQ-2024-005', 'attestations_scolarite', 'en attente', '2024-09-18', 'E002', NULL),
('D006', 'REQ-2024-006', 'attestations_reussite', 'traite', '2024-09-20', 'E010', 'ADM001'),
('D007', 'REQ-2024-007', 'releves_notes', 'traite', '2024-09-14', 'E006', 'ADM002'),
('D008', 'REQ-2024-008', 'conventions_stage', 'traite', '2024-09-22', 'E005', 'ADM001'),
('D009', 'REQ-2024-009', 'attestations_scolarite', 'traite', '2024-09-17', 'E007', 'ADM002'),
('D010', 'REQ-2024-010', 'releves_notes', 'traite', '2024-09-19', 'E001', 'ADM002');

-- --------------------------------------------------------

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
  `niveau_scolaire` enum('1er annee','2éme annee','3eme annee') NOT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `etudiants`
--

INSERT INTO `etudiants` (`id_etudiant`, `cin`, `numero_apogee`, `nom`, `prenom`, `date_naissance`, `lieu_naissance`, `niveau_scolaire`, `email`) VALUES
('E001', 'AB123456', '20240001', 'Benali', 'Ahmed', '2003-05-15', 'Tétouan', '1er annee', 'ahmed.benali@etu.uae.ac.ma'),
('E002', 'CD789012', '20240002', 'Alami', 'Fatima', '2003-08-20', 'Tétouan', '1er annee', 'fatima.alami@etu.uae.ac.ma'),
('E003', 'EF345678', '20230001', 'El Mansouri', 'Youssef', '2002-03-10', 'Tanger', '2éme annee', 'youssef.el-mansouri@etu.uae.ac.ma'),
('E004', 'GH901234', '20230002', 'Idrissi', 'Sara', '2002-11-25', 'Tétouan', '2éme annee', 'sara.idrissi@etu.uae.ac.ma'),
('E005', 'IJ567890', '20220001', 'Bennani', 'Omar', '2001-07-12', 'Chefchaouen', '3eme annee', 'omar.bennani@etu.uae.ac.ma'),
('E006', 'KL123456', '20220002', 'Tazi', 'Aicha', '2001-09-30', 'Tanger', '3eme annee', 'aicha.tazi@etu.uae.ac.ma'),
('E007', 'MN789012', '20240003', 'El Fassi', 'Mehdi', '2003-01-18', 'Tétouan', '1er annee', 'mehdi.elfassi@etu.uae.ac.ma'),
('E008', 'OP345678', '20240004', 'Bouazza', 'Layla', '2003-04-22', 'Tétouan', '1er annee', 'layla.bouazza@etu.uae.ac.ma'),
('E009', 'QR123789', '20230003', 'Cherkaoui', 'Ali', '2002-06-14', 'Tanger', '2éme annee', 'ali.cherkaoui@etu.uae.ac.ma'),
('E010', 'ST456123', '20220003', 'Hamidi', 'Nadia', '2001-12-05', 'Tétouan', '3eme annee', 'nadia.hamidi@etu.uae.ac.ma'),
('E011', 'UV789456', '20220004', 'Rhouli', 'Karim', '2001-02-28', 'Chefchaouen', '3eme annee', 'karim.rhouli@etu.uae.ac.ma'),
('E012', 'WX123789', '20230004', 'Bouzidi', 'Samir', '2002-10-15', 'Tétouan', '2éme annee', 'samir.bouzidi@etu.uae.ac.ma');

-- --------------------------------------------------------

--
-- Structure de la table `filiere`
--

CREATE TABLE `filiere` (
  `id_filiere` varchar(10) NOT NULL,
  `nom_filiere` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `filiere`
--

INSERT INTO `filiere` (`id_filiere`, `nom_filiere`) VALUES
('FIL001', '2AP1'),
('FIL002', '2AP2'),
('FIL003', 'Génie Informatique'),
('FIL004', 'Génie Mécanique'),
('FIL005', 'Génie Civil');

-- --------------------------------------------------------

--
-- Structure de la table `inscription_etudiant`
--

CREATE TABLE `inscription_etudiant` (
  `id_etudiant` varchar(10) NOT NULL,
  `id_filiere` varchar(10) NOT NULL,
  `id_annee` varchar(10) NOT NULL,
  `moyenne` decimal(4,2) DEFAULT NULL,
  `mention` varchar(20) DEFAULT NULL,
  `est_admis` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `inscription_etudiant`
--

INSERT INTO `inscription_etudiant` (`id_etudiant`, `id_filiere`, `id_annee`, `moyenne`, `mention`, `est_admis`) VALUES
('E001', 'FIL001', 'AN2024', 15.50, 'Bien', 1),
('E002', 'FIL001', 'AN2024', 14.25, 'Assez Bien', 1),
('E003', 'FIL002', 'AN2024', 15.00, 'Bien', 1),
('E004', 'FIL002', 'AN2024', 17.25, 'Très Bien', 1),
('E005', 'FIL003', 'AN2024', 16.00, 'Bien', 1),
('E006', 'FIL003', 'AN2024', 18.25, 'Très Bien', 1),
('E007', 'FIL001', 'AN2024', 13.00, 'Passable', 1),
('E008', 'FIL001', 'AN2024', 16.75, 'Très Bien', 1),
('E009', 'FIL002', 'AN2024', 12.50, 'Passable', 1),
('E010', 'FIL003', 'AN2024', 15.50, 'Bien', 1),
('E011', 'FIL003', 'AN2024', 17.75, 'Très Bien', 1),
('E012', 'FIL002', 'AN2024', 14.75, 'Assez Bien', 1);

-- --------------------------------------------------------

--
-- Structure de la table `inscrit_module`
--

CREATE TABLE `inscrit_module` (
  `id_etudiant` varchar(10) NOT NULL,
  `id_module` varchar(10) NOT NULL,
  `session` varchar(20) NOT NULL,
  `note` decimal(4,2) DEFAULT NULL,
  `est_valide` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `inscrit_module`
--

INSERT INTO `inscrit_module` (`id_etudiant`, `id_module`, `session`, `note`, `est_valide`) VALUES
('E001', 'MOD001', '2024-2025', 16.00, 1),
('E001', 'MOD002', '2024-2025', 15.00, 1),
('E001', 'MOD003', '2024-2025', 14.50, 1),
('E001', 'MOD004', '2024-2025', 17.00, 1),
('E001', 'MOD005', '2024-2025', 15.50, 1),
('E001', 'MOD006', '2024-2025', 16.00, 1),
('E002', 'MOD001', '2024-2025', 14.00, 1),
('E002', 'MOD002', '2024-2025', 13.50, 1),
('E002', 'MOD003', '2024-2025', 15.00, 1),
('E002', 'MOD004', '2024-2025', 14.00, 1),
('E002', 'MOD005', '2024-2025', 14.50, 1),
('E002', 'MOD006', '2024-2025', 16.00, 1),
('E003', 'MOD013', '2024-2025', 14.50, 1),
('E003', 'MOD014', '2024-2025', 15.00, 1),
('E003', 'MOD015', '2024-2025', 13.50, 1),
('E003', 'MOD016', '2024-2025', 16.00, 1),
('E003', 'MOD017', '2024-2025', 14.00, 1),
('E003', 'MOD018', '2024-2025', 15.50, 1),
('E004', 'MOD013', '2024-2025', 17.50, 1),
('E004', 'MOD014', '2024-2025', 18.00, 1),
('E004', 'MOD015', '2024-2025', 16.50, 1),
('E004', 'MOD016', '2024-2025', 17.00, 1),
('E004', 'MOD017', '2024-2025', 18.50, 1),
('E004', 'MOD018', '2024-2025', 17.50, 1),
('E005', 'MOD054', '2024-2025', 16.50, 1),
('E005', 'MOD055', '2024-2025', 15.75, 1),
('E005', 'MOD056', '2024-2025', 17.00, 1),
('E005', 'MOD057', '2024-2025', 15.25, 1),
('E005', 'MOD058', '2024-2025', 16.00, 1),
('E005', 'MOD059', '2024-2025', 17.50, 1),
('E005', 'MOD060', '2024-2025', 16.00, 1),
('E006', 'MOD054', '2024-2025', 18.00, 1),
('E006', 'MOD055', '2024-2025', 17.50, 1),
('E006', 'MOD056', '2024-2025', 18.50, 1),
('E006', 'MOD057', '2024-2025', 17.75, 1),
('E006', 'MOD058', '2024-2025', 18.00, 1),
('E006', 'MOD059', '2024-2025', 19.00, 1),
('E006', 'MOD060', '2024-2025', 18.50, 1),
('E007', 'MOD001', '2024-2025', 12.50, 1),
('E007', 'MOD002', '2024-2025', 13.00, 1),
('E007', 'MOD003', '2024-2025', 11.50, 1),
('E007', 'MOD004', '2024-2025', 14.00, 1),
('E007', 'MOD005', '2024-2025', 13.50, 1),
('E007', 'MOD006', '2024-2025', 12.00, 1),
('E008', 'MOD001', '2024-2025', 16.50, 1),
('E008', 'MOD002', '2024-2025', 17.00, 1),
('E008', 'MOD003', '2024-2025', 15.50, 1),
('E008', 'MOD004', '2024-2025', 16.00, 1),
('E008', 'MOD005', '2024-2025', 17.50, 1),
('E008', 'MOD006', '2024-2025', 16.00, 1),
('E009', 'MOD013', '2024-2025', 11.50, 1),
('E009', 'MOD014', '2024-2025', 12.00, 1),
('E009', 'MOD015', '2024-2025', 10.50, 1),
('E009', 'MOD016', '2024-2025', 13.00, 1),
('E009', 'MOD017', '2024-2025', 12.50, 1),
('E009', 'MOD018', '2024-2025', 11.00, 1),
('E010', 'MOD054', '2024-2025', 15.00, 1),
('E010', 'MOD055', '2024-2025', 16.00, 1),
('E010', 'MOD056', '2024-2025', 15.50, 1),
('E010', 'MOD057', '2024-2025', 16.50, 1),
('E010', 'MOD058', '2024-2025', 15.00, 1),
('E010', 'MOD059', '2024-2025', 16.00, 1),
('E010', 'MOD060', '2024-2025', 15.50, 1),
('E011', 'MOD054', '2024-2025', 17.00, 1),
('E011', 'MOD055', '2024-2025', 18.00, 1),
('E011', 'MOD056', '2024-2025', 16.50, 1),
('E011', 'MOD057', '2024-2025', 17.50, 1),
('E011', 'MOD058', '2024-2025', 18.50, 1),
('E011', 'MOD059', '2024-2025', 17.00, 1),
('E011', 'MOD060', '2024-2025', 18.00, 1),
('E012', 'MOD013', '2024-2025', 14.50, 1),
('E012', 'MOD014', '2024-2025', 15.00, 1),
('E012', 'MOD015', '2024-2025', 14.00, 1),
('E012', 'MOD016', '2024-2025', 15.50, 1),
('E012', 'MOD017', '2024-2025', 13.50, 1),
('E012', 'MOD018', '2024-2025', 14.50, 1);

-- --------------------------------------------------------

--
-- Structure de la table `module`
--

CREATE TABLE `module` (
  `id_module` varchar(10) NOT NULL,
  `nom_module` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `module`
--

INSERT INTO `module` (`id_module`, `nom_module`) VALUES
('MOD001', 'Algèbre 1'),
('MOD002', 'Analyse 1'),
('MOD003', 'Physique 1'),
('MOD004', 'Mécanique du point'),
('MOD005', 'Informatique 1'),
('MOD006', 'Langues 1 (Français / MTU)'),
('MOD007', 'Algèbre 2'),
('MOD008', 'Analyse 2'),
('MOD009', 'Physique 2'),
('MOD010', 'Chimie'),
('MOD011', 'Maple/Matlab'),
('MOD012', 'Langues 2 (Français / Anglais)'),
('MOD013', 'Algèbre 3'),
('MOD014', 'Analyse 3'),
('MOD015', 'Mécanique du solide'),
('MOD016', 'Thermodynamique'),
('MOD017', 'Électronique'),
('MOD018', 'Informatique 2'),
('MOD019', 'Langues 3 (Français / Anglais)'),
('MOD020', 'Analyse 4'),
('MOD021', 'Physique 3'),
('MOD022', 'Physique 4'),
('MOD023', 'Math appliquées'),
('MOD024', 'Langues 4 (Activité d\'ouverture / Anglais)'),
('MOD025', 'Probabilités et statistiques avancées'),
('MOD026', 'Théorie des Graphes et Recherche Opérationnelle'),
('MOD027', 'Architecture des Ordinateurs & Assembleur'),
('MOD028', 'Bases de Données Relationnelles'),
('MOD029', 'Réseaux Informatiques'),
('MOD030', 'Structure de Données en C'),
('MOD031', 'Langues étrangères 1'),
('MOD032', 'Digital Skills'),
('MOD033', 'Systèmes d\'Exploitation et Linux'),
('MOD034', 'Modélisation Orientée Objet'),
('MOD035', 'Technologie Enregistrement et Compilation'),
('MOD036', 'Développement Web'),
('MOD037', 'Programmation Orientée Objet Java'),
('MOD038', 'Langues étrangères 2'),
('MOD039', 'Culture & Arts & Sport Skills'),
('MOD040', 'Administration des Bases de Données Relationnelles'),
('MOD041', 'Développement Web Avancé'),
('MOD042', 'Réseaux Informatiques Avancés'),
('MOD043', 'Méthodologies et Génie Logiciel'),
('MOD044', 'Technologie DotNet'),
('MOD045', 'Langues étrangères 3'),
('MOD046', 'Power Skills : IA et éthique'),
('MOD047', 'Sécurité Informatique'),
('MOD048', 'Machine Learning'),
('MOD049', 'Administration Systèmes, Services et Sécurité Réseaux'),
('MOD050', 'Java Entreprise Edition'),
('MOD051', 'Microservices et Développement Mobile'),
('MOD052', 'Langues étrangères 4'),
('MOD053', 'Gestion de projet et entreprise'),
('MOD054', 'Frameworks Technologies Web'),
('MOD055', 'Big Data & Analytics'),
('MOD056', 'Systèmes de Planification des Ressources d\'Entreprise (ERP)'),
('MOD057', 'Urbanisme des Systèmes d\'Information'),
('MOD058', 'Deep Learning'),
('MOD059', 'Langues étrangères 5'),
('MOD060', 'Employment Skills'),
('MOD061', 'Stage PFE - Projet de Fin d\'Études');

-- --------------------------------------------------------

--
-- Structure de la table `module_filiere`
--

CREATE TABLE `module_filiere` (
  `id_filiere` varchar(10) NOT NULL,
  `id_module` varchar(10) NOT NULL,
  `semestre` int(1) DEFAULT NULL COMMENT 'Semestre (1 à 6) où le module est enseigné'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `module_filiere`
--

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
('FIL003', 'MOD026', 1),
('FIL003', 'MOD027', 1),
('FIL003', 'MOD028', 1),
('FIL003', 'MOD029', 1),
('FIL003', 'MOD030', 1),
('FIL003', 'MOD031', 1),
('FIL003', 'MOD032', 1),
('FIL003', 'MOD033', 2),
('FIL003', 'MOD034', 2),
('FIL003', 'MOD035', 2),
('FIL003', 'MOD036', 2),
('FIL003', 'MOD037', 2),
('FIL003', 'MOD038', 2),
('FIL003', 'MOD039', 2),
('FIL003', 'MOD040', 3),
('FIL003', 'MOD041', 3),
('FIL003', 'MOD042', 3),
('FIL003', 'MOD043', 3),
('FIL003', 'MOD044', 3),
('FIL003', 'MOD045', 3),
('FIL003', 'MOD046', 3),
('FIL003', 'MOD047', 4),
('FIL003', 'MOD048', 4),
('FIL003', 'MOD049', 4),
('FIL003', 'MOD050', 4),
('FIL003', 'MOD051', 4),
('FIL003', 'MOD052', 4),
('FIL003', 'MOD053', 4),
('FIL003', 'MOD054', 5),
('FIL003', 'MOD055', 5),
('FIL003', 'MOD056', 5),
('FIL003', 'MOD057', 5),
('FIL003', 'MOD058', 5),
('FIL003', 'MOD059', 5),
('FIL003', 'MOD060', 5),
('FIL003', 'MOD061', 6);

-- --------------------------------------------------------

--
-- Structure de la table `module_prof`
--

CREATE TABLE `module_prof` (
  `id_prof` varchar(10) NOT NULL,
  `id_module` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `module_prof`
--

INSERT INTO `module_prof` (`id_prof`, `id_module`) VALUES
('P001', 'MOD001'),
('P001', 'MOD002'),
('P001', 'MOD007'),
('P001', 'MOD008'),
('P002', 'MOD003'),
('P002', 'MOD009'),
('P002', 'MOD021'),
('P003', 'MOD013'),
('P003', 'MOD014'),
('P003', 'MOD020'),
('P003', 'MOD023'),
('P003', 'MOD026'),
('P004', 'MOD015'),
('P004', 'MOD016'),
('P004', 'MOD022'),
('P005', 'MOD005'),
('P005', 'MOD018'),
('P005', 'MOD027'),
('P005', 'MOD030'),
('P005', 'MOD032'),
('P005', 'MOD051'),
('P005', 'MOD057'),
('P006', 'MOD010'),
('P007', 'MOD004'),
('P007', 'MOD015'),
('P007', 'MOD017'),
('P008', 'MOD033'),
('P008', 'MOD034'),
('P008', 'MOD037'),
('P008', 'MOD039'),
('P008', 'MOD048'),
('P008', 'MOD050'),
('P009', 'MOD006'),
('P009', 'MOD012'),
('P009', 'MOD019'),
('P009', 'MOD024'),
('P010', 'MOD031'),
('P010', 'MOD038'),
('P010', 'MOD045'),
('P010', 'MOD052'),
('P011', 'MOD028'),
('P011', 'MOD040'),
('P011', 'MOD044'),
('P011', 'MOD055'),
('P011', 'MOD058'),
('P011', 'MOD061'),
('P012', 'MOD035'),
('P012', 'MOD036'),
('P012', 'MOD041'),
('P012', 'MOD046'),
('P012', 'MOD054'),
('P013', 'MOD029'),
('P013', 'MOD042'),
('P013', 'MOD047'),
('P013', 'MOD049'),
('P013', 'MOD053'),
('P014', 'MOD043'),
('P014', 'MOD056'),
('P014', 'MOD060');

-- --------------------------------------------------------

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
-- Déchargement des données de la table `professeur`
--

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

-- --------------------------------------------------------

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
  `date_reponse` date DEFAULT NULL,
  `id_etudiant` varchar(10) NOT NULL,
  `id_administrateur` varchar(10) DEFAULT NULL,
  `id_demande` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `reclamations`
--

INSERT INTO `reclamations` (`id_reclamation`, `numero_reference`, `date_reclamation`, `description`, `objet`, `statut`, `reponse`, `date_reponse`, `id_etudiant`, `id_administrateur`, `id_demande`) VALUES
('R001', 'REC-2024-001', '2024-09-18', 'Ma demande d\'attestation de scolarité n\'a pas été traitée depuis plus de 10 jours. J\'en ai besoin pour une bourse.', 'Retard traitement attestation', 'en attente', NULL, NULL, 'E002', NULL, 'D005'),
('R002', 'REC-2024-002', '2024-09-14', 'Le relevé de notes que j\'ai reçu ne contient pas toutes les notes du semestre 5.', 'Relevé de notes incomplet', 'en attente', NULL, NULL, 'E006', 'ADM002', 'D007'),
('R003', 'REC-2024-003', '2024-09-25', 'Ma demande de convention de stage est en attente alors que mon stage commence dans 2 semaines.', 'Urgence convention de stage', 'en attente', NULL, NULL, 'E005', NULL, 'D008');

-- --------------------------------------------------------

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
-- Déchargement des données de la table `releves_notes`
--

INSERT INTO `releves_notes` (`id_releve`, `annee_universitaire`, `semestre`, `id_demande`) VALUES
('RN001', '2024-2025', 'S1', 'D003'),
('RN002', '2024-2025', 'S5', 'D007'),
('RN003', '2024-2025', 'S1', 'D010');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `administrateurs`
--
ALTER TABLE `administrateurs`
  ADD PRIMARY KEY (`id_administrateur`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `login` (`login`);

--
-- Index pour la table `annee_universitaire`
--
ALTER TABLE `annee_universitaire`
  ADD PRIMARY KEY (`id_annee`);

--
-- Index pour la table `attestations_reussite`
--
ALTER TABLE `attestations_reussite`
  ADD PRIMARY KEY (`id_attestation`),
  ADD UNIQUE KEY `id_demande` (`id_demande`);

--
-- Index pour la table `conventions_stage`
--
ALTER TABLE `conventions_stage`
  ADD PRIMARY KEY (`id_convention`),
  ADD UNIQUE KEY `id_demande` (`id_demande`),
  ADD KEY `id_prof_encadrant` (`id_prof_encadrant`);

--
-- Index pour la table `demandes`
--
ALTER TABLE `demandes`
  ADD PRIMARY KEY (`id_demande`),
  ADD UNIQUE KEY `numero_reference` (`numero_reference`),
  ADD KEY `id_etudiant` (`id_etudiant`),
  ADD KEY `id_administrateur` (`id_administrateur`);

--
-- Index pour la table `etudiants`
--
ALTER TABLE `etudiants`
  ADD PRIMARY KEY (`id_etudiant`),
  ADD UNIQUE KEY `cin` (`cin`),
  ADD UNIQUE KEY `numero_apogee` (`numero_apogee`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `filiere`
--
ALTER TABLE `filiere`
  ADD PRIMARY KEY (`id_filiere`);

--
-- Index pour la table `inscription_etudiant`
--
ALTER TABLE `inscription_etudiant`
  ADD PRIMARY KEY (`id_etudiant`,`id_filiere`,`id_annee`),
  ADD KEY `id_filiere` (`id_filiere`),
  ADD KEY `id_annee` (`id_annee`);

--
-- Index pour la table `inscrit_module`
--
ALTER TABLE `inscrit_module`
  ADD PRIMARY KEY (`id_etudiant`,`id_module`,`session`),
  ADD KEY `id_module` (`id_module`);

--
-- Index pour la table `module`
--
ALTER TABLE `module`
  ADD PRIMARY KEY (`id_module`);

--
-- Index pour la table `module_filiere`
--
ALTER TABLE `module_filiere`
  ADD PRIMARY KEY (`id_filiere`,`id_module`),
  ADD KEY `id_module` (`id_module`);

--
-- Index pour la table `module_prof`
--
ALTER TABLE `module_prof`
  ADD PRIMARY KEY (`id_prof`,`id_module`),
  ADD KEY `id_module` (`id_module`);

--
-- Index pour la table `professeur`
--
ALTER TABLE `professeur`
  ADD PRIMARY KEY (`id_prof`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `reclamations`
--
ALTER TABLE `reclamations`
  ADD PRIMARY KEY (`id_reclamation`),
  ADD UNIQUE KEY `numero_reference` (`numero_reference`),
  ADD UNIQUE KEY `id_demande` (`id_demande`),
  ADD KEY `id_etudiant` (`id_etudiant`),
  ADD KEY `id_administrateur` (`id_administrateur`);

--
-- Index pour la table `releves_notes`
--
ALTER TABLE `releves_notes`
  ADD PRIMARY KEY (`id_releve`),
  ADD UNIQUE KEY `id_demande` (`id_demande`);

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `attestations_reussite`
--
ALTER TABLE `attestations_reussite`
  ADD CONSTRAINT `attestations_reussite_ibfk_1` FOREIGN KEY (`id_demande`) REFERENCES `demandes` (`id_demande`) ON DELETE CASCADE;

--
-- Contraintes pour la table `conventions_stage`
--
ALTER TABLE `conventions_stage`
  ADD CONSTRAINT `conventions_stage_ibfk_1` FOREIGN KEY (`id_demande`) REFERENCES `demandes` (`id_demande`) ON DELETE CASCADE,
  ADD CONSTRAINT `conventions_stage_ibfk_2` FOREIGN KEY (`id_prof_encadrant`) REFERENCES `professeur` (`id_prof`) ON DELETE CASCADE;

--
-- Contraintes pour la table `demandes`
--
ALTER TABLE `demandes`
  ADD CONSTRAINT `demandes_ibfk_1` FOREIGN KEY (`id_etudiant`) REFERENCES `etudiants` (`id_etudiant`) ON DELETE CASCADE,
  ADD CONSTRAINT `demandes_ibfk_2` FOREIGN KEY (`id_administrateur`) REFERENCES `administrateurs` (`id_administrateur`) ON DELETE SET NULL;

--
-- Contraintes pour la table `inscription_etudiant`
--
ALTER TABLE `inscription_etudiant`
  ADD CONSTRAINT `inscription_etudiant_ibfk_1` FOREIGN KEY (`id_etudiant`) REFERENCES `etudiants` (`id_etudiant`) ON DELETE CASCADE,
  ADD CONSTRAINT `inscription_etudiant_ibfk_2` FOREIGN KEY (`id_filiere`) REFERENCES `filiere` (`id_filiere`) ON DELETE CASCADE,
  ADD CONSTRAINT `inscription_etudiant_ibfk_3` FOREIGN KEY (`id_annee`) REFERENCES `annee_universitaire` (`id_annee`);

--
-- Contraintes pour la table `inscrit_module`
--
ALTER TABLE `inscrit_module`
  ADD CONSTRAINT `inscrit_module_ibfk_1` FOREIGN KEY (`id_etudiant`) REFERENCES `etudiants` (`id_etudiant`) ON DELETE CASCADE,
  ADD CONSTRAINT `inscrit_module_ibfk_2` FOREIGN KEY (`id_module`) REFERENCES `module` (`id_module`) ON DELETE CASCADE;

--
-- Contraintes pour la table `module_filiere`
--
ALTER TABLE `module_filiere`
  ADD CONSTRAINT `module_filiere_ibfk_1` FOREIGN KEY (`id_filiere`) REFERENCES `filiere` (`id_filiere`) ON DELETE CASCADE,
  ADD CONSTRAINT `module_filiere_ibfk_2` FOREIGN KEY (`id_module`) REFERENCES `module` (`id_module`) ON DELETE CASCADE;

--
-- Contraintes pour la table `module_prof`
--
ALTER TABLE `module_prof`
  ADD CONSTRAINT `module_prof_ibfk_1` FOREIGN KEY (`id_prof`) REFERENCES `professeur` (`id_prof`) ON DELETE CASCADE,
  ADD CONSTRAINT `module_prof_ibfk_2` FOREIGN KEY (`id_module`) REFERENCES `module` (`id_module`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reclamations`
--
ALTER TABLE `reclamations`
  ADD CONSTRAINT `reclamations_ibfk_1` FOREIGN KEY (`id_demande`) REFERENCES `demandes` (`id_demande`) ON DELETE CASCADE,
  ADD CONSTRAINT `reclamations_ibfk_2` FOREIGN KEY (`id_etudiant`) REFERENCES `etudiants` (`id_etudiant`) ON DELETE CASCADE,
  ADD CONSTRAINT `reclamations_ibfk_3` FOREIGN KEY (`id_administrateur`) REFERENCES `administrateurs` (`id_administrateur`) ON DELETE SET NULL;

--
-- Contraintes pour la table `releves_notes`
--
ALTER TABLE `releves_notes`
  ADD CONSTRAINT `releves_notes_ibfk_1` FOREIGN KEY (`id_demande`) REFERENCES `demandes` (`id_demande`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
