<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170318074850 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE remote_desktops CHANGE cloud_instance_provider cloud_instance_provider_id SMALLINT NOT NULL;');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE remote_desktops CHANGE cloud_instance_provider_id cloud_instance_provider SMALLINT NOT NULL;');
    }
}
