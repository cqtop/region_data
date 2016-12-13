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


	// 主页 日志
	public function index(){
		if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest'){
			$month = $this->input->post('month');
			$file = "./logs/{$month}.log";
			if(file_exists($file)){
				echo file_get_contents($file);
			}else{
				echo '文件未找到';
			}
		}else{
			$month = array();
			if ($handle = opendir('./logs')) {
			    while (false !== ($file = readdir($handle))) {
			        if(preg_match("/^(\d{6})\.log$/", $file, $matches)){
			        	array_push($month, $matches[1]);
			        }
			    }
			    closedir($handle);
			}

			$data['month'] = $month;
			$this->load->view('home', $data);
		}
	}


	/**
	 * @param $no 序号（第几个博物馆）
	 * @param $date 日期（某天的数据）
	 */
	public function count($no=0,$date=''){
		$this->date = $date;
		lineMsg(PHP_EOL.'============== '.($date?$date:date('Y-m-d', strtotime('yesterday'))).' ========');
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
			$date_base['count_hall'] = $this->api->count_hall();
			$date_base['count_storeroom'] = $this->api->count_storeroom();
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


	// 统计
	public function countmusem($date=''){
		$this->date = $date?$date:date('Y-m-d', strtotime('yesterday'));
		echo '====== '.$this->date.' ======<br>';

		$museum = $this->db->get("museum")->result_array();
		foreach ($museum as $m) {
			echo '+++ '.$m['name'].'<br>';
			$this->museum = $m;
			$this->initdb();
			unset($this->api);
			$this->load->library($m['db_type']."_api", array('db'=>$this->subdb,'mid'=>$m['id'],'date'=>$this->date),"api");
			$data = $this->api->countmusem();
			echo 'count_day: '.number_format($data['count_day']).'<br>';
			echo 'count_month: '.number_format($data['count_month']).'<br>';
		}

	}
}