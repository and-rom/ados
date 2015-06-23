<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			Индексный файл
*/

//-----------------------------------------------
// Предопределенные переменные и обработка ошибок
//-----------------------------------------------

define( 'PARSE_PNG'		, TRUE );
define( 'TMP_COOKIE'	, FALSE );
define( 'TMP_DOMAIN'	, "my.router" );
define( 'TMP_PATH'		, "/" );

if( !defined( "CRONTAB" ) and !file_exists( "db_engine.conf.php" ) )
{
	$dir = file_exists( "install.lock" ) ? "update" : "install";
	
	@header( "Location: ".str_replace( "index.php", "", $engine->base_url )."{$dir}/" );
	
	exit();
}

require_once( "db_engine.conf.php" );

error_reporting( E_ERROR | E_WARNING | E_PARSE );
set_magic_quotes_runtime(0);

//-----------------------------------------------
// Загузка движка
//-----------------------------------------------

require_once( "classes/class_engine.php" );
$engine = new engine;

//-----------------------------------------------
// Загрузка БД
//-----------------------------------------------

switch( DB_ENGINE )
{
	case 'sqlite2':	$db_file = "database.sqlite";
	break;
	
	case 'sqlite3':	$db_file = "database.s3db";
	break;
	
	case 'mysql': $db_file = "database.conf.php";
	break;
}

if( file_exists( $engine->home_dir.$db_file ) and file_exists( $engine->home_dir."install.lock" ) )
{
	$engine->load_module( "class", 'db_'.DB_ENGINE, FALSE );

	$engine->DB =& $engine->classes[ 'db_'.DB_ENGINE ];
}
else 
{
	@header( "Location: ".str_replace( "index.php", "", $engine->base_url )."install/" );
	
	exit();
}

//-----------------------------------------------
// Загрузка необходимых классов
//-----------------------------------------------

$engine->load_module( "class", "input"	, FALSE );
$engine->load_module( "class", "session", FALSE );
$engine->load_module( "class", "output"	, FALSE );

//-----------------------------------------------
// Загружаем настройки
//-----------------------------------------------
		
$engine->get_settings();

//-----------------------------------------------
// Проверяем, не требуется ли обновление
//-----------------------------------------------

if( !$engine->config['__current__']['numeric'][0] or
	 $engine->config['__current__']['numeric'][0] < $engine->config['__engine__']['numeric'][0] or 
	 $engine->config['__current__']['numeric'][1] < $engine->config['__engine__']['numeric'][1] or
	 $engine->config['__current__']['numeric'][2] < $engine->config['__engine__']['numeric'][2] or 
	($engine->config['__current__']['numeric'][3] and !$engine->config['__engine__']['numeric'][3] ) or 
	 strcasecmp( $engine->config['__current__']['numeric'][3], $engine->config['__engine__']['numeric'][3] ) < 0 or 
	 $engine->config['__current__']['numeric'][4] < $engine->config['__engine__']['numeric'][4] )
{
	$update_lock = dirname( __FILE__ )."/update.lock";
		
	if( !file_exists( $update_lock ) )
	{
		$file = fopen( $update_lock, "w" );
			
		fclose( $file );
	}
	
	@header( "Location: ".str_replace( "index.php", "", $engine->base_url )."update/" );
	
	exit();
}

//-----------------------------------------------
// Загружаем список языков
//-----------------------------------------------
		
$engine->load_system_languages();

//-----------------------------------------------
// Выполняем CRON задание
//-----------------------------------------------

if( defined( "CRONTAB" ) and CRONTAB === TRUE )
{
	$engine->load_module( "class", "cron", FALSE );
	
	exit();
}

//-----------------------------------------------
// Проверяем авторизацию
//-----------------------------------------------

if( $engine->input['login'] )
{
	$engine->classes['session']->authorize_manual();
	
	if( $engine->classes['session']->session['confirmed'] === TRUE )
	{
		@header( "Location: {$engine->base_url}" );
		
		exit();
	}
}

$engine->load_lang( 'global' );

if( $engine->classes['session']->session['confirmed'] !== TRUE )
{
	$engine->input['tab'] = 'auth';
	
	$engine->load_module( "section", "auth", FALSE );
	
	$engine->sections['auth']->get_auth_form();
	
	$engine->classes['output']->do_output();
	
	exit();
}

//-----------------------------------------------
// Глобальные переменные
//-----------------------------------------------

$engine->tabs = array(	'download'		=> 'all',
						'categories'	=> 'all',
						'schedule'		=> 'all',
						'log'			=> 'all',
						'settings'		=> 'admin',
						'users'			=> 'admin',
						'modules'		=> 'admin',
						);

//-----------------------------------------------
// Загрузка секции
//-----------------------------------------------

$engine->base_url .= "?";

if( !$engine->input['tab'] or !array_key_exists( $engine->input['tab'], $engine->tabs ) or ( !$engine->member['user_admin'] and $engine->tabs[ $engine->input['tab'] ] == 'admin' ) )
{
	$engine->input['tab'] = 'download';
}

$engine->load_module( "section", $engine->input['tab'], FALSE );
$engine->classes['output']->do_output();

?>