<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260803085721 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add mandatory audience URL to clients.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform),
            'This migration is only supported on PostgreSQL.'
        );

        $this->addSql('ALTER TABLE client ADD audience VARCHAR(3000) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform),
            'This migration is only supported on PostgreSQL.'
        );

        $this->addSql('ALTER TABLE client DROP audience');
    }
}
