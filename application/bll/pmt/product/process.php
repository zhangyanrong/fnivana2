<?php
// 老购物车优惠引擎
// 弃用！
// 蔡昀辰 2016
namespace bll\pmt\product;

class Process{

    private $_cart_info = array();

    public function __construct()
    {
        $this->ci = & get_instance();
    }

    public function get_cart()
    {
        return $this->_cart_info;
    }

    public function set_cart($cart_info)
    {
        $this->_cart_info = $cart_info;
    }

    public function set_region_id($region_id)
    {
        $this->_region_id = $region_id;
    }

    public function cal($itemkey,$item)
    {
        $pmtlist = $this->get_pmt($item['product_id']);

        if ($pmtlist)
            foreach ($pmtlist as $pmt) {
                $name = 'bll_pmt_product_condition_'.$pmt['pmt_type'];
                $this->ci->load->bll('pmt/product/condition/'.$pmt['pmt_type'],null,$name);

                if (!$this->ci->{$name} instanceof condition\Condition_abstract) {
                    show_error("{$name} is not subclass of Condition_abstract");
                }

                $filter = $this->ci->{$name}->filter($pmt['condition'],$item,$this->_cart_info);

                if ($filter === false) {
                    $tag = $this->ci->{$name}->get_tag();

                    // if ($tag)  $this->_cart_info['items'][$itemkey]['tags'][] = $tag;

                    $pmt_price_total = 0;
                    foreach ($pmt['solution'] as $type=>$solution) {
                        $name = 'bll_pmt_product_solution_'.$type;
                        $this->ci->load->bll('pmt/product/solution/'.$type,null,$name);

                        if (!$this->ci->{$name} instanceof solution\Solution_abstract) {
                            show_error("{$name} is not subclass of Solution_abstract");
                        }                        

                        $pmt_price = $this->ci->{$name}->set_pmt_id($pmt['pmt_id'])->process($itemkey,$item,$solution,$this->_cart_info);

                        if ($type == 'money') $pmt_price_total += $pmt_price;
                    }

                    if ($pmt_price_total){
                        $this->_cart_info['items'][$itemkey]['pmt_details'][] = array('pmt_id'=>$pmt['pmt_id'],'pmt_type'=>$pmt['pmt_type'],'pmt_price'=>$pmt_price_total,'tag'=>$tag);
                    }

                    if ($pmt['exclusive'] == true) break;
                }
            }
    }

    /**
     * 
     *
     * @return void
     * @author 
     **/
    public function pmt_alert()
    {
        if (!$this->_cart_info) return;

        $pmtlist = $this->get_all_pmt();

        if ($pmtlist)
            foreach ($pmtlist as $pmt) {
                $name = 'bll_pmt_product_condition_'.$pmt['pmt_type'];
                $this->ci->load->bll('pmt/product/condition/'.$pmt['pmt_type'],null,$name);
                
                if (!$this->ci->{$name} instanceof condition\Condition_abstract) {
                    show_error("{$name} is not subclass of Condition_abstract");
                }

                $meet = $this->ci->{$name}->meet($pmt['pmt_id'],$pmt['condition'],$this->_cart_info);

                if ($meet === true) {

                    $pmt_alert = array('pmt_type' => $pmt['pmt_type'],'solution'=>array());

                    if($name!='bll_pmt_product_condition_dapeigou'){
                        $pmt_alert['solution'] = $this->ci->{$name}->get_alert_title($pmt['pmt_id'],$pmt,$this->_cart_info);
                    }else{
                        continue;
                    }

                    $this->_cart_info['pmt_alert'][] = $pmt_alert;

                    // if ($pmt['exclusive'] == true) break;
                }
            }   

    }

