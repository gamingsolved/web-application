<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170328173640 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE account_movements ADD payment_instruction_id INT DEFAULT NULL;");

        $this->addSql("ALTER TABLE account_movements ADD payment_finished TINYINT(1) DEFAULT NULL;");

        $this->addSql("ALTER TABLE account_movements ADD CONSTRAINT FK_1A59AD7A8789B572 FOREIGN KEY (payment_instruction_id) REFERENCES payment_instructions (id);");

        $this->addSql("CREATE UNIQUE INDEX UNIQ_1A59AD7A8789B572 ON account_movements (payment_instruction_id);");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE account_movements DROP COLUMN payment_finished");

        $this->addSql("ALTER TABLE account_movements DROP COLUMN payment_instruction_id");
    }
}
