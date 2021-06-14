<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210614122112 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE address (id INT AUTO_INCREMENT NOT NULL, pool_id INT DEFAULT NULL, reaction_id INT DEFAULT NULL, company VARCHAR(255) DEFAULT NULL, street VARCHAR(255) DEFAULT NULL, street_format VARCHAR(255) DEFAULT NULL, zip VARCHAR(255) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, country VARCHAR(255) DEFAULT NULL, gender VARCHAR(255) DEFAULT NULL, first_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, status TINYINT(1) DEFAULT \'1\' NOT NULL, phone VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, comment LONGTEXT DEFAULT NULL, position VARCHAR(255) DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, file_url VARCHAR(255) DEFAULT NULL, var1 VARCHAR(255) DEFAULT NULL, var2 VARCHAR(255) DEFAULT NULL, var3 VARCHAR(255) DEFAULT NULL, var4 VARCHAR(255) DEFAULT NULL, var5 VARCHAR(255) DEFAULT NULL, blacklist TINYINT(1) DEFAULT \'0\' NOT NULL, INDEX IDX_D4E6F817B3406DF (pool_id), INDEX IDX_D4E6F81813C7171 (reaction_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaign (id INT AUTO_INCREMENT NOT NULL, template_id INT DEFAULT NULL, pool_id INT DEFAULT NULL, address_id INT DEFAULT NULL, date VARCHAR(255) NOT NULL, file VARCHAR(255) DEFAULT NULL, INDEX IDX_1F1512DD5DA0FB8 (template_id), INDEX IDX_1F1512DD7B3406DF (pool_id), INDEX IDX_1F1512DDF5B7AF75 (address_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE name_gender (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, gender VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pool (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, color VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_AF91A9865E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reaction (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE template (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, section VARCHAR(255) DEFAULT NULL, content LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE address ADD CONSTRAINT FK_D4E6F817B3406DF FOREIGN KEY (pool_id) REFERENCES pool (id)');
        $this->addSql('ALTER TABLE address ADD CONSTRAINT FK_D4E6F81813C7171 FOREIGN KEY (reaction_id) REFERENCES reaction (id)');
        $this->addSql('ALTER TABLE campaign ADD CONSTRAINT FK_1F1512DD5DA0FB8 FOREIGN KEY (template_id) REFERENCES template (id)');
        $this->addSql('ALTER TABLE campaign ADD CONSTRAINT FK_1F1512DD7B3406DF FOREIGN KEY (pool_id) REFERENCES pool (id)');
        $this->addSql('ALTER TABLE campaign ADD CONSTRAINT FK_1F1512DDF5B7AF75 FOREIGN KEY (address_id) REFERENCES address (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE campaign DROP FOREIGN KEY FK_1F1512DDF5B7AF75');
        $this->addSql('ALTER TABLE address DROP FOREIGN KEY FK_D4E6F817B3406DF');
        $this->addSql('ALTER TABLE campaign DROP FOREIGN KEY FK_1F1512DD7B3406DF');
        $this->addSql('ALTER TABLE address DROP FOREIGN KEY FK_D4E6F81813C7171');
        $this->addSql('ALTER TABLE campaign DROP FOREIGN KEY FK_1F1512DD5DA0FB8');
        $this->addSql('DROP TABLE address');
        $this->addSql('DROP TABLE campaign');
        $this->addSql('DROP TABLE name_gender');
        $this->addSql('DROP TABLE pool');
        $this->addSql('DROP TABLE reaction');
        $this->addSql('DROP TABLE template');
    }
}
