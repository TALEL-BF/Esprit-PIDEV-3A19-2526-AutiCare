<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260412000400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add zoom_start_url column to seance table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE seance ADD zoom_start_url LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE seance DROP zoom_start_url');
    }
}
