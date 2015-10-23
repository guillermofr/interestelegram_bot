<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_images_cache extends CI_Migration {

        private $_table = 'images_cache';
        private $_add_fields = array(
                        'id' => array(
                                'type' => 'INT',
                                'constraint' => 9,
                                'auto_increment' => TRUE
                        ),
                        'path' => array(
                                'type' => 'VARCHAR',
                                'constraint' => 255,
                                'default' => ''
                        ),
                        'telegram_id' => array(
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