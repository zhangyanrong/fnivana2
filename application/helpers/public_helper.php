<?php
function is_mobile($mobile)
{
    if (preg_match("/^1[0-9]{10}$/", $mobile)) {
        return true;
    } else {
        return false;
    }
}

function isint($val)
{
    preg_match("/^[0-9]*$/", $val, $matches);

    if (isset($matches[0]) && $matches[0]) {
        return true;
    } else {
        return false;
    }
}

function getMethod()
{
    return $_SERVER['REQUEST_METHOD'];
}

function arrayRecursive(&$array, $function, $apply_to_keys_also = false)
{
    static $recursive_counter = 0;
    if (++$recursive_counter > 1000) {
        die('possible deep recursion attack');
    }
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            arrayRecursive($array[$key], $function, $apply_to_keys_also);
        } else {
            $array[$key] = $function(addslashes(strip_tags($value)));
        }

        if ($apply_to_keys_also && is_string($key)) {
            $new_key = $function($key);
            if ($new_key != $key) {
                $array[$new_key] = $array[$key];
                unset($array[$key]);
            }
        }
    }
    $recursive_counter--;
}

function JSON($array)
{
    arrayRecursive($array, 'urlencode', true);
    $json = json_encode($array);
    return urldecode($json);
}

function pasre_api_data($input)
{
    $input = json_encode($input);

    if (isset($input['data'])) {
        return $input['data'];
    }
}

function rand_code($length = 6)
{
    $code="";
    for ($i=0; $i<$length; $i++) {
        $code .= mt_rand(0, 9);
    }
    return $code;
}

function encrypt_num($str)
{
    if (!isint($str)) {
        if (!isint(str_replace("****", "", $str))) {
            $ret_str = mb_substr($str, 0, 7);
            if (mb_strlen($str)>7) {
                $ret_str = $ret_str."...";
            }
            return $ret_str;
        } else {
            return $str;
        }
    }

    $len = strlen($str);
    if ($len == 2) {
        return str_replace(substr($str, 1), "*", $str);
    } else {
        return str_replace(substr($str, 3, 11), "********", $str);
    }
}

/*生成唯一短标示码*/
function tag_code($str)
{
    $str = crc32($str);
    $x = sprintf("%u", $str);
    $show = '';
    while ($x > 0) {
        $s = $x % 62;
        if ($s > 35) {
            $s = chr($s+61);
        } elseif ($s > 9 && $s <=35) {
            $s = chr($s + 55);
        }
        $show .= $s;
        $x = floor($x/62);
    }
    return $show;
}

/*
*必填参数&长度验证
*/
function check_required($params, $required_fields)
{
    foreach ($required_fields as $key => $value) {
        if (!isset($params[$key]) || $params[$key]=='') {
            return array('code'=>$value['required']['code'],'msg'=>$value['required']['msg']);
        }
        if (isset($value['length'])) {
            if (strlen($params[$key])>$value['length']['length']) {
                return array('code'=>$value['length']['code'],'msg'=>$value['length']['msg']);
            }
        }
    }
    return false;
}

if (!function_exists('array_column')) {
    function array_column(array $array, $column_key, $index_key = null)
    {
        $result = array();
        foreach ($array as $arr) {
            if (!is_array($arr)) {
                continue;
            }

            if (is_null($column_key)) {
                $value = $arr;
            } else {
                $value = $arr[$column_key];
            }

            if (!is_null($index_key)) {
                $key = $arr[$index_key];
                $result[$key] = $value;
            } else {
                $result[] = $value;
            }
        }
        return $result;
    }
}

function sortByArray($array, $sortArray, $field = 'id')
{
    if (!is_array($sortArray)) {
        return false;
    }

    $result = array();
    $temp = array_column($array, null, $field);

    foreach ($sortArray as $val) {
        if (isset($temp[$val])) {
            array_push($result, $temp[$val]);
        }
    }

    return $result;
}

/**
 * Ellipsize String
 *
 * This function will strip tags from a string, split it at its max_length and ellipsize
 *
 * @param   string      string to ellipsize
 * @param   integer     max length of string
 * @param   mixed       int (1|0) or float, .5, .2, etc for position to split
 * @param   string      ellipsis ; Default '...'
 * @return  string      ellipsized string
 */
