<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			Файл установки
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
// Загрузка необходимых классов
//-----------------------------------------------

$engine->load_module( "class", "input"	, FALSE );
$engine->load_module( "class", "output"	, FALSE );

//-----------------------------------------------
// Загружка установочного класса
//-----------------------------------------------

$install = new install;
$install->engine =& $engine;

$engine->sections['install'] =& $install;

$engine->input['install_update']	= TRUE;
$engine->input['tab']				= "install";
$engine->input['step']				= $engine->input['step'] ? $engine->input['step'] : 0;

$install->__class_construct();

//-----------------------------------------------
// Загружка указанного шага установки
//-----------------------------------------------

switch( $engine->input['step'] )
{
	case 1:
		$engine->input['execute'] ? $install->do_step_1() : $install->step_1();
		break;
	
	case 2:
		$engine->input['execute'] ? $install->do_step_2() : $install->step_2();
		break;
		
	case 3:
		$engine->input['execute'] ? $install->do_step_3() : $install->step_3();
		break;
		
	case 4:
		$engine->input['execute'] ? $install->do_step_4() : $install->step_4();
		break;
		
	case 5:
		$engine->input['execute'] ? $install->do_step_5() : $install->step_5();
		break;
		
	default:
		$install->step_0();
		break;
}

$engine->classes['output']->do_output();

//-----------------------------------------------
// Установка
//-----------------------------------------------

