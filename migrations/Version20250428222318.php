<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250428222318 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates work_schedules table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE work_schedules (
            id CHAR(36) NOT NULL,
            user_id CHAR(36) NOT NULL,
            skill_path_id INT NOT NULL,
            date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            notes VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE work_schedules ADD CONSTRAINT FK_work_schedules_user_id FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE work_schedules ADD CONSTRAINT FK_work_schedules_skill_path_id FOREIGN KEY (skill_path_id) REFERENCES employee_skill_paths (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_work_schedules_user_id ON work_schedules (user_id)');
        $this->addSql('CREATE INDEX IDX_work_schedules_skill_path_id ON work_schedules (skill_path_id)');
        $this->addSql('CREATE INDEX IDX_work_schedules_date ON work_schedules (date)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE work_schedules DROP FOREIGN KEY FK_work_schedules_user_id');
        $this->addSql('ALTER TABLE work_schedules DROP FOREIGN KEY FK_work_schedules_skill_path_id');
        $this->addSql('DROP TABLE work_schedules');
    }
}
