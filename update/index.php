<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			Файл обновления
*/

//-----------------------------------------------
// Обработка ошибок
//-----------------------------------------------

error_reporting( E_ERROR | E_WARNING | E_PARSE );
set_magic_quotes_runtime(0);

//-----------------------------------------------
// Загузка движка
//-----------------------------------------------

require_once( "../classes/class_engine.php" );
$engine = new engine;

//-----------------------------------------------
// Проверяем версию PHP
//-----------------------------------------------
		
preg_match( "#(\d\.\d\.\d)$#", PHP_VERSION, $phpver );

if( strcmp( $phpver[1], "5.1.3" ) < 0 )
{
	$engine->fatal_error( "This system requires PHP 5.1.3 or over. Your PHP version is {$phpver[1]}." );
}

//-----------------------------------------------
// Загузка БД
//-----------------------------------------------

if( !file_exists( "../db_engine.conf.php" ) )
{
	make_db_conf_file();
}

require_once( "../db_engine.conf.php" );

$engine->load_module( "class", "db_".DB_ENGINE, FALSE );

$engine->DB =& $engine->classes[ "db_".DB_ENGINE ];

//-----------------------------------------------
// Загрузка необходимых классов
//-----------------------------------------------

$engine->load_module( "class", "input"	, FALSE );
$engine->load_module( "class", "session", FALSE );
$engine->load_module( "class", "output"	, FALSE );

//-----------------------------------------------
// Загрузка класса обновления
//-----------------------------------------------

$update = new update;
$update->engine =& $engine;

$engine->sections['update'] =& $update;

$engine->input['install_update']	= TRUE;
$engine->input['tab']				= "update";
$engine->input['step']				= $engine->input['step'] ? $engine->input['step'] : 0;

$update->__class_construct();

//-----------------------------------------------
// Загрузка указанного шага установки
//-----------------------------------------------

if( $engine->input['login'] )
{
	$engine->classes['session']->authorize_manual();
}

if( $engine->classes['session']->session['confirmed'] === TRUE )
{
	if( $engine->member['user_admin'] ) switch( $engine->input['step'] )
	{
		case 1:
			$engine->input['execute'] ? $update->do_step_1() : $update->step_1();
			break;
		
		case 2:
			$engine->input['execute'] ? $update->do_step_2() : $update->step_2();
			break;
			
		case 3:
			$engine->input['execute'] ? $update->do_step_3() : $update->step_3();
			break;
			
		default:
			$update->step_0();
			break;
	}
	else 
	{
		$engine->fatal_error( "Only users with admin rigths can run the update." );
	}
}
else
{
	$engine->input['tab'] = "auth";
	
	$engine->load_module( "section", "auth", FALSE );
	
	$engine->sections['auth']->get_auth_form();
}

$engine->classes['output']->do_output();

//-----------------------------------------------
// Обновление
//-----------------------------------------------

class update
{
	/**
	* HTML код для вывода на экран
	*
	* @var string
	*/

	var $html 			= "";
	
	/**
	* Заголовок и описание страницы
	*
	* @var array
	*/

	var $page_info 		= array(	'title'	=> "",
									'desc'	=> ""
									);
									
	/**
	* Системное сообщение
	*
	* @var array
	*/

	var $message		= array(	'text'	=> "",
									'type'	=> "green"
									);
	
	/**
	* Конструктор класса (не страндартный PHP)
	* 
	* Загрузка языковых строк.
	* 
	* @return	void
	*/
	
	function __class_construct()
	{
		//-----------------------------------------------
		// Загружаем языковые строки
		//-----------------------------------------------
		
		$lang = $this->engine->input['lang'] ? $this->engine->input['lang'] : "en";
		
		$this->engine->load_lang( '../../update/update_'.$lang );
	}
	
	/**
    * Шаг 0. Экран приветствия
    * 
    * Выводим приветственное сообщение и форму
    * выбора языка.
    *
    * @return	void
    */
	
