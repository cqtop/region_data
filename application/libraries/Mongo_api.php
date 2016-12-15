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
        $datas = $this->mongo_db
            ->select(array("param"))
            ->where_between("receivetime",$this->btime,$this->etime)
            ->where(array("areano"=>$env_no))
            ->get("data.sensor.".$this->year);
        if(empty($datas)) return 0;
        $list = array_column(array_column($datas,"param"),$type);//一维数据列表
        if(empty($list)) return 0; //无对应环境参数数据
        $avg = array_sum($list)/count($list);//平均值
        if(!$avg) return 0;
        $sd = $this->getStandardDeviation($avg,$list); //标准差
        return round($sd/$avg,4);
    }

    //博物馆综合统计-数据达标和未达标数量统计-基于环境
    public function count_number($date,$param,$type,$env_no){
        if($date == "yesterday"){//天统计-计算原始表
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
                ->where(array("areano"=>$env_no))
                ->get("data.sensor.".$this->year);
            $normal = $abnormal = array();
            foreach($alldatas as $data){
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
        if($avg) return round($sd/$avg,4);
        return 0;
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
        foreach ($this->texture_no as $k => $nums){
            $same_arr = array_values(array_intersect($nums,$this->showcase)); //与展柜交集
            $arr = array($k=>$this->texture["zgkf"][$k]);
            if(!empty($same_arr)){
                $query['area_no'] = $same_arr;
                $data_deal =  $this->deal_param($arr, $query,"展柜",$date);
                $all = array_merge($all,$data_deal);
            }

            $same_arr = array_values(array_intersect($nums,$this->storeroom)); //与库房交集
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
        return $data;
    }

}