class install
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
	* Проверка наличия модуля SQLite и загрузка
	* языковых строк.
	* 
	* @return	void
	*/
	
	function __class_construct()
	{
		//-----------------------------------------------
		// Загружаем языковые строки
		//-----------------------------------------------
		
		$lang = $this->engine->input['lang'] ? $this->engine->input['lang'] : "en";
		
		$this->engine->load_lang( '../../install/install_'.$lang );
		
		//-----------------------------------------------
		// Проверяем версию PHP
		//-----------------------------------------------
		
		preg_match( "#(\d\.\d\.\d)$#", PHP_VERSION, $phpver );
		
		if( strcmp( $phpver[1], "5.1.3" ) < 0 )
		{
			$this->engine->fatal_error( "This system requires PHP 5.1.3 or over. Your PHP version is {$phpver[1]}." );
		}
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
			if( preg_match( "#^install_([a-z]{2})\.lng$#", $file, $match ) ) $languages_list[ $match[1] ] = $this->engine->lang['lang_'.$match[1] ];
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
								array(	$this->engine->lang['install_select_lang']										, "row1" ),
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
    * Шаг 2. Выбор БД.
    * 
    * Выводим форму для выбора движка базы данных, с
    * которой будет работать система.
    *
    * @return	void
    */
	
	function step_2()
	{
		if( !$this->engine->input['db_host'] ) $this->engine->input['db_host'] = "localhost";
		
		$selected['sqlite2'] = $this->engine->input['db_engine'] == "sqlite2" ? 1 : 0;
		$selected['sqlite3'] = $this->engine->input['db_engine'] == "sqlite3" ? 1 : 0;
		$selected['mysql']   = $this->engine->input['db_engine'] == "mysql" ? 1 : 0;
		
		if( !$this->engine->input['db_engine'] )
		{
			$selected['sqlite3'] = 1;
		}
		
		$disabled = $this->engine->input['db_engine'] == "mysql" ? "" : "disabled='disabled'";
		
		//-----------------------------------------------
		// JavaScript
		//-----------------------------------------------
		
		$this->html = "<script type='text/javascript' src='install.js'></script>\n";
		
		//-----------------------------------------------
		// Заголовок страницы
		//-----------------------------------------------
		
		$this->page_info['title']	= $this->engine->lang['step_2_title'];
		$this->page_info['desc']	= $this->engine->lang['step_2_desc'];
		
		//-----------------------------------------------
		// Выводим форму
		//-----------------------------------------------
		
		$this->html .= $this->engine->classes['output']->form_start( array(	'step'		=> 2,
																			'lang'		=> $this->engine->input['lang'],
																			'execute'	=> 'yes',
																			), "id='db_form'" );
																			
		$this->engine->classes['output']->table_add_header( ""	, "40%" );
		$this->engine->classes['output']->table_add_header( ""	, "60%" );
		
		$this->html .= $this->engine->classes['output']->table_start( $this->engine->lang['step_2'] );
		
		$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['db_engine']																																			, "row1" ),
								array(	$this->engine->skin['global']->form_radio( "db_engine", "sqlite2", $selected['sqlite2'], $this->engine->lang['db_sqlite2'], "onclick='toggle_params(this,false);'" )."<br/>\n".
										$this->engine->skin['global']->form_radio( "db_engine", "sqlite3", $selected['sqlite3'], $this->engine->lang['db_sqlite3'], "onclick='toggle_params(this,false);'" )."<br/>\n".
										$this->engine->skin['global']->form_radio( "db_engine", "mysql" , $selected['mysql'], $this->engine->lang['db_mysql'], "onclick='toggle_params(this,true);'" )				, "row2" ),
								)	);
								
		$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['db_mysql_host']																																		, "row1" ),
								array(	$this->engine->skin['global']->form_text( "db_host", $this->engine->input['db_host'], "large", "text", $disabled." tabindex='1'" )											, "row2" ),
								)	);
								
		$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['db_mysql_database']																																	, "row1" ),
								array(	$this->engine->skin['global']->form_text( "db_database", $this->engine->input['db_database'], "large", "text", $disabled." tabindex='2'" )									, "row2" ),
								)	);
								
		$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['db_mysql_user']																																		, "row1" ),
								array(	$this->engine->skin['global']->form_text( "db_user", $this->engine->input['db_user'], "large", "text", $disabled." tabindex='3'" )											, "row2" ),
								)	);
								
		$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['db_mysql_pass']																																		, "row1" ),
								array(	$this->engine->skin['global']->form_text( "db_pass", $this->engine->input['db_pass'], "large", "password", $disabled." tabindex='4'" )										, "row2" ),
								)	);
								
		$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['db_mysql_pass_confirm']																																, "row1" ),
								array(	$this->engine->skin['global']->form_text( "db_pass_confirm", $this->engine->input['db_pass_confirm'], "large", "password", $disabled." tabindex='5'" )						, "row2" ),
								)	);
								
		$this->html .= $this->engine->classes['output']->table_add_submit( $this->engine->lang['next'] );
		
		$this->html .= $this->engine->classes['output']->table_end();
		
		$this->html .= $this->engine->classes['output']->form_end();
	}
	
	/**
    * Выполнение шага 2.
    * 
    * Проверяет переданные настройки и возможность доступа
    * к БД MySQL, если она выбрана в качестве используемой БД.
    * В противном случае пытается создать файл БД SQLite.
    *
    * @return	void
    */
	
	function do_step_2()
	{
		//-----------------------------------------------
		// Обновляем идентификатор движка
		//-----------------------------------------------
		
		$file = fopen( $this->engine->home_dir."db_engine.conf.php", "w" );
		
		fputs( $file, "<?php define( 'DB_ENGINE', '{$this->engine->input['db_engine']}' ); ?>" );
		
		fclose( $file );
		
		//-----------------------------------------------
		// Пытаемся создать файл БД SQLite 2
		//-----------------------------------------------
		
		if( $this->engine->input['db_engine'] == "sqlite2" )
		{
			if( !in_array( "SQLite", $this->engine->php_ext ) or !in_array( "PDO", $this->engine->php_ext ) )
			{
				$this->message = array(	'text'	=> $this->engine->lang['no_sqlite2_support'],
										'type'	=> 'red',
										);
										
				$this->step_2();
			}
			
			$db_file = $this->engine->home_dir."database.sqlite";
			
			if( file_exists( $db_file ) and !@rename( $db_file, $db_file.".bak" ) and !@unlink( $db_file ) )
			{
				$this->message = array(	'text'	=> $this->engine->lang['cant_delete_db2_file'],
										'type'	=> 'red',
										);
																	
				$this->step_2();
			}
			else if( sqlite_open( $this->engine->home_dir."database.sqlite", 0666, $error ) === FALSE )
			{
				$this->message = array(	'text'	=> str_replace( "<#ERROR#>", $error, $this->engine->lang['cant_create_db'] ),
										'type'	=> 'red',
										);
																	
				$this->step_2();
			}
			else 
			{
				$this->step_3();
			}
			
			exit( $this->engine->classes['output']->do_output() );
		}
		
		//-----------------------------------------------
		// Пытаемся создать файл БД SQLite 3
		//-----------------------------------------------
		
		else if( $this->engine->input['db_engine'] == "sqlite3" )
		{
			if( !in_array( "pdo_sqlite", $this->engine->php_ext ) or !in_array( "PDO", $this->engine->php_ext ) )
			{
				$this->message = array(	'text'	=> $this->engine->lang['no_sqlite3_support'],
										'type'	=> 'red',
										);
										
				$this->step_2();
			}
			
			$db_file = $this->engine->home_dir."database.s3db";
			
			if( file_exists( $db_file ) and !@rename( $db_file, $db_file.".bak" ) and !@unlink( $db_file ) )
			{
				$this->message = array(	'text'	=> $this->engine->lang['cant_delete_db3_file'],
										'type'	=> 'red',
										);
																	
				$this->step_2();
			}
			else try
			{
				$db = new PDO( "sqlite:".$this->engine->home_dir."database.s3db" );
				
				$this->step_3();
			}
			catch( PDOException $e )
			{
				$this->message = array(	'text'	=> str_replace( "<#ERROR#>", $e->getMessage(), $this->engine->lang['cant_create_db'] ),
										'type'	=> 'red',
										);
																	
				$this->step_2();
			}
			
			exit( $this->engine->classes['output']->do_output() );
		}
		
		//-----------------------------------------------
		// Проверяем переданные параметры
		//-----------------------------------------------
		
		if( !$this->engine->input['db_host'] )
		{
			$errors[] = $this->engine->lang['no_db_host'];
		}
		
		if( !$this->engine->input['db_database'] )
		{
			$errors[] = $this->engine->lang['no_db_database'];
		}
		
		if( !$this->engine->input['db_user'] )
		{
			$errors[] = $this->engine->lang['no_db_user'];
		}
		
		if( $this->engine->input['db_pass'] != $this->engine->input['db_pass_confirm'] )
		{
			$errors[] = $this->engine->lang['no_db_confirm'];
		}
		
		//-----------------------------------------------
		// Выводим список ошибок
		//-----------------------------------------------
		
		if( is_array( $errors ) )
		{
			$this->message = array(	'text'	=> implode( "<br/>\n", $errors ),
														'type'	=> 'red',
														);
																
			$this->step_2();
			
			exit( $this->engine->classes['output']->do_output() );
		}
		
		//-----------------------------------------------
		// Создаем файл с настройками
		//-----------------------------------------------
		
		$file = fopen( $this->engine->home_dir."database.conf.php", "w" );
		
		fputs( $file, "<?php\n\n" );
		fputs( $file, "/**\n" );
		fputs( $file, "* @package		ADOS - Automatic Downloading System\n" );
		fputs( $file, "* @version		1.3.9 (build 74)\n\n" );
		fputs( $file, "* @author		DINI\n" );
		fputs( $file, "* @copyright	2007—2008\n\n" );
		fputs( $file, "* @name			{$this->engine->lang['db_params']}\n" );
		fputs( $file, "*/\n\n" );
		fputs( $file, "\$params = array(\n\n" );
		fputs( $file, "# {$this->engine->lang['db_mysql_host']}\n\n" );
		fputs( $file, "'host'		=> '{$this->engine->input['db_host']}',\n\n" );
		fputs( $file, "# {$this->engine->lang['db_mysql_database']}\n\n" );
		fputs( $file, "'database'	=> '{$this->engine->input['db_database']}',\n\n" );
		fputs( $file, "# {$this->engine->lang['db_mysql_user']}\n\n" );
		fputs( $file, "'user'		=> '{$this->engine->input['db_user']}',\n\n" );
		fputs( $file, "# {$this->engine->lang['db_mysql_pass']}\n\n" );
		fputs( $file, "'pass'		=> '{$this->engine->input['db_pass']}',\n\n" );
		fputs( $file, ")\n\n" );
		fputs( $file, "?>" );
		
		fclose( $file );
		
		//-----------------------------------------------
		// Пытаемся соединиться с БД
		//-----------------------------------------------
		
		if( $this->engine->load_module( "class", "db_mysql" ) === FALSE )
		{
			$this->message = array(	'text'	=> $this->engine->lang['cant_connect_db'],
									'type'	=> 'red',
									);
																
			$this->step_2();
			
			exit( $this->engine->classes['output']->do_output() );
		}
		
		$this->engine->classes['db_mysql']->close_db();
		
		//-----------------------------------------------
		// Переходим к следующему шагу
		//-----------------------------------------------
		
		$this->step_3();
	}
	
	/**
    * Шаг 3. Установка системы.
    * 
    * Выводим форму для настройки параметров пользователя, выбора
    * языков и модулей системы.
    *
    * @return	void
    */
	
	function step_3()
	{
		if( !$this->engine->input['save_path'] ) $this->engine->input['save_path'] = "/opt/ados";
		if( !$this->engine->input['cron_path'] ) $this->engine->input['cron_path'] = "/opt/etc/cron.1min";
		if( !$this->engine->input['php_path']  ) $this->engine->input['php_path']  = "/opt/bin/php";
		
		//-----------------------------------------------
		// Заголовок страницы
		//-----------------------------------------------
		
		$this->page_info['title']	= $this->engine->lang['step_3_title'];
		$this->page_info['desc']	= $this->engine->lang['step_3_desc_'.$this->engine->input['db_engine'] ]."<br/>".$this->engine->lang['step_3_desc'];
		
		//-----------------------------------------------
		// Получаем информацию о доступных языках
		//-----------------------------------------------
		
		$selected = array();
		
		if( $dir = opendir( '../languages/' ) ) while( FALSE !== ( $file = readdir( $dir ) ) )
		{
			if( preg_match( "#^([a-z]{2})$#", $file, $match ) ) $languages_list[ $match[1] ] = $this->engine->lang['lang_'.$match[1] ];
			
			$selected[] = $match[1];
		}
		
		closedir( $dir );
		
		//-----------------------------------------------
		// Выводим форму
		//-----------------------------------------------
		
		$this->html = $this->engine->classes['output']->form_start( array(	'step'		=> 3,
																			'lang'		=> $this->engine->input['lang'],
																			'execute'	=> 'yes',
																			'db_engine'	=> $this->engine->input['db_engine'],
																			) );
																			
		$this->engine->classes['output']->table_add_header( ""	, "40%" );
		$this->engine->classes['output']->table_add_header( ""	, "60%" );
		
		$this->engine->input['default_lang'] = $this->engine->input['default_lang'] ? $this->engine->input['default_lang'] : 'ru';
		
		$this->html .= $this->engine->classes['output']->table_start( $this->engine->lang['step_2'] );
		
		$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['user_login']																												, "row1" ),
								array(	$this->engine->skin['global']->form_text( "login", $this->engine->input['login'], "large", "text", "tabindex='1'" )								, "row2" ),
								)	);
								
		$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['user_password']																											, "row1" ),
								array(	$this->engine->skin['global']->form_text( "password", "", "large", "password", "tabindex='2'"  )												, "row2" ),
								)	);
								
		$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['user_confirm']																												, "row1" ),
								array(	$this->engine->skin['global']->form_text( "confirm", "", "large", "password", "tabindex='3'"  )													, "row2" ),
								)	);
								
		$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['save_path']																												, "row1" ),
								array(	$this->engine->skin['global']->form_text( "save_path", $this->engine->input['save_path'], "large", "text", "tabindex='4'"  )					, "row2" ),
								)	);
								
		$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['cron_path']																												, "row1" ),
								array(	$this->engine->skin['global']->form_text( "cron_path", $this->engine->input['cron_path'], "large", "text", "tabindex='5'"  )					, "row2" ),
								)	);
								
		$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['php_path']																													, "row1" ),
								array(	$this->engine->skin['global']->form_text( "php_path", $this->engine->input['php_path'], "large", "text", "tabindex='6'" )						, "row2" ),
								)	);
								
		$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['cookie_domain']																											, "row1" ),
								array(	$this->engine->skin['global']->form_text( "domain", $this->engine->input['domain'], "large", "text", "tabindex='7'"  )							, "row2" ),
								)	);
								
		$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['cookie_path']																												, "row1" ),
								array(	$this->engine->skin['global']->form_text( "path", $this->engine->input['path'], "large", "text", "tabindex='8'"  )								, "row2" ),
								)	);
								
		$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['system_languages']																											, "row1" ),
								array(	$this->engine->skin['global']->form_multiselect( "sys_lang", $languages_list, $selected, "large", "tabindex='9'" )								, "row2" ),
								)	);
								
		$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['default_language']																											, "row1" ),
								array(	$this->engine->skin['global']->form_dropdown( "default_lang", $languages_list, $this->engine->input['default_lang'], "large", "tabindex='10'" )	, "row2" ),
								)	);
								
		$this->html .= $this->engine->classes['output']->table_add_submit( $this->engine->lang['next'] );
		
		$this->html .= $this->engine->classes['output']->table_end();
		
		$this->html .= $this->engine->classes['output']->form_end();
	}
	
	/**
    * Выполнение шага 3.
    * 
    * Добавление в БД необходимых таблиц, обработка
    * переданной формы.
    * 
    * В случае возникновения ошибок возвращаемся
    * ко второму шагу и выводим их описание.
    * Если все нормально, то идем дальше.
    *
    * @return	void
    */
	
	function do_step_3()
	{
		//-----------------------------------------------
		// Проверяем переданные параметры
		//-----------------------------------------------
		
		if( !$this->engine->input['login'] )
		{
			$errors[] = $this->engine->lang['no_user_login'];
		}
		else if( !preg_match( "#^[a-zA-Z][a-zA-Z0-9_]{3,}$#", $this->engine->input['login'] ) )
		{
			$errors[] = $this->engine->lang['wrong_user_login'];
		}
		
		if( !$this->engine->input['password'] )
		{
			$errors[] = $this->engine->lang['no_user_password'];
		}
		else if( !preg_match( "#^\w{6,}$#", $this->engine->input['password'] ) )
		{
			$errors[] = $this->engine->lang['wrong_user_password'];
		}
		
		if( !$this->engine->input['confirm'] )
		{
			$errors[] = $this->engine->lang['no_user_confirm'];
		}
		else if( $this->engine->input['password'] != $this->engine->input['confirm'] )
		{
			$errors[] = $this->engine->lang['wrong_user_confirm'];
		}
		
		if( !is_dir( $this->engine->input['save_path'] ) or !is_writable( $this->engine->input['save_path'] ) )
		{
			$errors[] = $this->engine->lang['wrong_save_path'];
		}
		else 
		{
			$this->engine->input['save_path'] = preg_replace( "#/+$#", "", $this->engine->input['save_path'] );
		}
		
		if( !is_dir( $this->engine->input['cron_path'] ) or !is_writable( $this->engine->input['cron_path'] ) )
		{
			$errors[] = $this->engine->lang['wrong_cron_path'];
		}
		else 
		{
			$this->engine->input['cron_path'] = preg_replace( "#/+$#", "", $this->engine->input['cron_path'] );
		}
		
		if( !is_file( $this->engine->input['php_path'] ) or !is_executable( $this->engine->input['php_path'] ) )
		{
			$errors[] = $this->engine->lang['wrong_php_path'];
		}
		else 
		{
			$this->engine->input['php_path'] = preg_replace( "#/+$#", "", $this->engine->input['php_path'] );
		}
		
		if( !$this->engine->input['sys_lang'] or !is_array( $this->engine->input['sys_lang'] ) )
		{
			$errors[] = $this->engine->lang['no_sys_lang'];
		}
		else if( !in_array( $this->engine->input['default_lang'], $this->engine->input['sys_lang'] ) )
		{
			$errors[] = $this->engine->lang['wrong_default_lang'];
		}
		
		//-----------------------------------------------
		// Выводим список ошибок
		//-----------------------------------------------
		
		if( is_array( $errors ) )
		{
			$this->message = array(	'text'	=> implode( "<br/>\n", $errors ),
														'type'	=> 'red',
														);
																
			$this->step_3();
			
			exit( $this->engine->classes['output']->do_output() );
		}
		
		//-----------------------------------------------
		// Если мы здесь, то инициализируем соединение
		// с БД и создаем таблицы
		//-----------------------------------------------
		
		$db_engine = $this->engine->input['db_engine'] == "mysql" ? "mysql" : "sqlite";
		
		$this->engine->load_module( "class", "db_{$this->engine->input['db_engine']}", FALSE );

		$this->engine->DB =& $this->engine->classes[ "db_{$this->engine->input['db_engine']}" ];
		
		if( ( $strings = file( "queries_{$db_engine}_install.sql" ) ) === FALSE )
		{
			$this->message = array(	'text'	=> str_replace( "<#DB_ENGINE#>", $db_engine, $this->engine->lang['cant_read_queries'] ),
									'type'	=> 'red',
									);
																
			$this->step_3();
			
			exit( $this->engine->classes['output']->do_output() );
		}
		
		foreach( $strings as $string )
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
		
		//-----------------------------------------------
		// Добавляем информацию о пользователе
		//-----------------------------------------------
		
		$user = array(	"user_name"			=> $this->engine->input['login'],
						"user_pass"			=> md5( sha1( $this->engine->input['password'] ) ),
						"user_admin"		=> 1,
						"user_lang"			=> $this->engine->input['lang'],
						"user_max_amount"	=> -1,
						);
		
		$this->engine->DB->do_insert( "users_list", $user );
		
		//-----------------------------------------------
		// Создаем директории
		//-----------------------------------------------
		
		$user_name = strtolower( preg_replace( "#\W#", "_", $this->engine->input['login'] ) );
		
		$this->engine->DB->do_update( "categories_list", array( "cat_path" => $this->engine->input['save_path']."/{$user_name}/programs" ), "cat_id=1" );
		$this->engine->DB->do_update( "categories_list", array( "cat_path" => $this->engine->input['save_path']."/{$user_name}/images" ), "cat_id=2" );
		$this->engine->DB->do_update( "categories_list", array( "cat_path" => $this->engine->input['save_path']."/{$user_name}/music" ), "cat_id=3" );
		$this->engine->DB->do_update( "categories_list", array( "cat_path" => $this->engine->input['save_path']."/{$user_name}/video" ), "cat_id=4" );
		$this->engine->DB->do_update( "categories_list", array( "cat_path" => $this->engine->input['save_path']."/{$user_name}/archives" ), "cat_id=5" );
		
		$save_path = strpos( $this->engine->input['save_path'], "/" ) === 0 ? $this->engine->input['save_path'] : $this->engine->home_dir."/".$this->engine->input['save_path'];
		
		if( !is_dir( $save_path ) ) 							mkdir( $save_path );
		if( !is_dir( "{$save_path}/_tmp" ) ) 					mkdir( "{$save_path}/_tmp" );
		if( !is_dir( "{$save_path}/_log" ) ) 					mkdir( "{$save_path}/_log" );
		if( !is_dir( "{$save_path}/_all" ) ) 					mkdir( "{$save_path}/_all" );
		if( !is_dir( "{$save_path}/{$user_name}" ) ) 			mkdir( "{$save_path}/{$user_name}" );
		if( !is_dir( "{$save_path}/{$user_name}/programs" ) ) 	mkdir( "{$save_path}/{$user_name}/programs" );
		if( !is_dir( "{$save_path}/{$user_name}/images" ) ) 	mkdir( "{$save_path}/{$user_name}/images" );
		if( !is_dir( "{$save_path}/{$user_name}/music" ) ) 		mkdir( "{$save_path}/{$user_name}/music" );
		if( !is_dir( "{$save_path}/{$user_name}/video" ) ) 		mkdir( "{$save_path}/{$user_name}/video" );
		if( !is_dir( "{$save_path}/{$user_name}/archives" ) ) 	mkdir( "{$save_path}/{$user_name}/archives" );
		
		//-----------------------------------------------
		// Обновляем настройки
		//-----------------------------------------------
		
		if( !$this->engine->input['save_path'] != "/opt/ados" ) $this->engine->DB->do_update( "settings_list", array( "setting_value" => $this->engine->input['save_path'] ), "setting_key='save_path'" );
		if( !$this->engine->input['cron_path'] != "/opt/etc/cron.1min" ) $this->engine->DB->do_update( "settings_list", array( "setting_value" => $this->engine->input['cron_path'] ), "setting_key='cron_path'" );
		if( !$this->engine->input['php_path']  != "/opt/bin/php" ) $this->engine->DB->do_update( "settings_list", array( "setting_value" => $this->engine->input['php_path'] ), "setting_key='php_path'" );
		
		//-----------------------------------------------
		// Сохраняем указанные настройки cookie
		//-----------------------------------------------
		
		$this->engine->DB->do_update( "settings_list", array( "setting_value" => $this->engine->input['domain'] ), "setting_key='cookie_domain'" );
		$this->engine->DB->do_update( "settings_list", array( "setting_value" => $this->engine->input['path'] ), "setting_key='cookie_path'" );
		
		//-----------------------------------------------
		// Подключаем языки
		//-----------------------------------------------
		
		foreach( $this->engine->input['sys_lang'] as $key )
		{
			$strings = file( "../languages/{$key}/global.lng" );
			
			$authors = array(	'names'	=> array(),
								'links'	=> array(),
								);
								
			foreach( $strings as $str )
			{
				if( preg_match( "#^\s*\*\s*@(translator|tr_link|tr_email)\s+(.+)$#i", $str, $values ) )
				{
					switch( $values[1] )
					{
						case 'translator': $authors['names'][] = $values[2];
						break;
						
						case 'tr_link': $authors['links'][] = $values[2];
						break;
						
						case 'tr_email': $authors['links'][] = "mailto:".$values[2];
						break;
					}
				}
			}
			
			$lang = array(	'lang_key'		=> $key,
							'lang_name'		=> $this->engine->lang['lang_'.$key ],
							'lang_default'	=> $key == $this->engine->input['default_lang'] ? 1 : 0,
							'lang_authors'	=> count( $authors['names'] ) ? serialize( $authors['names'] ) : "",
							'lang_links'	=> count( $authors['links'] ) ? serialize( $authors['links'] ) : "",
							);
			
			$this->engine->DB->do_insert( "languages", $lang );
		}
		
		//-----------------------------------------------
		// Создаем файл в CRON папке
		//-----------------------------------------------
		
		$file = fopen( $this->engine->input['cron_path']."/"."ados.sh", "w" );
		
		fputs( $file, "#!/bin/sh\n", 1024 );
		fputs( $file, "echo >> {$this->engine->home_dir}cron.lock\n", 1024 );
		fputs( $file, "{$this->engine->input['php_path']} {$this->engine->home_dir}classes/class_cron.php >> {$this->engine->input['save_path']}/_log/cron_schedule.log", 1024 );
			
		fclose( $file );
		
		chmod( $this->engine->input['cron_path']."/"."ados.sh", 0755 );
		
		//-----------------------------------------------
		// Переходим к следующему шагу
		//-----------------------------------------------
		
		$this->step_4();
	}
	
	/**
    * Шаг 4. Установка модулей.
    * 
    * Выводим форму для выбора и настройки модулей.
    *
    * @return	void
    */
	
	function step_4()
	{
		//-----------------------------------------------
		// Заголовок страницы
		//-----------------------------------------------
		
		$this->page_info['title']	= $this->engine->lang['step_4_title'];
		$this->page_info['desc']	= $this->engine->lang['step_4_desc'];
		
		//-----------------------------------------------
		// Получаем информацию о доступных языках
		//-----------------------------------------------
		
		if( $dir = opendir( './' ) ) while( FALSE !== ( $file = readdir( $dir ) ) )
		{
			if( preg_match( "#^ados_module_(\w+)_([0-9\.]+)_?([a-zA-Z]+)?_?(\d+)?\.tar\.gz$#i", $file, $match ) )
	        {
	        	$modules[ $match[1] ] = ucfirst( str_replace( "_", " ", $match[1] ) )." ".$match[2];
	        	
	        	if( $match[3] ) $modules[ $match[1] ] .= " ".$match[3];
	        	if( $match[4] ) $modules[ $match[1] ] .= " ".$match[4];
	        }
		}
		
		closedir( $dir );
		
		if( !is_array( $modules ) )
		{
			$this->message = array(	'text'	=> $this->engine->lang['no_modules'] ,
									'type'	=> 'red',
									);
			
			exit( $this->engine->classes['output']->do_output() );
		}
		
		//-----------------------------------------------
		// Выводим форму
		//-----------------------------------------------
		
		$this->html = $this->engine->classes['output']->form_start( array(	'step'		=> 4,
																			'lang'		=> $this->engine->input['lang'],
																			'execute'	=> 'yes',
																			'db_engine'	=> $this->engine->input['db_engine'],
																			) );
																			
		$this->engine->classes['output']->table_add_header( $this->engine->lang['module_name']		, "40%" );
		$this->engine->classes['output']->table_add_header( $this->engine->lang['module_path']		, "30%" );
		$this->engine->classes['output']->table_add_header( $this->engine->lang['module_enable']	, "15%" );
		$this->engine->classes['output']->table_add_header( $this->engine->lang['module_default']	, "15%" );
		
		$this->html .= $this->engine->classes['output']->table_start( $this->engine->lang['step_3'] );
		
		$i = 1;
		
		$selected = array_key_exists( "curl", $modules ) ? "curl" : ( array_key_exists( "wget", $modules ) ? "wget" : NULL );
		
		foreach( $modules as $key => $name )
		{
			$selected = ( $selected === NULL or $selected == $key ) ? 1 : 0;
			
			$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$name.$this->engine->skin['global']->form_hidden( "module_{$key}_name", $name )																							, "row1" ),
								array(	$this->engine->skin['global']->form_text( "module_{$key}_path", $this->engine->input["module_{$key}_path"], "medium", "text", "style='width:200px;' tabindex='{$i}'" )	, "row2", "style='text-align:center'" ),
								array(	$this->engine->skin['global']->form_checkbox( "module_{$key}_enable", 1 )																								, "row2", "style='text-align:center'" ),
								array(	$this->engine->skin['global']->form_radio( "module_default", $key, $selected )																							, "row2", "style='text-align:center'" ),
								)	);
								
			$i++;
		}
								
		$this->html .= $this->engine->classes['output']->table_add_submit( $this->engine->lang['next'] );
		
		$this->html .= $this->engine->classes['output']->table_end();
		
		$this->html .= $this->engine->classes['output']->form_end();
	}
	
	/**
    * Выполнение шага 4.
    * 
    * Добавление в БД информации о модулях.
    * 
    * Проверяет правильность указанной информации,
    * считывает настройки из архивов с выбранными
    * модулями и копирует оттуда файлы.
    *
    * @return	void
    */
	
	function do_step_4()
	{
		//-----------------------------------------------
		// Проверяем переданные параметры
		//-----------------------------------------------
		
		foreach( $this->engine->input as $key => $value )
		{
			if( preg_match( "#module_(\w+)_enable#", $key, $match ) and $value )
			{
				$modules[ $match[1] ]['key']  = $match[1];
				$modules[ $match[1] ]['name'] = $this->engine->input['module_'.$match[1].'_name'];
				$modules[ $match[1] ]['file'] = "ados_module_".strtolower( str_replace( " ", "_", $this->engine->input['module_'.$match[1].'_name'] ) ).".tar.gz";
				
				if( $this->engine->input['module_'.$match[1].'_path'] )
				{
					$modules[ $match[1] ]['path'] = $this->engine->input['module_'.$match[1].'_path'];
				}
				else 
				{
					$errors[] = str_replace( "<#MODULE#>", $modules[ $match[1] ]['name'], $this->engine->lang['no_module_path'] );
				}
				
				if( $this->engine->input['module_default'] == $match[1] )
				{
					$modules[ $match[1] ]['default'] = $got_it = TRUE;
				}
			}
		}
		
		if( !is_array( $modules ) )
		{
			$errors[] = $this->engine->lang['no_modules_selected'];
		}
		else if( !$got_it )
		{
			$errors[] = $this->engine->lang['no_default_module'];
		}
		
		//-----------------------------------------------
		// Выводим список ошибок
		//-----------------------------------------------
		
		if( is_array( $errors ) )
		{
			$this->message = array(	'text'	=> implode( "<br/>\n", $errors ),
									'type'	=> 'red',
									);
																
			$this->step_4();
			
			exit( $this->engine->classes['output']->do_output() );
		}
		
		//-----------------------------------------------
		// Создаем временную директорию
		//-----------------------------------------------
			
		mkdir( $tmp_dir = $this->engine->home_dir."install/temp" );
		
		//-----------------------------------------------
		// Первый этап проверки: извлечение файлов
		//-----------------------------------------------
		
		foreach( $modules as $module )
		{
			//-----------------------------------------------
			// Загружаем TAR класс
			//-----------------------------------------------
			
			$this->engine->load_module( "class", "tar", FALSE, array( $this->engine->home_dir."install/".$module['file'] ) );
		
			//-----------------------------------------------
			// Извлекаем файлы из архива во временную
			// директорию модуля
			//-----------------------------------------------
			
			if( $this->engine->classes['tar']->extract( $tmp_dir."/".$module['key'] ) === FALSE )
			{
				$error = $this->engine->classes['tar']->error;
				break;
			}
			
			//-----------------------------------------------
			// Выгружаем TAR класс
			//-----------------------------------------------
			
			$this->engine->classes['tar'] = NULL;
		}
		
		//-----------------------------------------------
		// Выводим ошибку
		//-----------------------------------------------
		
		if( $error )
		{
			$this->engine->remove_dir( $tmp_dir );
			
			$this->message = array(	'text'	=> $error,
									'type'	=> 'red',
									);
																
			$this->step_4();
			
			exit( $this->engine->classes['output']->do_output() );
		}
		
		//-----------------------------------------------
		// Второй этап проверки: наличие файлов и настроек
		//-----------------------------------------------
		
		foreach( $modules as $mid => $module )
		{
			$module_dir = $tmp_dir."/".$module['key']."/";
			
			//-----------------------------------------------
			// Проверям файл настроек
			//-----------------------------------------------
				
			if( !is_readable( $module_dir."module_settings.xml" ) )
			{
				$error = str_replace( "<#MODULE#>", $module['name'], $this->engine->lang['cant_read_settings_file'] );
				break;
			}
				
			//-----------------------------------------------
			// Читаем XML с настройками
			//-----------------------------------------------
				
			if( ( $xml = simplexml_load_file( $module_dir."module_settings.xml" ) ) === FALSE )
			{
				$error = str_replace( "<#MODULE#>", $module['name'], $this->engine->lang['cant_read_module_settings'] );
				break;
			}
				
			//-----------------------------------------------
			// Получаем и информацию о модуле
			//-----------------------------------------------
				
			$values = array( 'key', 'name', 'author', 'url', 'version', 'engine_author', 'engine_url', 'engine_version_support' );
				
			foreach( $xml->module->children() as $name => $content )
			{
				if( in_array( $name, $values ) ) $modules[ $mid ]['info']["module_{$name}"] = $content;
			}
				
			if( !$modules[ $mid ]['info']['module_name'] or !$modules[ $mid ]['info']['module_key'] )
			{
				$error = str_replace( "<#MODULE#>", $module['name'], $this->engine->lang['cant_read_module_info'] );
				break;
			}
				
			//-----------------------------------------------
			// Проверяем файл модуля
			//-----------------------------------------------
				
			if( !is_file( $module_dir."modules/module_{$module['key']}.php" ) )
			{
				$error = str_replace( "<#MODULE#>", $module['name'], $this->engine->lang['cant_find_module_file'] );
				break;
			}
			
			//-----------------------------------------------
			// Поочередно обрабатываем настройки
			//-----------------------------------------------
				
			foreach( $xml->settings->children() as $element )
			{
				$values = array();
						
				//-----------------------------------------------
				// Обрабатываем и сохраняем (обновляем) настройку
				//-----------------------------------------------
					
				foreach( $element->children() as $type => $content )
				{
					$values[ $type ] = $content;
				}
					
				if( $values['key'] )
				{
					$modules[ $mid ]['settings'][] = array(	'setting_key'		=> $values['key'],
															'setting_default'	=> $values['key'] == 'engine_path' ? $module['path'] : $values['default'],
															'setting_type'		=> $values['type'],
															'setting_position'	=> $values['position'],
															'setting_actions'	=> $values['action'],
															);

				}
			}
		}
		
		//-----------------------------------------------
		// Выводим ошибку
		//-----------------------------------------------
		
		if( $error )
		{
			$this->engine->remove_dir( $tmp_dir );
			
			$this->message = array(	'text'	=> $error,
									'type'	=> 'red',
									);
																
			$this->step_4();
			
			exit( $this->engine->classes['output']->do_output() );
		}
		
		//-----------------------------------------------
		// Инициализируем соединение с БД
		//-----------------------------------------------
		
		$this->engine->load_module( "class", "db_{$this->engine->input['db_engine']}", FALSE );

		$this->engine->DB =& $this->engine->classes[ "db_{$this->engine->input['db_engine']}" ];
		
		//-----------------------------------------------
		// Подгружаем класс управления модулями
		//-----------------------------------------------
		
		$this->engine->load_module( "class", "downloader", FALSE );
		
		//-----------------------------------------------
		// Третий этап проверки: наличие исполняемых файлов
		//-----------------------------------------------

		foreach( $modules as $mid => $module )
		{
			$module_dir = $tmp_dir."/".$module['key']."/";
				
			//-----------------------------------------------
			// Копируем файлы из поддиректории lang
			//-----------------------------------------------
				
			$patterns = array(	'files'	=> array( "#^module_{$module['key']}\.lng$#" ),
								'dirs'	=> array( "#^[a-z]{2}$#i" ),
								);
				
			$this->engine->copy_dir( $module_dir."languages", $this->engine->home_dir."languages", 0755, FALSE, $patterns );
				
			//-----------------------------------------------
			// Копируем файлы из поддиректории modules
			//-----------------------------------------------
				
			if( is_file( $module_dir."modules/module_{$module['key']}.php" ) )
			{
				copy( $module_dir."modules/module_{$module['key']}.php", $this->engine->home_dir."modules/module_{$module['key']}.php" );
					
				if( is_dir( $module_dir."modules/{$module['key']}" ) )
				{
					$this->engine->copy_dir( $module_dir."modules/{$module['key']}", $this->engine->home_dir."modules/{$module['key']}", 0755 );
				}
			}
			
			//-----------------------------------------------
			// Добавляем в БД информацию о модуле
			//-----------------------------------------------
			
			$module['info']['module_default'] = $module['default'] ? 1 : 0;
			$module['info']['module_enabled'] = 1;
			
			$this->engine->DB->do_insert( "modules_list", $module['info'] );
			
			$module['id'] = $this->engine->DB->get_insert_id();
			
			//-----------------------------------------------
			// Добавляем в БД настройки модуля
			//-----------------------------------------------
			
			foreach( $module['settings'] as $setting )
			{
				$setting['setting_module'] = $module['id'];
				
				$this->engine->DB->do_insert( "modules_settings", $setting );
			}
			
			//-----------------------------------------------
			// Проверяем исполняемый файл модуля
			//-----------------------------------------------
			
			if( $this->engine->classes['downloader']->module_exists( $module['key'] ) === FALSE )
			{
				$error = str_replace( "<#MODULE#>", $module['name'], $this->engine->lang['cant_find_module_exe'] );
				break;
			}
		}
	    	
	    //-----------------------------------------------
		// Выводим ошибку
		//-----------------------------------------------
		
		if( $error )
		{
			$this->engine->remove_dir( $tmp_dir );
			$this->engine->remove_dir( $this->engine->home_dir."modules", TRUE );
			
			if( $dir = opendir( $this->engine->home_dir."languages" ) ) while( FALSE !== ( $file = readdir( $dir ) ) )
			{
				if( preg_match( "#^[a-z]{2}$#i", $file ) ) $dirs[] = $file;
			}
			
			closedir( $dir );
			
			foreach( $dirs as $path )
			{
				if( $dir = opendir( $this->engine->home_dir."languages/".$path ) ) while( FALSE !== ( $file = readdir( $dir ) ) )
				{
					if( preg_match( "#^module_(\w+)\.lng$#i", $file ) ) @unlink( $this->engine->home_dir."languages/{$path}/{$file}" );
				}
				
				closedir( $dir );
			}
			
			$this->engine->DB->do_delete( "modules_list", 1 );
			$this->engine->DB->do_delete( "modules_settings", 1 );
			
			$this->message = array(	'text'	=> $error,
									'type'	=> 'red',
									);
																
			$this->step_4();
			
			exit( $this->engine->classes['output']->do_output() );
		}
		
		$this->engine->remove_dir( $tmp_dir );
		
		//-----------------------------------------------
		// Создаем блокирующий файл
		//-----------------------------------------------
		
		$lock = fopen( $this->engine->home_dir."install.lock", "w" );
		
		fclose( $lock );
		
		//-----------------------------------------------
		// Заканчиваем установку
		//-----------------------------------------------
		
		$this->step_5();
	}
	
	/**
    * Шаг 5. Завершение установки.
    * 
    * Выводим сообщение об успешном завершении установки.
    *
    * @return	void
    */
	
	function step_5()
	{
		//-----------------------------------------------
		// Удаление блокирующих файлов
		//-----------------------------------------------
		
		@unlink( dirname( __FILE__ )."/../task.lock" );
		@unlink( dirname( __FILE__ )."/../cron.lock" );
		
		//-----------------------------------------------
		// Заголовок страницы
		//-----------------------------------------------
		
		$this->page_info['title']	= $this->engine->lang['step_5_title'];
		$this->page_info['desc']	= str_replace( "<#BASE_URL#>", str_replace( "install/", "", $this->engine->base_url ), $this->engine->lang['step_5_desc'] );
	}
}

?>