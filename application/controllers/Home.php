<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller {
	var $subdb = null;
	var $museum = null;
	public function __construct(){
		parent::__construct();
	}

	/**
	* @param $no 序号（第几个博物馆）
	*/
	public function index($no=0){
		$this->museum = $this->db->limit(1, $no)->get("museum")->row_array();
		if(empty($this->museum)){
			lineMsg('未查询到博物馆！', true);
		}
		// 初始化数据库
		$this->initdb();
		lineMsg('开始统计:'.$this->museum['name']);
		// 事务
		$this->db->trans_begin();
		try{
			$this->load->library($this->museum['db_type']."/api", array('db'=>$this->subdb,'mid'=>$this->museum['id']));

			$this->count_base();
			lineMsg('博物馆基础数据统计完成');
			$this->count_complex();
			lineMsg('博物馆综合统计完成');
			$this->count_param();
			lineMsg('博物馆参数综合统计完成');
			$this->count_envtype_param();
			lineMsg('环境类型参数综合统计完成');
			$this->data_env();
			lineMsg('博物馆环境统计完成');
			$this->data_analysis();
			lineMsg('博物馆环境分析数据统计完成');

			$this->db->trans_commit();
		}catch(Exception $e){
			$this->db->trans_rollback();
			lineMsg($e->getMessage());
		}
		lineMsg('统计结束');
	}

	// 博物馆基础数据统计
	function count_base(){
		try{
			$data_base = array();
			$data_base['count_relic'] = $this->api->count_relic();
			$data_base['count_precious_relic'] = $this->api->count_precious_relic();
			$data_base['count_showcase'] = $this->api->count_showcase();
			if($this->db->where('mid', $this->museum['id'])->count_all_results('data_base')){
				$this->db->where('mid', $this->museum['id'])->update('data_base', $data_base);
			}else{
				$data_base['mid'] = $this->museum['id'];
				$this->db->insert('data_base', $data_base);
			}

		}catch(Exception $e){
			throw new Exception("博物馆基础数据统计失败！");
		}
	}

	// 博物馆综合统计
	function count_complex(){
		try{
			$data_complex = array();
			$data_complex['mid'] = $this->museum['id'];
			$data_complex['date'] = date("Ymd",$this->api->btime);
			$data_complex['scatter_temp'] = $this->api->count_scatter('temperature');
			$data_complex['scatter_humidity'] = $this->api->count_scatter('humidity');
			$data_complex['is_wave_abnormal'] = $this->api->count_is_wave_abnormal();
			$data_complex['is_value_abnormal'] = $this->api->count_is_value_abnormal();

			$this->db->insert('data_complex', $data_complex);
		}catch(Exception $e){
			throw new Exception("博物馆综合统计失败！");
		}
	}

	// 博物馆参数综合统计
	function count_param(){
		try{
			$this->load->config('texture');
			$humidity = $this->config->item("humidity");
			$light = $this->config->item("light");
			$data_param = array();
			$mid = $this->museum['id'];

			//温度统计
			$data_param[] = $this->api->count_param($mid,false,"temperature");
			//湿度统计（分3类）
			foreach($humidity as $k => $v){
				$res = $this->api->count_param_humidity($mid,false,$k);
				if($res) $data_param[] = $res;
			}
			//光照统计（分3类）
			foreach($light as $k => $v){
				$res = $this->api->count_param_light($mid,false,$k);
				if($res) $data_param[] = $res;
			}
			//紫外统计
			$data_param[] = $this->api->count_param($mid,false,"uv");
			//VOC统计
			$data_param[] = $this->api->count_param($mid,false,"voc");

			$this->db->insert_batch("data_param",$data_param);
		}catch (Exception $e){
			throw new Exception("博物馆参数综合统计失败！");
		}
	}

	// 环境类型参数综合统计
	public function count_envtype_param(){
		try{
			$data_envtype_param = array();
			$this->load->config('texture');
			$humidityArr = $this->config->item("humidity");
			$lightArr = $this->config->item("light");
			$envArr = array(1=>"展厅", 2=>"展柜", 3=>"库房");
			$mid = $this->museum['id'];

			//温度统计
			foreach ($envArr as $k=>$v) {
				$result = $this->api->count_param($mid,$k,"temperature");
				if($result){
					$data_envtype_param[] = $result;
				}
			}
			//湿度统计（分3类）
			foreach ($envArr as $env_k=>$env_v) {
				foreach($humidityArr as $hum_k => $v){
					$result = $this->api->count_param_humidity($mid,$env_k,$hum_k);
					if($result){
						$data_envtype_param[] = $result;
					}
				}
			}
			//光照统计（分3类）
			foreach ($envArr as $env_k=>$env_v) {
				foreach($lightArr as $light_k => $v){
					$result = $this->api->count_param_light($mid,$env_k,$light_k);
					if($result){
						$data_envtype_param[] = $result;
					}
				}
			}
			//紫外统计
			foreach($envArr as $k=>$v){
				$result = $this->api->count_param($mid,$k,"uv");
				if($result){
					$data_envtype_param[] = $result;
				}
			}
			//VOC统计
			foreach($envArr as $k=>$v){
				$result = $this->api->count_param($mid,$k,"voc");
				if($result){
					$data_envtype_param[] = $result;
				}
			}
			

			$this->db->insert_batch("data_envtype_param",$data_envtype_param);
		}catch (Exception $e){
			throw new Exception("环境类型参数综合统计失败！");
		}

	}

	function data_env(){
		try{
			$datas = $this->api->data_env();
			foreach ($datas as $data){
				$exist = $this->db->select("id")->where(array("mid"=>$this->museum["id"],"sourceid"=>$data["sourceid"]))->get("data_env")->row_array();
				if($exist){
					$this->db->where("id",$exist["id"])->update("data_env",$data);
				}else{
					$this->db->insert("data_env",$data);
				}
			}

		}catch(Exception $e){
			throw new Exception("统计博物馆环境失败！");
		}
	}

	function data_analysis(){
		try{
			$datas = $this->api->data_analysis();
			foreach ($datas as $k => $data){
				$this->db->insert_batch("data_env_".$k,$data);
			}
		}catch(Exception $e){
			throw new Exception("统计博物馆环境分析数据失败！");
		}
	}


	// 初始化数据库
	private function initdb(){
		$museum = $this->museum;
		try{
			switch ($museum['db_type']) {
				case 'mysql':
					$this->subdb = array();
					$config = array();
					// mysql要求各子库ip、用户名、密码必须相同。
					$config['hostname'] = $museum['db_host'];
					$config['username'] = $museum['db_user'];
					$config['password'] = $museum['db_pass'];
					$config['dbdriver'] = 'mysqli';
					// 分子系统连接数据库
					foreach (array('base', 'relic', 'env') as $sub) {
						$config['database'] = $museum['db_name'].'_'.$sub;
						$this->subdb[$sub] = $this->load->database($config, TRUE);
					}
					break;
				case 'mongo':
					$server = 'mongodb://'.$museum['db_host'];
					$options = array();
					if($museum['db_user'] && $museum['db_pass']){
						$options['username'] = $museum['db_user'];
						$options['password'] = $museum['db_pass'];
					}
					$mongo = new MongoClient($server, $options);
					if($mongo){
						$this->subdb = $mongo->$museum['db_name'];
					}
					break;
				default:
					break;
			}
		}catch(Exception $e){
			lineMsg("数据库初始化失败：".$e->getMessage(), true);
		}

	}




	// 测试
	public function test(){
		$server = 'mongodb://192.168.8.11:27017';
		$options = array();
		// $options['username'] = '';
		// $options['password'] = '';
		$mongo = new MongoClient($server, $options);
		$data = $mongo->museum_ya->relic->base->find();
		debug(iterator_to_array($data));
	}
}
