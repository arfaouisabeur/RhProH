<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260506225718 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE doctrine_migration_version (version VARCHAR(191) NOT NULL, executed_at DATETIME DEFAULT NULL, execution_time INT DEFAULT NULL, PRIMARY KEY (version)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_message (id INT AUTO_INCREMENT NOT NULL, body VARCHAR(255) NOT NULL, headers VARCHAR(255) NOT NULL, queue_name VARCHAR(255) NOT NULL, created_at VARCHAR(255) NOT NULL, available_at VARCHAR(255) NOT NULL, delivered_at VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE password_reset_code (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(6) NOT NULL, expires_at DATETIME NOT NULL, user_id BIGINT NOT NULL, INDEX IDX_55C941F1A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id BIGINT AUTO_INCREMENT NOT NULL, nom VARCHAR(120) NOT NULL, prenom VARCHAR(120) NOT NULL, email VARCHAR(255) NOT NULL, mot_de_passe VARCHAR(255) NOT NULL, telephone VARCHAR(40) DEFAULT NULL, adresse VARCHAR(255) DEFAULT NULL, role VARCHAR(255) DEFAULT NULL, avatar_path VARCHAR(500) DEFAULT NULL, statut VARCHAR(50) NOT NULL, google_id VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE password_reset_code ADD CONSTRAINT FK_55C941F1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE password_reset_codes DROP FOREIGN KEY `FK_D1E1C4C0A76ED395`');
        $this->addSql('DROP TABLE password_reset_codes');
        $this->addSql('DROP TABLE users');
        $this->addSql('ALTER TABLE candidat DROP FOREIGN KEY `FK_6AB5B471A76ED395`');
        $this->addSql('ALTER TABLE candidat ADD CONSTRAINT FK_6AB5B471A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE employe DROP FOREIGN KEY `fk_employe_user`');
        $this->addSql('ALTER TABLE employe ADD CONSTRAINT FK_F804D3B9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE evenement CHANGE latitude latitude DOUBLE PRECISION DEFAULT NULL, CHANGE longitude longitude DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE rh DROP FOREIGN KEY `fk_rh_user`');
        $this->addSql('ALTER TABLE rh ADD CONSTRAINT FK_1FB9E0E1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE service_reaction DROP FOREIGN KEY `FK_5DA15CCAA76ED395`');
        $this->addSql('ALTER TABLE service_reaction DROP FOREIGN KEY `FK_5DA15CCA16FE72E1`');
        $this->addSql('ALTER TABLE service_reaction DROP FOREIGN KEY `FK_5DA15CCADE12AB56`');
        $this->addSql('DROP INDEX IDX_5DA15CCA16FE72E1 ON service_reaction');
        $this->addSql('DROP INDEX IDX_5DA15CCADE12AB56 ON service_reaction');
        $this->addSql('ALTER TABLE service_reaction ADD created_by_id BIGINT NOT NULL, ADD updated_by_id BIGINT DEFAULT NULL, DROP created_by, DROP updated_by');
        $this->addSql('ALTER TABLE service_reaction ADD CONSTRAINT FK_5DA15CCAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE service_reaction ADD CONSTRAINT FK_5DA15CCAB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE service_reaction ADD CONSTRAINT FK_5DA15CCA896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_5DA15CCAB03A8386 ON service_reaction (created_by_id)');
        $this->addSql('CREATE INDEX IDX_5DA15CCA896DBBDE ON service_reaction (updated_by_id)');
        $this->addSql('ALTER TABLE messenger_messages CHANGE id id BIGINT AUTO_INCREMENT NOT NULL, CHANGE body body LONGTEXT NOT NULL, CHANGE headers headers LONGTEXT NOT NULL, CHANGE queue_name queue_name VARCHAR(190) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE available_at available_at DATETIME NOT NULL, CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE password_reset_codes (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(6) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, expires_at DATETIME NOT NULL, user_id BIGINT NOT NULL, INDEX IDX_D1E1C4C0A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE users (id BIGINT AUTO_INCREMENT NOT NULL, nom VARCHAR(120) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, prenom VARCHAR(120) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, email VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, mot_de_passe VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, telephone VARCHAR(40) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, adresse VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, role VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, avatar_path VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, statut VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, google_id VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE password_reset_codes ADD CONSTRAINT `FK_D1E1C4C0A76ED395` FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE password_reset_code DROP FOREIGN KEY FK_55C941F1A76ED395');
        $this->addSql('DROP TABLE doctrine_migration_version');
        $this->addSql('DROP TABLE messenger_message');
        $this->addSql('DROP TABLE password_reset_code');
        $this->addSql('DROP TABLE user');
        $this->addSql('ALTER TABLE candidat DROP FOREIGN KEY FK_6AB5B471A76ED395');
        $this->addSql('ALTER TABLE candidat ADD CONSTRAINT `FK_6AB5B471A76ED395` FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE employe DROP FOREIGN KEY FK_F804D3B9A76ED395');
        $this->addSql('ALTER TABLE employe ADD CONSTRAINT `fk_employe_user` FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE evenement CHANGE latitude latitude VARCHAR(255) DEFAULT NULL, CHANGE longitude longitude VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages');
        $this->addSql('ALTER TABLE messenger_messages CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE body body VARCHAR(255) NOT NULL, CHANGE headers headers VARCHAR(255) NOT NULL, CHANGE queue_name queue_name VARCHAR(255) NOT NULL, CHANGE created_at created_at VARCHAR(255) NOT NULL, CHANGE available_at available_at VARCHAR(255) NOT NULL, CHANGE delivered_at delivered_at VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE rh DROP FOREIGN KEY FK_1FB9E0E1A76ED395');
        $this->addSql('ALTER TABLE rh ADD CONSTRAINT `fk_rh_user` FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE service_reaction DROP FOREIGN KEY FK_5DA15CCAA76ED395');
        $this->addSql('ALTER TABLE service_reaction DROP FOREIGN KEY FK_5DA15CCAB03A8386');
        $this->addSql('ALTER TABLE service_reaction DROP FOREIGN KEY FK_5DA15CCA896DBBDE');
        $this->addSql('DROP INDEX IDX_5DA15CCAB03A8386 ON service_reaction');
        $this->addSql('DROP INDEX IDX_5DA15CCA896DBBDE ON service_reaction');
        $this->addSql('ALTER TABLE service_reaction ADD updated_by BIGINT DEFAULT NULL, DROP created_by_id, CHANGE updated_by_id created_by BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE service_reaction ADD CONSTRAINT `FK_5DA15CCAA76ED395` FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE service_reaction ADD CONSTRAINT `FK_5DA15CCA16FE72E1` FOREIGN KEY (updated_by) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE service_reaction ADD CONSTRAINT `FK_5DA15CCADE12AB56` FOREIGN KEY (created_by) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_5DA15CCA16FE72E1 ON service_reaction (updated_by)');
        $this->addSql('CREATE INDEX IDX_5DA15CCADE12AB56 ON service_reaction (created_by)');
    }
}
