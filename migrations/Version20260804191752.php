<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260804191752 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add audience-specific scopes to groups.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE group_scope (id UUID NOT NULL, group_id UUID NOT NULL, audience VARCHAR(3000) NOT NULL, audience_hash VARCHAR(64) NOT NULL, scope VARCHAR(100) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_group_scope_group_id ON group_scope (group_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_group_scope_audience_scope ON group_scope (group_id, audience_hash, scope)');
        $this->addSql('COMMENT ON COLUMN group_scope.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN group_scope.group_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE group_scope ADD CONSTRAINT fk_group_scope_group_id FOREIGN KEY (group_id) REFERENCES "group" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE group_scope');
    }
}
