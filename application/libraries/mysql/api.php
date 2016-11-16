<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class API{

    public $btime = null;//查询开始时间
    public $etime = null;//查询结束时间
    public $EnvNo = array();//环境编号数组(展厅+展柜+库房)
    public $humidityEnvNo = array();
    public $lightEnvNo = array();

    private $texture_no = array();
    private $hall = array();
    private $showcase = array();
    private $storeroom = array();
    private $env_names = array();
    private $day = false;

    public function __construct($param)
    {
        $this->db = $param['db'];
        $this->CI = & get_instance();
        $this->CI->load->config("texture");
        $this->texture = config_item("texture");
        $this->areas = array();
        $this->museum_id = $param["mid"];
        $this->getArea();

//        $this->btime = strtotime('-1 day 00:00:00');
//        $this->etime = strtotime('-1 day 23:59:59');
//        $this->getEnvNo();
//        $this->getHumidityEnvNo();
//        $this->getLightEnvNo();

    }

    private function getArea(){
        $ty = array("展厅", "展柜", "库房");
        $areas = $this->db["base"]->select("env_no,name,type")->where_in("type", $ty)->get("env")->result_array();
        $texture = $this->texture["zgkf"];
        $hhs_arr = $this->texture["hh"];
        $hhs = array();
        foreach ($hhs_arr as $k => $h){
            foreach ($h as $k1 => $p){
                $hhs[$k1] = $k;
            }
        }
        $other = array("humidity"=>3,"light"=>6);
        foreach ($areas as $key => $v){
            $this->env_names[$v["env_no"]] = $v["name"];
            if($areas[$key]["type"] == "展厅"){
                $this->hall[] = $v["env_no"];
            }elseif ($areas[$key]["type"] == "展柜"){
                $this->showcase[] = $v["env_no"];
            }elseif ($areas[$key]["type"] == "库房"){
                $this->storeroom[] = $v["env_no"];
            }

            $data = array();
            //根据文物找材质类型
            $relic = $this->db["relic"]->select("material")->where("parent_env_no",$v["env_no"])->get("relic")->result_array();

            foreach ($relic as $r){
                if($r["material"]){
                    foreach ($texture as $k => $t){
                        foreach ($t as $k1 => $t1){
                            if(!empty($t1)){
                                if(in_array($r["material"],$t1)){
                                    $data["material_".$k1][] = $k;
                                }
                            }
                        }
                    }

                }

            }
            foreach ($other as $k => $o){
                $data["material_".$k] = (array_key_exists("material_".$k,$data) && is_array($data["material_".$k]) && $data["material_".$k])?$data["material_".$k]:array($o);
                $unique = array_unique($data["material_".$k]);
                if(is_array($unique)){
                    if(sizeof($unique) > 1){
                        $this->texture_no[$hhs[$k]][] = $v["env_no"];
                    }else{
                        $this->texture_no[$unique[0]][] = $v["env_no"];
                    }
                }
            }
        }

        $this->areas = $areas;
    }

    public function data_envtype_param(){
        $rs = array();
        $end_time = time();
        $this->day = date("Y年m月d日",$end_time);
        $start_time = $end_time - 24 * 60 * 60;
        $data_day = $this->data_envtype($start_time, $end_time,"D".date("Ymd",$end_time));//天
        $rs = array_merge($rs,$data_day);
        
        $this->day = false;
        $day_num = date("w");
        $start_time = strtotime("-".($day_num-1)." day");
        $end_time = strtotime("+".(7-$day_num)." day");
        $data_week = $this->data_envtype($start_time, $end_time,"W".date("Y").date("W"));//周
        $rs = array_merge($rs,$data_week);

        $this->day = false;
        $start_time = strtotime(date("Y-m-")."01");
        $end_time = strtotime(date("Y-m-").date("t"));
        $data_month = $this->data_envtype($start_time, $end_time,"M".date("Y").date("m"));//月
        $rs = array_merge($rs,$data_month);
        return $rs;
    }

    private function data_envtype($start,$end,$date){
        $all = array();
        //$now_time = strtotime("2016-01-05");//time();
        $all_zt = $this->db["env"]
            ->select("alert_param,temperature,humidity,light,uv,voc,env_no,equip_no,equip_time") //展厅，算全部参数
            ->where("equip_time >", $start)
            ->where("equip_time <", $end)
            ->where_in("env_no",$this->hall)
            ->get("data_sensor")
            ->result_array();
        $param_arr = $this->texture["common"];
        foreach ($this->texture["zt"] as $k=>$zt){
            $param_arr[$k] = $zt;
        }
        $data_all = $this->deal_param($param_arr, $all_zt,"展厅",$date);
        $all = array_merge($all,$data_all);
        //print_r($data_all);exit;
        $common_zg = $this->db["env"]->select("alert_param,temperature,humidity,light,uv,voc,env_no,equip_no,equip_time") //展柜,算common数据
            ->where_in("env_no",$this->showcase)
            ->where("equip_time >", $start)
            ->where("equip_time <", $end)
            ->get("data_sensor")
            ->result_array();
        $data_common_zg = $this->deal_param($this->texture["common"], $common_zg,"展柜",$date);
        $all = array_merge($all,$data_common_zg);
        //print_r($data_common_zg);exit;

        $common_kf = $this->db["env"]->select("alert_param,temperature,humidity,light,uv,voc,env_no,equip_no,equip_time") //库房,算common数据
        ->where_in("env_no",$this->storeroom)
            ->where("equip_time >", $start)
            ->where("equip_time <", $end)
            ->get("data_sensor")
            ->result_array();
        $data_common_kf = $this->deal_param($this->texture["common"], $common_kf,"库房",$date);
        $all = array_merge($all,$data_common_kf);
        foreach ($this->texture_no as $k => $nums){
            $same_arr = array_values(array_intersect($nums,$this->showcase)); //与展柜交集
            $arr = array($k=>$this->texture["zgkf"][$k]);
            if(!empty($same_arr)){
                $texture_zt = $this->db["env"]->select("alert_param,temperature,humidity,light,uv,voc,env_no,equip_no,equip_time") //展柜,算材质数据
                ->where_in("env_no",$same_arr)
                    ->where("equip_time >", $start)
                    ->where("equip_time <", $end)
                    ->get("data_sensor")
                    ->result_array();
                $data_texture_zt =  $this->deal_param($arr, $texture_zt,"展柜",$date);
                $all = array_merge($all,$data_texture_zt);
            }

            $same_arr = array_values(array_intersect($nums,$this->storeroom)); //与库房交集
            if(!empty($same_arr)){
                $texture_kf = $this->db["env"]->select("alert_param,temperature,humidity,light,uv,voc,env_no,equip_no,equip_time") //库房,算材质数据
                ->where_in("env_no",$same_arr)
                    ->where("equip_time >", $start)
                    ->where("equip_time <", $end)
                    ->get("data_sensor")
                    ->result_array();
                $data_texture_kf =  $this->deal_param($arr, $texture_kf,"库房",$date);
                $all = array_merge($all,$data_texture_kf);
            }
        }
        return $all;
    }

    private function deal_param($arr,$data,$ty,$date){
        $temperature = $uv = $voc = $humidity = $light = array();
        $alerts = array(
            "temperature"=>0,
            "uv"=>0,
            "voc"=>0,
            "humidity"=>0,
            "light"=>0
        );
        $temperature_areano = $uv_areano = $voc_areano = $humidity_areano = $light_areano = array();
        $rs = array();
        foreach ($data as $value){
            $equip_id = 0;
            $time = 0;
            if(array_key_exists("equip_no",$value)){
                $equip_id = $value["equip_no"];
            }
            if(array_key_exists("equip_time",$value)){
                $time = $value["equip_time"];
            }

                foreach ($arr as $k=>$p){
                    foreach ($p as $k1=>$p1){

                        if($k1 == "temperature" && array_key_exists("temperature",$value) && $value[$k1]){
                            $temperature[] = $value[$k1];
                            if(array_key_exists("env_no",$value)){
                                $temperature_areano[$value["env_no"]][] = array("data"=>$value[$k1],"equip_id"=>$equip_id,"time"=>$time);
                            }
                        }elseif ($k1 == "uv" && array_key_exists("uv",$value) && $value[$k1]){
                            $uv[] = $value[$k1];
                            if(array_key_exists("env_no",$value)){
                                $uv_areano[$value["env_no"]][] = array("data"=>$value[$k1],"equip_id"=>$equip_id,"time"=>$time);
                            }
                        }elseif ($k1 == "voc" && array_key_exists("voc",$value) && $value[$k1]){
                            $voc[] = $value[$k1];
                            if(array_key_exists("env_no",$value)){
                                $voc_areano[$value["env_no"]][] = array("data"=>$value[$k1],"equip_id"=>$equip_id,"time"=>$time);
                            }
                        }elseif ($k1 == "humidity" && array_key_exists("humidity",$value) && $value[$k1]){
                            $humidity[] = $value[$k1];
                            if(array_key_exists("env_no",$value)){
                                $humidity_areano[$value["env_no"]][] = array("data"=>$value[$k1],"equip_id"=>$equip_id,"time"=>$time);
                            }
                        }elseif ($k1 == "light" && array_key_exists("light",$value) && $value[$k1]){
                            $light[] = $value[$k1];
                            if(array_key_exists("env_no",$value)){
                                $light_areano[$value["env_no"]][] = array("data"=>$value[$k1],"equip_id"=>$equip_id,"time"=>$time);
                            }
                        }
                    }
                }


            if (array_key_exists("alert_param",$value) && $value["alert_param"]){
                $alerts_arr = explode(",",$value["alert_param"]);
                foreach ($alerts_arr as $v){
                    if(array_key_exists($v,$alerts)){
                        $alerts[$v] ++;
                    }
                }
            }
        }

        foreach ($arr as $k=>$p){
            foreach ($p as $k1=>$p1){
                if($k1 == "temperature" && !empty($temperature)){
                    $rs[] = $this->calculate($temperature,$k,$ty,$date,$temperature_areano,$alerts[$k1],$k1);
                }elseif ($k1 == "uv" && !empty($uv)){
                    $rs[] = $this->calculate($uv,$k,$ty,$date,$uv_areano,$alerts[$k1],$k1);
                }elseif ($k1 == "voc" && !empty($voc)){
                    $rs[] = $this->calculate($voc,$k,$ty,$date,$voc_areano,$alerts[$k1],$k1);
                }elseif ($k1 == "humidity" && !empty($humidity)){
                    $rs[] = $this->calculate($humidity,$k,$ty,$date,$humidity_areano,$alerts[$k1],$k1);
                }elseif ($k1 == "light" && !empty($light)){
                    $rs[] = $this->calculate($light,$k,$ty,$date,$light_areano,$alerts[$k1],$k1);
                }
            }
        }
        return $rs;
    }

    private function calculate($arr,$param,$ty,$date,$arr_areano,$alerts_no,$p){
        $data = array(
            "env_type"=>$ty,
            "param"=>$param,
            "mid"=>$this->museum_id,
            "date"=>$date,
            "max"=>max($arr),
            "min"=>min($arr)
        );
        $data["abnormal"] = array();//异常数据
        $data["wave_arr"] = array();//日波动超标数据
        $average = round(array_sum($arr)/sizeof($arr),2);
        $sum = 0;
        foreach ($arr as $k =>$v){
            $sum += pow($v - $average,2);
        }
        $standard = sqrt($sum/sizeof($arr));//标准差
        asort($arr);
        if(sizeof($arr)%2 == 0){ //中位值
            $end = intval(sizeof($arr)/2);
            $flag = $arr[$end-1] + $arr[$end];
            $data["middle"] = round($flag/2,2);
        }else{
            $data["middle"] = $arr[intval((sizeof($arr)+1)/2)-1];
        }
        $data["average"] = $average;
        $data["standard"] = round($standard,2);
        $abnormal = 0;
        $range = $range_normal = array();
        $range_areano = $range_normal_areano = array();
        $area_no_normal = array();
        foreach ($arr_areano as $area_no => $value){
            $datas = array();
            foreach ($value as $v){
                $datas[] = $v["data"];
                $z = $data["standard"]?($v["data"] - $data["average"]) / $data["standard"]:0;
                if(abs($z) > 3){
                    $abnormal++; //异常值个数
                    $data["abnormal"][] = array(
                        "date"=>date("Y年n月j日",$v["time"]),
                        "mid"=>$this->museum_id,
                        "equip_no"=>$v["equip_id"],
                        "val"=>$v["data"],
                        "time"=>date("H:i:s",$v["time"]),
                    );
                }else{
                    $area_no_normal[$area_no][] = $v["data"];
                }
            }
            $range[] = $range_areano[$area_no][] = max($datas) - min($datas);
        }
        foreach ($area_no_normal as $area_no => $value){
            $range_normal[] = $range_normal_areano[$area_no][] = max($value) - min($value);
        }
        $min_range = min($range);
        $max_range = max($range);
        $min_range_normal = min($range_normal);
        $max_range_normal = max($range_normal);
        $data["wave"] = $min_range.",".$max_range.",".$min_range_normal.",".$max_range_normal;
        $num = $num_normal = 0;
        if($p == "temperature"){
            if($min_range>=4){
                $num += pow(2,3);
            }
            if($max_range>=4){
                $num += pow(2,2);
            }
            if($min_range_normal>=4){
                $num += pow(2,1);
            }
            if($max_range_normal>=4){
                $num += pow(2,0);
            }
            if($this->day){
                foreach ($range_areano as $areano =>$value){
                    foreach ($value as $v){
                        if($v>=4){
                            $data["wave_arr"][] = array(
                                "date"=>$this->day,
                                "mid"=>$this->museum_id,
                                "type"=>0,
                                "val"=>$v,
                                "env_name"=>$this->env_names[$areano]
                            );
                        }
                    }
                }
                foreach ($range_normal_areano as $areano =>$value){
                    foreach ($value as $v){
                        if($v>=4){
                            $data["wave_arr"][] = array(
                                "date"=>$this->day,
                                "mid"=>$this->museum_id,
                                "type"=>1,
                                "val"=>$v,
                                "env_name"=>$this->env_names[$areano]
                            );
                        }
                    }
                }
            }
        }elseif ($p == "humidity"){
            if($min_range>=5){
                $num_normal += pow(2,3);
            }
            if($max_range>=5){
                $num_normal += pow(2,2);
            }
            if($min_range_normal>=5){
                $num_normal += pow(2,1);
            }
            if($max_range_normal>=5){
                $num_normal += pow(2,0);
            }
            if($this->day) {
                foreach ($range_areano as $areano => $value) {
                    foreach ($value as $v) {
                        if ($v >= 5) {
                            $data["wave_arr"][] = array(
                                "date" =>$this->day,
                                "mid" => $this->museum_id,
                                "type" => 0,
                                "val" => $v,
                                "env_name" => $this->env_names[$areano]
                            );
                        }
                    }
                }
                foreach ($range_normal_areano as $areano => $value) {
                    foreach ($value as $v) {
                        if ($v >= 5) {
                            $data["wave_arr"][] = array(
                                "date" =>$this->day,
                                "mid" => $this->museum_id,
                                "type" => 1,
                                "val" => $v,
                                "env_name" => $this->env_names[$areano]
                            );
                        }
                    }
                }
            }
        }
        $data["wave_status"] = $num?$num:($num_normal?$num_normal:0);
        $data["count_abnormal"] = $abnormal;
        $data["compliance"] = round((sizeof($arr) - $alerts_no)/sizeof($arr),2);
        return $data;
    }


}