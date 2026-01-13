<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260113203618 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Entry <-> guest Link Relation: ON DELETE SET NULL if guest-link was deleted';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__entries AS SELECT id, guest_link_id, uniq_link_id, filename, safe_filename, content_type, expires_at, created_at, updated_at, size, note FROM entries');
        $this->addSql('DROP TABLE entries');
        $this->addSql('CREATE TABLE entries (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, guest_link_id INTEGER DEFAULT NULL, uniq_link_id BLOB NOT NULL --(DC2Type:ulid)
        , filename VARCHAR(255) NOT NULL, safe_filename VARCHAR(255) DEFAULT NULL, content_type VARCHAR(255) NOT NULL, expires_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , size BIGINT UNSIGNED NOT NULL, note CLOB DEFAULT NULL, CONSTRAINT FK_2DF8B3C5ED61C63D FOREIGN KEY (guest_link_id) REFERENCES "guest_links" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO entries (id, guest_link_id, uniq_link_id, filename, safe_filename, content_type, expires_at, created_at, updated_at, size, note) SELECT id, guest_link_id, uniq_link_id, filename, safe_filename, content_type, expires_at, created_at, updated_at, size, note FROM __temp__entries');
        $this->addSql('DROP TABLE __temp__entries');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2DF8B3C5B37A49FC ON entries (uniq_link_id)');
        $this->addSql('CREATE INDEX IDX_2DF8B3C5ED61C63D ON entries (guest_link_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__entries AS SELECT id, guest_link_id, uniq_link_id, "filename", "safe_filename", content_type, expires_at, created_at, updated_at, size, note FROM "entries"');
        $this->addSql('DROP TABLE "entries"');
        $this->addSql('CREATE TABLE "entries" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, guest_link_id INTEGER DEFAULT NULL, uniq_link_id BLOB NOT NULL --(DC2Type:ulid)
        , "filename" VARCHAR(255) NOT NULL, "safe_filename" VARCHAR(255) DEFAULT NULL, content_type VARCHAR(255) NOT NULL, expires_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , size BIGINT UNSIGNED NOT NULL, note CLOB DEFAULT NULL, CONSTRAINT FK_2DF8B3C5ED61C63D FOREIGN KEY (guest_link_id) REFERENCES guest_links (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO "entries" (id, guest_link_id, uniq_link_id, "filename", "safe_filename", content_type, expires_at, created_at, updated_at, size, note) SELECT id, guest_link_id, uniq_link_id, "filename", "safe_filename", content_type, expires_at, created_at, updated_at, size, note FROM __temp__entries');
        $this->addSql('DROP TABLE __temp__entries');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2DF8B3C5B37A49FC ON "entries" (uniq_link_id)');
        $this->addSql('CREATE INDEX IDX_2DF8B3C5ED61C63D ON "entries" (guest_link_id)');
    }
}
