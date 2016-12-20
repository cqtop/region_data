<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once("MY_library.php");
class Mysql_api extends MY_library{
    private $db = null;
    public function __construct($param)
    {
        parent::__construct();
        $this->db = $param['db'];
        $this->museum_id = $param["mid"];
        if($param["date"]){
            $this->date = $param["date"];
        }
        $this->getArea();
        $this->getEnvNo();

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


    //统计函数-获取博物馆展厅/展柜/库房的环境编号
    public function getEnvNo(){
        $env_type = array(1=>"展厅", 2=>"展柜", 3=>"库房");
        foreach($env_type as $k=>$v){
            $envno_arr = $this->db['base']
                ->select("distinct(env_no)")
                ->where("env_no<>","null")
                ->where("type",$v)
                ->get("env")
                ->result_array();
            $this->EnvNo[$k] = array_column($envno_arr,"env_no");
        }
    }


    //博物馆基础数据-馆藏文物数量
    public function count_relic(){
        return $this->db['relic']->count_all_results('relic');
    }
    //博物馆基础数据-珍贵文物数量
    public function count_precious_relic(){
        return $this->db['relic']
            ->where_in("level",array("一级文物","二级文物","三级文物"))
            ->count_all_results('relic');
    }
    //博物馆基础数据-展柜数量
    public function count_cabinet(){
        return $this->db['base']->where("type","展柜")->count_all_results("env");
    }
    //博物馆基础数据-展厅数量
    public function count_hall(){
        return $this->db['base']->where("type","展厅")->count_all_results("env");
    }
    //博物馆基础数据-库房数量
    public function count_storeroom(){
        return $this->db['base']->where("type","库房")->count_all_results("env");
    }

    //博物馆综合统计-基于环境
    public function count_data_complex_env($date,$env_id){
        $this->date_conversion($date);
        $env_type = array(1=>"展厅", 2=>"展柜", 3=>"库房");
        $data = array();
        if(!$this->EnvNo[$env_id]) return false; //不存在对应的环境类型(hall/cabinet/storeroom)
        foreach($this->EnvNo[$env_id] as $env_no){ //遍历对应环境类型的环境编号
            $data[] = array(
                "date"=>$this->date_str,
                "env_no"=>$env_no,
                "env_type"=>$env_type[$env_id],
                "mid"=>$this->museum_id,
                "scatter_temperature"=>$this->count_scatter_env($env_no,"temperature"),
                "scatter_humidity"=>$this->count_scatter_env($env_no,"humidity"),
                "scatter_light"=>$this->count_scatter_env($env_no,"light"),
                "scatter_uv"=>$this->count_scatter_env($env_no,"uv"),
                "scatter_voc"=>$this->count_scatter_env($env_no,"voc"),
                "temperature_total"=>$this->count_number($date,"temperature","total",$env_no),
                "temperature_abnormal"=>$this->count_number($date,"temperature","abnormal",$env_no),
                "humidity_total"=>$this->count_number($date,"humidity","total",$env_no),
                "humidity_abnormal"=>$this->count_number($date,"humidity","abnormal",$env_no),
                "light_total"=>$this->count_number($date,"light","total",$env_no),
                "light_abnormal"=>$this->count_number($date,"light","abnormal",$env_no),
                "uv_total"=>$this->count_number($date,"uv","total",$env_no),
                "uv_abnormal"=>$this->count_number($date,"uv","abnormal",$env_no),
                "voc_total"=>$this->count_number($date,"voc","total",$env_no),
                "voc_abnormal"=>$this->count_number($date,"voc","abnormal",$env_no),
            );
        }

        return $data;
    }

    //博物馆综合统计-离散系数-基于单个环境编号
    public function count_scatter_env($env_no,$type){
        $Arr = $this->db['env']
            ->select($type)
            ->where("equip_time>", $this->btime)
            ->where("equip_time<",$this->etime)
            ->where("$type<>","null")
            ->where("env_no",$env_no)
            ->get("data_sensor")
            ->result_array();
        if(!$Arr) return 0;
        $list = array_column($Arr,$type);//转为一维
        $avg = array_sum($list)/count($list);//平均值
        if(!$avg) return 0;
        $sd = $this->getStandardDeviation($avg,$list); //标准差

        return round($sd/$avg,4);//离散系数
    }

    //博物馆综合统计-数据达标和未达标数量统计-基于环境
    public function count_number($date,$param,$type,$env_no){
        if($date == "yesterday"){//天统计-计算原始表
            $alldatas =  $this->db['env']
                ->select($param.",alert_param")
                ->where("equip_time>", $this->btime)
                ->where("equip_time<",$this->etime)
                ->where("env_no",$env_no)
                ->get("data_sensor")
                ->result_array();
            $normal = $abnormal = array();
            foreach($alldatas as $data){
                if($data[$param]){
                    if(strpos($data['alert_param'], $param) !== false){ //告警字段中存在告警参数
                        $abnormal[] = $data[$param];
                    } else {
                        $normal[] = $data[$param];
                    }
                }
            }
            $ret["total"] = count($normal)+count($abnormal);
            $ret["abnormal"] = count($abnormal);
            return $ret[$type];
        }else{ //周/月统计-累加统计表的天数据
            $alldatas = $this->CI->db
                ->select("SUM({$param}_{$type}) as number")
                ->where("env_no",$env_no)
                ->where("mid",$this->museum_id)
                ->where_in("date",$this->_date_list($this->date_start,$this->date_end))
                ->group_by("env_no")
                ->get("data_complex_env")
                ->result_array();
            if(!$alldatas) return 0;
            return $alldatas[0]['number'];
        }
    }


    //博物馆综合统计
    public function count_data_complex($date,$env_id){
        $this->date_conversion($date);
        $env_type = array(1=>"展厅", 2=>"展柜", 3=>"库房");
        $data = array();
        if(!$this->EnvNo[$env_id]) return false;

        $data['date'] = $this->date_str;
        $data['env_type'] = $env_type[$env_id];
        $data['mid'] = $this->museum_id;
        $data['scatter_temperature'] = $this->count_scatter($env_id,'temperature');
        $data['scatter_humidity'] = $this->count_scatter($env_id,'humidity');
        $data['scatter_light'] = $this->count_scatter($env_id,"light");
        $data['scatter_uv'] = $this->count_scatter($env_id,"uv");
        $data['scatter_voc'] = $this->count_scatter($env_id,"voc");
        //各种环境参数达标和未达标总和
        if($date == "yesterday") { //天数据
            $ta_datas = $this->count_total_abnormal($env_id);
            foreach($ta_datas as $param => $v){
                $data[$param."_total"] = $v['total'];
                $data[$param."_abnormal"] = $v['abnormal'];
            }
        } else { // 周/月数据统计
            $ta_datas = $this->count_total_abnormal_2($env_id);
            if($ta_datas) $data = array_merge($data,$ta_datas);
        }

        return $data;
    }

    //博物馆综合统计-离散系数
    public function count_scatter($env_id,$type)
    {
        $Arr = $this->db['env']
            ->select($type)
            ->where("equip_time>", $this->btime)
            ->where("equip_time<",$this->etime)
            ->where("$type<>","null")
            ->where_in("env_no",$this->EnvNo[$env_id])
            ->get("data_sensor")
            ->result_array();

        if(!$Arr) return 0;
        $list = array_column($Arr,$type);//转为一维
        $avg = array_sum($list)/count($list);//平均值
        if(!$avg) return 0;
        $sd = $this->getStandardDeviation($avg,$list); //标准差

        return round($sd/$avg,4);//离散系数
    }
    //博物馆综合统计-各环境参数达标总和未达标总和-天数据
    public function count_total_abnormal($env_id){
        $env_param = array("temperature","humidity","light","uv","voc");
        $alldatas =  $this->db['env']
            ->select("temperature,humidity,light,uv,voc,alert_param")
            ->where("equip_time>", $this->btime)
            ->where("equip_time<",$this->etime)
            ->where_in("env_no",$this->EnvNo[$env_id])
            ->get("data_sensor")->result_array();
        //if(!$alldatas) return false;
        foreach($env_param as $param){
            $normal = $abnormal = array();
            foreach($alldatas as $data){
                if($data[$param]){
                    if(strpos($data['alert_param'], $param) !== false){ //告警字段中存在告警参数
                        $abnormal[] = $data[$param];
                    } else {
                        $normal[] = $data[$param];
                    }
                }
            }
            $ret[$param]["total"] = count($normal)+count($abnormal);
            $ret[$param]["abnormal"] = count($abnormal);
        }

        return $ret;
    }
    //博物馆综合统计2-各环境参数达标总和未达标总和-周/月数据(累加天数据)
    public function count_total_abnormal_2($env_id){
        $env_param = array("temperature","humidity","light","uv","voc");
        $env_type = array(1=>"展厅", 2=>"展柜", 3=>"库房");

        $sumstr = '';
        foreach($env_param as $v){
            $sumstr .= ",SUM({$v}_total) as {$v}_total,SUM({$v}_abnormal) as {$v}_abnormal";
        }
        $sumstr = substr($sumstr,1);
        $alldatas = $this->CI->db
            ->select($sumstr)
            ->where("env_type",$env_type[$env_id])
            ->where("mid",$this->museum_id)
            ->where_in("date",$this->_date_list($this->date_start,$this->date_end))
            ->group_by("mid")
            ->get("data_complex")
            ->result_array();
        if(!$alldatas) return array();
        return $alldatas[0];
    }


    public function data_envtype_param(){
        $rs = array();
        $start_time = $this->date?strtotime($this->date):strtotime(date("Y-m-d",strtotime("-1 day")));
        $end_time = $start_time + 24*60*60;
        $this->day = date("Y年m月d日",$start_time);
        $data_day = $this->data_envtype($start_time, $end_time,"D".date("Ymd",$start_time));//天
        $rs = array_merge($rs,$data_day);

        $this->day = false;
        $day_num = date("w");
        $start_time = strtotime(date("Y-m-d",strtotime("-".($day_num-1)." day")));
        $end_time = $start_time + 24*60*60*7;
        $data_week = $this->data_envtype($start_time, $end_time,"W".date("YW"));//周
        $rs = array_merge($rs,$data_week);

        $this->day = false;
        $start_time = strtotime(date("Y-m-")."01");
        $end_time = strtotime(date("Y-m-").date("t"));
        $data_month = $this->data_envtype($start_time, $end_time,"M".date("Ym"));//月
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
        //$temperature = $uv = $voc = $humidity = $light = array();
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

                    if($k1 == "temperature" && array_key_exists("temperature",$value) && $value[$k1] !== null ){
                        //$temperature[] = $value[$k1];
                        if(array_key_exists("env_no",$value)){
                            $temperature_areano[$value["env_no"]][] = array("data"=>$value[$k1],"equip_id"=>$equip_id,"time"=>$time);
                        }
                    }elseif ($k1 == "uv" && array_key_exists("uv",$value) && $value[$k1] !== null ){
                        //$uv[] = $value[$k1];
                        if(array_key_exists("env_no",$value)){
                            $uv_areano[$value["env_no"]][] = array("data"=>$value[$k1],"equip_id"=>$equip_id,"time"=>$time);
                        }
                    }elseif ($k1 == "voc" && array_key_exists("voc",$value) && $value[$k1] !== null ){
                        //$voc[] = $value[$k1];
                        if(array_key_exists("env_no",$value)){
                            $voc_areano[$value["env_no"]][] = array("data"=>$value[$k1],"equip_id"=>$equip_id,"time"=>$time);
                        }
                    }elseif ($k1 == "humidity" && array_key_exists("humidity",$value) && $value[$k1] !== null ){
                        //$humidity[] = $value[$k1];
                        if(array_key_exists("env_no",$value)){
                            $humidity_areano[$value["env_no"]][] = array("data"=>$value[$k1],"equip_id"=>$equip_id,"time"=>$time);
                        }
                    }elseif ($k1 == "light" && array_key_exists("light",$value) && $value[$k1] !== null ){
                        //$light[] = $value[$k1];
                        if(array_key_exists("env_no",$value)){
                            $light_areano[$value["env_no"]][] = array("data"=>$value[$k1],"equip_id"=>$equip_id,"time"=>$time);
                        }
                    }
                }
            }


