<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260102221239 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'UNSIGNED BIG INT for `Entry::size`, UNSIGNED INT for `EntryChunk::dataChunkSize`';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__entries AS SELECT id, guest_link_id, uniq_link_id, filename, safe_filename, content_type, expires_at, created_at, updated_at, size, note FROM entries');
        $this->addSql('DROP TABLE entries');
        $this->addSql('CREATE TABLE entries (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, guest_link_id INTEGER DEFAULT NULL, uniq_link_id BLOB NOT NULL --(DC2Type:ulid)
        , filename VARCHAR(255) NOT NULL, safe_filename VARCHAR(255) DEFAULT NULL, content_type VARCHAR(255) NOT NULL, expires_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , size BIGINT UNSIGNED NOT NULL, note CLOB DEFAULT NULL, CONSTRAINT FK_2DF8B3C5ED61C63D FOREIGN KEY (guest_link_id) REFERENCES guest_links (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO entries (id, guest_link_id, uniq_link_id, filename, safe_filename, content_type, expires_at, created_at, updated_at, size, note) SELECT id, guest_link_id, uniq_link_id, filename, safe_filename, content_type, expires_at, created_at, updated_at, size, note FROM __temp__entries');
        $this->addSql('DROP TABLE __temp__entries');
        $this->addSql('CREATE INDEX IDX_2DF8B3C5ED61C63D ON entries (guest_link_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2DF8B3C5B37A49FC ON entries (uniq_link_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__entry_chunks AS SELECT id, entry_id, data_chunk, data_chunk_index, data_chunk_size FROM entry_chunks');
        $this->addSql('DROP TABLE entry_chunks');
        $this->addSql('CREATE TABLE entry_chunks (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, entry_id INTEGER NOT NULL, data_chunk BLOB NOT NULL, data_chunk_index SMALLINT NOT NULL, data_chunk_size INTEGER UNSIGNED DEFAULT 0 NOT NULL, CONSTRAINT FK_F87CD80BBA364942 FOREIGN KEY (entry_id) REFERENCES entries (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO entry_chunks (id, entry_id, data_chunk, data_chunk_index, data_chunk_size) SELECT id, entry_id, data_chunk, data_chunk_index, data_chunk_size FROM __temp__entry_chunks');
        $this->addSql('DROP TABLE __temp__entry_chunks');
        $this->addSql('CREATE INDEX IDX_F87CD80BBA364942 ON entry_chunks (entry_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__guest_links AS SELECT id, uniq_link_id, label, created_at, updated_at, expires_at, max_file_bytes, max_uploads, current_uploads, file_expiration, disabled FROM guest_links');
        $this->addSql('DROP TABLE guest_links');
        $this->addSql('CREATE TABLE guest_links (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uniq_link_id BLOB NOT NULL --(DC2Type:ulid)
        , label VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , expires_at DATETIME DEFAULT NULL, max_file_bytes INTEGER DEFAULT NULL, max_uploads INTEGER DEFAULT NULL, current_uploads INTEGER DEFAULT 0 NOT NULL, file_expiration VARCHAR(255) DEFAULT NULL, disabled BOOLEAN NOT NULL)');
        $this->addSql('INSERT INTO guest_links (id, uniq_link_id, label, created_at, updated_at, expires_at, max_file_bytes, max_uploads, current_uploads, file_expiration, disabled) SELECT id, uniq_link_id, label, created_at, updated_at, expires_at, max_file_bytes, max_uploads, current_uploads, file_expiration, disabled FROM __temp__guest_links');
        $this->addSql('DROP TABLE __temp__guest_links');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C47C360CB37A49FC ON guest_links (uniq_link_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__entries AS SELECT id, guest_link_id, uniq_link_id, "filename", "safe_filename", content_type, expires_at, created_at, updated_at, size, note FROM "entries"');
        $this->addSql('DROP TABLE "entries"');
        $this->addSql('CREATE TABLE "entries" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, guest_link_id INTEGER DEFAULT NULL, uniq_link_id BLOB NOT NULL --(DC2Type:ulid)
        , "filename" VARCHAR(255) NOT NULL, "safe_filename" VARCHAR(255) DEFAULT NULL, content_type VARCHAR(255) NOT NULL, expires_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , size INTEGER UNSIGNED NOT NULL, note CLOB DEFAULT NULL, CONSTRAINT FK_2DF8B3C5ED61C63D FOREIGN KEY (guest_link_id) REFERENCES "guest_links" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO "entries" (id, guest_link_id, uniq_link_id, "filename", "safe_filename", content_type, expires_at, created_at, updated_at, size, note) SELECT id, guest_link_id, uniq_link_id, "filename", "safe_filename", content_type, expires_at, created_at, updated_at, size, note FROM __temp__entries');
        $this->addSql('DROP TABLE __temp__entries');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2DF8B3C5B37A49FC ON "entries" (uniq_link_id)');
        $this->addSql('CREATE INDEX IDX_2DF8B3C5ED61C63D ON "entries" (guest_link_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__entry_chunks AS SELECT id, entry_id, data_chunk, data_chunk_size, data_chunk_index FROM "entry_chunks"');
        $this->addSql('DROP TABLE "entry_chunks"');
        $this->addSql('CREATE TABLE "entry_chunks" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, entry_id INTEGER NOT NULL, data_chunk BLOB NOT NULL, data_chunk_size SMALLINT DEFAULT 0 NOT NULL, data_chunk_index SMALLINT NOT NULL, CONSTRAINT FK_F87CD80BBA364942 FOREIGN KEY (entry_id) REFERENCES "entries" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO "entry_chunks" (id, entry_id, data_chunk, data_chunk_size, data_chunk_index) SELECT id, entry_id, data_chunk, data_chunk_size, data_chunk_index FROM __temp__entry_chunks');
        $this->addSql('DROP TABLE __temp__entry_chunks');
        $this->addSql('CREATE INDEX IDX_F87CD80BBA364942 ON "entry_chunks" (entry_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__guest_links AS SELECT id, uniq_link_id, label, created_at, updated_at, expires_at, max_file_bytes, max_uploads, current_uploads, file_expiration, disabled FROM "guest_links"');
        $this->addSql('DROP TABLE "guest_links"');
        $this->addSql('CREATE TABLE "guest_links" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uniq_link_id BLOB NOT NULL --
(DC2Type:ulid)
        , label VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL --
(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --
(DC2Type:datetime_immutable)
        , expires_at DATETIME DEFAULT NULL, max_file_bytes INTEGER DEFAULT NULL, max_uploads INTEGER DEFAULT NULL, current_uploads INTEGER DEFAULT 0 NOT NULL, file_expiration VARCHAR(255) DEFAULT NULL, disabled BOOLEAN NOT NULL)');
        $this->addSql('INSERT INTO "guest_links" (id, uniq_link_id, label, created_at, updated_at, expires_at, max_file_bytes, max_uploads, current_uploads, file_expiration, disabled) SELECT id, uniq_link_id, label, created_at, updated_at, expires_at, max_file_bytes, max_uploads, current_uploads, file_expiration, disabled FROM __temp__guest_links');
        $this->addSql('DROP TABLE __temp__guest_links');
    }
}
