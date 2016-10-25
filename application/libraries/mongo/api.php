<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class API{

    public function __construct($param)
    {
        //$this->db = $param['db'];
        require_once("Mongo_db.php");
        $this->mongo_db = new Mongo_db($param['db']);
        $this->CI = & get_instance();
        $this->CI->load->config("texture");
        $this->texture = config_item("texture");
        $this->areas = array();
        $this->museum_id = $param["mid"];
        $this->getArea();
    }

    private function getArea(){
        $ty = array("展厅", "展柜", "库房");
        $ids = $ty_arr = array();
        $tyid = $this->mongo_db->select(array("_id","name"))->where_in("name", $ty)->get("area.type");
        foreach ($tyid as $v) {
            $ids[] = $v["_id"];
            $ty_arr["'".$v["_id"]->{'$id'}."'"] = $v["name"];
        }
        $areas = $this->mongo_db->select(array("No","_id","name","type","threshold"))->where_in("type", $ids)->get("area.base");
        foreach ($areas as $k => $v){
            $areas[$k]["type"] = $ty_arr["'".$v["type"]->{'$id'}."'"];
        }
        $this->areas = $areas;
    }

    public function count_relic()
    {
        //return $this->db->relic->base->count();
        return $this->mongo_db->count("relic.base");
    }


    public function data_analysis() //环境综合统计  //环境参数达标统计 //环境参数综合统计
    {
        $areanos = $data_area = $env_arr = $threshold_arr = array();
        $params = array("temperature","humidity","light","uv","voc");
        //$now_time = strtotime(date("Y-m-d"));
        $now_time = strtotime("2016-01-05");
        foreach ($this->areas as $v) {
            $areanos[] = $v["No"];
        }
        $datas = $this->mongo_db->select(array("param","areano"))->where_in("areano", $areanos)->where_between("receivetime", $now_time - 24 * 60 * 60, $now_time)->get("data.sensor.".date("Y"));
        foreach ($datas as $v){
            if(array_key_exists("param",$v) && $v["param"]){
                if(is_array($v["param"])){
                    foreach ($params as $p){
                        if(array_key_exists($p,$v["param"]) && $v["param"][$p]){
                            $data_area[$v["areano"]][$p][] = $v["param"][$p]; //把当天所有数据统计到$data_area中，分环境->参数类型
                        }
                    }

                }
            }
        }
        $data_env = $this->CI->db->select("*")->where("mid",$this->museum_id)->get("data_env")->result_array();
        foreach ($data_env as $v){
            $env_arr[$v["sourceid"]] = $v["id"];
            $threshold_arr[$v["sourceid"]] = $v;
        }
        foreach ($data_area as $areano => $value){
            $param = array("temperature","humidity");
            $data = $data_compliance = $data_param = array();
            $data["date"] = $data_compliance["date"] = $data_param["date"] = date("Ymd",$now_time - 24 * 60 * 60);
            $data["eid"] = $data_compliance["eid"] = $data_param["eid"] = $env_arr[$areano];
            foreach ($params as $p){
                if(array_key_exists($p, $value) && is_array($value[$p]) && $value[$p]){
                    //环境综合统计
                    $average = round(array_sum($value[$p])/sizeof($value[$p]),2);
                    $sum = 0;
                    foreach ($value[$p] as $k =>$v){
                        $sum += pow($v - $average,2);
                    }
                    $standard = sqrt($sum/sizeof($value[$p]));//标准差
                    $scatter = round($standard/$average,2);//离散系数
                    if(in_array($p, $param)){
                        $data[$p."_scatter"] = $scatter;
                    }

                    //环境参数达标统计
                    $data_compliance[$p."_total"] = sizeof($value[$p]);
                    $data_compliance[$p."_abnormal"] = 0;
                    $normal = array();
                    if($threshold_arr[$areano][$p."_upper"] !== null || $threshold_arr[$areano][$p."_lower"] !== null ){
                        foreach ($value[$p] as $p1){
                            if($threshold_arr[$areano][$p."_upper"] && $p1 > $threshold_arr[$areano][$p."_upper"]){
                                $data_compliance[$p."_abnormal"]++;
                            }else if($threshold_arr[$areano][$p."_lower"] && $p1 < $threshold_arr[$areano][$p."_lower"]){
                                $data_compliance[$p."_abnormal"]++;
                            }else{
                                $normal[] = $p1;//正常值
                            }
                        }
                    }
                    if(empty($normal)){
                        $normal = $value[$p];
                    }
                    //环境参数综合统计
                    $data_param["param"] = $p;
                    $data_param["max"] = max($value[$p]);
                    $data_param["min"] = min($value[$p]);
                    $data_param["max2"] = max($normal);
                    $data_param["min2"] = min($normal);
                    asort($value[$p]);
                    if(sizeof($value[$p])%2 == 0){ //中位值
                        $end = intval(sizeof($value[$p])/2);
                        $flag = $value[$p][$end-1] + $value[$p][$end];
                        $data_param["middle"] = round($flag/2,2);
                    }else{
                        $data_param["middle"] = $value[$p][intval((sizeof($value[$p])+1)/2)-1];
                    }
                    $data_param["average"] = round(array_sum($normal)/sizeof($normal),2);
                    $data_param["count_abnormal"] = $data_compliance[$p."_abnormal"];
                    $data_param["standard"] = $standard;
                    $this->CI->db->insert("data_env_param",$data_param);
                }
            }
            $this->CI->db->insert("data_env_compliance",$data_compliance);
            $this->CI->db->insert("data_env_complex",$data);
        }

    }

    public function data_env(){ //环境表,需最先执行以获取全部环境
        foreach ($this->areas as $v) {
            $data = array();
            $other = array("humidity"=>3,"light"=>6);
            $params = array("temperature","humidity","light","uv","voc");
            //根据文物找材质类型
            $relic = $this->mongo_db->select(array("type"))->where(array("place"=>$v["_id"]))->get("relic.base");

            foreach ($relic as $r){
                if($r["type"]){
                    $flag = false;
                    foreach ($this->texture as $k => $t){
                        foreach ($t as $k1 => $t1){
                            if(!empty($t1)){
                                if(in_array($r["type"],$t1)){
                                    $data["material_".$k1] = (array_key_exists("material_".$k1,$data) && $data["material_".$k1])?$data["material_".$k1]:$k;
                                    $flag = true;
                                }
                            }
                        }
                    }
                    
                    if($flag){
                        break;
                    }
                }

            }

            foreach ($other as $k => $o){
                $data["material_".$k] = (array_key_exists("material_".$k,$data) && $data["material_".$k])?$data["material_".$k]:$o;
            }
            if(array_key_exists("threshold",$v) && $v["threshold"]){ //查阈值
                $threshold = $this->mongo_db->where(array("_id"=>$v["threshold"]))->getOne("threshold");
                if($threshold){
                    foreach ($params as $p){
                        if(array_key_exists($p,$threshold) && $threshold[$p]){
                            $data[$p."_lower"] = $threshold[$p]["min"] !== ''?$threshold[$p]["min"]:null;
                            $data[$p."_upper"] = $threshold[$p]["max"] !== ''?$threshold[$p]["max"]:null;
                        }
                    }
                }
            }

            $data["mid"] = $this->museum_id;
            $data["sourceid"] = $v["No"];
            $data["name"] = $v["name"];
            $data["env_type"] = $v["type"];

            $exist = $this->CI->db->select("id")->where(array("mid"=>$this->museum_id,"sourceid"=>$v["No"]))->get("data_env")->row_array();
            if($exist){
                $this->CI->db->where("id",$exist["id"])->update("data_env",$data);
            }else{
                $this->CI->db->insert("data_env",$data);
            }

        }
    }


    
    
}