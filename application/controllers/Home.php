<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller {
	var $subdb = null;
	var $museum = null;
	var $date = null; //获取某天的数据
	public function __construct(){
		parent::__construct();
		//$this->date = "2016-11-15";
	}
	/**
	 * @param $no 序号（第几个博物馆）
	 * @param $date 日期（某天的数据）
	 */
	public function index($no=0,$date=''){
		$this->date = $date;
		lineMsg('===================='.($date?$date:date('Y-m-d')));
		lineMsg('+++ start memory:'.number_format(memory_get_usage()));
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
			$this->load->library($this->museum['db_type']."_api", array('db'=>$this->subdb,'mid'=>$this->museum['id'],'date'=>$this->date),"api");
			$this->count_base();
			lineMsg('博物馆基础数据统计完成');
			$this->count_complex();
			lineMsg('博物馆综合统计完成');
			$this->data_envtype_param();
			lineMsg('环境类型参数综合统计完成');
			$this->db->trans_commit();
		}catch(Exception $e){
			$this->db->trans_rollback();
			lineMsg($e->getMessage());
		}
		lineMsg('统计结束');
		lineMsg('+++ end memory:'.number_format(memory_get_usage()));
		lineMsg('+++ memory peak:'.number_format(memory_get_peak_usage()));
	}
	public function data_envtype_param(){ //环境类型参数综合统计
		try{
			$datas = $this->api->data_envtype_param();
			foreach ($datas as $data){
				$abnormal = $data["abnormal"];
				$wave_arr = $data["wave_arr"];
				unset($data["wave_arr"],$data["abnormal"]);
				$exist = $this->db->select("id")
					->where("date",$data["date"])
					->where("mid",$data["mid"])
					->where("env_type",$data["env_type"])
					->where("param",$data["param"])
					->get("data_envtype_param")
					->row_array();
				if($exist){
					$depid = $exist["id"];
					$this->db->where("id",$depid)->update("data_envtype_param",$data);
				}else{
					$this->db->insert("data_envtype_param",$data);
					$depid = $this->db->insert_id();
				}
				if(!empty($abnormal)){
					foreach ($abnormal as $k=>$v){
						$abnormal[$k]["depid"] = $depid;
					}
					$this->db->delete("data_abnormal","depid = ".$depid);
					$this->db->insert_batch("data_abnormal",$abnormal);
				}
				if(!empty($wave_arr)){
					foreach ($wave_arr as $k=>$v){
						$wave_arr[$k]["depid"] = $depid;
					}
					$this->db->delete("data_wave_abnormal","depid = ".$depid);
					$this->db->insert_batch("data_wave_abnormal",$wave_arr);
				}
			}
		}catch(Exception $e){
			throw new Exception("统计博物馆环境分析数据失败！");
		}
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
			foreach(array("yesterday","week","month") as $date){
				foreach(array(1=>"展厅", 2=>"展柜", 3=>"库房") as $k=>$v){
					$result = $this->api->count_data_complex($date,$k);
					if(!$result) continue;
					$old_datas = $this->db
						->where("date",$result['date'])
						->where("env_type",$v)
						->where("mid",$this->museum['id'])
						->get("data_complex")
						->result_array();
					if($old_datas) {
						$this->db->where('date',$result['date'])
							->where("env_type",$v)
							->where("mid",$this->museum['id'])
							->update('data_complex', $result);
					}else{
						$this->db->insert("data_complex",$result);
					}
				}
			}
		}catch(Exception $e){
			throw new Exception("博物馆综合统计失败！");
		}
	}
	// 初始化数据库
	private function initdb(){
		$museum = $this->museum;
		try{
			switch ($museum['db_type']) {
				case 'Mysql':
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
				case 'Mongo':
					$server = 'mongodb://'.$museum['db_host'];
					$options = array();
					if($museum['db_user'] && $museum['db_pass']){
						$options['username'] = $museum['db_user'];
						$options['password'] = $museum['db_pass'];
						$options['db'] = $museum['db_name'];
					}
					$mongo = new MongoClient($server, $options);
					if($mongo){
						$this->subdb = $mongo->$museum['db_name'];
					}
					break;
				default:
					throw new Exception("数据库类型错误");
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