	function step_0()
	{
		//-----------------------------------------------
		// Заголовок страницы
		//-----------------------------------------------
		
		$this->page_info['title']	= $this->engine->lang['step_0_title'];
		$this->page_info['desc']	= $this->engine->lang['step_0_desc'];
		
		//-----------------------------------------------
		// Получаем информацию о доступных языках
		//-----------------------------------------------
		
		if( $dir = opendir( './' ) ) while( FALSE !== ( $file = readdir( $dir ) ) )
		{
			if( preg_match( "#^update_([a-z]{2})\.lng$#", $file, $match ) ) $languages_list[ $match[1] ] = $this->engine->lang['lang_'.$match[1] ];
		}
		
		closedir( $dir );
		
		//-----------------------------------------------
		// Выводим форму
		//-----------------------------------------------
		
		$this->html .= $this->engine->classes['output']->form_start( array(	'step'		=> 1,
																			)	);
																			
		$this->engine->classes['output']->table_add_header( ""	, "40%" );
		$this->engine->classes['output']->table_add_header( ""	, "60%" );
		
		$this->html .= $this->engine->classes['output']->table_start( $this->engine->lang['step_0'] );
		
		$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['update_select_lang']										, "row1" ),
								array(	$this->engine->skin['global']->form_dropdown( "lang", $languages_list, 'ru' )	, "row2" ),
								)	);
								
		$this->html .= $this->engine->classes['output']->table_add_submit( $this->engine->lang['next'] );
		
		$this->html .= $this->engine->classes['output']->table_end();
		
		$this->html .= $this->engine->classes['output']->form_end();
	}
									
	/**
    * Шаг 1. Лицензионное соглашение.
    * 
    * Выводим текст лицензионного соглашения.
    *
    * @return	void
    */
	
	function step_1()
	{
		//-----------------------------------------------
		// Заголовок страницы
		//-----------------------------------------------
		
		$this->page_info['title']	= $this->engine->lang['step_1_title'];
		$this->page_info['desc']	= $this->engine->lang['step_1_desc'];
		
		//-----------------------------------------------
		// Выводим форму
		//-----------------------------------------------
		
		$this->html = $this->engine->classes['output']->form_start( array(	'step'		=> 1,
																			'lang'		=> $this->engine->input['lang'],
																			'execute'	=> 'yes',
																			) );
																			
		$this->engine->classes['output']->table_add_header( ""	, "40%" );
		$this->engine->classes['output']->table_add_header( ""	, "60%" );
		
		$this->html .= $this->engine->classes['output']->table_start( $this->engine->lang['step_1'] );
		
		$this->html .= $this->engine->classes['output']->table_add_row_single_cell(
								"<div style='max-height:300px;overflow:auto;'>\n".$this->engine->lang['eula_text']."</div>\n"
								, "row2"  );
		
		$this->html .= $this->engine->classes['output']->table_add_row_single_cell( 
								$this->engine->skin['global']->form_radio( "agree", 1, 0, $this->engine->lang['eula_agree'] )."<br/>".
								$this->engine->skin['global']->form_radio( "agree", 0, 1, $this->engine->lang['eula_disagree'] )
								, "row1" );
								
		$this->html .= $this->engine->classes['output']->table_add_submit( $this->engine->lang['next'] );
		
		$this->html .= $this->engine->classes['output']->table_end();
		
		$this->html .= $this->engine->classes['output']->form_end();
	}
	
	/**
    * Выполнение шага 1.
    * 
    * Окончание установки в случае нежелания принимать
    * условия лицензионного соглашения.
    *
    * @return	void
    */
	
	function do_step_1()
	{
		//-----------------------------------------------
		// Переходим к следующему шагу
		//-----------------------------------------------
		
		if( $this->engine->input['agree'] )
		{
			$this->step_2();
			
			return;
		}
		
		//-----------------------------------------------
		// Говорим 'Спасибо' :D
		//-----------------------------------------------
		
		$this->page_info['title']	= $this->engine->lang['thank_you_title'];
		$this->page_info['desc']	= $this->engine->lang['thank_you_desc'];
	}
	
	/**
    * Шаг 2. Обновление системы.
    * 
    * Выводим сообщение о готовности к обновлению.
    *
    * @return	void
    */
	
	function step_2()
	{
		//-----------------------------------------------
		// Заголовок страницы
		//-----------------------------------------------
		
		$this->page_info['title']	= $this->engine->lang['step_2_title'];
		$this->page_info['desc']	= $this->engine->lang['step_2_desc'];
		
		//-----------------------------------------------
		// Получаем информацию о текущей версии
		//-----------------------------------------------
		
		$setting = $this->engine->DB->simple_exec_query( array(	'select'	=> 'setting_options',
																'from'		=> 'settings_list',
																'where'		=> "setting_key='save_path'",
																)	);
																
		preg_match( "#(\d+\.\d+\.\d+)( ([abr])(\d+))?#", $setting['setting_options'], $match );
		
		$version = $match[1] ? $match[1] : ( $setting['setting_options'] == "1.0.0 (beta 2)" ? "1.0.0 (beta 2)" : "1.0.0 (beta 1)" );
		
		if( $match[3] )
		{
			$version .= " (".( $match[3] == "a" ? "alpha" : $match[3] == "b" ? "beta" : "Release Candidate" )." ".$match[4].")";
		}
		
		//-----------------------------------------------
		// Выводим форму
		//-----------------------------------------------
		
		$this->html = $this->engine->classes['output']->form_start( array(	'step'		=> 2,
																			'lang'		=> $this->engine->input['lang'],
																			'execute'	=> 'yes',
																			) );
																			
		$this->engine->classes['output']->table_add_header( ""	, "40%" );
		$this->engine->classes['output']->table_add_header( ""	, "60%" );
		
		$this->html .= $this->engine->classes['output']->table_start( $this->engine->lang['step_2'] );
		
		$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['version_current']			, "row1" ),
								array(	$version										, "row2" ),
								)	);
								
		$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['version_new']				, "row1" ),
								array(	$this->engine->config['__engine__']['version']	, "row2" ),
								)	);
								
		$this->html .= $this->engine->classes['output']->table_add_submit( $this->engine->lang['next'] );
		
		$this->html .= $this->engine->classes['output']->table_end();
		
		$this->html .= $this->engine->classes['output']->form_end();
	}
	
	/**
    * Выполнение шага 2.
    * 
    * Выполнение скриптов обновления и SQL запросов для
    * обновления структуры и содержимого БД.
    *
    * @return	void
    */
	
	function do_step_2()
	{
		//-----------------------------------------------
		// Получаем идентификатор установленной версии
		//-----------------------------------------------
		
		$setting = $this->engine->DB->simple_exec_query( array(	'select'	=> 'setting_options',
																'from'		=> 'settings_list',
																'where'		=> "setting_key='save_path'",
																)	);
																
		preg_match( "#uid(\d+)#", $setting['setting_options'], $uid );
		
		//-----------------------------------------------
		// Выполняем скрипты обновления
		//-----------------------------------------------
		
		if( $dir = opendir( './' ) ) while( FALSE !== ( $subdir = readdir( $dir ) ) )
		{
			if( is_dir( './'.$subdir ) and preg_match( "#^\d+\.\d+$#", $subdir ) and is_dir( './'.$subdir.'/php' ) )
			{
				$sdir = opendir( './'.$subdir.'/php' );
				
				while( FALSE !== ( $file = readdir( $sdir ) ) )
				{
					if( !preg_match( "#^((\d+) \(\d+\.\d+\.\d+( (alpha|beta|rc) \d+)?\)\.php)$#i", $file, $match ) or intval( $match[2] ) <= $uid[1] ) continue;
					
					include_once( './'.$subdir.'/php/'.$file );
					
					$class_name = "update_script_".$match[2];
					
					$update_scripts[ $match[2] ] = new $class_name;
					$update_scripts[ $match[2] ]->engine = &$this->engine;
					
					if( method_exists( $update_scripts[ $match[2] ], "before_db_update" ) ) $update_scripts[ $match[2] ]->before_db_update();
					
					if( method_exists( $update_scripts[ $match[2] ], "after_db_update" ) ) $after_db_update[] = $match[2];
				}
				
				closedir( $sdir );
			}
		}
		
		closedir( $dir );
		
		//-----------------------------------------------
		// Выполняем запросы в БД
		//-----------------------------------------------
		
		$db_engine = DB_ENGINE == "mysql" ? "mysql" : "sqlite";
		
		if( $dir = opendir( './' ) ) while( FALSE !== ( $subdir = readdir( $dir ) ) )
		{
			if( is_dir( './'.$subdir ) and preg_match( "#^\d+\.\d+$#", $subdir ) and is_dir( './'.$subdir.'/'.$db_engine ) )
			{
				$sdir = opendir( './'.$subdir.'/'.$db_engine );
				
				while( FALSE !== ( $file = readdir( $sdir ) ) )
				{
					if( !preg_match( "#^((\d+) \(\d+\.\d+\.\d+( (alpha|beta|rc) \d+)?\)\.sql)$#i", $file, $match ) or intval( $match[2] ) <= $uid[1] ) continue;
					
					if( ( $strings = file( './'.$subdir.'/'.$db_engine.'/'.$match[1] ) ) !== FALSE ) foreach( $strings as $string )
					{
						if( $string != "\n" and strpos( $string, "--" ) !== 0 )
						{
							if( preg_match( "#;\s*$#", $string ) )
							{
								$query .= preg_replace( "#;\s*$#", "", $string );
								
								$this->engine->DB->query_exec( $query );
								
								$query = "";
							}
							else 
							{
								$query .= $string;
							}
						}
					}
				}
				
				closedir( $sdir );
			}
		}
		
		closedir( $dir );
		
		//-----------------------------------------------
		// Выполняем скрипты обновления еще раз
		//-----------------------------------------------
		
		if( is_array( $after_db_update ) ) foreach( $after_db_update as $id )
		{
			$update_scripts[ $id ]->after_db_update();
		}
		
		//-----------------------------------------------
		// Переходим к следующему шагу
		//-----------------------------------------------
		
		$this->step_3();
	}
	
	/**
    * Шаг 3. Завершение обновления.
    * 
    * Выводим сообщение об успешном завершении обновления.
    *
    * @return	void
    */
	
	function step_3()
	{
		//-----------------------------------------------
		// Удаление блокирующих файлов
		//-----------------------------------------------
		
		@unlink( dirname( __FILE__ )."/../update.lock" );
		@unlink( dirname( __FILE__ )."/../task.lock" );
		@unlink( dirname( __FILE__ )."/../cron.lock" );
		
		//-----------------------------------------------
		// Заголовок страницы
		//-----------------------------------------------
		
		$this->page_info['title']	= $this->engine->lang['step_3_title'];
		$this->page_info['desc']	= str_replace( "<#BASE_URL#>", str_replace( "update/", "", $this->engine->base_url ), $this->engine->lang['step_3_desc'] );
	}
}

//-----------------------------------------------
// Создание файла с информацией о движке БД
//-----------------------------------------------

function make_db_conf_file()
{
	global $engine;
	
	if( file_exists( $engine->home_dir."database.sqlite" ) )
	{
		$db_engine = "sqlite2";
	}
	else if( file_exists( $engine->home_dir."database.s3db" ) )
	{
		$db_engine = "sqlite3";
	}
	else if( file_exists( $engine->home_dir."database.conf.php" ) )
	{
		$db_engine = "mysql";
	}
	else 
	{
		$engine->fatal_error( "Can not load proper database engine. Please reinstall the system." );
	}
	
	//-----------------------------------------------
	// Обновляем идентификатор движка
	//-----------------------------------------------
		
	$file = fopen( $engine->home_dir."db_engine.conf.php", "w" );
		
	fputs( $file, "<?php define( 'DB_ENGINE', '{$db_engine}' ); ?>" );
	
	fclose( $file );
}

?>