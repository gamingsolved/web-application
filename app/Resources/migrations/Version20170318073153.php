<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170318073153 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('CREATE TABLE aws_cloud_instances (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', remote_desktops_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', INDEX IDX_6B809327CF108191 (remote_desktops_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE remote_desktops (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', users_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', is_running TINYINT(1) NOT NULL, streaming_client SMALLINT NOT NULL, cloud_instance_provider SMALLINT NOT NULL, INDEX IDX_192EB6B367B3B43D (users_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;');
        $this->addSql('ALTER TABLE aws_cloud_instances ADD CONSTRAINT FK_6B809327CF108191 FOREIGN KEY (remote_desktops_id) REFERENCES remote_desktops (id);');
        $this->addSql('ALTER TABLE remote_desktops ADD CONSTRAINT FK_192EB6B367B3B43D FOREIGN KEY (users_id) REFERENCES users (id);');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('DROP TABLE remote_desktops');
        $this->addSql('DROP TABLE aws_cloud_instances');
    }
}
