<?php
namespace bll;

class Spitslot
{
    //反馈上传图片
    private $photolimit = 9;
    //反馈图片存储路径
    private $photopath = "images/";
    //用户头像
    private $head_photopath = "up_images/";
    //当前机器时间
    private $stime;
    //反馈图片大小
    private $thumb_size = "320";

    public function __construct()
    {
        $this->ci = &get_instance();

        $this->ci->load->helper('public');
        $session_id = isset($params['connect_id'])?$params['connect_id']:'';
        if($session_id){
            $this->ci->load->library('session',array('session_id'=>$session_id));
        }
        $this->photopath = $this->photopath.date("Y-m-d");
        $this->head_photopath = $this->head_photopath.date("Y-m-d");
        $this->stime = time();
    }

    /**
     * App - 应用反馈
     */
    public function add($params)
    {
        $require_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect_id can not be null'))
        );
        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $this->ci->load->model('spitslot_model');

        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        $uid = $this->ci->login->get_uid();

        // 评论图片上传到七牛
        // 蔡昀辰 2015
        if ($_FILES && count($_FILES) <= $this->photolimit) {
            $img_arr = [
                "images"=>[],
                "thumbs"=>[]
            ];

            // 载入配置和lib
            $this->ci->config->load("qiniu", true, true);
            $this->ci->load->library('Qiniu/qiniu', $this->ci->config->item('qiniu'));

            // 获取图片
            foreach ($_FILES as $photo) {
                $path   = $photo['tmp_name'];
                $name   = $photo['name'];
                $date   = date("ymd", time());
                $prefix = 'img/spitslot';
                $hash   = str_replace('/tmp/php', '', $path);
                $key    = "{$prefix}/{$date}/{$hash}/{$name}";
                // 上传
                $ret = $this->ci->qiniu->put($key, $path);

                if($ret) {
                    $img_arr["images"][] = str_replace('img/', '', $key);
                    $img_arr["thumbs"][] = str_replace('img/', '', $key).'-thumb';
                }
            }
        }

        if(empty($params['description']))
        {
            return array('code'=>'300','msg'=>'吐槽描述内容请勿为空');
        }

        $data['description'] = strip_tags($params['description']);
        $data['title'] = empty($params['title']) ? "" : strip_tags($params['title']);
        $data['time'] = date('Y-m-d H:i:s');
        $data['uid'] = $uid;
        $data['photo'] = empty($img_arr['images']) ? "" : implode(",",$img_arr["images"]);
        $data['thumbs'] = empty($img_arr['images']) ? "" : implode(",",$img_arr["thumbs"]);

        $res = $this->ci->spitslot_model->add($data);
        if($res){
            return array('code'=>'200','msg'=>'发布成功');
        }else{
            return array('code'=>'300','msg'=>'发布失败');
        }

    }

}
