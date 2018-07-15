<?php
namespace bll\pool\o2o;

class Seller
{
    public function __construct()
    {
        $this->ci = & get_instance();
    }

    public function add($filter)
    {
        if (!$filter['name']) {
            return array('result'=>0, 'msg' => '商户名称不能留空');
        }

        if (!$filter['sid']) {
            return array('result'=>0, 'msg' => '商户ID不能留空');
        }

        $data = array(
            'name' => $filter['name'],
            'code' => $filter['code'],
            'ext_id' => $filter['sid'],
            'type' => $filter['type']
        );

        $this->ci->load->model('o2o_seller_model');

        $seller = $this->ci->o2o_seller_model->dump(array('code' => $filter['code']));
        if(!empty($seller)){
            $this->ci->o2o_seller_model->update($data, array('code' => $filter['code']));
        }else{
            $this->ci->o2o_seller_model->insert($data);
        }

        return array('result'=>1, 'msg' => '成功');
    }

    public function pull()
    {
        $this->ci->load->bll('rpc/o2o/request');
        $params = array(
            'url' => POOL_O2O_OMS_URL,
            'method' => 'seller.query',
            'data' => array(
                'time'=>date('Y-m-d H:i:s', time() - 3600 * 24 * 15),
            ),
        );

        $response = $this->ci->bll_rpc_o2o_request->realtime_call($params,10);
        $rs = array();
        if(!empty($response)){
            foreach($response as $v)
                $rs[] = $this->add($v);
        }
        return $rs;
    }

}
