<?php
class Alipay_model extends MY_Model {
    
    private $token_table = 'alipay_token';
    
    private $card_table = 'alipay_card';
    
    private $userinfo_table = 'alipay_userinfo';
    
    
    public function init_userinfo( $userinfo )
    {
        $insert_data = $userinfo;
        
        $insert_data['mobile'] = '';
        $insert_data['fruitday_user_id'] = '';
        $insert_data['bind_status'] = 'N';
        $insert_data['request_time'] = date("Y-m-d H:i:s");
        
        $result = $this->db->insert( $this->userinfo_table, $insert_data );
        
        return $result;
    }
    
    
    public function set_token( $tokeninfo )
    {
        $insert_data = $tokeninfo;
        
        $insert_data['expires_time'] = date("Y-m-d H:i:s", strtotime( "+" . $tokeninfo['expires_in'] . " seconds" ));
        $insert_data['re_expires_time'] = date("Y-m-d H:i:s", strtotime( "+" . $tokeninfo['re_expires_in'] . " seconds" ));
        $insert_data['request_time'] = date("Y-m-d H:i:s");
        
        $result = $this->db->insert( $this->token_table, $insert_data );
    
        return $result;
    }
    
    /**
     * @desc 绑定支付宝用户
     * $user_id 绑定用户user_id，类似微信open_id
     * $fruitday_user_id 果园用户user_id
     * $alipay_user_id 支付宝用户user_id
     */
    public function bind( $fruitday_user_id, $mobile, $userinfo )
    {
        if (!isset($userinfo['user_id'])) {
            return ['code' => 301, 'msg' => '没有获得用户支付宝账号信息。'];
        }

        if ((int)$fruitday_user_id <= 0) {
            return ['code' => 302, 'msg' => '您输入的账号信息错误，请重新输入。'];
        }
        
        $where = array( 'user_id' => $userinfo['user_id'] );
        $get_result = $this->get_alipay_userinfo( $where );
        
        if( $get_result ){
            
            if( $get_result['bind_status'] != 'Y' ){    //支付宝用户信息已存在，但未绑定
                $data = $userinfo;
                $data['mobile'] = $mobile;
                $data['fruitday_user_id'] = $fruitday_user_id;
                $data['bind_status'] = 'Y';
                $data['bind_time'] = date("Y-m-d H:i:s");
                $result = $this->db->update( $this->userinfo_table, $data, $where );
                
                if ($result === false) {
                    return ['code' => 300, 'msg' => '绑定失败'];
                }
            }else{
                $data = $get_result;
            }
            
        }else{
            
            $data = $userinfo;
            $data['mobile'] = $mobile;
            $data['fruitday_user_id'] = $fruitday_user_id;
            $data['bind_status'] = 'Y';
            $data['bind_time'] = date("Y-m-d H:i:s");
            
            $result = $this->db->insert($this->userinfo_table, $data);
            
            if ($result === false) {
                return ['code' => 300, 'msg' => '绑定失败'];
            }
        }
        
        return ['code' => 200, 'user_info' => $data];
    }
    
    /**
     * @desc 绑定支付宝用户
     * $user_id 绑定用户user_id，类似微信open_id
     * $fruitday_user_id 果园用户user_id
     * $alipay_user_id 支付宝用户user_id
     */
    public function bind_user_id( $fruitday_user_id, $mobile, $user_id )
    {
        if (!isset($user_id)) {
            return ['code' => 301, 'msg' => '没有获得用户支付宝账号信息。'];
        }

        if ((int)$fruitday_user_id <= 0) {
            return ['code' => 302, 'msg' => '您输入的账号信息错误，请重新输入。'];
        }
        
        $where = array( 'user_id' => $user_id );
        $get_result = $this->get_alipay_userinfo( $where );
        
        if( $get_result ){
            
            if( $get_result['bind_status'] != 'Y' ){    //支付宝用户信息已存在，但未绑定
                $data['mobile'] = $mobile;
                $data['user_id'] = $user_id;
                $data['fruitday_user_id'] = $fruitday_user_id;
                $data['bind_status'] = 'Y';
                $data['bind_time'] = date("Y-m-d H:i:s");
                $result = $this->db->update( $this->userinfo_table, $data, $where );
                
                if ($result === false) {
                    return ['code' => 300, 'msg' => '绑定失败'];
                }
            }else{
                $data = $get_result;
            }
            
        }else{
            
            $data = array();
            $data['mobile'] = $mobile;
            $data['user_id'] = $user_id;
            $data['fruitday_user_id'] = $fruitday_user_id;
            $data['bind_status'] = 'Y';
            $data['bind_time'] = date("Y-m-d H:i:s");
            
            $result = $this->db->insert($this->userinfo_table, $data);
            
            if ($result === false) {
                return ['code' => 300, 'msg' => '绑定失败'];
            }
        }
        
        return ['code' => 200, 'user_info' => $data];
    }
    
    
    public function set_alipay_card( $data )
    {
        //status: 0：已发放，1：已使用
        $data['add_time'] = date("Y-m-d H:i:s");
        
        $result = $this->db->insert($this->card_table, $data);
    
        if ($result === false) {
            return ['code' => 300, 'msg' => '添加失败'];
        }else{
            return ['code' => 200, 'msg' => '添加成功'];
        }
    }
    
