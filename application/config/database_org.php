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


$db['default']['username'] = 'ekam_user';
$db['default']['password'] = '#k@m_e$#R';


$db['default']['username'] = 'ekam_user';
$db['default']['password'] = 'XL9MvPvhNK8qD';

*/

$active_group = 'default';
$active_record = TRUE;

$db['default']['hostname'] = 'localhost';
$db['default']['username'] = 'ekam_user';
$db['default']['password'] = 'XL9MvPvhNK8qD';
$db['default']['database'] = 'ekam';
$db['default']['dbdriver'] = 'mysqli';
$db['default']['dbprefix'] = '';
$db['default']['pconnect'] = TRUE;
$db['default']['db_debug'] = TRUE;
$db['default']['cache_on'] = FALSE;
$db['default']['cachedir'] = '';
$db['default']['char_set'] = 'utf8';
$db['default']['dbcollat'] = 'utf8_general_ci';
$db['default']['swap_pre'] = '';
$db['default']['autoinit'] = TRUE;
$db['default']['stricton'] = FALSE;



$db['blessing']['hostname'] = 'localhost';
$db['blessing']['username'] = 'ekam_user';
$db['blessing']['password'] = 'XL9MvPvhNK8qD';
$db['blessing']['database'] = 'blessing';
$db['blessing']['dbdriver'] = 'mysqli';
$db['blessing']['dbprefix'] = '';
$db['blessing']['pconnect'] = TRUE;
$db['blessing']['db_debug'] = TRUE;
$db['blessing']['cache_on'] = FALSE;
$db['blessing']['cachedir'] = '';
$db['blessing']['char_set'] = 'utf8';
$db['blessing']['dbcollat'] = 'utf8_general_ci';
$db['blessing']['swap_pre'] = '';
$db['blessing']['autoinit'] = TRUE;
$db['blessing']['stricton'] = FALSE;
 



$db['ticket']['hostname'] = 'localhost';
$db['ticket']['username'] = 'ekam_user';
$db['ticket']['password'] = 'XL9MvPvhNK8qD';
$db['ticket']['database'] = 'ticket';
$db['ticket']['dbdriver'] = 'mysqli';
$db['ticket']['dbprefix'] = '';
$db['ticket']['pconnect'] = TRUE;
$db['ticket']['db_debug'] = TRUE;
$db['ticket']['cache_on'] = FALSE;
$db['ticket']['cachedir'] = '';
$db['ticket']['char_set'] = 'utf8';
$db['ticket']['dbcollat'] = 'utf8_general_ci';
$db['ticket']['swap_pre'] = '';
$db['ticket']['autoinit'] = TRUE;
$db['ticket']['stricton'] = FALSE;



$db['messages']['hostname'] = 'localhost';
$db['messages']['username'] = 'ekam_user';
$db['messages']['password'] = 'XL9MvPvhNK8qD';
$db['messages']['database'] = 'messages';
$db['messages']['dbdriver'] = 'mysqli';
$db['messages']['dbprefix'] = '';
$db['messages']['pconnect'] = TRUE;
$db['messages']['db_debug'] = TRUE;
$db['messages']['cache_on'] = FALSE;
$db['messages']['cachedir'] = '';
$db['messages']['char_set'] = 'utf8';
$db['messages']['dbcollat'] = 'utf8_general_ci';
$db['messages']['swap_pre'] = '';
$db['messages']['autoinit'] = TRUE;
$db['messages']['stricton'] = FALSE;



$db['paypal']['hostname'] = 'localhost';
$db['paypal']['username'] = 'ekam_user';
$db['paypal']['password'] = 'XL9MvPvhNK8qD';
$db['paypal']['database'] = 'paypal';
$db['paypal']['dbdriver'] = 'mysqli';
$db['paypal']['dbprefix'] = '';
$db['paypal']['pconnect'] = TRUE;
$db['paypal']['db_debug'] = TRUE;
$db['paypal']['cache_on'] = FALSE;
$db['paypal']['cachedir'] = '';
$db['paypal']['char_set'] = 'utf8';
$db['paypal']['dbcollat'] = 'utf8_general_ci';
$db['paypal']['swap_pre'] = '';
$db['paypal']['autoinit'] = TRUE;
$db['paypal']['stricton'] = FALSE;



