<?php
/**
 * Productredis v2.0
 * @author 蔡昀辰 2017-05-27
 * - master & slave
 * - random slave
 */
class Productredis {

    static $master;
    static $slave;
    static $read_only_funcs = [
        'GET', 'HGET', 'HMGET', 'HGETALL', 'EXISTS'
    ];

    public function __construct() {

        // config
        $ci = &get_instance();
        $ci->load->config('product_redis', true);
        $this->config = $ci->config->item('product_redis');

        if( !isset($this->config['slave']) )
            $this->config['slave'] = $this->config['master'];
        elseif( is_array($this->config['slave']) )
            $this->config['slave'] = $this->getRand($this->config['slave']);

    }

    private function getRand($items) {
        $max = count($items) - 1;
        $key = rand(0, $max);
        return $items[$key];
    }

    private function isReadFunc($func_name) {
        $func_name = strtoupper($func_name);
        if( in_array($func_name, self::$read_only_funcs) )
            return true;
    }

    private function getMaster() {
        // echo ":master\n";

        if(!self::$master) {
            self::$master = new Redis();
            self::$master->connect($this->config['master'], 6379, 1);
        }

        return self::$master;

    }

    private function getSlave() {
        // echo ":slave\n";

        if(!self::$slave) {
            self::$slave = new Redis();
            self::$slave->connect($this->config['slave'], 6379, 2);
        }

        return self::$slave;

    }

    ///////////////////////
    // here is the magic //
    ///////////////////////
    public function __call($func, $arguments) {
        // echo "$func";

        if( !$this->isReadFunc($func) )
            $obj = $this->getMaster();
        else
            $obj = $this->getSlave();

        return call_user_func_array([$obj,$func], $arguments);

    }

}
