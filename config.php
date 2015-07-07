<?php 
/*
* 2014-2015 Maghaza
* Configuration file v1.0
* 
*/

$GLOBALS['config'] = array(
	"database" 	=> array(
		"driver" 		=> "mysql",
		"host" 		=> "_serveur",
		"user" 		=> "_rootBase",
		"password" 	=> "_passBase",
		"db" 		=> "_nomBase",
		"charset" 	=> "utf8"
	),										
	'remember' 	=> array(
		'cookie_name' 	=> 'hash',
		'cookie_expiry'	=> 	604800 //per second that means 7 days
		), //how long do we want to remember the user if he checked remember me
	'remember_client' 	=> array(
		'cookie_name' 	=> 'hash_client',
		'cookie_expiry'	=> 	604800 //per second that means 7 days
		),
	'session' 	=> array(
		'session_name'	=> 'user',
		'token_name'	=> 'token'
		),
	'session_user' 	=> array(
		'session_user_name'	=> 'client',
		'token_user_name'	=> 'token'
		) 
);

/**
* Config class v1.0
*/
class Config
{	
	public static function get($path = null) {
		if($path){
			$config = $GLOBALS['config'];
			$path = explode('/', $path);

			foreach ($path as $bit) {
				if(isset($config[$bit]));
				$config = $config[$bit];
			}

			return $config;
		}

		return false;
	}
}

?>