$db['ashrams']['hostname'] = 'localhost';
$db['ashrams']['username'] = 'ekam_user';
$db['ashrams']['password'] = 'XL9MvPvhNK8qD';
$db['ashrams']['database'] = 'ashrams';
$db['ashrams']['dbdriver'] = 'mysqli';
$db['ashrams']['dbprefix'] = '';
$db['ashrams']['pconnect'] = TRUE;
$db['ashrams']['db_debug'] = TRUE;
$db['ashrams']['cache_on'] = FALSE;
$db['ashrams']['cachedir'] = '';
$db['ashrams']['char_set'] = 'utf8';
$db['ashrams']['dbcollat'] = 'utf8_general_ci';
$db['ashrams']['swap_pre'] = '';
$db['ashrams']['autoinit'] = TRUE;
$db['ashrams']['stricton'] = FALSE;



$db['log']['hostname'] = 'localhost';
$db['log']['username'] = 'ekam_user';
$db['log']['password'] = 'XL9MvPvhNK8qD';
$db['log']['database'] = 'log';
$db['log']['dbdriver'] = 'mysqli';
$db['log']['dbprefix'] = '';
$db['log']['pconnect'] = TRUE;
$db['log']['db_debug'] = TRUE;
$db['log']['cache_on'] = FALSE;
$db['log']['cachedir'] = '';
$db['log']['char_set'] = 'utf8';
$db['log']['dbcollat'] = 'utf8_general_ci';
$db['log']['swap_pre'] = '';
$db['log']['autoinit'] = TRUE;
$db['log']['stricton'] = FALSE;



$db['purchase']['hostname'] = 'localhost';
$db['purchase']['username'] = 'ekam_user';
$db['purchase']['password'] = 'XL9MvPvhNK8qD';
$db['purchase']['database'] = 'purchase';
$db['purchase']['dbdriver'] = 'mysqli';
$db['purchase']['dbprefix'] = '';
$db['purchase']['pconnect'] = TRUE;
$db['purchase']['db_debug'] = TRUE;
$db['purchase']['cache_on'] = FALSE;
$db['purchase']['cachedir'] = '';
$db['purchase']['char_set'] = 'utf8';
$db['purchase']['dbcollat'] = 'utf8_general_ci';
$db['purchase']['swap_pre'] = '';
$db['purchase']['autoinit'] = TRUE;
$db['purchase']['stricton'] = FALSE;



$db['locktown']['hostname'] = 'localhost';
$db['locktown']['username'] = 'ekam_user';
$db['locktown']['password'] = 'XL9MvPvhNK8qD';
$db['locktown']['database'] = 'locktown';
$db['locktown']['dbdriver'] = 'mysqli';
$db['locktown']['dbprefix'] = '';
$db['locktown']['pconnect'] = TRUE;
$db['locktown']['db_debug'] = TRUE;
$db['locktown']['cache_on'] = FALSE;
$db['locktown']['cachedir'] = '';
$db['locktown']['char_set'] = 'utf8';
$db['locktown']['dbcollat'] = 'utf8_general_ci';
$db['locktown']['swap_pre'] = '';
$db['locktown']['autoinit'] = TRUE;
$db['locktown']['stricton'] = FALSE;



$db['lms']['hostname'] = 'localhost';
$db['lms']['username'] = 'ekam_user';
$db['lms']['password'] = 'XL9MvPvhNK8qD';
$db['lms']['database'] = 'lms';
$db['lms']['dbdriver'] = 'mysqli';
$db['lms']['dbprefix'] = '';
$db['lms']['pconnect'] = TRUE;
$db['lms']['db_debug'] = TRUE;
$db['lms']['cache_on'] = FALSE;
$db['lms']['cachedir'] = '';
$db['lms']['char_set'] = 'utf8';
$db['lms']['dbcollat'] = 'utf8_general_ci';
$db['lms']['swap_pre'] = '';
$db['lms']['autoinit'] = TRUE;
$db['lms']['stricton'] = FALSE;


 

/* End of file database.php */
/* Location: ./application/config/database.php */
