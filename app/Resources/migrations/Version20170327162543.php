<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170327162543 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE billable_items CHANGE remote_desktops_id remote_desktops_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)';");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE billable_items CHANGE remote_desktops_id remote_desktops_id CHAR(36) NULL COMMENT '(DC2Type:guid)';");
    }
}
