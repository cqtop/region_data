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

    //博物馆基础数据-馆藏文物数量
    public function count_relic()
    {
        return $this->db['relic']->count_all_results('relic');
    }

    //博物馆基础数据-珍贵文物数量
    public function count_precious_relic()
    {
        //待确认
        return 0;
        return $this->db['relic']->count_all_results('precious_relic');
    }

    //博物馆基础数据-固定展览文物数量
    public function count_fixed_exhibition()
    {
        //待确认
        return 0;
        return $this->db['relic']->count_all_results('fixed_exhibition');
    }

    //博物馆基础数据-临时展览文物数量
    public function count_temporary_exhibition()
    {
        //待确认
        return 0;
        return $this->db['relic']->count_all_results('temporary_exhibition');
    }
    //博物馆基础数据-展柜数量
    public function count_showcase()
    {
        return $this->db['base']->where("type","展柜")->count_all_results("env");
    }

    //博物馆综合统计-温度离散系数
    public function count_scatter_temp()
    {
        $btime = strtotime('-10 day 00:00:00');
        $etime = strtotime('-1 day 23:59:59');

        $tempArr = $this->db['env']
            //->query("select temperature from data_sensor where (equip_time between $btime and $etime) and temperature is not null")
            ->select("temperature")
            ->where("equip_time>",$btime)->where("equip_time<",$etime)->where("temperature<>","null")
            ->get("data_sensor")
            ->result_array();

        if(empty($tempArr)){throw new Exception();}
        $templist = array_column($tempArr,"temperature");//转为一维温度数组
        $avg = array_sum($templist)/count($templist);//平均值
        $sd = $this->getStandardDeviation($avg,$templist); //标准差

        return round($sd/$avg,2);
    }

    //博物馆综合统计-湿度离散系数
    public function count_scatter_humidity()
    {
        $btime = strtotime('-10 day 00:00:00');
        $etime = strtotime('-10 day 23:59:59');

        $humidityArr = $this->db['env']
            ->select("humidity")
            ->where("equip_time>",$btime)->where("equip_time<",$etime)->where("humidity<>","null")
            ->get("data_sensor")
            ->result_array();

        if(empty($humidityArr)){throw new Exception();}
        $humiditylist = array_column($humidityArr,"humidity");//转为一维数组
        $avg = array_sum($humiditylist)/count($humiditylist);//平均值
        $sd = $this->getStandardDeviation($avg,$humiditylist); //标准差

        return round($sd/$avg,2);
    }

    //博物馆综合统计-是否有日波动超标
    public function count_is_wave_abnormal()
    {
        
        return 0;
    }

    //博物馆综合统计-是否有异常值
    public function count_is_value_abnormal()
    {
        //待确认
        return 0;
    }

    //博物馆参数综合统计-温度
    public function count_param_temperature($mid){
        /*...*/
        return array(
            "date"=>date("Ymd"),
            "mid"=>$mid,
            "param"=>"temperature",
            "max"=>0,
            "min"=>0,
            "max2"=>0,
            "min2"=>0,
            "middle"=>0,
            "average"=>0,
            "count_abnormal"=>0,
            "standard"=>0,
        );
    }

    //博物馆参数综合统计-紫外
    public function count_param_uv($mid){
        /*...*/
        return array(
            "date"=>date("Ymd"),
            "mid"=>$mid,
            "param"=>"uv",
            "max"=>0,
            "min"=>0,
            "max2"=>0,
            "min2"=>0,
            "middle"=>0,
            "average"=>0,
            "count_abnormal"=>0,
            "standard"=>0,
        );
    }

    //博物馆参数综合统计-voc
    public function count_param_voc($mid){
        /*...*/
        return array(
            "date"=>date("Ymd"),
            "mid"=>$mid,
            "param"=>"voc",
            "max"=>0,
            "min"=>0,
            "max2"=>0,
            "min2"=>0,
            "middle"=>0,
            "average"=>0,
            "count_abnormal"=>0,
            "standard"=>0,
        );
    }

    //博物馆参数综合统计-湿度
    public function count_param_humidity($mid,$classId){
        switch($classId){
            case 1;//("石质","陶器","瓷器")
                /*......*/
                return array(
                    "date"=>date("Ymd"),
                    "mid"=>$mid,
                    "param"=>"humidity".$classId,
                    "max"=>0,
                    "min"=>0,
                    "max2"=>0,
                    "min2"=>0,
                    "middle"=>0,
                    "average"=>0,
                    "count_abnormal"=>0,
                    "standard"=>0,
                );
                break;
            case 2;//("铁质","青铜")
                /*......*/
                return array(
                    "date"=>date("Ymd"),
                    "mid"=>$mid,
                    "param"=>"humidity".$classId,
                    "max"=>0,
                    "min"=>0,
                    "max2"=>0,
                    "min2"=>0,
                    "middle"=>0,
                    "average"=>0,
                    "count_abnormal"=>0,
                    "standard"=>0,
                );
                break;
            case 3;//("纸质","壁画","纺织品","漆木器")
                /*.....*/
                return array(
                    "date"=>date("Ymd"),
                    "mid"=>$mid,
                    "param"=>"humidity".$classId,
                    "max"=>0,
                    "min"=>0,
                    "max2"=>0,
                    "min2"=>0,
                    "middle"=>0,
                    "average"=>0,
                    "count_abnormal"=>0,
                    "standard"=>0,
                );
                break;
            default:
                break;
        }

    }

    //博物馆参数综合统计-光照
    public function count_param_light($mid,$classId)
    {
        switch($classId){
            case 1;//("石质","陶器","瓷器","铁质","青铜")
                /*......*/
                return array(
                    "date"=>date("Ymd"),
                    "mid"=>$mid,
                    "param"=>"light".$classId,
                    "max"=>0,
                    "min"=>0,
                    "max2"=>0,
                    "min2"=>0,
                    "middle"=>0,
                    "average"=>0,
                    "count_abnormal"=>0,
                    "standard"=>0,
                );
                break;
            case 2;//("纸质","壁画","纺织品")
                /*......*/
                return array(
                    "date"=>date("Ymd"),
                    "mid"=>$mid,
                    "param"=>"light".$classId,
                    "max"=>0,
                    "min"=>0,
                    "max2"=>0,
                    "min2"=>0,
                    "middle"=>0,
                    "average"=>0,
                    "count_abnormal"=>0,
                    "standard"=>0,
                );
                break;
            case 3;//("漆木器")
                /*.....*/
                return array(
                    "date"=>date("Ymd"),
                    "mid"=>$mid,
                    "param"=>"light".$classId,
                    "max"=>0,
                    "min"=>0,
                    "max2"=>0,
                    "min2"=>0,
                    "middle"=>0,
                    "average"=>0,
                    "count_abnormal"=>0,
                    "standard"=>0,
                );
                break;
            default:
                break;
        }


    }


    //环境类型参数综合统计-展厅-温度
    public function count_envtype_showroom_temperature($mid){
        /*...*/
        return array(
            "date"=>date("Ymd"),
            "mid"=>$mid,
            "env_type"=>"showroom",
            "param"=>"temperature",
            "max"=>0,
            "min"=>0,
            "max2"=>0,
            "min2"=>0,
            "middle"=>0,
            "average"=>0,
            "count_abnormal"=>0,
            "standard"=>0,
        );
    }

    //环境类型参数综合统计-展厅-湿度
    public function count_envtype_showroom_humidity($mid,$classId){
        /*....*/
        switch($classId){
            case 1;//("石质","陶器","瓷器")
                /*......*/
                return array(
                    "date"=>date("Ymd"),
                    "mid"=>$mid,
                    "env_type"=>"showroom",
                    "param"=>"humidity".$classId,
                    "max"=>0,
                    "min"=>0,
                    "max2"=>0,
                    "min2"=>0,
                    "middle"=>0,
                    "average"=>0,
                    "count_abnormal"=>0,
                    "standard"=>0,
                );
                break;
            case 2;//("铁质","青铜")
                /*......*/
                return array(
                    "date"=>date("Ymd"),
                    "mid"=>$mid,
                    "env_type"=>"showroom",
                    "param"=>"humidity".$classId,
                    "max"=>0,
                    "min"=>0,
                    "max2"=>0,
                    "min2"=>0,
                    "middle"=>0,
                    "average"=>0,
                    "count_abnormal"=>0,
                    "standard"=>0,
                );
                break;
            case 3;//("纸质","壁画","纺织品","漆木器")
                /*.....*/
                return array(
                    "date"=>date("Ymd"),
                    "mid"=>$mid,
                    "env_type"=>"showroom",
                    "param"=>"humidity".$classId,
                    "max"=>0,
                    "min"=>0,
                    "max2"=>0,
                    "min2"=>0,
                    "middle"=>0,
                    "average"=>0,
                    "count_abnormal"=>0,
                    "standard"=>0,
                );
                break;
            default:
                break;
        }

    }

    //环境类型参数综合统计-展厅-光照
    public function count_envtype_showroom_light($mid,$classId)
    {
        /*........*/
        switch($classId){
            case 1;//("石质","陶器","瓷器","铁质","青铜")
                /*......*/
                return array(
                    "date"=>date("Ymd"),
                    "mid"=>$mid,
                    "env_type"=>"showroom",
                    "param"=>"light".$classId,
                    "max"=>0,
                    "min"=>0,
                    "max2"=>0,
                    "min2"=>0,
                    "middle"=>0,
                    "average"=>0,
                    "count_abnormal"=>0,
                    "standard"=>0,
                );
                break;
            case 2;//("纸质","壁画","纺织品")
                /*......*/
                return array(
                    "date"=>date("Ymd"),
                    "mid"=>$mid,
                    "env_type"=>"showroom",
                    "param"=>"light".$classId,
                    "max"=>0,
                    "min"=>0,
                    "max2"=>0,
                    "min2"=>0,
                    "middle"=>0,
                    "average"=>0,
                    "count_abnormal"=>0,
                    "standard"=>0,
                );
                break;
            case 3;//("漆木器")
                /*.....*/
                return array(
                    "date"=>date("Ymd"),
                    "mid"=>$mid,
                    "env_type"=>"showroom",
                    "param"=>"light".$classId,
                    "max"=>0,
                    "min"=>0,
                    "max2"=>0,
                    "min2"=>0,
                    "middle"=>0,
                    "average"=>0,
                    "count_abnormal"=>0,
                    "standard"=>0,
                );
                break;
            default:
                break;
        }


    }

    //环境类型参数综合统计-展厅-紫外
    public function count_envtype_showroom_uv($mid){
        /*...*/
        return array(
            "date"=>date("Ymd"),
            "mid"=>$mid,
            "env_type"=>"showroom",
            "param"=>"uv",
            "max"=>0,
            "min"=>0,
            "max2"=>0,
            "min2"=>0,
            "middle"=>0,
            "average"=>0,
            "count_abnormal"=>0,
            "standard"=>0,
        );
    }

    //环境类型参数综合统计-展厅-VOC
    public function count_envtype_showroom_voc($mid){
        /*...*/
        return array(
            "date"=>date("Ymd"),
            "mid"=>$mid,
            "env_type"=>"showroom",
            "param"=>"voc",
            "max"=>0,
            "min"=>0,
            "max2"=>0,
            "min2"=>0,
            "middle"=>0,
            "average"=>0,
            "count_abnormal"=>0,
            "standard"=>0,
        );
    }

    //环境类型参数综合统计-展柜-温度
    public function count_envtype_showcase_temperature($mid){
        /*...*/
        return array(
            "date"=>date("Ymd"),
            "mid"=>$mid,
            "env_type"=>"showcase",
            "param"=>"temperature",
            "max"=>0,
            "min"=>0,
            "max2"=>0,
            "min2"=>0,
            "middle"=>0,
            "average"=>0,
            "count_abnormal"=>0,
            "standard"=>0,
        );
    }

    //环境类型参数综合统计-展柜-湿度
    public function count_envtype_showcase_humidity($mid,$classId){
        /*....*/
        switch($classId){
            case 1;//("石质","陶器","瓷器")
                /*......*/
                return array(
                    "date"=>date("Ymd"),
                    "mid"=>$mid,
                    "env_type"=>"showcase",
                    "param"=>"humidity".$classId,
                    "max"=>0,
                    "min"=>0,
                    "max2"=>0,
                    "min2"=>0,
                    "middle"=>0,
                    "average"=>0,
                    "count_abnormal"=>0,
                    "standard"=>0,
                );
                break;
            case 2;//("铁质","青铜")
                /*......*/
                return array(
                    "date"=>date("Ymd"),
                    "mid"=>$mid,
                    "env_type"=>"showcase",
                    "param"=>"humidity".$classId,
                    "max"=>0,
                    "min"=>0,
                    "max2"=>0,
                    "min2"=>0,
                    "middle"=>0,
                    "average"=>0,
                    "count_abnormal"=>0,
                    "standard"=>0,
                );
                break;
            case 3;//("纸质","壁画","纺织品","漆木器")
                /*.....*/
                return array(
                    "date"=>date("Ymd"),
                    "mid"=>$mid,
                    "env_type"=>"showcase",
                    "param"=>"humidity".$classId,
                    "max"=>0,
                    "min"=>0,
                    "max2"=>0,
                    "min2"=>0,
                    "middle"=>0,
                    "average"=>0,
                    "count_abnormal"=>0,
                    "standard"=>0,
                );
                break;
            default:
                break;
        }
    }

    //环境类型参数综合统计-展柜-光照
    public function count_envtype_showcase_light($mid,$classId)
    {
        switch($classId){
            case 1;//("石质","陶器","瓷器","铁质","青铜")
                /*......*/
                return array(
                    "date"=>date("Ymd"),
                    "mid"=>$mid,
                    "env_type"=>"showcase",
                    "param"=>"light".$classId,
                    "max"=>0,
                    "min"=>0,
                    "max2"=>0,
                    "min2"=>0,
                    "middle"=>0,
                    "average"=>0,
                    "count_abnormal"=>0,
                    "standard"=>0,
                );
                break;
            case 2;//("纸质","壁画","纺织品")
                /*......*/
                return array(
                    "date"=>date("Ymd"),
                    "mid"=>$mid,
                    "env_type"=>"showcase",
                    "param"=>"light".$classId,
                    "max"=>0,
                    "min"=>0,
                    "max2"=>0,
                    "min2"=>0,
                    "middle"=>0,
                    "average"=>0,
                    "count_abnormal"=>0,
                    "standard"=>0,
                );
                break;
            case 3;//("漆木器")
                /*.....*/
                return array(
                    "date"=>date("Ymd"),
                    "mid"=>$mid,
                    "env_type"=>"showcase",
                    "param"=>"light".$classId,
                    "max"=>0,
                    "min"=>0,
                    "max2"=>0,
                    "min2"=>0,
                    "middle"=>0,
                    "average"=>0,
                    "count_abnormal"=>0,
                    "standard"=>0,
                );
                break;
            default:
                break;
        }


    }

    //环境类型参数综合统计-展柜-紫外
    public function count_envtype_showcase_uv($mid){
        /*...*/
        return array(
            "date"=>date("Ymd"),
            "mid"=>$mid,
            "env_type"=>"showcase",
            "param"=>"uv",
            "max"=>0,
            "min"=>0,
            "max2"=>0,
            "min2"=>0,
            "middle"=>0,
            "average"=>0,
            "count_abnormal"=>0,
            "standard"=>0,
        );
    }

    //环境类型参数综合统计-展柜-VOC
    public function count_envtype_showcase_voc($mid){
        /*...*/
        return array(
            "date"=>date("Ymd"),
            "mid"=>$mid,
            "env_type"=>"showcase",
            "param"=>"voc",
            "max"=>0,
            "min"=>0,
            "max2"=>0,
            "min2"=>0,
            "middle"=>0,
            "average"=>0,
            "count_abnormal"=>0,
            "standard"=>0,
        );
    }

