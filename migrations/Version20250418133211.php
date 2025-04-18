<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250418133211 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users and work schedule availabilities tables with proper types';
    }

    public function up(Schema $schema): void
    {
        // Create users table
        $this->addSql('CREATE TABLE users (
            id CHAR(36) NOT NULL COMMENT \'(DC2Type:user_id)\',
            email VARCHAR(180) NOT NULL,
            password VARCHAR(255) NOT NULL,
            roles JSON NOT NULL,
            first_name VARCHAR(255) NOT NULL,
            last_name VARCHAR(255) NOT NULL,
            active BOOLEAN NOT NULL,
            hire_date DATE DEFAULT NULL,
            manager_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:user_id)\',
            employment_type VARCHAR(255) NOT NULL COMMENT \'(DC2Type:App\\Domain\\User\\ValueObject\\EmploymentType)\',
            UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email),
            INDEX IDX_1483A5E9783E3463 (manager_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create work_schedule_availabilities table
        $this->addSql('CREATE TABLE work_schedule_availabilities (
            id CHAR(36) NOT NULL,
            user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:user_id)\',
            employment_type VARCHAR(255) NOT NULL COMMENT \'(DC2Type:App\\Domain\\User\\ValueObject\\EmploymentType)\',
            start_time TIME NOT NULL COMMENT \'(DC2Type:time_immutable)\',
            end_time TIME NOT NULL COMMENT \'(DC2Type:time_immutable)\',
            date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\',
            recurrence_pattern JSON DEFAULT NULL,
            INDEX IDX_AVAILABILITY_USER (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Add foreign keys
        $this->addSql('ALTER TABLE users 
            ADD CONSTRAINT FK_1483A5E9783E3463 FOREIGN KEY (manager_id) REFERENCES users (id) ON DELETE SET NULL
        ');

        $this->addSql('ALTER TABLE work_schedule_availabilities 
            ADD CONSTRAINT FK_AVAILABILITY_USER_FK FOREIGN KEY (user_id) REFERENCES users (id)
        ');
    }

    public function down(Schema $schema): void
    {
        // Drop tables in reverse order to handle foreign key constraints
        $this->addSql('ALTER TABLE work_schedule_availabilities DROP FOREIGN KEY FK_AVAILABILITY_USER_FK');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9783E3463');
        $this->addSql('DROP TABLE work_schedule_availabilities');
        $this->addSql('DROP TABLE users');
    }
}
