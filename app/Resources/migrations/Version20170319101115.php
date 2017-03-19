<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170319101115 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE aws_cloud_instances ADD flavor_internal_name VARCHAR(128) NOT NULL, ADD image_internal_name VARCHAR(128) NOT NULL;');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE aws_cloud_instances DROP image_internal_name;');
        $this->addSql('ALTER TABLE aws_cloud_instances DROP flavor_internal_name;');
    }
}
