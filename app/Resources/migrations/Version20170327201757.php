<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170327201757 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("CREATE TABLE account_movements (id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', users_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', billable_items_id CHAR(36) DEFAULT NULL COMMENT '(DC2Type:guid)', datetime_occured DATETIME NOT NULL, movement_type SMALLINT NOT NULL, amount DOUBLE PRECISION NOT NULL, INDEX IDX_1A59AD7A67B3B43D (users_id), UNIQUE INDEX UNIQ_1A59AD7AF29A82E3 (billable_items_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");

        $this->addSql("ALTER TABLE account_movements ADD CONSTRAINT FK_1A59AD7A67B3B43D FOREIGN KEY (users_id) REFERENCES users (id);");

        $this->addSql("ALTER TABLE account_movements ADD CONSTRAINT FK_1A59AD7AF29A82E3 FOREIGN KEY (billable_items_id) REFERENCES billable_items (id);");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("DROP TABLE account_movements");
    }
}
