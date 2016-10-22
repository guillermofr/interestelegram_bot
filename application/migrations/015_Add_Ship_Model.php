<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_Ship_Model extends CI_Migration {

        private $_table = 'ships';

        public function up()
        {
            $this->db->query("ALTER TABLE {$this->_table} ADD COLUMN `model` smallint(3) NOT NULL DEFAULT 0 AFTER `angle`");
        }

        public function down()
        {
            $this->db->query("ALTER TABLE {$this->_table} DROP COLUMN model");
        }
}