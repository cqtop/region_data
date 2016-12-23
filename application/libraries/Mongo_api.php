<?php 
defined('BASEPATH') OR exit('No direct script access allowed');
require_once("MY_library.php");
class Mongo_api extends MY_library{
    private $mongo_db = null;
    private $year = null;
    public function __construct($param)
    {
        parent::__construct();
        require_once("Mongo_db.php");
        $this->mongo_db = new Mongo_db($param['db']);
        $this->museum_id = $param["mid"];
        $this->year = date("Y",strtotime("-1 day"));
        if($param["date"]){
            $this->date = $param["date"];
            $this->year = substr($param["date"],0,4);
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
            $relic = $this->mongo_db->select(array("material"))->where(array("place"=>$v["_id"]))->get("relic.base");
            //$this->material
            foreach ($relic as $r){
                if($r["material"]){
                    foreach ($this->material as $material => $value){
                        if(in_array($r["material"],$value)){
                            $r["material"] = $material;
                            break;
                        }
                    }
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
                        if ($areas[$key]["type"] == "展柜"){
                            $this->texture_no_zg[$hhs[$k]][] = $v["No"];
                        }elseif($areas[$key]["type"] == "库房"){
                            $this->texture_no_kf[$hhs[$k]][] = $v["No"];
                        }
                    }else{
                        if ($areas[$key]["type"] == "展柜") {
                            $this->texture_no_zg[$unique[0]][] = $v["No"];
                        }elseif($areas[$key]["type"] == "库房"){
                            $this->texture_no_kf[$unique[0]][] = $v["No"];
                        }
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
    public function count_relic(){
        return $this->mongo_db->count("relic.base");
    }
    //博物馆基础数据-珍贵文物数量
    public function count_precious_relic(){
        return $this->mongo_db->where_in("level",array("一级","二级","三级"))->count("relic.base");
    }
    //博物馆基础数据-展柜数量
    public function count_cabinet(){
        $showcase_id = $this->mongo_db->select(array("_id"))->where(array("name"=>"展柜"))->getOne("area.type");
        return $this->mongo_db->where(array("type"=>$showcase_id['_id']))->count('area.base');
    }
    //博物馆基础数据-展厅数量
    public function count_hall(){
        $showcase_id = $this->mongo_db->select(array("_id"))->where(array("name"=>"展厅"))->getOne("area.type");
        return $this->mongo_db->where(array("type"=>$showcase_id['_id']))->count('area.base');
    }
    //博物馆基础数据-库房数量
    public function count_storeroom(){
        $showcase_id = $this->mongo_db->select(array("_id"))->where(array("name"=>"库房"))->getOne("area.type");
        return $this->mongo_db->where(array("type"=>$showcase_id['_id']))->count('area.base');
    }

    //博物馆综合统计-基于环境
    public function count_data_complex_env($date,$env_id){
        $this->date_conversion($date);
        $env_type = array(1=>"展厅", 2=>"展柜", 3=>"库房");
        $data = array();
        if(!$this->EnvNo[$env_id]) return false; //不存在对应的环境类型(hall/cabinet/storeroom)
        foreach($this->EnvNo[$env_id] as $env_no){ //遍历对应环境类型的环境编号
            $ret = $this->count_complex_env_all($date,$env_no);
            $base = array(
                "date"=>$this->date_str,
                "env_no"=>$env_no,
                "env_type"=>$env_type[$env_id],
                "mid"=>$this->museum_id,
            );
            $data[] = array_merge($base,$ret);
        }

        return $data;
    }
    //博物馆综合统计-离散系数&参数达标个数-基于环境-所有参数一起统计
    public function count_complex_env_all($date,$env_no){
        $ret = array();
        $env_param = array("temperature","humidity","light","uv","voc");
        $datas = $this->mongo_db
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
            ->where(array("areano"=>$env_no))
            ->get("data.sensor.".$this->year);
        if(empty($datas))
            return array(
                "scatter_temperature"=>0,
                "scatter_humidity"=>0,
                "scatter_light"=>0,
                "scatter_uv"=>0,
                "scatter_voc"=>0,
                "temperature_total"=>0,
                "temperature_abnormal"=>0,
                "humidity_total"=>0,
                "humidity_abnormal"=>0,
                "light_total"=>0,
                "light_abnormal"=>0,
                "uv_total"=>0,
                "uv_abnormal"=>0,
                "voc_total"=>0,
                "voc_abnormal"=>0
            );
        $param_arr = array_column($datas,"param");
        foreach($env_param as $param){
            //离散系数统计
            $param_list = array_column($param_arr,$param);
            if(empty($param_list)) {
                $ret['scatter_'.$param] = 0;
            }else{
                $avg = array_sum($param_list)/count($param_list);//平均值
                if(!$avg) {
                    $ret['scatter_'.$param] = 0;
                }else{
                    $sd = $this->getStandardDeviation($avg,$param_list); //标准差
                    $ret['scatter_'.$param] = round($sd/$avg,4);
                }
            }
            //达标个数统计(分天和周/月)
            if($date=="yesterday"){//统计原始天数据
                $normal = $abnormal = array();
                foreach($datas as $data){
                    if(isset($data['param'][$param])){ //存在参数数据
                        if(isset($data['alerts'])){ //存在告警字段
                            $alarm_list = array_column($data['alerts'],"parameter");
                            if(in_array($param,$alarm_list)) $abnormal[] = $data['param'][$param]; //告警列表包含此参数
                            else $normal[] = $data['param'][$param];
                        }else{
                            $normal[] = $data['param'][$param];
                        }
                    }
                }
                $ret[$param."_total"] = count($normal)+count($abnormal);
                $ret[$param."_abnormal"] = count($abnormal);
            }else{//累加统计表天数据
                $alldatas = $this->CI->db
                    ->select("SUM({$param}_total) as total,SUM({$param}_abnormal) as abnormal")
                    ->where("env_no",$env_no)
                    ->where("mid",$this->museum_id)
                    ->where_in("date",$this->_date_list($this->date_start,$this->date_end))
                    ->group_by("env_no")
                    ->get("data_complex_env")
                    ->result_array();
                if(!$alldatas) {
                    $ret[$param."_total"] = 0;
                    $ret[$param."_abnormal"] = 0;
                }else{
                    $ret[$param."_total"] = $alldatas[0]['total'];
                    $ret[$param."_abnormal"] = $alldatas[0]['abnormal'];
                }
            }
        }

        return $ret;
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
        $datas = $this->mongo_db
            ->select(array("param"))
            ->where_between("receivetime",$this->btime,$this->etime)
            ->where_in("areano",$this->EnvNo[$env_id])
            ->get("data.sensor.".$this->year);
        if(empty($datas)) return 0;
        $list = array_column(array_column($datas,"param"),$type);//一维数据列表
        if(empty($list)) return 0; //无对应环境参数数据
        $avg = array_sum($list)/count($list);//平均值
        $sd = $this->getStandardDeviation($avg,$list); //标准差
        if(!$avg) return 0;
        return round($sd/$avg,4);

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
            ->get("data.sensor.".$this->year);
        //if(!$alldatas) return false;
        foreach($env_param as $param){
            $normal = $abnormal = array();
            foreach($alldatas as $data){
                if(isset($data['param'][$param])){
                    if(isset($data['alerts'])){ //存在告警字段
                        $alarm_list = array_column($data['alerts'],"parameter");
                        if(in_array($param,$alarm_list)) $abnormal[] = $data['param'][$param]; //包含告警参数
                        else $normal[] = $data['param'][$param];
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
            ->where_gte("receivetime", $start)
            ->where_lt("receivetime",$end )
            ->get("data.sensor.".$this->year);
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
        $query = array('start' => $start, 'end' => $end);

        $all = array();
        $param_arr = $this->texture["common"];
        foreach ($this->texture["zt"] as $k=>$zt){
            $param_arr[$k] = $zt;
        }
        $query['area_no'] = $this->hall;
        $data_deal = $this->deal_param($param_arr, $query,"展厅",$date);
        $all = array_merge($all,$data_deal);

        $query['area_no'] = $this->showcase;
        $data_deal = $this->deal_param($this->texture["common"], $query,"展柜",$date);
        $all = array_merge($all,$data_deal);

        $query['area_no'] = $this->storeroom;
        $data_deal = $this->deal_param($this->texture["common"], $query,"库房",$date);
        $all = array_merge($all,$data_deal);
        foreach ($this->texture_no_zg as $k => $nums){
            $same_arr = array_values(array_intersect($nums,$this->showcase)); //与展柜交集
            $arr = array($k=>$this->texture["zgkf"][$k]);
            if(!empty($same_arr)){
                $query['area_no'] = $same_arr;
                $data_deal =  $this->deal_param($arr, $query,"展柜",$date);
                $all = array_merge($all,$data_deal);
            }
        }
        foreach ($this->texture_no_kf as $k => $nums){
            $same_arr = array_values(array_intersect($nums,$this->storeroom)); //与库房交集
            $arr = array($k=>$this->texture["zgkf"][$k]);
            if(!empty($same_arr)){
                $query['area_no'] = $same_arr;
                $data_deal =  $this->deal_param($arr, $query,"库房",$date);
                $all = array_merge($all,$data_deal);
            }
        }
        return $all;
    }


    private function deal_param($arr,$query,$ty,$date){
        $data = $this->allData($query['area_no'], $query['start'], $query['end']);

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
                            if(is_array($v) && array_key_exists("parameter",$v) && $v["parameter"] == $k1){
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



    // 博物馆统计信息
    public function countmusem(){
        $daytime = strtotime($this->date);
        $monthtime1 = strtotime(date('Y-m-01', $daytime));
        $monthtime2 = strtotime(date('Y-m-01', strtotime('+1 month',$daytime)))-1;
        $data = array();
        $data['count_day'] = $this->mongo_db->where_between("receivetime", $daytime, $daytime+86400)->count("data.sensor.".$this->year);
        $data['count_month'] = $this->mongo_db->where_between("receivetime", $monthtime1, $monthtime2)->count("data.sensor.".$this->year);

        // 环境统计
        foreach(array('show_cabinet', 'hall', 'storage') as $type){
            $at = $this->mongo_db->where(array("id"=>$type))->getOne("area.type");
            if($at){
                $data[$type] = $this->mongo_db->where(array("type"=>$at['_id']))->count("area.base");
            }else{
                $data[$type] = 0;
            }
        }
        
        return $data;
    }

}
