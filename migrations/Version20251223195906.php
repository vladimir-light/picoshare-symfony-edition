<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251223195906 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS `downloads` (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, entry_id INTEGER NOT NULL, downloaded_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , client_ip VARCHAR(255) DEFAULT NULL, client_ipv6 VARCHAR(255) DEFAULT NULL, user_agent CLOB DEFAULT NULL, CONSTRAINT FK_4B73A4B5BA364942 FOREIGN KEY (entry_id) REFERENCES entries (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_4B73A4B5BA364942 ON downloads (entry_id)');
        $this->addSql('CREATE TABLE IF NOT EXISTS `entries` (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, guest_link_id INTEGER DEFAULT NULL, nano_id VARCHAR(32) NOT NULL, `filename` VARCHAR(255) NOT NULL, content_type VARCHAR(255) NOT NULL, expires_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , size INTEGER UNSIGNED NOT NULL, note CLOB DEFAULT NULL, CONSTRAINT FK_2DF8B3C5ED61C63D FOREIGN KEY (guest_link_id) REFERENCES guest_links (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_2DF8B3C5ED61C63D ON entries (guest_link_id)');
        $this->addSql('CREATE TABLE IF NOT EXISTS `entry_chunks` (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, entry_id INTEGER NOT NULL, data_chunk_index SMALLINT NOT NULL, data_chunk BLOB NOT NULL, CONSTRAINT FK_F87CD80BBA364942 FOREIGN KEY (entry_id) REFERENCES entries (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_F87CD80BBA364942 ON entry_chunks (entry_id)');
        $this->addSql('CREATE TABLE IF NOT EXISTS `guest_links` (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nano_id VARCHAR(32) NOT NULL, label VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , expires_at DATETIME DEFAULT NULL, max_file_bytes INTEGER DEFAULT NULL, max_uploads INTEGER DEFAULT NULL, file_expiration VARCHAR(255) DEFAULT NULL, disabled BOOLEAN NOT NULL)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE downloads');
        $this->addSql('DROP TABLE entries');
        $this->addSql('DROP TABLE entry_chunks');
        $this->addSql('DROP TABLE guest_links');
    }
}
