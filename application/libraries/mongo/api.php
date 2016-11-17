<?php defined('BASEPATH') OR exit('No direct script access allowed');

class API{

    public $btime = null;//查询开始时间
    public $etime = null;//查询结束时间
    public $EnvNo = array();//环境编号(展厅/展柜/库房)

    private $texture_no = array();
    private $hall = array();
    private $showcase = array();
    private $storeroom = array();
    private $env_names = array();
    private $day = false;

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

        $this->btime = strtotime('-1 day 00:00:00');
        $this->etime = strtotime('-1 day 23:59:59');
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

    //统计函数-计算标准差
    public function getStandardDeviation($avg, $list)
    {
        $total_var = 0;
        foreach ($list as $lv){
            $total_var += pow( ($lv - $avg), 2 );
        }
        return sqrt( $total_var / (count($list) ) );
    }
    //统计函数-计算中位值
    public function getMiddleValue($list){
        sort($list);//升序排序
        $num = count($list);
        if($num%2 == 0){
            $middleValue = ($list[$num/2]+$list[($num/2)-1])/2;
        }else{
            $middleValue = $list[floor($num/2)];
        }
        return $middleValue;
    }
    //统计函数-计算异常值
    public function getAbnormalValue($list){
        $avg = array_sum($list)/count($list);
        $sd = $this->getStandardDeviation($avg,$list);
        foreach($list as $v){
            $Z = abs(($v-$avg)/$sd);
            if($Z>3) return true;
        }
        return false;
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
        //判断日期 转换对应时间戳
        switch ($date){
            case "yesterday": //昨天
                $this->btime = strtotime('-1 day 00:00:00');
                $this->etime = strtotime('-1 day 23:59:59');
                $date_str = "D".date("Ymd",$this->btime);
                break;
            case "week": //本周
                $this->btime = mktime(0,0,0,date('m'),date('d')-date('w')+1,date('y'));
                $this->etime = strtotime('-1 day 23:59:59');
                $date_str = "W".date("YW");
                break;
            case "month": //本月
                $this->btime = mktime(0,0,0,date('m'),1,date('y'));
                $this->etime = strtotime('-1 day 23:59:59');
                $date_str = "M".date("Ym");
                break;
        }

        $env_type = array(1=>"展厅", 2=>"展柜", 3=>"库房");
        $data = array();
        if(!$this->EnvNo[$env_id]) return false; //不存在该环境类型

        $data['date'] = $date_str;
        $data['env_type'] = $env_type[$env_id];
        $data['mid'] = $this->museum_id;
        $data['scatter_temperature'] = $this->count_scatter($env_id,'temperature');
        $data['scatter_humidity'] = $this->count_scatter($env_id,'humidity');

        $ta_datas = $this->count_total_abnormal($env_id);
        foreach($ta_datas as $param => $v){
            $data[$param."_total"] = $v['total'];
            $data[$param."_abnormal"] = $v['abnormal'];
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
        if(empty($datas))return NUll;
        $list = array_column(array_column($datas,"param"),$type);//一维数据列表
        $avg = array_sum($list)/count($list);//平均值
        $sd = $this->getStandardDeviation($avg,$list); //标准差

        return round($sd/$avg,3);
    }
    //博物馆综合统计-各参数达标总和未达标总和
    public function count_total_abnormal($env_id){
        $env_param = array("temperature","humidity","light","uv","voc");
        $alldatas =  $this->mongo_db
            ->where_between("receivetime",$this->btime,$this->etime)
            ->where_in("areano",$this->EnvNo[$env_id])
            ->get("data.sensor.2016");

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

    public function data_envtype_param(){
        $rs = array();
        $end_time = strtotime("2016-01-04");//time();
        $this->day = date("Y年m月d日",$end_time);
        $start_time = $end_time - 24 * 60 * 60;
        $data_day = $this->data_envtype($start_time, $end_time,"D".date("Ymd",$end_time));//天
        $rs = array_merge($rs,$data_day);
        $this->day = false;
        $day_num = date("w");
//        $start_time = strtotime("-".($day_num-1)." day");
//        $end_time = strtotime("+".(7-$day_num)." day");
        $start_time = $end_time - 24*60*60*($day_num-1);
        $end_time = $end_time + 24*60*60*(7-$day_num);
        $data_week = $this->data_envtype($start_time, $end_time,"W".date("Y").date("W"));//周
        $rs = array_merge($rs,$data_week);
        $this->day = false;
//        $start_time = strtotime(date("Y-m-")."01");
//        $end_time = strtotime(date("Y-m-").date("t"));
        $start_time = strtotime("2016-01-01");
        $end_time = strtotime("2016-01-31");
        $data_month = $this->data_envtype($start_time, $end_time,"M".date("Y").date("m"));//月
        $rs = array_merge($rs,$data_month);
        return $rs;
    }

    private function data_envtype($start,$end,$date){

        $all = array();
        //$now_time = strtotime("2016-01-05");//time();
        $all_zt = $this->mongo_db->select(array("alerts","param","areano","equip_id","receivetime")) //展厅，算全部参数
        ->where_in("areano",$this->hall)
            ->where_between("receivetime", $start, $end)
            ->get("data.sensor.".date("Y"));
        $param_arr = $this->texture["common"];
        foreach ($this->texture["zt"] as $k=>$zt){
            $param_arr[$k] = $zt;
        }
        $data_all = $this->deal_param($param_arr, $all_zt,"展厅",$date);
        $all = array_merge($all,$data_all);
        //print_r($data_all);exit;
        $common_zg = $this->mongo_db->select(array("alerts","param","areano","equip_id","receivetime")) //展柜,算common数据
        ->where_in("areano",$this->showcase)
            ->where_between("receivetime", $start, $end)
            ->get("data.sensor.".date("Y"));
        $data_common_zg = $this->deal_param($this->texture["common"], $common_zg,"展柜",$date);
        $all = array_merge($all,$data_common_zg);
        //print_r($data_common_zg);exit;

        $common_kf = $this->mongo_db->select(array("alerts","param","areano","equip_id","receivetime")) //库房,算common数据
        ->where_in("areano",$this->storeroom)
            ->where_between("receivetime", $start, $end)
            ->get("data.sensor.".date("Y"));
        $data_common_kf = $this->deal_param($this->texture["common"], $common_kf,"库房",$date);
        $all = array_merge($all,$data_common_kf);
        foreach ($this->texture_no as $k => $nums){
            $same_arr = array_values(array_intersect($nums,$this->showcase)); //与展柜交集
            $arr = array($k=>$this->texture["zgkf"][$k]);
            if(!empty($same_arr)){
                $texture_zt = $this->mongo_db->select(array("alerts","param","areano","equip_id","receivetime")) //展柜,算材质数据
                ->where_in("areano",$same_arr)
                    ->where_between("receivetime", $start, $end)
                    ->get("data.sensor.".date("Y"));
                $data_texture_zt =  $this->deal_param($arr, $texture_zt,"展柜",$date);
                $all = array_merge($all,$data_texture_zt);
            }

            $same_arr = array_values(array_intersect($nums,$this->storeroom)); //与库房交集
            if(!empty($same_arr)){
                $texture_kf = $this->mongo_db->select(array("alerts","param","areano","equip_id","receivetime")) //库房,算材质数据
                ->where_in("areano",$same_arr)
                    ->where_between("receivetime", $start, $end)
                    ->get("data.sensor.".date("Y"));
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
                            $temperature[] = $value["param"][$k1];
                            if(array_key_exists("areano",$value)){
                                $temperature_areano[$value["areano"]][] = array("data"=>$value["param"][$k1],"equip_id"=>$equip_id,"time"=>$time);
                            }
                        }elseif ($k1 == "uv" && array_key_exists("uv",$value["param"])){
                            $uv[] = $value["param"][$k1];
                            if(array_key_exists("areano",$value)){
                                $uv_areano[$value["areano"]][] = array("data"=>$value["param"][$k1],"equip_id"=>$equip_id,"time"=>$time);
                            }
                        }elseif ($k1 == "voc" && array_key_exists("voc",$value["param"])){
                            $voc[] = $value["param"][$k1];
                            if(array_key_exists("areano",$value)){
                                $voc_areano[$value["areano"]][] = array("data"=>$value["param"][$k1],"equip_id"=>$equip_id,"time"=>$time);
                            }
                        }elseif ($k1 == "humidity" && array_key_exists("humidity",$value["param"])){
                            $humidity[] = $value["param"][$k1];
                            if(array_key_exists("areano",$value)){
                                $humidity_areano[$value["areano"]][] = array("data"=>$value["param"][$k1],"equip_id"=>$equip_id,"time"=>$time);
                            }
                        }elseif ($k1 == "light" && array_key_exists("light",$value["param"])){
                            $light[] = $value["param"][$k1];
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
                $z = ($v["data"] - $data["average"]) / $data["standard"];
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
        if($p == "temperature" || $p == "humidity"){
            $data["wave"] = $min_range.",".$max_range.",".$min_range_normal.",".$max_range_normal;
            $data["wave_status"] = $num?$num:($num_normal?$num_normal:0);
        }
        $data["count_abnormal"] = $abnormal;
        $data["compliance"] = round((sizeof($arr) - $alerts_no)/sizeof($arr),2);
        return $data;
    }

}
