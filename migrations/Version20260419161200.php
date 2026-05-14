<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds the candidat_offre_favori join table for the ManyToMany
 * relation between Candidat and OffreEmploi (favorites feature).
 */
final class Version20260419161200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create candidat_offre_favori join table for the favorites system';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE IF NOT EXISTS candidat_offre_favori (
                candidat_id      BIGINT(20) NOT NULL,
                offre_emploi_id  BIGINT(20) NOT NULL,
                PRIMARY KEY (candidat_id, offre_emploi_id),
                INDEX IDX_FAV_CANDIDAT  (candidat_id),
                INDEX IDX_FAV_OFFRE     (offre_emploi_id),
                CONSTRAINT FK_FAV_CANDIDAT FOREIGN KEY (candidat_id)
                    REFERENCES candidat (user_id) ON DELETE CASCADE,
                CONSTRAINT FK_FAV_OFFRE FOREIGN KEY (offre_emploi_id)
                    REFERENCES offre_emploi (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS candidat_offre_favori');
    }
}
