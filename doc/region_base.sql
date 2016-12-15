/*
Navicat MySQL Data Transfer

Source Server         : 192.168.8.11
Source Server Version : 50620
Source Host           : 192.168.8.11:3306
Source Database       : region_base

Target Server Type    : MYSQL
Target Server Version : 50620
File Encoding         : 65001

Date: 2016-12-14 16:24:14
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for config
-- ----------------------------
DROP TABLE IF EXISTS `config`;
CREATE TABLE `config` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `key` varchar(50) DEFAULT NULL COMMENT '键名',
  `val` text COMMENT '值',
  `group` varchar(50) DEFAULT NULL COMMENT '分组',
  `input_type` varchar(50) DEFAULT NULL COMMENT 'input类型',
  `sort` int(11) DEFAULT NULL COMMENT '排序',
  PRIMARY KEY (`id`),
  KEY `key` (`key`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='系统设置';

-- ----------------------------
-- Records of config
-- ----------------------------
INSERT INTO `config` VALUES ('1', 'app_name', '四川省区域中心综合管理平台', null, null, null);
INSERT INTO `config` VALUES ('2', 'region_no', 'R61007200', null, null, null);
INSERT INTO `config` VALUES ('3', 'region_name', '四川省博物院', null, null, null);
INSERT INTO `config` VALUES ('4', 'map_name', 'sichuan', null, null, null);

-- ----------------------------
-- Table structure for log
-- ----------------------------
DROP TABLE IF EXISTS `log`;
CREATE TABLE `log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `user_id` int(11) DEFAULT NULL COMMENT '用户id',
  `username` varchar(50) DEFAULT NULL COMMENT '用户名',
  `time` int(11) DEFAULT NULL COMMENT '记录时间',
  `content` text COMMENT '记录内容',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='日志表';

-- ----------------------------
-- Records of log
-- ----------------------------

-- ----------------------------
-- Table structure for logs
-- ----------------------------
DROP TABLE IF EXISTS `logs`;
CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uri` varchar(255) NOT NULL,
  `method` varchar(6) NOT NULL,
  `params` text,
  `token` varchar(40) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `start_time` varchar(20) NOT NULL,
  `exec_time` varchar(10) DEFAULT NULL,
  `user` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='api访问日志';

-- ----------------------------
-- Records of logs
-- ----------------------------

-- ----------------------------
-- Table structure for museum
-- ----------------------------
DROP TABLE IF EXISTS `museum`;
CREATE TABLE `museum` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(50) NOT NULL COMMENT '博物馆名称',
  `db_type` enum('Mysql','Mongo') DEFAULT NULL COMMENT '数据库类型：mysql或者mongo',
  `db_host` varchar(20) DEFAULT NULL COMMENT '数据库主机（包含端口）',
  `db_user` varchar(20) DEFAULT NULL COMMENT '数据库用户名',
  `db_pass` varchar(20) DEFAULT NULL COMMENT '数据库密码',
  `db_name` varchar(20) DEFAULT NULL COMMENT 'mysql：子系统数据库名称前缀；mongo：数据库名称',
  `longitude` float(6,2) DEFAULT NULL COMMENT '经度',
  `latitude` float(6,2) DEFAULT NULL COMMENT '纬度',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COMMENT='博物馆列表';

-- ----------------------------
-- Records of museum
-- ----------------------------
INSERT INTO `museum` VALUES ('1', '金沙博物馆金沙博物馆金沙博物馆金沙博物馆金沙博物馆', 'Mysql', '192.168.8.11', 'root', 'mysql', 'jinsha', '104.02', '30.69');
INSERT INTO `museum` VALUES ('2', '四川博物院（洛阳）', 'Mysql', '192.168.8.11', 'root', 'mysql', 'luoyang', '102.04', '30.66');
INSERT INTO `museum` VALUES ('3', '雅安博物馆', 'Mongo', '192.168.8.11', null, null, 'museum_ya', '103.00', '30.00');
INSERT INTO `museum` VALUES ('4', '成都博物馆（智联）', 'Mongo', '192.168.8.11', '', '', 'museum_test', '103.07', '30.66');
INSERT INTO `museum` VALUES ('5', '泸州博物馆', 'Mysql', null, null, null, null, '105.44', '28.88');
INSERT INTO `museum` VALUES ('6', '5.12汶川特大地震纪念馆', null, null, null, null, null, '103.49', '31.06');
INSERT INTO `museum` VALUES ('7', 'test1', null, null, null, null, null, '0.00', '0.00');
INSERT INTO `museum` VALUES ('8', 'test2', 'Mongo', null, null, null, null, '0.00', '0.00');
INSERT INTO `museum` VALUES ('9', 'tesr3', null, null, null, null, null, '0.00', '0.00');

-- ----------------------------
-- Table structure for permission
-- ----------------------------
DROP TABLE IF EXISTS `permission`;
CREATE TABLE `permission` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` varchar(100) DEFAULT NULL COMMENT '权限名',
  `val` varchar(100) DEFAULT NULL COMMENT '权限值',
  `group` varchar(50) DEFAULT NULL COMMENT '权限分组',
  `app` varchar(50) DEFAULT NULL COMMENT '所属子系统',
  `sort` int(11) DEFAULT NULL COMMENT '排序',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COMMENT='权限表';

-- ----------------------------
-- Records of permission
-- ----------------------------
INSERT INTO `permission` VALUES ('1', '环境监测', '环境监测', '页面', 'base', '1');
INSERT INTO `permission` VALUES ('2', '系统管理', '系统管理', '页面', 'base', '2');
INSERT INTO `permission` VALUES ('3', '查询用户列表', '查询用户列表', '用户', 'base', '99');
INSERT INTO `permission` VALUES ('4', '获取单个用户基本信息', '获取单个用户基本信息', '用户', 'base', '90');
INSERT INTO `permission` VALUES ('5', '添加用户', '添加用户', '用户', 'base', null);
INSERT INTO `permission` VALUES ('6', '修改用户', '修改用户', '用户', 'base', null);
INSERT INTO `permission` VALUES ('7', '删除用户', '删除用户', '用户', 'base', null);
INSERT INTO `permission` VALUES ('8', '获取角色列表', '获取角色列表', '角色', 'base', null);

-- ----------------------------
-- Table structure for role
-- ----------------------------
DROP TABLE IF EXISTS `role`;
CREATE TABLE `role` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `parent_id` int(11) DEFAULT NULL COMMENT '上级角色id',
  `name` varchar(50) DEFAULT NULL COMMENT '角色名',
  `permissions` text COMMENT '权限值列表,以","分割',
  `data_scope` text COMMENT '数据范围(环境编号列表)',
  `sort` int(11) DEFAULT NULL COMMENT '排序',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='角色表';

-- ----------------------------
-- Records of role
-- ----------------------------
INSERT INTO `role` VALUES ('1', '0', '管理员', '环境监测,系统管理', null, '999');
INSERT INTO `role` VALUES ('2', '0', '领导', '环境监测,系统管理,查询用户列表', null, '888');
INSERT INTO `role` VALUES ('3', '0', 'test', '环境监测,系统管理', '1222', '777');
INSERT INTO `role` VALUES ('4', '3', 'test', '环境监测,系统管理', '1222', '777');

-- ----------------------------
-- Table structure for tokens
-- ----------------------------
DROP TABLE IF EXISTS `tokens`;
CREATE TABLE `tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `token` varchar(100) DEFAULT NULL COMMENT 'token字符串',
  `level` tinyint(2) DEFAULT NULL COMMENT '级别',
  `ip` varchar(20) DEFAULT NULL COMMENT 'ip',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `last_activity` int(11) DEFAULT NULL COMMENT '最后存活时间',
  `user` text COMMENT '绑定用户json,包含用户及权限信息',
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=246 DEFAULT CHARSET=utf8 COMMENT='token身份认证';

-- ----------------------------
-- Records of tokens
-- ----------------------------
INSERT INTO `tokens` VALUES ('235', 'base_UmcyVmFqM2FvUHFRTndHVjBDS0NmQ2xwZkt4eEgvcEY0TStKZTM4UTlyU0JSMEZrVUxLL2JnPT0=', '1', '192.168.8.91', '1481677958', '1481703344', '{\"id\":\"1\",\"username\":\"admin\",\"level\":\"\\u8d85\\u7ea7\\u7ba1\\u7406\\u5458\",\"real_name\":\"\\u8d85\\u7ea7\\u7ba1\\u7406\\u5458\",\"permissions\":\"administrator\",\"data_scope\":\"\",\"ip\":\"192.168.8.91\"}');
INSERT INTO `tokens` VALUES ('236', 'base_eVB1U29aSjQvcS9qaERjcHV0WWdzd0lFZzRVN3pReVkwVWJ3clVscVVhdC8wcEJlcy9EOTBRPT0=', '1', '192.168.8.219', '1481678337', '1481700640', '{\"id\":\"1\",\"username\":\"admin\",\"level\":\"\\u8d85\\u7ea7\\u7ba1\\u7406\\u5458\",\"real_name\":\"\\u8d85\\u7ea7\\u7ba1\\u7406\\u5458\",\"permissions\":\"administrator\",\"data_scope\":\"\",\"ip\":\"192.168.8.219\"}');
INSERT INTO `tokens` VALUES ('237', 'base_aSszejE1MU1JblF4d3JKbXQxVjVadWRzcHdWS2RKbXZzK1A0LzR2cnVOM29wcmVEWi9XNDlRPT0=', '1', '::1', '1481679192', '1481681045', '{\"id\":\"1\",\"username\":\"admin\",\"level\":\"超级管理员\",\"real_name\":\"超级管理员\",\"permissions\":\"administrator\",\"data_scope\":\"\",\"ip\":\"::1\"}');
INSERT INTO `tokens` VALUES ('238', 'base_TGtqUTNXQi9IaldQaWdheEdLUDZCYnl3ck5kM0V4MHZzMzFLOWZCbkhVWlJTeDJqSVVNSWdnPT0=', '1', '127.0.0.1', '1481679545', '1481685587', '{\"id\":\"1\",\"username\":\"admin\",\"level\":\"超级管理员\",\"real_name\":\"超级管理员\",\"permissions\":\"administrator\",\"data_scope\":\"\",\"ip\":\"127.0.0.1\"}');
INSERT INTO `tokens` VALUES ('239', 'base_UWM0UVg2aXlzTTFaMll6L1FaOFcwRXZhckQxWWFLY21OeXMyZFN2bUpJbzR3cmNXdmx3Z0x3PT0=', '1', '192.168.8.215', '1481680621', '1481681120', '{\"id\":\"1\",\"username\":\"admin\",\"level\":\"\\u8d85\\u7ea7\\u7ba1\\u7406\\u5458\",\"real_name\":\"\\u8d85\\u7ea7\\u7ba1\\u7406\\u5458\",\"permissions\":\"administrator\",\"data_scope\":\"\",\"ip\":\"192.168.8.215\"}');
INSERT INTO `tokens` VALUES ('240', 'base_U2lCejUxUktVeFZjWjFSOHBlbldmRUNud0dzWjBkcittem5nSG9pRHhrczV3Z1pYS3hiQmJ3PT0=', '1', '192.168.8.152', '1481681348', '1481682343', '{\"id\":\"1\",\"username\":\"admin\",\"level\":\"\\u8d85\\u7ea7\\u7ba1\\u7406\\u5458\",\"real_name\":\"\\u8d85\\u7ea7\\u7ba1\\u7406\\u5458\",\"permissions\":\"administrator\",\"data_scope\":\"\",\"ip\":\"192.168.8.152\"}');
INSERT INTO `tokens` VALUES ('242', 'base_eXlVaUV1N2hOU0M3eWxYdENUUjhOaVk5aENXeC9rc3k3bzU1bDB1ZXlReWpXRnFOajVCcTh3PT0=', '1', '192.168.8.152', '1481684706', '1481684706', '{\"id\":\"1\",\"username\":\"admin\",\"level\":\"\\u8d85\\u7ea7\\u7ba1\\u7406\\u5458\",\"real_name\":\"\\u8d85\\u7ea7\\u7ba1\\u7406\\u5458\",\"permissions\":\"administrator\",\"data_scope\":\"\",\"ip\":\"192.168.8.152\"}');
INSERT INTO `tokens` VALUES ('243', 'base_alJSVG42U1FUUUlwbUZGcisyWVhFQk0xMGtwbVBQTVAzQzRvUkNxdHhNeFVVYnBVNS9xdDdBPT0=', '1', '192.168.8.152', '1481684729', '1481684809', '{\"id\":\"1\",\"username\":\"admin\",\"level\":\"\\u8d85\\u7ea7\\u7ba1\\u7406\\u5458\",\"real_name\":\"\\u8d85\\u7ea7\\u7ba1\\u7406\\u5458\",\"permissions\":\"administrator\",\"data_scope\":\"\",\"ip\":\"192.168.8.152\"}');
INSERT INTO `tokens` VALUES ('244', 'base_enlYVS82RGxldFNWT1NGUVlXWkVKbjBhbjRHbmxaWkJqbXFaZG1wdUF4dm9wcmVEWi9XNDlRPT0=', '1', '192.168.8.152', '1481684730', '1481684731', '{\"id\":\"1\",\"username\":\"admin\",\"level\":\"\\u8d85\\u7ea7\\u7ba1\\u7406\\u5458\",\"real_name\":\"\\u8d85\\u7ea7\\u7ba1\\u7406\\u5458\",\"permissions\":\"administrator\",\"data_scope\":\"\",\"ip\":\"192.168.8.152\"}');

-- ----------------------------
-- Table structure for user
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `username` varchar(50) DEFAULT NULL COMMENT '用户名',
  `password` varchar(50) DEFAULT NULL COMMENT '密码',
  `role_ids` text COMMENT '角色id列表',
  `status` varchar(20) DEFAULT NULL COMMENT '状态',
  `level` varchar(50) DEFAULT NULL COMMENT '用户级别(领导、研究者、工作人员)',
  `real_name` varchar(50) DEFAULT NULL COMMENT '真实姓名',
  `tel` varchar(50) DEFAULT NULL COMMENT '电话',
  `department` varchar(50) DEFAULT NULL COMMENT '部门',
  `position` varchar(50) DEFAULT NULL COMMENT '职位',
  `sort` int(11) DEFAULT NULL COMMENT '排序',
  `favorite` text COMMENT '偏好设置json',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='用户表';

-- ----------------------------
-- Records of user
-- ----------------------------
INSERT INTO `user` VALUES ('1', 'admin', 'f6fdffe48c908deb0f4c3bd36c032e72', '1,2', '正常', '超级管理员', '超级管理员', '18716435779', null, null, '1', '{\"bg\":[\"ds\",\"dss\",\"dds\",\"rr\"],\"music\":\"\\u6d6e\\u5938\",\"search\":{\"history\":[\"fd\",\"type\",\"sd\"]}}');
INSERT INTO `user` VALUES ('2', 'test', '05a671c66aefea124cc08b76ea6d30bb', '1', '锁定', '测试', '测试', '15555555', null, null, null, null);
INSERT INTO `user` VALUES ('3', 'admins', '1df07bcb21e91dd29ac01c91680ea349', '1,2', '正常', '工作人员', '刘丹', null, null, null, '1', null);

-- ----------------------------
-- Table structure for user_behavior
-- ----------------------------
DROP TABLE IF EXISTS `user_behavior`;
CREATE TABLE `user_behavior` (
  `uid` int(10) NOT NULL COMMENT '用户ID',
  `webkey` varchar(100) NOT NULL COMMENT '页面关键字',
  `behavior` text COMMENT '行为记录（json）',
  PRIMARY KEY (`uid`,`webkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户行为记录表';

-- ----------------------------
-- Records of user_behavior
-- ----------------------------
INSERT INTO `user_behavior` VALUES ('1', 'test', '1,2,3');
INSERT INTO `user_behavior` VALUES ('1', 'test1', '1,2,3,sdfdf');

-- ----------------------------
-- Table structure for user_ip
-- ----------------------------
DROP TABLE IF EXISTS `user_ip`;
CREATE TABLE `user_ip` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` varchar(100) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `login_time` int(11) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL COMMENT '登陆城市',
  `ip` varchar(50) DEFAULT NULL COMMENT '登录ip',
  `pass` varchar(50) DEFAULT NULL COMMENT '是否通过验证（是/否）',
  `code` varchar(10) DEFAULT NULL COMMENT '短信验证码',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户登陆ip表';

-- ----------------------------
-- Records of user_ip
-- ----------------------------

-- ----------------------------
-- Table structure for user_login
-- ----------------------------
DROP TABLE IF EXISTS `user_login`;
CREATE TABLE `user_login` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `login_time` int(11) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL COMMENT '登陆城市',
  `ip` varchar(50) DEFAULT NULL COMMENT '登录ip',
  `pass` varchar(50) DEFAULT NULL COMMENT '是否通过验证（是/否）',
  `code` varchar(10) DEFAULT NULL COMMENT '短信验证码',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8 COMMENT='用户登录记录';

-- ----------------------------
-- Records of user_login
-- ----------------------------
INSERT INTO `user_login` VALUES ('1', '1', '1478848642', null, null, null, null);
INSERT INTO `user_login` VALUES ('2', '1', '1479106474', null, null, null, null);
INSERT INTO `user_login` VALUES ('3', '1', '1479173622', null, null, null, null);
INSERT INTO `user_login` VALUES ('4', '1', '1479277922', null, null, null, null);
INSERT INTO `user_login` VALUES ('5', '1', '1479437877', null, null, null, null);
INSERT INTO `user_login` VALUES ('6', '1', '1479698002', null, null, null, null);
INSERT INTO `user_login` VALUES ('7', '1', '1479779293', null, null, null, null);
INSERT INTO `user_login` VALUES ('8', '1', '1479879846', null, null, null, null);
INSERT INTO `user_login` VALUES ('9', '1', '1479962998', null, null, null, null);
INSERT INTO `user_login` VALUES ('10', '1', '1480037882', null, null, null, null);
INSERT INTO `user_login` VALUES ('11', '1', '1480295287', null, null, null, null);
INSERT INTO `user_login` VALUES ('12', '3', '1480296141', null, null, null, null);
INSERT INTO `user_login` VALUES ('13', '1', '1480381925', null, null, null, null);
INSERT INTO `user_login` VALUES ('14', '1', '1480467949', null, null, null, null);
INSERT INTO `user_login` VALUES ('15', '1', '1480641975', null, null, null, null);
INSERT INTO `user_login` VALUES ('16', '1', '1482040738', null, null, null, null);
