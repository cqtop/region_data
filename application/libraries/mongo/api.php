<?php defined('BASEPATH') OR exit('No direct script access allowed');

class API{

    public $btime = null;//查询开始时间
    public $etime = null;//查询结束时间
    public $EnvNo = array();//环境编号(展厅/展柜/库房)

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
        $nums = array();
        foreach ($areas as $k => $v){
            $nums["'".$v["_id"]->{'$id'}."'"] = $v["No"];
        }
        foreach ($areas as $k => $v){
            if(array_key_exists("belong",$v)){
                $belong_id = "'".$v["belong"]->{'$id'}."'";
                if(array_key_exists($belong_id,$nums)){
                    $areas[$k]["pid"] = $nums[$belong_id];
                }

            }
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

}
