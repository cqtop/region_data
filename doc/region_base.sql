/*
Navicat MySQL Data Transfer

Source Server         : 192.168.8.11
Source Server Version : 50620
Source Host           : 192.168.8.11:3306
Source Database       : region_base

Target Server Type    : MYSQL
Target Server Version : 50620
File Encoding         : 65001

Date: 2016-10-20 11:19:08
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
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='系统设置';

-- ----------------------------
-- Table structure for data_base
-- ----------------------------
DROP TABLE IF EXISTS `data_base`;
CREATE TABLE `data_base` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `mid` int(10) NOT NULL,
  `count_relic` int(5) DEFAULT NULL COMMENT '馆藏文物数量',
  `count_precious_relic` int(5) DEFAULT NULL COMMENT '珍贵文物数量',
  `count_fixed_exhibition` int(5) DEFAULT NULL COMMENT '固定展览数量',
  `count_temporary_exhibition` int(5) DEFAULT NULL COMMENT '临时展览数量',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='数据统计 - 基础数据';

-- ----------------------------
-- Table structure for data_complex
-- ----------------------------
DROP TABLE IF EXISTS `data_complex`;
CREATE TABLE `data_complex` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `date` int(8) DEFAULT NULL COMMENT '日期',
  `mid` int(10) DEFAULT NULL COMMENT '博物馆ID',
  `standard` float(2,2) DEFAULT NULL COMMENT '达标率',
  `scatter_temp` float(2,2) DEFAULT NULL COMMENT '温度离散系数',
  `scatter_humidity` float(2,2) DEFAULT NULL COMMENT '湿度离散系数',
  `is_wave_abnormal` tinyint(1) DEFAULT NULL COMMENT '是否有日波动超标',
  `is_value_abnormal` tinyint(1) DEFAULT NULL COMMENT '是否有异常值',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='数据统计 - 综合统计';

-- ----------------------------
-- Table structure for data_envtype_param
-- ----------------------------
DROP TABLE IF EXISTS `data_envtype_param`;
CREATE TABLE `data_envtype_param` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `date` int(8) DEFAULT NULL COMMENT '日期',
  `mid` int(10) DEFAULT NULL COMMENT '博物馆ID',
  `env_type` varchar(100) DEFAULT NULL COMMENT '环境类型',
  `param` varchar(20) DEFAULT NULL COMMENT '参数名称',
  `max` float(5,2) DEFAULT NULL COMMENT '最大值',
  `min` float(5,2) DEFAULT NULL COMMENT '最小值',
  `middle` float(5,2) DEFAULT NULL COMMENT '中位值',
  `average` float(5,2) DEFAULT NULL COMMENT '平均值（剔除异常值）',
  `count_abnormal` int(5) DEFAULT NULL COMMENT '异常值个数',
  `max2` float(5,2) DEFAULT NULL COMMENT '最大值（剔除异常值）',
  `min2` float(5,2) DEFAULT NULL COMMENT '最小值（剔除异常值）',
  `standard` float(5,2) DEFAULT NULL COMMENT '标准差',
  `compliance` float(2,2) DEFAULT NULL COMMENT '达标率',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='数据统计 - 环境类型参数';

-- ----------------------------
-- Table structure for data_env_param
-- ----------------------------
DROP TABLE IF EXISTS `data_env_param`;
CREATE TABLE `data_env_param` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `date` int(8) DEFAULT NULL COMMENT '日期',
  `mid` int(10) DEFAULT NULL COMMENT '博物馆ID',
  `env_name` varchar(100) DEFAULT NULL COMMENT '环境名称',
  `param` varchar(20) DEFAULT NULL COMMENT '参数名称',
  `max` float(5,2) DEFAULT NULL COMMENT '最大值',
  `min` float(5,2) DEFAULT NULL COMMENT '最小值',
  `middle` float(5,2) DEFAULT NULL COMMENT '中位值',
  `average` float(5,2) DEFAULT NULL COMMENT '平均值（剔除异常值）',
  `count_abnormal` int(5) DEFAULT NULL COMMENT '异常值个数',
  `max2` float(5,2) DEFAULT NULL COMMENT '最大值（剔除异常值）',
  `min2` float(5,2) DEFAULT NULL COMMENT '最小值（剔除异常值）',
  `standard` float(5,2) DEFAULT NULL COMMENT '标准差',
  `compliance` float(2,2) DEFAULT NULL COMMENT '达标率',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='数据统计 - 环境参数';

-- ----------------------------
-- Table structure for data_param
-- ----------------------------
DROP TABLE IF EXISTS `data_param`;
CREATE TABLE `data_param` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `date` int(8) DEFAULT NULL COMMENT '日期',
  `mid` int(10) DEFAULT NULL COMMENT '博物馆ID',
  `param` varchar(20) DEFAULT NULL COMMENT '参数名称',
  `max` float(5,2) DEFAULT NULL COMMENT '最大值',
  `min` float(5,2) DEFAULT NULL COMMENT '最小值',
  `middle` float(5,2) DEFAULT NULL COMMENT '中位值',
  `average` float(5,2) DEFAULT NULL COMMENT '平均值（剔除异常值）',
  `count_abnormal` int(5) DEFAULT NULL COMMENT '异常值个数',
  `max2` float(5,2) DEFAULT NULL COMMENT '最大值（剔除异常值）',
  `min2` float(5,2) DEFAULT NULL COMMENT '最小值（剔除异常值）',
  `standard` float(5,2) DEFAULT NULL COMMENT '标准差',
  `compliance` float(2,2) DEFAULT NULL COMMENT '达标率',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='数据统计 - 参数';

-- ----------------------------
-- Table structure for data_scatter
-- ----------------------------
DROP TABLE IF EXISTS `data_scatter`;
CREATE TABLE `data_scatter` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `date` int(8) DEFAULT NULL COMMENT '日期',
  `mid` int(10) DEFAULT NULL COMMENT '博物馆ID',
  `env_name` varchar(100) DEFAULT NULL COMMENT '环境名称',
  `param` varchar(20) DEFAULT NULL COMMENT '温度或者湿度',
  `scatter` float(2,2) DEFAULT NULL COMMENT '离散系数',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='数据统计 - 环境离散系数';

-- ----------------------------
-- Table structure for data_standard
-- ----------------------------
DROP TABLE IF EXISTS `data_standard`;
CREATE TABLE `data_standard` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `date` int(8) DEFAULT NULL COMMENT '日期',
  `mid` int(10) DEFAULT NULL COMMENT '博物馆ID',
  `env_name` varchar(100) DEFAULT NULL COMMENT '环境名称',
  `param` varchar(50) DEFAULT NULL COMMENT '参数组合',
  `standard` float(2,2) DEFAULT NULL COMMENT '达标率',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='数据统计 - 环境参数达标率';

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
-- Table structure for museum
-- ----------------------------
DROP TABLE IF EXISTS `museum`;
CREATE TABLE `museum` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name` varchar(50) NOT NULL COMMENT '博物馆名称',
  `db_type` varchar(20) DEFAULT NULL COMMENT '数据库类型：mysql或者mongo',
  `db_host` varchar(20) DEFAULT NULL COMMENT '数据库主机（包含端口）',
  `db_user` varchar(20) DEFAULT NULL COMMENT '数据库用户名',
  `db_pass` varchar(20) DEFAULT NULL COMMENT '数据库密码',
  `db_name` varchar(20) DEFAULT NULL COMMENT 'mysql：子系统数据库名称前缀；mongo：数据库名称',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='博物馆列表';

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='token身份认证';

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户登录记录';