if (! function_exists('ellipsize')) {
    function ellipsize($str, $max_length, $position = 1, $ellipsis = '&hellip;')
    {
        // Strip tags
        $str = trim(strip_tags($str));

        // Is the string long enough to ellipsize?
        if (strlen($str) <= $max_length) {
            return $str;
        }

        $beg = substr($str, 0, floor($max_length * $position));

        $position = ($position > 1) ? 1 : $position;

        if ($position === 1) {
            $end = substr($str, 0, -($max_length - strlen($beg)));
        } else {
            $end = substr($str, -($max_length - strlen($beg)));
        }

        return $beg.$ellipsis.$end;
    }
}

/*********************************************************************
函数名称:encrypt
函数作用:加密解密字符串
使用方法:
加密     :encrypt('str','E','nowamagic');
解密     :encrypt('被加密过的字符串','D','nowamagic');
参数说明:
$string   :需要加密解密的字符串
$operation:判断是加密还是解密:E:加密   D:解密
$key      :加密的钥匙(密匙);
 *********************************************************************/
function encrypt($string, $operation, $key = '')
{
    $key=md5($key);
    $key_length=strlen($key);
    $string=$operation=='D'?base64_decode($string):substr(md5($string.$key), 0, 8).$string;
    $string_length=strlen($string);
    $rndkey=$box=array();
    $result='';
    for ($i=0; $i<=255; $i++) {
        $rndkey[$i]=ord($key[$i%$key_length]);
        $box[$i]=$i;
    }
    for ($j=$i=0; $i<256; $i++) {
        $j=($j+$box[$i]+$rndkey[$i])%256;
        $tmp=$box[$i];
        $box[$i]=$box[$j];
        $box[$j]=$tmp;
    }
    for ($a=$j=$i=0; $i<$string_length; $i++) {
        $a=($a+1)%256;
        $j=($j+$box[$a])%256;
        $tmp=$box[$a];
        $box[$a]=$box[$j];
        $box[$j]=$tmp;
        $result.=chr(ord($string[$i])^($box[($box[$a]+$box[$j])%256]));
    }
    if ($operation=='D') {
        if (substr($result, 0, 8)==substr(md5(substr($result, 8).$key), 0, 8)) {
            return substr($result, 8);
        } else {
            return'';
        }
    } else {
        return str_replace('=', '', base64_encode($result));
    }
}

/*
*获取cdn图片路径通用方法
*/
function cdnImageUrl($id = 0)
{
    return constant(CDN_URL.($id%9+1));
}


/*
 * 年轮 － 门店列表转换
 */
function changeStoreId($ids)
{
    $params['store_id_list'] = $ids;

    $store_id = explode(',', $ids);
    $list = array();

    $params['tms_region_type'] = 1;
    $params['tms_region_time'] = 1;

    if (count($store_id) >1) {
        $tms_region_type = array();
        $tms_region_time = array();
        foreach ($store_id as $k => $v) {
            if (strpos($v, "T")) {
                $nian = explode('T', $v);
                array_push($tms_region_type, $nian[1]);
                array_push($tms_region_time, $nian[2]);
                array_push($list, $nian[0]);
            } else {
                array_push($list, $v);
            }
        }

        if (count($tms_region_type) >0) {
            $params['tms_region_type'] = min($tms_region_type);
            $params['tms_region_time'] = min($tms_region_time);
        }
        $params['store_id_list'] = implode(',', $list);
    }

    return $params['store_id_list'];
}

