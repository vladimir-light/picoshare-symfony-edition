<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251230214204 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Safe Filename for URL, current_uploads for GuestLink and data_chunk_size for single chunk';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `entries` ADD COLUMN "safe_filename" VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE `entry_chunks` ADD COLUMN data_chunk_size SMALLINT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE `guest_links` ADD COLUMN current_uploads INTEGER DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__entries AS SELECT id, guest_link_id, uniq_link_id, "filename", content_type, expires_at, created_at, updated_at, size, note FROM "entries"');
        $this->addSql('DROP TABLE "entries"');
        $this->addSql('CREATE TABLE "entries" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, guest_link_id INTEGER DEFAULT NULL, uniq_link_id BLOB NOT NULL --(DC2Type:ulid)
        , "filename" VARCHAR(255) NOT NULL, content_type VARCHAR(255) NOT NULL, expires_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , size INTEGER UNSIGNED NOT NULL, note CLOB DEFAULT NULL, CONSTRAINT FK_2DF8B3C5ED61C63D FOREIGN KEY (guest_link_id) REFERENCES "guest_links" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO "entries" (id, guest_link_id, uniq_link_id, "filename", content_type, expires_at, created_at, updated_at, size, note) SELECT id, guest_link_id, uniq_link_id, "filename", content_type, expires_at, created_at, updated_at, size, note FROM __temp__entries');
        $this->addSql('DROP TABLE __temp__entries');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2DF8B3C5B37A49FC ON "entries" (uniq_link_id)');
        $this->addSql('CREATE INDEX IDX_2DF8B3C5ED61C63D ON "entries" (guest_link_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__entry_chunks AS SELECT id, entry_id, data_chunk, data_chunk_index FROM "entry_chunks"');
        $this->addSql('DROP TABLE "entry_chunks"');
        $this->addSql('CREATE TABLE "entry_chunks" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, entry_id INTEGER NOT NULL, data_chunk BLOB NOT NULL, data_chunk_index SMALLINT NOT NULL, CONSTRAINT FK_F87CD80BBA364942 FOREIGN KEY (entry_id) REFERENCES "entries" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO "entry_chunks" (id, entry_id, data_chunk, data_chunk_index) SELECT id, entry_id, data_chunk, data_chunk_index FROM __temp__entry_chunks');
        $this->addSql('DROP TABLE __temp__entry_chunks');
        $this->addSql('CREATE INDEX IDX_F87CD80BBA364942 ON "entry_chunks" (entry_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__guest_links AS SELECT id, uniq_link_id, label, created_at, updated_at, expires_at, max_file_bytes, max_uploads, file_expiration, disabled FROM "guest_links"');
        $this->addSql('DROP TABLE "guest_links"');
        $this->addSql('CREATE TABLE "guest_links" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uniq_link_id BLOB NOT NULL --(DC2Type:ulid)
        , label VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , expires_at DATETIME DEFAULT NULL, max_file_bytes INTEGER DEFAULT NULL, max_uploads INTEGER DEFAULT NULL, file_expiration VARCHAR(255) DEFAULT NULL, disabled BOOLEAN NOT NULL)');
        $this->addSql('INSERT INTO "guest_links" (id, uniq_link_id, label, created_at, updated_at, expires_at, max_file_bytes, max_uploads, file_expiration, disabled) SELECT id, uniq_link_id, label, created_at, updated_at, expires_at, max_file_bytes, max_uploads, file_expiration, disabled FROM __temp__guest_links');
        $this->addSql('DROP TABLE __temp__guest_links');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C47C360CB37A49FC ON "guest_links" (uniq_link_id)');
    }
}
