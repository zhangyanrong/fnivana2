<?php

class Ad_model extends MY_Model
{
    public function table_name()
    {
        return 'b2o_ad';
    }

    /**
     * 根据门店ID获取有效的广告信息
     *
     * @param array|string $storeId
     * @param string $field
     * @param array $filter
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getAdByStore($storeId, $field = "a.*", $filter, $offset = 0, $limit = -1)
    {
        if (is_string($storeId)) {
            $storeId = explode(',', $storeId);
        }

        $where = array_merge([
            'ast.store_id' => $storeId,
        ], $filter);

        $this->db->select($field)
                 ->from('b2o_ad a')
                 ->join('b2o_ad_store ast', 'a.id = ast.ad_id', 'left')
                 ->group_by('a.id')
                 ->order_by('a.sort DESC');
        $this->_filter($where);
        if ($limit < 0) $limit = '4294967295';
        $this->db->limit($limit, $offset);
        $list = $this->db->get()->result_array();
        return $list ?: [];
    }
}
