<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260416001000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Zoom URLs columns to seance table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE seance ADD zoom_join_url VARCHAR(255) DEFAULT NULL, ADD zoom_start_url LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE seance DROP zoom_join_url, DROP zoom_start_url');
    }
}
