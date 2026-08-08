<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDeliveryAddressToQuotesOrders extends Migration
{
    public function up()
    {
        // Quotes table
        $this->forge->addColumn('quotes', [
            'q_cda_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => '送貨地址ID',
                'after' => 'q_c_id',
            ],
        ]);

        // Orders table
        $this->forge->addColumn('orders', [
            'o_cda_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => '送貨地址ID',
                'after' => 'o_c_id',
            ],
        ]);

        $this->db->query('ALTER TABLE quotes ADD CONSTRAINT fk_quotes_delivery_address FOREIGN KEY (q_cda_id) REFERENCES customer_delivery_addresses(cda_id) ON DELETE SET NULL ON UPDATE CASCADE');
        $this->db->query('ALTER TABLE orders ADD CONSTRAINT fk_orders_delivery_address FOREIGN KEY (o_cda_id) REFERENCES customer_delivery_addresses(cda_id) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE orders DROP FOREIGN KEY fk_orders_delivery_address');
        $this->db->query('ALTER TABLE quotes DROP FOREIGN KEY fk_quotes_delivery_address');

        $this->forge->dropColumn('orders', 'o_cda_id');
        $this->forge->dropColumn('quotes', 'q_cda_id');
    }
}

