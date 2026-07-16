<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716121917 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add application, terms of service, privacy policy, and logo URL fields to client table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE client ADD application_url VARCHAR(3000) DEFAULT NULL');
        $this->addSql('ALTER TABLE client ADD terms_of_service_url VARCHAR(3000) DEFAULT NULL');
        $this->addSql('ALTER TABLE client ADD privacy_policy_url VARCHAR(3000) DEFAULT NULL');
        $this->addSql('ALTER TABLE client ADD logo_url VARCHAR(3000) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE client DROP application_url');
        $this->addSql('ALTER TABLE client DROP terms_of_service_url');
        $this->addSql('ALTER TABLE client DROP privacy_policy_url');
        $this->addSql('ALTER TABLE client DROP logo_url');
    }
}
