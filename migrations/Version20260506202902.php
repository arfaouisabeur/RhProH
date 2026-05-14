<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260506202902 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activite DROP FOREIGN KEY `FK_B8755515FD02F13`');
        $this->addSql('ALTER TABLE activite ADD CONSTRAINT FK_B8755515FD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE demande_service DROP FOREIGN KEY `FK_D16A217D8CDE5729`');
        $this->addSql('DROP INDEX IDX_D16A217D8CDE5729 ON demande_service');
        $this->addSql('ALTER TABLE demande_service CHANGE type type_id BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE demande_service ADD CONSTRAINT FK_D16A217DC54C8C93 FOREIGN KEY (type_id) REFERENCES type_service (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_D16A217DC54C8C93 ON demande_service (type_id)');
        $this->addSql('ALTER TABLE employe CHANGE position position VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE event_participation DROP FOREIGN KEY `FK_8F0C52E3FD02F13`');
        $this->addSql('ALTER TABLE event_participation CHANGE evenement_id evenement_id INT NOT NULL');
        $this->addSql('ALTER TABLE event_participation ADD CONSTRAINT FK_8F0C52E3FD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rating DROP FOREIGN KEY `FK_D8892622FD02F13`');
        $this->addSql('ALTER TABLE rating CHANGE evenement_id evenement_id INT NOT NULL');
        $this->addSql('ALTER TABLE rating ADD CONSTRAINT FK_D8892622FD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activite DROP FOREIGN KEY FK_B8755515FD02F13');
        $this->addSql('ALTER TABLE activite ADD CONSTRAINT `FK_B8755515FD02F13` FOREIGN KEY (evenement_id) REFERENCES evenement (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE demande_service DROP FOREIGN KEY FK_D16A217DC54C8C93');
        $this->addSql('DROP INDEX IDX_D16A217DC54C8C93 ON demande_service');
        $this->addSql('ALTER TABLE demande_service CHANGE type_id type BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE demande_service ADD CONSTRAINT `FK_D16A217D8CDE5729` FOREIGN KEY (type) REFERENCES type_service (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_D16A217D8CDE5729 ON demande_service (type)');
        $this->addSql('ALTER TABLE employe CHANGE position position VARCHAR(120) NOT NULL');
        $this->addSql('ALTER TABLE event_participation DROP FOREIGN KEY FK_8F0C52E3FD02F13');
        $this->addSql('ALTER TABLE event_participation CHANGE evenement_id evenement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE event_participation ADD CONSTRAINT `FK_8F0C52E3FD02F13` FOREIGN KEY (evenement_id) REFERENCES evenement (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE rating DROP FOREIGN KEY FK_D8892622FD02F13');
        $this->addSql('ALTER TABLE rating CHANGE evenement_id evenement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE rating ADD CONSTRAINT `FK_D8892622FD02F13` FOREIGN KEY (evenement_id) REFERENCES evenement (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
