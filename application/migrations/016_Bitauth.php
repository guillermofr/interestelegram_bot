<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Bitauth extends CI_Migration {
        public function up()
        {

                $this->db->query("CREATE TABLE IF NOT EXISTS `bitauth_assoc` (
                                  `assoc_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                                  `user_id` int(10) unsigned NOT NULL,
                                  `group_id` int(10) unsigned NOT NULL,
                                  PRIMARY KEY (`assoc_id`),
                                  KEY `user_id` (`user_id`,`group_id`)
                                ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;");
                
                $this->db->query("INSERT INTO `bitauth_assoc` (`assoc_id`, `user_id`, `group_id`) VALUES
                                (1, 1, 1);");
                
                $this->db->query("CREATE TABLE IF NOT EXISTS `bitauth_groups` (
                                  `group_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                                  `name` varchar(48) NOT NULL,
                                  `description` text NOT NULL,
                                  `roles` text NOT NULL,
                                  PRIMARY KEY (`group_id`)
                                ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;");
                
                $this->db->query("INSERT INTO `bitauth_groups` (`group_id`, `name`, `description`, `roles`) VALUES
                                (1, 'Administrators', 'Administrators (Full Access)', 1),
                                (2, 'Users', 'Default User Group', 0);");
                
                $this->db->query("CREATE TABLE IF NOT EXISTS `bitauth_logins` (
                                  `login_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                                  `ip_address` int(10) unsigned NOT NULL,
                                  `user_id` int(10) unsigned NOT NULL,
                                  `time` datetime NOT NULL,
                                  `success` tinyint(1) NOT NULL DEFAULT '0',
                                  PRIMARY KEY (`login_id`),
                                  KEY `user_id` (`user_id`)
                                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;");
                
                $this->db->query("CREATE TABLE IF NOT EXISTS `bitauth_userdata` (
                                  `userdata_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                                  `user_id` int(10) unsigned NOT NULL,
                                  `fullname` varchar(40) NOT NULL,
                                  `email` varchar(254) NOT NULL,
                                  PRIMARY KEY (`userdata_id`),
                                  KEY `user_id` (`user_id`)
                                ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;");
                
                $this->db->query("INSERT INTO `bitauth_userdata` (`userdata_id`, `user_id`, `fullname`, `email`) VALUES
                                (1, 1, 'Administrator', 'admin@admin.com');");
                
                $this->db->query("CREATE TABLE IF NOT EXISTS `bitauth_users` (
                                  `user_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                                  `username` varchar(32) NOT NULL,
                                  `password` varchar(60) NOT NULL,
                                  `password_last_set` datetime NOT NULL,
                                  `password_never_expires` tinyint(1) NOT NULL DEFAULT '0',
                                  `remember_me` varchar(40) NOT NULL,
                                  `activation_code` varchar(40) NOT NULL,
                                  `active` tinyint(1) NOT NULL DEFAULT '0',
                                  `forgot_code` varchar(40) NOT NULL,
                                  `forgot_generated` datetime NOT NULL,
                                  `enabled` tinyint(1) NOT NULL DEFAULT '1',
                                  `last_login` datetime NOT NULL,
                                  `last_login_ip` int(10) NOT NULL,
                                  PRIMARY KEY (`user_id`)
                                ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;");
                
                $this->db->query("INSERT INTO `bitauth_users` (`user_id`, `username`, `password`, `password_last_set`, `password_never_expires`, `remember_me`, `activation_code`, `active`, `forgot_code`, `forgot_generated`, `enabled`, `last_login`, `last_login_ip`) VALUES
                                (1, 'admin', '$2a$08$560JEYl2Np/7/6RLc/mq/ecnumuBXig3e.pHh1lnH1pgpk94sTZhu', now(), 0, '', '', 1, '', '0000-00-00 00:00:00', 1, '0000-00-00 00:00:00', 0);");

                $this->db->query("ALTER TABLE `bitauth_userdata` ADD COLUMN `clan`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `fullname`;");
                $this->db->query("ALTER TABLE `bitauth_userdata` ADD COLUMN `puntos`  bigint(20) NOT NULL DEFAULT 0 AFTER `email`;");
                $this->db->query("ALTER TABLE `bitauth_userdata` ADD COLUMN `racha`  int(255) NOT NULL DEFAULT 0 AFTER `puntos`;");
                $this->db->query("ALTER TABLE `bitauth_userdata` ADD COLUMN `participante`  int(11) NOT NULL DEFAULT 0 AFTER `racha`;");
                $this->db->query("ALTER TABLE `bitauth_userdata` ADD COLUMN `status`  int(11) NOT NULL DEFAULT 0 AFTER `participante`;");
                $this->db->query("ALTER TABLE `bitauth_userdata` MODIFY COLUMN `clan`  varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL AFTER `fullname`;");

                
        }

        public function down()
        {
                $this->dbforge->drop_table('bitauth_assoc');
                $this->dbforge->drop_table('bitauth_groups');
                $this->dbforge->drop_table('bitauth_logins');
                $this->dbforge->drop_table('bitauth_userdata');
                $this->dbforge->drop_table('bitauth_users');
        }
}