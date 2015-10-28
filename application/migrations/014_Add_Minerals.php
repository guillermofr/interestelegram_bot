<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_minerals extends CI_Migration {

        private $_table = 'minerals';
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
                        'minerals' => array(
                                'type' => 'INT',
                                'constraint' => 9,
                                'default' => 300
                        ),
                        'timestamp' => array(
                                'type' => 'INT',
                                'constraint' => 9,
                                'null' => TRUE,
                                'default' => NULL
                        )
                );

        public function up()
        {
                // $this->db->query("CREATE TABLE ...") también vale
                $this->dbforge->add_field( $this->_add_fields );
                $this->dbforge->add_key('id', TRUE);
                $this->dbforge->create_table( $this->_table );

                $this->db->query("ALTER TABLE `ships`
                            ADD `dont_remind_full_minerals` int(8) NOT NULL DEFAULT 0 AFTER `minerals`,
                            ADD `max_minerals` int(8) NOT NULL DEFAULT 5 AFTER `minerals`;
                           ");
        }

        public function down()
        {
                $this->dbforge->drop_table( $this->_table );
                $this->db->query("ALTER TABLE `ships`
                            DROP COLUMN `dont_remind_full_minerals`,
                            DROP COLUMN `max_minerals`;
                            ");
        }
}