<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class API{

    public $btime = null;//查询开始时间
    public $etime = null;//查询结束时间
    public $EnvNo = array();//环境编号(展厅/展柜/库房)


    public function __construct($param)
    {
        $this->db = $param['db'];
        $this->CI = & get_instance();
        $this->CI->load->config("texture");
        $this->texture = config_item("texture");
        $this->areas = array();
        $this->museum_id = $param["mid"];
        $this->getArea();

        $this->getEnvNo();

    }

    private function getArea(){
        $ty = array("展厅", "展柜", "库房");
        $areas = $this->db["base"]->select("env_no,parent_env_no,name,type")->where_in("type", $ty)->get("env")->result_array();
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
        $env_type = array(1=>"展厅", 2=>"展柜", 3=>"库房");
        foreach($env_type as $k=>$v){
            $envno_arr = $this->db['base']
                ->select("distinct(env_no)")
                ->where("env_no<>","null")
                ->where("type",$v)
                ->get("env")
                ->result_array();
            $this->EnvNo[$k] = array_column($envno_arr,"env_no");
        }
    }


    //博物馆基础数据-馆藏文物数量
    public function count_relic(){
        return $this->db['relic']->count_all_results('relic');
    }
    //博物馆基础数据-珍贵文物数量
    public function count_precious_relic(){
        return $this->db['relic']
            ->where_in("level",array("一级文物","二级文物","三级文物"))
            ->count_all_results('relic');
    }
    //博物馆基础数据-展柜数量
    public function count_showcase(){
        return $this->db['base']->where("type","展柜")->count_all_results("env");
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
        if(!$this->EnvNo[$env_id]) return false;

        $data['date'] = $date_str;
        $data['env_type'] = $env_type[$env_id];
        $data['mid'] = $this->museum_id;
        $data['scatter_temperature'] = $this->count_scatter($env_id,'temperature');
        $data['scatter_humidity'] = $this->count_scatter($env_id,'humidity');
        //各种环境参数达标和未达标总和
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
        $Arr = $this->db['env']
            ->select($type)
            ->where("equip_time>", $this->btime)
            ->where("equip_time<",$this->etime)
            ->where("$type<>","null")
            ->where_in("env_no",$this->EnvNo[$env_id])
            ->get("data_sensor")
            ->result_array();

        if(!$Arr) return null;
        $list = array_column($Arr,$type);//转为一维
        $avg = array_sum($list)/count($list);//平均值
        $sd = $this->getStandardDeviation($avg,$list); //标准差

        return round($sd/$avg,3);//离散系数
    }
    //博物馆综合统计-各环境参数达标总和未达标总和
    public function count_total_abnormal($env_id){
        $env_param = array("temperature","humidity","light","uv","voc");
        $alldatas =  $this->db['env']
            ->select("temperature,humidity,light,uv,voc,alert_param")
            ->where("equip_time>", $this->btime)
            ->where("equip_time<",$this->etime)
            ->where_in("env_no",$this->EnvNo[$env_id])
            ->get("data_sensor")->result_array();
        //var_dump($alldatas);
        foreach($env_param as $param){
            $normal = $abnormal = array();
            foreach($alldatas as $data){
                if($data[$param]){
                    if(strpos($data['alert_param'], $param) !== false) $abnormal[] = $data[$param];//存在告警参数
                    else $normal[] = $data[$param];
                }
            }
            $ret[$param]["total"] = count($normal)+count($abnormal);
            $ret[$param]["abnormal"] = count($abnormal);
        }

        return $ret;
    }




}