<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250427135618 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create employee skills system tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE employee_skill_paths (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            PRIMARY KEY(id),
            UNIQUE INDEX UNIQ_skill_path_name (name)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE employee_skills (
            id INT AUTO_INCREMENT NOT NULL,
            skill_path_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            PRIMARY KEY(id),
            UNIQUE INDEX UNIQ_skill_name_path (name, skill_path_id),
            INDEX IDX_skill_path (skill_path_id),
            CONSTRAINT FK_employee_skills_skill_path FOREIGN KEY (skill_path_id) 
                REFERENCES employee_skill_paths (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE employee_skill_paths_assignments (
            id INT AUTO_INCREMENT NOT NULL,
            user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:user_id)\',
            skill_path_id INT NOT NULL,
            PRIMARY KEY(id),
            UNIQUE INDEX UNIQ_user_skill_path (user_id, skill_path_id),
            INDEX IDX_user (user_id),
            INDEX IDX_skill_path (skill_path_id),
            CONSTRAINT FK_employee_skill_paths_assignments_user FOREIGN KEY (user_id) 
                REFERENCES users (id) ON DELETE CASCADE,
            CONSTRAINT FK_employee_skill_paths_assignments_skill_path FOREIGN KEY (skill_path_id) 
                REFERENCES employee_skill_paths (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE employee_skills_assignments (
            id INT AUTO_INCREMENT NOT NULL,
            user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:user_id)\',
            skill_id INT NOT NULL,
            level INT NOT NULL,
            PRIMARY KEY(id),
            UNIQUE INDEX UNIQ_user_skill (user_id, skill_id),
            INDEX IDX_user (user_id),
            INDEX IDX_skill (skill_id),
            CONSTRAINT FK_employee_skills_assignments_user FOREIGN KEY (user_id) 
                REFERENCES users (id) ON DELETE CASCADE,
            CONSTRAINT FK_employee_skills_assignments_skill FOREIGN KEY (skill_id) 
                REFERENCES employee_skills (id) ON DELETE CASCADE,
            CONSTRAINT CHECK_skill_level CHECK (level BETWEEN 1 AND 5)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE employee_skills_assignments');
        $this->addSql('DROP TABLE employee_skill_paths_assignments');
        $this->addSql('DROP TABLE employee_skills');
        $this->addSql('DROP TABLE employee_skill_paths');
    }
}
