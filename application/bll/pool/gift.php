<?php 
namespace bll\pool;

class Gift
{

    public function __construct()
    {
        $this->ci = & get_instance();
    }

    public function addGift($filter){
        $this->rpc_log = array('rpc_desc' => '添加赠品','obj_type'=>'addgift','obj_name'=>$filter['tag']);
        if (!$filter['tag']) return array('result'=>0,'errorMsg' => 'tag码不能为空');
        if (!$filter['uids']) return array('result'=>0,'errorMsg' => '客户ID不能为空');
        //if (!$filter['batch']) return array('result'=>0,'errorMsg' => '客户ID不能为空');
        $uids = explode(',', $filter['uids']);
        $tag = $filter['tag'];
        if(empty($uids)) return array('result'=>0,'errorMsg' => '无有效客户ID');
        $batch = $filter['batch']?$filter['batch']:0;
        $sql = "select * from ttgy_gift_send where tag='".$tag."'";
        $tmpl = $this->ci->db->query($sql)->row_array();
        if(empty($tmpl)) return array('result'=>0,'errorMsg' => '无赠品活动');
        $gift = array();
        $gift['gift_id'] = $tmpl['id'];
        $gift['tag'] = $tmpl['tag'];
        $acontent = serialize($gift);
        $insert_data = array('acontent'=>$acontent,'utype'=>1,'ucontent'=>implode(',', $uids),'ctime'=>time());
        $res = $this->ci->db->insert('add_gift',$insert_data);
        if($res) return array('result'=>1,'data' => '添加成功');
        else return array('result'=>0,'errorMsg' => '添加失败');
    }
}