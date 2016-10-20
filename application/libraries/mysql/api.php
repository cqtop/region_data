<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class API{
    
    public function __construct($param)
    {
        $this->db = $param['db'];
    }

    //馆藏文物数量
    public function count_relic()
    {
        return $this->db['relic']->count_all_results('relic');
    }

    //珍贵文物数量
    public function count_precious_relic()
    {
        return $this->db['relic']->count_all_results('precious_relic');
    }

    //固定展览馆数量
    public function count_fixed_exhibition()
    {
        return $this->db['relic']->count_all_results('fixed_exhibition');
    }

    //临时展览馆数量
    public function count_temporary_exhibition()
    {
        return $this->db['relic']->count_all_results('temporary_exhibition');
    }

}