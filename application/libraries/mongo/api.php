<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class API{

    public function __construct($param)
    {
        $this->db = $param['db'];
    }

    public function count_relic()
    {
        return $this->db->relic->base->count();
    }

}