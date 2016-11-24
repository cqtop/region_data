<?php
/**
 * Created by PhpStorm.
 * User: USER
 * Date: 2016/11/23
 * Time: 14:18
 */
class MY_library{
    protected $btime = null;//查询开始时间
    protected $etime = null;//查询结束时间
    protected $date_start = null;//周月查询开始日期
    protected $date_end = null;//周月查询结束日期
    protected $EnvNo = array();//环境编号(展厅/展柜/库房)
    protected $CI = null;
    protected $texture_no = array();
    protected $hall = array();
    protected $showcase = array();
    protected $storeroom = array();
    protected $env_names = array();
    protected $texture = null;
    protected $areas = null;
    protected $museum_id = null;
    protected $day = false;
    
    function __construct()
    {
        $this->CI = & get_instance();
        $this->CI->load->config("texture");
        $this->texture = config_item("texture");
        $this->areas = array();
    }

    protected function calculate($param,$ty,$date,$arr_areano,$alerts_no,$p){
        $data = array(
            "env_type"=>$ty,
            "param"=>$param,
            "mid"=>$this->museum_id,
            "date"=>$date
        );
        $data["abnormal"] = array();//异常数据
        $data["wave_arr"] = array();//日波动超标数据

        $abnormal = 0;
        $range = $range_normal = $arr = $arr_normal =  array();
        $range_areano = $range_normal_areano = array();
        $area_no_normal = array();
        foreach ($arr_areano as $area_no => $value){
            $datas = array();
            foreach ($value as $v){
                $datas[] = $arr[] = $v["data"];
            }
            $range[] = $range_areano[$area_no][] = max($datas) - min($datas);
        }

        $average = sizeof($arr)?round(array_sum($arr)/sizeof($arr),2):0;
        $sum = 0;
        foreach ($arr as $k =>$v){
            $sum += pow($v - $average,2);
        }
        $standard = sizeof($arr)?sqrt($sum/sizeof($arr)):0;//标准差
        asort($arr);
        if(sizeof($arr)%2 == 0){ //中位值
            $end = intval(sizeof($arr)/2);
            $flag = $arr[$end-1] + $arr[$end];
            $data["middle"] = round($flag/2,2);
        }else{
            $data["middle"] = $arr[intval((sizeof($arr)+1)/2)-1];
        }
        $data["standard"] = round($standard,2);
        $data["max"] = max($arr);
        $data["min"] = min($arr);

        foreach ($arr_areano as $area_no => $value){
            foreach ($value as $v) {
                $z = $data["standard"]?($v["data"] - $average) / $data["standard"]:0;
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
                    $area_no_normal[$area_no][] = $arr_normal[] = $v["data"];
                }
            }
        }

        $average_normal = sizeof($arr_normal)?round(array_sum($arr_normal)/sizeof($arr_normal),2):0;
        $data["average"] = $average_normal;

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
        if(($p == "temperature" || $p == "humidity") && $this->day){
            $data["wave"] = $min_range.",".$max_range.",".$min_range_normal.",".$max_range_normal;
            $data["wave_status"] = $num?$num:($num_normal?$num_normal:0);
        }
        $data["count_abnormal"] = $abnormal;
        $data["compliance"] = sizeof($arr)?round((sizeof($arr) - $alerts_no)/sizeof($arr),2):0;
        return $data;
    }


    //统计函数-计算标准差
    protected function getStandardDeviation($avg, $list)
    {
        $total_var = 0;
        foreach ($list as $lv){
            $total_var += pow( ($lv - $avg), 2 );
        }
        return sqrt( $total_var / (count($list) ) );
    }
    //统计函数-计算中位值
    protected function getMiddleValue($list){
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
    protected function getAbnormalValue($list){
        $avg = array_sum($list)/count($list);
        $sd = $this->getStandardDeviation($avg,$list);
        foreach($list as $v){
            $Z = abs(($v-$avg)/$sd);
            if($Z>3) return true;
        }
        return false;
    }
    //生成周/月日期列表
    protected function _date_list($s,$e){
        $date = array();
        for ($i = strtotime($s); $i <= strtotime($e); $i += 86400) {
            $date[] = "D". date("Ymd", $i);
        }
        return $date;
    }
    
}