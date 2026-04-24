<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260424194657 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE article_conseille (
              id_article INT AUTO_INCREMENT NOT NULL,
              titre VARCHAR(255) NOT NULL,
              contenu LONGTEXT NOT NULL,
              categorie VARCHAR(50) NOT NULL,
              date_creation DATETIME NOT NULL,
              Auteur VARCHAR(255) NOT NULL,
              likes_count INT NOT NULL,
              auteur_image VARCHAR(255) DEFAULT NULL,
              PRIMARY KEY (id_article)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE suivie (
              ID_SUIVIE INT AUTO_INCREMENT NOT NULL,
              NOM_ENFANT VARCHAR(255) NOT NULL,
              EMAIL_PARENT VARCHAR(255) NOT NULL,
              AGE INT NOT NULL,
              NOM_PSY VARCHAR(255) NOT NULL,
              DATE_SUIVIE DATETIME NOT NULL,
              SCORE_HUMEUR INT NOT NULL,
              SCORE_STRESS INT NOT NULL,
              SCORE_ATTENTION INT NOT NULL,
              NIVEAU_SEANCE INT DEFAULT NULL,
              COMPORTEMENT VARCHAR(255) NOT NULL,
              INTERACTION_SOCIALE VARCHAR(255) NOT NULL,
              OBSERVATION VARCHAR(255) NOT NULL,
              STATUT VARCHAR(255) NOT NULL,
              CR_RESUME LONGTEXT DEFAULT NULL,
              CR_PDF_PATH VARCHAR(500) DEFAULT NULL,
              PARENT_PDF_UPLOADED_AT DATETIME DEFAULT NULL,
              ID_THERAPIE_RECO INT DEFAULT NULL,
              INDEX IDX_92F3582149EC721D (ID_THERAPIE_RECO),
              PRIMARY KEY (ID_SUIVIE)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE therapie (
              ID_THERAPIE INT AUTO_INCREMENT NOT NULL,
              NOM_EXERCICE VARCHAR(255) NOT NULL,
              TYPE_EXERCICE VARCHAR(255) NOT NULL,
              OBJECTIF VARCHAR(255) NOT NULL,
              DESCRIPTION VARCHAR(255) NOT NULL,
              DUREE_MIN INT NOT NULL,
              MATERIEL VARCHAR(2550) NOT NULL,
              ADAPTATION_TSA VARCHAR(255) NOT NULL,
              CIBLE VARCHAR(30) NOT NULL,
              NIVEAUX_HUMEUR VARCHAR(255) NOT NULL,
              NIVEAUX_ATTENTION VARCHAR(255) NOT NULL,
              NIVEAUX_STRESSE VARCHAR(255) NOT NULL,
              COMPORTEMENT VARCHAR(255) NOT NULL,
              INTERACTION VARCHAR(255) NOT NULL,
              NIVEAU INT NOT NULL,
              PRIMARY KEY (ID_THERAPIE)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              suivie
            ADD
              CONSTRAINT FK_92F3582149EC721D FOREIGN KEY (ID_THERAPIE_RECO) REFERENCES therapie (ID_THERAPIE)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE suivie DROP FOREIGN KEY FK_92F3582149EC721D');
        $this->addSql('DROP TABLE article_conseille');
        $this->addSql('DROP TABLE suivie');
        $this->addSql('DROP TABLE therapie');
    }
}
