<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250427221916 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create calls table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE calls (
            id INT AUTO_INCREMENT NOT NULL,
            date_time DATETIME NOT NULL,
            line_id INT NOT NULL,
            phone_number VARCHAR(50) NOT NULL,
            operator_id CHAR(36) NOT NULL,
            duration INT NOT NULL,
            INDEX IDX_C3F33F5F4D7B7542 (line_id),
            INDEX IDX_C3F33F5F584598A3 (operator_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        $this->addSql('ALTER TABLE calls 
            ADD CONSTRAINT FK_C3F33F5F4D7B7542 FOREIGN KEY (line_id) REFERENCES employee_skills (id),
            ADD CONSTRAINT FK_C3F33F5F584598A3 FOREIGN KEY (operator_id) REFERENCES users (id)
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE calls DROP FOREIGN KEY FK_C3F33F5F4D7B7542');
        $this->addSql('ALTER TABLE calls DROP FOREIGN KEY FK_C3F33F5F584598A3');
        $this->addSql('DROP TABLE calls');
    }
}
