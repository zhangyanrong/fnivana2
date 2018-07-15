<?php
class O2o_region_model extends MY_Model {
    
    public $attr = array(
                         1=>'province',
                         2=>'city', 
                         3=>'area', 
                         4=>'bszone', 
                         5=>'building' 
    );

    public function table_name(){
        return 'o2o_region';
    }

    /**
     * 获取父节点
     *
     * @return void
     * @author 
     **/
    public function getParents($pid = 0)
    {
        static $parents;

        if ($pid == 0){
            $return = $parents;
            $parents = array();
            return $return;
        }

        $r = $this->getRegion($pid);

        $parents[] = $r;
        
        return $this->getParents($r['pid']);
    }

    /**
     * 获取行政区
     *
     * @return void
     * @author 
     **/
    public function getRegion($id)
    {
        static $regions;

        if ($regions[$id]) return $regions[$id];

        $regions[$id] = $this->dump(array('id'=>$id)); 

        return $regions[$id];
    }

    public function is_show($id)
    {
        $row = $this->db->query('SELECT r.id FROM ttgy_o2o_region AS r LEFT JOIN ttgy_o2o_store_building AS b ON(r.id=b.building_id) WHERE r.attr=5 AND b.id is not null AND `path` like "%,'.$id.',%"')->row_array();

        return $row ? true : false;
    }

    public function get_region_by_connectid($connect_id){
        $this->db->select('region_id');
        $this->db->from('connectid_regionid');
        $this->db->where('connect_id',$connect_id);
        return $this->db->get()->row_array();
    }

    public function get_city_by_province_id($id){
        $this->db->select('pid');
        $this->db->from('area');
        $this->db->where('id',$id);
        $result = $this->db->get()->row_array();
        if($result['pid']==0){
            $this->db->select('id');
            $this->db->from('area');
            $this->db->where('pid',$id);
            $result = $this->db->get()->row_array();
            return $result['id'];
        }
        return $id;
    }

    /**
     * 保存
     *
     * @return void
     * @author
     **/
    public function save($data)
    {
        // 判断行政区是否已经存在
        if ($data['area_id']) {
            $region = $this->dump(array('area_id'=>$data['area_id']),'id');
            if ($region) {
                $rs = $this->update(array('name'=>$data['name']),array('id'=>$region['id']));

                return $region['id'];
            }
        }

        return $this->insert($data);
    }

}