//环境类型参数综合统计-库房-温度
    public function count_envtype_storeroom_temperature($mid){
        /*...*/
        return array(
            "date"=>date("Ymd"),
            "mid"=>$mid,
            "env_type"=>"storeroom",
            "param"=>"temperature",
            "max"=>0,
            "min"=>0,
            "max2"=>0,
            "min2"=>0,
            "middle"=>0,
            "average"=>0,
            "count_abnormal"=>0,
            "standard"=>0,
        );
    }

    //环境类型参数综合统计-库房-湿度
    public function count_envtype_storeroom_humidity($mid,$classId){
        /*....*/
        switch($classId){
            case 1;//("石质","陶器","瓷器")
                /*......*/
                return array(
                    "date"=>date("Ymd"),
                    "mid"=>$mid,
                    "env_type"=>"storeroom",
                    "param"=>"humidity".$classId,
                    "max"=>0,
                    "min"=>0,
                    "max2"=>0,
                    "min2"=>0,
                    "middle"=>0,
                    "average"=>0,
                    "count_abnormal"=>0,
                    "standard"=>0,
                );
                break;
            case 2;//("铁质","青铜")
                /*......*/
                return array(
                    "date"=>date("Ymd"),
                    "mid"=>$mid,
                    "env_type"=>"storeroom",
                    "param"=>"humidity".$classId,
                    "max"=>0,
                    "min"=>0,
                    "max2"=>0,
                    "min2"=>0,
                    "middle"=>0,
                    "average"=>0,
                    "count_abnormal"=>0,
                    "standard"=>0,
                );
                break;
            case 3;//("纸质","壁画","纺织品","漆木器")
                /*.....*/
                return array(
                    "date"=>date("Ymd"),
                    "mid"=>$mid,
                    "env_type"=>"storeroom",
                    "param"=>"humidity".$classId,
                    "max"=>0,
                    "min"=>0,
                    "max2"=>0,
                    "min2"=>0,
                    "middle"=>0,
                    "average"=>0,
                    "count_abnormal"=>0,
                    "standard"=>0,
                );
                break;
            default:
                break;
        }
    }

    //环境类型参数综合统计-库房-光照
    public function count_envtype_storeroom_light($mid,$classId)
    {
        /*.......*/
        switch($classId){
            case 1;//("石质","陶器","瓷器","铁质","青铜")
                /*......*/
                return array(
                    "date"=>date("Ymd"),
                    "mid"=>$mid,
                    "env_type"=>"storeroom",
                    "param"=>"light".$classId,
                    "max"=>0,
                    "min"=>0,
                    "max2"=>0,
                    "min2"=>0,
                    "middle"=>0,
                    "average"=>0,
                    "count_abnormal"=>0,
                    "standard"=>0,
                );
                break;
            case 2;//("纸质","壁画","纺织品")
                /*......*/
                return array(
                    "date"=>date("Ymd"),
                    "mid"=>$mid,
                    "env_type"=>"storeroom",
                    "param"=>"light".$classId,
                    "max"=>0,
                    "min"=>0,
                    "max2"=>0,
                    "min2"=>0,
                    "middle"=>0,
                    "average"=>0,
                    "count_abnormal"=>0,
                    "standard"=>0,
                );
                break;
            case 3;//("漆木器")
                /*.....*/
                return array(
                    "date"=>date("Ymd"),
                    "mid"=>$mid,
                    "env_type"=>"storeroom",
                    "param"=>"light".$classId,
                    "max"=>0,
                    "min"=>0,
                    "max2"=>0,
                    "min2"=>0,
                    "middle"=>0,
                    "average"=>0,
                    "count_abnormal"=>0,
                    "standard"=>0,
                );
                break;
            default:
                break;
        }


    }

    //环境类型参数综合统计-库房-紫外
    public function count_envtype_storeroom_uv($mid){
        /*...*/
        return array(
            "date"=>date("Ymd"),
            "mid"=>$mid,
            "env_type"=>"storeroom",
            "param"=>"uv",
            "max"=>0,
            "min"=>0,
            "max2"=>0,
            "min2"=>0,
            "middle"=>0,
            "average"=>0,
            "count_abnormal"=>0,
            "standard"=>0,
        );
    }

    //环境类型参数综合统计-库房-VOC
    public function count_envtype_storeroom_voc($mid){
        /*...*/
        return array(
            "date"=>date("Ymd"),
            "mid"=>$mid,
            "env_type"=>"storeroom",
            "param"=>"voc",
            "max"=>0,
            "min"=>0,
            "max2"=>0,
            "min2"=>0,
            "middle"=>0,
            "average"=>0,
            "count_abnormal"=>0,
            "standard"=>0,
        );
    }






}