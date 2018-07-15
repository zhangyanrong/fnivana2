<?php

class Ad_tab_model extends MY_Model
{
    public function table_name()
    {
        return 'b2o_ad_tab';
    }

    /**
     * 根据门店ID获取有效的广告Tab信息
     *
     * @param array|string $storeId
     * @param string $field
     * @return array
     */
    public function getTabByStore($storeId, $field = "at.*")
    {
        if (is_string($storeId)) {
            $storeId = explode(',', $storeId);
        }

        $this->db->select($field)
                 ->from('b2o_ad_tab at')
                 ->join('b2o_ad_tab_store ats', 'at.id = ats.tab_id', 'left')
                 ->group_by('at.id')
                 ->order_by('at.sort DESC');
        $this->_filter([
            'at.status' => '1',
            'ats.store_id' => $storeId,
            'at.url' => '',
        ]);
        $list = $this->db->get()->result_array();
        return $list ?: [];
    }
}
