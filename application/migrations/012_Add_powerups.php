<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_powerups extends CI_Migration {

        private $_table = 'powerups';
        private $_add_fields = array(
                        'id' => array(
                                'type' => 'INT',
                                'constraint' => 9,
                                'auto_increment' => TRUE
                        ),
                        'x' => array(
                                'type' => 'INT',
                                'constraint' => 3,
                                'default' => 0
                        ),
                        'y' => array(
                                'type' => 'INT',
                                'constraint' => 3,
                                'default' => 0
                        ),
                        'type' => array(
                                'type' => 'INT',
                                'constraint' => 9,
                                'default' => 0
                        ),
                        'created_at' => array(
                                'type' => 'TIMESTAMP',
                                'null' => TRUE
                        ),
                        'updated_at' => array(
                                'type' => 'TIMESTAMP',
                                'null' => TRUE,
                                'default' => NULL
                        ),
                        'rarity' => array(
                                'type' => 'INT',
                                'constraint' => 9,
                                'default' => 0
                        ),
                        'value' => array(
                                'type' => 'INT',
                                'constraint' => 9,
                                'default' => 0
                        ),
                        'label' => array(
                                'type' => 'VARCHAR',
                                'constraint' => 255,
                                'default' => ''
                        )
                );

        public function up()
        {
                // $this->db->query("CREATE TABLE ...") también vale
                $this->dbforge->add_field( $this->_add_fields );
                $this->dbforge->add_key('id', TRUE);
                $this->dbforge->create_table( $this->_table );
        }

        public function down()
        {
                $this->dbforge->drop_table( $this->_table );
        }
}