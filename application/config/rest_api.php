<?php defined('BASEPATH') OR exit('No direct script access allowed');

$config['debug'] = TRUE;//返回错误时调试信息,_token,_get_args,_post_args
/**
 * api config
 */
$config['local_api'] = 'base';//本地api名

//api列表
$config['api_hosts'] = array();

$config['allow_cross_domain'] = FALSE;//允许跨域
//rest支持jsonp返回
$config['force_https'] = FALSE;//只接受https请求
$config['enable_emulate_request'] = TRUE;//开启模拟请求

$config['allowed_http_methods'] = array('get', 'delete', 'post', 'put', 'options', 'patch', 'head');

/*
开启 Token 认证，每次发出请求都必须带上
  	CREATE TABLE `tokens` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `token` varchar(100) NOT NULL COMMENT 'token',
	  `level` int(2) NOT NULL COMMENT '级别',
	  `ip` varchar(20) NOT NULL COMMENT 'ip',
	  `create_time` int(11) NOT NULL COMMENT '创建时间',
	  `last_activity` int(11) NOT NULL COMMENT '最后存活时间',
	  `user` text COMMENT '绑定用户json',
	  PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='token用户认证';
*/
$config['rest_token_enable'] = TRUE;
$config['rest_token_param_name'] = 'access_token';//Token参数名,header,get,post, 长度超过40
$config['rest_token_auto_insert'] = TRUE;//自动注册 任意token,否则需要进行解密，解密错误则access_token无效，API_decode(app,token)
/*
| IP 白名单
| Example: ['123.456.789.0','987.654.32.1'];
| 127.0.0.1 and 0.0.0.0 are allowed by default.
*/
$config['rest_ip_white_list'] = array();

/*
| 开启日志
	CREATE TABLE `logs` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `uri` varchar(255) NOT NULL,
	  `method` varchar(6) NOT NULL,
	  `params` text DEFAULT NULL,
	  `token` varchar(40) NOT NULL,
	  `ip_address` varchar(45) NOT NULL,
	  `start_time` varchar(20) NOT NULL,
	  `exec_time` varchar(10) DEFAULT NULL,
	  `user` text NOT NULL,
	  PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='api访问日志';
*/
$config['rest_logging_enable'] = FALSE;//记录api访问日志
$config['rest_logs_table'] = 'logs';//日志表名