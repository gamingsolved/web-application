<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170319102635 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE aws_cloud_instances ADD status SMALLINT NOT NULL, ADD runstatus SMALLINT NOT NULL;');
        $this->addSql('ALTER TABLE remote_desktops CHANGE kind kind SMALLINT NOT NULL, CHANGE cloud_instance_provider cloud_instance_provider SMALLINT NOT NULL;');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE remote_desktops CHANGE kind kind INT NOT NULL, CHANGE cloud_instance_provider cloud_instance_provider INT NOT NULL;');
        $this->addSql('ALTER TABLE aws_cloud_instances DROP status;');
        $this->addSql('ALTER TABLE aws_cloud_instances DROP runstatus;');
    }
}
