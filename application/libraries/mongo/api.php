<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class API{

    public $btime = null;//查询开始时间
    public $etime = null;//查询结束时间
    public $EnvNo = array();//环境编号数组(展厅+展柜+库房)
    public $humidityEnvNo = array();//湿度环境编号
    public $lightEnvNo = array();//光照环境编号
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

        $this->btime = strtotime('20160103 00:00:00');
        $this->etime = strtotime('20160103 23:59:59');
        $this->getEnvNo();
        $this->getHumidityEnvNo();
        $this->getLightEnvNo();
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
    //统计函数-获取湿度3个类别材质的环境编号
    public function getHumidityEnvNo(){
        //一类
        $area_ids = $this->mongo_db->select(array("place"))->like("material","石|陶|瓷")->get("relic.base");
        $area_nos = array();
        foreach($area_ids as $v){
            $datas = $this->mongo_db->select(array("No"))->where(array("_id"=>$v['place']))->getOne("area.base");
            if($datas)array_push($area_nos,$datas);
        }
        $this->humidityEnvNo[1] = array_values(array_unique(array_column($area_nos,"No")));
        //二类
        $area_ids = $this->mongo_db->select(array("place"))->like("material","铁|铜")->get("relic.base");
        $area_nos = array();
        foreach($area_ids as $v){
            $datas = $this->mongo_db->select(array("No"))->where(array("_id"=>$v['place']))->getOne("area.base");
            if($datas)array_push($area_nos,$datas);
        }
        $this->humidityEnvNo[2] = array_values(array_unique(array_column($area_nos,"No")));
        //三类
        $area_ids = $this->mongo_db->select(array("material","place"))->get("relic.base");
        foreach($area_ids as $k=>$v){
            //排除一二类
            if(strpos($v['material'],"石") !== false){unset($area_ids[$k]);}
            elseif(strpos($v['material'],"陶") !== false)unset($area_ids[$k]);
            elseif(strpos($v['material'],"瓷") !== false)unset($area_ids[$k]);
            elseif(strpos($v['material'],"铁") !== false)unset($area_ids[$k]);
            elseif(strpos($v['material'],"铜") !== false)unset($area_ids[$k]);
        }
        $area_nos = array();
        foreach($area_ids as $v){
            $datas = $this->mongo_db->select(array("No"))->where(array("_id"=>$v['place']))->getOne("area.base");
            if($datas)array_push($area_nos,$datas);
        }
        $this->humidityEnvNo[3] = array_values(array_unique(array_column($area_nos,"No")));
    }
    //统计函数-获取光照3个类别环境编号
    public function getLightEnvNo(){
        //一类
        $area_ids = $this->mongo_db->select(array("place"))->like("material","石|陶|瓷|铁|铜")->get("relic.base");
        $area_nos = array();
        foreach($area_ids as $v){
            $datas = $this->mongo_db->select(array("No"))->where(array("_id"=>$v['place']))->getOne("area.base");
            if($datas)array_push($area_nos,$datas);
        }

        $this->lightEnvNo[1] = array_values(array_unique(array_column($area_nos,"No")));
        //二类
        $area_ids = $this->mongo_db->select(array("place"))->like("material","纸|画|布|绸")->get("relic.base");
        $area_nos = array();
        foreach($area_ids as $v){
            $datas = $this->mongo_db->select(array("No"))->where(array("_id"=>$v['place']))->getOne("area.base");
            if($datas)array_push($area_nos,$datas);
        }
        $this->lightEnvNo[2] = array_values(array_unique(array_column($area_nos,"No")));
        //三类
        $area_ids = $this->mongo_db->select(array("material","place"))->get("relic.base");
        foreach($area_ids as $k=>$v){
            //排除一二类
            if(strpos($v['material'],"石") !== false){unset($area_ids[$k]);}
            elseif(strpos($v['material'],"陶") !== false)unset($area_ids[$k]);
            elseif(strpos($v['material'],"瓷") !== false)unset($area_ids[$k]);
            elseif(strpos($v['material'],"铁") !== false)unset($area_ids[$k]);
            elseif(strpos($v['material'],"铜") !== false)unset($area_ids[$k]);
            elseif(strpos($v['material'],"纸") !== false)unset($area_ids[$k]);
            elseif(strpos($v['material'],"画") !== false)unset($area_ids[$k]);
            elseif(strpos($v['material'],"布") !== false)unset($area_ids[$k]);
            elseif(strpos($v['material'],"绸") !== false)unset($area_ids[$k]);
        }
        $area_nos = array();
        foreach($area_ids as $v){
            $datas = $this->mongo_db->select(array("No"))->where(array("_id"=>$v['place']))->getOne("area.base");
            if($datas)array_push($area_nos,$datas);
        }
        $this->lightEnvNo[3] = array_values(array_unique(array_column($area_nos,"No")));
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

    //博物馆综合统计-离散系数
    public function count_scatter($type)
    {
        $datas = $this->mongo_db->select(array("param"))->where_between("receivetime",$this->btime,$this->etime)->get("data.sensor.2016");
        $list = array_column(array_column($datas,"param"),$type);//一维数据列表
        $avg = array_sum($list)/count($list);//平均值
        $sd = $this->getStandardDeviation($avg,$list); //标准差
        return round($sd/$avg,2);
    }
    //博物馆综合统计-是否有日波动超标
    public function count_is_wave_abnormal()
    {
        $datas = $this->mongo_db->select(array("areano","param"))->where_between("receivetime",$this->btime,$this->etime)->get("data.sensor.2016");
        foreach($datas as $v){
           if(array_key_exists("areano",$v)) {
               if(array_key_exists("temperature",$v['param']) || array_key_exists("humidity",$v['param']))
               $new_data[$v['areano']][] = $v['param'];//区域编号+参数值的三维数组
           }
        }
        foreach($new_data as $v){
            $temp_list = array_column($v,"temperature");
            if(max($temp_list) - min($temp_list) >= 4){return 1;}
            $humidity_list = array_column($v,"humidity");
            if(max($humidity_list) - min($humidity_list) >= 5){return 1;}
        }
        return 0;
    }
    //博物馆综合统计-是否有异常值
    public function count_is_value_abnormal()
    {
        $datas = $this->mongo_db->select(array("param.temperature","param.humidity"))->where_between("receivetime",$this->btime,$this->etime)->get("data.sensor.2016");

        $temp_list = array_column(array_column($datas,"param"),"temperature");
        if($this->getAbnormalValue($temp_list))return 1;

        $humidity_list = array_column(array_column($datas,"param"),"humidity");
        if($this->getAbnormalValue($humidity_list))return 1;

        return 0;

    }

    //博物馆参数/环境类型参数综合统计-温度/UV/VOC
    public function count_param($mid,$envId,$type){
        $data = array();
        $data["date"] = date("Ymd",$this->btime);
        $data['mid'] = $mid;
        $data['param'] = $type;
        if($envId){
            $envArr = array(1=>"showroom", 2=>"showcase", 3=>"storeroom");
            $data['env_type'] = $envArr[$envId];
            $envno_list = $this->EnvNo[$envId];
            if(empty($envno_list)){return false;}

            $datas = $this->mongo_db->select(array("equip_id","param.{$type}","receivetime"))
                ->where_between("receivetime",$this->btime,$this->etime)
                ->where_in("areano",$envno_list)
                ->get("data.sensor.2016");
        }else{
            $datas = $this->mongo_db->select(array("equip_id","param.{$type}","receivetime"))
                ->where_between("receivetime",$this->btime,$this->etime)
                ->get("data.sensor.2016");
        }

        foreach($datas as $k=>$v){
            if(!array_key_exists($type,$v['param'])){
                unset($datas[$k]);
            }
        }
        if(empty($datas)) return false;

        $list = array_column(array_column($datas,"param"),$type);
        $max = max($list);
        $min = min($list);
        $avg = array_sum($list)/count($list);//平均值
        $middle = $this->getMiddleValue($list); //中位值
        $sd = $this->getStandardDeviation($avg,$list);//标准差

        //异常值
        $abnormal_arr = array();
        $normal_arr = array();
        foreach($datas as $v){
            if(array_key_exists($type,$v['param'])){
                $Z = abs(($v['param'][$type]-$avg)/$sd);
                if($Z>3) {
                    $abnormal_arr[] = array($type=>$v['param'][$type],"equip_id"=>$v['equip_id'],"receivetime"=>$v['receivetime']);
                }else{
                    $normal_arr[] = $v['param'][$type];
                }
            }
        }

        if(empty($abnormal_arr)){
            $max2 = $max;
            $min2 = $min;
            $count_abnormal = 0;
        }else{
            $max2 = max($normal_arr);
            $min2 = min($normal_arr);
            $count_abnormal = count($abnormal_arr);
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
        $data = array();
        $data["date"] = date("Ymd",$this->btime);
        $data['mid'] = $mid;
        $data['param'] = "humidity".$classId;

        if($envId){
            $envArr = array(1=>"showroom", 2=>"showcase", 3=>"storeroom");
            $data['env_type'] = $envArr[$envId];
            if(empty($this->EnvNo[$envId]) || empty($this->humidityEnvNo[$classId])) return false;
            $envno_list = array_values(array_intersect($this->EnvNo[$envId],$this->humidityEnvNo[$classId]));
        }else{
            $envno_list = $this->humidityEnvNo[$classId];
        }

        if(empty($envno_list)){return false;} //不存在类别环境列表则退出该类别的统计
        //昨日湿度数据记录
        $datas = $this->mongo_db->select(array("equip_id","param.humidity","receivetime"))
            ->where_between("receivetime",$this->btime,$this->etime)
            ->where_in("areano",$envno_list)
            ->get("data.sensor.2016");

        foreach($datas as $k=>$v){
            if(!array_key_exists("humidity",$v['param'])){
                unset($datas[$k]);
            }
        }

        if(empty($datas)) return false; //不存在湿度数据跳出该类别统计

        $list = array_column(array_column($datas,"param"),"humidity");
        $max = max($list);
        $min = min($list);
        $avg = array_sum($list)/count($list);//平均值
        $middle = $this->getMiddleValue($list); //中位值
        $sd = $this->getStandardDeviation($avg,$list);//标准差

        //异常值
        $abnormal_arr = array();
        $normal_arr = array();
        foreach($datas as $v){
            if(array_key_exists("humidity",$v['param'])){
                $Z = abs(($v['param']["humidity"]-$avg)/$sd);
                if($Z>3) {
                    $abnormal_arr[] = array("humidity"=>$v['param']["humidity"],"equip_id"=>$v['equip_id'],"receivetime"=>$v['receivetime']);
                }else{
                    $normal_arr[] = $v['param']["humidity"];
                }
            }
        }

        if(empty($abnormal_arr)){
            $max2 = $max;
            $min2 = $min;
            $count_abnormal = 0;
        }else{
            $max2 = max($normal_arr);
            $min2 = min($normal_arr);
            $count_abnormal = count($abnormal_arr);
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
        $data = array();
        $data["date"] = date("Ymd",$this->btime);
        $data['mid'] = $mid;
        $data['param'] = "light".$classId;

        if($envId){
            $envArr = array(1=>"showroom", 2=>"showcase", 3=>"storeroom");
            $data['env_type'] = $envArr[$envId];
            if(empty($this->EnvNo[$envId]) || empty($this->lightEnvNo[$classId])) return false;
            $envno_list = array_values(array_intersect($this->EnvNo[$envId],$this->lightEnvNo[$classId]));
        }else{
            $envno_list = $this->lightEnvNo[$classId];
        }
        if(empty($envno_list)){return false;}

        //昨日光照数据记录
        $datas = $this->mongo_db->select(array("areano","equip_id","param.light","receivetime"))
            ->where_between("receivetime",$this->btime,$this->etime)
            ->where_in("areano",$envno_list)
            ->get("data.sensor.2016");
        foreach($datas as $k=>$v){
            if(!array_key_exists("light",$v['param'])){
                unset($datas[$k]);
            }
        }
        if(empty($datas)){return false;}
        $list = array_column(array_column($datas,"param"),"light");
        $max = max($list);
        $min = min($list);
        $avg = array_sum($list)/count($list);//平均值
        $middle = $this->getMiddleValue($list); //中位值
        $sd = $this->getStandardDeviation($avg,$list);//标准差

        //异常值
        $abnormal_arr = array();
        $normal_arr = array();
        foreach($datas as $v){
            if(array_key_exists("light",$v['param'])){
                $Z = abs(($v['param']["light"]-$avg)/$sd);
                if($Z>3) {
                    $abnormal_arr[] = array("light"=>$v['param']["light"],"equip_id"=>$v['equip_id'],"receivetime"=>$v['receivetime']);
                }else{
                    $normal_arr[] = $v['param']["light"];
                }
            }
        }

        if(empty($abnormal_arr)){
            $max2 = $max;
            $min2 = $min;
            $count_abnormal = 0;
        }else{
            $max2 = max($normal_arr);
            $min2 = min($normal_arr);
            $count_abnormal = count($abnormal_arr);
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



    /********************************/

    public function data_analysis() //环境综合统计  //环境参数达标统计 //环境参数综合统计
    {
        $data_analysis = array();
        $areanos = $data_area = $env_arr = $threshold_arr = array();
        $params = array("temperature","humidity","light","uv","voc");
        //$now_time = strtotime(date("Y-m-d"));
        $now_time = strtotime("2016-01-02");
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

    public function data_env(){ //环境表,需最先执行以获取全部环境
        $datas = array();
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
            array_push($datas, $data);
        }
        return $datas;
    }


    
    
}