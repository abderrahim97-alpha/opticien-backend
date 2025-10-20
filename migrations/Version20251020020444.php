<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251020020444 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE image CHANGE opticien_id opticien_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', CHANGE monture_id monture_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045FD40ADBBC FOREIGN KEY (monture_id) REFERENCES monture (id)');
        $this->addSql('CREATE INDEX IDX_C53D045FD40ADBBC ON image (monture_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE image DROP FOREIGN KEY FK_C53D045FD40ADBBC');
        $this->addSql('DROP INDEX IDX_C53D045FD40ADBBC ON image');
        $this->addSql('ALTER TABLE image CHANGE opticien_id opticien_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', CHANGE monture_id monture_id INT NOT NULL');
    }
}
