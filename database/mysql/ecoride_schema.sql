-- ============================================================
-- EcoRide - Schéma MySQL (base relationnelle)
-- Corrections appliquées :
--   - roles NOT NULL (initialisé côté Symfony)
--   - updated_at avec auto-mise à jour
--   - CHECK constraints sur les champs métier sensibles
--   - fusion confirmation_passager/validation_trajet -> ENUM validation_passager
--   - renommage id_utilisateur -> id_chauffeur / id_passager selon contexte
--   - FK nommées, ON DELETE RESTRICT / ON UPDATE CASCADE
-- Non retenu : id_chauffeur redondant dans AVIS (dénormalisation jugée
-- inutile à cette échelle, jointure avis->covoiturage->utilisateur suffisante)
--
-- Corrections supplémentaires appliquées :
--   - roles DEFAULT (JSON_ARRAY()) : filet de sécurité pour l'insertion
--     manuelle du compte admin hors application (US13)
--   - prix_par_personne en int unsigned : le système fonctionne en credits,
--     pas en euros, donc pas de decimal
--   - updated_at ajouté sur covoiturage/participation/avis
--   - UNIQUE (id_passager, id_covoiturage) sur avis : un seul avis par trajet
--
-- Non retenu : ajout de 'INCIDENT' dans covoiturage.statut. Un incident est
-- porté par un passager en particulier (participation.validation_passager),
-- pas par le trajet entier ; le mélanger au cycle de vie du covoiturage
-- (PLANNED/STARTED/COMPLETED/CANCELLED) créerait une ambiguïté si plusieurs
-- passagers ont des retours différents sur le même trajet.
-- ============================================================

