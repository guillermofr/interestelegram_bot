<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_crew extends CI_Migration {

        private $_table = 'crew';
        private $_add_fields = array(
                        'ship_id' => array(
                                'type' => 'BIGINT',
                                'constraint' => 11
                        ),
                        'user_id' => array(
                                'type' => 'BIGINT',
                                'constraint' => 11
                        ),
                        'role' => array(
                                'type' => 'VARCHAR',
                                'constraint' => 20,
                                'null' => true
                        )
                );

        public function up()
        {
                // $this->db->query("CREATE TABLE ...") también vale
                $this->dbforge->add_field( $this->_add_fields );
                $this->dbforge->add_key('ship_id', TRUE);
                $this->dbforge->add_key('user_id', TRUE);
                $this->dbforge->create_table( $this->_table );
        }

        public function down()
        {
                $this->dbforge->drop_table( $this->_table );
        }
}