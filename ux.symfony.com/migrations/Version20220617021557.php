<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220617021557 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE todo_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, todo_list_id INTEGER NOT NULL, description VARCHAR(255) NOT NULL, priority INTEGER NOT NULL)');
        $this->addSql('CREATE INDEX IDX_40CA4301E8A7DCFA ON todo_item (todo_list_id)');
        $this->addSql('CREATE TABLE todo_list (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE todo_item');
        $this->addSql('DROP TABLE todo_list');
    }
}
