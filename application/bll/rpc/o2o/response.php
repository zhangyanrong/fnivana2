<?php
namespace bll\rpc\o2o;

class Response
{

    private $_data_format = 'json';

    private $_response_data = null;

    private $_error = null;

    private $_rpc_log = array();

    private $_allowed_services = array(
        'seller.pull',          //获取商户
        'store.pull',           //获取门店数据
        'store.queryAll',       //提供门店初始化数据
        'order.sendLogistics',  //提供订单发货接口
        'order.finish',         //提供订单完成接口
        'order.status',         //提供订单状态接口
        'order.callback',       //提供订单状态接口
        'order.cancel',         //提供订单取消接口
        'order.pushoms',        //根据order_name推送订单（o2o后台调用）
        'order.syncOrderInfo',  //查看推送订单（o2o后台调用）
        'order.allocation',     //生产调拨单（o2o后台调用）
        'region.getBuildings',  //提供楼宇接口
        'region.save',          //提供楼宇(新增,编辑)接口
        'region.change',        //提供楼宇(数据初始化)
        'stock.pushAll',        //提供tms全量库存更新
        'stock.pushOne',        //提供tms单个库存更新
        'stock.query',          //获取tms库存
        'stock.update',         //tms修改销售库存(增量加减库存)
        'stock.updateAll',      //tms修改销售库存(同步实体库存)
        'area.push',            //推送省市区
    );

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->library('aes', null, 'encrypt_aes');
        $this->ci->load->library('poolhash');
    }

    /**
     * 签名认证
     *
     * @return void
     * @author
     **/
    public function check_sign($params)
    {
        $sign = $params['sign'];
        unset($params['sign']);
        $params['data'] = $this->ci->encrypt_aes->AesDecrypt($params['data'], base64_decode(POOL_O2O_AES_KEY));
        if (!$this->ci->poolhash->check_sign($params, $sign, POOL_O2O_SECRET)) {
            $this->_error = array('result' => 0, 'msg' => '验签失败');
            return false;
        }
        return true;
    }

    /**
     * 获取参数
     * @return array
     */
    public function repost()
    {
        if (empty($_POST)) {
            $_POST = json_decode(file_get_contents("php://input"), 1);
        }

        if (empty($_POST))
            $_POST = array();

        $params = array_merge($_POST, $_GET);
        return $params;
    }

    /**
     * 系统级参数验证
     * @param $params
     * @return bool
     */
    public function check_sys_params($params)
    {
        if (empty($params['appId']) || $params['appId'] != POOL_O2O_APPID) {
            $this->_error = array('result' => 0, 'msg' => 'appid错误');
            return false;
        }

        if (empty($params['method'])) {
            $this->_error = array('result' => 0, 'msg' => '缺少系统级参数');
            return false;
        }

        if (!in_array($params['method'], $this->_allowed_services)) {
            $this->_error = array('result' => 0, 'msg' => '接口不支持');
            return false;
        }

        if (empty($params['v']) || $params['v'] != POOL_O2O_VERSION) {
            $this->_error = array('result' => 0, 'msg' => '版本号错误');
            return false;
        }

        return true;
    }

    /**
     * 处理
     *
     * @return void
     * @author
     **/
    public function process()
    {

        $params = $this->repost();
        if(isset($params['data']))
            $params['data'] = urldecode($params['data']);

        if (!$this->check_sys_params($params) || !$this->check_sign($params))
            return $this;

        $data = $this->ci->encrypt_aes->AesDecrypt($params['data'], base64_decode(POOL_O2O_AES_KEY));
        $data = json_decode($data, true);

        list($class, $action) = explode('.', $params['method']);
        $action = !empty($action) ? $action : 'index';

        $this->ci->load->bll('pool/o2o/' . $class);
        $name = 'bll_pool_o2o_' . $class;

        $rs = $this->ci->{$name}->{$action}($data);

        if ($rs !== false) {
            $this->_response_data = $rs;
        }

        if (!empty($this->ci->{$name}->rpc_log))
            $this->_rpc_log = $this->ci->{$name}->rpc_log;

        // 记日志
        $status = 'succ';
        if ($this->_error || $rs['result'] === 0) {
            $status = 'fail';
        }

        $this->insert_log($params['method'], $data, $rs, $status);


        return $this;
    }

    /**
     * 输出
     *
     * @return void
     * @author
     **/
    public function output()
    {
        $this->ci->load->library('aes',null,'encrypt_aes');
        if($this->_error){
            $params = array(
                'code' => $this->_error['result'] ? '200' : '300',
                'msg'  => $this->_error['msg'],
            );
        }else{

            $params = array(
                'code' => !isset($this->_response_data['result']) || $this->_response_data['result'] == 1 ? '200' : '300',
            );
            if($params['code'] == '200'){
                $params['msg']  =  "成功";
                $params['data'] = urlencode($this->ci->encrypt_aes->AesEncrypt(json_encode($this->_response_data, JSON_UNESCAPED_UNICODE), base64_decode(POOL_O2O_AES_KEY)));
            }else{
                $params['msg']  =  $this->_response_data['msg'];
            }
        }

        switch ($this->_data_format) {
            case 'json':
                echo stripslashes(json_encode($params, JSON_UNESCAPED_UNICODE));
                break;
            case 'xml':

                break;
        }
    }

    /**
     * rpc日志
     *
     * @return void
     * @author
     **/
    private function insert_log($method, $resp, $result, $status)
    {
        if($method == 'region.getBuildings' && count($resp) > 50){
            $resp = array_slice($resp, 0, 50);
        }
        $log = array(
            'data' => json_encode(array('req' => $resp, 'resp' => $result), JSON_UNESCAPED_UNICODE),
            'createtime' => date('Y-m-d H:i:s'),
            'type' => 'response',
            'obj_type' => $method,
            'status' => $status,
        );

        if ($status == 'fail') {
            $log['errorNumber'] = '';
            $log['errorMessage'] = $this->_error['msg'] ? $this->_error['msg'] : $result['msg'];
        }

        switch ($method) {
            case 'order.callback':
                $log['rpc_desc'] = '订单回调';
                $log['obj_type'] = 'order';
                $log['obj_name'] = $resp['orderNo'];
                break;
            default:

                break;
        }

        if (is_array($this->_rpc_log)) {
            $allow_key = array('rpc_desc', 'obj_type', 'obj_name');
            foreach ($allow_key as $value) {
                if ($this->_rpc_log[$value]) $log[$value] = $this->_rpc_log[$value];
            }
        }

        $this->ci->load->model('o2o_rpc_log_model');

        $this->ci->o2o_rpc_log_model->insert($log);

    }
}