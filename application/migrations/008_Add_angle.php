<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_angle extends CI_Migration {

        private $_table = 'ships';

        public function up()
        {
            $this->db->query("ALTER TABLE {$this->_table} ADD angle SMALLINT(3) NOT NULL DEFAULT 0");
        }

        public function down()
        {
            $this->db->query("ALTER TABLE {$this->_table} DROP COLUMN angle");
        }
}