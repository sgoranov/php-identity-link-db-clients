<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260529120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add system-managed flags to clients, groups, and secrets.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform),
            'This migration is only supported on PostgreSQL.'
        );

        $this->addSql('ALTER TABLE client ADD is_system BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE "group" ADD is_system BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE secret ADD is_system BOOLEAN DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform),
            'This migration is only supported on PostgreSQL.'
        );

        $this->addSql('ALTER TABLE client DROP is_system');
        $this->addSql('ALTER TABLE "group" DROP is_system');
        $this->addSql('ALTER TABLE secret DROP is_system');
    }
}
