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

			$this->count_base();
			lineMsg('博物馆基础数据统计完成');

			$this->count_complex();
			lineMsg('博物馆综合统计完成');

			$this->count_param();
			lineMsg('博物馆环境指标综合统计完成');




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
			$this->load->library($this->museum['db_type']."/api", array('db'=>$this->subdb));

			$data_base['count_relic'] = $this->api->count_relic();
			$data_base['count_precious_relic'] = $this->api->count_precious_relic();
			$data_base['count_fixed_exhibition'] = $this->api->count_fixed_exhibition();
			$data_base['count_temporary_exhibition'] = $this->api->count_temporary_exhibition();

			if($this->db->where('mid', $this->museum['id'])->count_all_results('data_base')){
				$this->db->where('mid', $this->museum['id'])->update('data_base', $data_base);
			}else{
				$data_base['mid'] = $this->museum['id'];
				$this->db->insert('data_base', $data_base);
			}

		}catch(Exception $e){
			throw new Exception("统计博物馆基础数据失败！");
		}
	}

	// 博物馆综合统计
	function count_complex(){
		try{
			$data_complex = array();
			$this->load->library($this->museum['db_type']."/api", array('db'=>$this->subdb));

			$data_complex['mid'] = $this->museum['id'];
			$data_complex['date'] = date("Ymd");
			$data_complex['standard'] = $this->api->count_standard();
			$data_complex['scatter_temp'] = $this->api->count_scatter_temp();
			$data_complex['scatter_humidity'] = $this->api->count_scatter_humidity();
			$data_complex['is_wave_abnormal'] = $this->api->count_is_wave_abnormal();
			$data_complex['is_value_abnormal'] = $this->api->count_is_value_abnormal();

			$this->db->insert('data_complex', $data_complex);

		}catch(Exception $e){
			throw new Exception("统计博物馆综合数据失败！");
		}
	}

	// 博物馆环境指标综合统计
	function count_param(){
		try{
			$data_param = array();
			$this->load->library($this->museum['db_type']."/api",array('db'=>$this->subdb));

			//$this->load->config("museum"); //读取湿度、光照配置文件
			//$humidity = $this->config->item("humidity");
			//$light = $this->config->item("light");
			$humidity = array(
					1=>array("石质","陶器","瓷器"),
					2=>array("铁质","青铜"),
					3=>array("纸质","壁画","纺织品","漆木器")
			);
			$light = array(
					1=>array("石质","陶器","瓷器","铁质","青铜"),
					2=>array("纸质","壁画","纺织品"),
					3=>array("漆木器")
			);
			$mid = $this->museum['id'];
			foreach($humidity as $k => $v){
				$paramStr = '('.implode("、",$v).')';
				$data_param[] = $this->api->count_param_humidity($mid,$k,$paramStr);

			}

			$this->db->insert_batch("data_param",$data_param);
		}catch (Exception $e){
			throw new Exception("统计博物馆环境指标综合数据失败！");
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
