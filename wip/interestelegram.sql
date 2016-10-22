/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50711
Source Host           : localhost:3306
Source Database       : interestelegram

Target Server Type    : MYSQL
Target Server Version : 50711
File Encoding         : 65001

Date: 2016-10-22 18:19:19
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
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of images_cache
-- ----------------------------
INSERT INTO `images_cache` VALUES ('1', 'C:\\UwAmp\\www\\inter\\application\\../imgs/map/scans/3fe52b2b132323a82b60532f365118a4.png', '123');
INSERT INTO `images_cache` VALUES ('2', 'C:\\UwAmp\\www\\inter\\application\\../imgs/map/scans/18d4c8da26807a5cb5ad7ba92491e8ec.png', '123');
INSERT INTO `images_cache` VALUES ('3', 'C:\\UwAmp\\www\\inter\\application\\../imgs/map/scans/2c50d145f367ee8ec7ac84ca99cf139b.png', '123');
INSERT INTO `images_cache` VALUES ('4', 'C:\\UwAmp\\www\\inter\\application\\../imgs/map/scans/dcf6f81323b2ad9e016f9d3c9873fb6d.png', '123');
INSERT INTO `images_cache` VALUES ('5', 'C:\\UwAmp\\www\\inter\\application\\../imgs/map/scans/6cf9febd18a83ffc91e7033a3cbdd86d.png', '123');
INSERT INTO `images_cache` VALUES ('6', 'C:\\UwAmp\\www\\inter\\application\\../imgs/map/scans/cff2559d3e2b455db5c94f9ef4e8e1b8.png', '123');
INSERT INTO `images_cache` VALUES ('7', 'C:\\UwAmp\\www\\inter\\application\\../imgs/map/scans/118b1361eb0e3e0f4726d431c4c1e90c.png', '123');
INSERT INTO `images_cache` VALUES ('8', 'C:\\UwAmp\\www\\inter\\application\\../imgs/map/scans/1feb5b5fb33acb5b17fac8fdba718def.png', '123');
INSERT INTO `images_cache` VALUES ('9', 'C:\\UwAmp\\www\\inter\\application\\../imgs/map/scans/dc1d123ec40b0ee79328001034253116.png', '123');
INSERT INTO `images_cache` VALUES ('10', 'C:\\UwAmp\\www\\inter\\application\\../imgs/map/scans/9210531fba905c32ee168270d07cafa2.png', '123');
INSERT INTO `images_cache` VALUES ('11', 'C:\\UwAmp\\www\\inter\\application\\../imgs/map/scans/b5278b04c4c351a6dd7c6aeb2225e1be.png', '123');
INSERT INTO `images_cache` VALUES ('12', 'C:\\UwAmp\\www\\inter\\application\\../imgs/map/scans/c89c499167738c0de84acab616e968fc.png', '123');
INSERT INTO `images_cache` VALUES ('13', 'C:\\UwAmp\\www\\inter\\application\\../imgs/map/scans/6b89221d6aa275623be659b30dbc7915.png', '123');
INSERT INTO `images_cache` VALUES ('14', 'C:\\UwAmp\\www\\inter\\application\\../imgs/map/scans/c649445e2c517dd989c75538ccfe13f6.png', '123');
INSERT INTO `images_cache` VALUES ('15', 'C:\\UwAmp\\www\\inter\\application\\../imgs/map/scans/0e09f2e7d41998edd72336e112a3d585.png', '123');
INSERT INTO `images_cache` VALUES ('16', 'C:\\UwAmp\\www\\inter\\application\\../imgs/map/scans/71632c5f8cdf8a4b1f64123ff414b4d2.png', '123');
INSERT INTO `images_cache` VALUES ('17', 'C:\\UwAmp\\www\\inter\\application\\../imgs/map/scans/fb0ddf43ec6796645cf4cc6d7813fb78.png', '123');
INSERT INTO `images_cache` VALUES ('18', 'C:\\UwAmp\\www\\inter\\application\\../imgs/map/scans/21568dddfcb7b061f0b8b537aea62567.png', '123');

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
INSERT INTO `ships` VALUES ('1', '123', 'Test1', '6', '3', '1', '123', '1', '0', '0', '5', '1', '5', '5', '0', '2', '1000', '3', '270', '22');
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
