<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class API{
    
    public function __construct($param)
    {
        $this->db = $param['db'];
    }

    /**
     * 统计函数-计算标准差
     * @param float $avg 平均值
     * @param Array $list 队列数组
     * @return float 标准差值
     */
    public function getStandardDeviation($avg, $list)
    {
        $total_var = 0;
        foreach ($list as $lv){
            $total_var += pow( ($lv - $avg), 2 );
        }
        return sqrt( $total_var / (count($list) ) );
    }

    /**
     * 统计函数-计算中位值
     * @param Array $list 一维数组
     * @return float 中位值
     */
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

    //博物馆综合统计-温度离散系数
    public function count_scatter_temp()
    {
        $btime = strtotime('-11 day 00:00:00');
        $etime = strtotime('-11 day 23:59:59');

        $tempArr = $this->db['env']
            //->query("select temperature from data_sensor where (equip_time between $btime and $etime) and temperature is not null")
            ->select("temperature")
            ->where("equip_time>",$btime)->where("equip_time<",$etime)->where("temperature<>","null")
            ->get("data_sensor")
            ->result_array();

        if(empty($tempArr)){lineMsg("温度记录不存在！");throw new Exception();}
        $templist = array_column($tempArr,"temperature");//转为一维温度数组
        $avg = array_sum($templist)/count($templist);//平均值
        $sd = $this->getStandardDeviation($avg,$templist); //标准差

        return round($sd/$avg,2);
    }

    //博物馆综合统计-湿度离散系数
    public function count_scatter_humidity()
    {
        $btime = strtotime('-11 day 00:00:00');
        $etime = strtotime('-11 day 23:59:59');

        $humidityArr = $this->db['env']
            ->select("humidity")
            ->where("equip_time>",$btime)->where("equip_time<",$etime)->where("humidity<>","null")
            ->get("data_sensor")
            ->result_array();

        if(empty($humidityArr)){lineMsg("湿度记录不存在！");throw new Exception();}
        $humiditylist = array_column($humidityArr,"humidity");//转为一维数组
        $avg = array_sum($humiditylist)/count($humiditylist);//平均值
        $sd = $this->getStandardDeviation($avg,$humiditylist); //标准差

        return round($sd/$avg,2);
    }

    //博物馆综合统计-是否有日波动超标
    public function count_is_wave_abnormal()
    {
        $btime = strtotime('-11 day 00:00:00');
        $etime = strtotime('-11 day 23:59:59');

        $thArr = $this->db['env']
            ->select("max(temperature) as tmax,max(humidity) as hmax,min(temperature) as tmin,min(humidity) as hmin")
            ->where("equip_time>",$btime)->where("equip_time<",$etime)
            ->get("data_sensor")
            ->result_array();

        if($thArr[0]['tmax']-$thArr[0]['tmin'] >=4){
            return 1;
        }
        if($thArr[0]['hmax']-$thArr[0]['hmin'] >=5){
            return 1;
        }
        return 0;
    }

    //博物馆综合统计-是否有异常值
    public function count_is_value_abnormal()
    {
        $btime = strtotime('-11 day 00:00:00');
        $etime = strtotime('-11 day 23:59:59');

        //昨日温湿度数据
        $thArr = $this->db['env']
            ->select("temperature,humidity")
            ->where("equip_time>",$btime)->where("equip_time<",$etime)
            ->where("temperature<>","null")->where("humidity<>","null")
            ->get("data_sensor")
            ->result_array();
        $temp_list = array_column($thArr,"temperature");
        $humidity_list = array_column($thArr,"humidity");

        //计算温度异常值
        $temp_avg = array_sum($temp_list)/count($temp_list);
        $temp_sd = $this->getStandardDeviation($temp_avg,$temp_list);
        foreach($temp_list as $temp){
            $Z = abs(($temp-$temp_avg)/$temp_sd);
            if($Z>3) return 1;
        }
        //计算湿度异常值
        $humidity_avg = array_sum($humidity_list)/count($humidity_list);
        $humidity_sd = $this->getStandardDeviation($humidity_avg,$humidity_list);
        foreach($humidity_list as $humidity){
            $Z = abs(($humidity-$humidity_avg)/$humidity_sd); //Z分数
            if($Z>3) return 1;
        }
        return 0;
    }

    //博物馆参数综合统计-温度
    public function count_param_temperature($mid){
        $btime = strtotime('-12 day 00:00:00');
        $etime = strtotime('-12 day 23:59:59');

        //昨日温度记录
        $temp_arr = $this->db['env']
            ->select("env_no,equip_no,temperature,equip_time")
            ->where("equip_time>",$btime)->where("equip_time<",$etime)
            ->where("temperature<>","null")
            ->get("data_sensor")
            ->result_array();

        $temp_list = array_column($temp_arr,"temperature");
        $temp_max = max($temp_list);
        $temp_min = min($temp_list);
        $temp_avg = array_sum($temp_list)/count($temp_list);//平均值
        $temp_middle = $this->getMiddleValue($temp_list); //中位值
        $temp_sd = $this->getStandardDeviation($temp_avg,$temp_list);//标准差

        $temp_abnormal_arr = array();
        $temp_normal_arr = array();
        foreach($temp_arr as $v){
            $Z = abs(($v['temperature']-$temp_avg)/$temp_sd);
            if($Z>3) {
                $temp_abnormal_arr[] = $v;
            }else{
                $temp_normal_arr[] = $v;
            }
        }
        if(empty($temp_abnormal_arr)){
            $temp_max2 = $temp_max;
            $temp_min2 = $temp_min;
            $temp_count_abnormal = 0;
        }else{
            $temp_max2 = max(array_column($temp_normal_arr,"temperature"));
            $temp_min2 = min(array_column($temp_normal_arr,"temperature"));
            $temp_count_abnormal = count($temp_abnormal_arr);
        }

        return array(
            "date"=>date("Ymd",$btime),
            "mid"=>$mid,
            "param"=>"temperature",
            "max"=>$temp_max,
            "min"=>$temp_min,
            "max2"=>$temp_max2,
            "min2"=>$temp_min2,
            "middle"=>$temp_middle,
            "average"=>round($temp_avg,2),
            "count_abnormal"=>$temp_count_abnormal,
            "standard"=>round($temp_sd,2),
        );
    }

    //博物馆参数综合统计-紫外UV
    public function count_param_uv($mid){
        $btime = strtotime('-12 day 00:00:00');
        $etime = strtotime('-12 day 23:59:59');

        //昨日uv记录
        $uv_arr = $this->db['env']
            ->select("env_no,equip_no,uv,equip_time")
            ->where("equip_time>",$btime)->where("equip_time<",$etime)
            ->where("uv<>","null")
            ->get("data_sensor")
            ->result_array();
        if(!$uv_arr ){lineMsg("UV数据不存在！");throw new Exception();}
        $uv_list = array_column($uv_arr,"uv");
        $uv_max = max($uv_list);
        $uv_min = min($uv_list);
        $uv_avg = array_sum($uv_list)/count($uv_list);//平均值
        $uv_middle = $this->getMiddleValue($uv_list); //中位值
        $uv_sd = $this->getStandardDeviation($uv_avg,$uv_list);//标准差

        $uv_abnormal_arr = array();
        $uv_normal_arr = array();
        foreach($uv_arr as $v){
            $Z = abs(($v['uv']-$uv_avg)/$uv_sd);
            if($Z>3) {
                $uv_abnormal_arr[] = $v;
            }else{
                $uv_normal_arr[] = $v;
            }
        }
        if(empty($uv_abnormal_arr)){
            $uv_max2 = $uv_max;
            $uv_min2 = $uv_min;
            $uv_count_abnormal = 0;
        }else{
            $uv_max2 = max(array_column($uv_normal_arr,"uv"));
            $uv_min2 = min(array_column($uv_normal_arr,"uv"));
            $uv_count_abnormal = count($uv_abnormal_arr);
        }

        return array(
            "date"=>date("Ymd",$btime),
            "mid"=>$mid,
            "param"=>"uv",
            "max"=>$uv_max,
            "min"=>$uv_min,
            "max2"=>$uv_max2,
            "min2"=>$uv_min2,
            "middle"=>$uv_middle,
            "average"=>round($uv_avg,2),
            "count_abnormal"=>$uv_count_abnormal,
            "standard"=>round($uv_sd,2),
        );
    }

    //博物馆参数综合统计-voc
    public function count_param_voc($mid){
        $btime = strtotime('-12 day 00:00:00');
        $etime = strtotime('-12 day 23:59:59');
        //昨日voc记录
        $voc_arr = $this->db['env']
            ->select("env_no,equip_no,voc,equip_time")
            ->where("equip_time>",$btime)->where("equip_time<",$etime)
            ->where("voc<>","null")
            ->get("data_sensor")
            ->result_array();
        if(!$voc_arr ){lineMsg("VOC数据不存在！！");throw new Exception();}
        $voc_list = array_column($voc_arr,"voc");
        $voc_max = max($voc_list);
        $voc_min = min($voc_list);
        $voc_avg = array_sum($voc_list)/count($voc_list);//平均值
        $voc_middle = $this->getMiddleValue($voc_list); //中位值
        $voc_sd = $this->getStandardDeviation($voc_avg,$voc_list);//标准差

        $voc_abnormal_arr = array();
        $voc_normal_arr = array();
        foreach($voc_arr as $v){
            $Z = abs(($v['voc']-$voc_avg)/$voc_sd);
            if($Z>3) {
                $voc_abnormal_arr[] = $v;
            }else{
                $voc_normal_arr[] = $v;
            }
        }
        if(empty($voc_abnormal_arr)){
            $voc_max2 = $voc_max;
            $voc_min2 = $voc_min;
            $voc_count_abnormal = 0;
        }else{
            $voc_max2 = max(array_column($voc_normal_arr,"voc"));
            $voc_min2 = min(array_column($voc_normal_arr,"voc"));
            $voc_count_abnormal = count($voc_abnormal_arr);
        }

        return array(
            "date"=>date("Ymd",$btime),
            "mid"=>$mid,
            "param"=>"voc",
            "max"=>$voc_max,
            "min"=>$voc_min,
            "max2"=>$voc_max2,
            "min2"=>$voc_min2,
            "middle"=>$voc_middle,
            "average"=>round($voc_avg,2),
            "count_abnormal"=>$voc_count_abnormal,
            "standard"=>round($voc_sd,2),
        );
    }

    //博物馆参数综合统计-湿度(分3类)
    public function count_param_humidity($mid,$classId){
        $btime = strtotime('-11 day 00:00:00');
        $etime = strtotime('-11 day 23:59:59');
        if($classId == 1){//"石质","陶器","瓷器"
            $envno_arr = $this->db['relic']
                ->select("distinct(parent_env_no)")
                ->where("parent_env_no<>","null")
                ->group_start()
                ->like('material', '石', 'both')
                ->or_like('material', '瓷', 'both')
                ->or_like('material', '陶', 'both')
                ->group_end()
                ->get("relic")
                ->result_array();
            $envno_list = array_column($envno_arr,"parent_env_no");
        }elseif($classId == 2){//"铁质","青铜"
            $envno_arr = $this->db['relic']
                ->select("distinct(parent_env_no)")
                ->where("parent_env_no<>","null")
                ->group_start()
                ->like('material', '铁', 'both')
                ->or_like('material', '铜', 'both')
                ->group_end()
                ->get("relic")
                ->result_array();
            $envno_list = array_column($envno_arr,"parent_env_no");
        }else{//"纸质","壁画","纺织品","漆木器","其他"
            $envno_arr = $this->db['relic']
                ->select("distinct(parent_env_no)")
                ->where("parent_env_no<>","null")
                ->group_start()
                ->not_like('material','石')
                ->not_like('material','瓷')
                ->not_like('material','陶')
                ->not_like('material','铁')
                ->not_like('material','铜')
                ->group_end()
                ->get("relic")
                ->result_array();
            $envno_list = array_column($envno_arr,"parent_env_no");
        }

        //昨日湿度记录
        $humidity_arr = $this->db['env']
            ->select("env_no,equip_no,humidity,equip_time")
            ->where("equip_time>",$btime)->where("equip_time<",$etime)
            ->where("humidity<>","null")
            ->where_in("env_no",$envno_list)//3大类
            ->get("data_sensor")
            ->result_array();

        $humidity_list = array_column($humidity_arr,"humidity");
        $humidity_max = max($humidity_list);
        $humidity_min = min($humidity_list);
        $humidity_avg = array_sum($humidity_list)/count($humidity_list);//平均值
        $humidity_middle = $this->getMiddleValue($humidity_list); //中位值
        $humidity_sd = $this->getStandardDeviation($humidity_avg,$humidity_list);//标准差

        $humidity_abnormal_arr = array();
        $humidity_normal_arr = array();
        foreach($humidity_arr as $v){
            $Z = abs(($v['humidity']-$humidity_avg)/$humidity_sd);
            if($Z>3) {
                $humidity_abnormal_arr[] = $v;
            }else{
                $humidity_normal_arr[] = $v;
            }
        }
        if(empty($humidity_abnormal_arr)){
            $humidity_max2 = $humidity_max;
            $humidity_min2 = $humidity_min;
            $humidity_count_abnormal = 0;
        }else{
            $humidity_max2 = max(array_column($humidity_normal_arr,"humidity"));
            $humidity_min2 = min(array_column($humidity_normal_arr,"humidity"));
            $humidity_count_abnormal = count($humidity_abnormal_arr);
        }

        return array(
            "date"=>date("Ymd",$btime),
            "mid"=>$mid,
            "param"=>"humidity".$classId,
            "max"=>$humidity_max,
            "min"=>$humidity_min,
            "max2"=>$humidity_max2,
            "min2"=>$humidity_min2,
            "middle"=>$humidity_middle,
            "average"=>round($humidity_avg,2),
            "count_abnormal"=>$humidity_count_abnormal,
            "standard"=>round($humidity_sd,2),
        );
    }

    //博物馆参数综合统计-光照
    public function count_param_light($mid,$classId)
    {
        $btime = strtotime('2016/6/22 00:00:00');
        $etime = strtotime('2016/6/22 23:59:59');
        if($classId == 1){//"石质","陶器","瓷器","铁质","青铜"
            $envno_arr = $this->db['relic']
                ->select("distinct(parent_env_no)")
                ->where("parent_env_no<>","null")
                ->group_start()
                ->like('material', '石')
                ->or_like('material', '瓷')
                ->or_like('material', '陶')
                ->or_like('material', '铁')
                ->or_like('material', '铜')
                ->group_end()
                ->get("relic")
                ->result_array();
            $envno_list = array_column($envno_arr,"parent_env_no");
        }elseif($classId == 2){//"纸质","壁画","纺织品"
            $envno_arr = $this->db['relic']
                ->select("distinct(parent_env_no)")
                ->where("parent_env_no<>","null")
                ->group_start()
                ->like('material', '纸')
                ->or_like('material', '画')
                ->or_like('material', '布')
                ->or_like('material', '绸')
                ->group_end()
                ->get("relic")
                ->result_array();
            $envno_list = array_column($envno_arr,"parent_env_no");
        }else{//"漆木器","其他"
            $envno_arr = $this->db['relic']
                ->select("distinct(parent_env_no)")
                ->where("parent_env_no<>","null")
                ->group_start()
                ->not_like('material','石')
                ->not_like('material','瓷')
                ->not_like('material','陶')
                ->not_like('material','铁')
                ->not_like('material','铜')
                ->not_like('material','纸')
                ->not_like('material','画')
                ->not_like('material','布')
                ->not_like('material','绸')
                ->group_end()
                ->get("relic")
                ->result_array();
            $envno_list = array_column($envno_arr,"parent_env_no");
        }

        //昨日光照记录(按照材质分3类)
        $light_arr = $this->db['env']
            ->select("env_no,equip_no,light,equip_time")
            ->where("equip_time>",$btime)->where("equip_time<",$etime)
            ->where("light<>","null")
            ->where_in("env_no",$envno_list)
            ->get("data_sensor")
            ->result_array();
        if(empty($light_arr) ){lineMsg("光照数据不存在！");throw new Exception();}
        $light_list = array_column($light_arr,"light");
        $light_max = max($light_list);
        $light_min = min($light_list);
        $light_avg = array_sum($light_list)/count($light_list);//平均值
        $light_middle = $this->getMiddleValue($light_list); //中位值
        $light_sd = $this->getStandardDeviation($light_avg,$light_list);//标准差

        $light_abnormal_arr = array();
        $light_normal_arr = array();
        foreach($light_arr as $v){
            $Z = abs(($v['light']-$light_avg)/$light_sd);
            if($Z>3) {
                $light_abnormal_arr[] = $v;
            }else{
                $light_normal_arr[] = $v;
            }
        }
        if(empty($light_abnormal_arr)){
            $light_max2 = $light_max;
            $light_min2 = $light_min;
            $light_count_abnormal = 0;
        }else{
            $light_max2 = max(array_column($light_normal_arr,"light"));
            $light_min2 = min(array_column($light_normal_arr,"light"));
            $light_count_abnormal = count($light_abnormal_arr);
        }

        return array(
            "date"=>date("Ymd",$btime),
            "mid"=>$mid,
            "param"=>"light".$classId,
            "max"=>$light_max,
            "min"=>$light_min,
            "max2"=>$light_max2,
            "min2"=>$light_min2,
            "middle"=>$light_middle,
            "average"=>round($light_avg,2),
            "count_abnormal"=>$light_count_abnormal,
            "standard"=>round($light_sd,2),
        );
    }


    //环境类型参数综合统计-温度
    public function count_envtype_temperature($mid,$envId){
        $btime = strtotime('-11 day 00:00:00');
        $etime = strtotime('-11 day 23:59:59');
        if($envId == 1){
            $env = "showroom";
            $envno_arr = $this->db['base']->select("distinct(env_no)")->where("env_no<>","null")->where("type","展厅")->get("env")->result_array();
            $search_envno_list = array_column($envno_arr,"env_no");
        }
        elseif($envId == 2){
            $env = "showcase";
            $envno_arr = $this->db['base']->select("distinct(env_no)")->where("env_no<>","null")->where("type","展柜")->get("env")->result_array();
            $search_envno_list = array_column($envno_arr,"env_no");
        }
        else{
            $env = "storeroom";
            $envno_arr = $this->db['base']->select("distinct(env_no)")->where("env_no<>","null")->where("type","库房")->get("env")->result_array();
            $search_envno_list = array_column($envno_arr,"env_no");
        }
        if(empty($envno_arr)){return false;};
        //昨日温度记录
        $temp_arr = $this->db['env']
            ->select("env_no,equip_no,temperature,equip_time")
            ->where("equip_time>",$btime)->where("equip_time<",$etime)
            ->where("temperature<>","null")
            ->where_in("env_no",$search_envno_list) //展厅/展柜/库房
            ->get("data_sensor")
            ->result_array();
        if(empty($temp_arr)){return false;};
        $temp_list = array_column($temp_arr,"temperature");
        $temp_max = max($temp_list);
        $temp_min = min($temp_list);
        $temp_avg = array_sum($temp_list)/count($temp_list);//平均值
        $temp_middle = $this->getMiddleValue($temp_list); //中位值
        $temp_sd = $this->getStandardDeviation($temp_avg,$temp_list);//标准差

        $temp_abnormal_arr = array();
        $temp_normal_arr = array();
        foreach($temp_arr as $v){
            $Z = abs(($v['temperature']-$temp_avg)/$temp_sd);
            if($Z>3) {
                $temp_abnormal_arr[] = $v;
            }else{
                $temp_normal_arr[] = $v;
            }
        }
        if(empty($temp_abnormal_arr)){
            $temp_max2 = $temp_max;
            $temp_min2 = $temp_min;
            $temp_count_abnormal = 0;
        }else{
            $temp_max2 = max(array_column($temp_normal_arr,"temperature"));
            $temp_min2 = min(array_column($temp_normal_arr,"temperature"));
            $temp_count_abnormal = count($temp_abnormal_arr);
        }

        return array(
            "date"=>date("Ymd",$btime),
            "mid"=>$mid,
            "env_type"=>$env,
            "param"=>"temperature",
            "max"=>$temp_max,
            "min"=>$temp_min,
            "max2"=>$temp_max2,
            "min2"=>$temp_min2,
            "middle"=>$temp_middle,
            "average"=>round($temp_avg,2),
            "count_abnormal"=>$temp_count_abnormal,
            "standard"=>round($temp_sd,2),
        );
    }

    //环境类型参数综合统计-湿度(分环境、材质)
    public function count_envtype_humidity($mid,$envId,$classId){

        $btime = strtotime('-11 day 00:00:00');
        $etime = strtotime('-11 day 23:59:59');
        //筛选展厅/展柜/库房
        if($envId == 1){
            $env = "showroom";
            $envno_arr = $this->db['base']->select("distinct(env_no)")->where("env_no<>","null")->where("type","展厅")->get("env")->result_array();
            $envno_list = array_column($envno_arr,"env_no");
        }
        elseif($envId == 2){
            $env = "showcase";
            $envno_arr = $this->db['base']->select("distinct(env_no)")->where("env_no<>","null")->where("type","展柜")->get("env")->result_array();
            $envno_list = array_column($envno_arr,"env_no");
        }
        else{
            $env = "storeroom";
            $envno_arr = $this->db['base']->select("distinct(env_no)")->where("env_no<>","null")->where("type","库房")->get("env")->result_array();
            $envno_list = array_column($envno_arr,"env_no");
        }

        if(empty($envno_arr)){return false;};
        //筛选湿度3个分类
        if($classId == 1){//"石质","陶器","瓷器"
            $envno_humidity_arr = $this->db['relic']
                ->select("distinct(parent_env_no)")
                ->where("parent_env_no<>","null")
                ->group_start()
                ->like('material', '石', 'both')
                ->or_like('material', '瓷', 'both')
                ->or_like('material', '陶', 'both')
                ->group_end()
                ->get("relic")
                ->result_array();
            $envno_humidity_list = array_column($envno_humidity_arr,"parent_env_no");
        }elseif($classId == 2){//"铁质","青铜"
            $envno_humidity_arr = $this->db['relic']
                ->select("distinct(parent_env_no)")
                ->where("parent_env_no<>","null")
                ->group_start()
                ->like('material', '铁', 'both')
                ->or_like('material', '铜', 'both')
                ->group_end()
                ->get("relic")
                ->result_array();
            $envno_humidity_list = array_column($envno_humidity_arr,"parent_env_no");
        }else{//"纸质","壁画","纺织品","漆木器","其他"
            $envno_humidity_arr = $this->db['relic']
                ->select("distinct(parent_env_no)")
                ->where("parent_env_no<>","null")
                ->group_start()
                ->not_like('material','石')
                ->not_like('material','瓷')
                ->not_like('material','陶')
                ->not_like('material','铁')
                ->not_like('material','铜')
                ->group_end()
                ->get("relic")
                ->result_array();
            $envno_humidity_list = array_column($envno_humidity_arr,"parent_env_no");
        }
        //两组环境编号取交集
        $envno_intersection_list = array_intersect($envno_list,$envno_humidity_list);
        if(empty($envno_intersection_list)){return false;}

        //昨日湿度记录
        $humidity_arr = $this->db['env']
            ->select("env_no,equip_no,humidity,equip_time")
            ->where("equip_time>",$btime)->where("equip_time<",$etime)
            ->where("humidity<>","null")
            ->where_in("env_no",$envno_intersection_list)//分类关联查询
            ->get("data_sensor")
            ->result_array();
        if(empty($humidity_arr)){return false;}
        $humidity_list = array_column($humidity_arr,"humidity");
        $humidity_max = max($humidity_list);
        $humidity_min = min($humidity_list);
        $humidity_avg = array_sum($humidity_list)/count($humidity_list);//平均值
        $humidity_middle = $this->getMiddleValue($humidity_list); //中位值
        $humidity_sd = $this->getStandardDeviation($humidity_avg,$humidity_list);//标准差

        $humidity_abnormal_arr = array();
        $humidity_normal_arr = array();
        foreach($humidity_arr as $v){
            $Z = abs(($v['humidity']-$humidity_avg)/$humidity_sd);
            if($Z>3) {
                $humidity_abnormal_arr[] = $v;
            }else{
                $humidity_normal_arr[] = $v;
            }
        }
        if(empty($humidity_abnormal_arr)){
            $humidity_max2 = $humidity_max;
            $humidity_min2 = $humidity_min;
            $humidity_count_abnormal = 0;
        }else{
            $humidity_max2 = max(array_column($humidity_normal_arr,"humidity"));
            $humidity_min2 = min(array_column($humidity_normal_arr,"humidity"));
            $humidity_count_abnormal = count($humidity_abnormal_arr);
        }

        return array(
            "date"=>date("Ymd",$btime),
            "mid"=>$mid,
            "env_type"=>$env,
            "param"=>"humidity".$classId,
            "max"=>$humidity_max,
            "min"=>$humidity_min,
            "max2"=>$humidity_max2,
            "min2"=>$humidity_min2,
            "middle"=>$humidity_middle,
            "average"=>round($humidity_avg,2),
            "count_abnormal"=>$humidity_count_abnormal,
            "standard"=>round($humidity_sd,2),
        );
    }

    //环境类型参数综合统计-光照(分环境、材质)
    public function count_envtype_light($mid,$envId,$classId)
    {
        $btime = strtotime('-10 day 00:00:00');
        $etime = strtotime('-10 day 23:59:59');
        //筛选展厅/展柜/库房
        if($envId == 1){
            $env = "showroom";
            $envno_arr = $this->db['base']->select("distinct(env_no)")->where("env_no<>","null")->where("type","展厅")->get("env")->result_array();
            $envno_list = array_column($envno_arr,"env_no");
        }
        elseif($envId == 2){
            $env = "showcase";
            $envno_arr = $this->db['base']->select("distinct(env_no)")->where("env_no<>","null")->where("type","展柜")->get("env")->result_array();
            $envno_list = array_column($envno_arr,"env_no");
        }
        else{
            $env = "storeroom";
            $envno_arr = $this->db['base']->select("distinct(env_no)")->where("env_no<>","null")->where("type","库房")->get("env")->result_array();
            $envno_list = array_column($envno_arr,"env_no");
        }

        if(empty($envno_arr)){return false;};
        //光照材质分类
        if($classId == 1){//"石质","陶器","瓷器","铁质","青铜"
            $envno_light_arr = $this->db['relic']
                ->select("distinct(parent_env_no)")
                ->where("parent_env_no<>","null")
                ->group_start()
                ->like('material', '石')
                ->or_like('material', '瓷')
                ->or_like('material', '陶')
                ->or_like('material', '铁')
                ->or_like('material', '铜')
                ->group_end()
                ->get("relic")
                ->result_array();
            $envno_light_list = array_column($envno_light_arr,"parent_env_no");
        }elseif($classId == 2){//"纸质","壁画","纺织品"
            $envno_light_arr = $this->db['relic']
                ->select("distinct(parent_env_no)")
                ->where("parent_env_no<>","null")
                ->group_start()
                ->like('material', '纸')
                ->or_like('material', '画')
                ->or_like('material', '布')
                ->or_like('material', '绸')
                ->group_end()
                ->get("relic")
                ->result_array();
            $envno_light_list = array_column($envno_light_arr,"parent_env_no");
        }else{//"漆木器","其他"
            $envno_light_arr = $this->db['relic']
                ->select("distinct(parent_env_no)")
                ->where("parent_env_no<>","null")
                ->group_start()
                ->not_like('material','石')
                ->not_like('material','瓷')
                ->not_like('material','陶')
                ->not_like('material','铁')
                ->not_like('material','铜')
                ->not_like('material','纸')
                ->not_like('material','画')
                ->not_like('material','布')
                ->not_like('material','绸')
                ->group_end()
                ->get("relic")
                ->result_array();
            $envno_light_list = array_column($envno_light_arr,"parent_env_no");
        }

        $envno_intersection_list = array_intersect($envno_list,$envno_light_list);
        if(empty($envno_intersection_list)){return false;}

        //昨日光照记录(按照材质分3类)
        $light_arr = $this->db['env']
            ->select("env_no,equip_no,light,equip_time")
            ->where("equip_time>",$btime)->where("equip_time<",$etime)
            ->where("light<>","null")
            ->where_in("env_no",$envno_list)
            ->get("data_sensor")
            ->result_array();
        if(empty($light_arr)){return false;}
        $light_list = array_column($light_arr,"light");
        $light_max = max($light_list);
        $light_min = min($light_list);
        $light_avg = array_sum($light_list)/count($light_list);//平均值
        $light_middle = $this->getMiddleValue($light_list); //中位值
        $light_sd = $this->getStandardDeviation($light_avg,$light_list);//标准差

        $light_abnormal_arr = array();
        $light_normal_arr = array();
        foreach($light_arr as $v){
            $Z = abs(($v['light']-$light_avg)/$light_sd);
            if($Z>3) {
                $light_abnormal_arr[] = $v;
            }else{
                $light_normal_arr[] = $v;
            }
        }
        if(empty($light_abnormal_arr)){
            $light_max2 = $light_max;
            $light_min2 = $light_min;
            $light_count_abnormal = 0;
        }else{
            $light_max2 = max(array_column($light_normal_arr,"light"));
            $light_min2 = min(array_column($light_normal_arr,"light"));
            $light_count_abnormal = count($light_abnormal_arr);
        }

        return array(
            "date"=>date("Ymd",$btime),
            "mid"=>$mid,
            "env_type"=>$env,
            "param"=>"light".$classId,
            "max"=>$light_max,
            "min"=>$light_min,
            "max2"=>$light_max2,
            "min2"=>$light_min2,
            "middle"=>$light_middle,
            "average"=>round($light_avg,2),
            "count_abnormal"=>$light_count_abnormal,
            "standard"=>round($light_sd,2),
        );

    }

    //环境类型参数综合统计-紫外
    public function count_envtype_uv($mid,$envId){
        $btime = strtotime('-11 day 00:00:00');
        $etime = strtotime('-11 day 23:59:59');
        //筛选展厅/展柜/库房
        if($envId == 1){
            $env = "showroom";
            $envno_arr = $this->db['base']->select("distinct(env_no)")->where("env_no<>","null")->where("type","展厅")->get("env")->result_array();
            $envno_list = array_column($envno_arr,"env_no");
        }
        elseif($envId == 2){
            $env = "showcase";
            $envno_arr = $this->db['base']->select("distinct(env_no)")->where("env_no<>","null")->where("type","展柜")->get("env")->result_array();
            $envno_list = array_column($envno_arr,"env_no");
        }
        else{
            $env = "storeroom";
            $envno_arr = $this->db['base']->select("distinct(env_no)")->where("env_no<>","null")->where("type","库房")->get("env")->result_array();
            $envno_list = array_column($envno_arr,"env_no");
        }

        if(empty($envno_arr)){return false;};
        //昨日紫外记录
        $uv_arr = $this->db['env']
            ->select("env_no,equip_no,uv,equip_time")
            ->where("equip_time>",$btime)->where("equip_time<",$etime)
            ->where("uv<>","null")
            ->where_in("env_no",$envno_list) // 展厅/展柜/库房
            ->get("data_sensor")
            ->result_array();
        if(empty($uv_arr)){return false;};
        $uv_list = array_column($uv_arr,"uv");
        $uv_max = max($uv_list);
        $uv_min = min($uv_list);
        $uv_avg = array_sum($uv_list)/count($uv_list);//平均值
        $uv_middle = $this->getMiddleValue($uv_list); //中位值
        $uv_sd = $this->getStandardDeviation($uv_avg,$uv_list);//标准差

        $uv_abnormal_arr = array();
        $uv_normal_arr = array();
        foreach($uv_arr as $v){
            $Z = abs(($v['uv']-$uv_avg)/$uv_sd);
            if($Z>3) {
                $uv_abnormal_arr[] = $v;
            }else{
                $uv_normal_arr[] = $v;
            }
        }
        if(empty($uv_abnormal_arr)){
            $uv_max2 = $uv_max;
            $uv_min2 = $uv_min;
            $uv_count_abnormal = 0;
        }else{
            $uv_max2 = max(array_column($uv_normal_arr,"uv"));
            $uv_min2 = min(array_column($uv_normal_arr,"uv"));
            $uv_count_abnormal = count($uv_abnormal_arr);
        }

        return array(
            "date"=>date("Ymd",$btime),
            "mid"=>$mid,
            "env_type"=>$env,
            "param"=>"uv",
            "max"=>$uv_max,
            "min"=>$uv_min,
            "max2"=>$uv_max2,
            "min2"=>$uv_min2,
            "middle"=>$uv_middle,
            "average"=>round($uv_avg,2),
            "count_abnormal"=>$uv_count_abnormal,
            "standard"=>round($uv_sd,2),
        );
    }

    //环境类型参数综合统计-VOC
    public function count_envtype_voc($mid,$envId){
        $btime = strtotime('-11 day 00:00:00');
        $etime = strtotime('-11 day 23:59:59');
        //筛选展厅/展柜/库房
        if($envId == 1){
            $env = "showroom";
            $envno_arr = $this->db['base']->select("distinct(env_no)")->where("env_no<>","null")->where("type","展厅")->get("env")->result_array();
            $envno_list = array_column($envno_arr,"env_no");
        }
        elseif($envId == 2){
            $env = "showcase";
            $envno_arr = $this->db['base']->select("distinct(env_no)")->where("env_no<>","null")->where("type","展柜")->get("env")->result_array();
            $envno_list = array_column($envno_arr,"env_no");
        }
        else{
            $env = "storeroom";
            $envno_arr = $this->db['base']->select("distinct(env_no)")->where("env_no<>","null")->where("type","库房")->get("env")->result_array();
            $envno_list = array_column($envno_arr,"env_no");
        }

        if(empty($envno_arr)){return false;};
        //昨日VOC记录
        $voc_arr = $this->db['env']
            ->select("env_no,equip_no,voc,equip_time")
            ->where("equip_time>",$btime)->where("equip_time<",$etime)
            ->where("voc<>","null")
            ->where_in("env_no",$envno_list) //展厅、展柜、库房
            ->get("data_sensor")
            ->result_array();
        if(empty($voc_arr)){return false;}
        $voc_list = array_column($voc_arr,"voc");
        $voc_max = max($voc_list);
        $voc_min = min($voc_list);
        $voc_avg = array_sum($voc_list)/count($voc_list);//平均值
        $voc_middle = $this->getMiddleValue($voc_list); //中位值
        $voc_sd = $this->getStandardDeviation($voc_avg,$voc_list);//标准差

        $voc_abnormal_arr = array();
        $voc_normal_arr = array();
        foreach($voc_arr as $v){
            $Z = abs(($v['voc']-$voc_avg)/$voc_sd);
            if($Z>3) {
                $voc_abnormal_arr[] = $v;
            }else{
                $voc_normal_arr[] = $v;
            }
        }
        if(empty($voc_abnormal_arr)){
            $voc_max2 = $voc_max;
            $voc_min2 = $voc_min;
            $voc_count_abnormal = 0;
        }else{
            $voc_max2 = max(array_column($voc_normal_arr,"voc"));
            $voc_min2 = min(array_column($voc_normal_arr,"voc"));
            $voc_count_abnormal = count($voc_abnormal_arr);
        }

        return array(
            "date"=>date("Ymd",$btime),
            "mid"=>$mid,
            "env_type"=>$env,
            "param"=>"voc",
            "max"=>$voc_max,
            "min"=>$voc_min,
            "max2"=>$voc_max2,
            "min2"=>$voc_min2,
            "middle"=>$voc_middle,
            "average"=>round($voc_avg,2),
            "count_abnormal"=>$voc_count_abnormal,
            "standard"=>round($voc_sd,2),
        );
    }








}