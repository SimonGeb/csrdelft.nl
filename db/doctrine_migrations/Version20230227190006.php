<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230227190006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Toevoegingen voor web-push';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE web_push (id INT AUTO_INCREMENT NOT NULL, uid VARCHAR(4) COMMENT \'(DC2Type:uid)\' NOT NULL, client_endpoint VARCHAR(255) NOT NULL, client_keys VARCHAR(255) NOT NULL, INDEX IDX_BVRODGMKX13C5GFL (uid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE web_push ADD CONSTRAINT FK_PO8QANZI4VTBA61 FOREIGN KEY (uid) REFERENCES accounts (uid)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE web_push');
    }
}
