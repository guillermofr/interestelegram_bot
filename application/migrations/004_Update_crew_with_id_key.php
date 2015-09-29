<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Update_crew_with_id_key extends CI_Migration {

        private $_table = 'crew';

        public function up()
        {
            $this->db->query("ALTER TABLE {$this->_table} DROP PRIMARY KEY");
            $this->db->query("ALTER TABLE {$this->_table} ADD id BIGINT(15) PRIMARY KEY AUTO_INCREMENT FIRST");
        }

        public function down()
        {
            $this->dbforge->drop_column( $this->_table, 'id' );
            $this->db->query("ALTER TABLE {$this->_table} ADD PRIMARY KEY(ship_id, user_id)");
        }
}