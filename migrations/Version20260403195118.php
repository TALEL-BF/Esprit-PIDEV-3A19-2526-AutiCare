<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260403195118 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE event_sponsor (idEvent INT NOT NULL, idSponsor INT NOT NULL, INDEX IDX_4DB607B2C6A49BA (idEvent), INDEX IDX_4DB607B9135A226 (idSponsor), PRIMARY KEY (idEvent, idSponsor)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE event_sponsor ADD CONSTRAINT FK_4DB607B2C6A49BA FOREIGN KEY (idEvent) REFERENCES event (idEvent)');
        $this->addSql('ALTER TABLE event_sponsor ADD CONSTRAINT FK_4DB607B9135A226 FOREIGN KEY (idSponsor) REFERENCES sponsor (idSponsor)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event_sponsor DROP FOREIGN KEY FK_4DB607B2C6A49BA');
        $this->addSql('ALTER TABLE event_sponsor DROP FOREIGN KEY FK_4DB607B9135A226');
        $this->addSql('DROP TABLE event_sponsor');
    }
}
