<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250721133053 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates the food table with indexes on name and type for optimized search';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE food (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                quantity_in_grams INT NOT NULL,
                type VARCHAR(20) NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
        ');

        $this->addSql('CREATE UNIQUE INDEX idx_food_name_type ON food (name, type);');
        $this->addSql('CREATE INDEX idx_food_name ON food (name);');
        $this->addSql('CREATE INDEX idx_food_type ON food (type);');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE food');
    }
}
