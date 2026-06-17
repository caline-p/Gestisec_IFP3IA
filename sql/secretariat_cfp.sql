-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 22 mai 2026 à 14:00
-- Version du serveur : 10.4.24-MariaDB
-- Version de PHP : 8.1.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `secretariat_cfp`
--

-- --------------------------------------------------------

--
-- Structure de la table `apprenants`
--

CREATE TABLE `apprenants` (
  `id` int(11) NOT NULL,
  `matricule` varchar(30) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `date_naissance` date DEFAULT NULL,
  `sexe` enum('M','F') DEFAULT 'M',
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `filiere_id` int(11) DEFAULT NULL,
  `niveau` varchar(20) DEFAULT '1',
  `statut` enum('inscrit','suspendu','diplome','abandonne') DEFAULT 'inscrit',
  `date_inscription` date DEFAULT curdate(),
  `photo` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `lieu_naissance` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `apprenants`
--

INSERT INTO `apprenants` (`id`, `matricule`, `nom`, `prenom`, `date_naissance`, `sexe`, `telephone`, `email`, `adresse`, `filiere_id`, `niveau`, `statut`, `date_inscription`, `photo`, `created_at`, `lieu_naissance`) VALUES
(1, 'APP-2026-0001', 'NGAH', 'Denise', '2007-05-06', 'F', '652192354', 'ngah@institut3ia.com', 'foreke', 5, '1', 'inscrit', '2026-04-22', NULL, '2026-04-22 12:00:01', NULL),
(2, 'APP-2026-0002', 'DONFACK', 'Caline', '2025-12-16', 'F', '650195540', 'donfack@institut3ia.com', 'Yaounde', 2, '2', 'inscrit', '2026-04-23', NULL, '2026-04-23 13:52:35', NULL),
(3, 'CM-3IA-26INFOG0001', 'MELI', 'LOGAN', '2026-04-30', 'F', '650195540', 'rayangoune@gmail.com', 'AKWA', 4, '1', 'inscrit', '2026-04-30', NULL, '2026-04-30 15:47:44', 'KALIT'),
(4, 'CM-3IA-26MSRI0001', 'tejeuteu', 'durand', '2026-05-05', 'M', '650195540', 'rayangoune@gmail.com', 'AKWA', 6, '1', 'inscrit', '2026-05-05', NULL, '2026-05-05 12:07:45', 'KALIT');

-- --------------------------------------------------------

--
-- Structure de la table `attestation`
--

CREATE TABLE `attestation` (
  `id` int(20) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `date_birth` varchar(100) DEFAULT NULL,
  `place_birth` varchar(100) DEFAULT NULL,
  `specialty` varchar(100) DEFAULT NULL,
  `start_date` varchar(100) DEFAULT NULL,
  `end_date` varchar(100) DEFAULT NULL,
  `origin` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `conges`
--

CREATE TABLE `conges` (
  `id` int(11) NOT NULL,
  `personnel_id` int(11) NOT NULL,
  `type_conge` enum('annuel','maladie','maternite','autre') DEFAULT 'annuel',
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `nb_jours` int(11) NOT NULL,
  `motif` text DEFAULT NULL,
  `statut` enum('en_attente','approuve','refuse') DEFAULT 'en_attente',
  `approuve_par` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `courriers`
--

CREATE TABLE `courriers` (
  `id` int(11) NOT NULL,
  `reference` varchar(50) NOT NULL,
  `type` enum('entrant','sortant') DEFAULT 'entrant',
  `objet` text NOT NULL,
  `expediteur` varchar(200) DEFAULT NULL,
  `destinataire` varchar(200) DEFAULT NULL,
  `date_courrier` date DEFAULT curdate(),
  `date_reception` date DEFAULT NULL,
  `statut` enum('recu','en_traitement','traite','archive') DEFAULT 'recu',
  `priorite` enum('normale','urgente','confidentielle') DEFAULT 'normale',
  `affecte_a` int(11) DEFAULT NULL,
  `observations` text DEFAULT NULL,
  `fichier` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `courriers`
--

INSERT INTO `courriers` (`id`, `reference`, `type`, `objet`, `expediteur`, `destinataire`, `date_courrier`, `date_reception`, `statut`, `priorite`, `affecte_a`, `observations`, `fichier`, `created_at`) VALUES
(1, 'COU-20260423-0EA1F', 'entrant', 'livraison d\'un ordinateur portable', 'ETS-SIR0TECH', 'Informatique', '2026-04-23', '2026-04-23', 'recu', 'urgente', 1, '', NULL, '2026-04-23 14:02:35'),
(2, 'COU-20260423-3BE70', 'sortant', 'invitation a un seminaire d\'orientation Informatique', 'ETS-SIR-TECH', 'Proviseur du lycee Bilingue de Dschang', '2026-04-23', '2026-04-23', 'recu', 'confidentielle', 1, '', NULL, '2026-04-23 14:05:34');

-- --------------------------------------------------------

--
-- Structure de la table `depenses`
--

CREATE TABLE `depenses` (
  `id` int(11) NOT NULL,
  `libelle` varchar(200) NOT NULL,
  `montant` decimal(12,2) NOT NULL,
  `categorie` varchar(100) DEFAULT NULL,
  `date_depense` date DEFAULT curdate(),
  `justificatif` varchar(255) DEFAULT NULL,
  `saisi_par` int(11) DEFAULT NULL,
  `observations` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `filieres`
--

CREATE TABLE `filieres` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `nom` varchar(150) NOT NULL,
  `duree` int(11) NOT NULL COMMENT 'durée en mois',
  `cout` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `filieres`
--

INSERT INTO `filieres` (`id`, `code`, `nom`, `duree`, `cout`) VALUES
(1, 'MAINT', 'Maintenance Informatique', 24, '350000.00'),
(2, 'DEV', 'Développement Logiciel', 24, '400000.00'),
(3, 'RESEAU', 'Réseaux et Télécommunications', 24, '380000.00'),
(4, 'INFOG', 'Infographie et Multimédia', 12, '280000.00'),
(5, 'BUREAUT', 'Bureautique et Secrétariat', 12, '250000.00'),
(6, 'MSRI', 'MAINTENANCE DES SYSTEMES ET RESEAUX INFORMATIQUES', 12, '360000.00');

-- --------------------------------------------------------

--
-- Structure de la table `paiements`
--

CREATE TABLE `paiements` (
  `id` int(11) NOT NULL,
  `reference` varchar(50) NOT NULL,
  `apprenant_id` int(11) NOT NULL,
  `montant` decimal(12,2) NOT NULL,
  `mode_paiement` enum('especes','mobile_money','virement','cheque') DEFAULT 'especes',
  `date_paiement` date DEFAULT curdate(),
  `mois_concerne` varchar(20) DEFAULT NULL,
  `observations` text DEFAULT NULL,
  `enregistre_par` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `paiements`
--

INSERT INTO `paiements` (`id`, `reference`, `apprenant_id`, `montant`, `mode_paiement`, `date_paiement`, `mois_concerne`, `observations`, `enregistre_par`, `created_at`) VALUES
(1, 'PAI-20260422-170C3', 1, '130000.00', 'especes', '2026-04-22', 'April 2026', '', 3, '2026-04-22 12:56:56');

-- --------------------------------------------------------

--
-- Structure de la table `personnel`
--

CREATE TABLE `personnel` (
  `id` int(11) NOT NULL,
  `matricule` varchar(30) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `poste` varchar(100) NOT NULL,
  `departement` varchar(100) DEFAULT NULL,
  `type_contrat` enum('CDI','CDD','vacataire','stage') DEFAULT 'CDI',
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `date_embauche` date DEFAULT NULL,
  `salaire` decimal(12,2) DEFAULT 0.00,
  `solde_conge` int(11) DEFAULT 30,
  `statut` enum('actif','inactif') DEFAULT 'actif',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `personnel`
--

INSERT INTO `personnel` (`id`, `matricule`, `nom`, `prenom`, `poste`, `departement`, `type_contrat`, `telephone`, `email`, `date_embauche`, `salaire`, `solde_conge`, `statut`, `created_at`) VALUES
(1, 'PERS-2026-0001', 'prisca', 'caline', 'Comptable', 'Administration', 'vacataire', '650195540', 'donfackcaline@gmail.com', '2026-04-22', '60.00', 30, 'actif', '2026-04-22 11:55:46');

-- --------------------------------------------------------

--
-- Structure de la table `plannings`
--

CREATE TABLE `plannings` (
  `id` int(11) NOT NULL,
  `titre` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('rendez_vous','reunion','deplacement','echeance') DEFAULT 'rendez_vous',
  `date_debut` datetime NOT NULL,
  `date_fin` datetime DEFAULT NULL,
  `lieu` varchar(200) DEFAULT NULL,
  `participant` varchar(200) DEFAULT NULL,
  `statut` enum('planifie','confirme','annule','effectue') DEFAULT 'planifie',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `plannings`
--

INSERT INTO `plannings` (`id`, `titre`, `description`, `type`, `date_debut`, `date_fin`, `lieu`, `participant`, `statut`, `created_at`) VALUES
(1, 'evaluation normale du deuxieme trimestre', 'cette reunion a pour objectif de preparer les evaluations pour donner tout ce qu\'il faut prevoir', 'reunion', '2026-04-27 13:56:00', '2026-04-30 13:56:00', 'CAMPUS B IFP-3IA', 'Directeur General, Directeur des Affaires Academique, Secretaire General, Responsable des departements, Formateurss', 'planifie', '2026-04-23 13:59:38');

-- --------------------------------------------------------

--
-- Structure de la table `presences`
--

CREATE TABLE `presences` (
  `id` int(11) NOT NULL,
  `apprenant_id` int(11) NOT NULL,
  `date_presence` date NOT NULL,
  `statut` enum('present','absent','retard','excuse') DEFAULT 'present'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `reunions`
--

CREATE TABLE `reunions` (
  `id` int(11) NOT NULL,
  `titre` varchar(200) NOT NULL,
  `date_reunion` datetime NOT NULL,
  `lieu` varchar(200) DEFAULT NULL,
  `ordre_du_jour` text DEFAULT NULL,
  `compte_rendu` text DEFAULT NULL,
  `statut` enum('planifiee','tenue','annulee') DEFAULT 'planifiee',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `reunions`
--

INSERT INTO `reunions` (`id`, `titre`, `date_reunion`, `lieu`, `ordre_du_jour`, `compte_rendu`, `statut`, `created_at`) VALUES
(1, 'ASSEMBLEE GENERALE', '2026-03-22 12:51:00', 'CAMPUS B IFP-3IA', 'GYT7RTY67TYUGFYUFTRYUFTRY6UT7\r\nTFD5TRD5TR6T\r\nTYR5TR67T\r\nY7T67T7TR7T8\r\n8TY87YT8Y89Y7\r\nU89YU89', NULL, 'tenue', '2026-04-22 12:54:26');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role` enum('directeur','secretaire','comptable','admin') DEFAULT 'secretaire',
  `statut` enum('actif','inactif') DEFAULT 'actif',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `prenom`, `email`, `mot_de_passe`, `role`, `statut`, `created_at`) VALUES
(1, 'Administrateur', 'Système', 'admin@cfp.cm', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'actif', '2026-04-22 11:49:39'),
(2, 'DIRECTEUR', 'Jean', 'directeur@cfp.cm', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'directeur', 'actif', '2026-04-22 11:49:39'),
(3, 'SECRÉTAIRE', 'Marie', 'secretaire@cfp.cm', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'secretaire', 'actif', '2026-04-22 11:49:39');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `apprenants`
--
ALTER TABLE `apprenants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `matricule` (`matricule`),
  ADD KEY `filiere_id` (`filiere_id`);

--
-- Index pour la table `attestation`
--
ALTER TABLE `attestation`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `conges`
--
ALTER TABLE `conges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `personnel_id` (`personnel_id`),
  ADD KEY `approuve_par` (`approuve_par`);

--
-- Index pour la table `courriers`
--
ALTER TABLE `courriers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference` (`reference`),
  ADD KEY `affecte_a` (`affecte_a`);

--
-- Index pour la table `depenses`
--
ALTER TABLE `depenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `saisi_par` (`saisi_par`);

--
-- Index pour la table `filieres`
--
ALTER TABLE `filieres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Index pour la table `paiements`
--
ALTER TABLE `paiements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference` (`reference`),
  ADD KEY `apprenant_id` (`apprenant_id`),
  ADD KEY `enregistre_par` (`enregistre_par`);

--
-- Index pour la table `personnel`
--
ALTER TABLE `personnel`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `matricule` (`matricule`);

--
-- Index pour la table `plannings`
--
ALTER TABLE `plannings`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `presences`
--
ALTER TABLE `presences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_presence` (`apprenant_id`,`date_presence`);

--
-- Index pour la table `reunions`
--
ALTER TABLE `reunions`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `apprenants`
--
ALTER TABLE `apprenants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `attestation`
--
ALTER TABLE `attestation`
  MODIFY `id` int(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `conges`
--
ALTER TABLE `conges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `courriers`
--
ALTER TABLE `courriers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `depenses`
--
ALTER TABLE `depenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `filieres`
--
ALTER TABLE `filieres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `paiements`
--
ALTER TABLE `paiements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `personnel`
--
ALTER TABLE `personnel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `plannings`
--
ALTER TABLE `plannings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `presences`
--
ALTER TABLE `presences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `reunions`
--
ALTER TABLE `reunions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `apprenants`
--
ALTER TABLE `apprenants`
  ADD CONSTRAINT `apprenants_ibfk_1` FOREIGN KEY (`filiere_id`) REFERENCES `filieres` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `conges`
--
ALTER TABLE `conges`
  ADD CONSTRAINT `conges_ibfk_1` FOREIGN KEY (`personnel_id`) REFERENCES `personnel` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conges_ibfk_2` FOREIGN KEY (`approuve_par`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `courriers`
--
ALTER TABLE `courriers`
  ADD CONSTRAINT `courriers_ibfk_1` FOREIGN KEY (`affecte_a`) REFERENCES `personnel` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `depenses`
--
ALTER TABLE `depenses`
  ADD CONSTRAINT `depenses_ibfk_1` FOREIGN KEY (`saisi_par`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `paiements`
--
ALTER TABLE `paiements`
  ADD CONSTRAINT `paiements_ibfk_1` FOREIGN KEY (`apprenant_id`) REFERENCES `apprenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `paiements_ibfk_2` FOREIGN KEY (`enregistre_par`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `presences`
--
ALTER TABLE `presences`
  ADD CONSTRAINT `presences_ibfk_1` FOREIGN KEY (`apprenant_id`) REFERENCES `apprenants` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
