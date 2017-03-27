<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170327150942 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE billable_items ADD remote_desktops_id CHAR(36) DEFAULT NULL COMMENT '(DC2Type:guid)';");

        $this->addSql("ALTER TABLE billable_items ADD CONSTRAINT FK_8A66732BCF108191 FOREIGN KEY (remote_desktops_id) REFERENCES remote_desktops (id);");

        $this->addSql("CREATE INDEX IDX_8A66732BCF108191 ON billable_items (remote_desktops_id);");

        $this->addSql("ALTER TABLE remote_desktop_events CHANGE remote_desktops_id remote_desktops_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)';");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE billable_items DROP COLUMN remote_desktops_id;");

        $this->addSql("ALTER TABLE billable_items DROP FOREIGN KEY FK_8A66732BCF108191;");

        $this->addSql("DROP INDEX IDX_8A66732BCF108191 ON billable_items (remote_desktops_id);");

        $this->addSql("ALTER TABLE remote_desktop_events CHANGE remote_desktops_id remote_desktops_id CHAR(36) NULL;");
    }
}
