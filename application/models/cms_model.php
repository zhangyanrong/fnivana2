<?php
class Cms_model extends MY_Model {
    function __construct() {
        parent::__construct();
    }

    public function get_cms_model($id)
    {
        $sql = "select * from ttgy_cms_model where id='{$id}'";
        $data = $this->db->query($sql)->result_array();

        return $data;
    }

    public function get_cms_advertisement($id)
    {
        $data = array();
        $sql = "select * from ttgy_cms_advertisement where cms_id='{$id}'";
        $arr = $this->db->query($sql)->result_array();

        foreach ($arr as $val) {
                $imgArr[0] = $val['img1'];
                $imgArr[1] = $val['img2'];
                $imgArr[2] = $val['img3'];
                $thumbs[0] = $val['thumbs1'];
                $thumbs[1] = $val['thumbs2'];
                $thumbs[2] = $val['thumbs3'];
                $url[0] = $val['url1'];
                $url[1] = $val['url2'];
                $url[2] = $val['url3'];
                $proId[0] = $val['pro_id1'];
                $proId[1] = $val['pro_id2'];
                $proId[2] = $val['pro_id3'];
                unset($val['img1']);
                unset($val['img2']);
                unset($val['img3']);
                unset($val['thumbs1']);
                unset($val['thumbs2']);
                unset($val['thumbs3']);
                unset($val['url1']);
                unset($val['url2']);
                unset($val['url3']);
                unset($val['pro_id1']);
                unset($val['pro_id2']);
                unset($val['pro_id3']);
                $val['imgArr'] = $imgArr;
                $val['thumbs'] = $thumbs;
                $val['url'] = $url;
                $val['proId'] = $proId;
                array_push($data, $val);
        }

        return $data;
    }

}