<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0777);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/

define('FOPEN_READ',							'rb');
define('FOPEN_READ_WRITE',						'r+b');
define('FOPEN_WRITE_CREATE_DESTRUCTIVE',		'wb'); // truncates existing file data, use with care
define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE',	'w+b'); // truncates existing file data, use with care
define('FOPEN_WRITE_CREATE',					'ab');
define('FOPEN_READ_WRITE_CREATE',				'a+b');
define('FOPEN_WRITE_CREATE_STRICT',				'xb');
define('FOPEN_READ_WRITE_CREATE_STRICT',		'x+b');

/*订单池接口*/
define('AES_KEY', 'SNwtRw68U23J0m4784frWAd3H3JpAmkn7pty/JLsrPc=');
define('HASH256_KEY', '12345678910111213fruitday');

define('POOL_ORDER_URL', 'http://122.144.167.61:38080/official/ordersync');
define('POOL_INVOICE_URL','http://122.144.167.61:38080/official/invoiceSync');
define('POOL_ORDER_STATUS_URL','http://122.144.167.61:38080/official/syncOrderState');
define('POOL_RECHARGE_URL','http://122.144.167.61:38080/official/chargeSync');
define('POOL_SYNCFEE_URL','http://122.144.167.61:38080/official/syncFee');
define('POOL_GETEXPNO_URL','http://122.144.167.61:38080/official/getExpNo');
define('POOL_APPROVAL_URL','http://122.144.167.61:38080/official/approval');
define('POOL_TRANSACTION_URL','http://54.223.45.237:8081/third-api/billfee/syncFeeMsg');
define('POOL_LOGISTICTRACE_URL','http://122.144.167.61:38086/api/routeQService');

/* O2O <=> OMS 接口 */
defined('POOL_O2O_AES_KEY')     or define('POOL_O2O_AES_KEY', 'SNwtRw68U23J0m4784frWAd3H3JpAmkn7pty/JLsrPc=');
defined('POOL_O2O_APPID')       or define('POOL_O2O_APPID', '00000000001');
defined('POOL_O2O_VERSION')     or define('POOL_O2O_VERSION', '1.0');
defined('POOL_O2O_SECRET')      or define('POOL_O2O_SECRET', 'a03a1553fbb9f7c80fe43d9836c8564a');
defined('POOL_O2O_OMS_APPID')   or define('POOL_O2O_OMS_APPID', '00000000001');
defined('POOL_O2O_OMS_VERSION') or define('POOL_O2O_OMS_VERSION', '1.0');
defined('POOL_O2O_OMS_SECRET')  or define('POOL_O2O_OMS_SECRET', 'secretkey');
defined('POOL_O2O_OMS_URL')     or define('POOL_O2O_OMS_URL', 'http://54.223.35.147:28080/open-ext-api/api');

defined('POOL_O2O_TMS_URL')     or define('POOL_O2O_TMS_URL', 'http://pt.fruitday.com/router');
defined('POOL_O2O_TMS_SECRET')  or define('POOL_O2O_TMS_SECRET', 'lkjflkdjsalfjdlsajflkjdsaf');
defined('POOL_O2O_TMS_APPID')   or define('POOL_O2O_TMS_APPKEY', 'ios01');
defined('POOL_O2O_TMS_VERSION') or define('POOL_O2O_TMS_VERSION', '1.0');

// fday_config
define('PRO_SECRET', 'ee4a5e81f08d491987567104zec97737');
define('ERP_TEST',true);
define('PIC_URL',"http://cdn.fruitday.com/");
define('IMG_URL',"http://img4.fruitday.com/");
define('PIC_URL_TMP',"http://apicdn.fruitday.com/img/");
define('API_SECRET',"caa21c26dfc990c7a534425ec87a111c");
define('OPEN_MEMCACHE',true);
define('OPEN_SPHINX',true);
define('SMS_CHANNEL','local');
define('SMS_API_URL','http://3tong.net/http/sms/Submit');
define('SMS_ACCOUNT','dh1689');
define('SMS_PASSWD','8s7*KYaL');
define('SMS_SECRET','3410w312ecf4a3j814y50b6abff6f6b97e16');
define('WAPI_API_SECRET','f3409da68c62ab2d24b3938807d5259');
define('OPENAPI_SECRET','d50b6a5ff6ff4a3j814y6f6b97ec62ab');
define('REFRESH_MEMCACHE','api.service'); //api.service
define('O2O_SECRET','3ca59a237313bdad9244145641244946');
define('CRM_SECRET', '56b44d6cd9b7f902ef36f1f0c1dac79f');
define('CRM_DATA_SECRET', 'KJKL234NLJ32LKJL');

define('WX_APP_ID', 'wx1061e4e55dd6de25');
define('WX_SECRET', '9dbfb0f945333b1c141cbc215aa734c3');

//if(!defined('REFRESH_MEMCACHE')){
//  define('REFRESH_MEMCACHE','region.getRegion');
//}
/* End of file constants.php */
/* Location: ./application/config/constants.php */
define('OPEN_CURL_PROXY', true);
define('CURL_PROXY_PORT','8088');
define('CURL_PROXY_ADDR','10.251.241.71');
// define('CURL_PROXY_ADDR_II','10.160.41.235');