//不完美拆单
function imperfect_split($order, $items, &$counts)
{
    asort($order);
    $dis_types = array_keys($order);

    $amount = 0;
    foreach ($items as $item) {
        $amount = bcadd($amount, bcsub($item['amount'], $item['discount'], 2), 2);
    }
    $count_split_nums = array();
    $all_fz = array();
    $add_fz = array();
    $add_fm = 0;
    $sort_arr = array();
    $sort_arr2 = array();
    foreach ($items as $item) {
        foreach ($dis_types as $type) {
            $key = $type.'-'.$item['id'];
            if ($item[$type] == 2 || $amount == 0) {
                $count_split_nums[$type] ++;
                $all_fz[$type] = '';
            } else {
                if ($item[$type] != 0 && count($items) > 1) {
                    if (!isset($all_fz[$type])) {
                        $all_fz[$type] = $item['id'];
                    } else {
                        $all_fz[$type] = '';
                    }
                }
            }
            if (!isset($sort_arr2[$type])) {
                $sort_arr2[$type] = 0;
            }
            if (! $item[$type]) {
                array_unshift($sort_arr, $key);
            } else {
                array_push($sort_arr, $key);
                $sort_arr2[$type] ++ ;
            }
        }
    }
    asort($sort_arr2);
    foreach ($items as $item) {
        foreach ($dis_types as $type) {
            $key = $type.'-'.$item['id'];
            if ($item[$type] == 2 || $amount == 0) {
                $add_fz[$item['id']] = bcadd($add_fz[$item['id']], bcmul(bcdiv(1, $count_split_nums[$type], 8), $order[$type], 2), 2);
                if (!isset($add_type[$type]) || $add_type[$type] != 1) {
                    $add_fm = bcsub($add_fm, $order[$type], 2);
                    $add_type[$type] = 1;
                }
            } elseif ($all_fz[$type] && $all_fz[$type] == $item['id']) {
                $add_fz[$item['id']] = bcadd($add_fz[$item['id']], $order[$type], 2);
                if (!isset($add_type[$type]) || $add_type[$type] != 1) {
                    //$add_fm = bcsub($add_fm,$order[$type],2);
                    $add_type[$type] = 1;
                }
            }
        }
    }
    $discount_fz = array();
    $discount_fm = array();
    $mul = array();
    foreach ($items as $item) {
        foreach ($dis_types as $type) {
            $key = $type.'-'.$item['id'];
            if ($order[$type] == 0) {
                $discount_fz[$key] = 0;
                $discount_fm[$type] = 1;
                continue;
            }
            if (isset($item[$type])) {
                if ($item[$type] == 2 || $amount == 0) { //按份数分摊
                    $discount_fz[$key] = 1;
                    $discount_fm[$type] = $count_split_nums[$type];
                } elseif ($item[$type] == 1) {
                    $fz = bcsub($item['amount'], $item['discount'], 2);
                    $discount_fz[$type.'-'.$item['id']] = bcsub($fz, $add_fz[$item['id']], 2);
                    $discount_fm[$type] = bcadd(isset($discount_fm[$type])?$discount_fm[$type]:0, $discount_fz[$type.'-'.$item['id']], 2);
                    if ($all_fz[$type] && $all_fz[$type] == $item['id']) {
                        $discount_fz[$type.'-'.$item['id']] = 1;
                        $discount_fm[$type] = 1;
                    }
                } else {
                    $discount_fz[$type.'-'.$item['id']] = 0;
                    if (! $all_fz[$type]) {
                        //$discount_fm[$type] = bcsub($discount_fm[$type], $add_fz[$item['id']], 2);
                    }
                    $mul[$type][$item['id']] = bcdiv(bcsub($amount, bcsub(bcsub($item['amount'], $item['discount'], 2), $add_fz[$item['id']], 2), 2), $amount, 4);
                }
            } else {
                $discount_fz[$type.'-'.$item['id']] = $item['amount'];
                $discount_fm[$type] = bcadd(isset($discount_fm[$type])?$discount_fm[$type]:0, $item['amount'], 2);
            }
        }
    }

    $start = array();
    $end = array();
    $bls = array();
    $f_bls = array(); //负数比例
    foreach ($discount_fz as $key => $v) {
        $keyarr = explode('-', $key);
        $dis_type = $keyarr[0];
        $dis_item = $keyarr[1];
        if (!isset($items[$dis_item][$dis_type])) {
            continue;
        }
        if ($order[$dis_type] < 0) {
            $f_bls[$key] = bcdiv($v, $discount_fm[$dis_type], 8);
        } else {
            $bls[$key] = bcdiv($v, $discount_fm[$dis_type], 8);
        }
    }
    asort($f_bls);
    arsort($bls);
    $bls = array_merge($f_bls, $bls); //运费等增加订单金额项目先分摊
    $bl_amount = array();
    foreach ($bls as $key => $bl) {
        $keyarr = explode('-', $key);
        $dis_type = $keyarr[0];
        $dis_item = $keyarr[1];
        if ($order[$dis_type] == 0) {
            continue;
        }
        if (! isset($bl_amount[$dis_item])) {
            $bl_amount[$dis_item] = 0;
        }

        $am = bcdiv(bcmul($discount_fz[$key], $order[$dis_type], 2), $discount_fm[$dis_type], 2);
        foreach ($mul as $t => $m) {
            foreach ($m as $i => $v) {
                if ($i == $dis_item && $t == $dis_type) {
                    $am = $am;
                } elseif ($i == $dis_item && $t != $dis_type) {
                    //$am = bcdiv($am, $v , 2);
                } elseif ($i != $dis_item && $t == $dis_type) {
                    $am = $am;
                } else {
                    $am = bcmul($am, $v, 2);
                }
            }
        }

        if (bcadd($bl_amount[$dis_item], $am, 2) > bcsub($items[$dis_item]['amount'], $items[$dis_item]['discount'], 2) && $discount_fz[$key] != 0 && $order[$dis_type] != 0) {
            $check_fz = bcsub(bcsub($items[$dis_item]['amount'], $items[$dis_item]['discount'], 2), $bl_amount[$dis_item], 2);
            if ($check_fz > 0) {
            } else {
                $check_fz = 0;
            }
            $discount_fz[$key] = bcdiv(bcmul($check_fz, $discount_fm[$dis_type], 2), $order[$dis_type], 2);
            $bl_amount[$dis_item] = bcadd($bl_amount[$dis_item], $check_fz, 2);
        } else {
            $bl_amount[$dis_item] = bcadd($bl_amount[$dis_item], $am, 2);
        }
    }

    foreach ($sort_arr2 as $type => $value) {
        foreach ($sort_arr as $key) {
            $keyarr = explode('-', $key);
            $dis_type = $keyarr[0];
            $dis_item = $keyarr[1];
            if ($dis_type == $type) {
                $am = bcdiv(bcmul($discount_fz[$key], $order[$dis_type], 2), $discount_fm[$dis_type], 2);
                if (isset($items[$dis_item][$dis_type])) {
                    foreach ($mul as $t => $m) {
                        foreach ($m as $i => $v) {
                            if ($i == $dis_item && $t == $dis_type) {
                                $am = $am;
                            } elseif ($i == $dis_item && $t != $dis_type) {
                                //$am = bcdiv($am, $v , 2);
                            } elseif ($i != $dis_item && $t == $dis_type) {
                                $am = $am;
                            } else {
                                if ($items[$dis_item][$type] !== 2) {
                                    $am = bcmul($am, $v, 2);
                                }
                            }
                        }
                    }
                }
                $start[$key] = $am;
                //$start[$key] = bcdiv(bcmul($discount_fz[$key], $order[$dis_type],4),$discount_fm[$dis_type],2);
                if ($discount_fz[$key] == 0) {
                    $end[$key] = 0;
                } else {
                    $end[$key] = $order[$dis_type];
                }
            }
        }
    }
    $result = do_imperfect_split($items, $dis_types, $order, $start, $end, $counts);
    return $result;
}

