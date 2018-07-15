<?php
namespace bll\pool;

class Order
{
    private  $_online_pay = array();

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->_online_pay = $this->ci->config->item("oms_online_pay");
        $this->_da_zha_xie = $this->ci->config->item("da_zha_xie");
        $this->_xian_huo = $this->ci->config->item("xian_huo");
    }

    /**
     * 地区匹配
     *
     * @return void
     * @author 
     **/
    private function region_match($province,$city,$area)
    {
        $province = trim($province); $city = trim($city); $area = trim($area);

        $region3 = array(
            '浙江省' => array(
                '绍兴市' => array(
                    '上虞市' => '上虞区',
                    ),
                ),
            '江苏省' => array(
                '无锡市' => array('新区' => '滨湖区'),
                '苏州市' => array(
                        '平江区' => '姑苏区',
                        '沧浪区' => '姑苏区',
                        '金阊区' => '姑苏区',
                        '吴江市' => '吴江区',
                        '高新区' => '虎丘区',
                        '苏州工业园区' => '姑苏区',
                    ),
                '南通市' => array(
                        '通州市' => '通州区',
                        '南通经济技术开发区' => '崇川区',
                    ),
                '淮安市' => array(
                        '楚州区' => '淮安区',
                        '经济开发区' => '清河区',
                    ),
                '扬州市' => array(
                        '江都区' => '江都市',
                        '维扬区' => '邗江区',
                        '开发区' => '邗江区',
                        '胥浦区' => '仪征市',
                    ),
                '徐州市' => array(
                        '铜山县' => '铜山区',
                    ),
            ),
            // '浙江省' => array(

            // ),
            '上海' => array(
                '上海市' => array(
                        '浦东新区（外环线以内）' => '浦东新区',
                        '浦东新区（外环线以外，郊环线以内）' => '浦东新区',
                        '浦东新区（郊环线以外）' => '浦东新区',
                        '卢湾区' => '黄浦区',
                        '普陀区（外环以内）' => '普陀区',
                        '闵行区（外环线以内及莘庄地区）' => '闵行区',
                        '闵行区（外环线以外除莘庄地区）' => '闵行区',
                        '宝山区（外环线以内）' => '宝山区',
                        '宝山区（外环线以外）' => '宝山区',
                        '嘉定区（郊环线以内）' =>'嘉定区',
                        '嘉定区（郊环线以外）' =>'嘉定区',
                        '青浦区（郊环线以内）' =>'青浦区',
                        '青浦区（郊环线以外）' =>'青浦区',
                        '松江区（郊环线以内）' =>'松江区',
                        '松江区（郊环线以外）' =>'松江区',
                        '奉贤区（郊环线以内）' =>'奉贤区',
                        '奉贤区（郊环线以外）' =>'奉贤区',
                        '金山区（郊环线以内）' =>'金山区',
                        '金山区（郊环线以外）' =>'金山区',
                       ' 普陀区（外环以外）' =>'普陀区',
                        '宝山区（郊环线以外）' =>'宝山区',
                    ),
            ),
            '安徽省' => array(
                '合肥市' => array(
                        '滨湖新区' => '包河区',
                    ),
                '芜湖市' => array(
                        '新芜区' => '芜湖县',
                        '经济技术开发区' => '鸠江区',
                    ),
                '蚌埠市' => array(
                        '高新技术产业开发区' => '禹会区',
                        '新城开发区' => '蚌山区',
                    ),
                '淮南市' => array(
                        '高新技术产业开发区' => '大通区',
                    ),
                '马鞍山市' => array(
                        '金家庄区' => '花山区',
                    ),
                '阜阳市' => array(
                        '阜阳经济技术开发区' => '颍东区',
                    ),
                '铜陵市' => array(
                        '新城区'=> '狮子山区',
                    ),
            ),
            '北京' => array(
                '北京市' => array(
                        '宣武区' => '西城区',
                        '崇文区' => '东城区',
                        '朝阳区（六环内）'=>'朝阳区',
                        '丰台区（六环内）'=>'丰台区',
                        '石景山区（六环内）'=>'石景山区',
                        '大兴区（六环内）'=>'大兴区',
                        '通州区（六环内）'=>'通州区',
                        '房山区（六环内）'=>'房山区',
                        '昌平区（六环内）'=>'昌平区',
                        '顺义区（六环内）'=>'顺义区',
                        '丰台区（五环内）'=>'丰台区',
                        '石景山区（五环内）'=>'石景山区',
                        '海淀区（五环内）'=>'海淀区',
                        '朝阳区（五环内）'=>'朝阳区',
                    ),
            ),
            '北京（五环外）' => array(
                '北京市（五环外）' => array(
                        '宣武区' => '西城区',
                        '崇文区' => '东城区',
                        '海淀区（五坏外）'=>'海淀区',
                        '朝阳区（五环外）'=>'朝阳区',
                    ),
            ),
            '陕西省' => array(
                '宝鸡市' => array(
                    '高新技术产业开发' => '金台区',
                ),
                '西安市'=> array(
                    '高新技术开发区' => '雁塔区',
                    '西安经济技术' => '未央区',
                ),
                '渭南市' => array(
                    '高新经济开发区' => '临渭区',
                ),
            ),
            '河南省' => array(
                '郑州市' => array(
                    '高新区' => '中原区',
                    '郑东新区' => '金水区',
                    ),
                '洛阳市' => array(
                    '瀍河区' => '瀍河回族区',
                    '高新技术开发区' => '洛龙区',
                    '经济技术开发区' => '洛龙区',
                    ),
            ),
            '河北省' => array(
                '承德市' => array(
                    '开发西区' => '双桥区',
                    '开发东区' => '双桥区',
                    '承德高教园区' => '双桥区',
                    ),
                '唐山市' => array(
                    '高新技术开发区' => '路北区',
                    '唐海县' => '曹妃甸区',
                    ),
                '沧州市' => array(
                    '沧州市经济技术开发区' => '新华区'
                    ),
                '邯郸市' => array(
                    '邯郸市经济技术开发区' => '邯郸县',
                    '武安' => '武安市',
                    '邯郸经济开发区' => '邯郸县',
                    ),
                '衡水市' => array(
                    '衡水高新技术开发区' => '桃城区',
                    ),
                '邢台市'=> array(
                    '高开区'=>'桥东区',
                    ),
                '张家口市' => array(
                    '高新开发区'=>'桥西区',
                    ),
            ),
            '山西省' => array(
                '运城市' => array(
                    '空港新区' => '盐湖区',
                    '禹都经济技术开发区' => '盐湖区',
                    ),
                '长治市' => array(
                    '高新技术开发区' => '城区',
                    ),
                ),
            '天津省' => array(
                '天津市' => array(
                    '大港区' => '滨海新区',
                    '汉沽区' => '滨海新区',
                    '塘沽区' => '滨海新区',
                    ),
            ),
            '山东省' => array(
                '枣庄市' => array(
                    '高新区' => '薛城区',
                    ),
                '济南市' => array(
                    '高新技术开发区' => '历下区',
                    '济阳' => '济阳县',
                    ),
                '滨州市' => array(
                    '滨州高新区' => '滨城区',
                    '滨州经济开发区' => '滨城区',
                    ),
                '德州市' => array(
                    '经济开发区' => '德城区',
                    '商贸开发区' => '德城区',
                    '天衢工业园' => '德城区',
                    ),
                '聊城市' => array(
                    '经济技术开发区' => '东昌府区',
                    ),
                '烟台市' => array(
                    '高新区' => '福山区',
                    ),
            ),
            '吉林省' => array(
                '长春市' => array(
                    '汽车产业开发区' => '绿园区',
                    '经济技术产业开发区' => '二道区',
                    '净月潭旅游经济开发区' => '二道区',
                    ),
            ),
            '辽宁省' => array(
                '沈阳市' => array(
                    '铁西新区' => '铁西区',
                    '浑南新区' => '东陵区',
                    ),
                '大连市' => array(
                    '高新园区' => '甘井子区',
                    ),
            ),
            '广东省' => array(
                '韶关市' => array(
                    '曲江县' => '曲江区',
                    '乳源县' => '乳源瑶族自治县',
                    ),
                '江门市' => array(
                    '新会市' => '新会区',
                    '长沙区' => '开平市',
                    '三埠区' => '开平市',
                    ),
                '湛江市' => array(
                    '湛江经济技术开发区' => '赤坎区',
                    ),
                '肇庆市' => array(
                    '大旺高新技术开发区' => '四会市',
                    ),
                '惠州市' => array(
                    '大亚湾区' => '惠阳区',
                    ),
                '汕尾市' => array(
                    '汕尾城区' => '城区',
                    '红海湾区' => '城区',
                    ),
                '清远市' => array(
                    '清新区' => '清新县',
                    ),
                '揭阳市' => array(
                    '渔湖经济开发试验区' => '榕城区',
                    '揭东区' => '揭东县',
                    '东山区' => '榕城区',
                    ), 
                '潮州市' => array(
                    '枫溪区' => '潮安县',
                    '潮安区' => '潮安县',
                    ),
                '深圳市' => array(
                    '光明新区' => '宝安区',
                    ),
            ),
            '海南省' => array(
                '白沙黎族自治县' => array(
                    '白沙县' => '白沙黎族自治县',
                    ),
                '昌江黎族自治县' => array(
                    '昌江县' => '昌江黎族自治县',
                    ),
                '陵水黎族自治县' => array(
                    '陵水县' => '陵水黎族自治县',
                    ),
                '保亭黎族苗族自治县' => array(
                    '保亭县' => '保亭黎族苗族自治县',
                    ),
                '琼中黎族苗族自治县' => array(
                    '琼中县' => '琼中黎族苗族自治县',
                    ),
                '乐东黎族自治县' => array(
                    '乐东县' => '乐东黎族自治县',
                    ),
            ),
            '广西省' => array(
                '桂林市' => array(
                    '临桂区' => '临桂县',
                    ),
                '贺州市' => array(
                    '平桂区' => '平桂管理区',
                    ),
                '梧州市' => array(
                    '龙圩区' => '苍梧县',
                    ),
                '钦州市' => array(
                    '钦州港经济技术开发区' => '钦南区',
                    ),
            ),
            '湖南省' => array(
                '   ' => array(
                    '西洞庭管理区' => '武陵区',
                    ),
                '娄底市' => array(
                    '娄底经济开发区' => '娄星区',
                    ),
                '岳阳市' => array(
                    '汩罗市' => '汨罗市',
                    '经济技术开发区' => '岳阳楼区',
                    '洞庭湖旅游度假区' => '岳阳楼区',
                    ),
                '株洲市' => array(
                    '云龙示范区' => '荷塘区',
                    ),
                '怀化市' => array(
                    '洪江区' => '洪江市',
                    ),

            ),
            '四川省' => array(
                '成都市' => array(
                    '高新区' => '武侯区',
                    '高新西区' => '郫县',
                    ),
                '绵阳市' => array(
                    '高新技术开发区' => '涪城区',
                    ),
                '宜宾市' => array(
                    '南溪县' => '南溪区',
                    ),
                // '巴中市' => array(
                //     '恩阳区' => '',
                //     ),
                // '广安市' => array(
                //     '前锋区' =>　'',
                //     ),
                // '雅安市' => array(
                //     '名山区' => '',
                //     ),

            ),
            '重庆' => array(
                '重庆市' => array(
                    '大足县' => '大足区',
                    '高新区' => '渝北区',
                    '万盛区' => '綦江区',
                    ),
            ),
            '云南省' => array(
                '昆明市' => array(
                    '呈贡县' => '呈贡区',
                    '石林县' => '石林彝族自治县',
                    ),
                '普洱市' => array(
                    '宁洱县' => '宁洱哈尼族彝族自治县',
                    ),
            ),
            '贵州省' => array(
                '安顺市' => array(
                    '开发区' => '西秀区',
                    ),
                '贵阳市' => array(
                    '金阳新区' => '乌当区',
                    ),
                '六盘水市' => array(
                    '红桥新区' => '水城县',
                    ),
            ),
            '湖北省' => array(
                '武汉市' => array(
                    '东湖新技术开发区' => '洪山区',
                    '武汉吴家山经济技术开发区' => '汉阳区',
                    '武汉经济技术开发区' => '汉阳区',
                    )
            ),
            '江西省' => array(
                '南昌' => array(
                    '南昌高新技术开发区' => '南昌县',
                    '昌北经济技术开发区'=> '青山湖区',
                    '红谷滩新区' => '东湖区',
                    ),
                '抚州市' => array(
                    '金巢经济开发区' => '临川区',
                    ),
                '九江市' => array(
                    '城西港区' => '庐山区',
                    '九江经济技术开发区' => '庐山区',
                    ),
            ),
            '上海崇明' => array(
                '崇明县' => array(
                        '崇明三岛' => '崇明县',
                    ),
            ),
            '上海市郊' => array(
                '崇明县' => array(
                        '崇明三岛' => '崇明县',
                    ),
            ),
            '上海崇明' => array(
                '崇明县' => array(
                        '崇明三岛' => '崇明县',
                    ),
            ),
        );
        
        $region1 = array(
            '广西省' => '广西壮族自治区',
            '上海崇明' => '上海',
            '上海市郊' => '上海',
            '上海崇明' => '上海',
            '天津省' => '天津市',
            '北京（五环外）'=>'北京',
        );
        $region2 = array(
            '上海崇明' => array(
                '崇明县' => '上海市',
                ),
            '上海市郊' => array(
                '崇明县' => '上海市',
                ),
            '上海崇明' => array(
                '崇明县' => '上海市',
                ),
            '云南省' => array(
                '云南红河州哈尼族彝族自治州' => '红河哈尼族彝族自治州',
                ),
            '海南省' => array(
                '东方市' => '省直辖',
                '白沙黎族自治县' => '省直辖',
                '昌江黎族自治县' => '省直辖',   
                '陵水黎族自治县' => '省直辖',
                '保亭黎族苗族自治县' => '省直辖',
                '琼中黎族苗族自治县' => '省直辖',
                '乐东黎族自治县' => '省直辖',
                '万宁市' => '省直辖',
                ),
            '湖北省' => array(
                '天门市'=>'省直辖',
                '仙桃市'=>'省直辖',
                '潜江市'=>'省直辖',
                '神农架'=>'省直辖',
                ),
            '北京（五环外）'=>array(
                '北京市（五环外）'=>'北京市',
                ),
            );

        $data = array(
            'province' => $region1[$province] ? $region1[$province] : $province,
            'city' => $region2[$province][$city] ? $region2[$province][$city] : $city,
            'area' => $region3[$province][$city][$area] ? $region3[$province][$city][$area] : $area,
            );

        if ($province == '海南省' && $city=='海口市' && in_array($area,array('文昌市','儋州市','琼海市'))) {
            $data['city'] = '省直辖';
        }
        if ($province == '青海省' && $city=='西宁市' && $area=='乐都县') {
            $data['city'] = '海东地区';
        }
        if ($province == '贵州省' && $city == '黔西南布依族苗族自治州' && $area == '都匀市') {
            $data['city'] = '黔南布依族苗族自治州';
        }

        return $data;
    }

    public function set_sync($orderid_arr,$sync_status)
    {
        $this->ci->load->model('order_model');

        $filter = array(
            'id' => $orderid_arr,
        );
        if($sync_status == 0){
            $filter['sync_status !='] = 1;
        }
        $affected_rows = $this->ci->order_model->update(array('sync_status'=>$sync_status),$filter);

        return $affected_rows;
    }


    public function week($sendDate,$shtime='',$cang_id='1',$deliver_type='1')
    {
        $sendtime = strtotime($sendDate);
        $this->ci->load->model('warehouse_model');
        $warehouse_info = $this->ci->warehouse_model->getWarehouseByID($cang_id);

        $add_send_time = 0;
        if($warehouse_info['send_type'] == 1){
            $add_send_time = 0;
        }else{
            $add_send_time = 86400;
        }

        $gdate = date('Ymd',$sendtime+$add_send_time);
        if($shtime == 'weekday') $type = 1;
        elseif($shtime == 'weekend') $type = 0;
        else return $sendDate;
        $n_date = $this->getNextWorkHoliDay($gdate,$type);
        if($n_date){
            if($warehouse_info['send_type'] == 1){
                $sendDate = date('Y-m-d',strtotime($n_date));
            }else{
                $sendDate = date('Y-m-d',strtotime($n_date)-24*3600);
            }
            return $sendDate;
        }

        if ($shtime == 'weekday') {
            $gdate = getdate($sendtime+$add_send_time);
            if ($gdate['wday'] == 6 || $gdate['wday'] == 0) {
                if($warehouse_info['send_type'] == 1){
                    $sendDate = date('Y-m-d',strtotime('next Monday',$sendtime));
                }else{
                    $sendDate = date('Y-m-d',strtotime('next Sunday',$sendtime));
                }
            }
        }

        if ($shtime == 'weekend') {
            $gdate = getdate($sendtime+$add_send_time);
            if (!in_array($gdate['wday'],array(0,6))) {
                if($warehouse_info['send_type'] == 1){
                    $sendDate = date('Y-m-d',strtotime('next Saturday',$sendtime));
                }else{
                    $sendDate = date('Y-m-d',strtotime('next Friday',$sendtime));
                }
            }
        }

        return $sendDate;
    }

    function getNextWorkHoliDay($date,$type=0){
        if($type == 1){
            $check_arr = array(1,2);
        }else{
            $check_arr = array(0);
        }
        if($date < date('Ymd')) $date = date('Ymd');
        $this->ci->load->library('phpredis');
        $redis = $this->ci->phpredis->getConn();
        $res = $redis->hget('holiday',$date);
        if(isset($res) && $res){
            if(in_array($res, $check_arr)){
                $next_date = date('Ymd',strtotime($date)+24*3600);
                return $this->getNextWorkHoliDay($next_date,$type);
            }else{
                return $date;
            }
        }
        return false;
    }

    /**
     * 两到三天配送时间计算
     *
     * @return void
     * @author 
     **/
    public function after2to3days($province,$area,$createtime,$shtime='',$cang_id=1)
    {
        $createtime = strtotime($createtime);

        $area_refelect = $this->ci->config->item("area_refelect");

        $send_h = date('H',$createtime);
        $sendtime = $send_h >= $area['cut_off_time'] ? ($createtime+172800) : ($createtime+86400);
        $sendDate = date('Y-m-d', $sendtime);
        return $this->week($sendDate,$shtime,$cang_id,1);

        // if (!in_array($province['id'],$area_refelect[1])) { //外地
        //     $send_h = date('H',$createtime);
        //     $sendtime = $send_h >= $area['cut_off_time'] ? ($createtime+86400) : $createtime;

        //     $sendDate = date('Y-m-d',$sendtime);

        //     if ($shtime == 'weekday') {
        //         $gdate = getdate($sendtime+86400);
        //         if ($gdate['wday'] == 6 || $gdate['wday'] == 0) {
        //             $sendDate = date('Y-m-d',strtotime('sunday',$sendtime));
        //         }
        //     }

        //     if ($shtime == 'weekend') {
        //         $gdate = getdate($sendtime+86400);
        //         if (!in_array($gdate['wday'],array(0,6))) {
        //             $sendDate = date('Y-m-d',strtotime('friday',($sendtime+86400)));
        //         }
        //     }

        //     return $sendDate;
        // } else {
        //     $send_h = date('H',$createtime);

        //     $sendtime = $send_h >= $area['cut_off_time'] ? ($createtime+172800) : ($createtime+86400);
        //     $sendDate = date('Y-m-d', $sendtime);

        //     if ($shtime == 'weekday') {
        //         $gdate = getdate($sendtime);
        //         if (!in_array($gdate['wday'],array(1,2,3,4,5))) {
        //             $sendDate = date('Y-m-d',strtotime('weekday',$sendtime));
        //         }
        //     }

        //     if ($shtime == 'weekend') {
        //         $gdate = getdate($sendtime);

        //         if (!in_array($gdate['wday'],array(0,6)) ) {
        //             $sendDate = date('Y-m-d',strtotime('saturday',$sendtime));
        //         }
        //     }

        //     return $sendDate;
        // }
    }

    /**
     * 订单校验
     *
     * @return void
     * @author 
     **/
    public function check_order($orders)
    {
        $error = array();
        foreach ($orders as $key => $order) {
            $goods_money = 0;

            foreach ($order['order_items'] as $k => $v) {
                $goods_money += $v['totalAmount'];

                if ($v['saletype'] == 2 && 0 != bccomp($v['totalAmount'], 0,3) && $v['add_discount'] == 0) {
                    $error[] = $order['orderNo']."  ".$v['saletype']."  ".$v['totalAmount'];
                    unset($orders[$key]);
                    continue 2;
                }

                if ($v['saletype'] == 1 && 0 == bccomp($v['totalAmount'], 0,3) && $v['add_discount'] == 0) {
                    $error[] = $order['orderNo']."  ".$v['saletype']."  ".$v['totalAmount'];
                    unset($orders[$key]);
                    continue 2;
                }

                unset($orders[$key]['order_items'][$k]['add_discount']);
            }

            $totalAmount = $goods_money+$order['freightFee']-$order['disamount']-$order['dedamount']+$order['invoice_info']['invTransFee'];
            $totalAmount = number_format($totalAmount,3,'.','');
            if (0 != bccomp($totalAmount, $order['totalAmount'],3)) {
                $error[] = $order['orderNo']."  ".$order['totalAmount']."=".$totalAmount."=".$goods_money."+".$order['freightFee']."-".$order['disamount']."-".$order['dedamount']."+".$order['invoice_info']['invTransFee'];
                unset($orders[$key]);
            }
            // if($order['is_special_card']){
            //     $orders[$key]['dedamount'] = 0;   
            // }
        }

        if ($error) {
            $this->ci->load->model('jobs_model');
            $emailList = array( 'songtao@fruitday.com','lusc@fruitday.com');
            foreach ($emailList as $email) {
                $emailContent = implode('、',$error);
                $this->ci->jobs_model->add(array('email'=>$email,'text'=>$emailContent,'title'=>"订单金额异常"), "email");  
            }
        }

        
        return $orders;
    }

    public function get_push_orders($order_names = array(),$valid = true)
    {
        $this->ci->load->model('order_model');
        
        // 同步15天以内的订单 
        $ftime = date('Y-m-d H:i:s',(time()-1296000));
        // $ftime = '2015-04-01 00:00:00';
        // $fk = "( sync_status=0 AND order_type!=3 AND order_type!=4 AND time>'".$ftime."' AND (pay_status=1 OR pay_parent_id=4) AND order_status='1' AND operation_id in ('0','1') AND channel!='7') OR ( (pay_status=1 OR pay_parent_id=4) AND sync_status=0 AND order_type!=3 AND order_type!=4 AND time>'".$ftime."' AND order_status='1' AND operation_id='2' AND erp_active_tag!='' AND channel='7' ) " ;
        $fk = "IF(order_type=9,lyg=9,'1=1') AND (( sync_status=0 AND order_type!=3 AND order_type!=4 AND order_type!=7 AND time>'".$ftime."' AND (pay_status=1 OR pay_parent_id=4) AND order_status='1' AND operation_id in ('0','1') AND channel!='7') OR ( (pay_status=1 OR pay_parent_id=4) AND sync_status=0 AND order_type!=3 AND order_type!=4 AND time>'".$ftime."' AND order_status='1' AND operation_id in(2,9) AND erp_active_tag!='' AND channel='7' ) OR ( pay_status=1 AND operation_id=5 AND sync_status=0 AND order_type!=3 AND order_type!=4 AND time>'".$ftime."' AND order_status='1' and pay_parent_id in (1,3,7,8,9,10,11,12) AND channel!='7'))" ;

        // 指定订单号
        if ($order_names) {
            $order_names = implode("','", $order_names);
            $fk = "IF(order_type=9,lyg=9,'1=1') AND ( ( order_name in('".$order_names."') AND order_type!=3 AND order_type!=4 AND (pay_status=1 OR pay_parent_id=4) AND order_status='1' AND operation_id in ('0','1') AND channel!='7') OR ( (pay_status=1 OR pay_parent_id=4)  AND order_status='1' AND operation_id in(2,9) AND erp_active_tag!='' AND channel='7' AND order_type!=3 AND order_type!=4 AND order_name in('".$order_names."') ) OR ( pay_status=1 AND pay_parent_id in (1,3,7,8,9,10,11,12) AND order_status='1' AND operation_id='5'  AND channel!='7' AND order_type!=3 AND order_type!=4 AND order_name in('".$order_names."') ) )" ;

            if ($valid === false) {
                $fk = "( order_name in('".$order_names."') AND order_status='1' AND operation_id != 5 ) ";
            }
        }

        $filter = array(
	       $fk => null,
	    );



        // 获取已支付或货到付款订单
        $orders = $this->ci->order_model->getList('*',$filter,0,500);

        if (!$orders) return array();
        
        // 线上支付方式与订单池的映射关系
       // $online_pay = array(
       //      '1' => array('way_id' => 1, 'platform_id'=>1003),
       //      '2' => array('way_id' => 1, 'platform_id'=>1002),
       //      '3' => array(
       //          'way_id' => 1, 
       //          'platform_id'=>1001,
       //          'children_platform_id' => array(
       //              '00021' => 1006,
       //              '00005' => 1004,
       //              '00003' => 1007,
       //              '00100' => 1010,
       //              '00101' => 1012,
       //              '00102' => 1011,
       //              '00103' => 1015,
       //              '00105' => 1016,
       //              '00106' => 1018,
       //          ),
       //      ),
       //      '5' => array('way_id' => 9, 'platform_id'=>null),
       //      '6' => array('way_id' => 5, 'platform_id'=>null),
       //      '7' => array('way_id' => 1, 'platform_id'=>1005),
       //      '8' => array('way_id' => 1, 'platform_id'=>1014),
       //      '9' => array('way_id' => 1, 'platform_id'=>1008),
       //      '10' => array('way_id'=> 1, 'platform_id'=>1013),
       //      '11' => array('way_id'=> 1, 'platform_id'=>1017),
       //      );

        // 组织结构
        $f_orders = array();

        $addressid_arr = $orderid_arr = $uid_arr = $ordername = array();
        foreach ($orders as $o) {
            $uid_arr[]       = $o['uid'];
            $orderid_arr[]   = $o['id'];
            if($o['address_id']) $addressid_arr[] = $o['address_id'];
            $ordername[] = $o['order_name'];
        }

        $users = array();
        if ($uid_arr) {
            $this->ci->load->model('user_model');
            $tmp_users = $this->ci->user_model->getList('id,uname,username,mobile,user_rank',array('id'=>$uid_arr),0,-1);
            foreach ((array) $tmp_users as $key => $value) {
                $users[$value['id']] = $value;
            }
            $black_users = $this->ci->user_model->checkUserBlackList($uid_arr);
            unset($tmp_users);
        }

        $order_addresses = array();
        $this->ci->load->model('order_address_model');
        $tmp_order_addresses = $this->ci->order_address_model->getList('*',array('order_id'=>$orderid_arr),0,-1);
        foreach ((array) $tmp_order_addresses as $key => $value) {
            $order_addresses[$value['order_id']] = $value;

            if ($value['province']) $areaid_arr[$value['province']] = $value['province'];
            if ($value['city']) $areaid_arr[$value['city']] = $value['city'];
            if ($value['area']) $areaid_arr[$value['area']] = $value['area'];
        }
        unset($tmp_order_addresses);

        $order_cart = array();
        $tmp_order_cart = $this->ci->db->select('order_id,content')->from('ttgy_order_cart')->where_in('order_id',$orderid_arr)->get()->result_array();
        foreach ($tmp_order_cart as $key => $value) {
            $content = json_decode($value['content'],true);
            $order_cart[$value['order_id']] = array_filter($content['pmt_detail']);
        }
        unset($tmp_order_cart);

        $order_products = array();
        $this->ci->load->model('order_product_model');
        $tmp_order_products = $this->ci->order_product_model->getList('*',array('order_id'=>$orderid_arr),0,-1);
        foreach ((array) $tmp_order_products as $key => $value) {
            $order_products[$value['order_id']][] = $value;
        }
        unset($tmp_order_products);

        if ($addressid_arr) {
            $user_addresses = array(); //$areaid_arr = array();
            $this->ci->load->model('user_address_model');
            $tmp_user_addresses = $this->ci->user_address_model->getList('*',array('id' => $addressid_arr),0,-1);
            foreach ((array) $tmp_user_addresses as $key => $value) {
                $user_addresses[$value['id']] = $value;
                $areaid_arr[$value['province']] = $value['province'];
                $areaid_arr[$value['city']] = $value['city'];
                $areaid_arr[$value['area']] = $value['area'];
            }
            unset($tmp_user_addresses);
        }

        if ($areaid_arr) {
            $area = array();
            $this->ci->load->model('area_model');
            $tmp_area = $this->ci->area_model->getList('id,name,cut_off_time,send_time',array('id'=>$areaid_arr),0,-1);
            foreach ((array) $tmp_area as $key => $value) {
                $area[$value['id']] = $value;
            }
            unset($tmp_area);
        }

        $sale_bankcom = array();
        $this->ci->load->model('bankcom_records_model');
        $tmp_sale_bankcom = $this->ci->bankcom_records_model->getList('ordername,sale',array('ordername' => $ordername),0,-1);
        if(!empty($tmp_sale_bankcom)){
        foreach ((array) $tmp_sale_bankcom as $key => $value) {
            $sale_bankcom[$value['ordername']] = $value['sale'];
        }
        }
        unset($tmp_sale_bankcom);

        $order_invoice = array();
        $this->ci->load->model('order_invoice_model');
        $tmp_order_invoice = $this->ci->order_invoice_model->getList('*',array('order_id' => $orderid_arr,'is_valid'=>1),0,-1);
        if(!empty($tmp_order_invoice)){
        foreach ($tmp_order_invoice as $key => $value) {
            $order_invoice[$value['order_id']] = $value;
        }
        }
        unset($tmp_order_invoice);

        $order_record = array();
        $tmp_order_record = $this->ci->order_model->getOrderRecord($orderid_arr);
        if(!empty($tmp_order_record)){
        foreach ($tmp_order_record as $key => $value) {
            $order_record[$value['order_id']] = $value;
        }
        }
        unset($tmp_order_record);

        $dz_fp = array();
        $tmp_dz_fp = $this->ci->order_model->getDzFp($ordername);
        if(!empty($tmp_dz_fp)){
        foreach ($tmp_dz_fp as $key => $value) {
            $dz_fp[$value['order_name']] = $value;
        }
        }
        unset($tmp_dz_fp);
        

        foreach ($orders as $o) {
            if ($o['pay_parent_id'] == '6' && $o['pay_id'] == '1'){
                if (false !== strpos($o['shtime'], '-')){
                    list($o['shtime'],$o['stime']) = explode('-', $o['shtime']);
                }
            }

            $province_id = $order_addresses[$o['id']]['province'] ? $order_addresses[$o['id']]['province'] : $user_addresses[$o['address_id']]['province'];
            $city_id     = $order_addresses[$o['id']]['city'] ? $order_addresses[$o['id']]['city'] : $user_addresses[$o['address_id']]['city'];
            $area_id     = $order_addresses[$o['id']]['area'] ? $order_addresses[$o['id']]['area'] : $user_addresses[$o['address_id']]['area'];

            $order = array();
            // $order['chId']        = (int) $o['channel']; // 渠道(1:官网,2:手机,3:预售,4:光明,5:手机预售,6:app订单,7:app线下订单)

            // if ($o['channel'] == '3') $order['chId'] = 1;
            // if ($o['channel'] == '5') $order['chId'] = 2;
            // if ($o['channel'] == '8') $order['chId'] = 9;

            $order['type']        = (int) $o['order_type']; // 订单类型(1:普通订单,2:试吃订单,3:o2o配送订单,4:o2o自提订单，5:预售订单，6:跨境通，7:团购)
            if ($o['channel'] == '3' || $o['channel'] == '5') $order['type'] = 2;
            if ($o['order_type'] == '2') $order['type'] = 1;
            if ($o['order_type'] == '5') $order['type'] = 2;
            if ($o['order_type'] == '6') $order['type'] = 1;
            if ($o['order_type'] == '7') $order['type'] = 1;
            if ($o['order_type'] == '9') $order['type'] = 23; //23:礼包，送礼
            // 大客户订单
            if ($o['is_enterprise']) {
                // $order['chId'] = 8;
                $order['type'] = 13;
                $order['enterpriseno'] = $o['is_enterprise'];
            }

            $order['chId'] = $this->get_pool_channel($o['channel'],$o['is_enterprise']);
            if ($o['order_type'] == '6') $order['chId'] = 10;
            if ($o['order_type'] == '7') $order['chId'] = 12;

            $order['isActivity'] = 0;

            $order['orderNo']     = $o['order_name']; // 单号
            $order['createdate']  = $o['time']; // 创建时间
            $order['payDate']     = ($o['update_pay_time'] && $o['update_pay_time'] != '0000-00-00 00:00:00') ? $o['update_pay_time'] : $o['time'];

            if ($o['pay_parent_id'] == 6 && false !== strpos($o['shtime'],'-')) {
                list($shtime,$stime) = explode('-',$o['shtime']);
                $o['shtime'] = $shtime;
                $o['stime'] = $stime;
            }
            
            $order['sendDate']    = is_numeric($o['shtime']) ? date('Y-m-d',strtotime($o['shtime'])) : $o['shtime'];
            $order['delivertime'] = $o['stime'];

            // 
            $area_refelect = $this->ci->config->item("area_refelect");
            //收货时间需求
            switch ($order['delivertime']) {
                case 'weekday':
                    $order['delDay'] = 1;
                    break;
                case 'weekend':
                    $order['delDay'] = 2;
                    break;
                default:
                    $order['delDay'] = 0;
                    break;
            }
            $this->ci->load->model('warehouse_model');
            $warehouse_info = $this->ci->warehouse_model->getWarehouseByID($o['cang_id']);

            if (is_numeric($o['shtime']) && in_array($order['delivertime'],array('weekday','weekend','all')) ) {
                $order['sendDate'] = $this->week($order['sendDate'],$order['delivertime'],$o['cang_id'],$o['deliver_type']);
                $order['delivertime'] = '0918';
            }elseif ($order['sendDate'] == 'after2to3days' || $order['sendDate'] == 'after1to2days' || !$order['sendDate'] || in_array($order['delivertime'],array('weekday','weekend','all'))) {
                $order['sendDate'] = $this->after2to3days($area[$province_id],$area[$area_id],$o['time'],$order['delivertime'],$o['cang_id']);
                $order['delivertime'] = '0918';
            }elseif(is_numeric($o['shtime']) && !in_array($order['delivertime'],array('weekday','weekend','all'))){
                if($warehouse_info['send_type'] != 1){
                    $order['sendDate'] = date('Y-m-d',(strtotime($order['sendDate'])-86400));
                }
            }elseif ($area[$area_id]['send_time'] != 'chose_days'){
                $order['sendDate'] = date('Y-m-d',(strtotime($order['sendDate'])-86400));
            }
            //elseif ($province_id && !in_array($province_id,$area_refelect[1])) { // 外地
            //    $order['sendDate'] = date('Y-m-d',(strtotime($order['sendDate'])-86400));
            //}
             
            $order['receiverDate'] = $order['sendDate'];
            if(in_array($order['delivertime'],array('weekday','weekend','all'))){
                $order['receiverDate'] = date('Y-m-d',(strtotime($order['receiverDate'])+86400));
            }elseif(is_numeric($o['shtime']) && !in_array($order['delivertime'],array('weekday','weekend','all'))){
                if($warehouse_info['send_type'] != 1){
                    $order['receiverDate'] = date('Y-m-d',(strtotime($order['receiverDate'])+86400));
                }
            }

            if ($order['delivertime'] == '0918') {
                $order['delivertime'] = 1;
            }elseif ($order['delivertime'] == '1822') {
                $order['delivertime'] = 2;
            }elseif ($order['delivertime'] == '0914') {
                $order['delivertime'] = 3;
            }elseif ($order['delivertime'] == '1418') {
                $order['delivertime'] = 4;
            }else {
                $order['delivertime'] = 1;
            }

            $order['billno']      = $o['billno'] ? $o['billno'] : '';
            $order['score']       = (float) $o['score'];
            $order['gcardinfo']   = $o['hk'];
            $order['note']        = $o['msg'];
            $order['orderAmount'] = (float) bcsub($o['goods_money'],$o['pay_discount'],2);
            $order['freightFee']  = (float) $o['method_money'];
            $order['totalAmount'] = (float) bcsub($o['money'],$o['bank_discount'],2);

            //todo 2016-08-16
            $order['disamount']   = (float) ($o['manbai_money']+$o['member_card_money']+$o['new_pay_discount']+$o['oauth_discount']+$sale_bankcom[$o['order_name']]+$o['bank_discount']);
            $order['dedamount']   = (float) $o['card_money'];
            $order['ispay']       = $o['pay_status'] == '1' ? 1 : 0;
            $order['payment']     = (int) $o['pay_id']; // 线上，线下...
            if ($o['pay_parent_id'] == 4 && $o['pay_id'] == 6) {
                $order['payment'] = 1;
            }
            if (isset($this->_online_pay[$o['pay_parent_id']]) || $o['order_type']=='2') $order['payment'] = 0;

            // if ($o['pay_parent_id']=='6' && $o['pay_id'] == '1') {
            //     $order['disamount'] = (float) ($o['goods_money'] - $o['money']);
            // }
            
            $order['status']      = $o['operation_id']; // 订单状态(0:待审核核,1:已审核,2:已发货,3:已完成,4:未完成,5:已取消,6:等待完成,7:退货中,8:换货中)
            $order['deliverystate'] = 2;

            if ($o['channel'] == '7') {
                $order['deliverystate'] = 1;
                $order['activityno'] = $o['erp_active_tag'];
                $order['type'] = 4;
            }

            $order['invoice_info']['invoiceis']    = $o['fp_dz'] ? 0 : 1; // 是否和货物一起配送

            $region_match = $this->region_match($order_invoice[$o['id']]['province'],$order_invoice[$o['id']]['city'],$order_invoice[$o['id']]['area']);
            $order['invoice_info']['ivprovince']   = $region_match['province'];
            $order['invoice_info']['ivcity']       = $region_match['city'];
            $order['invoice_info']['ivarea']       = $region_match['area'];
            unset($region_match);

            $order['invoice_info']['ivRec']        = $order_invoice[$o['id']]['username'];
            if(isset($dz_fp[$o['order_name']]['order_name'])){
                $order['invoice_info']['invoicetype']  = 2;//电子发票
                $order['invoice_info']['ivPhone']  = isset($dz_fp[$o['order_name']]['mobile'])?$dz_fp[$o['order_name']]['mobile']:'';
            }else{
                $order['invoice_info']['invoicetype']  = 1;//纸质发票
                $order['invoice_info']['ivPhone']      = $order_invoice[$o['id']]['mobile'];
            }
            $order['invoice_info']['invoiceaddr']  = $order_invoice[$o['id']]['address'] ? $order_invoice[$o['id']]['address'] : $o['fp_dz'];
            $order['invoice_info']['invoicetitle'] = $order_invoice[$o['id']]['name']?$order_invoice[$o['id']]['name']:$o['fp'];
	        $order['invoice_info']['ivAmount']     = $o['fp'] ? (float) $o['money'] : 0;
            $order['invoice_info']['invTransFee']  = $o['invoice_money'];
            
            if ($o['pay_parent_id']=='5'){   //余额支付订单不开票
                $order['invoice_info']['invoicetype'] = 1;
                $order['invoice_info']['ivPhone'] = '';
                $order['invoice_info']['invoiceaddr'] = '';
                $order['invoice_info']['invoicetitle'] = '';
                $order['invoice_info']['ivAmount'] = 0;
                $order['invoice_info']['invTransFee'] = 0;
            }

            $order['member_info']['buyerId']    = (int) $o['uid'];
            $order['member_info']['buyer']      = $users[$o['uid']]['username'] ? $users[$o['uid']]['username'] : $users[$o['uid']]['mobile'];
            $order['member_info']['buyer'] or $order['member_info']['buyer'] = $o['uid'];
            $order['member_info']['buyerPhone'] = $users[$o['uid']]['mobile'];
            $order['member_info']['buyerLevel'] = $users[$o['uid']]['user_rank'];
            $order['member_info']['name'] = isset($order_record[$o['id']]['name'])?$order_record[$o['id']]['name']:'';
            $order['member_info']['idCardType'] = isset($order_record[$o['id']]['id_card_type'])?$order_record[$o['id']]['id_card_type']:'';
            $order['member_info']['idCardNumber'] = isset($order_record[$o['id']]['id_card_number'])?$order_record[$o['id']]['id_card_number']:'';
            $order['member_info']['phoneNumber'] = isset($order_record[$o['id']]['mobile'])?$order_record[$o['id']]['mobile']:'';
            $order['member_info']['email'] = isset($order_record[$o['id']]['email'])?$order_record[$o['id']]['email']:'';
            $order['member_info']['credit_rank'] = isset($black_users[$o['uid']])?$black_users[$o['uid']]:0;


            $order['consignee_info']['province']  = $area[$province_id]['name'];
            $order['consignee_info']['city']      = $area[$city_id]['name'];
            $order['consignee_info']['region']    = preg_replace('/（.*）/', '', $area[$area_id]['name']);

            $region_match = $this->region_match($order['consignee_info']['province'],$order['consignee_info']['city'],$order['consignee_info']['region']);
            
            $order['consignee_info']['reAddress'] = str_replace($order_addresses[$o['id']]['position'], '', $order_addresses[$o['id']]['address']);
            if ($region_match['area'] != $order['consignee_info']['region'] && preg_match('/开发区/', $order['consignee_info']['region'])) {
                $order['consignee_info']['reAddress'] = $order['consignee_info']['region'].$order['consignee_info']['reAddress'];
            }

            $order['consignee_info']['province'] = $region_match['province'];
            $order['consignee_info']['city'] = $region_match['city'];
            $order['consignee_info']['region'] = $region_match['area'];


            unset($region_match);

            $order['consignee_info']['receiver']  = $order_addresses[$o['id']]['name'];

            $order['consignee_info']['rePhone']   = $order_addresses[$o['id']]['telephone'];
            $order['consignee_info']['reMobile']  = $order_addresses[$o['id']]['mobile'];
            $address_flag = 0;
            switch ($user_addresses[$o['address_id']]['flag']) {
                case '家':
                    $address_flag = 1;
                    break;
                case '公司':
                    $address_flag = 2;
                    break;
                default:
                    $address_flag = 0;
                    break;
            }
            $order['consignee_info']['flag']  = $address_flag;

            if (!trim($order['consignee_info']['reMobile']) && $order['consignee_info']['rePhone']) {
                $order['consignee_info']['reMobile'] = $order['consignee_info']['rePhone'];
            }

            $order['wh'] = $o['order_type'] == '6' ? 'FSHZM' : '';

            /*优惠券促销信息整理start*/
            // $order['is_special_card'] = 0;
            $card_pro_arr = array();
            if(!empty($o['use_card'])){
                $card_info = $this->ci->db->select('product_id')->from('card')->where(array('card_number'=>$o['use_card']))->get()->row_array();
                if(!empty($card_info) && !empty($card_info['product_id'])){
                    $card_pro_arr = explode(',', $card_info['product_id']);
                }
            }
            $card_used_pro = array();
            $card_used_pro_total = 0;
            $card_used_pro_i = 0;
            $card_used_pro_tmp = array();
            if(!empty($card_pro_arr)){
                foreach ((array) $order_products[$o['id']] as $op) {
                    if ($op['type'] == 2 || $op['type'] == 3 || $op['type'] == 5 || $op['type'] == 6 || 0 == bccomp($op['total_money'], 0,3)){
                        continue;
                    }
                    if(in_array($op['product_id'], $card_pro_arr)){
                        $card_used_pro[$op['id']]['product_id'] = $op['product_id'];
                        $card_used_pro[$op['id']]['total_money'] = $op['total_money'];
                        $card_used_pro_total += $op['total_money'];
                    }
                }
                if(!empty($card_used_pro)){
                    // $order['is_special_card'] = 1;
                    $card_used_pro_count = count($card_used_pro)-1;
                    $card_used_pro_money_sy = 0;
                    foreach ($card_used_pro as $key => $value) {
                        if($card_used_pro_i == $card_used_pro_count){
                            $card_used_pro_money = $order['dedamount'] - $card_used_pro_money_sy;
                        }else{
                            $card_used_pro_money = ceil(bcmul(bcdiv($value['total_money'],$card_used_pro_total,3),$order['dedamount'],3));
                            $card_used_pro_money_sy += $card_used_pro_money;
                        }
                        $card_used_pro_tmp[$key] = $card_used_pro_money;
                        $card_used_pro_i++;
                    }
                    $order['orderAmount'] = $order['orderAmount'] - $order['dedamount'];
                    $order['dedamount'] = 0;
                }
            }
            
            /*优惠券促销信息整理end*/

            $order_items = array(); $discount = 0; $amount = 0;
            $has_discount = array();
            foreach ((array) $order_products[$o['id']] as $op) {
                if (!$op['product_name'] && !$op['product_id'] && !$op['product_no']) continue;

                if ($op['product_no']=='30317' || $op['product_no']=='201411316' || $op['product_no']=='201411315') continue;

                if(in_array($op['product_no'], $this->_da_zha_xie)){
                    $order['type'] = 20; //产地直销
                }
                if(in_array($op['product_no'], $this->_xian_huo)){
                    $order['type'] = 22; //鲜活，去供应商取货
                }
                $order_item = array();


                $sale_price = bcdiv($op['total_money'], $op['qty'],3);



                $cardAmount = 0;
                $add_card_amount = 0;//优惠券抵扣商品处理
                $add_discount = 0;
                if ($op['type'] == 2 || $op['type'] == 3 || $op['type'] == 5 || $op['type'] == 6 || 0 == bccomp($op['total_money'], 0,3)){
                    
                }else{
                    $cardAmount = isset($card_used_pro_tmp[$op['id']])?$card_used_pro_tmp[$op['id']]:0;//优惠券促销信息整理
                    if($op['qty']>1){
                        $add_card_amount = $cardAmount;
                        $add_discount = 0;
                    }else{
                        $add_card_amount = 0;
                        $add_discount = $cardAmount;
                    }
                }
                $order_item['prdno']       = $op['product_no'];
                $order_item['price']       = (float) $op['price'];   //原单价
                $order_item['discount']    = bcsub((float) $op['price'], (float) $sale_price,3) + $add_discount; //折后每件优惠
                $order_item['add_discount']= $add_discount; //专用优惠券金额（购买单件）
                $order_item['disPrice']    = (float) $sale_price; //折后单价，不计算专用优惠券
                $order_item['count']       = (int) $op['qty'];
                $order_item['cardAmount']  = (float)$add_card_amount; //单品优惠券金额（购买多件）

                //todo 2016-08-16
                $itemDisAmount = 0;
                if($order_cart[$o['id']] && $op['type'] == 1){
                    foreach ($order_cart[$o['id']] as $pmt_cart) {
                        if($op['group_pro_id'] && isset($has_discount[$op['group_pro_id'].'-'.$pmt_cart['id']]) && $has_discount[$op['group_pro_id'].'-'.$pmt_cart['id']] === true){
                            continue;
                        }
                        foreach ($pmt_cart['items'] as $cart_pmt_item) {
                            if($op['group_pro_id'] && $op['group_pro_id'] == $cart_pmt_item['product_id']){
                                $itemDisAmount = bcadd($itemDisAmount, $cart_pmt_item['reduce_money'],2);
                                $has_discount[$op['group_pro_id'].'-'.$pmt_cart['id']] = true;
                            }elseif($cart_pmt_item['product_id'] == $op['product_id'] && $cart_pmt_item['product_no'] == $op['product_no']){
                                $itemDisAmount = bcadd($itemDisAmount, $cart_pmt_item['reduce_money'],2);
                            }
                        }
                    }
                }
                
                $order_item['itemDisAmount'] = (float) $itemDisAmount;//购物车优惠
                $order_item['totalAmount'] = (float) bcsub(bcsub((float) $op['total_money'], (float) $cardAmount,3),$itemDisAmount,3);
                

                $order_item['distype']     = 2;
                $order_item['primaryCode'] = '';
                $order_item['disCode']     = '';
                $order_item['score']       = (float) $op['score'];
                
                if ($op['type'] == 1 || $op['type'] == 4) $order_item['saletype'] = 1;
                if ($op['type'] == 2 || $op['type'] == 3 || $op['type'] == 5 || $op['type'] == 6 || 0 == bccomp($op['total_money'], 0,3)){
                    $order_item['price']    = 0;
                    $order_item['disPrice'] = 0;
                    $order_item['discount'] = 0;
                    $order_item['saletype'] = 2;
                } 
                if ($o['order_type'] == 2) $order_item['saletype'] = 3;

                $order_items[] = $order_item;

                // $discount += $order_item['discount'] * $op['qty'];

                // 判断是否为生鲜
                // if ($order['wh']=='' && $op['product_id'] && in_array($op['type'],array(1,4)) ) {

                    // $productinfo = $this->ci->db->query('select type from ttgy_product where id='.$op['product_id'].' limit 1')->row_array();

                    // if ($productinfo['type'] == '4') {
                    //     $order['wh'] = 'FSH1';
                    // }
                // }

                // if ($op['product_id'] == '3664') {
                //     $order['wh'] = 'FSH1';
                // }

                $amount += $op['total_money'];

                //活动订单
                if($order['isActivity'] != 1){
                    $is_huodong = $this->check_huodong_product($op['product_id']);
                    if($is_huodong === true){
                        $order['isActivity'] = 1;
                    }
                }
            }

            

            if ($order['wh'] == 'FSH1' && !in_array($area[$user_addresses[$o['address_id']]['province']]['id'],$area_refelect[1]) && ($o['shtime'] == 'after2to3days' || $o['shtime'] == 'after1to2days') ) {
                // 指定上海仓且发外地
                $order['sendDate'] = $this->after2to3days($area[$user_addresses[$o['address_id']]['province']],array('cut_off_time'=>16),$o['time'],$o['stime'],$o['cang_id']);
                $order['delivertime'] = 1;
            }

            $order['order_items'] = $order_items;
            // $order['disamount'] += $discount;
            // $order['orderAmount'] += $discount;
            if ($o['pay_parent_id']=='6' && $o['pay_id'] == '1' && $amount >= $o['money']) {
                $order['disamount'] = (float) ($amount - $o['money'] + $o['method_money']);
                $order['orderAmount'] = $amount;
            }

            $payment_info = $this->get_pool_payment($o);

            // $payment_info = array();
            // if ($o['pay_status'] == '1' 
            //     && $o['pay_parent_id'] 
            //     && $o['money']>0 
            //     && !in_array($o['pay_parent_id'],array('4','6')) 
            // ) {
            //     $payment_info[] = array(
            //         'paym'        => $online_pay[$o['pay_parent_id']]['way_id'], // 1:支付宝付款,2:联华OK会员卡在线支付,3:网上银行支付,4:线下支付,5:账户余额支付,6:券卡支付
            //         'payAmount'   => (float) $o['money'],
            //         'payplatform' => $online_pay[$o['pay_parent_id']]['children_platform_id'][$o['pay_id']] ? $online_pay[$o['pay_parent_id']]['children_platform_id'][$o['pay_id']] : $online_pay[$o['pay_parent_id']]['platform_id'],
            //         'ticketCode'  => '',
            //         'ticketCount' => 0,
            //         'chrgno'       => ($o['pay_id']=='00003' && !$o['trade_no']) ? $o['order_name'] : $o['trade_no'],
            //         'disCode'     => '',
            //     );
            // }

            if ($o['use_money_deduction']) { // 帐户余额抵消
                // $payment_info[] = array(
                //     'paym'        => 9,
                //     'payAmount'   => (float) $o['use_money_deduction'],
                //     'payplatform' => null,
                //     'ticketCode'  => '',
                //     'ticketCount' => 0,
                //     'chrgno'       => $o['trade_no'],
                //     'disCode'     => '',
                // );

                $order['totalAmount'] = bcadd($order['totalAmount'], $o['use_money_deduction'],3);
            }

            if ($o['jf_money']) { // 积分
                // $payment_info[] = array(
                //     'paym'        => 8,
                //     'payAmount'   => (float) $o['jf_money'],
                //     'payplatform' => '',
                //     'ticketCode'  => '',
                //     'ticketCount' => 0,
                //     'chrgno'       => '',
                //     'disCode'     => '',
                // );
                $order['totalAmount'] = bcadd($order['totalAmount'], $o['jf_money'],3);
            }


            // if($order['dedamount'] > 0){
            //     $order['totalAmount'] = bcadd($order['totalAmount'], $order['dedamount'],3);
            // }
            // if ($o['pay_parent_id'] == '6') {
            //     $juan = $this->ci->db->select('card_number')->from('pro_card')->where(array('order_name'=>$o['order_name'],'is_used'=>'1','is_sent'=>'1'))->get()->row_array();

            //     $payment_info[] = array(
            //         'paym'        => 5, 
            //         'payAmount'   => (float) $o['money'],
            //         'payplatform' => 5001,
            //         'ticketCode'  => $juan ? $juan['card_number'] : '',
            //         'ticketCount' => $juan ? 1 : 0,
            //         'chrgno'       => '',
            //         'disCode'     => '',
            //     );
            // }

            $order['payment_info'] = $payment_info;

            //取消订单处理
            if($o['operation_id']==5){
                $order['isCancel'] = 1;
            }else{
                $order['isCancel'] = 0;
            }

            //订单金额是否打印
            if($o['sheet_show_price']=='1'){
                $order['isPrint'] = 1;
            }else{
                $order['isPrint'] = 2;
            }
            if($o['pay_status'] != 1){
                $order['isPrint'] = 1;
            }

            $order['pmt_detail'] = $order_cart[$o['id']]?$order_cart[$o['id']]:array();
            //临时修复gift_id类型问题start
            foreach ($order['pmt_detail'] as $tk => $tv) {
                if(isset($tv['gift_id'])){
                    unset($order['pmt_detail'][$tk]['gift_id']);
                }
            }
            //临时修复gift_id类型问题end

            //周期购订单处理
            if($o['order_type'] == 8){
                $this->ci->load->model('subscription_model');
                $order['type'] = 21;
                $order['isPrint'] = 2;
                $order['group'] = $this->ci->subscription_model->pushOrderInfo($o['id']);
            }

            $f_orders[$o['id']] = $order;

            $this->ci->order_model->update(array('send_date'=>$order['sendDate']),array('id'=>$o['id']));
        }

        $this->rpc_log = array('rpc_desc' => '订单推送','obj_type'=>'order');
        return $f_orders;
    }

    /**
     * 订单推送后的回调
     *
     * @return void
     * @author 
     **/
    public function callback($filter)
    {
        if ($filter['result'] == '1') {
            $order_name = $filter['orderNo'];

            $this->ci->load->model('order_model');

            $order = $this->ci->order_model->dump(array('order_name' => $order_name),'id,operation_id,order_name,channel,is_enterprise,order_type');
            if (!$order) {

                //o2o调拨单
                $this->ci->load->model('o2o_allocation_order_model');
                $ao = $this->ci->o2o_allocation_order_model->dump(array('order_name' => $order_name));
                if($ao){
                    return $this->ci->o2o_allocation_order_model->sync_succ($ao);
                }

                return array('result' => 0,'msg' => '订单不存在');
            }

            $id = $order['id'];

            if ($order['operation_id'] == 5) {
                $affected_rows = $this->ci->order_model->update(array('sync_status'=>1),array('id'=>$id));

                // 请求取消接口
                $this->ci->load->bll('pool');
                $this->ci->bll_pool->pool_order_cancel($order['order_name'],$order['channel'],1,$order['is_enterprise'],$order['order_type']);

            } elseif($order['operation_id'] == 0) {
                $affected_rows = $this->ci->order_model->update(array('sync_status'=>1,'operation_id'=>'1'),array('id'=>$id));
            }else{
                $affected_rows = $this->ci->order_model->update(array('sync_status'=>1),array('id'=>$id));
            }

            return $affected_rows >= 0 ? array('result' => 1,'msg' => '') : array('result' => 0,'msg' => '更新失败');
        }

        // 失败发邮件
        $this->ci->load->model('jobs_model');
        $emailList = array( 'gongting@fruitday.com','chenping@fruitday.com','songtao@fruitday.com','lusc@fruitday.com');
        foreach ($emailList as $email) {
            $emailContent = '订单['.$filter['orderNo'].']推送OMS失败,原因：'.$filter['msg'];
            $this->ci->jobs_model->add(array('email'=>$email,'text'=>$emailContent,'title'=>"推送订单失败".$filter['orderNo']), "email");  
        }

        $this->rpc_log = array('rpc_desc' => '订单回调','obj_type'=>'order');

        return array('result' => 0,'msg'=>'');
    }

    public function batchcallback($filter)
    {
        foreach ($filter as $value) {
            if ($value['result'] == '1') {
                $succ_orders[] = $value['orderNo'];
            }
        }
        $this->ci->load->model('order_model');
        $orders = $this->ci->order_model->getList('id,operation_id,order_name,channel,is_enterprise,order_type',array('order_name' => $succ_orders));
        foreach ($orders as $order) {
            if($order['operation_id'] == 5){
                $cancel_ids[] = $order['id'];
            }elseif($order['operation_id'] == 0){
                $order_op_1[] = $order['id'];
            }else{
                $order_op_2[] = $order['id'];
            }
        }
        
        $affected_rows = 0;
        if($order_op_1){
            $affected_rows = $this->ci->order_model->update(array('sync_status'=>1,'operation_id'=>'1'),array('id'=>$order_op_1));
        }

        if($order_op_2){
            $affected_rows = $this->ci->order_model->update(array('sync_status'=>1),array('id'=>$order_op_2));
        }
        
        if($cancel_ids){
            $data_arr = array();
            foreach ($cancel_ids as $id) {
                $data = array();
                $data['order_id'] = $id;
                $data['status'] = 0;
                $data['time'] = date('Y-m-d');
                $data_arr[] = $data;
            }
            $data_arr and $this->ci->db->insert_batch('ttgy_oms_cancel_queue', $data_arr);
        }

        //o2o调拨单
        $this->ci->load->model('o2o_allocation_order_model');
        $ao = $this->ci->o2o_allocation_order_model->getList('*', array('order_name' => $succ_orders));
        if($ao){
            $this->ci->o2o_allocation_order_model->sync_succ($ao);
        }

        $this->rpc_log = array('rpc_desc' => '订单批量回调','obj_type'=>'order');
        return array('result' => 1,'msg'=>'');
    }

    /**
     * 确认发货
     *
     * @return void
     * @author 
     **/
    public function sendLogistics($filter)
    {
        $order_name = $filter['orderNo'];

        if (!$order_name) return array('result'=>0,'msg' => '订单号不能为空');

        $this->ci->load->model('order_model');
        $order = $this->ci->order_model->dump(array('order_name'=>$order_name));

        if (!$order) return array('result' => 0,'msg' => '订单不存在');

        $this->ci->load->bll('order');
        $rs = $this->ci->bll_order->delivery($order,$filter);

        if ($rs['rs'] != 'succ') return array('result' => 0, 'msg' => $rs['msg']);

        $this->rpc_log = array('rpc_desc' => '订单发货','obj_type'=>'order','obj_name'=>$order_name);

        return array('result' => 1,'msg' => '发货成功');
    }

    /**
     * 批量发货
     *
     * @return void
     * @author 
     **/
    public function batchSendLogistics($filter)
    {
        $deliverys = $filter['orders'];

        if (!$deliverys) return array('result'=>0,'msg' => '发货信息不存在');

        $errorMsg = array();
        $this->ci->load->model('order_model');
        $this->ci->load->bll('order');
        foreach ($deliverys as $key => $delivery) {
            $order = $this->ci->order_model->dump(array('order_name'=>$delivery['orderNo']));

            if (!$order) {
                $errorMsg[] = array('orderNo' => $delivery['orderNo'],'msg' => '订单不存在');
                continue;
            }

            $rs = $this->ci->bll_order->delivery($order,$delivery);

            if ($rs['rs'] != 'succ') {
                $errorMsg[] = array('orderNo' => $delivery['orderNo'], 'msg' => $rs['msg']);
            }
        }

        $this->rpc_log = array('rpc_desc' => '批量发货','obj_type'=>'order',);

        return array('result' => 1, 'errorMsg' => $errorMsg);
    }

    /**
     * 订单取消
     *
     * @return void
     * @author 
     **/
    public function cancel($filter)
    {
        $order_name = $filter['orderNo'];

        $this->ci->load->library('fdaylog'); 
        $db_log = $this->ci->load->database('db_log', TRUE); 
        $this->ci->fdaylog->add($db_log,'pool_order_cancel_0727',$filter);
        
        // error_log($order_name."  ".print_r($result,true),3,"/tmp/st.log");
        if (!$order_name) return array('result'=>0,'msg' => '订单号不能为空');

        $this->ci->load->model('order_model');
        $order = $this->ci->order_model->dump(array('order_name'=>$order_name),'id');

        if (!$order) return array('result' => 0, 'msg' => '订单不存在');

        $this->ci->load->bll('order');

        $result = $this->ci->bll_order->cancel($order['id'],$msg,true);
        // error_log($order_name."  ".print_r($result,true)."\n",3,"/tmp/st.log");
        $this->rpc_log = array('rpc_desc' => '订单取消','obj_type'=>'order','obj_name' => $order_name);

        return array('result' => $result ? 1 : 0,'msg' => $msg);
    }

    /**
     * 订单完成
     *
     * @return void
     * @author 
     **/
    public function finish($filter)
    {
        $order_name = $filter['orderNo'];
        $score      = $filter['score'] ? $filter['score'] : 0;
        $final_money = isset($filter['finalAmt'])?$filter['finalAmt']:NULL;

        if (!$order_name) return array('result'=>0,'msg' => '订单号不能为空');

        $this->ci->load->model('order_model');
        $order = $this->ci->order_model->dump(array('order_name'=>$order_name));

        if (!$order) return array('result' => 0, 'msg' => '订单不存在');

        $this->ci->load->bll('order');
        $rs = $this->ci->bll_order->finish($order,$score,$final_money);

        if ($rs['rs'] != 'succ') return array('result' => 0, 'msg'=>$rs['msg']);

        $this->rpc_log = array('rpc_desc' => '订单完成','obj_type'=>'order','obj_name'=>$order_name);

        return array('result' => 1,'msg'=>'订单完成');
    }

    /**
     * 批量完成
     *
     * @return void
     * @author 
     **/
    public function batchFinish($filter)
    {
        $orders = $filter['orders'];

        if (!$orders) return array('result'=>0,'msg' => '订单信息不存在');

        $errorMsg = array();
        $this->ci->load->model('order_model');
        $this->ci->load->bll('order');
        foreach ($orders as $key => $value) {
            $order = $this->ci->order_model->dump(array('order_name'=>$value['orderNo']));

            if (!$order) {
                $errorMsg[] = array('orderNo' => $value['orderNo'],'msg' => '订单不存在');
                continue;
            }
            $final_money = isset($value['finalAmt'])?$value['finalAmt']:NULL;
            $rs = $this->ci->bll_order->finish($order,$value['score'],$final_money);

            if ($rs['rs'] != 'succ') {
                $errorMsg[] = array('orderNo' => $value['orderNo'], 'msg' => $rs['msg']);
            }
        }

        $this->rpc_log = array('rpc_desc' => '批量订单完成','obj_type'=>'order',);

        return array('result' => 1, 'errorMsg' => $errorMsg);
    }

    /**
     * 同步订单状态
     *
     * @return void
     * @author 
     **/
    public function status($filter)
    {
        $orders = $filter['orders'];

        if (!$orders) return array('result'=>0,'msg' => '订单信息不存在');

        $this->ci->load->bll('order','bll_order');
        foreach ($orders as $key => $value) {
            $rs = $this->ci->bll_order->status($value['orderNo'],$value['status'],$value['sendCompleteTime']);

            if ($rs['rs'] != 'succ') {
                $errorMsg[] = array('orderNo' => $value['orderNo'], 'msg' => $rs['msg']);
            }
        }

        $this->rpc_log = array('rpc_desc' => '批量订单状态','obj_type'=>'order',);

        return array('result' => 1, 'errorMsg' => $errorMsg);
    }

    /**
     * 更新支付状态
     *
     * @return void
     * @author 
     **/
    public function pay_status($filter)
    {
        $orders = $filter['orders'];

        if (!$orders) return array('result'=>0,'msg' => '订单信息不存在');

        $this->ci->load->bll('order','bll_order');
        foreach ($orders as $key => $order_name) {
            $rs = $this->ci->bll_order->pay_status($order_name);

            if ($rs['rs'] != 'succ') {
                $errorMsg[] = array('orderNo' => $order_name, 'msg' => $rs['msg']);
            }
        }

        $this->rpc_log = array('rpc_desc' => '批量订单支付状态','obj_type'=>'order');

        return array('result' => 1, 'errorMsg' => $errorMsg);
    }


    public function pushoms($params)
    {
        if (!$params['order_name']) return array('code'=>300,'msg'=>'空订单号');

        $orders = $this->get_push_orders($params['order_name']);
        if (!$orders) {
            return array('code'=>300,'msg'=>'订单不满足同步条件');
        }

        $this->ci->load->bll('rpc/request');

        $orderids = array_keys($orders);

        if ($orderids) {
            $this->set_sync($orderids,'2');
        }

        // 金额校验
        $orders = $this->check_order($orders);
        if (!$orders) return array('code'=>300,'msg'=>'订单金额异常');

        $this->ci->bll_rpc_request->set_rpc_log(array('rpc_desc' => '指定订单号同步','obj_type'=>'order'));

        $response = $this->ci->bll_rpc_request->realtime_call(POOL_ORDER_URL,array_values($orders));

         if ($response['result'] != '1' && $orderids) {
             //$this->set_sync($orderids,'0');

             return array('code'=>300,'msg'=>'同步失败');
         }

         return array('code'=>200,'msg'=>'同步成功');
    }

    /**
     * 同步支付方式
     *
     * @return void
     * @author 
     **/
    public function syncfee($order_id)
    {
        if (!$order_id) return ;

        $this->ci->load->model('order_model');

        $order = $this->ci->order_model->dump(array('id'=>$order_id));

        if (!$order) return ;
        $chId = $this->get_pool_channel($order['channel'],$order['is_enterprise']);
        if($order['order_type'] == 6)   $chId=10;
        $data = array(
            'chId'    => $chId,
            'orderNo' => $order['order_name'],
            'ispay'   => $order['pay_status'] == '1' ? 1 : 0,
            'payment' => (int) $order['pay_id'],
            'payDate' => ($order['update_pay_time'] && $order['update_pay_time'] != '0000-00-00 00:00:00') ? $order['update_pay_time'] : $order['time'],
        );

        if ($order['pay_parent_id'] == 4 && $order['pay_id'] == 6) {
            $data['payment'] = 1;
        }

        if (isset($this->_online_pay[$order['pay_parent_id']]) || $order['order_type'] == 2) {
            $data['payment'] = 0;
        }

        $data['payment_info'] = $this->get_pool_payment($order,true);

        $this->ci->load->bll('rpc/request');

        $log = array(
            'rpc_desc' => '订单支付推送',
            'obj_type' => 'order_payment',
            'obj_name' => $order['order_name'],
        );
        $this->ci->bll_rpc_request->set_rpc_log($log);

        $rs = $this->ci->bll_rpc_request->realtime_call(POOL_SYNCFEE_URL,$data,'POST',20);

        if ($rs['result'] != 1) {
            return array('status' => 'fail','msg' => '推送失败');
        }

        return array('status' => 'succ','msg' => '推送成功');
    }


    public function get_pool_payment($order,$syncfee=false)
    {
        $payment_info = array();

        if ($order['pay_status'] == '1' && $order['pay_parent_id'] && $order['money']>0 && !in_array($order['pay_parent_id'],array('4','6')) ) {
            $order['bank_discount'] = $order['bank_discount']?$order['bank_discount']:0;
            $payment_info[] = array(
                'paym'        => $this->_online_pay[$order['pay_parent_id']]['way_id'], // 1:支付宝付款,2:联华OK会员卡在线支付,3:网上银行支付,4:线下支付,5:账户余额支付,6:券卡支付
                'payAmount'   => bcsub($order['money'],$order['bank_discount'],2),//number_format($order['money'],2,'.',''),
                'payplatform' => $this->_online_pay[$order['pay_parent_id']]['children_platform_id'][$order['pay_id']] ? $this->_online_pay[$order['pay_parent_id']]['children_platform_id'][$order['pay_id']] : $this->_online_pay[$order['pay_parent_id']]['platform_id'],
                'ticketCode'  => '',
                'ticketCount' => 0,
                'chrgno'       => ($order['pay_id']=='00003' && !$order['trade_no']) ? $order['order_name'] : $order['trade_no'],
                'disCode'     => '',
            );
        }
        
        if($syncfee === false){
                if ($order['use_money_deduction']>0) { // 帐户余额抵消
                $payment_info[] = array(
                    'paym'        => 9,
                    'payAmount'   => number_format($order['use_money_deduction'],2,'.',''),
                    'payplatform' => null,
                    'ticketCode'  => '',
                    'ticketCount' => 0,
                    'chrgno'       => $order['trade_no'],
                    'disCode'     => '',
                );
            }
        }

        if($syncfee === false){
            if ($order['jf_money']>0) { // 积分
                $payment_info[] = array(
                    'paym'        => 8,
                    'payAmount'   => number_format($order['jf_money'],2,'.',''),
                    'payplatform' => '',
                    'ticketCode'  => '',
                    'ticketCount' => 0,
                    'chrgno'       => '',
                    'disCode'     => '',
                );
            }
        }

        if ($order['pay_parent_id'] == '6') {
            $juan = $this->ci->db->select('card_number')->from('pro_card')->where(array('order_name'=>$order['order_name'],'is_used'=>'1','is_sent'=>'1'))->get()->row_array();

            $payment_info[] = array(
                'paym'        => 5, 
                'payAmount'   => number_format($order['money'],2,'.',''),
                'payplatform' => 5001,
                'ticketCode'  => $juan ? $juan['card_number'] : '',
                'ticketCount' => $juan ? 1 : 0,
                'chrgno'       => '',
                'disCode'     => '',
            );
        }

        return $payment_info;
    }

    public function get_pool_channel($channel,$is_enterprise,$order_type=0)
    {
        switch ($channel) {
            case '3':
                $channel = 1;
                break;
            case '5':
                $channel = 2;
                break;
            case '8':
                $channel = 10;
                break;
            case '9':
                $channel = 11;
                break;
        }

        if ($is_enterprise) {
            $channel = 8;
        }

        switch ($order_type) {
            case '6':
                $channel = 10;
                break;
            case '7':
                $channel = 12;
                break;
        }

        return (int) $channel;
    }

    public function get_order_trade($order_names){
        $where = '';
        $limit = 100;
        $s_time = date('Y-m-d H:i:s',(time()-3600*24*3));
        $e_time = date('Y-m-d H:i:s',(time()));
        if ($order_names) {
            $order_names = implode("','", $order_names);
            $where = " and o.order_name in('".$order_names."')";
        }else{
            $where = " and (o.update_pay_time between '".$s_time."' and '".$e_time."' or o.time between '".$s_time."' and '".$e_time."')";
        }
        $sql = "select o.id,u.username,o.order_name,o.billno,o.trade_no,o.pay_time,o.time,o.update_pay_time,o.money,o.pay_parent_id,o.pay_id,o.uid,o.sync_erp ,o.use_money_deduction,o.jf_money,o.card_money,o.use_card,o.order_type,o.bank_discount from ttgy_order o left join ttgy_user u on u.id=o.uid where o.order_status=1 and o.pay_status=1 and o.sync_erp=0 and (sync_status=1 or order_type=9) and o.pay_parent_id<>4 and o.money>0 and o.order_type <> 8 ".$where." order by o.time limit ".$limit;
        $result = $this->ci->db->query($sql)->result_array();
        return $result;
    }

    public function order_trade_call_back($filter)
    {
        if ($filter['result'] == '1') {
            $order_name = $filter['feeNum'];
            $this->ci->load->model('order_model');
            $this->ci->load->model('trade_model');
            if($filter['feeType'] == 1){
                $order = $this->ci->order_model->dump(array('order_name' => $order_name));
                if (!$order) return array('result' => 0,'msg' => '订单不存在');
                $id = $order['id'];
                $affected_rows = $this->ci->order_model->update(array('sync_erp'=>1),array('id'=>$id));
                return $affected_rows >= 0 ? array('result' => 1,'msg' => '') : array('result' => 0,'msg' => '更新失败');
            }elseif($filter['feeType'] == 3){
                $trade = $this->ci->trade_model->dump(array('trade_number' => $order_name));
                if (!$trade) return array('result' => 0,'msg' => '交易不存在');
                $id = $trade['id'];
                $affected_rows = $this->ci->trade_model->update(array('sync_erp'=>'2',array('id'=>$id)));
                return $affected_rows >= 0 ? array('result' => 1,'msg' => '') : array('result' => 0,'msg' => '更新失败');
            }
            
        }
        // 失败发邮件
        $this->ci->load->model('jobs_model');
        $emailList = array( 'gongting@fruitday.com','chenping@fruitday.com','songtao@fruitday.com','lusc@fruitday.com');
        foreach ($emailList as $email) {
            $emailContent = '交易['.$filter['feeNum'].']推送OMS失败,原因：'.$filter['msg'];
            $this->ci->jobs_model->add(array('email'=>$email,'text'=>$emailContent,'title'=>"推送交易失败".$filter['feeNum']), "email");  
        }

        $this->rpc_log = array('rpc_desc' => '订单回调','obj_type'=>'transaction');

        return array('result' => 0,'msg'=>'');
    }

    function getProCard($order_name){
        $card = $this->ci->db->select('card_number')->from('pro_card')->where(array('order_name'=>$order_name,'is_used'=>'1','is_sent'=>'1'))->get()->row_array();
        return $card['card_number']?$card['card_number']:'';
    }

    function getO2oChildOrderInfo($order_id){
        $sql = "select c.money,c.jf_money,c.card_money,sp.code from ttgy_o2o_child_order c join ttgy_o2o_store_physical sp on sp.id=c.store_id where c.p_order_id=".$order_id;
        return $this->ci->db->query($sql)->result_array();
    }

    function pushOmsCancelOrder($filter = array(),$limit = 30){
        $where = '';
        if($filter['order_id']){
            $where = "and order_id=".$filter['order_id'];
        }
        $sql = "select order_id from ttgy_oms_cancel_queue where status=0".$where." order by id limit ".$limit;
        $cancel_orders = $this->ci->db->query($sql)->result_array();
        if(!$cancel_orders) return;

        foreach ($cancel_orders as $value) {
            $c_order_ids[] = $value['order_id'];
        }
        $this->ci->load->model('order_model');
        $orders = $this->ci->order_model->getList('id,operation_id,order_name,channel,is_enterprise,order_type,sync_status',array('id' => $c_order_ids));

        // 请求取消接口
        $this->ci->load->bll('pool');
        foreach ($orders as $order) {
            if($order['operation_id'] == 5 && $order['sync_status'] != 1){
                $this->ci->bll_pool->pool_order_cancel($order['order_name'],$order['channel'],1,$order['is_enterprise'],$order['order_type']);
            }
            $this->ci->order_model->update(array('sync_status'=>1),array('id'=>$order['id']));
            $sql = "update ttgy_oms_cancel_queue set status=1 where order_id=".$order['id'];
            $this->ci->db->query($sql);
        }
        return true;
    }

    function orderRepair($filter){
        $order_name = $filter['orderNo'];

        if (!$order_name) return array('result'=>0,'msg' => '订单号不能为空');
        $this->ci->load->model('order_model');
        $order = $this->ci->order_model->dump(array('order_name'=>$order_name),'id');
        
        if (!$order) return array('result' => 0, 'msg' => '订单不存在');
        $this->ci->load->bll('order');
        $result = $this->ci->bll_order->repair($order['id'],$msg);

        $this->rpc_log = array('rpc_desc' => '订单恢复','obj_type'=>'order','obj_name' => $order_name);

        return array('result' => $result ? 1 : 0,'msg' => $msg);
	}
	
    function getCheckStatusOrders($type,$order_name=''){
        $this->ci->load->model('order_model');
        
        // 同步2个月以内,2天未做改变的订单 
        $ftime = date('Y-m-d H:i:s',strtotime('-2 month'));
        $stime = date('Y-m-d',strtotime('-2 day'));
        switch ($type) {
            // case 'callback':
            //     $fk = "sync_status=2 and operation_id=0 AND time>=".$ftime." AND order_type!=3 AND order_type!=4";
            //     break;
            case 'sendLogistics':
                $fk = "sync_status = 1 AND operation_id in(1,4) AND time>='".$ftime."' AND order_type!=3 AND order_type!=4 AND last_modify_time<='".$stime."'";
                $order_name and $fk = "sync_status = 1 AND operation_id in(1,4) AND order_type!=3 AND order_type!=4 AND order_name='".$order_name."'";
                break;
            case 'finish':
                $fk = "sync_status = 1 AND operation_id in(2,6,9) AND time>='".$ftime."' AND order_type!=3 AND order_type!=4 AND last_modify_time<='".$stime."'";
                $order_name and $fk = "sync_status = 1 AND operation_id in(2,6,9) AND order_type!=3 AND order_type!=4 AND order_name='".$order_name."'";
                break;
            default:
                return;
                break;
        }
        $filter = array(
           $fk => null,
        );
        $orders = $this->ci->order_model->getList('id,order_name,channel,is_enterprise,channel',$filter,0,500);
        $order_arr = array();
        foreach ($orders as $key => $o) {
            $order = array();
            $order['orderNo'] = $o['order_name'];
            $order['chId'] = $this->get_pool_channel($o['channel'],$o['is_enterprise'],$o['order_type']);
            $order_arr[] = $order;
        }
        return $order_arr;
    }

    private function check_huodong_product($product_id){
        $sql = "select is_huodong from ttgy_huodong_product where product_id=".intval($product_id);
        $res = $this->ci->db->query($sql)->row_array();
        if($res && $res['is_huodong'] == 1){
            return true;
        }
        return false;
    }

    public function getRefundLog($order_name){
        $this->ci->load->bll('rpc/request');
        $log = array(
            'rpc_desc' => '订单退款日志',
            'obj_type' => 'refund_log',
            'obj_name' => $order_name,
        );
        $data['ouOrderNo'] = $order_name;
        $this->ci->bll_rpc_request->set_rpc_log($log);

        $rs = $this->ci->bll_rpc_request->realtime_call(POOL_REFUNDLOG_URL,$data,'POST',20);
        if($rs['result'] == 1){
            return $rs;
        }
        return false;
    }

    public function orderRefund($filter){
        $orders = $filter['orders'];
        if(empty($orders)) return array('result'=>0,'msg' => '订单号不能为空');
        foreach ($orders as $key => $value) {
            $order_names[] = $value;
        }
        if(empty($order_names)) return array('result'=>0,'msg' => '订单号不能为空');
        $this->ci->load->model('order_model');
        $order_ids = $this->ci->order_model->getList('id',array('order_status'=>1,'order_name'=>$order_names));
        if(empty($order_ids)) return array('result'=>0,'msg' => '官网没有找到对应的订单号');
        $orderids = array();
        foreach ($order_ids as $key => $value) {
            $orderids[] = $value['id'];
        }
        $has_refund = $this->ci->db->select('order_id')->from('ttgy_order_refund')->where_in('order_id',$orderids)->get()->result_array();
        $refund_ids = array();
        foreach ($has_refund as $key => $value) {
            $refund_ids[] = $value['order_id'];
        }
        $orderids = array_diff($orderids, $refund_ids);
        if(empty($orderids)) return array('result'=>1,'msg' => '退款已经录入成功');
        
        $insert_data = array();
        foreach ($orderids as $id) {
            $data = array();
            $data['order_id'] = $id;
            $data['has_refund'] = 1;
            $insert_data[] = $data;
        }
        $res = $this->ci->db->insert_batch("ttgy_order_refund",$insert_data);
        if($res) return array('result'=>1,'msg' => '退款录入成功');
        return array('result'=>0,'msg' => '退款录入失败');
    }

    public function getLogisticTrace($order_name){
        $this->ci->load->bll('rpc/request');
        $log = array(
            'rpc_desc' => '订单路由',
            'obj_type' => 'logistic_trace',
            'obj_name' => $order_name,
        );
        $data['orderNo'] = $order_name;
        $this->ci->bll_rpc_request->set_rpc_log($log);

        $rs = $this->ci->bll_rpc_request->realtime_call(POOL_ORDERROUTES_URL,$data,'JSON',6,base64_decode(DELIVER_AESKEY),DELIVER_HASHKEY);
        if($rs['result'] == 1){
            return $rs['routes'];
        }
        return false;
    }
}
