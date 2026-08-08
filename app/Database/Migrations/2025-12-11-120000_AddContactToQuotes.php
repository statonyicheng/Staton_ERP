<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddContactToQuotes extends Migration
{
    public function up()
    {
        $this->forge->addColumn('quotes', [
            'q_cc_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => '聯絡人ID',
                'after' => 'q_c_id',
            ],
        ]);

        $this->db->query('ALTER TABLE quotes ADD CONSTRAINT fk_quotes_contact FOREIGN KEY (q_cc_id) REFERENCES customer_contacts(cc_id) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE quotes DROP FOREIGN KEY fk_quotes_contact');
        $this->forge->dropColumn('quotes', 'q_cc_id');
    }
}

