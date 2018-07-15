<?php

class Banner_disply_group_model extends MY_Model
{
    public function table_name()
    {
        return 'banner_disply_group';
    }

    /**
     * 获取 banner 分组信息
     */
    public function getDisplayGroup($channelCode = array(), $source = 'app', $region = 106092, $pageCode = 'mobile-index')
    {
        $date = date("Y-m-d H:i:s", time());
        $where = array(
            'bp.page_code' => $pageCode,
            'dg.status' => '1',
            "FIND_IN_SET('" . $source . "', dg.source)" => null,
            "dg.region LIKE '%\"" . $region . "\"%'" => null,
            "((dg.start_time = 0 AND dg.end_time = 0) OR (dg.start_time <= '" . $date . "' AND dg.end_time >= '" . $date . "'))" => null,
        );

        $this->db->select('dg.group_id, dg.banner_type_id, dg.group_title, dg.group_url, dg.end_time, dg.everyday_start_hour, dg.user_type')
                 ->from('banner_display_group AS dg')
                 ->join('banner_channel AS bc', 'dg.channel_id = bc.channel_id', 'left')
                 ->join('banner_page_type AS pt', 'dg.banner_type_id = pt.banner_type_id', 'left')
                 ->join('banner_page AS bp', 'pt.page_id = bp.page_id', 'left')
                 ->where($where)
                 ->where_in('bc.channel_code', $channelCode)
                 ->order_by('dg.sort ASC, dg.group_id DESC');

        $result = $this->db->get()->result_array();
        return $result ?: array();
    }

    /**
     * 获取分组 banner 列表
     */
    public function getDisplayGroupBanner($gruopId = 0)
    {
        $date = date("Y-m-d H:i:s", time());
        $where = array(
            "((b.banner_start_time = 0 AND b.banner_end_time = 0) OR (b.banner_start_time <= '" . $date . "' AND b.banner_end_time >= '" . $date . "'))" => null,
        );

        if (!$gruopId) {
            return array();
        } else {
            $where['bd.group_id'] = $gruopId;
        }

        $this->db->select('b.*')
                 ->from('banner_display AS bd')
                 ->join('banner b', 'bd.banner_id = b.banner_id', 'left')
                 ->where($where)
                 ->order_by('bd.sort');
        $result = $this->db->get()->result_array();
        return $result ?: array();
    }
}
