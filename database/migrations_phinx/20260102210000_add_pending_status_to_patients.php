<?php

use Phinx\Migration\AbstractMigration;

class AddPendingStatusToPatients extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('patients');
        
        // Modify the enum column to include 'pending' status
        $this->execute("
            ALTER TABLE patients 
            MODIFY COLUMN status ENUM('active', 'archived', 'needs_review', 'pending') DEFAULT 'active'
        ");
    }
}