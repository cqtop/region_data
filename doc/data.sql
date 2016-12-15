/*
Navicat MySQL Data Transfer

Source Server         : 192.168.8.11
Source Server Version : 50620
Source Host           : 192.168.8.11:3306
Source Database       : region_base

Target Server Type    : MYSQL
Target Server Version : 50620
File Encoding         : 65001

Date: 2016-12-14 16:24:32
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for data_abnormal
-- ----------------------------
DROP TABLE IF EXISTS `data_abnormal`;
CREATE TABLE `data_abnormal` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `date` varchar(10) NOT NULL COMMENT '日期D/周W/月M',
  `mid` int(10) NOT NULL COMMENT '博物馆ID',
  `depid` int(10) NOT NULL COMMENT '环境类型参数综合统计ID',
  `equip_no` varchar(50) DEFAULT NULL COMMENT '设备编号',
  `val` varchar(20) DEFAULT NULL COMMENT '数据值',
  `time` varchar(10) DEFAULT NULL COMMENT '时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=311348 DEFAULT CHARSET=utf8 COMMENT='数据统计 - 数据异常值列表（环境类型参数综合统计扩展表）';

-- ----------------------------
-- Table structure for data_base
-- ----------------------------
DROP TABLE IF EXISTS `data_base`;
CREATE TABLE `data_base` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `mid` int(10) NOT NULL COMMENT '博物馆ID',
  `count_relic` int(5) DEFAULT NULL COMMENT '馆藏文物数量',
  `count_precious_relic` int(5) DEFAULT NULL COMMENT '珍贵文物数量',
  `count_fixed_exhibition` int(5) DEFAULT NULL COMMENT '固定展览数量',
  `count_temporary_exhibition` int(5) DEFAULT NULL COMMENT '临时展览数量',
  `count_cabinet` int(5) DEFAULT NULL COMMENT '展柜数量',
  `count_hall` int(5) DEFAULT NULL COMMENT '展厅数量',
  `count_storeroom` int(5) DEFAULT NULL COMMENT '库房数量',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COMMENT='数据统计 - 博物馆基础数据';

-- ----------------------------
-- Table structure for data_complex
-- ----------------------------
DROP TABLE IF EXISTS `data_complex`;
CREATE TABLE `data_complex` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `date` varchar(10) NOT NULL COMMENT '日期D/周W/月M',
  `mid` int(10) NOT NULL COMMENT '博物馆ID',
  `env_type` varchar(20) NOT NULL COMMENT '环境类型',
  `scatter_temperature` float(5,4) DEFAULT NULL COMMENT '温度离散系数',
  `scatter_humidity` float(5,4) DEFAULT NULL COMMENT '湿度离散系数',
  `scatter_light` float(5,4) DEFAULT NULL COMMENT '光照离散系数',
  `scatter_uv` float(5,4) DEFAULT NULL COMMENT '紫外离散系数',
  `scatter_voc` float(5,4) DEFAULT NULL COMMENT 'VOC离散系数',
  `temperature_total` int(5) DEFAULT NULL COMMENT '温度数据总数',
  `temperature_abnormal` int(5) DEFAULT NULL COMMENT '温度未达标数',
  `humidity_total` int(5) DEFAULT NULL COMMENT '湿度数据总数',
  `humidity_abnormal` int(5) DEFAULT NULL COMMENT '湿度未达标数',
  `light_total` int(5) DEFAULT NULL COMMENT '光照数据总数',
  `light_abnormal` int(5) DEFAULT NULL COMMENT '光照未达标数',
  `uv_total` int(5) DEFAULT NULL COMMENT '紫外数据总数',
  `uv_abnormal` int(5) DEFAULT NULL COMMENT '紫外未达标数',
  `voc_total` int(5) DEFAULT NULL COMMENT 'VOC数据总数',
  `voc_abnormal` int(5) DEFAULT NULL COMMENT 'VOC未达标数',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=859 DEFAULT CHARSET=utf8 COMMENT='数据统计 - 环境类型综合统计';

-- ----------------------------
-- Table structure for data_complex_env
-- ----------------------------
DROP TABLE IF EXISTS `data_complex_env`;
CREATE TABLE `data_complex_env` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `date` varchar(10) NOT NULL COMMENT '日期D/周W/月M',
  `mid` int(10) NOT NULL COMMENT '博物馆ID',
  `env_no` varchar(30) DEFAULT NULL COMMENT '环境编号',
  `env_type` varchar(20) NOT NULL COMMENT '环境类型',
  `scatter_temperature` float(5,4) DEFAULT NULL COMMENT '温度离散系数',
  `scatter_humidity` float(5,4) DEFAULT NULL COMMENT '湿度离散系数',
  `scatter_light` float(5,4) DEFAULT NULL COMMENT '光照离散系数',
  `scatter_uv` float(5,4) DEFAULT NULL COMMENT '紫外离散系数',
  `scatter_voc` float(5,4) DEFAULT NULL COMMENT 'VOC离散系数',
  `temperature_total` int(5) DEFAULT NULL COMMENT '温度数据总数',
  `temperature_abnormal` int(5) DEFAULT NULL COMMENT '温度未达标数',
  `humidity_total` int(5) DEFAULT NULL COMMENT '湿度数据总数',
  `humidity_abnormal` int(5) DEFAULT NULL COMMENT '湿度未达标数',
  `light_total` int(5) DEFAULT NULL COMMENT '光照数据总数',
  `light_abnormal` int(5) DEFAULT NULL COMMENT '光照未达标数',
  `uv_total` int(5) DEFAULT NULL COMMENT '紫外数据总数',
  `uv_abnormal` int(5) DEFAULT NULL COMMENT '紫外未达标数',
  `voc_total` int(5) DEFAULT NULL COMMENT 'VOC数据总数',
  `voc_abnormal` int(5) DEFAULT NULL COMMENT 'VOC未达标数',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=560 DEFAULT CHARSET=utf8 COMMENT='数据统计 - 环境综合统计';

-- ----------------------------
-- Table structure for data_envtype_param
-- ----------------------------
DROP TABLE IF EXISTS `data_envtype_param`;
CREATE TABLE `data_envtype_param` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `date` varchar(10) NOT NULL COMMENT '日期D/周W/月M',
  `mid` int(10) NOT NULL COMMENT '博物馆ID',
  `env_type` varchar(20) DEFAULT NULL COMMENT '环境类型',
  `param` varchar(20) DEFAULT NULL COMMENT '参数名称（湿度、光照要分材质）',
  `max` float(5,2) DEFAULT NULL COMMENT '最大值',
  `min` float(5,2) DEFAULT NULL COMMENT '最小值',
  `wave` varchar(100) DEFAULT NULL COMMENT '日波动（min,max,min2,max2）',
  `wave_status` int(5) DEFAULT NULL COMMENT '日波动超标状态（1111：min|max|min2|max2）',
  `middle` float(5,2) DEFAULT NULL COMMENT '中位值',
  `average` float(5,2) DEFAULT NULL COMMENT '平均值（剔除异常值）',
  `count_abnormal` int(5) DEFAULT NULL COMMENT '异常值个数',
  `standard` float(5,2) DEFAULT NULL COMMENT '标准差',
  `compliance` float(5,2) DEFAULT NULL COMMENT '达标率',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1719 DEFAULT CHARSET=utf8 COMMENT='数据统计 - 环境类型参数综合统计';

-- ----------------------------
-- Table structure for data_wave_abnormal
-- ----------------------------
DROP TABLE IF EXISTS `data_wave_abnormal`;
CREATE TABLE `data_wave_abnormal` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `date` varchar(20) NOT NULL COMMENT '日期D/周W/月M',
  `mid` int(10) NOT NULL COMMENT '博物馆ID',
  `depid` int(10) NOT NULL COMMENT '环境类型参数综合统计ID',
  `type` int(2) DEFAULT NULL COMMENT '是否剔除异常值：1是 0否',
  `env_name` varchar(50) DEFAULT NULL COMMENT '环境名称',
  `val` varchar(20) DEFAULT NULL COMMENT '波动值',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11078 DEFAULT CHARSET=utf8 COMMENT='数据统计 - 环境日波动异常表（环境类型参数综合统计扩展表）';
