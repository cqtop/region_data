<?php 
defined('BASEPATH') OR exit('No direct script access allowed');
require_once("MY_library.php");
class Mongo_api extends MY_library{
    private $mongo_db = null;
    public function __construct($param)
    {
        parent::__construct();
        require_once("Mongo_db.php");
        $this->mongo_db = new Mongo_db($param['db']);
        $this->museum_id = $param["mid"];
        if($param["date"]){
            $this->date = $param["date"];
        }
        $this->getArea();
        $this->getEnvNo();
    }

    private function getArea(){
        $ty = array("展厅", "展柜", "库房");
        $ids = $ty_arr = array();
        $tyid = $this->mongo_db->select(array("_id","name"))->where_in("name", $ty)->get("area.type");
        foreach ($tyid as $v) {
            $ids[] = $v["_id"];
            $ty_arr["'".$v["_id"]->{'$id'}."'"] = $v["name"];
        }
        $areas = $this->mongo_db->select(array("No","_id","name","type","threshold","belong"))->where_in("type", $ids)->get("area.base");
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
            $this->env_names[$v["No"]] = $v["name"];
            $areas[$key]["type"] = $ty_arr["'".$v["type"]->{'$id'}."'"];
            if($areas[$key]["type"] == "展厅"){
                $this->hall[] = $v["No"];
            }elseif ($areas[$key]["type"] == "展柜"){
                $this->showcase[] = $v["No"];
            }elseif ($areas[$key]["type"] == "库房"){
                $this->storeroom[] = $v["No"];
            }

            $data = array();
            //根据文物找材质类型
            $relic = $this->mongo_db->select(array("type"))->where(array("place"=>$v["_id"]))->get("relic.base");

            foreach ($relic as $r){
                if($r["type"]){
                    foreach ($texture as $k => $t){
                        foreach ($t as $k1 => $t1){
                            if(!empty($t1)){
                                if(in_array($r["type"],$t1)){
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
                        $this->texture_no[$hhs[$k]][] = $v["No"];
                    }else{
                        $this->texture_no[$unique[0]][] = $v["No"];
                    }
                }
            }
        }
        $this->areas = $areas;
    }


    //统计函数-获取博物馆展厅/展柜/库房的环境编号
    public function getEnvNo(){
        $_ids = array();
        foreach(array(1=>"展厅",2=>"展柜",3=>"库房") as $k=>$v){
            $_ids = $this->mongo_db->select(array("_id"))->where(array("name"=>$v))->getOne("area.type");
            $area_nos =  $this->mongo_db->select(array("No"))->where(array("type"=>$_ids['_id']))->get("area.base");

            $this->EnvNo[$k] = array_values(array_unique(array_column($area_nos,"No")));
        }
    }


    //博物馆基础数据-馆藏文物数量
    public function count_relic()
    {
        return $this->mongo_db->count("relic.base");
    }
    //博物馆基础数据-珍贵文物数量
    public function count_precious_relic()
    {
        return $this->mongo_db->where_in("level",array("一级","二级","三级"))->count("relic.base");
    }
    //博物馆基础数据-展柜数量
    public function count_showcase()
    {
        $showcase_id = $this->mongo_db->select(array("_id"))->where(array("name"=>"展柜"))->getOne("area.type");
        return $this->mongo_db->where(array("type"=>$showcase_id['_id']))->count('area.base');
    }

    //博物馆综合统计
    public function count_data_complex($date,$env_id){
        $this->date_conversion($date);
        $env_type = array(1=>"展厅", 2=>"展柜", 3=>"库房");
        $data = array();
        if(!$this->EnvNo[$env_id]) return false; //不存在该环境类型

        $data['date'] = $this->date_str;
        $data['env_type'] = $env_type[$env_id];
        $data['mid'] = $this->museum_id;
        $data['scatter_temperature'] = $this->count_scatter($env_id,'temperature');
        $data['scatter_humidity'] = $this->count_scatter($env_id,'humidity');
        $data['scatter_light'] = $this->count_scatter($env_id,"light");
        $data['scatter_uv'] = $this->count_scatter($env_id,"uv");
        $data['scatter_voc'] = $this->count_scatter($env_id,"voc");

        //各环境达标和未达标总和
        if($date == "yesterday") { //天数据
            $ta_datas = $this->count_total_abnormal($env_id);
            if($ta_datas){
                foreach($ta_datas as $param => $v){
                    $data[$param."_total"] = $v['total'];
                    $data[$param."_abnormal"] = $v['abnormal'];
                }
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
        $datas = $this->mongo_db
            ->select(array("param"))
            ->where_between("receivetime",$this->btime,$this->etime)
            ->where_in("areano",$this->EnvNo[$env_id])
            ->get("data.sensor.2016");
        if(empty($datas)) return null;
        $list = array_column(array_column($datas,"param"),$type);//一维数据列表
        if(empty($list)) return null; //无对应环境参数数据
        $avg = array_sum($list)/count($list);//平均值
        $sd = $this->getStandardDeviation($avg,$list); //标准差

        return round($sd/$avg,3);
    }
    //博物馆综合统计-各参数达标总和未达标总和-天数据
    public function count_total_abnormal($env_id){
        $env_param = array("temperature","humidity","light","uv","voc");
        $alldatas =  $this->mongo_db
            ->select(array(),array(
                "_id",
                "voltage",
                "instruct",
                "version",
                "sensorno",
                "sendidtip",
                "ip",
                "time",
                "size",
                "socketstr",
                "parsertime",
                "rssi",
                "exestarttime",
                "equip_id",
                "relicno",
                "exeendtime",
                "pid"))
            ->where_between("receivetime",$this->btime,$this->etime)
            ->where_in("areano",$this->EnvNo[$env_id])
            ->get("data.sensor.2016");
        if(!$alldatas) return false;
        foreach($env_param as $param){
            $normal = $abnormal = array();
            foreach($alldatas as $data){
                if(isset($data['param'][$param])){
                    if(isset($data['alerts']) && in_array($param,$data['alerts'])){ //存在告警字段和对应的告警参数
                        $abnormal[] = $data['param'][$param];
                    }else{
                        $normal[] = $data['param'][$param];
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

    private function getData($area_no,$start,$end){
        $data = $this->mongo_db->select(array("alerts","param","areano","equip_id","receivetime")) //展厅，算全部参数
        ->where_in("areano",$area_no)
            ->where_between("receivetime", $start, $end)
            ->get("data.sensor.".date("Y"));
        return $data;
    }

    private function allData($source = array(),$start, $end){
        $arrs = array_chunk($source,50);
        $all = array();
        foreach ($arrs as $arr){
            $data = $this->getData($arr, $start, $end);
            $all = array_merge($all,$data);
        }
        return $all;
    }

    private function data_envtype($start,$end,$date){

        $all = array();
        //$now_time = strtotime("2016-01-05");//time();
        $all_zt = $this->allData($this->hall, $start, $end);
        $param_arr = $this->texture["common"];
        foreach ($this->texture["zt"] as $k=>$zt){
            $param_arr[$k] = $zt;
        }
        $data_all = $this->deal_param($param_arr, $all_zt,"展厅",$date);
        $all = array_merge($all,$data_all);
        //print_r($data_all);exit;

        $common_zg = $this->allData($this->showcase, $start, $end);
        $data_common_zg = $this->deal_param($this->texture["common"], $common_zg,"展柜",$date);
        $all = array_merge($all,$data_common_zg);
        //print_r($data_common_zg);exit;

        $common_kf = $this->allData($this->storeroom, $start, $end);
        $data_common_kf = $this->deal_param($this->texture["common"], $common_kf,"库房",$date);
        $all = array_merge($all,$data_common_kf);
        foreach ($this->texture_no as $k => $nums){
            $same_arr = array_values(array_intersect($nums,$this->showcase)); //与展柜交集
            $arr = array($k=>$this->texture["zgkf"][$k]);
            if(!empty($same_arr)){
                $texture_zt = $this->allData($same_arr, $start, $end);
                $data_texture_zt =  $this->deal_param($arr, $texture_zt,"展柜",$date);
                $all = array_merge($all,$data_texture_zt);
            }

            $same_arr = array_values(array_intersect($nums,$this->storeroom)); //与库房交集
            if(!empty($same_arr)){
                $texture_kf = $this->allData($same_arr, $start, $end);
                $data_texture_kf =  $this->deal_param($arr, $texture_kf,"库房",$date);
                $all = array_merge($all,$data_texture_kf);
            }
        }
        return $all;
    }


    private function deal_param($arr,$data,$ty,$date){
        $rs = array();

        foreach ($arr as $k=>$p){
            foreach ($p as $k1=>$p1){
                $param_data = array();
                $alert = 0;

                foreach ($data as $value){
                    $equip_id = 0;
                    $time = 0;
                    if(array_key_exists("equip_id",$value)){
                        $equip_id = $value["equip_id"];
                    }
                    if(array_key_exists("receivetime",$value)){
                        $time = $value["receivetime"];
                    }
                    if (array_key_exists("param",$value) && array_key_exists($k1,$value["param"])){
                        if(array_key_exists("areano",$value)){
                            $param_data[$value["areano"]][] = array("data"=>$value["param"][$k1],"equip_id"=>$equip_id,"time"=>$time);
                        }
                    }

                    if (array_key_exists("alerts",$value) && !empty($value["alerts"])){
                        foreach ($value["alerts"] as $v){
                            if(array_key_exists("parameter",$v) && $v["parameter"] == $k1){
                                $alert ++;
                            }
                        }
                    }
                }

                if($param_data){
                    $rs[] = $this->calculate($k,$ty,$date,$param_data,$alert,$k1);
                }
            }
        }


        return $rs;
    }

    private function deal_param1($arr,$data,$ty,$date){
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
            if(array_key_exists("equip_id",$value)){
                $equip_id = $value["equip_id"];
            }
            if(array_key_exists("receivetime",$value)){
                $time = $value["receivetime"];
            }
            if (array_key_exists("param",$value)){
                foreach ($arr as $k=>$p){
                    foreach ($p as $k1=>$p1){

                        if($k1 == "temperature" && array_key_exists("temperature",$value["param"])){
                            //$temperature[] = $value["param"][$k1];
                            if(array_key_exists("areano",$value)){
                                $temperature_areano[$value["areano"]][] = array("data"=>$value["param"][$k1],"equip_id"=>$equip_id,"time"=>$time);
                            }
                        }elseif ($k1 == "uv" && array_key_exists("uv",$value["param"])){
                            //$uv[] = $value["param"][$k1];
                            if(array_key_exists("areano",$value)){
                                $uv_areano[$value["areano"]][] = array("data"=>$value["param"][$k1],"equip_id"=>$equip_id,"time"=>$time);
                            }
                        }elseif ($k1 == "voc" && array_key_exists("voc",$value["param"])){
                            //$voc[] = $value["param"][$k1];
                            if(array_key_exists("areano",$value)){
                                $voc_areano[$value["areano"]][] = array("data"=>$value["param"][$k1],"equip_id"=>$equip_id,"time"=>$time);
                            }
                        }elseif ($k1 == "humidity" && array_key_exists("humidity",$value["param"])){
                            //$humidity[] = $value["param"][$k1];
                            if(array_key_exists("areano",$value)){
                                $humidity_areano[$value["areano"]][] = array("data"=>$value["param"][$k1],"equip_id"=>$equip_id,"time"=>$time);
                            }
                        }elseif ($k1 == "light" && array_key_exists("light",$value["param"])){
                            //$light[] = $value["param"][$k1];
                            if(array_key_exists("areano",$value)){
                                $light_areano[$value["areano"]][] = array("data"=>$value["param"][$k1],"equip_id"=>$equip_id,"time"=>$time);
                            }
                        }
                    }
                }
            }

            if (array_key_exists("alerts",$value) && !empty($value["alerts"])){
                foreach ($value["alerts"] as $v){
                    if(array_key_exists("parameter",$v) && $v["parameter"] && array_key_exists($v["parameter"],$alerts)){
                        $alerts[$v["parameter"]] ++;
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

}
