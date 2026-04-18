<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260412000500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add zoom_join_url and zoom_start_url columns to rdv table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE rdv ADD zoom_join_url VARCHAR(255) DEFAULT NULL, ADD zoom_start_url LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE rdv DROP zoom_join_url, DROP zoom_start_url');
    }
}
