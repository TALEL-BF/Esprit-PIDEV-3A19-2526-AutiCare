<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260412000300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure zoom_join_url column exists on seance table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE seance ADD zoom_join_url VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE seance DROP zoom_join_url');
    }
}
