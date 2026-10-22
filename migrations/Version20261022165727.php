<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261022165727 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE task (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, due_at DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, priority VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, contact_id INT DEFAULT NULL, opportunity_id INT DEFAULT NULL, owner_id INT DEFAULT NULL, INDEX IDX_527EDB25E7A1254A (contact_id), INDEX IDX_527EDB259A34590F (opportunity_id), INDEX IDX_527EDB257E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB259A34590F FOREIGN KEY (opportunity_id) REFERENCES opportunity (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB257E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25E7A1254A');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB259A34590F');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB257E3C61F9');
        $this->addSql('DROP TABLE task');
    }
}
