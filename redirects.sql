/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 50529
 Source Host           : localhost
 Source Database       : ivillage6

 Target Server Type    : MySQL
 Target Server Version : 50529
 File Encoding         : utf-8

 Date: 05/28/2013 12:35:15 PM
*/

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `redirects`
-- ----------------------------
DROP TABLE IF EXISTS `redirects`;
CREATE TABLE `redirects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `old_host` varchar(255) NOT NULL,
  `old_path` varchar(2000) NOT NULL,
  `old_query` varchar(2000) NOT NULL,
  `old_fragment` varchar(2000) NOT NULL,
  `new_host` varchar(200) NOT NULL,
  `new_path` varchar(2000) NOT NULL,
  `new_query` varchar(2000) NOT NULL,
  `new_fragment` varchar(2000) NOT NULL,
  `response_code` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx1` (`old_host`) USING BTREE,
  KEY `idx2` (`old_query`(767)) USING BTREE,
  KEY `idx3` (`old_host`,`old_query`(767),`old_fragment`(767)) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=118522 DEFAULT CHARSET=latin1;

SET FOREIGN_KEY_CHECKS = 1;
