<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_prev_command_to_actions extends CI_Migration {

        private $_table = 'actions';
        private $_add_fields = array(
                    'prev_command' => array(
                            'type' => 'VARCHAR',
                            'constraint' => 50,
                            'null' => TRUE
                    )
                );
        public function up()
        {
        	$this->dbforge->add_column( $this->_table, $this->_add_fields );
        }

        public function down()
        {
            $this->dbforge->drop_column( $this->_table, 'prev_command' );
        }
}