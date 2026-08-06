<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121121500 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'create_medical_record_icd_table';
    }

    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('medical_record_icd');
        $table->addColumn('medical_record_id', 'integer', ['unsigned' => true]);
        $table->addColumn('icd_code_id', 'integer', ['unsigned' => true]);
        $table->setPrimaryKey(['medical_record_id', 'icd_code_id']);
        $table->addIndex(['medical_record_id']);
        $table->addIndex(['icd_code_id']);
        $table->addForeignKeyConstraint('medical_records', ['medical_record_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
        $table->addForeignKeyConstraint('icd_codes', ['icd_code_id'], ['id'], ['onDelete' => 'CASCADE', 'onUpdate' => 'NO_ACTION']);
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('medical_record_icd');
    }
}
