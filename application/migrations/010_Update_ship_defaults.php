<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Update_ship_defaults extends CI_Migration {

        private $_table = 'ships';

        public function up()
        {
            $this->db->query("ALTER TABLE `ships`
                            MODIFY COLUMN `max_health`  int(8) NOT NULL DEFAULT 5 AFTER `minerals`,
                            MODIFY COLUMN `health`  int(8) NOT NULL DEFAULT 5 AFTER `max_health`,
                            MODIFY COLUMN `max_shield`  int(8) NOT NULL DEFAULT 0 AFTER `health`,
                            MODIFY COLUMN `shield`  int(8) NOT NULL DEFAULT 0 AFTER `max_shield`,
                            MODIFY COLUMN `score`  int(8) UNSIGNED NOT NULL DEFAULT 1000 AFTER `shield`;");
        }

        public function down()
        {
            $this->db->query("ALTER TABLE `ships`
                            MODIFY COLUMN `max_health`  int(8) NOT NULL DEFAULT 20 AFTER `minerals`,
                            MODIFY COLUMN `health`  int(8) NOT NULL DEFAULT 20 AFTER `max_health`,
                            MODIFY COLUMN `max_shield`  int(8) NOT NULL DEFAULT 5 AFTER `health`,
                            MODIFY COLUMN `shield`  int(8) NOT NULL DEFAULT 5 AFTER `max_shield`,
                            MODIFY COLUMN `score`  int(8) UNSIGNED NOT NULL DEFAULT 1 AFTER `shield`;");
        }
}