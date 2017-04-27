<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170425071357 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE aws_cloud_instances ADD root_volume_size INT NOT NULL, ADD additional_volume_size INT NOT NULL;');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE aws_cloud_instances DROP COLUMN root_volume_size;');
        $this->addSql('ALTER TABLE aws_cloud_instances DROP COLUMN additional_volume_size;');
    }
}
