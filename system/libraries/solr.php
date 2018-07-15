<?php
class CI_Solr{

    var $solr_query;
    var $solr_obj;
    function __construct($params){
        $ci = &get_instance();
        $ci->config->load("solr", true, true);
        $solr_config = $ci->config->item('solr');
        $path = $params['path'];
        $disMax = $params['disMax']?true:false;
        if (!$solr_config['enable'])
            return;
        if (empty($path))
            return;

        $options = array(
            'hostname' => $solr_config['address'],
            'login' => $solr_config['username'],
            'password' => $solr_config['password'],
            'port' => $solr_config['port'],
            'path' => $path,
            );
        try {  
            $this->solr_obj = new SolrClient($options);
            $this->solr_obj->ping();
        } catch (Exception $e) {  
            return;
        }  
        if($disMax){
            $this->solr_query = new SolrDisMaxQuery();
            if($params['EdisMax'] === true){
                $this->solr_query->useEDisMaxQueryParser();
            }else{
                $this->solr_query->useDisMaxQueryParser();
            }
        }else{
            $this->solr_query = new SolrQuery();
        }
    }

    function setTerms($bool = true){
        $this->solr_query->setTerms($bool);
        return $this;
    }

    function setQuery($filed = "*",$value = ''){
        if($value){
            $q = $filed.":".$value;
        }else{
            $q = $filed;
        }
        $this->solr_query->setQuery($q);
        return $this;
    }

    function addField($filed){
        $this->solr_query->addField($filed);
        return $this;
    }

    function sort($filed,$order=1){
        $this->solr_query->addSortField('score',1);
        $this->solr_query->addSortField($filed,$order);
        return $this;
    }

    function addFilterQuery($filed,$filter,$or = false){
        $fq = '';
        $con = " AND ";
        $or AND $con = " OR ";
        if(is_array($filter)){
            foreach($filter as $value){
                $fq .= $filed.":".$this->escape($value).$con;
            }
            $fq = rtrim($fq,$con);
        }else{
            $fq = $filed.":".$this->escape($filter);
        }
        $this->solr_query->addFilterQuery($fq);
    }

    function addRegionFilter($region_id){
        $region_id = trim($region_id,',');
        $region_id_m = "*,".$region_id.",*";
        $region_id_l = ",".$region_id.",*";
        $region_id_r = "*,".$region_id.",";
        $region_id_b = ",".$region_id.",";
        $filter = array($region_id_m,$region_id_l,$region_id_r,$region_id_b,',,');
        $this->addFilterQuery('region_id',$filter,true);
        return $this;
    }
    
    function limit($limit = 10,$offset = 0){
        $this->solr_query->setRows($limit);
        $this->solr_query->setStart($offset);
        return $this;
    }

    function query(){
        $result = array();
        $this->solr_obj->setResponseWriter("json");
        $response = $this->solr_obj->query($this->solr_query);
        if($response->success()){
            $result = $response->getResponse()->response;
            return $result;
        }
        return false;
    }
    
    function escape($value){
        //$pattern = '/(\+|-|&|\||!|\(|\)|\{|}|\[|]|\^|"|~|\?|:|;|~|\/)/';
        $pattern = '/(\+|-|&|\||!|\(|\)|\{|}|\^|"|~|\?|:|;|~|\/)/';
        $replace = '\\\$1';
        return preg_replace($pattern, $replace, $value);
    }
    
    function addGroupField($filed){
        $this->solr_query->addGroupField($filed);
    }
    
    function setGroup($bool = true){
        $this->solr_query->setGroup($bool);
    }
    
    function setGroupMain($bool = true){
        $this->solr_query->setGroupMain($bool);
    }

    function groupBy($filed){
        $this->setGroup();
        $this->setGroupMain();
        $this->addGroupField($filed);
        return $this;
    }

    //设置字段评分，仅disMax为TRUE时使用
    function addQueryField($filed,$value){
        $this->solr_query->addQueryField($filed,$value);
    }

    function setBoostFunction($function){
        $this->solr_query->setBoostFunction($function);
    }

    function addBoostQuery($filed,$value,$boost){
        $this->solr_query->addBoostQuery($filed,$value,$boost);
    }
 
    function addGroupSortField($filed,$sort=0){
        $this->solr_query->addGroupSortField($filed,$sort);
    }
}
