<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251031105705 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commande (id INT AUTO_INCREMENT NOT NULL, acheteur_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', status VARCHAR(255) NOT NULL, total_price NUMERIC(10, 2) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', validated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', note_admin LONGTEXT DEFAULT NULL, INDEX IDX_6EEAA67D96A7BB5F (acheteur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE commande_item (id INT AUTO_INCREMENT NOT NULL, commande_id INT NOT NULL, monture_id INT NOT NULL, vendeur_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', quantite INT NOT NULL, prix_unitaire NUMERIC(10, 2) NOT NULL, sous_total NUMERIC(10, 2) NOT NULL, INDEX IDX_747724FD82EA2E54 (commande_id), INDEX IDX_747724FDD40ADBBC (monture_id), INDEX IDX_747724FD858C065E (vendeur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D96A7BB5F FOREIGN KEY (acheteur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE commande_item ADD CONSTRAINT FK_747724FD82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id)');
        $this->addSql('ALTER TABLE commande_item ADD CONSTRAINT FK_747724FDD40ADBBC FOREIGN KEY (monture_id) REFERENCES monture (id)');
        $this->addSql('ALTER TABLE commande_item ADD CONSTRAINT FK_747724FD858C065E FOREIGN KEY (vendeur_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D96A7BB5F');
        $this->addSql('ALTER TABLE commande_item DROP FOREIGN KEY FK_747724FD82EA2E54');
        $this->addSql('ALTER TABLE commande_item DROP FOREIGN KEY FK_747724FDD40ADBBC');
        $this->addSql('ALTER TABLE commande_item DROP FOREIGN KEY FK_747724FD858C065E');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE commande_item');
    }
}
