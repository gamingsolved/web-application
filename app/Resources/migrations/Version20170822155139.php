<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170822155139 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE paperspace_cloud_instances (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', remote_desktops_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', ps_instance_id VARCHAR(128) DEFAULT NULL, status SMALLINT NOT NULL, runstatus SMALLINT NOT NULL, flavor_internal_name VARCHAR(128) NOT NULL, image_internal_name VARCHAR(128) NOT NULL, region_internal_name VARCHAR(128) NOT NULL, root_volume_size INT NOT NULL, additional_volume_size INT NOT NULL, public_address VARCHAR(128) DEFAULT NULL, admin_password VARCHAR(128) DEFAULT NULL, schedule_for_stop_at DATETIME DEFAULT NULL, INDEX IDX_A6D93C16CF108191 (remote_desktops_id), INDEX ps_instance_id_index (ps_instance_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE paperspace_cloud_instances ADD CONSTRAINT FK_A6D93C16CF108191 FOREIGN KEY (remote_desktops_id) REFERENCES remote_desktops (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE paperspace_cloud_instances');
    }
}
