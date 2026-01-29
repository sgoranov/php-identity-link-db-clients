<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\Migrations\AbstractMigration;

final class Version20260129164210 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store client redirect URIs as JSON arrays instead of a single string.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform),
            'This migration is only supported on PostgreSQL.'
        );

        $this->addSql('ALTER TABLE client ALTER COLUMN redirect_uri TYPE JSON USING json_build_array(redirect_uri)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform),
            'This migration is only supported on PostgreSQL.'
        );

        $this->addSql("ALTER TABLE client ALTER COLUMN redirect_uri TYPE VARCHAR(3000) USING redirect_uri->>0");
    }
}
