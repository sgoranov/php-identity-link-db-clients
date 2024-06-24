<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240531124612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE client (id UUID NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(3000) NOT NULL, redirect_uri VARCHAR(3000) NOT NULL, grant_types JSON NOT NULL, scopes JSON NOT NULL, is_public BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C74404555E237E06 ON client (name)');
        $this->addSql('COMMENT ON COLUMN client.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE client_group (client_id UUID NOT NULL, group_id UUID NOT NULL, PRIMARY KEY(client_id, group_id))');
        $this->addSql('CREATE INDEX IDX_CEADD87219EB6921 ON client_group (client_id)');
        $this->addSql('CREATE INDEX IDX_CEADD872FE54D947 ON client_group (group_id)');
        $this->addSql('COMMENT ON COLUMN client_group.client_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN client_group.group_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE "group" (id UUID NOT NULL, name VARCHAR(100) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN "group".id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE secret (id UUID NOT NULL, client_id UUID NOT NULL, password VARCHAR(100) NOT NULL, password_hint VARCHAR(500) NOT NULL, expiration_date_time TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5CA2E8E519EB6921 ON secret (client_id)');
        $this->addSql('COMMENT ON COLUMN secret.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN secret.client_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE client_group ADD CONSTRAINT FK_CEADD87219EB6921 FOREIGN KEY (client_id) REFERENCES client (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE client_group ADD CONSTRAINT FK_CEADD872FE54D947 FOREIGN KEY (group_id) REFERENCES "group" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE secret ADD CONSTRAINT FK_5CA2E8E519EB6921 FOREIGN KEY (client_id) REFERENCES client (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE client_group DROP CONSTRAINT FK_CEADD87219EB6921');
        $this->addSql('ALTER TABLE client_group DROP CONSTRAINT FK_CEADD872FE54D947');
        $this->addSql('ALTER TABLE secret DROP CONSTRAINT FK_5CA2E8E519EB6921');
        $this->addSql('DROP TABLE client');
        $this->addSql('DROP TABLE client_group');
        $this->addSql('DROP TABLE "group"');
        $this->addSql('DROP TABLE secret');
    }
}
