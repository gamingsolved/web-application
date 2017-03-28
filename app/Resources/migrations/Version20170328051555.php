<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170328051555 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE remote_desktop_events DROP FOREIGN KEY FK_B59D83F31128F5D;");
        $this->addSql("DROP INDEX IDX_B59D83F31128F5D ON remote_desktop_events;");
        $this->addSql("ALTER TABLE remote_desktop_events DROP billable_item_id;");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) {}
}
