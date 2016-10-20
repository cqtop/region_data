<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class API{
    
    public function __construct($param)
    {
        $this->db = $param['db'];
    }

    //基础数据-馆藏文物数量
    public function count_relic()
    {
        return $this->db['relic']->count_all_results('relic');
    }

    //基础数据-珍贵文物数量
    public function count_precious_relic()
    {
        //待确认
        return 0;
        return $this->db['relic']->count_all_results('precious_relic');
    }

    //基础数据-固定展览馆数量
    public function count_fixed_exhibition()
    {
        //待确认
        return 0;
        return $this->db['relic']->count_all_results('fixed_exhibition');
    }

    //基础数据-临时展览馆数量
    public function count_temporary_exhibition()
    {
        //待确认
        return 0;
        return $this->db['relic']->count_all_results('temporary_exhibition');
    }

    //综合统计-达标率
    public function count_standard()
    {
        //待确认
        return 0;
    }

    //综合统计-温度离散系数
    public function count_scatter_temp()
    {
        //待确认
        return 0;
    }

    //综合统计-湿度离散系数
    public function count_scatter_humidity()
    {
        //待确认
        return 0;
    }

    //综合统计-是否有日波动超标
    public function count_is_wave_abnormal()
    {
        //待确认
        return 0;
    }

    //综合统计-是否有异常值
    public function count_is_value_abnormal()
    {
        //待确认
        return 0;
    }

    //环境指标综合统计-湿度
    public function count_param_humidity($mid,$classId,$paramStr){
        switch($classId){
            case 1;//("石质","陶器","瓷器")
                /*......*/
                return array(
                    "date"=>date("Ymd"),
                    "mid"=>$mid,
                    "param"=>"湿度".$paramStr,
                    "max"=>0,
                    "min"=>0,
                    "middle"=>0,
                    "average"=>0,
                    "count_abnormal"=>0,
                    "max2"=>0,
                    "min2"=>0,
                    "standard"=>0,
                    "compliance"=>0
                );
                break;
            case 2;//("铁质","青铜")
                /*......*/
                return array(
                    "date"=>date("Ymd"),
                    "mid"=>$mid,
                    "param"=>"湿度".$paramStr,
                    "max"=>0,
                    "min"=>0,
                    "middle"=>0,
                    "average"=>0,
                    "count_abnormal"=>0,
                    "max2"=>0,
                    "min2"=>0,
                    "standard"=>0,
                    "compliance"=>0
                );
                break;
            case 3;//("纸质","壁画","纺织品","漆木器")
                /*.....*/
                return array(
                    "date"=>date("Ymd"),
                    "mid"=>$mid,
                    "param"=>"湿度".$paramStr,
                    "max"=>0,
                    "min"=>0,
                    "middle"=>0,
                    "average"=>0,
                    "count_abnormal"=>0,
                    "max2"=>0,
                    "min2"=>0,
                    "standard"=>0,
                    "compliance"=>0
                );
                break;
            default:
                break;
        }

    }








}