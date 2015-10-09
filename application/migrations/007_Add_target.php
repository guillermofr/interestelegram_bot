<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_target extends CI_Migration {

        private $_table = 'ships';

        public function up()
        {
            $this->db->query("ALTER TABLE {$this->_table} ADD target BIGINT(15)");
        }

        public function down()
        {
            $this->db->query("ALTER TABLE {$this->_table} DROP COLUMN target");
        }
}