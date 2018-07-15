<?php
class Promotion_v2_model extends CI_model {

    public $strategies = [];

    /**
     * [loadStrategies description]
     * @param  array $stores   store_ids
     * @param  number $member   user_rank
     * @param  string $source   app/pc/wap
     * @param  string $version  4.0.0
     * @return array  $strategies
     */
    public function loadStrategies($stores, $member, $source, $version = null) {

        $this->strategies = [];

        $this->load->model('amount_v2_model');
        $this->load->model('quantity_v2_model');
        $this->load->library('phpredis');
        $redis = $this->phpredis->getConn();

        $now = time();
        // fix
        if(!$member)
            $member = 1;

        // $strategies = json_decode($redis->get("cart_v2:promotion:{$now}"));
        // if(!$strategies) {
            $query = $this->db
                ->select('*')
                ->from('promotion_v2')
                ->where('active', 1)
                ->where('start <=', $now)
                ->where('end >=', $now);

            $query->like('range_sources', "\"{$source}\"", 'match');

            $query->like('range_members', "\"{$member}\"", 'match');

            $strategies = $query->get()->result();
            // $redis->setex("cart_v2:promotion:{$now}", 1, json_encode($strategies));
        // }

        // echo json_encode($strategies);die;

        // decode json
        foreach( $strategies as &$strategy ) {
            $strategy->range_sources     = json_decode($strategy->range_sources);
            $strategy->range_members     = json_decode($strategy->range_members);
            $strategy->range_stores      = json_decode($strategy->range_stores);
            $strategy->range_products    = json_decode($strategy->range_products);
            $strategy->solution_products = json_decode($strategy->solution_products);
        }
        // echo json_encode($strategies);die;

        // new strategy
        foreach( $strategies as $s ) {
            if($s->condition_type == 'amount')
                $this->strategies[] = $this->amount_v2_model->init($s);
            if($s->condition_type == 'quantity')
                $this->strategies[] = $this->quantity_v2_model->init($s);
        }

        // print_r($this->strategies);die;
        return $this;

    }

    /**
     * 获取一条优惠规则
     * @param  Number $pmt_id 优惠规则id
     * @return Object $strategy
     */
    public function getOneStrategy($pmt_id = null) {
        $query = $this->db->select('*')
            ->from('promotion_v2')
            ->where('active', 1)
            ->where('id', $pmt_id);
        $strategy = $query->get()->row();

        $strategy->range_sources     = json_decode($strategy->range_sources);
        $strategy->range_members     = json_decode($strategy->range_members);
        $strategy->range_stores      = json_decode($strategy->range_stores);
        $strategy->range_products    = json_decode($strategy->range_products);
        $strategy->solution_products = json_decode($strategy->solution_products);

        return $strategy;
    }

    // TODO 同步到cart v3
    public function getSingleStrategy($product_id, $store_id) {

        $query = $this->db->select('*')
            ->from('promotion_v2')
            ->where('range_type', 'single')
            ->where('range_products', '["'.$product_id.'"]')
            ->like('range_stores', '"'.$store_id.'"')
            ->where('active', 1);
        $strategy = $query->get()->row();

        $strategy->range_sources     = json_decode($strategy->range_sources);
        $strategy->range_members     = json_decode($strategy->range_members);
        $strategy->range_stores      = json_decode($strategy->range_stores);
        $strategy->range_products    = json_decode($strategy->range_products);
        $strategy->solution_products = json_decode($strategy->solution_products);

        return $strategy;

    }

    // 执行优惠
    public function implementStrategies($products, $cart) {

        foreach( $this->strategies as $strategy ) {

            if($strategy->range_type == 'all')
                $strategy->all($products, $cart);
            if($strategy->range_type == 'group')
                $strategy->group($products, $cart);
            if($strategy->range_type == 'single')
                $strategy->single($products, $cart);

        }

    }

}
