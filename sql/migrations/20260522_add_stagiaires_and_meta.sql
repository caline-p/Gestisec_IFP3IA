-- Migration: Add stagiaires_externes and attestation_meta tables
-- Run this SQL once against the database `secretariat_cfp`.

CREATE TABLE IF NOT EXISTS `stagiaires_externes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(150) NOT NULL,
  `prenom` VARCHAR(150) DEFAULT NULL,
  `etablissement` VARCHAR(255) DEFAULT NULL,
  `specialty` VARCHAR(150) DEFAULT NULL,
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `type` ENUM('academique','professionnel') DEFAULT 'academique',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `attestation_meta` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `attestation_id` INT NOT NULL,
  `category` ENUM('etudiant','stagiaire_academique','stagiaire_professionnel') NOT NULL DEFAULT 'etudiant',
  `apprenant_id` INT DEFAULT NULL,
  `stagiaire_externe_id` INT DEFAULT NULL,
  `template` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY (`attestation_id`),
  KEY (`apprenant_id`),
  KEY (`stagiaire_externe_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional foreign keys (enable if desired)
-- ALTER TABLE attestation_meta ADD CONSTRAINT fk_att_meta_att FOREIGN KEY (attestation_id) REFERENCES attestation(id) ON DELETE CASCADE;
-- ALTER TABLE attestation_meta ADD CONSTRAINT fk_att_meta_app FOREIGN KEY (apprenant_id) REFERENCES apprenants(id) ON DELETE SET NULL;
-- ALTER TABLE attestation_meta ADD CONSTRAINT fk_att_meta_stag FOREIGN KEY (stagiaire_externe_id) REFERENCES stagiaires_externes(id) ON DELETE SET NULL;
