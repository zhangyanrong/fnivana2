<?php

/**
 * 分类
 *
 * */
class Cat_model extends MY_Model {

	/**
	 * 分类表
	 *
	 * @var string
	 * */
	const _TABLE_NAME = 'class';

	public function table_name() {
		return self::_TABLE_NAME;
	}

	public function selectClass($regionId, $source, $where = array(), $cangId = '') {
        $where["c.is_show"] = 1;
        $where["p.is_tuan"] = 0;
        $where["p.lack"] = 0;
        switch ($source) {
            case 'pc':
                $where['online'] = 1;
                break;
            case 'app':
                $where['app_online'] = 1;
                break;
            case 'wap':
                $where['mobile_online'] = 1;
                break;
            default:
                break;
        }

        $isUseCang = $this->config->item('is_enable_cang');
        if ($isUseCang) { // warehouse
            $where['(p.cang_id LIKE \'' . $cangId . ',%\' OR p.cang_id LIKE \'%,' . $cangId . ',%\' OR p.cang_id LIKE \'%,' . $cangId . '\' OR p.cang_id = \'' . $cangId . '\')'] = null;
        } else { // region
            $where['p.send_region LIKE \'%"' . $regionId . '"%\''] = null;
        }

        $this->db->select("c.*")
                 ->from("product AS p")
                 ->join("pro_class AS pc", "p.id=pc.product_id", "left")
                 ->join("class AS c", "pc.class_id=c.id")
                 ->where($where)
                 ->group_by("c.id")
                 ->order_by("c.parent_id ASC, c.order_id ASC");

        if ($source != 'pc') {
            // mobile 隐藏 全部生鲜、礼品券卡
            $this->db->where_not_in('c.id', [277, 43]);
        }

        $result = $this->db->get()->result_array();
        return $result;
	}

	public function selectClassName($id) {
		$this->db->select('name');
		$this->db->from("class");
		$this->db->where(array('id' => $id));
		$query = $this->db->get();
		$result = $query->row_array();
		return $result['name'];
	}

	public function app_menu_array($region_id, $source, $cangId) {
		$result = $this->selectClass($region_id, $source, [], $cangId);
		$result_class = array();
		foreach ($result as $key => $value) {
//			if ($value['parent_id'] == 0) {
			$send_region = empty($value['send_region']) ? array() : unserialize($value['send_region']);
			if (!in_array($region_id, $send_region)) {
				continue;
			}
			$result_class[$value['id']]['id'] = $value['id'];
			$result_class[$value['id']]['name'] = $value['name'];
			$result_class[$value['id']]['ename'] = $value['ename'];
			$result_class[$value['id']]['is_hot'] = $value['is_hot'];
			$result_class[$value['id']]['parent_id'] = $value['parent_id'];
			$result_class[$value['id']]['step'] = $value['step'];
			$result_class[$value['id']]['photo'] = empty($value['class_photo']) ? '' : PIC_URL . $value['class_photo'];
			$result_class[$value['id']]['class_photo'] = empty($value['class_photo']) ? '' : PIC_URL . $value['class_photo'];
//			}
		}
		return $result_class;
	}

    public function getClassPairsByProIds($proIds = array(), $parentId = 40)
    {
        $this->db->select('class.id, class.name')
                 ->from('pro_class')
                 ->join('class', 'pro_class.class_id=class.id', 'left')
                 ->where_in('pro_class.product_id', $proIds)
                 ->where('class.parent_id', $parentId);
        $result = $this->db->get()->result_array();
        return $result;
    }

    public function getClassByPid($productId = 0)
    {
        if (!$productId) {
            return array();
        }

        $this->db->select('class_id')
                 ->from('pro_class')
                 ->where(array('product_id' => $productId));
        $result = $this->db->get()->result_array();

        return $result ?: array();
    }
}
