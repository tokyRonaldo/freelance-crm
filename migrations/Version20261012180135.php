<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261012180135 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activity (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, occurred_at DATETIME NOT NULL, type VARCHAR(255) NOT NULL, contact_id INT NOT NULL, opportunity_id INT DEFAULT NULL, INDEX IDX_AC74095AE7A1254A (contact_id), INDEX IDX_AC74095A9A34590F (opportunity_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT FK_AC74095AE7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT FK_AC74095A9A34590F FOREIGN KEY (opportunity_id) REFERENCES opportunity (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY FK_AC74095AE7A1254A');
        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY FK_AC74095A9A34590F');
        $this->addSql('DROP TABLE activity');
    }
}