    /**
     * 获取商品优惠提醒方案
     *
     * @return void
     * @author 
     **/
    public function get_pmt_alert_solution($pmt_type, $pmt_id, $use_exch_as_limit2buy = false)
    {
        if ($use_exch_as_limit2buy and $pmt_type === 'singleskuexch') {
            $pmt_type = 'limit2buy';
        }

        $this->ci->load->model('promotion_model');

        $pmt = array();

        switch ($pmt_type) {
            case 'singleskuexch':
                $pmt = $this->ci->promotion_model->get_one_single_promotion($pmt_id);

                if ($pmt) {
                    $pmt = $this->_format_pmt($pmt,$pmt_type);
                }
                break;
            case 'dapeigou':
                $pmt = $this->ci->promotion_model->get_one_sale_rule($pmt_id);

                if ($pmt) {
                    $pmt = $this->_format_pmt($pmt,$pmt_type);
                }
                break;
            case 'limit2buy':
                $pmt = $this->ci->promotion_model->get_limit2buy_promotion($pmt_id);

                if ($pmt) {
                    $pmt = $this->_format_pmt($pmt, $pmt_type);
                }
            default:
                # code...
                break;
        }

        if (!$pmt || !$this->_cart_info) return array();

        $name = 'bll_pmt_product_condition_'.$pmt_type;
        $this->ci->load->bll('pmt/product/condition/'.$pmt_type,null,$name);

        if (!$this->ci->{$name} instanceof condition\Condition_abstract) {
            show_error("{$name} is not subclass of Condition_abstract");
        }

        // 判断有满足条件的么
        $meet = $this->ci->{$name}->meet($pmt_id,$pmt['condition'],$this->_cart_info);

        $data = array();
        
        if ($meet) {
            $data = array('pmt_type' => $pmt_type,'solution'=>array());
            $data['solution'] = $this->ci->{$name}->get_solution($pmt_id,$pmt['solution'],$this->_cart_info); 
        }
        return $data;
    }

    /**
     * 获取优惠促销
     *
     * @return void
     * @author 
     **/
    private function get_pmt($product_id)
    {
        $pmtlist = $this->get_all_pmt();

        foreach ($pmtlist as $key => $value) {
            if (!in_array($product_id,$value['condition']['product_id'])){
              unset($pmtlist[$key]);
              continue;  
            }
        }

        $giftpmt = array();

        // 商品赠品
        $this->ci->load->model('gift_model');
        $giftlist = $this->ci->gift_model->getlist('*',array('pid'=>$product_id));

        if ($giftlist) {
            foreach ($giftlist as $key => $value) {

                $giftpmt[] = array(
                    'pmt_type' => 'gifthas',
                    'condition' => array('product_id' => $product_id,'qty' => $value['gleast']),
                    'solution' => array('gift' => array('gift' => $value)),
                );
            }
        }
        

        return array_merge($giftpmt,$pmtlist);
    }

    /**
     * 获取所有优惠促销
     *
     * @return void
     * @author 
     **/
    private function get_all_pmt()
    {
        static $pmtlist;

        if ($pmtlist) return $pmtlist;

        $this->ci->load->model('promotion_model');

        // 单品换
        $single_promo_ex = $this->ci->promotion_model->get_single_promotion(1);
        if ($single_promo_ex)
            foreach ($single_promo_ex as $key => $value) {
                $pmtlist[] = $this->_format_pmt($value,'singleskuexch');
            }

        // 搭配购
        $dpg_promo = $this->ci->promotion_model->get_sale_rule('1');
        if ($dpg_promo)
            foreach ($dpg_promo as $key => $value) {
                $pmtlist[] = $this->_format_pmt($value,'dapeigou');
            }

        // 满额折
        $mez_promo = $this->ci->promotion_model->get_sale_rule('2');
        if ($mez_promo)
            foreach ($mez_promo as $key => $value) {
                $pmtlist[] = $this->_format_pmt($value,'manezhe');
            }

        // 单品促销
        $single_promo = $this->ci->promotion_model->get_single_promotion(2);
        if ($single_promo)
            foreach ($single_promo as $key => $value) {
                $pmtlist[] = $this->_format_pmt($value,'singleskuqty');
            }

        // 捆绑促销
        $pkg_promo = $this->ci->promotion_model->get_pkg_promotion();
        if ($pkg_promo)
            foreach ($pkg_promo as $key => $value) {
                $pmtlist[] = $this->_format_pmt($value,'pkgqty');
            }

        return $pmtlist ? $pmtlist : array();        
    }

