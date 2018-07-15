<?php if (!defined('BASEPATH')) exit ('No direct script access allowed');

class O2o_push_model extends MY_Model
{
    public function table_name()
    {
        return 'o2o_push';
    }

    public function get_pushes($method, $startTime = '', $endTime = '')
    {
        if (empty($method)) {
            return array();
        }

        if (empty($endTime))
            $endTime = date('Y-m-d H:i:s');

        $rs = $this->db->query('select DISTINCT oid,is_deleted from ttgy_o2o_push where method=? and created_at >= ? and created_at <= ? order by created_at desc'
            , array($method, $startTime, $endTime))
            ->result_array();
        return $rs;
    }

}