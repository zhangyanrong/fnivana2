<?php
/**
 * 订单池发票结构
 *
 * PHP version 5.4
 *
 * @category  PHP
 * @package   bll
 * @author    pax <chenping@fruitday.com>
 * @copyright 2014 fruitday
 * @version   GIT: $Id: Invoice.php 1 2014-08-14 10:34:22Z pax $
 * @link      http://www.fruitday.com
 **/    
namespace bll\pool;

class Invoice
{
    
    public function __construct()
    {
        $this->ci = & get_instance();
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
                    // '高新区' => '',
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
        );
        
        $region1 = array(
            '广西省' => '广西壮族自治区',
            '上海崇明' => '上海',
            '北京（五环外）'=>'北京',
        );
        $region2 = array(
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

    /**
     * 订单池发票结构
     *
     * @return void
     * @author 
     **/
    public function get_push_invoice()
    {
        $this->ci->load->model('trade_invoice_model');
        $invoice_list = $this->ci->trade_invoice_model->getList('*',array('sync_erp' => '0','time >'=>'2015-02-28 22:00:00'),0,5);
        if( !$invoice_list )  { return array(); }

        $data = array();
        foreach ($invoice_list as $key => $value) {
            $region_match = $this->region_match($value['province'],$value['city'],$value['area']);

            $data[$value['id']] = array(
                'name'           => $value['username'],
                'buyerId'        => (int) $value['uid'],
                'phone'          => $value['mobile'],
                'title'          => $value['name'],
                'province'       => (string) $region_match['province'],
                'city'           => (string) $region_match['city'],
                'area'           => (string) $region_match['area'],
                'addr'           => $value['address'],
                'deliveryPerson' => '',
                'deliveryphone'  => $value['mobile'],
                'deliveryphone2' => '',
                'amount'         => $value['money'],
                'content'        => implode(',', unserialize($value['invoice_content'])),
                'note'           => '',
                'invoiceId'     => 'TTGY_T_'.$value['id'],
            );

            unset($region_match);
        }

        $this->rpc_log = array('rpc_desc' => '发票推送','obj_type'=>'invoice');

        return $data;
    }

    function setInvoiceStatus($filter){
        $invoices = $filter['invoices'];
        if(empty($invoices)){
            return array('result' => 0,'msg'=>'无发票信息');
        }
        $errors = array();
        foreach ($invoices as $key => $invoice_info) {
            $invoice_id = $invoice_info['invoiceId'];
            $tracking_number = $invoice_info['expNo'];
            $express = $invoice_info['lcName'];
            $express_id = $invoice_info['logiId'];
            $invoice_no = $invoice_info['invCode'];
            $type = $invoice_info['appType'];
            if($type == 2){
                //充值发票
                $this->ci->load->model('trade_invoice_model');
                $result = $this->ci->trade_invoice_model->upInvoiceTrack($invoice_id,$tracking_number,$express,$express_id,$invoice_no,$msg);
            }else if($type == 9){
                //补开发票
                $this->ci->load->model('subsequent_invoice_model');
                $result = $this->ci->subsequent_invoice_model->upInvoiceTrack($invoice_id,$tracking_number,$express,$express_id,$invoice_no,$msg);
            }
            if(!$result){
                $error_info['invoiceId'] = $invoice_id;
                $error_info['msg'] = $msg;
                $errors[] = $error_info;
            }
        }
        if($errors){
            return array('result' => 0,'msg'=>'更新失败','errors'=>json_encode($errors));
        }
        return array('result' => 1,'msg'=>'更新成功');
    }
}