    public function update_alipay_card( $data, $where )
    {
        if( isset($where['uid']) ){
            $this->db->where('uid', $where['uid']);    
        }
        if( isset($where['card_number']) ){
            $this->db->where('card_number', $where['card_number']);    
        }
        if( isset($where['alipay_user_id']) ){
            $this->db->where('alipay_user_id', $where['alipay_user_id']);    
        }
        
        return $this->db->update( $this->card_table, $data );
    }
    
    
    public function get_alipay_card( $where )
    {
        $this->db->select( '*' );
        $this->db->from( $this->card_table );
        
        if( isset($where['uid']) ){
            $this->db->where('uid', $where['uid']);    
        }
        if( isset($where['card_number']) ){
            $this->db->where('card_number', $where['card_number']);    
        }
        if( isset($where['alipay_user_id']) ){
            $this->db->where('alipay_user_id', $where['alipay_user_id']);    
        }
        
        $result = $this->db->get()->row_array();
        
        return $result;
    }
    
    
    public function get_alipay_userinfo( $where )
    {
        $this->db->select( '*' );
        $this->db->from( $this->userinfo_table );
        
        if( isset($where['user_id']) ){
            $this->db->where('user_id', $where['user_id']);
        }
        
        if( isset($where['mobile']) ){
            $this->db->where('mobile', $where['mobile']);
        }
        
        if( isset($where['fruitday_user_id']) ){
            $this->db->where('fruitday_user_id', $where['fruitday_user_id']);
        }
        
        $result = $this->db->get()->row_array();
        
        return $result;
    }
    
    
    public function check_user_exists( $where )
    {
        $result = $this->get_alipay_userinfo( $where );
        
        if( is_array($result) ){
            return true;
        }else{
            return false;
        }
    }
    
    
    public function update_userinfo( $user_id, $userinfo )
    {
        $this->db->where('user_id', $user_id);
        return $this->db->update( $this->userinfo_table, $userinfo );
    }
    
    
    
    /**
     *@desc 添加用户地址
        //成功选取收货地址的情况
        //data返回对象
        //"address": "古荡街道万塘路18号黄龙时代广场B座", 详细地址
        //"addressCode": "330106", 地址邮编
        //"addressId": "214118119", 地址id
        //"area": "西湖区", 区
        //"city": "杭州市", 市
        //"fullname": "张三", 姓名
        //"mobilePhone": "13912345678", 手机号
        //"post": "310012",  区域邮编
        //"prov": "浙江省" 省份
    */
    public function bind_alipay_address( $user_id, $address )
    {
        $insert_data = array();
        $insert_data['province'] = 0;
        $insert_data['city'] = 0;
        $insert_data['area'] = 0;
        
        if( $address['prov'] ){
            $prov = str_replace( array('省','市'), '', $address['prov'] );
            $insert_data['province'] = $this->get_area_id( $prov );
        }
        if( $address['city'] ){
            $insert_data['city'] = $this->get_area_id( $address['city'] );
        }
        if( $address['area'] ){
            $insert_data['area'] = $this->get_area_id( $address['area'] );
        }
        
        if( $insert_data['province'] == 0 || $insert_data['city'] == 0 || $insert_data['area'] == 0 ){
            return ['code' => 301, 'msg' => '添加失败，地址匹配错误'];
        }

        $insert_data['uid'] = $user_id;
        $insert_data['name'] = $address['fullname'];
        $insert_data['position'] = 0;
        $insert_data['flag'] = '';
        $insert_data['address'] = $address['address'];
        $insert_data['zipcode'] = $address['addressCode'];
        $insert_data['email'] = '';
        $insert_data['telephone'] = '';
        $insert_data['mobile'] = $address['mobilePhone'];
        $insert_data['is_default'] = 1;
        $insert_data['tmscode'] = '';
    
        $result = $this->db->insert( 'user_address', $insert_data );
        
        if ($result === false) {
            return ['code' => 300, 'msg' => '添加失败'];
        }else{
            return ['code' => 200, 'msg' => '添加成功'];   
        }
    }
    
    
    private function get_area_id( $area_name )
    {
        $this->db->select( 'id' );
        $this->db->from( 'area' );
        
        if( $area_name ){
            $this->db->where('name', $area_name);
        }else{
            return false;
        }
        
        $result = $this->db->get()->row_array();
        
        if( $result ){
            return $result['id'];    
        }else{
            return 0;
        }
    }
    
}