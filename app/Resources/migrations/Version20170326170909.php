<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170326170909 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("CREATE TABLE billable_items (id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', item_type SMALLINT NOT NULL, timewindow_begin DATETIME NOT NULL, timewindow_end DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");

        $this->addSql("CREATE TABLE remote_desktop_events (id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', remote_desktops_id CHAR(36) DEFAULT NULL COMMENT '(DC2Type:guid)', billable_item_id CHAR(36) DEFAULT NULL COMMENT '(DC2Type:guid)', event_type SMALLINT NOT NULL, datetime_occured DATETIME NOT NULL, INDEX IDX_B59D83FCF108191 (remote_desktops_id), INDEX IDX_B59D83F31128F5D (billable_item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");

        $this->addSql("ALTER TABLE remote_desktop_events ADD CONSTRAINT FK_B59D83FCF108191 FOREIGN KEY (remote_desktops_id) REFERENCES remote_desktops (id);");

        $this->addSql("ALTER TABLE remote_desktop_events ADD CONSTRAINT FK_B59D83F31128F5D FOREIGN KEY (billable_item_id) REFERENCES billable_items (id);");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("DROP TABLE remote_desktop_events;");

        $this->addSql("DROP TABLE billable_items;");
    }
}
