<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------
| DATABASE CONNECTIVITY SETTINGS
| -------------------------------------------------------------------
| This file will contain the settings needed to access your database.
|
| For complete instructions please consult the 'Database Connection'
| page of the User Guide.
|
| -------------------------------------------------------------------
| EXPLANATION OF VARIABLES
| -------------------------------------------------------------------
|
|	['hostname'] The hostname of your database server.
|	['username'] The username used to connect to the database
|	['password'] The password used to connect to the database
|	['database'] The name of the database you want to connect to
|	['dbdriver'] The database type. ie: mysql.  Currently supported:
				 mysql, mysqli, postgre, odbc, mssql, sqlite, oci8
|	['dbprefix'] You can add an optional prefix, which will be added
|				 to the table name when using the  Active Record class
|	['pconnect'] TRUE/FALSE - Whether to use a persistent connection
|	['db_debug'] TRUE/FALSE - Whether database errors should be displayed.
|	['cache_on'] TRUE/FALSE - Enables/disables query caching
|	['cachedir'] The path to the folder where cache files should be stored
|	['char_set'] The character set used in communicating with the database
|	['dbcollat'] The character collation used in communicating with the database
|				 NOTE: For MySQL and MySQLi databases, this setting is only used
| 				 as a backup if your server is running PHP < 5.2.3 or MySQL < 5.0.7
|				 (and in table creation queries made with DB Forge).
| 				 There is an incompatibility in PHP with mysql_real_escape_string() which
| 				 can make your site vulnerable to SQL injection if you are using a
| 				 multi-byte character set and are running versions lower than these.
| 				 Sites using Latin-1 or UTF-8 database character set and collation are unaffected.
|	['swap_pre'] A default table prefix that should be swapped with the dbprefix
|	['autoinit'] Whether or not to automatically initialize the database.
|	['stricton'] TRUE/FALSE - forces 'Strict Mode' connections
|							- good for ensuring strict SQL while developing
|
| The $active_group variable lets you choose which connection group to
| make active.  By default there is only one group (the 'default' group).
|
| The $active_record variables lets you determine whether or not to load
| the active record class
*/

$active_group = 'default_master';
$active_record = TRUE;
$_master_slave_relation = array(
    'default_master' => array('default_slave','default_slave2','default_slave3','default_slave4','dts2','dts3'),
# 'default_master'=>array(),
);

$db['default_master']['hostname'] = 'rdsazjayeazjaye.mysql.rds.aliyuncs.com';
$db['default_master']['username'] = 'www1';
$db['default_master']['password'] = 'sekrtwww';
$db['default_master']['database'] = 'gold';
$db['default_master']['dbdriver'] = 'mysqli';
$db['default_master']['dbprefix'] = 'ttgy_';
$db['default_master']['pconnect'] = FALSE;
$db['default_master']['db_debug'] = FALSE;
$db['default_master']['cache_on'] = FALSE;
$db['default_master']['cachedir'] = '';
$db['default_master']['char_set'] = 'utf8';
$db['default_master']['dbcollat'] = 'utf8_general_ci';
$db['default_master']['swap_pre'] = '';
$db['default_master']['autoinit'] = FALSE;
$db['default_master']['stricton'] = FALSE;

$db['default_slave']['hostname'] = 'rdsaykfl5d2njaqnky0i8.mysql.rds.aliyuncs.com';
$db['default_slave']['username'] = 'www1';
$db['default_slave']['password'] = 'sekrtwww';
$db['default_slave']['database'] = 'gold';
$db['default_slave']['dbdriver'] = 'mysqli';
$db['default_slave']['dbprefix'] = 'ttgy_';
$db['default_slave']['pconnect'] = FALSE;
$db['default_slave']['db_debug'] = FALSE;
$db['default_slave']['cache_on'] = FALSE;
$db['default_slave']['cachedir'] = '';
$db['default_slave']['char_set'] = 'utf8';
$db['default_slave']['dbcollat'] = 'utf8_general_ci';
$db['default_slave']['swap_pre'] = '';
$db['default_slave']['autoinit'] = FALSE;
$db['default_slave']['stricton'] = FALSE;

$db['default_slave2']['hostname'] = 'rdsjqf76a8nb8b2cbcx3i.mysql.rds.aliyuncs.com';
$db['default_slave2']['username'] = 'www1';
$db['default_slave2']['password'] = 'sekrtwww';
$db['default_slave2']['database'] = 'gold';
$db['default_slave2']['dbdriver'] = 'mysqli';
$db['default_slave2']['dbprefix'] = 'ttgy_';
$db['default_slave2']['pconnect'] = FALSE;
$db['default_slave2']['db_debug'] = FALSE;
$db['default_slave2']['cache_on'] = FALSE;
$db['default_slave2']['cachedir'] = '';
$db['default_slave2']['char_set'] = 'utf8';
$db['default_slave2']['dbcollat'] = 'utf8_general_ci';
$db['default_slave2']['swap_pre'] = '';
$db['default_slave2']['autoinit'] = FALSE;
$db['default_slave2']['stricton'] = FALSE;

$db['default_slave3']['hostname'] = 'rdsc92ehhze1w7nhr2d2o.mysql.rds.aliyuncs.com';
$db['default_slave3']['username'] = 'www1';
$db['default_slave3']['password'] = 'sekrtwww';
$db['default_slave3']['database'] = 'gold';
$db['default_slave3']['dbdriver'] = 'mysqli';
$db['default_slave3']['dbprefix'] = 'ttgy_';
$db['default_slave3']['pconnect'] = FALSE;
$db['default_slave3']['db_debug'] = FALSE;
$db['default_slave3']['cache_on'] = FALSE;
$db['default_slave3']['cachedir'] = '';
$db['default_slave3']['char_set'] = 'utf8';
$db['default_slave3']['dbcollat'] = 'utf8_general_ci';
$db['default_slave3']['swap_pre'] = '';
$db['default_slave3']['autoinit'] = FALSE;
$db['default_slave3']['stricton'] = FALSE;