    /**
     * 优惠转成统一结构
     *
     * @return void
     * @author 
     **/
    private function _format_pmt($pmt,$pmt_type)
    {
        $data = array();

        switch ($pmt_type) {
            case 'dapeigou': // 搭配购
                $money = $product_id = array();

                $relative_rule = unserialize($pmt['relative_rule']);
                foreach ($relative_rule as $r) {
                    $product_id[] = $r['product_id'];

                    $money[$r['product_id']] = array('product_id' => $r['product_id'],'price' => $r['product_price'],'math' => 'dapeigou','main_product_id'=>$pmt['main_rule']);
                }

                $data = array(
                    'condition' => array('main_product_id'=>$pmt['main_rule'],'product_id'=>$product_id),
                    'solution' => array('money'=>$money),
                    'pmt_type' => $pmt_type,
                    'exclusive' => true,
                    'pmt_id' => $pmt['id'],
                );
                break;
            case 'manezhe':  // 满额折
                $money = array(); $product_id = array();

                $relative_rule = unserialize($pmt['relative_rule']);

                foreach ($relative_rule as $r) {
                    $money[$r['product_id']] = array('product_id' => $r['product_id'],'price' => $r['product_price'],'math' => 'equal');

                    $product_id[] = $r['product_id'];
                }

                $data = array(
                    'condition' => array('totalamount'=>$pmt['main_rule'],'product_id'=>$product_id),
                    'solution' => array('money'=>$money),
                    'pmt_type' => $pmt_type,
                    'exclusive' => true,
                    'pmt_id' => $pmt['id'],
                );
                break;
            case 'singleskuqty':
                $content = unserialize($pmt['content']);
                $money = array();
                foreach (explode(',', $pmt['product_id']) as $pid) {
                    $money[$pid] = array('product_id' => $pid,'price' => $content['cut_money'],'math'=>'sub');
                }

                $data = array(
                    'condition' => array('product_id'=>explode(',', $pmt['product_id']),'qty'=>$content['pro_upto_num']),
                    'solution' => array('money'=>$money),
                    'pmt_type' => $pmt_type,
                    'exclusive' => true,
                    'pmt_id' => $pmt['id'],
                );
                break;
            case 'singleskuexch':   // 换购
                $content = unserialize($pmt['content']);

                $data = array(
                    'condition' => array('product_id'=>explode(',', $pmt['product_id']),'pmt_id'=>$pmt['id']),
                    'solution' => array('exchage'=>array('addmoney'=>$content['addmoney'],'product_id'=>$content['product_id2'],'pmt_id'=>$pmt['id'],'dep_product_id'=>$pmt['product_id'])),
                    'pmt_type' => $pmt_type,
                    // 'exclusive' => true,
                    'pmt_id' => $pmt['id'],
                );
                break;
            case 'pkgqty':
                $product_id = array();$money = array();
                $content = unserialize($pmt['content']);
                foreach ($content as $k => $c) {
                    $product_id[] = $c['product_id'];

                    // $content[$k]['price'] = $c['cut_money'];
                    // $content[$k]['math'] = 'sub';

                    $money[$c['product_id']] = $c;
                    $money[$c['product_id']]['price'] = $c['cut_money'];
                    $money[$c['product_id']]['math'] = 'sub';
                }

                $data = array(
                    'condition' => array('product_id' => $product_id),
                    'solution' => array('money'=>$money),
                    'pmt_type' => $pmt_type,
                    'exclusive' => true,
                    'pmt_id' => $pmt['id'],
                );
                break;
            case 'limit2buy':
                $solution = [];

                foreach ($pmt['content_arr'] as $value) {
                    $solution[$value['product_id']] = $value;
                    unset($solution[$value['product_id']]['product_id']);
                }

                $data = [
                    'condition' => ['region_id' => $this->_region_id],
                    'solution' => $solution,
                    'pmt_type' => $pmt_type,
                    'pmt_id' => $pmt['id'],
                ];

                break;
            default:
                # code...
                break;
        }

        return $data;
    }

}