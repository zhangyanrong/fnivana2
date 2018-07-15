<?php
namespace bll;
class Cms
{
    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->model('cms_model');
        $this->ci->load->helper('public');
    }

    /*
	 * 获取cms
	 */
    public function getCms($params){
        $required_fields = array(
            'cms_id' => array('required' => array('code' => '300', 'msg' => 'id不能为空')),
        );

        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $data = array();
        $data['cms_model'] = $this->ci->cms_model->get_cms_model($params['cms_id']);
        $data['cms_advertisement'] = $this->ci->cms_model->get_cms_advertisement($params['cms_id']);

        return $data;
    }

}
