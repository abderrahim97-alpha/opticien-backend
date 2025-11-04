<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Final corrected migration:
 * - Ensure monture_id is INT (matches monture.id)
 * - Ensure vendeur_id is BINARY(16) (matches user.id)
 * - Fix foreign keys accordingly
 */
final class Version20251103114625 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix foreign key constraints for commande_item: monture_id→monture(id), vendeur_id→user(id)';
    }

    public function up(Schema $schema): void
    {
        // Drop any wrong FKs first
        $this->addSql('ALTER TABLE commande_item DROP FOREIGN KEY IF EXISTS FK_747724FDD40ADBBC');
        $this->addSql('ALTER TABLE commande_item DROP FOREIGN KEY IF EXISTS FK_747724FDVendeur');

        // Fix column types
        $this->addSql('ALTER TABLE commande_item MODIFY monture_id INT NOT NULL');
        $this->addSql('ALTER TABLE commande_item MODIFY vendeur_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');

        // Recreate correct FKs
        $this->addSql('ALTER TABLE commande_item ADD CONSTRAINT FK_COMMANDE_ITEM_MONTURE FOREIGN KEY (monture_id) REFERENCES monture (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande_item ADD CONSTRAINT FK_COMMANDE_ITEM_VENDEUR FOREIGN KEY (vendeur_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Drop the fixed constraints
        $this->addSql('ALTER TABLE commande_item DROP FOREIGN KEY IF EXISTS FK_COMMANDE_ITEM_MONTURE');
        $this->addSql('ALTER TABLE commande_item DROP FOREIGN KEY IF EXISTS FK_COMMANDE_ITEM_VENDEUR');

        // (Optional) revert to the older type setup if necessary
        $this->addSql('ALTER TABLE commande_item MODIFY monture_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE commande_item MODIFY vendeur_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');

        // Restore old FKs (if needed)
        $this->addSql('ALTER TABLE commande_item ADD CONSTRAINT FK_747724FDD40ADBBC FOREIGN KEY (monture_id) REFERENCES monture (id)');
    }
}
