<?php
namespace bll\pool\o2o;

class Stock
{
    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->helper('public');
    }

    public function query($filter)
    {
        if (empty($filter['storeCode'])) {
            return array('result' => 0, 'msg' => 'sap门店编码不能为空');
        }

        $this->ci->load->bll('rpc/o2o/request');
        $this->ci->bll_rpc_o2o_request->set_rpc_log(array('rpc_desc' => '库存同步', 'obj_name' => $filter['storeCode'], 'obj_type' => 'stock.query'));

        $params = array(
            'method' => 'stock.query',
            'siteCode' => $filter['storeCode']
        );

        $response = $this->ci->bll_rpc_o2o_request->tms_call($params);

        if ($response === false) {
            $error = $this->ci->bll_rpc_o2o_request->get_errorinfo();
            return array('result' => 0, 'msg' => $error['errorMessage']);
        }
        return $this->pushAll($response['stockProducts'], isset($filter['updateSaleStock']) ? $filter['updateSaleStock'] : false);
    }

    public function pushAll($filter, $updateSaleStock = false)
    {
        $this->ci->load->model('product_groups_model');
        if (empty($filter)) {
            return array('result' => 0, 'msg' => '数据不能为空');
        }
        $this->ci->load->model('o2o_store_physical_model');

        $data = array();
        $stores = array();
        foreach ($filter as $value) {
            if (!isset($stores[$value['storeCode']])) {
                $physical_store = $this->ci->o2o_store_physical_model->dump(array('sap_code' => $value['storeCode']), 'id');
                if (empty($physical_store)) {
                    return array('result' => 0, 'msg' => '门店不存在: ' . $value['storeCode']);
                }
                $stores[$value['storeCode']] = $physical_store['id'];
            }
            $data[$stores[$value['storeCode']]][$value['productNo']] = $value['stock'] - $value['num'];
        }

        //unset($filter);

        foreach ($data as $key => $value) {
            $storeStocks = $value;
            $physical_store_id = $key;
            $storeProducts = $this->getStoreProduct($physical_store_id);

            //加上 - 未支付的订单库存
            $storeNoPayProducts = $this->getNoPayProducts($physical_store_id);

            if (!empty($storeProducts)) {
                foreach ($storeProducts as $spk => $spv) {
                    if (isset($storeNoPayProducts[$spv['product_id']])) {
                        if(trim($spv['cart_tag']) == '买1送1'){
                            $storeProducts[$spk]['stock'] += $storeNoPayProducts[$spv['product_id']] * 2;
                        }else{
                            $storeProducts[$spk]['stock'] += $storeNoPayProducts[$spv['product_id']];
                        }
                    }
                }
            }

            if ($updateSaleStock) {

                foreach ($storeProducts as $prod) {
                    if(isset($storeStocks[$prod['inner_code']]) && $prod['inner_code'] != '900000001'){
                        $stock = $storeStocks[$prod['inner_code']];
                        if (isset($storeNoPayProducts[$prod['product_id']])) {
                            $stock -= $storeNoPayProducts[$prod['product_id']];
                        }
                        if(trim($prod['cart_tag']) == '买1送1'){
                            $stock = floor($stock / 2);
                        }
                        unset($storeStocks[$prod['inner_code']]);
                    }else{
                        $stock = 0;

                    }
                    $stock = $stock > 0 ? $stock : 0;
                    $this->updateSaleStock($prod['store_id'], $prod['product_id'], $stock);
                }
                return $this->pushAll($filter);
            }
            $prodIDs = array_column($storeProducts, 'product_id');
            $groupProds = array();
            if (!empty($prodIDs)) {
                $groupProdsTmp = $this->getGroupProductNew($prodIDs);
                foreach ($groupProdsTmp as $gpt) {
                    $groupProds[$gpt['product_id']][] = $gpt;
                }
            }
            foreach ($storeProducts as $prod) {
                //新组合商品
                if (!empty($groupProds[$prod['product_id']])){
                    foreach ($groupProds[$prod['product_id']] as $gp) {
                        if (isset($storeStocks[$gp['inner_code']])) {

                            $storeStocks[$gp['inner_code']] -= $prod['stock'] * $gp['g_qty'];
                        }
                    }
                } elseif (!empty($prod['group_pro'])) {
                    $groupProducts = $this->getGroupProduct($prod['group_pro']);
                    foreach ($groupProducts as $gp) {
                        if (isset($storeStocks[$gp['inner_code']])) {
                            $storeStocks[$gp['inner_code']] -= $prod['stock'];
                        }
                    }
                } elseif (isset($storeStocks[$prod['inner_code']])) {
                    $storeStocks[$prod['inner_code']] -= $prod['stock'];
                }
            }

            $params = array();
            foreach ($storeStocks as $prodNo => $prod) {
                $params[] = array(
                    'physical_store_id' => $physical_store_id,
                    'inner_code' => $prodNo,
                    'stock' => $prod,
                );
            }

            $this->ci->db->delete('o2o_stock', array('physical_store_id' => $physical_store_id));

            $this->ci->db->insert_batch('o2o_stock', $params);
        }
        return array('result' => 1, 'msg' => '全量处理库存成功');
    }

    public function pushOne($filter)
    {
        $storeCode = $filter['storeCode'];
        $productNo = $filter['productNo'];
        $stockNumber = $filter['stock'];
        $type = $filter['type'];

        if (empty($storeCode)) {
            return array('result' => 0, 'msg' => '门店编码不能为空');
        }

        if (empty($productNo)) {
            return array('result' => 0, 'msg' => '商品编号不能为空');
        }

        if ($stockNumber <= 0) {
            return array('result' => 0, 'msg' => '库存量不能为空');
        }

        // 1: 加库存, 2: 抽库存
        if (!in_array($type, array(1, 2))) {
            return array('result' => 0, 'msg' => '数据不能为空');
        }

        $this->ci->load->model('o2o_store_physical_model');

        $physical_store = $this->ci->o2o_store_physical_model->dump(array('sap_code' => $storeCode), 'id');
        if (empty($physical_store)) {
            return array('result' => 0, 'msg' => '门店不存在: ' . $storeCode);
        }
        $stock = $this->getStock($physical_store['id'], $productNo);
        if (!empty($stock)) {
            if ($type == 1) {
                $stock['stock'] += $stockNumber;
            } elseif ($type == 2) {
                $stock['stock'] -= $stockNumber;
            }
            if ($stock['stock'] < 0) {
                return array('result' => 0, 'msg' => '库存不足,不能减库存!');
            }
            $this->ci->db->query('update ttgy_o2o_stock set stock = ? where id = ?', array($stock['stock'], $stock['id']));
        } elseif ($type == 1) {
            $this->ci->db->insert('ttgy_o2o_stock', array(
                'physical_store_id' => $physical_store['id'],
                'inner_code' => $productNo,
                'stock' => $stockNumber,
            ));
        }

        return array('result' => 1, 'msg' => '处理库存成功');
    }

    public function update($filter)
    {
        $storeCode = $filter['storeCode'];
        $productNo = $filter['productNo'];
        $stockNumber = $filter['stock'];
        $orderName = $filter['orderName'];
        $num = $filter['num'];

        if (empty($storeCode)) {
            return array('result' => 0, 'msg' => '门店编码不能为空');
        }

        if (empty($productNo)) {
            return array('result' => 0, 'msg' => '商品编号不能为空');
        }

        $store = $this->ci->db->query('SELECT s.id FROM ttgy_o2o_store s
                LEFT JOIN ttgy_o2o_store_physical p ON p.id = s.physical_store_id
                WHERE s.isopen=1 and p.sap_code =?', array($storeCode))->row_array();

        if (empty($store)) {
            return array('result' => 0, 'msg' => '门店不存在: ' . $storeCode);
        }
        $products = [];
        if (!empty($orderName)) {
            $orderProducts = $this->ci->db->query('select op.product_id, op.qty,op.type,p.cart_tag,pp.inner_code from ttgy_order o 
inner join ttgy_order_product op on op.order_id=o.id 
inner join ttgy_product p on p.id=op.product_id
inner join ttgy_product_price pp on pp.product_id=p.id 
where o.order_name=? order by first_limit asc, p.cart_tag asc', array($orderName))->result_array();

            foreach ($orderProducts as $v) {
                //赠品或非当前sku
                if($v['type'] == 3 || $v['inner_code'] != $productNo){
                    continue;
                }

                //买一送一
                if(trim($v['cart_tag']) == '买1送1'){
                    $num = floor($num / 2);
                }

                if ($v['qty'] >= $num) {
                    array_push($products, array(
                        'product_id' => $v['product_id'],
                        'stock' => $num,
                    ));
                    break;
                } else {
                    $num -= $v['qty'];
                    array_push($products, array(
                        'product_id' => $v['product_id'],
                        'stock' => $v['qty'],
                    ));
                }
            }
        } else {
            $storeProducts = $this->ci->db->query('SELECT pp.product_id,p.first_limit,p.cart_tag FROM ttgy_o2o_store_goods g
                LEFT JOIN ttgy_product_price pp ON pp.product_id = g.product_id
                LEFT JOIN ttgy_product p ON p.id = pp.product_id
                WHERE g.store_id = ? and pp.inner_code = ? order by first_limit asc, p.cart_tag asc', array($store['id'], $productNo))->result_array();

            foreach ($storeProducts as $v) {
                //买一送一
                if(trim($v['cart_tag']) == '买1送1'){
                    $num = $num / 2;
                    if($num > 0){
                        $num = floor($num);
                    }else{
                        $num = ceil($num);
                    }
                }
                array_push($products, array(
                    'product_id' => $v['product_id'],
                    'stock' => $num,
                ));
                break;
            }
        }

        if(empty($products)){
            return array('result' => 0, 'msg' => '修改销售库存失败:没有找到对应sku商品');
        }

        foreach ($products as $v){
            $this->updateSaleStock($store['id'],$v['product_id'], $v['stock'], true);
        }
        //组合商品暂不考虑
        return array('result' => 1, 'msg' => '修改销售库存成功');
    }

    public function updateAll($filter)
    {
        $storeCode = $filter['storeCode'];

        if (empty($storeCode)) {
            return array('result' => 0, 'msg' => '门店编码不能为空');
        }

        return $this->query(array('storeCode' => $storeCode, 'updateSaleStock' => true));
    }

    private function getStoreProduct($store_id)
    {
        $sql = "SELECT p.product_id,p.inner_code,pr.group_pro,g.stock,s.id store_id,pr.cart_tag FROM ttgy_o2o_store_goods g
                LEFT JOIN ttgy_o2o_store s ON s.id = g.store_id
                LEFT JOIN ttgy_product_price p ON p.product_id = g.product_id
                LEFT JOIN ttgy_product pr ON pr.id = p.product_id
                WHERE s.physical_store_id = ? and s.isopen=1";
        return $this->ci->db->query($sql, array($store_id))->result_array();
    }

    private function getGroupProduct($ids)
    {
        if (!is_array($ids)) {
            $ids = explode(',', $ids);
        }
        $this->ci->db->select('p.product_id,p.inner_code')
            ->from('product pr')
            ->join('product_price p', 'pr.id = p.product_id')
            ->where_in('pr.id', $ids);
        return $this->ci->db->get()->result_array();
    }

    private function getGroupProductNew($ids)
    {
        $this->ci->db->select('g.product_id,g.g_product_id,p.inner_code,g.g_qty')
            ->from('product_groups g')
            ->join('product_price p', 'g.g_product_id = p.product_id')
            ->where_in('g.product_id', $ids);
        return $this->ci->db->get()->result_array();
    }

    private function getStock($store_id, $product_no)
    {
        $this->ci->db->select('id, stock')
            ->from('o2o_stock')
            ->where('physical_store_id', $store_id)
            ->where('inner_code', $product_no);
        return $this->ci->db->get()->row_array();
    }

    private function getNoPayProducts($store_id)
    {

        $rs = $this->ci->db->query('select p.product_id, sum(qty) sum from ttgy_o2o_child_order c join ttgy_order o on c.p_order_id=o.id join ttgy_order_product p on p.order_id=o.id where o.time > ? and o.order_type in (3,4) and c.operation_id=0 and c.sync_status != 1 and c.store_id=? group by p.product_id', array(date('Y-m-d'), $store_id))->result_array();
        if (!empty($rs)) {
            return array_column($rs, 'sum', 'product_id');
        }
        return array();
    }

    private function updateSaleStock($store_id, $product_id, $stock, $update = false)
    {
        if($update){
            $rs = $this->ci->db->query('select stock from  ttgy_o2o_store_goods where store_id = ? and product_id = ?', array('store_id' => $store_id, 'product_id' => $product_id))->row_array();
            if(!$rs){
                return false;
            }
            $stock += $rs['stock'];
        }
        $stock = $stock > 0 ? $stock : 0;
        return $this->ci->db->query('update ttgy_o2o_store_goods set stock = ? where store_id = ? and product_id = ?', array('stock' => $stock, 'store_id' => $store_id, 'product_id' => $product_id));
    }
}
