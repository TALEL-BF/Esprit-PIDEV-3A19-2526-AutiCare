<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260404104050 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP face_model_path, DROP otp_expiry, CHANGE nom nom VARCHAR(100) NOT NULL, CHANGE prenom prenom VARCHAR(100) NOT NULL, CHANGE email email VARCHAR(180) NOT NULL, CHANGE password password VARCHAR(255) NOT NULL, CHANGE role role VARCHAR(50) NOT NULL, CHANGE otp_code otp_code VARCHAR(10) DEFAULT NULL, CHANGE status status VARCHAR(20) DEFAULT NULL, CHANGE created_at created_at VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX email ON user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD face_model_path VARCHAR(255) DEFAULT NULL, ADD otp_expiry DATETIME DEFAULT NULL, CHANGE nom nom VARCHAR(50) DEFAULT NULL, CHANGE prenom prenom VARCHAR(50) DEFAULT NULL, CHANGE email email VARCHAR(100) DEFAULT NULL, CHANGE password password VARCHAR(255) DEFAULT NULL, CHANGE role role ENUM(\'admin\', \'user\', \'professeur\', \'psychologue\', \'enfant\', \'parent\') DEFAULT \'user\', CHANGE status status ENUM(\'active\', \'merged\', \'deactivated\') DEFAULT \'active\', CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE otp_code otp_code VARCHAR(6) DEFAULT NULL');
        $this->addSql('DROP INDEX uniq_8d93d649e7927c74 ON user');
        $this->addSql('CREATE UNIQUE INDEX email ON user (email)');
    }
}
