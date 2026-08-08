<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveContactPersonFromCustomers extends Migration
{
    public function up()
    {
        // 移除 customers.c_contact_person
        $this->forge->dropColumn('customers', 'c_contact_person');
    }

    public function down()
    {
        // 還原 customers.c_contact_person
        $this->forge->addColumn('customers', [
            'c_contact_person' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => '聯絡人',
                'after' => 'c_manager',
            ],
        ]);
    }
}
