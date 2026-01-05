<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251223212849 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Basic Tables - Part 2';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__entries AS SELECT id, guest_link_id, filename, content_type, expires_at, created_at, updated_at, size, note FROM entries');
        $this->addSql('DROP TABLE entries');
        $this->addSql('CREATE TABLE entries (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, guest_link_id INTEGER DEFAULT NULL, filename VARCHAR(255) NOT NULL, content_type VARCHAR(255) NOT NULL, expires_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , size INTEGER UNSIGNED NOT NULL, note CLOB DEFAULT NULL, uniq_link_id BLOB NOT NULL --(DC2Type:ulid)
        , CONSTRAINT FK_2DF8B3C5ED61C63D FOREIGN KEY (guest_link_id) REFERENCES guest_links (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO entries (id, guest_link_id, filename, content_type, expires_at, created_at, updated_at, size, note) SELECT id, guest_link_id, filename, content_type, expires_at, created_at, updated_at, size, note FROM __temp__entries');
        $this->addSql('DROP TABLE __temp__entries');
        $this->addSql('CREATE INDEX IDX_2DF8B3C5ED61C63D ON entries (guest_link_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2DF8B3C5B37A49FC ON entries (uniq_link_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__guest_links AS SELECT id, label, created_at, updated_at, expires_at, max_file_bytes, max_uploads, file_expiration, disabled FROM guest_links');
        $this->addSql('DROP TABLE guest_links');
        $this->addSql('CREATE TABLE guest_links (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, label VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , expires_at DATETIME DEFAULT NULL, max_file_bytes INTEGER DEFAULT NULL, max_uploads INTEGER DEFAULT NULL, file_expiration VARCHAR(255) DEFAULT NULL, disabled BOOLEAN NOT NULL, uniq_link_id BLOB NOT NULL --(DC2Type:ulid)
        )');
        $this->addSql('INSERT INTO guest_links (id, label, created_at, updated_at, expires_at, max_file_bytes, max_uploads, file_expiration, disabled) SELECT id, label, created_at, updated_at, expires_at, max_file_bytes, max_uploads, file_expiration, disabled FROM __temp__guest_links');
        $this->addSql('DROP TABLE __temp__guest_links');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C47C360CB37A49FC ON guest_links (uniq_link_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__entries AS SELECT id, guest_link_id, "filename", content_type, expires_at, created_at, updated_at, size, note FROM "entries"');
        $this->addSql('DROP TABLE "entries"');
        $this->addSql('CREATE TABLE "entries" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, guest_link_id INTEGER DEFAULT NULL, "filename" VARCHAR(255) NOT NULL, content_type VARCHAR(255) NOT NULL, expires_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , size INTEGER UNSIGNED NOT NULL, note CLOB DEFAULT NULL, nano_id VARCHAR(32) NOT NULL, CONSTRAINT FK_2DF8B3C5ED61C63D FOREIGN KEY (guest_link_id) REFERENCES "guest_links" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO "entries" (id, guest_link_id, "filename", content_type, expires_at, created_at, updated_at, size, note) SELECT id, guest_link_id, "filename", content_type, expires_at, created_at, updated_at, size, note FROM __temp__entries');
        $this->addSql('DROP TABLE __temp__entries');
        $this->addSql('CREATE INDEX IDX_2DF8B3C5ED61C63D ON "entries" (guest_link_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__guest_links AS SELECT id, label, created_at, updated_at, expires_at, max_file_bytes, max_uploads, file_expiration, disabled FROM "guest_links"');
        $this->addSql('DROP TABLE "guest_links"');
        $this->addSql('CREATE TABLE "guest_links" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, label VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , expires_at DATETIME DEFAULT NULL, max_file_bytes INTEGER DEFAULT NULL, max_uploads INTEGER DEFAULT NULL, file_expiration VARCHAR(255) DEFAULT NULL, disabled BOOLEAN NOT NULL, nano_id VARCHAR(32) NOT NULL)');
        $this->addSql('INSERT INTO "guest_links" (id, label, created_at, updated_at, expires_at, max_file_bytes, max_uploads, file_expiration, disabled) SELECT id, label, created_at, updated_at, expires_at, max_file_bytes, max_uploads, file_expiration, disabled FROM __temp__guest_links');
        $this->addSql('DROP TABLE __temp__guest_links');
    }
}