$db['default_slave4']['hostname'] = 'rds1x1fy96971irb9hz5.mysql.rds.aliyuncs.com';
$db['default_slave4']['username'] = 'www1';
$db['default_slave4']['password'] = 'sekrtwww';
$db['default_slave4']['database'] = 'gold';
$db['default_slave4']['dbdriver'] = 'mysqli';
$db['default_slave4']['dbprefix'] = 'ttgy_';
$db['default_slave4']['pconnect'] = FALSE;
$db['default_slave4']['db_debug'] = FALSE;
$db['default_slave4']['cache_on'] = FALSE;
$db['default_slave4']['cachedir'] = '';
$db['default_slave4']['char_set'] = 'utf8';
$db['default_slave4']['dbcollat'] = 'utf8_general_ci';
$db['default_slave4']['swap_pre'] = '';
$db['default_slave4']['autoinit'] = FALSE;
$db['default_slave4']['stricton'] = FALSE;


$db['default_slave5']['hostname'] = 'rds1yusmb208ec93ort0q.mysql.rds.aliyuncs.com';
$db['default_slave5']['username'] = 'www1';
$db['default_slave5']['password'] = 'sekrtwww';
$db['default_slave5']['database'] = 'gold';
$db['default_slave5']['dbdriver'] = 'mysqli';
$db['default_slave5']['dbprefix'] = 'ttgy_';
$db['default_slave5']['pconnect'] = FALSE;
$db['default_slave5']['db_debug'] = FALSE;
$db['default_slave5']['cache_on'] = FALSE;
$db['default_slave5']['cachedir'] = '';
$db['default_slave5']['char_set'] = 'utf8';
$db['default_slave5']['dbcollat'] = 'utf8_general_ci';
$db['default_slave5']['swap_pre'] = '';
$db['default_slave5']['autoinit'] = FALSE;
$db['default_slave5']['stricton'] = FALSE;


$db['dts1']['hostname'] = 'rdsd2j4hj6hzi19f656e.mysql.rds.aliyuncs.com';
$db['dts1']['username'] = 'www1';
$db['dts1']['password'] = 'sekrtwww';
$db['dts1']['database'] = 'gold';
$db['dts1']['dbdriver'] = 'mysqli';
$db['dts1']['dbprefix'] = 'ttgy_';
$db['dts1']['pconnect'] = FALSE;
$db['dts1']['db_debug'] = FALSE;
$db['dts1']['cache_on'] = FALSE;
$db['dts1']['cachedir'] = '';
$db['dts1']['char_set'] = 'utf8';
$db['dts1']['dbcollat'] = 'utf8_general_ci';
$db['dts1']['swap_pre'] = '';
$db['dts1']['autoinit'] = FALSE;
$db['dts1']['stricton'] = FALSE;


$db['dts2']['hostname'] = 'rds3t83xm2x5jrw6yz3v.mysql.rds.aliyuncs.com';
$db['dts2']['username'] = 'www1';
$db['dts2']['password'] = 'sekrtwww';
$db['dts2']['database'] = 'gold';
$db['dts2']['dbdriver'] = 'mysqli';
$db['dts2']['dbprefix'] = 'ttgy_';
$db['dts2']['pconnect'] = FALSE;
$db['dts2']['db_debug'] = FALSE;
$db['dts2']['cache_on'] = FALSE;
$db['dts2']['cachedir'] = '';
$db['dts2']['char_set'] = 'utf8';
$db['dts2']['dbcollat'] = 'utf8_general_ci';
$db['dts2']['swap_pre'] = '';
$db['dts2']['autoinit'] = FALSE;
$db['dts2']['stricton'] = FALSE;


$db['dts3']['hostname'] = 'rds6z2944ydy03j53zl0.mysql.rds.aliyuncs.com';
$db['dts3']['username'] = 'www1';
$db['dts3']['password'] = 'sekrtwww';
$db['dts3']['database'] = 'gold';
$db['dts3']['dbdriver'] = 'mysqli';
$db['dts3']['dbprefix'] = 'ttgy_';
$db['dts3']['pconnect'] = FALSE;
$db['dts3']['db_debug'] = FALSE;
$db['dts3']['cache_on'] = FALSE;
$db['dts3']['cachedir'] = '';
$db['dts3']['char_set'] = 'utf8';
$db['dts3']['dbcollat'] = 'utf8_general_ci';
$db['dts3']['swap_pre'] = '';
$db['dts3']['autoinit'] = FALSE;
$db['dts3']['stricton'] = FALSE;


$db['db_log']['hostname'] = 'rdsazjayeazjaye.mysql.rds.aliyuncs.com';
$db['db_log']['username'] = 'www1';
$db['db_log']['password'] = 'sekrtwww';
$db['db_log']['database'] = 'db_log';
$db['db_log']['dbdriver'] = 'mysqli';
$db['db_log']['dbprefix'] = 'tb_';
$db['db_log']['pconnect'] = FALSE;
$db['db_log']['db_debug'] = FALSE;
$db['db_log']['cache_on'] = FALSE;
$db['db_log']['cachedir'] = '';
$db['db_log']['char_set'] = 'utf8';
$db['db_log']['dbcollat'] = 'utf8_general_ci';
$db['db_log']['swap_pre'] = '';
$db['db_log']['autoinit'] = FALSE;
$db['db_log']['stricton'] = FALSE;
/* End of file database.php */
/* Location: ./application/config/database.php */

