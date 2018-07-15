<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Model extends CI_Model
{
    public function __construct($group_name = '')
    {
        parent::__construct();
        $this->initDb($group_name);
    }

    private function initDb($group_name = '')
    {
        $db_conn_name = $this->getDbName($group_name);
        $CI = & get_instance();
        if(isset($CI->{$db_conn_name}) && is_object($CI->{$db_conn_name})) {
            $this->db = $CI->{$db_conn_name};
        } else {
            $CI->{$db_conn_name} = $this->db = $this->load->database($group_name, TRUE);
        }
    }

    private function getDbName($group_name = '')
    {
        if($group_name == '') {
            $db_conn_name = 'db';
        } else {
            $db_conn_name = 'db_'.$group_name;
        }
        return $db_conn_name;
    }

    public function _filter($filter = array()){
        foreach ($filter as $key => $value) {
            if (is_array($value)) {
                $this->db->where_in($key,$value);
            } else {
                $this->db->where($key,$value);
            }
        }
    }

    /**
    * 获取
    *
    * @return void
    * @author
    **/
    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderby=''){

        $this->db->reconnect();

        $this->db->select($cols);
        $this->_filter($filter);
        $this->db->from($this->table_name());
        if ($orderby) $this->db->order_by($orderby);
        if ($limit < 0) $limit = '4294967295';
        $this->db->limit($limit,$offset);
        $list = $this->db->get()->result_array();
        return $list ? $list : array();
    }

    /**
    * 更新
    *
    * @return void
    * @author
    **/
    public function update($set,$filter){
        $this->db->reconnect();
        $this->_filter($filter);
        $res = $this->db->update($this->table_name(),$set);
        return ($res == true) ? $this->db->affected_rows() : false;
    }

    /**
    * undocumented function
    *
    * @return void
    * @author
    **/
    public function dump($filter,$cols='*'){

        $this->db->reconnect();
        $this->db->select($cols);
        $this->_filter($filter);
        $this->db->from($this->table_name());
        $this->db->limit(1,0);
        $list = $this->db->get()->row_array();
        return $list;
    }

    /**
    * 插入
    *
    * @return void
    * @author
    **/
    public function insert($data){
        $this->db->reconnect();
        $rs = $this->db->insert($this->table_name(),$data);
        return $rs ? $this->db->insert_id() : 0;
    }

    /**
    * 计算数量
    *
    * @return void
    * @author
    **/
    public function count($filter = array()){
        $this->db->reconnect();
        $this->db->select('1');
        $this->_filter($filter);
        $count = $this->db->count_all_results($this->table_name());
        return $count;
    }

    /**
    * 删除
    *
    * @return void
    * @author
    **/
    public function delete($filter){
        $this->db->reconnect();
        $this->_filter($filter);
        $res = $this->db->delete($this->table_name());
        return ($res == true) ? $this->db->affected_rows() : false;
    }

    /**
     * 处理富文本字段。
     * @param string $content
     * @return string
     */
    protected function handleRichText($content)
    {
        $content = str_replace('http://fruitday://Product?', 'fruitday://Product?', $content);
        $content = str_replace('http://fruitday://Guoshi_Baike?', 'fruitday://Guoshi_Baike?', $content);
        $content = str_replace('http://fruitday://Guoshi_Shequ?', 'fruitday://Guoshi_Shequ?', $content);
        $content = str_replace('http://fruitday://Guoshi_Activity?', 'fruitday://Guoshi_Activity?', $content);
        $content = $this->_initContentForRichText($content);

        return $content;
    }

    protected function _initContentForRichText($content){
        $html = <<<EOT
<!DOCTYPE html>
<html lang="zh-cn">
  <head>
    <meta charset="utf-8">
    <title>天天果园</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="X-UA-Compatible" content="IE-9">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="renderer" content="webkit">
    <meta name="format-detection" content="telephone=no">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">
    <meta content='black' name='apple-mobile-web-app-status-bar-style' />

    <link rel="shortcut icon" href="favicon.ico" >
    <link rel="apple-touch-icon" href="touch-icon-iphone.png">
    <link rel="apple-touch-icon" sizes="76x76" href="touch-icon-ipad.png">
    <link rel="apple-touch-icon" sizes="120x120" href="touch-icon-iphone-retina.png">
    <link rel="apple-touch-icon" sizes="152x152" href="touch-icon-ipad-retina.png">

    <!-- Bootstrap -->
    <link rel="stylesheet" href="http://m.fruitday.com/css/bootstrap.css">
    <link rel="stylesheet" href="http://m.fruitday.com/css/app.css">
    <!--[if lt IE 9]>
    <script src="http://cdn.bootcss.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <![endif]-->
</head>
<body>
    <section class="m-app-baike" style="padding-bottom:0">
        <div class="page-content">
            <p class="p-baike-img">
            $content
             </p>
        </div>
    </section>
</body>
</html>
EOT;
        return $html;
    }
}