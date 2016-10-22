/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50711
Source Host           : localhost:3306
Source Database       : interestelegram

Target Server Type    : MYSQL
Target Server Version : 50711
File Encoding         : 65001

Date: 2016-10-22 18:43:21
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `actions`
-- ----------------------------
DROP TABLE IF EXISTS `actions`;
CREATE TABLE `actions` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `ship_id` bigint(11) NOT NULL,
  `captain_id` bigint(11) NOT NULL,
  `message_id` bigint(11) NOT NULL,
  `command` varchar(20) NOT NULL,
  `params` varchar(255) NOT NULL,
  `fail` tinyint(1) NOT NULL DEFAULT '1',
  `positives` tinyint(4) NOT NULL DEFAULT '0',
  `negatives` tinyint(4) NOT NULL DEFAULT '0',
  `required` tinyint(4) NOT NULL DEFAULT '1',
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `closedAt` timestamp NULL DEFAULT NULL,
  `prev_command` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of actions
-- ----------------------------

-- ----------------------------
-- Table structure for `asteroids`
-- ----------------------------
DROP TABLE IF EXISTS `asteroids`;
CREATE TABLE `asteroids` (
  `id` int(9) NOT NULL AUTO_INCREMENT,
  `x` int(3) NOT NULL DEFAULT '0',
  `y` int(3) NOT NULL DEFAULT '0',
  `size` int(9) NOT NULL DEFAULT '300',
  `timestamp` int(9) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of asteroids
-- ----------------------------
INSERT INTO `asteroids` VALUES ('1', '2', '1', '300', '1476997487');
INSERT INTO `asteroids` VALUES ('2', '8', '5', '300', '1476997487');

-- ----------------------------
-- Table structure for `crew`
-- ----------------------------
DROP TABLE IF EXISTS `crew`;
CREATE TABLE `crew` (
  `id` bigint(15) NOT NULL AUTO_INCREMENT,
  `ship_id` bigint(11) NOT NULL,
  `user_id` bigint(11) NOT NULL,
  `role` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of crew
-- ----------------------------

-- ----------------------------
-- Table structure for `images_cache`
-- ----------------------------
DROP TABLE IF EXISTS `images_cache`;
CREATE TABLE `images_cache` (
  `id` int(9) NOT NULL AUTO_INCREMENT,
  `path` varchar(255) NOT NULL DEFAULT '',
  `telegram_id` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of images_cache
-- ----------------------------

-- ----------------------------
-- Table structure for `migrations`
-- ----------------------------
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `version` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of migrations
-- ----------------------------
INSERT INTO `migrations` VALUES ('15');

-- ----------------------------
-- Table structure for `minerals`
-- ----------------------------
DROP TABLE IF EXISTS `minerals`;
CREATE TABLE `minerals` (
  `id` int(9) NOT NULL AUTO_INCREMENT,
  `x` int(3) NOT NULL DEFAULT '0',
  `y` int(3) NOT NULL DEFAULT '0',
  `type` int(9) NOT NULL DEFAULT '0',
  `minerals` int(9) NOT NULL DEFAULT '300',
  `timestamp` int(9) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of minerals
-- ----------------------------
INSERT INTO `minerals` VALUES ('1', '5', '5', '0', '296', null);
INSERT INTO `minerals` VALUES ('2', '3', '3', '0', '300', null);
INSERT INTO `minerals` VALUES ('3', '9', '11', '0', '300', null);
INSERT INTO `minerals` VALUES ('4', '5', '1', '0', '300', null);

-- ----------------------------
-- Table structure for `powerups`
-- ----------------------------
DROP TABLE IF EXISTS `powerups`;
CREATE TABLE `powerups` (
  `id` int(9) NOT NULL AUTO_INCREMENT,
  `x` int(3) NOT NULL DEFAULT '0',
  `y` int(3) NOT NULL DEFAULT '0',
  `type` int(9) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `rarity` int(9) NOT NULL DEFAULT '0',
  `value` int(9) NOT NULL DEFAULT '0',
  `label` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of powerups
-- ----------------------------
INSERT INTO `powerups` VALUES ('1', '5', '4', '0', null, null, '0', '0', '');
INSERT INTO `powerups` VALUES ('2', '10', '6', '0', null, null, '0', '0', '');
INSERT INTO `powerups` VALUES ('3', '10', '3', '1', null, null, '0', '0', '');
INSERT INTO `powerups` VALUES ('4', '2', '8', '1', null, null, '0', '0', '');
INSERT INTO `powerups` VALUES ('5', '2', '6', '2', null, null, '0', '0', '');
INSERT INTO `powerups` VALUES ('6', '3', '6', '2', null, null, '0', '0', '');

-- ----------------------------
-- Table structure for `ships`
-- ----------------------------
DROP TABLE IF EXISTS `ships`;
CREATE TABLE `ships` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `chat_id` bigint(11) NOT NULL,
  `name` varchar(50) NOT NULL DEFAULT '',
  `x` int(8) NOT NULL DEFAULT '0',
  `y` int(8) NOT NULL DEFAULT '0',
  `total_crew` int(8) NOT NULL DEFAULT '1',
  `captain` bigint(11) NOT NULL,
  `active` tinyint(1) NOT NULL,
  `money` bigint(11) NOT NULL DEFAULT '0',
  `minerals` bigint(11) NOT NULL DEFAULT '0',
  `max_minerals` int(8) NOT NULL DEFAULT '5',
  `dont_remind_full_minerals` int(8) NOT NULL DEFAULT '0',
  `max_health` int(8) NOT NULL DEFAULT '5',
  `health` int(8) NOT NULL DEFAULT '5',
  `max_shield` int(8) NOT NULL DEFAULT '0',
  `shield` int(8) NOT NULL DEFAULT '0',
  `score` int(8) unsigned NOT NULL DEFAULT '1000',
  `target` bigint(15) DEFAULT NULL,
  `angle` smallint(3) NOT NULL DEFAULT '0',
  `model` smallint(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ships
-- ----------------------------
INSERT INTO `ships` VALUES ('1', '123', 'Test1', '5', '4', '1', '123', '1', '0', '0', '5', '1', '5', '5', '0', '2', '1000', '3', '315', '22');
INSERT INTO `ships` VALUES ('2', '321', 'Test2', '5', '3', '1', '124', '1', '0', '0', '5', '0', '5', '5', '0', '5', '1000', null, '180', '12');
INSERT INTO `ships` VALUES ('3', '333', 'Test3', '4', '5', '1', '125', '1', '0', '0', '5', '0', '5', '5', '0', '0', '1000', null, '90', '31');

-- ----------------------------
-- Table structure for `users`
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL DEFAULT '',
  `score` int(8) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of users
-- ----------------------------

-- ----------------------------
-- Table structure for `votes`
-- ----------------------------
DROP TABLE IF EXISTS `votes`;
CREATE TABLE `votes` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `action_id` bigint(11) NOT NULL,
  `user_id` bigint(11) NOT NULL,
  `vote` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of votes
-- ----------------------------
