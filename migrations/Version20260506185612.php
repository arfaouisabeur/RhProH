<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506185612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sync schema: fix INT/BIGINT types, add missing FKs, add missing columns, rename indexes';
    }

    public function up(Schema $schema): void
    {
        // ── STEP 1: Drop all FKs that reference columns we are about to change ──

        // activite → evenement
        $this->addSql('ALTER TABLE activite DROP FOREIGN KEY FK_B8755515FD02F13');

        // event_participation → evenement + employe
        $this->addSql('ALTER TABLE event_participation DROP FOREIGN KEY fk_participation_evenement');
        $this->addSql('ALTER TABLE event_participation DROP FOREIGN KEY fk_participation_employe');

        // rating → evenement + employe
        $this->addSql('ALTER TABLE rating DROP FOREIGN KEY fk_rating_evenement');
        $this->addSql('ALTER TABLE rating DROP FOREIGN KEY fk_rating_employe');

        // reponse → conge_tt + demande_service + employe + rh
        $this->addSql('ALTER TABLE reponse DROP FOREIGN KEY fk_reponse_conge');
        $this->addSql('ALTER TABLE reponse DROP FOREIGN KEY fk_reponse_demande');
        $this->addSql('ALTER TABLE reponse DROP FOREIGN KEY fk_reponse_employe');
        $this->addSql('ALTER TABLE reponse DROP FOREIGN KEY fk_reponse_rh');

        // tache → projet + employe + prime
        $this->addSql('ALTER TABLE tache DROP FOREIGN KEY fk_tache_projet');
        $this->addSql('ALTER TABLE tache DROP FOREIGN KEY fk_tache_employe');
        $this->addSql('ALTER TABLE tache DROP FOREIGN KEY tache_ibfk_1');

        // prime → contract
        $this->addSql('ALTER TABLE prime DROP FOREIGN KEY fk_prime_contract');

        // salaire → contract
        $this->addSql('ALTER TABLE salaire DROP FOREIGN KEY fk_salaire_contract');

        // contract → employe + rh
        $this->addSql('ALTER TABLE contract DROP FOREIGN KEY contract_ibfk_1');
        $this->addSql('ALTER TABLE contract DROP FOREIGN KEY contract_ibfk_2');

        // conge_tt → employe
        $this->addSql('ALTER TABLE conge_tt DROP FOREIGN KEY fk_conge_employe');

        // demande_service → employe + type_service
        $this->addSql('ALTER TABLE demande_service DROP FOREIGN KEY fk_demande_service_employe');
        $this->addSql('ALTER TABLE demande_service DROP FOREIGN KEY fk_demande_type');

        // evenement → rh
        $this->addSql('ALTER TABLE evenement DROP FOREIGN KEY fk_evenement_rh');

        // offre_emploi → rh
        $this->addSql('ALTER TABLE offre_emploi DROP FOREIGN KEY fk_offre_rh');

        // projet → rh + employe
        $this->addSql('ALTER TABLE projet DROP FOREIGN KEY fk_projet_rh');
        $this->addSql('ALTER TABLE projet DROP FOREIGN KEY fk_projet_responsable');

        // ── STEP 2: Change column types (BIGINT → INT where needed) ──

        // evenement.id must change BEFORE activite.evenement_id
        $this->addSql('ALTER TABLE evenement CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE titre titre VARCHAR(255) NOT NULL, CHANGE date_debut date_debut VARCHAR(255) NOT NULL, CHANGE date_fin date_fin VARCHAR(255) NOT NULL, CHANGE lieu lieu VARCHAR(255) NOT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE rh_id rh_id BIGINT DEFAULT NULL, CHANGE image_url image_url VARCHAR(255) DEFAULT NULL, CHANGE latitude latitude VARCHAR(255) DEFAULT NULL, CHANGE longitude longitude VARCHAR(255) DEFAULT NULL');

        // now activite.evenement_id can become INT
        $this->addSql('ALTER TABLE activite CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE evenement_id evenement_id INT NOT NULL');

        // conge_tt
        $this->addSql('ALTER TABLE conge_tt CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE type_conge type_conge VARCHAR(50) NOT NULL, CHANGE statut statut VARCHAR(50) NOT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE ocr_verified ocr_verified TINYINT DEFAULT NULL');

        // contract (keep employe_id/rh_id as BIGINT since employe/rh use BIGINT PKs)
        $this->addSql('ALTER TABLE contract CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE employe_id employe_id BIGINT DEFAULT NULL, CHANGE rh_id rh_id BIGINT DEFAULT NULL, CHANGE date_debut date_debut VARCHAR(255) NOT NULL, CHANGE date_fin date_fin VARCHAR(255) DEFAULT NULL, CHANGE type type VARCHAR(255) DEFAULT NULL, CHANGE statut statut VARCHAR(255) DEFAULT NULL, CHANGE salaire_base salaire_base VARCHAR(255) DEFAULT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL');

        // demande_service
        $this->addSql('ALTER TABLE demande_service CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE titre titre VARCHAR(255) NOT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE date_demande date_demande VARCHAR(255) NOT NULL, CHANGE statut statut VARCHAR(255) NOT NULL, CHANGE employe_id employe_id BIGINT DEFAULT NULL, CHANGE etape_workflow etape_workflow VARCHAR(255) DEFAULT NULL, CHANGE date_derniere_etape date_derniere_etape VARCHAR(255) DEFAULT NULL, CHANGE priorite priorite VARCHAR(255) DEFAULT NULL, CHANGE deadline_reponse deadline_reponse VARCHAR(255) DEFAULT NULL, CHANGE sla_depasse sla_depasse VARCHAR(255) DEFAULT NULL, CHANGE type type INT DEFAULT NULL');

        // event_participation (evenement_id → INT now that evenement.id is INT)
        $this->addSql('ALTER TABLE event_participation CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE date_inscription date_inscription VARCHAR(255) NOT NULL, CHANGE statut statut VARCHAR(255) NOT NULL, CHANGE evenement_id evenement_id INT DEFAULT NULL, CHANGE employe_id employe_id BIGINT DEFAULT NULL');

        // messenger_messages
        $this->addSql('ALTER TABLE messenger_messages CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE body body VARCHAR(255) NOT NULL, CHANGE headers headers VARCHAR(255) NOT NULL, CHANGE queue_name queue_name VARCHAR(255) NOT NULL, CHANGE created_at created_at VARCHAR(255) NOT NULL, CHANGE available_at available_at VARCHAR(255) NOT NULL, CHANGE delivered_at delivered_at VARCHAR(255) DEFAULT NULL, ADD PRIMARY KEY (id)');

        // offre_emploi
        $this->addSql('DROP INDEX uq_offre_unique ON offre_emploi');
        $this->addSql('ALTER TABLE offre_emploi ADD latitude DOUBLE PRECISION DEFAULT NULL, ADD longitude DOUBLE PRECISION DEFAULT NULL, CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE titre titre VARCHAR(255) NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE localisation localisation VARCHAR(255) NOT NULL, CHANGE type_contrat type_contrat VARCHAR(100) NOT NULL, CHANGE statut statut VARCHAR(100) NOT NULL');

        // prime (contract_id → INT now that contract.id is INT)
        $this->addSql('ALTER TABLE prime CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE montant montant VARCHAR(255) NOT NULL, CHANGE date_attribution date_attribution VARCHAR(255) NOT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE contract_id contract_id INT DEFAULT NULL');

        // projet
        $this->addSql('ALTER TABLE projet CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE titre titre VARCHAR(255) NOT NULL, CHANGE statut statut VARCHAR(255) NOT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE date_debut date_debut DATETIME NOT NULL, CHANGE date_fin date_fin DATETIME NOT NULL');

        // rating (evenement_id → INT now that evenement.id is INT)
        $this->addSql('ALTER TABLE rating CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE evenement_id evenement_id INT DEFAULT NULL, CHANGE employe_id employe_id BIGINT DEFAULT NULL, CHANGE commentaire commentaire VARCHAR(255) NOT NULL, CHANGE etoiles etoiles VARCHAR(255) NOT NULL, CHANGE date_creation date_creation VARCHAR(255) NOT NULL');

        // reponse (conge_tt_id + demande_service_id → INT)
        $this->addSql('ALTER TABLE reponse CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE decision decision VARCHAR(255) NOT NULL, CHANGE rh_id rh_id BIGINT DEFAULT NULL, CHANGE employe_id employe_id BIGINT DEFAULT NULL, CHANGE conge_tt_id conge_tt_id INT DEFAULT NULL, CHANGE demande_service_id demande_service_id INT DEFAULT NULL');

        // salaire (contract_id → INT)
        $this->addSql('ALTER TABLE salaire CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE mois mois VARCHAR(255) NOT NULL, CHANGE annee annee VARCHAR(255) NOT NULL, CHANGE montant montant VARCHAR(255) NOT NULL, CHANGE date_paiement date_paiement VARCHAR(255) DEFAULT NULL, CHANGE statut statut VARCHAR(255) NOT NULL, CHANGE contract_id contract_id INT DEFAULT NULL');

        // tache (projet_id → INT, prime_id → INT)
        $this->addSql('ALTER TABLE tache CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE titre titre VARCHAR(255) NOT NULL, CHANGE statut statut VARCHAR(255) NOT NULL, CHANGE projet_id projet_id INT NOT NULL, CHANGE prime_id prime_id INT DEFAULT NULL, CHANGE date_debut date_debut DATETIME NOT NULL, CHANGE date_fin date_fin DATETIME NOT NULL, CHANGE level level VARCHAR(255) NOT NULL');

        // type_service
        $this->addSql('ALTER TABLE type_service CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE categorie categorie VARCHAR(255) NOT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL');

        // users
        $this->addSql('ALTER TABLE users CHANGE statut statut VARCHAR(50) NOT NULL');

        // candidature: add missing columns + fix types
        $this->addSql('DROP INDEX uq_candidature_unique ON candidature');
        $this->addSql('ALTER TABLE candidature ADD cv_skills LONGTEXT DEFAULT NULL, ADD ai_analysis LONGTEXT DEFAULT NULL, ADD lettre_motivation LONGTEXT DEFAULT NULL, ADD disponibilite VARCHAR(50) DEFAULT NULL, ADD pretention_salariale INT DEFAULT NULL, CHANGE date_candidature date_candidature DATE DEFAULT NULL, CHANGE offre_emploi_id offre_emploi_id INT NOT NULL, CHANGE cv_uploaded_at cv_uploaded_at DATETIME DEFAULT NULL, CHANGE contract_status contract_status VARCHAR(50) DEFAULT NULL');

        // employe index rename
        $this->addSql('DROP INDEX matricule ON employe');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F804D3B912B2DC9C ON employe (matricule)');

        // users index rename
        $this->addSql('DROP INDEX email ON users');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');

        // ── STEP 3: Rename indexes ──
        $this->addSql('ALTER TABLE activite RENAME INDEX idx_b8755515fd02f13 TO idx_activite_evenement');
        $this->addSql('ALTER TABLE candidature RENAME INDEX fk_candidature_offre TO IDX_E33BD3B8B08996ED');
        $this->addSql('ALTER TABLE contract RENAME INDEX employe_id TO IDX_E98F28591B65292');
        $this->addSql('ALTER TABLE contract RENAME INDEX rh_id TO IDX_E98F285922A2877C');
        $this->addSql('ALTER TABLE demande_service RENAME INDEX fk_demande_service_employe TO IDX_D16A217D1B65292');
        $this->addSql('ALTER TABLE demande_service RENAME INDEX fk_demande_type TO IDX_D16A217D8CDE5729');
        $this->addSql('ALTER TABLE evenement RENAME INDEX fk_evenement_rh TO IDX_B26681E22A2877C');
        $this->addSql('ALTER TABLE event_participation RENAME INDEX fk_participation_evenement TO IDX_8F0C52E3FD02F13');
        $this->addSql('ALTER TABLE event_participation RENAME INDEX fk_participation_employe TO IDX_8F0C52E31B65292');
        $this->addSql('ALTER TABLE offre_emploi RENAME INDEX fk_offre_rh TO IDX_132AD0D122A2877C');
        $this->addSql('ALTER TABLE prime RENAME INDEX fk_prime_contract TO IDX_544B0F572576E0FD');
        $this->addSql('ALTER TABLE projet RENAME INDEX fk_projet_rh TO IDX_50159CA922A2877C');
        $this->addSql('ALTER TABLE projet RENAME INDEX fk_projet_responsable TO IDX_50159CA9F59FA192');
        $this->addSql('ALTER TABLE rating RENAME INDEX fk_rating_evenement TO IDX_D8892622FD02F13');
        $this->addSql('ALTER TABLE rating RENAME INDEX fk_rating_employe TO IDX_D88926221B65292');
        $this->addSql('ALTER TABLE reponse RENAME INDEX fk_reponse_rh TO IDX_5FB6DEC722A2877C');
        $this->addSql('ALTER TABLE reponse RENAME INDEX fk_reponse_employe TO IDX_5FB6DEC71B65292');
        $this->addSql('ALTER TABLE reponse RENAME INDEX fk_reponse_conge TO IDX_5FB6DEC7998B593A');
        $this->addSql('ALTER TABLE reponse RENAME INDEX fk_reponse_demande TO IDX_5FB6DEC71B90A347');
        $this->addSql('ALTER TABLE salaire RENAME INDEX fk_salaire_contract TO IDX_3BCBBD112576E0FD');
        $this->addSql('ALTER TABLE tache RENAME INDEX fk_tache_projet TO IDX_93872075C18272');
        $this->addSql('ALTER TABLE tache RENAME INDEX fk_tache_employe TO IDX_938720751B65292');
        $this->addSql('ALTER TABLE tache RENAME INDEX fk_tache_prime TO IDX_9387207569247986');

        // ── STEP 4: Re-add all FKs ──
        $this->addSql('ALTER TABLE activite ADD CONSTRAINT FK_B8755515FD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE candidat ADD CONSTRAINT FK_6AB5B471A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE candidat_offre_favori ADD CONSTRAINT FK_2AC721AC8D0EB82 FOREIGN KEY (candidat_id) REFERENCES candidat (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE candidat_offre_favori ADD CONSTRAINT FK_2AC721ACB08996ED FOREIGN KEY (offre_emploi_id) REFERENCES offre_emploi (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE conge_tt ADD CONSTRAINT FK_2877E6211B65292 FOREIGN KEY (employe_id) REFERENCES employe (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT IDX_E98F28591B65292 FOREIGN KEY (employe_id) REFERENCES employe (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT IDX_E98F285922A2877C FOREIGN KEY (rh_id) REFERENCES rh (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE demande_service ADD CONSTRAINT FK_D16A217D1B65292 FOREIGN KEY (employe_id) REFERENCES employe (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE demande_service ADD CONSTRAINT FK_D16A217D8CDE5729 FOREIGN KEY (type) REFERENCES type_service (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE evenement ADD CONSTRAINT FK_B26681E22A2877C FOREIGN KEY (rh_id) REFERENCES rh (user_id)');
        $this->addSql('ALTER TABLE event_participation ADD CONSTRAINT FK_8F0C52E3FD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_participation ADD CONSTRAINT FK_8F0C52E31B65292 FOREIGN KEY (employe_id) REFERENCES employe (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE offre_emploi ADD CONSTRAINT FK_132AD0D122A2877C FOREIGN KEY (rh_id) REFERENCES rh (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE password_reset_codes ADD CONSTRAINT FK_D1E1C4C0A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE prime ADD CONSTRAINT FK_544B0F572576E0FD FOREIGN KEY (contract_id) REFERENCES contract (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE projet ADD CONSTRAINT FK_50159CA922A2877C FOREIGN KEY (rh_id) REFERENCES rh (user_id)');
        $this->addSql('ALTER TABLE projet ADD CONSTRAINT FK_50159CA9F59FA192 FOREIGN KEY (responsable_employe_id) REFERENCES employe (user_id)');
        $this->addSql('ALTER TABLE rating ADD CONSTRAINT FK_D8892622FD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rating ADD CONSTRAINT FK_D88926221B65292 FOREIGN KEY (employe_id) REFERENCES employe (user_id)');
        $this->addSql('ALTER TABLE reponse ADD CONSTRAINT FK_5FB6DEC71B65292 FOREIGN KEY (employe_id) REFERENCES employe (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reponse ADD CONSTRAINT FK_5FB6DEC722A2877C FOREIGN KEY (rh_id) REFERENCES rh (user_id)');
        $this->addSql('ALTER TABLE reponse ADD CONSTRAINT FK_5FB6DEC7998B593A FOREIGN KEY (conge_tt_id) REFERENCES conge_tt (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reponse ADD CONSTRAINT FK_5FB6DEC71B90A347 FOREIGN KEY (demande_service_id) REFERENCES demande_service (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE salaire ADD CONSTRAINT FK_3BCBBD112576E0FD FOREIGN KEY (contract_id) REFERENCES contract (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE service_reaction ADD CONSTRAINT FK_5DA15CCAA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE service_reaction ADD CONSTRAINT FK_5DA15CCAF05F7FC3 FOREIGN KEY (type_service_id) REFERENCES type_service (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tache ADD CONSTRAINT FK_93872075C18272 FOREIGN KEY (projet_id) REFERENCES projet (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tache ADD CONSTRAINT FK_938720751B65292 FOREIGN KEY (employe_id) REFERENCES employe (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tache ADD CONSTRAINT FK_9387207569247986 FOREIGN KEY (prime_id) REFERENCES prime (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // Reverse is not needed for this project — schema was manually created
        $this->throwIrreversibleMigrationException('This migration cannot be reversed automatically.');
    }
}
