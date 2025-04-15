<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250414153145 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add hire_date and manager_id columns to users table';
    }

    public function up(Schema $schema): void
    {
        // Add hire_date column (nullable)
        $this->addSql('ALTER TABLE users ADD hire_date DATE DEFAULT NULL');
        
        // Add manager_id column (nullable) with foreign key
        $this->addSql('ALTER TABLE users ADD manager_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9783E3463 FOREIGN KEY (manager_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_1483A5E9783E3463 ON users (manager_id)');
    }

    public function down(Schema $schema): void
    {
        // Drop foreign key first
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9783E3463');
        
        // Drop index and columns
        $this->addSql('DROP INDEX IDX_1483A5E9783E3463 ON users');
        $this->addSql('ALTER TABLE users DROP manager_id');
        $this->addSql('ALTER TABLE users DROP hire_date');
    }
} 