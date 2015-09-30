<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_votes extends CI_Migration {

        private $_table = 'votes';
        private $_add_fields = array(
                        'id' => array(
                                'type' => 'BIGINT',
                                'constraint' => 11,
                                'auto_increment' => TRUE
                        ),
                        'action_id' => array(
                                'type' => 'BIGINT',
                                'constraint' => 11
                        ),
                        'user_id' => array(
                                'type' => 'BIGINT',
                                'constraint' => 11
                        ),
                        'vote' => array(
                                'type' => 'TINYINT',
                                'constraint' => 1,
                                'default' => 0
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