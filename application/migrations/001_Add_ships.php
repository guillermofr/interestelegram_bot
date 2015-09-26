<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_ships extends CI_Migration {

        public function up()
        {
                // $this->db->query("CREATE TABLE ...") también vale
                $this->dbforge->add_field(array(
                        'id' => array(
                                'type' => 'BIGINT',
                                'constraint' => 11,
                                'auto_increment' => TRUE
                        ),
                        'chat_id' => array(
                                'type' => 'BIGINT',
                                'constraint' => 11
                        ),
                        'name' => array(
                                'type' => 'VARCHAR',
                                'constraint' => '50',
                                'default' => ''
                        ),
                        'x' => array(
                                'type' => 'INT',
                                'constraint' => 8,
                                'default' => 0
                        ),
                        'y' => array(
                                'type' => 'INT',
                                'constraint' => 8,
                                'default' => 0
                        ),
                        'total_crew' => array(
                                'type' => 'INT',
                                'constraint' => 8,
                                'default' => 1
                        ),
                        'captain' => array(
                                'type' => 'BIGINT',
                                'constraint' => 11
                        ),
                        'active' => array(
                                'type' => 'TINYINT',
                                'constraint' => 1
                        ),
                        'money' => array(
                                'type' => 'BIGINT',
                                'constraint' => 11,
                                'default' => 0
                        ),
                        'minerals' => array(
                                'type' => 'BIGINT',
                                'constraint' => 11,
                                'default' => 0
                        ),
                        'max_health' => array(
                                'type' => 'INT',
                                'constraint' => 8,
                                'default' => 20
                        ),
                        'health' => array(
                                'type' => 'INT',
                                'constraint' => 8,
                                'default' => 20
                        ),
                        'max_shield' => array(
                                'type' => 'INT',
                                'constraint' => 8,
                                'default' => 5
                        ),
                        'shield' => array(
                                'type' => 'INT',
                                'constraint' => 8,
                                'default' => 5
                        ),
                        'score' => array(
                                'type' => 'INT',
                                'constraint' => 8,
                                'default' => 1
                        )
                ));
                $this->dbforge->add_key('id', TRUE);
                $this->dbforge->create_table('ships');
        }

        public function down()
        {
                $this->dbforge->drop_table('ships');
        }
}