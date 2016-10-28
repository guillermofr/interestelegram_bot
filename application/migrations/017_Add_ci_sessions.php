<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_ci_sessions extends CI_Migration {

        private $_table = 'ci_sessions';

        public function up()
        {
            $this->db->query("DROP TABLE IF EXISTS {$this->_table}");
            $this->db->query('CREATE TABLE ci_sessions ('.
                            ' id varchar(128) NOT NULL,'.
                            ' ip_address varchar(45) NOT NULL,'.
                            ' timestamp int(10) unsigned NOT NULL DEFAULT "0",'.
                            ' data blob NOT NULL,'.
                            ' KEY ci_sessions_timestamp (`timestamp`)'.
                            ' ) ENGINE=InnoDB DEFAULT CHARSET=utf8');
        }

        public function down()
        {
            $this->db->query("DROP TABLE IF EXISTS {$this->_table}");
        }
}

;
;