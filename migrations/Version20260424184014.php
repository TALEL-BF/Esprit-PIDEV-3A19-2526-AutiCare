<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260424184014 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_CLINICAL_NOTE_ENFANT_ID ON clinical_note');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              clinical_note
            CHANGE
              session_date session_date DATETIME NOT NULL,
            CHANGE
              created_at created_at DATETIME NOT NULL
        SQL);
        $this->addSql('DROP INDEX IDX_GAME_SESSION_ENFANT_ID ON game_session');
        $this->addSql('ALTER TABLE game_session CHANGE played_at played_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE
              clinical_note
            CHANGE
              session_date session_date DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            CHANGE
              created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql('CREATE INDEX IDX_CLINICAL_NOTE_ENFANT_ID ON clinical_note (enfant_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              game_session
            CHANGE
              played_at played_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql('CREATE INDEX IDX_GAME_SESSION_ENFANT_ID ON game_session (enfant_id)');
    }
}