//不完美拆单
function do_imperfect_split($items, $types, $order, $start, $end, &$counts)
{
    $counts ++;
    $data = array();

    foreach ($start as $key => $a_m) {
        $keyarr = explode('-', $key);
        $type = $keyarr[0];
        $itemid = $keyarr[1];
        if (! isset($one_type_discount[$type])) {
            $one_type_discount[$type] = 0;
        }
        if (! isset($one_item_discount[$itemid])) {
            $one_item_discount[$itemid] = 0;
        }
        $one_type_discount[$type] = bcadd($one_type_discount[$type], $a_m, 2);
        if (isset($items[$itemid][$type])) {
            $one_item_discount[$itemid] = bcadd($one_item_discount[$itemid], $a_m, 2);
        }
    }
    $is_succ = true;
    $fail_type = array();
    foreach ($types as $type) {
        $diff_type = bcsub($order[$type], $one_type_discount[$type], 2);
        if ($diff_type != 0) {
            $is_succ = "false1";
            $fail_type[$type] = $diff_type;
        }
    }
    $fail_item = array();
    foreach ($items as $item) {
        $diff_item = bcsub(bcsub($item['amount'], $item['discount'], 2), $one_item_discount[$item['id']], 2);
        if (bccomp($diff_item, 0, 2) != 0) {
            $is_succ = "false2";
            $fail_item[$item['id']] = $diff_item;
        }
    }
    $diff_item_discount = array();
    foreach ($fail_type as $type => $type_m) {
        foreach ($items as $itemid => $item) {
            $key = $type."-".$itemid;
            if (! isset($diff_item_discount[$itemid])) {
                $diff_item_discount[$itemid] = 0;
            }
            if (bcsub($end[$key], $start[$key], 2) > 0) {
                $diff_item_discount[$itemid] = bcadd($diff_item_discount[$itemid], min(bcsub($end[$key], $start[$key], 2), $type_m), 2);
            }
        }
    }
    if ($is_succ === true) {
        foreach ($start as $key => $value) {
            $keyarr = explode('-', $key);
            $type = $keyarr[0];
            $itemid = $keyarr[1];
            $data[$itemid][$type] = abs($value);
            $data[$itemid]['amount'] = $items[$itemid]['amount'];
            $data[$itemid]['discount'] = $items[$itemid]['discount'];
        }
        return $data;
    } else {
        $new_start = $start;
        foreach ($start as $sp_key => $value) {
            $keyarr = explode('-', $sp_key);
            $type = $keyarr[0];
            $itemid = $keyarr[1];
            if (! in_array($type, array_keys($fail_type))) {
                continue;
            }
            $item = $items[$itemid];
            for ($amount[$sp_key] = $start[$sp_key]; abs($amount[$sp_key]) < abs($end[$sp_key]) && abs($one_type_discount[$type]) < abs($order[$type]);) {
                $add_arr = array();

                $add_arr[] = bcsub(abs($end[$sp_key]), abs($amount[$sp_key]), 2);

                if (isset($items[$itemid][$type])) {
                    if ($diff_item_discount[$itemid] > 0) {
                        $add_arr[] = $diff_item_discount[$itemid];
                    }
                    if (isset($fail_item[$itemid]) && abs($fail_item[$itemid]) > 0) {
                        $add_arr[] = abs($fail_item[$itemid]);
                    }
                }

                if (isset($fail_type[$type]) && abs($fail_type[$type]) > 0) {
                    $add_arr[] = abs($fail_type[$type]);
                }
                $add = min($add_arr);
                if ($add == 0) {
                    break;
                }

                if ($order[$type] < 0 && $diff_item_discount[$itemid] > 0) {
                    $amount[$sp_key] = bcsub($amount[$sp_key], $add, 2);
                    $one_type_discount[$type] = bcsub($one_type_discount[$type], $add, 2);
                    if (isset($items[$item['id']][$type])) {
                        $one_item_discount[$item['id']] = bcsub($one_item_discount[$item['id']], $add, 2);
                    }
                } else {
                    if (isset($items[$item['id']][$type]) && bcsub(bcsub($item['amount'], $item['discount'], 2), $one_item_discount[$item['id']], 2) <= 0) {
                        break;
                    }
                    $amount[$sp_key] = bcadd($amount[$sp_key], $add, 2);
                    $one_type_discount[$type] = bcadd($one_type_discount[$type], $add, 2);
                    if (isset($items[$item['id']][$type])) {
                        $one_item_discount[$item['id']] = bcadd($one_item_discount[$item['id']], $add, 2);
                    }
                }
                $new_start[$sp_key] = $amount[$sp_key];
                return do_imperfect_split($items, $types, $order, $new_start, $end, $counts);
            }
        }
    }
}
