<?php
class CI_Memcached{

    var $mem_obj;
    // var $ocs_address = '9c966cc2180411e4.m.cnhzalicm10pub001.ocs.aliyuncs.com'; // 老的逻辑
    // var $ocs_port = '11211'; // 老的逻辑  


    // 增加config/memcache.php配置文件
    // 蔡昀辰 2015
    function __construct(){
        if(!OPEN_MEMCACHE)
            return;

        $ci = &get_instance();
        $ci->config->load("memcache", true, true);
        $memcache_config = $ci->config->item('memcache');

        if (!$memcache_config['enable'])
            return;

        // $memc = new Memcached('ocs');
        $memc = new Memcached;
        // if (count($memc->getServerList()) == 0){
            $memc->setOption(Memcached::OPT_COMPRESSION, false);
            $memc->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
            $memc->addServer($memcache_config['address'], $memcache_config['port']);
            // $memc->addServer($this->ocs_address, $this->ocs_port); // 老的逻辑
        // }
        $this->mem_obj = $memc;

    }

   
  
    function set($key,$value, $expiration = 0){
        $this->mem_obj->set($key, $value, $expiration);
        $this->mem_obj->quit();
    }

    function get($key){
       $mem_result = $this->mem_obj->get($key);
       $this->mem_obj->quit();
       return $mem_result;
    }

    function quit(){
        //$this->mem_obj->quit();
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    function delete($key)
    {
        $this->mem_obj->delete($key);
        $this->mem_obj->quit();
    }

}
