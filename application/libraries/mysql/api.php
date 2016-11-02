<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class API{

    public $btime = null;//查询开始时间
    public $etime = null;//查询结束时间
    public $EnvNo = array();//环境编号数组(展厅+展柜+库房)
    public $humidityEnvNo = array();
    public $lightEnvNo = array();

    public function __construct($param)
    {
        $this->db = $param['db'];
        $this->CI = & get_instance();
        $this->CI->load->config("texture");
        $this->texture = config_item("texture");
        $this->areas = array();
        $this->museum_id = $param["mid"];
        $this->getArea();

        $this->btime = strtotime('-1 day 00:00:00');
        $this->etime = strtotime('-1 day 23:59:59');
        $this->getEnvNo();
        $this->getHumidityEnvNo();
        $this->getLightEnvNo();

    }

    private function getArea(){
        $ty = array("展厅", "展柜", "库房");
        $areas = $this->db["base"]->select("env_no,name,type")->where_in("type", $ty)->get("env")->result_array();
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
            $envno_arr = $this->db['base']->select("distinct(env_no)")->where("env_no<>","null")->where("type","展厅")->get("env")->result_array();
            $this->EnvNo[1] = array_column($envno_arr,"env_no");

            $envno_arr = $this->db['base']->select("distinct(env_no)")->where("env_no<>","null")->where("type","展柜")->get("env")->result_array();
            $this->EnvNo[2] = array_column($envno_arr,"env_no");

            $envno_arr = $this->db['base']->select("distinct(env_no)")->where("env_no<>","null")->where("type","库房")->get("env")->result_array();
            $this->EnvNo[3] = array_column($envno_arr,"env_no");
    }
    //统计函数-获取湿度3个类别材质的环境编号
    public function getHumidityEnvNo(){
            $envno_humidity_arr = $this->db['relic']->select("distinct(parent_env_no)")->where("parent_env_no<>","null")
                ->group_start()->like('material', '石', 'both')->or_like('material', '瓷', 'both')
                ->or_like('material', '陶', 'both')->group_end()->get("relic")->result_array();
            $this->humidityEnvNo[1] = array_column($envno_humidity_arr,"parent_env_no");

            $envno_humidity_arr = $this->db['relic']->select("distinct(parent_env_no)")->where("parent_env_no<>","null")
                ->group_start()->like('material', '铁', 'both')->or_like('material', '铜', 'both')->group_end()
                ->get("relic")->result_array();
             $this->humidityEnvNo[2] = array_column($envno_humidity_arr,"parent_env_no");

            $envno_humidity_arr = $this->db['relic']->select("distinct(parent_env_no)")->where("parent_env_no<>","null")
                ->group_start()->not_like('material','石')->not_like('material','瓷')->not_like('material','陶')
                ->not_like('material','铁')->not_like('material','铜')->group_end()->get("relic")->result_array();
            $this->humidityEnvNo[3]  = array_column($envno_humidity_arr,"parent_env_no");
    }
    //统计函数-获取光照各类别环境编号
    public function getLightEnvNo(){

            $envno_light_arr = $this->db['relic']->select("distinct(parent_env_no)")
                ->where("parent_env_no<>","null")->group_start()
                ->like('material', '石')
                ->or_like('material', '瓷')
                ->or_like('material', '陶')
                ->or_like('material', '铁')
                ->or_like('material', '铜')
                ->group_end()->get("relic")->result_array();
            $this->lightEnvNo[1] = array_column($envno_light_arr,"parent_env_no");

            $envno_light_arr = $this->db['relic']->select("distinct(parent_env_no)")
                ->where("parent_env_no<>","null")->group_start()
                ->like('material', '纸')
                ->or_like('material', '画')
                ->or_like('material', '布')
                ->or_like('material', '绸')
                ->group_end()->get("relic")->result_array();
            $this->lightEnvNo[2] = array_column($envno_light_arr,"parent_env_no");

            $envno_light_arr = $this->db['relic']->select("distinct(parent_env_no)")
                ->where("parent_env_no<>","null")->group_start()
                ->not_like('material','石')
                ->not_like('material','瓷')
                ->not_like('material','陶')
                ->not_like('material','铁')
                ->not_like('material','铜')
                ->not_like('material','纸')
                ->not_like('material','画')
                ->not_like('material','布')
                ->not_like('material','绸')
                ->group_end()->get("relic")->result_array();
            $this->lightEnvNo[3] = array_column($envno_light_arr,"parent_env_no");
    }

    //博物馆基础数据-馆藏文物数量
    public function count_relic()
    {
        return $this->db['relic']->count_all_results('relic');
    }
    //博物馆基础数据-珍贵文物数量
    public function count_precious_relic()
    {
        return $this->db['relic']
            ->where_in("level",array("一级文物","二级文物","三级文物"))
            ->count_all_results('relic');
    }
    //博物馆基础数据-展柜数量
    public function count_showcase()
    {
        return $this->db['base']->where("type","展柜")->count_all_results("env");
    }

    //博物馆综合统计-离散系数
    public function count_scatter($envId,$type)
    {
        $Arr = $this->db['env']
            ->select($type)->where("equip_time>", $this->btime)->where("equip_time<",$this->etime)
            ->where("$type<>","null")->where_in("env_no",$this->EnvNo[$envId])
            ->get("data_sensor")->result_array();

        $list = array_column($Arr,$type);//转为一维
        $avg = array_sum($list)/count($list);//平均值
        $sd = $this->getStandardDeviation($avg,$list); //标准差

        return round($sd/$avg,2);
    }
    //博物馆综合统计-是否有日波动超标
    public function count_is_wave_abnormal($envId)
    {
        $thArr = $this->db['env']
            ->select("env_no,max(temperature) as tmax,min(temperature) as tmin,max(humidity) as hmax,min(humidity) as hmin")
            ->where("equip_time>",$this->btime)->where("equip_time<",$this->etime)
            ->where_in("env_no",$this->EnvNo[$envId])->group_by("env_no")
            ->having("MAX(temperature)-MIN(temperature)>=4 OR MAX(humidity)-MIN(humidity)>=5")
            ->get("data_sensor")->result_array();

        if(empty($thArr)) {return 0;}
        else {return 1;}

    }
    //博物馆综合统计-是否有异常值
    public function count_is_value_abnormal($envId)
    {
        //昨日温湿度数据
        $thArr = $this->db['env']
            ->select("temperature,humidity")
            ->where("equip_time>",$this->btime)->where("equip_time<",$this->etime)
            ->where("temperature<>","null")->where("humidity<>","null")
            ->where_in("env_no",$this->EnvNo[$envId])
            ->get("data_sensor")->result_array();
        $temp_list = array_column($thArr,"temperature");
        $humidity_list = array_column($thArr,"humidity");

        //计算异常值
        if($this->getAbnormalValue($temp_list) || $this->getAbnormalValue($humidity_list)){
            return 1;
        }
        return 0;
    }


    //博物馆参数/环境类型参数综合统计-温度/UV/VOC
    public function count_param($mid,$envId,$type){
        $param = array("temperature"=>7,"uv"=>8,"voc"=>9);
        $data = array();
        $data["date"] = date("Ymd",$this->btime);
        $data['mid'] = $mid;
        $data['param'] = $param[$type];
        if($envId){
            $envArr = array(1=>"展厅", 2=>"展柜", 3=>"库房");
            $data['env_type'] = $envArr[$envId];
            $envno_list = $this->EnvNo[$envId];

            if(empty($envno_list)){return false;}
            //昨日数据记录
            $arr = $this->db['env']
                ->select("env_no,equip_no,$type,equip_time")->where("equip_time>",$this->btime)->where("equip_time<",$this->etime)
                ->where("{$type}<>","null")->where_in("env_no",$envno_list)
                ->get("data_sensor")->result_array();
            if(empty($arr)){return false;}
        }else{
            $arr = $this->db['env']
                ->select("env_no,equip_no,$type,equip_time")->where("equip_time>",$this->btime)->where("equip_time<",$this->etime)
                ->where("{$type}<>","null")->get("data_sensor")->result_array();
        }

        $list = array_column($arr,$type);
        $max = max($list);
        $min = min($list);
        $avg = array_sum($list)/count($list);//平均值
        $middle = $this->getMiddleValue($list); //中位值
        $sd = $this->getStandardDeviation($avg,$list);//标准差

        //异常值
        $abnormal_arr = array();
        $normal_arr = array();
        foreach($arr as $v){
            $Z = abs(($v[$type]-$avg)/$sd);
            if($Z>3) { $abnormal_arr[] = $v;}
            else{ $normal_arr[] = $v;}
        }
        if(empty($abnormal_arr)){
            $max2 = $max;
            $min2 = $min;
            $count_abnormal = 0;
        }else{
            $normal_list = array_column($normal_arr,$type);
            $max2 = max($normal_list);
            $min2 = min($normal_list);
            $count_abnormal = count($abnormal_arr);
            $avg = array_sum($normal_list)/count($normal_list);
        }

        $data['max'] = $max;
        $data['min'] = $min;
        $data['max2'] = $max2;
        $data['min2'] = $min2;
        $data['middle'] = round($middle,2);
        $data['average'] = round($avg,2);
        $data["standard"] = round($sd,2);
        $data["count_abnormal"] = $count_abnormal;

        return $data;
    }

    //博物馆参数/环境类型参数综合统计-湿度(分环境、材质)
    public function count_param_humidity($mid,$envId,$classId){
        $param = array(1=>1,2=>2,3=>3);
        $data = array();
        $data["date"] = date("Ymd",$this->btime);
        $data['mid'] = $mid;
        $data['param'] = $param[$classId];

        if($envId){
            $envArr = array(1=>"展厅", 2=>"展柜", 3=>"库房");
            $data['env_type'] = $envArr[$envId];
            $envno_list = array_values(array_intersect($this->EnvNo[$envId],$this->humidityEnvNo[$classId]));
            if(empty($envno_list)){return false;}
        }else{
            $envno_list = $this->humidityEnvNo[$classId];
        }

        //昨日湿度数据记录
        $arr = $this->db['env']
            ->select("env_no,equip_no,humidity,equip_time")->where("equip_time>",$this->btime)->where("equip_time<",$this->etime)
            ->where("humidity<>","null")->where_in("env_no",$envno_list)
            ->get("data_sensor")->result_array();
        if(empty($arr)){return false;}

        $list = array_column($arr,"humidity");
        $max = max($list);
        $min = min($list);
        $avg = array_sum($list)/count($list);//平均值
        $middle = $this->getMiddleValue($list); //中位值
        $sd = $this->getStandardDeviation($avg,$list);//标准差

        //异常值
        $abnormal_arr = array();
        $normal_arr = array();
        foreach($arr as $v){
            $Z = abs(($v['humidity']-$avg)/$sd);
            if($Z>3) { $abnormal_arr[] = $v;}
            else{ $normal_arr[] = $v;}
        }
        if(empty($abnormal_arr)){
            $max2 = $max;
            $min2 = $min;
            $count_abnormal = 0;
        }else{
            $normal_list = array_column($normal_arr,"humidity");
            $max2 = max($normal_list);
            $min2 = min($normal_list);
            $count_abnormal = count($abnormal_arr);
            $avg = array_sum($normal_list)/count($normal_list);
        }

        $data['max'] = $max;
        $data['min'] = $min;
        $data['max2'] = $max2;
        $data['min2'] = $min2;
        $data['middle'] = round($middle,2);
        $data['average'] = round($avg,2);
        $data["standard"] = round($sd,2);
        $data["count_abnormal"] = $count_abnormal;

        return $data;
    }

    //博物馆参数/环境类型参数综合统计-光照(分环境、材质)
    public function count_param_light($mid,$envId,$classId)
    {
        $param = array(1=>4,2=>5,3=>6);
        $data = array();
        $data["date"] = date("Ymd",$this->btime);
        $data['mid'] = $mid;
        $data['param'] = $param[$classId];

        if($envId){
            $envArr = array(1=>"展厅", 2=>"展柜", 3=>"库房");
            $data['env_type'] = $envArr[$envId];
            $envno_list = array_values(array_intersect($this->EnvNo[$envId],$this->lightEnvNo[$classId]));
            if(empty($envno_list)){return false;}
        }else{
            $envno_list = $this->lightEnvNo[$classId];
        }

        //昨日光照数据记录
        $arr = $this->db['env']
            ->select("env_no,equip_no,light,equip_time")->where("equip_time>",$this->btime)->where("equip_time<",$this->etime)
            ->where("light<>","null")->where_in("env_no",$envno_list)
            ->get("data_sensor")->result_array();
        if(empty($arr)){return false;}

        $list = array_column($arr,"light");
        $max = max($list);
        $min = min($list);
        $avg = array_sum($list)/count($list);//平均值
        $middle = $this->getMiddleValue($list); //中位值
        $sd = $this->getStandardDeviation($avg,$list);//标准差

        //异常值
        $abnormal_arr = array();
        $normal_arr = array();
        foreach($arr as $v){
            $Z = abs(($v['light']-$avg)/$sd);
            if($Z>3) { $abnormal_arr[] = $v;}
            else{ $normal_arr[] = $v;}
        }
        if(empty($abnormal_arr)){
            $max2 = $max;
            $min2 = $min;
            $count_abnormal = 0;
        }else{
            $normal_list = array_column($normal_arr,"light");
            $max2 = max($normal_list);
            $min2 = min($normal_list);
            $count_abnormal = count($abnormal_arr);
            $avg = array_sum($normal_list)/count($normal_list);
        }

        $data['max'] = $max;
        $data['min'] = $min;
        $data['max2'] = $max2;
        $data['min2'] = $min2;
        $data['middle'] = round($middle,2);
        $data['average'] = round($avg,2);
        $data["standard"] = round($sd,2);
        $data["count_abnormal"] = $count_abnormal;

        return $data;

    }


    /***********************************/
    public function data_env(){
        $datas = array();
        $threshold_tb = $this->db["relic"]->table_exists("threshold");
        $threshold = array();
        if($threshold_tb){
            $threshold = $this->db["relic"]->get("threshold")->result_array();
        }
        $threshold_arr = array();
        foreach ($threshold as $v){
            $threshold_arr[$v["type"]] = $v;
        }
        foreach ($this->areas as $v) {
            $data = array();
            $other = array("humidity"=>3,"light"=>6);
            $params = array("temperature","humidity","light","uv","voc");
            //根据文物找材质类型
            $relic = $this->db["relic"]->select("category")->where("parent_env_no",$v["env_no"])->get("relic")->result_array();
            $category = "";
            foreach ($relic as $r){
                if($r["category"]){
                    $flag = false;
                    foreach ($this->texture as $k => $t){
                        foreach ($t as $k1 => $t1){
                            if(!empty($t1)){
                                if(in_array($r["category"],$t1)){
                                    $data["material_".$k1] = (array_key_exists("material_".$k1,$data) && $data["material_".$k1])?$data["material_".$k1]:$k;
                                    $flag = true;
                                }
                            }
                        }
                    }

                    if($flag){
                        $category = $r["category"];
                        break;
                    }
                }
                
            }

            if(!empty($threshold_arr)){
                if(!array_key_exists($category,$threshold_arr) && array_key_exists("其他",$threshold_arr)){
                    $category = "其他";
                }
                foreach ($params as $p){
                    $data[$p."_lower"] = $threshold_arr[$category][$p."_min"] !== ''?$threshold_arr[$category][$p."_min"]:null;
                    $data[$p."_upper"] = $threshold_arr[$category][$p."_max"] !== ''?$threshold_arr[$category][$p."_max"]:null;
                }
            }
            foreach ($other as $k => $o){
                $data["material_".$k] = (array_key_exists("material_".$k,$data) && $data["material_".$k])?$data["material_".$k]:$o;
            }

            $data["mid"] = $this->museum_id;
            $data["sourceid"] = $v["env_no"];
            $data["name"] = $v["name"];
            $data["env_type"] = $v["type"];
            array_push($datas, $data);
        }
        return $datas;
    }

    public function data_analysis() //环境综合统计  //环境参数达标统计 //环境参数综合统计
    {
        $data_analysis = array();
        $areanos = $data_area = $env_arr = $threshold_arr = array();
        $params = array("temperature","humidity","light","uv","voc");
        $now_time = strtotime(date("Y-m-d"));
        //$now_time = strtotime("2016-02-06");
        foreach ($this->areas as $v) {
            $areanos[] = $v["env_no"];
        }
        $datas = $this->db["env"]
                ->select("env_no,temperature,humidity,light,uv,voc")
                ->where_in("env_no", $areanos)
                ->where("equip_time >", $now_time - 24 * 60 * 60)
                ->where("equip_time <", $now_time)
                ->get("data_sensor")
                ->result_array();
        foreach ($datas as $v){
            foreach ($params as $p){
                if(array_key_exists($p,$v) && $v[$p]){
                    $data_area[$v["env_no"]][$p][] = $v[$p]; //把当天所有数据统计到$data_area中，分环境->参数类型
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
                    $data_param["standard"] = round($standard,2);
                    $data_analysis["param"][] = $data_param;
                    //$this->CI->db->insert("data_env_param",$data_param);
                }else{
                    $data_compliance[$p."_total"] = 0;
                    $data_compliance[$p."_abnormal"] = 0;
                }
            }
            $data_analysis["compliance"][] = $data_compliance;
            $data_analysis["complex"][] = $data;
            //$this->CI->db->insert("data_env_compliance",$data_compliance);
            //$this->CI->db->insert("data_env_complex",$data);
        }
        return $data_analysis;
    }



}