            if (array_key_exists("alert_param",$value) && $value["alert_param"]){
                $alerts_arr = explode(",",$value["alert_param"]);
                $alerts_arr = array_unique($alerts_arr);
                foreach ($alerts_arr as $v){
                    if(array_key_exists($v,$alerts)){
                        $alerts[$v] ++;
                    }
                }
            }
        }

        foreach ($arr as $k=>$p){
            foreach ($p as $k1=>$p1){
                if($k1 == "temperature" && !empty($temperature_areano)){
                    $rs[] = $this->calculate($k,$ty,$date,$temperature_areano,$alerts[$k1],$k1);
                }elseif ($k1 == "uv" && !empty($uv_areano)){
                    $rs[] = $this->calculate($k,$ty,$date,$uv_areano,$alerts[$k1],$k1);
                }elseif ($k1 == "voc" && !empty($voc_areano)){
                    $rs[] = $this->calculate($k,$ty,$date,$voc_areano,$alerts[$k1],$k1);
                }elseif ($k1 == "humidity" && !empty($humidity_areano)){
                    $rs[] = $this->calculate($k,$ty,$date,$humidity_areano,$alerts[$k1],$k1);
                }elseif ($k1 == "light" && !empty($light_areano)){
                    $rs[] = $this->calculate($k,$ty,$date,$light_areano,$alerts[$k1],$k1);
                }
            }
        }
        return $rs;
    }


    // 测试统计信息
    public function countmusem(){
        $daytime = strtotime($this->date);
        $monthtime1 = strtotime(date('Y-m-01', $daytime));
        $monthtime2 = strtotime(date('Y-m-01', strtotime('+1 month',$daytime)))-1;
        $data = array();
        $data['count_day'] = $this->db["env"]->where("equip_time > ", $daytime)->where("equip_time < ", $daytime+86400)->count_all_results("data_sensor");
        $data['count_month'] = $this->db["env"]->where("equip_time > ", $monthtime1)->where("equip_time < ", $monthtime2)->count_all_results("data_sensor");

        //环境统计
        $sql = "SELECT type, COUNT(*) AS c FROM env GROUP BY type";
        $env = $this->db['base']->query($sql)->result_array();
        $env = array_column($env, 'c', 'type');
        $areatype = array('show_cabinet'=>'展柜', 'hall'=>'展厅', 'storage'=>'库房');
        foreach ($areatype as $t=>$n) {
            $data[$t] = isset($env[$n]) ? $env[$n] : 0;
        }
        
        return $data;
    }

}
