<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250428222319 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates work_schedule_entries table with auto-incrementing integer IDs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE work_schedule_entries (
            id INT AUTO_INCREMENT NOT NULL,
            user_id CHAR(36) NOT NULL,
            skill_path_id INT NOT NULL,
            date DATE NOT NULL,
            time_range_start TIME NOT NULL,
            time_range_end TIME NOT NULL,
            notes TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Add foreign key constraints
        $this->addSql('ALTER TABLE work_schedule_entries 
            ADD CONSTRAINT FK_work_schedule_entries_user_id 
            FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        
        $this->addSql('ALTER TABLE work_schedule_entries 
            ADD CONSTRAINT FK_work_schedule_entries_skill_path_id 
            FOREIGN KEY (skill_path_id) REFERENCES employee_skill_paths (id) ON DELETE CASCADE');

        // Add indexes
        $this->addSql('CREATE INDEX IDX_work_schedule_entries_user_id ON work_schedule_entries (user_id)');
        $this->addSql('CREATE INDEX IDX_work_schedule_entries_skill_path_id ON work_schedule_entries (skill_path_id)');
        $this->addSql('CREATE INDEX IDX_work_schedule_entries_date ON work_schedule_entries (date)');
        $this->addSql('CREATE UNIQUE INDEX unique_schedule_entry ON work_schedule_entries (user_id, skill_path_id, date, time_range_start, time_range_end)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE work_schedule_entries');
    }
} 