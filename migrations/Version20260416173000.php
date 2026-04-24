<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260416173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create game_session and clinical_note tables for weekly insights, KPI dashboard, and clinical history.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE game_session (id INT AUTO_INCREMENT NOT NULL, enfant_id INT NOT NULL, game_title VARCHAR(120) NOT NULL, skill VARCHAR(30) NOT NULL, score DOUBLE PRECISION NOT NULL, max_score DOUBLE PRECISION NOT NULL, duration_seconds INT NOT NULL, played_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', notes LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('CREATE INDEX IDX_GAME_SESSION_ENFANT_ID ON game_session (enfant_id)');

        $this->addSql("CREATE TABLE clinical_note (id INT AUTO_INCREMENT NOT NULL, enfant_id INT NOT NULL, session_date DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', session_note LONGTEXT NOT NULL, proposed_action LONGTEXT DEFAULT NULL, result_after_action LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('CREATE INDEX IDX_CLINICAL_NOTE_ENFANT_ID ON clinical_note (enfant_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE clinical_note');
        $this->addSql('DROP TABLE game_session');
    }
}
