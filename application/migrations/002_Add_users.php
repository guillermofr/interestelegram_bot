<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_users extends CI_Migration {

        private $_table = 'users';
        private $_add_fields = array(
                        'id' => array(
                                'type' => 'BIGINT',
                                'constraint' => 11,
                                'auto_increment' => TRUE
                        ),
                        'username' => array(
                                'type' => 'VARCHAR',
                                'constraint' => 50
                        ),
                        'first_name' => array(
                                'type' => 'VARCHAR',
                                'constraint' => 50,
                                'default' => ''
                        ),
                        'score' => array(
                                'type' => 'INT',
                                'constraint' => 8,
                                'default' => 1
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