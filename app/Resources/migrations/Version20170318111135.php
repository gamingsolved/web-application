<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170318111135 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE aws_cloud_instances ADD regionInternalName VARCHAR(128) NOT NULL;');
        $this->addSql('ALTER TABLE remote_desktops ADD title VARCHAR(128) NOT NULL, ADD kind INT NOT NULL;');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE remote_desktops DROP title');
        $this->addSql('ALTER TABLE aws_cloud_instances DROP regionInternalName');
    }
}