CREATE TABLE `utilisateur` (
  `id_utilisateur` int PRIMARY KEY AUTO_INCREMENT,
  `pseudo` varchar(50) UNIQUE NOT NULL,
  `email` varchar(255) UNIQUE NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `roles` json NOT NULL DEFAULT (JSON_ARRAY()) COMMENT 'Rôles de sécurité Symfony: ["ROLE_USER"], ["ROLE_EMPLOYEE"], ["ROLE_ADMIN"]. Défaut utile pour la création manuelle du compte admin (US13).',
  `photo` varchar(255),
  `credits` int NOT NULL DEFAULT 20,
  `est_chauffeur` boolean NOT NULL DEFAULT false COMMENT 'Statut métier, distinct des roles de sécurité',
  `est_passager` boolean NOT NULL DEFAULT true,
  `accepte_fumeur` boolean NOT NULL DEFAULT false,
  `accepte_animaux` boolean NOT NULL DEFAULT false,
  `preferences_libres` text,
  `actif` boolean NOT NULL DEFAULT true COMMENT 'Permet la suspension de compte (US13)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) COMMENT = 'Compte visiteur devenu utilisateur (US7). Le rôle admin est créé en amont hors application (US13).';

CREATE TABLE `marque` (
  `id_marque` int PRIMARY KEY AUTO_INCREMENT,
  `libelle` varchar(100) UNIQUE NOT NULL
);

CREATE TABLE `vehicule` (
  `id_vehicule` int PRIMARY KEY AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `id_marque` int NOT NULL,
  `immatriculation` varchar(20) UNIQUE NOT NULL,
  `date_premiere_immatriculation` date NOT NULL,
  `modele` varchar(100) NOT NULL,
  `couleur` varchar(50),
  `energie` varchar(50) NOT NULL COMMENT 'Ex: essence, diesel, electrique, hybride',
  `nombre_places` tinyint unsigned NOT NULL,
  CONSTRAINT `chk_vehicule_places` CHECK (`nombre_places` > 0)
);

CREATE TABLE `covoiturage` (
  `id_covoiturage` int PRIMARY KEY AUTO_INCREMENT,
  `id_chauffeur` int NOT NULL,
  `id_vehicule` int NOT NULL,
  `ville_depart` varchar(100) NOT NULL,
  `adresse_depart` varchar(255) NOT NULL,
  `ville_arrivee` varchar(100) NOT NULL,
  `adresse_arrivee` varchar(255) NOT NULL,
  `date_heure_depart` datetime NOT NULL,
  `date_heure_arrivee` datetime NOT NULL,
  `prix_par_personne` int unsigned NOT NULL COMMENT 'Prix en credits (pas en euros), fixe par le chauffeur',
  `places_initiales` int NOT NULL,
  `places_restantes` int NOT NULL,
  `statut` varchar(30) NOT NULL DEFAULT 'PLANNED' COMMENT 'PLANNED, STARTED, COMPLETED, CANCELLED',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `chk_covoiturage_prix` CHECK (`prix_par_personne` > 0),
  CONSTRAINT `chk_covoiturage_places_initiales` CHECK (`places_initiales` > 0),
  CONSTRAINT `chk_covoiturage_places_restantes` CHECK (`places_restantes` >= 0 AND `places_restantes` <= `places_initiales`),
  CONSTRAINT `chk_covoiturage_dates` CHECK (`date_heure_arrivee` > `date_heure_depart`)
);

CREATE TABLE `participation` (
  `id_participation` int PRIMARY KEY AUTO_INCREMENT,
  `id_passager` int NOT NULL,
  `id_covoiturage` int NOT NULL,
  `credits_payes` int NOT NULL,
  `statut` varchar(30) NOT NULL DEFAULT 'CONFIRMED' COMMENT 'CONFIRMED, CANCELLED',
  `validation_post_trajet` enum('PENDING','OK','INCIDENT') NOT NULL DEFAULT 'PENDING',
  `validated_at` timestamp NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `cancelled_at` timestamp NULL,
  CONSTRAINT `chk_participation_credits` CHECK (`credits_payes` > 0),
  CONSTRAINT `uq_participation_passager_covoiturage` UNIQUE (`id_passager`, `id_covoiturage`)
);

CREATE TABLE `avis` (
  `id_avis` int PRIMARY KEY AUTO_INCREMENT,
  `id_passager` int NOT NULL COMMENT 'Auteur de l avis',
  `id_covoiturage` int NOT NULL,
  `note` tinyint unsigned NOT NULL,
  `commentaire` text,
  `statut` varchar(30) NOT NULL DEFAULT 'PENDING' COMMENT 'PENDING, APPROVED, REJECTED',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `moderated_at` timestamp NULL,
  CONSTRAINT `chk_avis_note` CHECK (`note` BETWEEN 1 AND 5),
  CONSTRAINT `uq_avis_passager_covoiturage` UNIQUE (`id_passager`, `id_covoiturage`)
);

CREATE TABLE `transaction_credit` (
  `id_transaction` int PRIMARY KEY AUTO_INCREMENT,
  `id_utilisateur` int NOT NULL,
  `id_covoiturage` int NULL COMMENT 'Nullable: ex bonus inscription non lie a un trajet',
  `montant` int NOT NULL COMMENT 'Positif = credit, negatif = debit',
  `type` varchar(30) NOT NULL COMMENT 'BONUS_INSCRIPTION, RESERVATION, REMBOURSEMENT, VERSEMENT_CHAUFFEUR, COMMISSION',
  `description` varchar(255),
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- Clés étrangères nommées, comportements de suppression explicites
-- RESTRICT partout : on ne supprime jamais un utilisateur ayant un
-- historique (véhicules, covoiturages, participations, avis, crédits).
-- La suspension se fait via utilisateur.actif = false.
-- ============================================================

ALTER TABLE `vehicule`
  ADD CONSTRAINT `fk_vehicule_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vehicule_marque` FOREIGN KEY (`id_marque`) REFERENCES `marque` (`id_marque`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `covoiturage`
  ADD CONSTRAINT `fk_covoiturage_chauffeur` FOREIGN KEY (`id_chauffeur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_covoiturage_vehicule` FOREIGN KEY (`id_vehicule`) REFERENCES `vehicule` (`id_vehicule`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `participation`
  ADD CONSTRAINT `fk_participation_passager` FOREIGN KEY (`id_passager`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_participation_covoiturage` FOREIGN KEY (`id_covoiturage`) REFERENCES `covoiturage` (`id_covoiturage`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `avis`
  ADD CONSTRAINT `fk_avis_passager` FOREIGN KEY (`id_passager`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_avis_covoiturage` FOREIGN KEY (`id_covoiturage`) REFERENCES `covoiturage` (`id_covoiturage`) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `transaction_credit`
  ADD CONSTRAINT `fk_transaction_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transaction_covoiturage` FOREIGN KEY (`id_covoiturage`) REFERENCES `covoiturage` (`id_covoiturage`) ON DELETE RESTRICT ON UPDATE CASCADE;
