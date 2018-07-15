<?php
namespace bll\wap;
include_once("wap.php");
/**
 * 商品相关接口
 */
class Marketing extends wap{
    function __construct($params=array()){
        $this->ci = &get_instance();
    }
    function banner($params){
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE!=$params['service']) {
            if(!$this->ci->memcached){
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service']."_".$params['source']."_".$params['version']."_".$params['region_id']."_".$params['cang_id']."_".$params['channel'];
            $data = $this->ci->memcached->get($mem_key);
            if($data){
                return $data;
            }
        }
        $banner_list = parent::call_bll($params);
        $rotation_banner_list = array();
        $top_banner_list = array();
        $normal_banner_list = array();
        $mix_banner_list = array();
        $wx_banner_list = array();
        //会员中心app
        $app_foretaste_banner_list = array();
        $app_rotation_banner_list = array();
        $app_general_banner_list = array();

        foreach ($banner_list as $key => &$value) {
            $is_top = $value['is_top'];
            unset($value['is_top']);
            switch ($value['position']) {
                case '0':
                    $rotation_banner_list[] = $value;
                    break;
                case '1':
                    if($is_top == 1){
                        $top_banner_list[] = $value;
                    }else{
                        $normal_banner_list[] = $value;
                    }
                    break;
                case '16':
                    $mix_banner_list[] = $value;//todo by lusc
                    break;
                case '61':
                    $wx_banner_list[] = $value;
                    break;
                case '23':
                    $app_foretaste_banner_list[] = $value;  //todo by jackchen
                    break;
                case '80':
                    $app_rotation_banner_list[] = $value;  //todo by jackchen
                    break;
                case '81':
                    $app_general_banner_list[] = $value;  //todo by jackchen
                    break;
                default:
                    # code...
                    break;
            }
        }
        // $day_product_list = $this->xsh($region_id);//todo
        $data = array(
            'rotation'=>$rotation_banner_list,
            'banner'=>$normal_banner_list,
            'top_banner'=>$top_banner_list,
            'mix_banner'=>$mix_banner_list,
            'wx_banner_list'=>$wx_banner_list,
            'app_foretaste_banner_list'=>$app_foretaste_banner_list,
            'app_rotation_banner_list'=>$app_rotation_banner_list,
            'app_general_banner_list'=>$app_general_banner_list,
        );
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            if(!$this->ci->memcached){
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service']."_".$params['source']."_".$params['version']."_".$params['region_id']."_".$params['cang_id']."_".$params['channel'];
            $this->ci->memcached->set($mem_key,$data,600);
        }
        return $data;
    }

    /**
     * 获取会员
     *
     * @return void
     * @author
     **/
    public function get_userid()
    {
        $this->ci->load->library('login');
        $this->ci->login->init($this->_sessid);
        return $this->ci->login->get_uid();
    }

    /**
     * 广告图片
     *
     * @return void
     * @author
     **/
    public function appActive()
    {
        $this->ci->load->model('app_active_model');
        $res = $this->ci->app_active_model->get_list();
        return $res;
    }

}