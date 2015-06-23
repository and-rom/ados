<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			Файл конвертера
*/

set_time_limit( 90 );

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
	$engine->fatal_error( "File 'db_engine.conf.php' was not found in system root directory. Can not continue convertion process." );
}

require_once( "../db_engine.conf.php" );

if( DB_ENGINE != 'sqlite2' )
{
	$engine->fatal_error( "The system doesn't use SQLite 2 as main DB engine. No convertion is needed." );
}

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

$converter = new converter;
$converter->engine =& $engine;

$engine->sections['converter'] =& $converter;

$engine->input['install_update']	= TRUE;
$engine->input['tab']				= "converter";
$engine->input['step']				= $engine->input['step'] ? $engine->input['step'] : 0;

$converter->__class_construct();

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
			$engine->input['execute'] ? $converter->do_step_1() : $converter->step_1();
			break;
			
		default:
			$converter->step_0();
			break;
	}
	else 
	{
		$engine->fatal_error( "Only users with admin rigths can perform the convertion." );
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
// Выполнение
//-----------------------------------------------

class converter
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
	* Эагрузка языковых строк.
	* 
	* @return	void
	*/
	
	function __class_construct()
	{
		//-----------------------------------------------
		// Загружаем языковые строки
		//-----------------------------------------------
		
		$lang = $this->engine->input['lang'] ? $this->engine->input['lang'] : "en";
		
		$this->engine->load_lang( '../../converter/converter_'.$lang );
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
			if( preg_match( "#^converter_([a-z]{2})\.lng$#", $file, $match ) ) $languages_list[ $match[1] ] = $this->engine->lang['lang_'.$match[1] ];
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
								array(	$this->engine->lang['converter_select_lang']									, "row1" ),
								array(	$this->engine->skin['global']->form_dropdown( "lang", $languages_list, 'ru' )	, "row2" ),
								)	);
								
		$this->html .= $this->engine->classes['output']->table_add_submit( $this->engine->lang['next'] );
		
		$this->html .= $this->engine->classes['output']->table_end();
		
		$this->html .= $this->engine->classes['output']->form_end();
	}
									
	/**
    * Шаг 1. Конвертирование БД.
    * 
    * Выводим сообщение о готовности к конвертации.
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
		
		$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['delete_current']						, "row1" ),
								array(	$this->engine->skin['global']->form_yes_no( "delete", 0 )	, "row2" ),
								)	);
								
		$this->html .= $this->engine->classes['output']->table_add_submit( $this->engine->lang['next'], "onclick='this.disabled=true;'" );
		
		$this->html .= $this->engine->classes['output']->table_end();
		
		$this->html .= $this->engine->classes['output']->form_end();
	}
	
	/**
    * Выполнение шага 1.
    * 
    * Проверка наличия необходимых модулей PHP для
    * работы с БД SQLite 3 и выполнение конвертации.
    *
    * @return	void
    */
	
	function do_step_1()
	{
		//-----------------------------------------------
		// Получаем идентификатор установленной версии
		//-----------------------------------------------
		
		$setting = $this->engine->DB->simple_exec_query( array(	'select'	=> 'setting_options',
																'from'		=> 'settings_list',
																'where'		=> "setting_key='save_path'",
																)	);
																
		preg_match( "#uid(\d+)#", $setting['setting_options'], $uid );
		
		if( $uid[1] < 25 )
		{
			$this->message = array(	'text'	=> $this->engine->lang['wrong_system_version'],
									'type'	=> 'red',
									);
										
			$this->step_1();
			
			exit( $this->engine->classes['output']->do_output() );
		}
		
		//-----------------------------------------------
		// Проверяем наличие модулей для SQLite 3
		//-----------------------------------------------
		
		$extensions = get_loaded_extensions();
		
		if( !in_array( "pdo_sqlite", $extensions ) or !in_array( "PDO", $extensions ) )
		{
			$this->message = array(	'text'	=> $this->engine->lang['no_sqlite3_support'],
									'type'	=> 'red',
									);
			
			$this->step_1();
			
			exit( $this->engine->classes['output']->do_output() );
		}

		//-----------------------------------------------
		// Подключаем класс для работы с SQLite 3
		//-----------------------------------------------
		
		$this->engine->load_module( "class", "db_sqlite3", FALSE );
		
		$this->engine->DB3 =& $this->engine->classes["db_sqlite3"];
		
		//-----------------------------------------------
		// Создаем блокирующий файл
		//-----------------------------------------------
		
		$file = fopen( $this->engine->home_dir."update.lock", "w" );
		
		fclose( $file );
		
		//-----------------------------------------------
		// Создаем новую БД и формируем структуру
		//-----------------------------------------------
		
		if( ( $strings = file( './queries_sqlite_converter.sql' ) ) !== FALSE ) foreach( $strings as $string )
		{
			if( $string != "\n" and strpos( $string, "--" ) !== 0 )
			{
				if( preg_match( "#;\s*$#", $string ) )
				{
					$query .= preg_replace( "#;\s*$#", "", $string );
					
					$this->engine->DB3->query_exec( $query );
					
					$query = "";
				}
				else 
				{
					$query .= $string;
				}
			}
		}
		
		//-----------------------------------------------
		// Конвертируем таблицы
		//-----------------------------------------------
		
		$tables = array(	'categories_files',
							'categories_list',
							'domains_list',
							'languages',
							'modules_list',
							'modules_settings',
							'modules_versions',
							'schedule_events',
							'schedule_time',
							'settings_groups',
							'settings_list',
							'system_cache',
							'system_log',
							'users_list',
							);
							
		foreach( $tables as $table )
		{
			$this->engine->DB->simple_construct( array(	'select'	=> '*',
												 		'from'		=> $table,
												 		)	);
			$this->engine->DB->simple_exec();
			
			while( $row = $this->engine->DB->fetch_row() )
			{
				foreach( $row as $name => $value ) if( strpos( $cell, "b64_" ) === 0 )
				{
					$row[ $name ] = base64_decode( substr( $value, 4 ) );
				}
				
				$this->engine->DB3->do_insert( $table, $row );
			}
		}
		
		//-----------------------------------------------
		// Обновляем идентификатор движка БД
		//-----------------------------------------------
		
		$file = fopen( $this->engine->home_dir."db_engine.conf.php", "w" );
		
		fputs( $file, "<?php define( 'DB_ENGINE', 'sqlite3' ); ?>" );
		
		fclose( $file );
		
		//-----------------------------------------------
		// Удаляем файл SQLite 2
		//-----------------------------------------------
		
		if( $this->engine->input['delete'] and !@unlink( $this->engine->home_dir."/database.sqlite" ) )
		{
			$this->message = array(	'text'	=> $this->engine->lang['cant_unlink_file'],
									'type'	=> 'orange',
									);
		}
		
		//-----------------------------------------------
		// Переходим к следующему шагу
		//-----------------------------------------------
		
		$this->step_2();
	}
	
	/**
    * Шаг 2. Завершение конвертации.
    * 
    * Выводим сообщение об успешном завершении конвертации.
    *
    * @return	void
    */
	
	function step_2()
	{
		//-----------------------------------------------
		// Удаление блокирующих файлов
		//-----------------------------------------------
		
		@unlink( $this->engine->home_dir."update.lock" );
		@unlink( $this->engine->home_dir."task.lock" );
		@unlink( $this->engine->home_dir."cron.lock" );
		
		//-----------------------------------------------
		// Заголовок страницы
		//-----------------------------------------------
		
		$this->page_info['title']	= $this->engine->lang['step_2_title'];
		$this->page_info['desc']	= str_replace( "<#BASE_URL#>", str_replace( "converter/", "", $this->engine->base_url ), $this->engine->lang['step_2_desc'] );
	}
}

?>