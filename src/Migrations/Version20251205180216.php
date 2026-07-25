<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251205180216 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add_profile_photo_path_to_users_table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE users ADD COLUMN profile_photo_path VARCHAR(255) NULL AFTER last_name");
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('users');
        $table->dropColumn('profile_photo_path');
    }
}
