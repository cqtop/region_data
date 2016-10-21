<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class API{

    public function __construct($param)
    {
        //$this->db = $param['db'];
        require_once("Mongo_db.php");
        $this->mongo_db = new Mongo_db($param['db']);
    }

    public function count_relic()
    {
        //return $this->db->relic->base->count();
        return $this->mongo_db->count("relic.base");
    }
    
    public function env_param_minmax(){
        
    }

    public function scatter() //离散系数
    {
        $ty = array("展厅", "展柜", "库房");
        $ids = $areanos = array();
        //$now_time = time();
        $now_time = strtotime("2016-07-27");
        $tyid = $this->mongo_db->select(array("_id"))->where_in("name", $ty)->get("area.type");
        foreach ($tyid as $v) {
            $ids[] = $v["_id"];
        }
        $areas = $this->mongo_db->select(array("No"))->where_in("type", $ids)->get("area.base");
        foreach ($areas as $v) {
            $areanos[] = $v["No"];
        }
        $datas = $this->mongo_db->select(array("param"))->where_in("areano", $areanos)->where_between("receivetime", $now_time - 24 * 60 * 60, $now_time)->get("data.sensor.".date("Y"));

    }
    
    
}