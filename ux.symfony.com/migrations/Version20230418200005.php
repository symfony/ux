<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230418200005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE TABLE invoice (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, customer_name VARCHAR(255) NOT NULL, customer_email VARCHAR(255) NOT NULL, tax_rate INTEGER NOT NULL)');
        $this->addSql('CREATE TABLE invoice_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, invoice_id INTEGER NOT NULL, product_id INTEGER NOT NULL, quantity INTEGER NOT NULL, CONSTRAINT FK_1DDE477B2989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_1DDE477B4584665A FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_1DDE477B2989F1FD ON invoice_item (invoice_id)');
        $this->addSql('CREATE INDEX IDX_1DDE477B4584665A ON invoice_item (product_id)');
        $this->addSql('CREATE TABLE product (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, category_id INTEGER NOT NULL, name VARCHAR(255) NOT NULL, price INTEGER NOT NULL, CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_D34A04AD12469DE2 ON product (category_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__food AS SELECT id, name, votes FROM food');
        $this->addSql('DROP TABLE food');
        $this->addSql('CREATE TABLE food (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, votes INTEGER NOT NULL)');
        $this->addSql('INSERT INTO food (id, name, votes) SELECT id, name, votes FROM __temp__food');
        $this->addSql('DROP TABLE __temp__food');
        $this->addSql('CREATE TEMPORARY TABLE __temp__todo_item AS SELECT id, todo_list_id, description, priority FROM todo_item');
        $this->addSql('DROP TABLE todo_item');
        $this->addSql('CREATE TABLE todo_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, todo_list_id INTEGER NOT NULL, description VARCHAR(255) NOT NULL, priority INTEGER NOT NULL, CONSTRAINT FK_40CA4301E8A7DCFA FOREIGN KEY (todo_list_id) REFERENCES todo_list (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO todo_item (id, todo_list_id, description, priority) SELECT id, todo_list_id, description, priority FROM __temp__todo_item');
        $this->addSql('DROP TABLE __temp__todo_item');
        $this->addSql('CREATE INDEX IDX_40CA4301E8A7DCFA ON todo_item (todo_list_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE invoice');
        $this->addSql('DROP TABLE invoice_item');
        $this->addSql('DROP TABLE product');
        $this->addSql('CREATE TEMPORARY TABLE __temp__food AS SELECT id, name, votes FROM food');
        $this->addSql('DROP TABLE food');
        $this->addSql('CREATE TABLE food (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, votes INTEGER DEFAULT 0 NOT NULL)');
        $this->addSql('INSERT INTO food (id, name, votes) SELECT id, name, votes FROM __temp__food');
        $this->addSql('DROP TABLE __temp__food');
        $this->addSql('CREATE TEMPORARY TABLE __temp__todo_item AS SELECT id, todo_list_id, description, priority FROM todo_item');
        $this->addSql('DROP TABLE todo_item');
        $this->addSql('CREATE TABLE todo_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, todo_list_id INTEGER NOT NULL, description VARCHAR(255) NOT NULL, priority INTEGER NOT NULL)');
        $this->addSql('INSERT INTO todo_item (id, todo_list_id, description, priority) SELECT id, todo_list_id, description, priority FROM __temp__todo_item');
        $this->addSql('DROP TABLE __temp__todo_item');
        $this->addSql('CREATE INDEX IDX_40CA4301E8A7DCFA ON todo_item (todo_list_id)');
    }
}
