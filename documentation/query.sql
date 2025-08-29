-- MySQL Script generated from current Stampee Database
-- Current Database Structure as of 2024-12-19
-- Based on actual running database analysis

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema stampee_db
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `stampee_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
USE `stampee_db` ;

-- -----------------------------------------------------
-- Table `stampee_db`.`membre`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `stampee_db`.`membre` (
  `id_membre` int NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier for the member',
  `nom_utilisateur` varchar(100) NOT NULL COMMENT 'Unique username for the member',
  `courriel` varchar(255) NOT NULL COMMENT 'Email address of the member',
  `mot_de_passe` varchar(255) NOT NULL COMMENT 'Hashed password of the member',
  `historique_offres` text COMMENT 'JSON string storing the member''s bidding history (can be normalized later)',
  `profil_acheteur` text COMMENT 'JSON string storing relevant buyer profile information (can be normalized later)',
  `date_inscription` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Date and time of member registration',
  `is_admin` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id_membre`),
  UNIQUE KEY `nom_utilisateur` (`nom_utilisateur`),
  UNIQUE KEY `courriel` (`courriel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Table to store user accounts and profiles.';

-- -----------------------------------------------------
-- Table `stampee_db`.`timbre`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `stampee_db`.`timbre` (
  `id_timbre` int NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier for the stamp',
  `nom` varchar(255) NOT NULL COMMENT 'Name of the stamp',
  `date_creation` date DEFAULT NULL COMMENT 'Date the stamp was created or issued',
  `couleurs` varchar(255) DEFAULT NULL COMMENT 'Description of the stamp colors',
  `pays_origine` varchar(100) DEFAULT NULL COMMENT 'Country of origin of the stamp',
  `image_principale` varchar(255) DEFAULT NULL COMMENT 'URL or path to the main image of the stamp',
  `images_supplementaires` text COMMENT 'JSON array of URLs or paths to additional stamp images',
  `condition` enum('Parfaite','Excellente','Bonne','Moyenne','Endommagé') DEFAULT NULL COMMENT 'Physical condition of the stamp',
  `tirage` int DEFAULT NULL COMMENT 'Number of copies produced (edition size)',
  `dimensions` varchar(50) DEFAULT NULL COMMENT 'Physical dimensions of the stamp (e.g., "25x30mm")',
  `certifie` tinyint(1) DEFAULT '0' COMMENT 'Indicates if the stamp is certified (TRUE/FALSE)',
  PRIMARY KEY (`id_timbre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Table to store details about collectible stamps.';

-- -----------------------------------------------------
-- Table `stampee_db`.`images`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `stampee_db`.`images` (
  `id_image` int NOT NULL AUTO_INCREMENT,
  `id_timbre` int NOT NULL,
  `chemin` varchar(255) NOT NULL,
  `est_principale` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id_image`),
  KEY `id_timbre` (`id_timbre`),
  CONSTRAINT `images_ibfk_1` FOREIGN KEY (`id_timbre`) REFERENCES `stampee_db`.`timbre` (`id_timbre`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- -----------------------------------------------------
-- Table `stampee_db`.`enchere`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `stampee_db`.`enchere` (
  `id_enchere` int NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier for the auction',
  `id_timbre` int NOT NULL COMMENT 'Foreign key to Timbre table (one-to-one relationship)',
  `id_membre` int NOT NULL COMMENT 'Foreign key to Membre table (the member who published the auction)',
  `date_debut` datetime NOT NULL COMMENT 'Start date and time of the auction',
  `date_fin` datetime NOT NULL COMMENT 'End date and time of the auction',
  `prix_plancher` decimal(10,2) NOT NULL COMMENT 'Minimum bid price for the auction',
  `coup_de_coeur_lord` tinyint(1) DEFAULT '0' COMMENT 'Indicates if the auction is a Lord''s favorite (TRUE/FALSE)',
  `statut` enum('Active','Archivée','Terminée') DEFAULT 'Active' COMMENT 'Current status of the auction',
  PRIMARY KEY (`id_enchere`),
  UNIQUE KEY `id_timbre` (`id_timbre`),
  KEY `fk_enchere_membre` (`id_membre`),
  CONSTRAINT `fk_enchere_membre` FOREIGN KEY (`id_membre`) REFERENCES `stampee_db`.`membre` (`id_membre`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_enchere_timbre` FOREIGN KEY (`id_timbre`) REFERENCES `stampee_db`.`timbre` (`id_timbre`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Table to manage auction details for stamps.';

-- -----------------------------------------------------
-- Table `stampee_db`.`offre`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `stampee_db`.`offre` (
  `id_offre` int NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier for the bid',
  `id_enchere` int NOT NULL COMMENT 'Foreign key to Enchere table',
  `id_membre` int NOT NULL COMMENT 'Foreign key to Membre table (the member who placed the bid)',
  `montant` decimal(10,2) NOT NULL COMMENT 'Amount of the bid',
  `date_offre` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Date and time the bid was placed',
  PRIMARY KEY (`id_offre`),
  KEY `fk_offre_enchere` (`id_enchere`),
  KEY `fk_offre_membre` (`id_membre`),
  CONSTRAINT `fk_offre_enchere` FOREIGN KEY (`id_enchere`) REFERENCES `stampee_db`.`enchere` (`id_enchere`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_offre_membre` FOREIGN KEY (`id_membre`) REFERENCES `stampee_db`.`membre` (`id_membre`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Table to record bids placed on auctions.';

-- -----------------------------------------------------
-- Table `stampee_db`.`favoris`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `stampee_db`.`favoris` (
  `id_membre` int NOT NULL COMMENT 'Foreign key to Membre table',
  `id_enchere` int NOT NULL COMMENT 'Foreign key to Enchere table',
  `date_ajout` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Date and time the auction was added to favorites',
  PRIMARY KEY (`id_membre`,`id_enchere`),
  KEY `fk_favoris_enchere` (`id_enchere`),
  CONSTRAINT `fk_favoris_enchere` FOREIGN KEY (`id_enchere`) REFERENCES `stampee_db`.`enchere` (`id_enchere`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_favoris_membre` FOREIGN KEY (`id_membre`) REFERENCES `stampee_db`.`membre` (`id_membre`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Table to store member favorite auctions.';

-- -----------------------------------------------------
-- Table `stampee_db`.`commentaires`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `stampee_db`.`commentaires` (
  `id_commentaire` int NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier for the comment',
  `id_enchere` int NOT NULL COMMENT 'Foreign key to Enchere table (archived auctions only)',
  `id_membre` int NOT NULL COMMENT 'Foreign key to Membre table (the member who wrote the comment)',
  `contenu` text NOT NULL COMMENT 'Content of the comment',
  `note` tinyint(1) DEFAULT NULL COMMENT 'Rating from 1 to 5 (optional)',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Date and time the comment was created',
  `date_modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Date and time the comment was last modified',
  `approuve` tinyint(1) DEFAULT '1' COMMENT 'Whether the comment is approved (moderation)',
  PRIMARY KEY (`id_commentaire`),
  KEY `fk_commentaire_enchere` (`id_enchere`),
  KEY `fk_commentaire_membre` (`id_membre`),
  KEY `idx_date_creation` (`date_creation`),
  KEY `idx_approuve` (`approuve`),
  CONSTRAINT `fk_commentaire_enchere` FOREIGN KEY (`id_enchere`) REFERENCES `stampee_db`.`enchere` (`id_enchere`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_commentaire_membre` FOREIGN KEY (`id_membre`) REFERENCES `stampee_db`.`membre` (`id_membre`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_note` CHECK (`note` >= 1 AND `note` <= 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Table to store comments and ratings for archived auctions.';

-- -----------------------------------------------------
-- Table `stampee_db`.`categories_timbres`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `stampee_db`.`categories_timbres` (
  `id_categorie` int NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier for the category',
  `nom_categorie` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Name of the category',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'Description of the category',
  `categorie_parent` int DEFAULT NULL COMMENT 'Parent category ID for hierarchical categories',
  `icone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Icon path for the category',
  `actif` tinyint(1) DEFAULT '1' COMMENT 'Whether the category is active',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Date when category was created',
  PRIMARY KEY (`id_categorie`),
  UNIQUE KEY `uk_nom_categorie` (`nom_categorie`),
  KEY `fk_categorie_parent_idx` (`categorie_parent`),
  KEY `idx_actif` (`actif`),
  CONSTRAINT `fk_categorie_parent` FOREIGN KEY (`categorie_parent`) REFERENCES `stampee_db`.`categories_timbres` (`id_categorie`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table to categorize stamps for better organization.';

-- -----------------------------------------------------
-- Table `stampee_db`.`timbres_categories`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `stampee_db`.`timbres_categories` (
  `id_timbre` int NOT NULL COMMENT 'Foreign key to Timbre table',
  `id_categorie` int NOT NULL COMMENT 'Foreign key to Categories table',
  `date_ajout` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Date when category was assigned',
  PRIMARY KEY (`id_timbre`,`id_categorie`),
  KEY `fk_timbres_categories_categorie_idx` (`id_categorie`),
  CONSTRAINT `fk_timbres_categories_categorie` FOREIGN KEY (`id_categorie`) REFERENCES `stampee_db`.`categories_timbres` (`id_categorie`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_timbres_categories_timbre` FOREIGN KEY (`id_timbre`) REFERENCES `stampee_db`.`timbre` (`id_timbre`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Junction table linking stamps to categories (many-to-many relationship).';

-- -----------------------------------------------------
-- Table `stampee_db`.`historique_actions`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `stampee_db`.`historique_actions` (
  `id_action` int NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier for the action',
  `id_membre` int DEFAULT NULL COMMENT 'Foreign key to Membre table (NULL if anonymous)',
  `type_action` enum('connexion','inscription','creation_enchere','placement_offre','ajout_favoris','modification_profil','suppression_compte') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Type of action performed',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'Description of the action',
  `ip_adresse` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IP address of the user',
  `user_agent` text COLLATE utf8mb4_unicode_ci COMMENT 'User agent string',
  `date_action` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Date and time of the action',
  `donnees_supplementaires` json DEFAULT NULL COMMENT 'Additional data related to the action',
  PRIMARY KEY (`id_action`),
  KEY `fk_historique_membre_idx` (`id_membre`),
  KEY `idx_type_action` (`type_action`),
  KEY `idx_date_action` (`date_action`),
  KEY `idx_ip_adresse` (`ip_adresse`),
  CONSTRAINT `fk_historique_membre` FOREIGN KEY (`id_membre`) REFERENCES `stampee_db`.`membre` (`id_membre`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table to log user actions for audit and analytics.';

-- -----------------------------------------------------
-- Insert default categories
-- -----------------------------------------------------
INSERT INTO `stampee_db`.`categories_timbres` (`nom_categorie`, `description`) VALUES
('Classiques', 'Timbre classiques et traditionnels'),
('Commemoratifs', 'Timbre commémoratifs et événementiels'),
('Thématiques', 'Timbre avec des thèmes spécifiques'),
('Historiques', 'Timbre d''importance historique'),
('Rares', 'Timbre rares et de collection');

-- -----------------------------------------------------
-- Insert default admin user
-- -----------------------------------------------------
INSERT INTO `stampee_db`.`membre` (`nom_utilisateur`, `courriel`, `mot_de_passe`, `is_admin`) VALUES
('admin', 'admin@stampee.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- -----------------------------------------------------
-- Insert test users for the user guide
-- -----------------------------------------------------
-- Admin user: testuser / testpass123
INSERT INTO `stampee_db`.`membre` (`nom_utilisateur`, `courriel`, `mot_de_passe`, `is_admin`) VALUES
('testuser', 'testuser@stampee.com', '$2y$12$bm2gvItA6A44kS1ig5.I..8GsXHvUtoJPNi7Q1V3rXkcQfgFgi5j2', 1);

-- Normal user: testuser2@gmail.com / testuser2testuser2
INSERT INTO `stampee_db`.`membre` (`nom_utilisateur`, `courriel`, `mot_de_passe`, `is_admin`) VALUES
('testuser2', 'testuser2@gmail.com', '$2y$12$T629l8.GbN5Q5Jprl.J4cOIpXyyXbEW0gl2tARahm/c7kUUgRiunS', 0);

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;