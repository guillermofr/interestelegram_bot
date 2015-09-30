<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_actions extends CI_Migration {

        private $_table = 'actions';
        private $_add_fields = array(
                        'id' => array(
                                'type' => 'BIGINT',
                                'constraint' => 11,
                                'auto_increment' => TRUE
                        ),
                        'ship_id' => array(
                                'type' => 'BIGINT',
                                'constraint' => 11
                        ),
                        'captain_id' => array(
                                'type' => 'BIGINT',
                                'constraint' => 11
                        ),
                        'message_id' => array(
                                'type' => 'BIGINT',
                                'constraint' => 11
                        ),
                        'command' => array(
                                'type' => 'VARCHAR',
                                'constraint' => 20
                        ),
                        'params' => array(
                                'type' => 'VARCHAR',
                                'constraint' => 255
                        ),
                        'fail' => array(
                                'type' => 'TINYINT',
                                'constraint' => 1,
                                'default' => 1
                        ),
                        'positives' => array(
                                'type' => 'TINYINT',
                                'constraint' => 4,
                                'default' => 0
                        ),
                        'negatives' => array(
                                'type' => 'TINYINT',
                                'constraint' => 4,
                                'default' => 0
                        ),
                        'required' => array(
                                'type' => 'TINYINT',
                                'constraint' => 4,
                                'default' => 1
                        ),
                        'createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                        'closedAt' => array(
                                'type' => 'TIMESTAMP',
                                'null' => TRUE
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