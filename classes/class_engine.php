<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			Движок скрипта
*/

/**
* Класс, содержащий глобальные функции
*
* @author   DINI
* @version	1.3.9 (build 74)
*/

class engine
{
	/**
	* Массив с переменными конфигурации
	*
	* @var array
	*/

	var $config			= array();
	
	/**
	* Массив с кэшируемой информацией
	*
	* @var array
	*/

	var $cache			= array();

	/**
	* Входные данные
	*
	* @var array
	*/

	var $input			= array();

	/**
	* Массив с классами
	*
	* @var array
	*/

	var $classes		= array();
	
	/**
	* Массив с секциями
	*
	* @var array
	*/

	var $sections		= array();
	
	/**
	* Массив с событиями
	*
	* @var array
	*/

	var $events			= array();
	
	/**
	* Массив со списком доступных библиотек
	*
	* @var array
	*/

	var $php_ext		= array();
	
	/**
	* Массив с языками
	*
	* @var array
	*/

	var $languages		= array(	'list'		=> array(),
									'default'	=> "",
	);


	/**
	* Время работы скрипта и БД
	*
	* @var array
	*/

	var $debug			= array(	'script'	=> 0.00000,
									'database'	=> array( 'sum' => 0.00000, 'current' => 0.00000 ),
	);

	/**
	* Типы строк дат
	*
	* @var array
	*/

	var $time_options	= array(	'TINY'		=> 'H:i',				// 16:46
									'SHORT'   	=> 'd-m-Y',				// 28-04-2007
									'MEDIUM' 	=> 'j F Y',				// 28 Апреля 2007
									'LONG'		=> 'd.m.Y H:i',			// 28.04.2007 16:46
									'FULL'   	=> 'l, j F Y - H:i',	// Суббота, 28 Апреля 2007 - 16:46
									'MICRO'		=> 'j F',				// 28 Апреля
	);

	/**
	* Массив с языковыми строками
	*
	* @var array
	*/

	var $lang			= array();

	/**
	* Объект скина
	*
	* @var object
	*/

	var $skin			= NULL;

	/**
	* Адрес домашней страницы
	*
	* @var string
	*/

	var $base_url		= "";
	
	/**
	* Домашняя директория
	*
	* @var string
	*/

	var $home_dir		= "./";

	/**
	* Значение PHP-директивы magic_quotes_gpc
	*
	* @var bool
	*/

	var $get_magic_quotes 	= FALSE;
	
	/**
	* Время последней проверки наличия обновлений
	*
	* @var int
	*/

	var $update_last_check 	= array( 'date'		=> 0,
									 'result'	=> ''
									 );

	/*-------------------------------------------------------------------------*/
	// Основные функции
	/*-------------------------------------------------------------------------*/

	/**
	* Инициализация текущего класса
	* 
	* Сохраняет значение директивы magic_quotes_gpc и
	* загружает основные настройки.
	* В случае, если настройки не могут быть прочитаны,
	* выводит сообщение об ошибке.
	*
	* @return	void
	*/

	function engine()
	{
		$this->get_magic_quotes = get_magic_quotes_gpc();
		
		$this->start_timer( 'script' );
		
		//------------------------------------------
		// Сведения о движке
		//------------------------------------------
		
		$this->config['__engine__'] = array(	'version'	=> "1.3.9",
												'numeric'	=> array( 1, 3, 9, '', 0 ),
												'build'		=> 74,
												'name'		=> "ADOS",
												'full_name'	=> "Automatic Downloading System",
												'author'	=> "DINI",
												'copyright'	=> "© 2007—2008",
												'url'		=> "http://dini.su"
												);
												
		//------------------------------------------
		// Рабочая директория
		//------------------------------------------
												
		$this->home_dir  = dirname( __FILE__ );
		$this->home_dir  = dirname( $this->home_dir."../" );
		$this->home_dir .= "/";
		
		//------------------------------------------
		// Список доступных библиотек PHP
		//------------------------------------------
		
		$this->php_ext = get_loaded_extensions();
		
		//------------------------------------------
		// Ссылка на главную страницу
		//------------------------------------------
		
		if( $_POST['login'] and is_numeric( $_POST['use_port'] ) )
		{			
			$_SERVER['SERVER_PORT'] = intval( $_POST['use_port'] );
			
			$this->my_setcookie( "use_port", $_SERVER['SERVER_PORT'] );
		}
		else if( $use_port = $_COOKIE['use_port'] )
		{
			$_SERVER['SERVER_PORT'] = intval( $use_port );
		}
		
		if( !preg_match( "#:(\d+)$#", $_SERVER['HTTP_HOST'], $match ) and !in_array( $_SERVER['SERVER_PORT'], array( 80, 443 ) ) )
		{
			$_SERVER['HTTP_HOST'] .= ":{$_SERVER['SERVER_PORT']}";
		}
		
		if( $_SERVER['HTTPS'] )
		{
			$_SERVER['SERVER_PROTOCOL'] = "https";
		}
		
		$this->base_url = strtolower( strtok( $_SERVER['SERVER_PROTOCOL'], '/' ) ).'://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
		
		//------------------------------------------
		// Режим отладки
		//------------------------------------------
		
		define( 'IN_DEBUG', ( file_exists( $this->home_dir."classes/class_debug.php" ) and md5( $_COOKIE["debug"] ) == "72f41673607aab47fbd728602d93a944" ) ? 1 : 0 );
		
		if( IN_DEBUG )
		{
			$this->load_module( "class", "debug" );
		}
	}
	
	/**
	* Получение настроек системы
	* 
	* Загружает из БД настройки системы, сохраняя
	* их в массив $this->config.
	*
	* @return	void
	*/
	
	function get_settings()
	{
		$this->DB->simple_construct( array(	'select'	=> '*',
											'from'		=> "settings_list",
											)	);
		$this->DB->simple_exec();

		if( !$this->DB->get_num_rows() )
		{
			$this->fatal_error( "Settings load failed. Please reinstall the system." );
		}

		while( $setting = $this->DB->fetch_row() )
		{
			$this->config[ $setting['setting_key'] ] = ( $setting['setting_value'] or is_numeric( $setting['setting_value'] ) ) ? $setting['setting_value'] : $setting['setting_default'];
			
			if( $setting['setting_key'] == "save_path" )
			{
				preg_match( "#(\d+)\.(\d+)\.(\d+)( ([abr])(\d+))?#", $setting['setting_options'], $match );
				
				$this->config['__current__']['numeric'][0] = $match[1];
				$this->config['__current__']['numeric'][1] = $match[2];
				$this->config['__current__']['numeric'][2] = $match[3];
				$this->config['__current__']['numeric'][3] = $match[5] == "a" ? "alpha" : $match[5] == "b" ? "beta" : "rc";
				$this->config['__current__']['numeric'][4] = $match[6];
				
				if( !$match[4] ) $this->config['__current__']['numeric'][3] = NULL;
			}
			else if( $setting['setting_key'] == "reserved_space" )
			{
				preg_match( "#^(\d{10}) ?([a-zA-Z0-9=\+/]+)?( build)?(\d+)?$#", $setting['setting_options'], $match );
				
				$this->update_last_check = array( 'date' => $match[1], 'result' => $match[2], 'build' => $match[4] );
			}
		}
		
		if( TMP_COOKIE === TRUE )
		{
			$this->config['cookie_domain'] = TMP_DOMAIN;
			$this->config['cookie_path'] = TMP_PATH;
		}
	}

	/**
	* Загрузка модуля
	* 
	* Загружает указанный класс или секцию.
	* Если загрузка прошла успешно, помещает указатель
	* на объект класса в массив $this->classes, а указатель
	* на объект секции в массив $this->sections.
	* В случае возникновения ошибки либо выводит ее на экран,
	* либо возвращает FALSE.
	* 
	* @param	string			Тип модуля
	* @param	string			Имя модуля
	* @param	bool	[opt]	Возвращать текст ошибки
	* @param	array	[opt]	Переменные, передаваемые конструктору класса
	*
	* @return	void или FALSE
	*/

	function load_module( $type, $name, $return_error=TRUE, $construct_params=array() )
	{
		$plural = $type == "section" ? "sections" : "classes";
		
		//------------------------------------------
		// Проверяем наличие модуля
		//------------------------------------------
		
		if( $type == "section" and $this->sections[ $name ] ) return;
		if( $type == "class" and $this->classes[ $name ] ) return;
		
		//------------------------------------------
		// Проверяем наличие файла
		//------------------------------------------

		if( !file_exists( $this->home_dir.$plural."/{$type}_".$name.".php" ) )
		{
			if( $return_error )
			{
				return FALSE;
			}
			else
			{
				$this->fatal_error( ucfirst( $type )." '{$name}' load failed." );
			}
		}

		//------------------------------------------
		// Инициализируем модуль
		//------------------------------------------

		require_once( $this->home_dir.$plural."/{$type}_".$name.".php" );

		if( $type == "section" )
		{
			$this->sections[ $name ] =& new $name;
			$this->sections[ $name ]->engine =& $this;
		}
		else 
		{
			$this->classes[ $name ] =& new $name;
			$this->classes[ $name ]->engine =& $this;
		}

		//------------------------------------------
		// Запускаем функцию инициализации, если она
		// существует в модуле
		//------------------------------------------
		
		if( $type == "section" and method_exists( $this->sections[ $name ], "__class_construct" ) )
		{
			if( call_user_func_array( array( &$this->sections[ $name ], "__class_construct" ), $construct_params ) !== TRUE )
			{
				if( $return_error )
				{
					return FALSE;
				}
				else
				{
					$this->fatal_error( "Section {$name} load failed." );
				}
			}
		}
		else if( $type == "class" and method_exists( $this->classes[ $name ], "__class_construct" ) )
		{
			if( call_user_func_array( array( &$this->classes[ $name ], "__class_construct" ), $construct_params ) !== TRUE )
			{
				if( $return_error )
				{
					return FALSE;
				}
				else
				{
					$this->fatal_error( "Class {$name} load failed." );
				}
			}
		}
	}
	
	/**
	* Запуск таймера выполнения
	* 
	* Записывает в переменную $this->debug текущее
	* значение времени для указанного параметра.
	* 
	* @param	string	Название параметра
	*
	* @return	void
	*/

	function start_timer( $place )
	{
		$mtime = microtime();
		$mtime = explode( ' ', $mtime );
		$mtime = (float)$mtime[1] + (float)$mtime[0];

		$place == "script" ? $this->debug[ $place ] = $mtime : $this->debug[ $place ]['current'] = $mtime;
	}

	/**
	* Остановка таймера выполнения
	* 
	* Подсчитывает время, прошедшее с
	* момента запуска таймера для указанного
	* параметра.
	* Результат записывает в переменную
	* $this->debug.
	* 
	* @param	string			Название параметра
	*
	* @return	void
	*/

	function stop_timer( $place )
	{
		$mtime = microtime();
		$mtime = explode ( ' ', $mtime );
		$mtime = (float)$mtime[1] + (float)$mtime[0];

		if( $place == "script" )
		{
			$this->debug[ $place ] = round( ( $mtime - $this->debug[ $place ] ), 5 );
		}
		else
		{
			$this->debug[ $place ]['sum'] += round( ( $mtime - $this->debug[ $place ]['current'] ), 5 );
		}
	}
	
	/**
    * Запуск сервисной функции
    * 
    * Загружает класс сервисных функций и вызывает
    * указанную функцию с заданными параметрами, если
    * она найдена в классе.
    * 
    * @param 	array			Массив с названием функции и ее параметрами
    * @param 	array	[opt]	Массив с параметрами текущей настройки
    * @param 	mixed	[opt]	Новое значение настройки
    *
    * @return	mixed	Результат выполнения вызванной функции
    */
	
	function call_service_function( $params, $setting=array(), $new=NULL )
	{
		//-----------------------------------------------------------
		// Подгружаем класс сервисных функций
		//-----------------------------------------------------------
		
		if( !$this->classes['service'] )
		{
			$this->load_module( "class", "service" );
		}
		
		//-----------------------------------------------------------
		// Определяем название и параметры требуемой функции
		//-----------------------------------------------------------
		
		$setting['setting_current'] = $setting['setting_value'] ? $setting['setting_value'] : $setting['setting_default'];
		
		$vars = array();
					
		if( $params[2] ) $vars = explode( ",", $params[2] );
		
		foreach( $vars as $vid => $value )
		{
			$value = trim( $value );
			
			if     ( $value == "value"   ) $value = $setting['setting_value'];
			else if( $value == "default" ) $value = $setting['setting_default'];
			else if( $value == "current" ) $value = $setting['setting_current'];
			else if( $value == "new" 	 ) $value = $new;
			
			else
			{
				$value = preg_replace( "#'(.*)'#", "\\1", $value );
				$value = strval( $value );
			}
			
			$vars[ $vid ] = $value;
		}
		
		//-----------------------------------------------------------
		// Выполняем поиск и вызов функции
		//-----------------------------------------------------------
		
		if( method_exists( $this->classes['service'], $params[1] ) )
		{
			return call_user_func_array( array( &$this->classes['service'], $params[1] ), $vars );
		}
	}

	/**
	* Критическая ошибка
	* 
	* Выводит сообщение о возникновении критической
	* ошибки и прекращает выполнение скрипта.
	* 
	* @param	string	Текст ошибки
	* @param	array	Параметры ошибки БД
	*
	* @return	void
	*/

	function fatal_error( $txt="Unknown error", $db_error=array() )
	{
		$error[] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">';
		$error[] = '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">';
		$error[] = '<head>';
		$error[] = "<title>{$this->config['__engine__']['name']} {$this->config['__engine__']['version']} - Fatal Error</title>";
		$error[] = '<meta http-equiv="content-type" content="text/html; charset=utf-8" />';
		$error[] = '</head>';
		$error[] = '<body>';
		$error[] = "<div><b>Fatal error:</b> {$txt}</div>";

		if( count( $db_error ) )
		{
			if( isset( $db_error['query'] ) )
			{
				$error[] = "<div style='padding:3px;'><b>Query</b><br/>";
				$error[] = "<form action='' name='mysql_query'>";
				$error[] = "<textarea rows='7' cols='60'>".$db_error['query']."</textarea>";
				$error[] = "</form>";
				$error[] = "</div>";
			}

			if( isset( $db_error['answer'] ) )
			{
				$error[] = "<div style='padding:3px;'><b>Answer</b><br/>";
				$error[] = "<form action='' name='mysql_answer'>";
				$error[] = "<textarea rows='7' cols='60'>".$db_error['answer']."</textarea>";
				$error[] = "</form>";
				$error[] = "</div>";
			}
		}

		$error[] = '</body>';
		$error[] = '</html>';

		exit( implode( "\n", $error ) );
	}

	/*-------------------------------------------------------------------------*/
	// Шифрование
	/*-------------------------------------------------------------------------*/

	/**
    * Шифрование строки по алгоритму RC4
    *
    * Приводит строку и ключ к ASCII-совместимому виду
    * и зашифровывает строку.
    * Возвращает зашифрованную строку.
	* 
	* @param	string	Строка для шифрования
	* @param	string	Ключ
	* 
    * @return	sting	Зашифрованная строка
    */

	function rc4_encrypt( $string, $key )
	{
		$string = $this->rc4( $string, $key );

		return base64_encode( $string );
	}

	/**
    * Дешифрование строки по алгоритму RC4
    *
    * Расшифровывает строку и декодирует ее
    * по алгоритму BASE-64.
    * Возвращает расшифрованную строку.
	* 
	* @param	string	Строка для расшифровки
	* @param	string	Ключ
	* 
    * @return	sting	Расшифрованная строка
    */

	function rc4_decrypt( $string, $key )
	{
		$string = base64_decode( $string );

		return $this->rc4( $string, $key );
	}

	/**
    * (Де)шифрование строки
    * 
    * Производит (де)шифрование указанной строки по
    * указанному ключу, используя алгоритм RC4.
    * 
    * Возвращает зашифрованную (расшифрованную) строку
    * или FALSE в случае возникновения ошибки.
    *
    * @package 		PEAR
    * @subpackage	Crypt
	* @author		Dave Mertens <dmertens@zyprexia.com>
	* 
	* @param	string	Строка для (де)шифрования
	* @param	string	Ключ
	* 
    * @return	string	(де)шифрованная строка
    */

	function rc4( $string, $key )
	{
		//------------------------------------------
		// Предопределенные переменные
		//------------------------------------------

		$i = $j = 0;

		$s = array();

		$len['key'] = strlen( $key );
		$len['str'] = strlen( $string );

		if( $len['key'] == 0 or $len['str'] == 0 )
		{
			return FALSE;
		}

		//------------------------------------------
		// Обработка ключа
		//------------------------------------------

		for( $i=0; $i<256; $i++ )
		{
			$s[ $i ] = $i;
		}

		$j = 0;

		for( $i=0; $i < 256; $i++ )
		{
			$j = ( $j + $s[ $i ] + ord( $key[ $i % $len['key'] ] ) ) % 256;
			$t = $s[ $i ];
			$s[ $i ] = $s[ $j ];
			$s[ $j ] = $t;
		}

		$i = $j = 0;

		//------------------------------------------
		// (Де)шифровка строки
		//------------------------------------------

		for ( $c=0; $c<$len['str']; $c++ )
		{
			$i = ( $i + 1) % 256;
			$j = ( $j + $s[ $i ] ) % 256;
			$t = $s[ $i ];
			$s[ $i ] = $s[ $j ];
			$s[ $j ] = $t;

			$t = ( $s[ $i ] + $s[ $j ] ) % 256;

			$string[ $c ] = chr( ord( $string[ $c ] ) ^ $s[ $t ] );
		}

		return $string;
	}

	/*-------------------------------------------------------------------------*/
	// Cookie
	/*-------------------------------------------------------------------------*/

	/**
    * Запись cookie
    * 
    * Производит запись cookie с указанными параметрами.
    *
    * @param	string			Имя
	* @param	string	[opt]	Значение
	* @param	int		[opt]	Дата истечения срока действия
	* @param	bool	[opt]	Передавать только через защищенное соединение
	* 
    * @return	bool			Отметка об успшеном выполнении
    */

	function my_setcookie( $name, $value="", $expires=-1 )
	{
		$expires = $expires == -1 ? time() + 60*60*24*365 : ( $expires === 0 ? 0 : time() + $expires );
		
		if( !$this->config['cookie_path'] ) $this->config['cookie_path'] = "/";

		return setcookie( $name, urlencode( $value ), $expires, $this->config['cookie_path'], $this->config['cookie_domain'] );
	}

	/**
    * Чтение cookie
    * 
    * Производит чтение cookie с указанным именем.
    * Возвращает значение cookie или FALSE в случае
    * возникновения ошибки.
    *
    * @param	string			Имя
	* 
    * @return	string			Значение
    */

	function my_getcookie( $name )
	{
		if( isset( $_COOKIE[ $name ] ) )
		{
			return $this->classes['input']->parse_clean_value( urldecode( $_COOKIE[ $name ] ) );
		}
		else
		{
			return FALSE;
		}
	}

	/*-------------------------------------------------------------------------*/
	// Список языков, языковые файлы и шаблоны скинов
	/*-------------------------------------------------------------------------*/
	
	/**
    * Загрузка списка языков
    * 
    * Формирует список установленных в системе языков
    * и сохраняет его как глобальный массив.
	* 
    * @return		void
    */
	
	function load_system_languages()
	{
		unset( $this->languages );
		
		$this->DB->simple_construct( array(	'select'	=> '*',
											'from'		=> 'languages',
											)	);
		$this->DB->simple_exec();
		
		while( $lang = $this->DB->fetch_row() )
		{
			if( $lang['lang_id'] == 1 ) $this->languages['default'] = $lang['lang_key'];
			
			$this->languages['list'][ $lang['lang_key'] ] = $lang['lang_name'];
		}
	}

	/**
    * Загрузка шаблока скина
    * 
    * Загружает шаблон скина и помещает ссылку на объект
    * в массив $this->skin.
    * В случае ошибки возвращает FALSE.
    *
    * @param		string			Имя шаблона
	* 
    * @return		void
    */

	function load_skin( $name="" )
	{
		if( $name == "" )
		{
			return FALSE;
		}
		
		require_once( $this->home_dir."templates/{$name}.tpl" );

		$class_name = "template_".$name;

		$this->skin[ $name ] = new $class_name;
		$this->skin[ $name ]->engine =& $this;
	}

	/**
    * Загрузка языкового файла
    * 
    * Загружает языковой файл и записывает языковые строки
    * в массив $this->lang.
    * В случае ошибки возвращает FALSE.
    *
    * @param	string			Имя языкового файла
    * @param	string	[opt]	Ключ языка
    * @param 	bool	[opt]	Возвратить массив со строками
	* 
    * @return	void или array
    */

	function load_lang( $name="", $key="", $return=FALSE )
	{
		if( $name == "" )
		{
			return FALSE;
		}
		
		if( !$this->member['user_id'] ) $this->member['user_lang'] = $this->input['lang_selector'];
		
		$user_lang = array_key_exists( $this->member['user_lang'], &$this->languages['list'] ) ? $this->member['user_lang'] : $this->languages['default'];
		
		$key = $key ? $key : ( $user_lang ? $user_lang : ( $this->languages['default'] ? $this->languages['default'] : "ru" ) );
		
		require_once( $this->home_dir."languages/{$key}/{$name}.lng" );
		
		if( is_array( $lang ) )
		{
			if( $return === TRUE ) return $lang;
			
			foreach( $lang as $k => $v )
			{
				$this->lang[ $k ] = preg_replace( "#\n{1}^\s+#m", "<br/>\n", stripslashes( $v ) );
				$this->lang[ $k ] = preg_replace( "#</li><br/>\n{1}$#", "</li>\n", $this->lang[ $k ] );
				$this->lang[ $k ] = preg_replace( "#<desc>(.+?)</desc>#s", "<div class='description'>\\1</div>", $this->lang[ $k ] );
				$this->lang[ $k ] = str_replace( "--", "&mdash;", $this->lang[ $k ] );
				
				preg_match_all( "/<#(__ENGINE__|SETTINGS)\['(\w+)'\]#>/", $v, $match, PREG_SET_ORDER );
				
				if( is_array( $match ) and !empty( $match ) ) foreach( $match as $elem )
				{
					$this->lang[ $k ] = str_replace( "<#{$elem[1]}['{$elem[2]}']#>", $this->config[ strtolower( $elem[1] ) ][ $elem[2] ], $this->lang[ $k ] );
				}
			}
		}

		unset( $lang );
	}

	/**
    * Замена тэга <br/> на перевод строки
    *
    * @param	string			Исходная строка
	* 
    * @return	string			Обработанная строка
    */

	function my_br2nl( $txt )
	{
		return preg_replace( "/(<br>|<br\/>|<\/br>|<br \/>)/i", "\n", $txt );
	}

	/**
    * Замена перевода строки на тэг <br/>
    *
    * @param	string			Исходная строка
	* 
    * @return	string			Обработанная строка
    */

	function my_nl2br( $txt )
	{
		return str_replace( "\n", "<br/>", $txt );
	}

	/*-------------------------------------------------------------------------*/
	// Обработка строк
	/*-------------------------------------------------------------------------*/

	/**
    * Смена кодировки строки
    * 
    * Изменяет кодировку переданной строки в
    * соответствии с переданными параметрами.
    * В случае, если вместо строки передан массив,
    * рекурсивно вызывает себя для преобразования
    * каждого элемента массива.
    *
    * @param	string			Имя языкового файла
	* 
    * @return	void
    */

	function convert_encoding( &$txt, $from, $to )
	{
		if( is_array( $txt ) )
		{
			foreach( $txt as $k => $v )
			{
				$this->convert_encoding( $txt[ $k ], $from, $to );
			}
		}
		else
		{
			$txt = iconv( $from, $to, $txt );
		}
	}
	
	/**
    * Расшифровка unicode символов
    * 
    * Расшифровывает символы, переданные серверу через запрос и
    * являющиеся символами unicode.
    * 
    * @author 	pedantic@hotmail.co.jp
    * 
    * @param 	string			Закодированная строка
	* 
    * @return	string			Обработанная строка
    */
	
	function urludecode( $str )
	{
		$res = '';

		$i = 0;

		$max = strlen( $str ) - 6;

		while( $i <= $max )
		{
			$character = $str[$i];

			if( $character == '%' && $str[ $i + 1 ] == 'u' )
			{
				$value = hexdec( substr( $str, $i + 2, 4 ) );

				$i += 6;

				if( $value < 0x0080 ) // 1 byte: 0xxxxxxx
				{
					$character = chr( $value );
				}
				else if ( $value < 0x0800 ) // 2 bytes: 110xxxxx 10xxxxxx
				{
					$character  =chr( ( ( $value & 0x07c0 ) >> 6 ) | 0xc0 ).chr( ( $value & 0x3f ) | 0x80 );
				}
				else // 3 bytes: 1110xxxx 10xxxxxx 10xxxxxx
				{
					$character = chr( ( ( $value & 0xf000 ) >> 12 ) | 0xe0 ).chr( ( ( $value & 0x0fc0 ) >> 6 ) | 0x80 ).chr( ( $value & 0x3f ) | 0x80 );
				}
			}
			else
			{
				$i++;
			}

			$res .= $character;
		}

		return $res.substr( $str, $i );
	}
	
	/**
    * Расшифровка не-unicode символов
    * 
    * Расшифровывает символы, переданные серверу через запрос и
    * не являющиеся символами unicode.
    * 
    * @author 	pedantic@hotmail.co.jp
    * @author 	DINI (non-unicode decode)
    * 
    * @param 	string			Закодированная строка
	* 
    * @return	string			Обработанная строка
    */
	
	function urledecode( $str )
	{
		$res = '';

		$i = 0;

		$max = strlen( $str ) - 3;
		
		while( $i <= $max )
		{
			$character = $str[ $i ];
			
			if( $character == '%' and preg_match( "#[c-fC-F]#", $str[ $i + 1 ] ) )
			{
				$character = urldecode( $str[ $i ].$str[ $i + 1 ].$str[ $i + 2 ] );
				
				$i += 3;
				
				$encoding = mb_detect_encoding( $character, "utf-8,windows-1251,jis,euc-jp,sjis", TRUE );
				
				$character = mb_convert_encoding( $character, "utf-8", $encoding );
			}
			else
			{
				$i++;
			}

			$res .= $character;
		}

		return $res.substr( $str, $i );
	}

	/**
    * Изменение языка в строке даты
    * 
    * Заменяет английские слова в строке даты
    * на соответствующие слова текущего языка
    * пользователя.
    * Возвращает отформатированную строку.
    *
    * @param	string			Тип строки
    * @param 	int				Временной штамп
	* 
    * @return	string			Отформатированная строка
    */

	function get_date_str( $method, $date )
	{
		if( !is_array( $this->lang ) ) $this->lang = $this->load_lang( 'global' );

		if( strstr( $method, "M" ) ) 		$method = str_replace( "M", $this->lang['mshort_'.date( 'm', $date )]	, $method );
		else if( strstr( $method, "F" ) )	$method = str_replace( "F", $this->lang['mfull_' .date( 'm', $date )]	, $method );

		if( strstr( $method, "D" ) )		$method = str_replace( "D", $this->lang['dshort_'.date( 'w', $date )]	, $method );
		else if( strstr( $method, "l" ) )	$method = str_replace( "l", $this->lang['dfull_' .date( 'w', $date )]	, $method );

		if( strstr( $method, "S" ) )		$method = str_replace( "S", $this->lang['suffix'], $method );

		return date( $method, $date );
	}
	
	/*-------------------------------------------------------------------------*/
	// Дата и время
	/*-------------------------------------------------------------------------*/

	/**
    * Время по Гринвичу
    * 
    * Укзанное время приводит ко времени по Гринвичу.
    * Возвращает сформированный временной штамп.
    *
    * @param	int		[opt]	Часы
    * @param	int		[opt]	Минуты
    * @param	int		[opt]	Секунды
    * @param	int		[opt]	Месяц
    * @param	int		[opt]	День
    * @param	int		[opt]	Год
	* 
    * @return	int				Временной штамп
    */

	function date_gmmktime( $hour=0, $min=0, $sec=0, $month=0, $day=0, $year=0 )
	{
		$offset = date( 'Z' );

		$time   = mktime( $hour, $min, $sec, $month, $day, $year );

		$dst    = intval( date( 'I', $time ) );

		return $offset + ( $dst * 3600 ) + $time;
	}

	/**
    * Дата
    * 
    * Обрабатывает переданный временной штамп и
    * в зависимости от переданного типа формирует
    * строку с текущей датой.
    * Возвращает сформированую строку.
    *
    * @param	int				Временной штамп
    * @param	string			Тип
    * @param	bool	[opt]	Релятивное значение
	* 
    * @return	string			Сформированная дата
    */

	function get_date( $date, $method, $relative=0 )
	{
		if( !$date )
		{
			return '--';
		}

		if( empty( $method ) )
		{
			$method = 'LONG';
		}

		if( $relative == 1 )
		{
			$this_time = date( 'd,m,Y', $date );

			if( $this_time == $this->today_time )
			{
				return str_replace( '{--}', $this->lang['time_today'], date( $this->time_options['short'], $date ) );
			}
			else if( $this_time == $this->yesterday_time )
			{
				return str_replace( '{--}', $this->lang['time_yesterday'], date( $this->time_options['short'], $date ) );
			}
			else if( $this->member['user_lang'] != 'en' )
			{
				return $this->get_date_str( &$this->time_options[ $method ], $date );
			}
			else
			{
				return date( $this->time_options[ $method ], $date );
			}
		}
		else if( $this->member['user_lang'] != 'en' )
		{
			return $this->get_date_str( $this->time_options[ $method ], $date );
		}
		else
		{
			return date( $this->time_options[ $method ], $date );
		}
	}
	
	/**
    * Преобразование времени
    * 
    * Преобразует переданное количество секунд в дни, часы, минуты
    * и секунды.
    * 
    * @param 	int				Количество секунд
	* @param 	string	[opt]	Максимальная единица измерения
	* @param 	string	[opt]	Символ разделителя
	* @param 	bool	[opt]	Возвращать строку с указанием единиц измерения
	* 
    * @return	string			Обработанная строка
    */
	
	function convert_time_measure( $size, $unit="", $separator=":", $string=FALSE )
	{
		$size = intval( $size );
		
		if( $size < 0 )
		{
			return FALSE;
		}
		
		if( !$unit )
		{
			if( $size < 60 ) $unit = "sec";
			else if( $size < 3600 ) $unit = "min";
			else if( $size < 86400 ) $unit = "hrs";
			else $unit = "days";
		}
		
		$unit = strtolower( $unit );
		
		if( $unit == "min" )
		{
			$time['min'] = floor( $size / 60 );
			$time['sec'] = $size - 60 * $time['min'];
			
			return $string == TRUE	? $time['min']." ".$this->lang['time_min']." ".$time['sec'].$this->lang['time_sec']
									: "00".$separator.sprintf( "%02d", $time['min'] ).$separator.sprintf( "%02d", $time['sec'] );
		}
		else if( $unit == "hrs" )
		{
			$time['hrs'] = floor( $size / 3600 );
			$time['min'] = floor( ( $size - 3600 * $time['hrs'] ) / 60 );
			$time['sec'] = $size - 60 * $time['min'] - 3600 * $time['hrs'];
			
			return $string == TRUE	? $time['hrs']." ".$this->lang['time_hrs']." ".$time['min']." ".$this->lang['time_min']." ".$time['sec'].$this->lang['time_sec']
									: sprintf( "%02d", $time['hrs'] ).$separator.sprintf( "%02d", $time['min'] ).$separator.sprintf( "%02d", $time['sec'] );
		}
		else if( $unit == "days" )
		{
			$time['days'] = floor( $size / 86400 );
			$time['hrs']  = floor( ( $size - 86400 * $time['days'] ) / 3600 );
			$time['min']  = floor( ( $size - 86400 * $time['days'] - 3600 * $time['hrs'] ) / 60 );
			$time['sec']  = $size - 60 * $time['min'] - 3600 * $time['hrs'] - 86400 * $time['days'];
			
			return $string == TRUE	? $time['days']." ".$this->lang['time_days']." ".$time['hrs']." ".$this->lang['time_hrs']." ".
									  $time['min']." ".$this->lang['time_min']." ".$time['sec'].$this->lang['time_sec']
									: $time['days']." ".sprintf( "%02d", $time['hrs'] ).$separator.sprintf( "%02d", $time['min'] ).$separator.sprintf( "%02d", $time['sec'] );
		}
		else 
		{
			$time['sec'] = $size;
			
			return $string == TRUE ? $size." ".$this->lang['time_sec'] : "00".$separator."00".$separator.sprintf( "%02d", $time['sec'] );
		}
	}
	
	/*-------------------------------------------------------------------------*/
	// Запись в журнал событий
	/*-------------------------------------------------------------------------*/
	
	/**
    * Добавление события
    * 
    * Добавляет запись о событии в массив.
    *
    * @param	int				Степень детализации
    * @param	string			Код события
    * @param 	array	[opt]	Дополнительные данные
    * @param 	string	[opt]	Тип события
	* 
    * @return	void
    */
	
	function add_log_event( $detail, $code, $misc=array(), $type='info' )
	{
		if( $this->config['log_detail'] < 1 or !is_int( $detail ) or $detail < 1 or $detail > 5 ) return;
		
		//------------------------------------------
		// Определяем тип события
		//------------------------------------------
		
		switch( $detail )
		{
			case 1:
				if( $this->config['log_detail'] >= 1 ) $type = 'error';
				else return;
				break;
				
			case 2:
				if( $this->config['log_detail'] >= 2 ) $type = 'warn';
				else return;
				break;
				
			case 3:
				if( $this->config['log_detail'] < 3 ) return;
				break;
				
			case 4:
				if( $this->config['log_detail'] < 4 ) return;
				break;
				
			default:
				return;
		}
		
		if( !in_array( $type, array( 'info', 'warn', 'error' ) ) ) $type = 'info';
		
		//------------------------------------------
		// Формируем массив с параметрами события
		//------------------------------------------
		
		$event = array(	'log_type'		=> &$type,
						'log_system'	=> $this->member['user_id'] ? 0 : 1,
						'log_code'		=> &$code,
						'log_time'		=> time(),
						'log_user'		=> &$this->member['user_id'],
						'log_visible'	=> $detail == 4 ? 0 : 1,
						'log_misc'		=> ( is_array( $misc ) and count( $misc ) ) ? serialize( $misc ) : "",
						);
		
		$this->events[] = $event;
		
		//------------------------------------------
		// Добавляем событие в БД
		//------------------------------------------
		
		if( $this->input['tab'] == 'log' )
		{
			$this->insert_db_log();
			
			$this->events = array();
		}
	}
	
	
	/**
    * Запись в журнал
    * 
    * Записывает все накопленные записи о событиях в
    * базу данных.
	* 
    * @return	void
    */
	
	function insert_db_log()
	{
		if( count( $this->events ) ) foreach( $this->events as $event ) $this->DB->do_insert( "system_log", &$event );
	}
	
	/*-------------------------------------------------------------------------*/
	// Работа с файлами и директориями
	/*-------------------------------------------------------------------------*/
	
	/**
    * Подсчет свободного места
    * 
    * Подсчитывает свободное место у пользователя для сохранения файла.
    * 
    * @param 	int				Идентификатор пользователя
	* 
    * @return	int				Количество свободного места
    */
	
	function get_free_space( $uid )
	{
		//------------------------------------------
		// Получаем количество занятого места из кэша
		//------------------------------------------
		
		if( is_numeric( $this->cache['engine']['total_free'] ) )
		{
			$total_free = $this->cache['engine']['total_free'];
		}
		
		//------------------------------------------
		// Определяем количество занятого места
		//------------------------------------------
		
		else
		{
			$dir_size = $this->dirsize( $this->config['save_path'] );
			
			$reserved_space = floor( $this->config['reserved_space'] * 1024 * 1024 );
			
			if( $dir_size >= $reserved_space )
			{
				$this->cache['engine']['total_free'] = 0;
				
				return 0;
			}
			
			//------------------------------------------
			// Получаем сведения о размере закачиваемых
			// файлов
			//------------------------------------------
			
			$this->DB->simple_construct( array(	'select'	=> '*',
												'from'		=> 'categories_files',
												'where'		=> "file_state='running'"
												)	);
			$this->DB->simple_exec();
			
			if( $this->DB->get_num_rows() )
			{
				//-----------------------------------------------------------
				// Подгружаем класс для работы с модулями
				//-----------------------------------------------------------
				
				$this->load_module( "class", "downloader", FALSE );
				
				//-----------------------------------------------------------
				// Обновляем информацию о каждом файле
				//-----------------------------------------------------------
				
				while( $file = $this->DB->fetch_row() )
				{
					$modules[ $file['file_dl_module'] ][] = $file;
				}
				
				foreach( $modules as $mid => $files )
				{
					if( $this->classes['downloader']->load_module( "", $mid ) === FALSE )
					{
						continue;
					}
					
					foreach( $files as $file )
					{
						$this->classes['downloader']->update_download_state( $file );
						
						$size_left = $this->classes['downloader']->state['file_dl_left'];
						
						if( $size_left ) $dir_size += $size_left;
					}
				}
			}
			
			//-----------------------------------------------------------
			// Еще раз определяем количество занятого места
			//-----------------------------------------------------------
			
			if( $dir_size >= $reserved_space )
			{
				$this->cache['engine']['total_free'] = 0;
				
				return 0;
			}
			
			$total_free = $reserved_space - $dir_size;
			
			//-----------------------------------------------------------
			// Сохраняем полученное значение в кэш
			//-----------------------------------------------------------
			
			$this->cache['engine']['total_free'] = &$total_free;
		}
		
		//------------------------------------------
		// Получаем сведения о пользователе
		//------------------------------------------
		
		if( $this->member['user_id'] == $uid )
		{
			$member = &$this->member;
		}
		else if( $uid )
		{
			$member = $this->DB->simple_exec_query( array(	'select'	=> 'user_name, user_max_amount',
															'from'		=> 'users_list',
															'where'		=> "user_id='{$uid}'"
															)	);
		}
		else
		{
			$member = array( 'user_name' => '_all' );
		}
		
		//------------------------------------------
		// Определяем размер скачанных файлов
		//------------------------------------------
		
		if( $member['user_max_amount'] >= 0 )
		{
			$dirname = strtolower( preg_replace( "#\W#", "_", $member['user_name'] ) );
			
			$dir_size = $this->dirsize( $this->config['save_path']."/{$dirname}" );
			
			$dir_left = $member['user_max_amount'] * 1024 * 1024 - $dir_size;
			
			return ( ( $total_free - $dir_left ) > 0 ) ? $dir_left : 0;
		}
		else
		{
			return $total_free;
		}
	}
	
	/**
    * Определение размера директории
    * 
    * Определяет количество места, занимаемое указанной директорией
    * и всеми вложенными директориями и файлами.
    * 
    * @author 	medhefgo@googlemail.com
    * 
    * @param 	string			Путь до директории
	* 
    * @return	bool			FALSE или
    * 			int				Итоговый размер в байтах
    */
	
	function dirsize( $dirname )
	{
		if( !is_dir( $dirname ) || !is_readable( $dirname ) ) return FALSE;

		$dirname_stack[] = $dirname;
		$size = 0;

		while( count( $dirname_stack ) > 0 )
		{
			$dirname = array_shift( $dirname_stack );
			$handle = opendir( $dirname );

			while( FALSE !== ( $file = readdir( $handle ) ) )
			{
				if( $file != '.' && $file != '..' && is_readable( $dirname."/".$file ) )
				{
					if( is_dir( $dirname."/".$file ) )
					{
						$dirname_stack[] = $dirname."/".$file;
					}

					$size += filesize( $dirname."/".$file );
				}
			}

			closedir($handle);
		}
	
		return $size;
	}
	
	/**
    * Перемещение исполняемых файлов CRON
    * 
    * Подсчитывает количество имеющихся исполняемых файлов,
    * созданных скриптом, в директории CRON. Если он один
    * или их нет вовсе, выполняет перемещение одного нового
    * исполняемого файла из временной директории в рабочую.
	* 
    * @return	void
    */
	
	function move_cron_files()
	{
		//------------------------------------------
		// Определяем количество имеющихся файлов
		//------------------------------------------
		
		$count = 0;
		
		if( $dir = opendir( $this->config['cron_path'] ) )  while( FALSE !== ( $file = readdir( $dir ) ) )
        {
        	if( preg_match( "#^ados_[a-zA-Z0-9]{32}\.sh$#", $file ) ) $count++;
		}
		
		closedir( $dir );
		
		if( $count > 1 ) return;
		
		//------------------------------------------
		// Проверяем наличие файлов во временной директории
		//------------------------------------------
		
		if( $dir = opendir( $this->config['save_path']."/_tmp" ) )  while( FALSE !== ( $file = readdir( $dir ) ) )
        {
        	if( preg_match( "#^ados_[a-zA-Z0-9]{32}\.sh$#", $file ) )
        	{
        		$got_it = TRUE;
        		
        		break;
        	}
		}
		
		closedir( $dir );
		
		if( !$got_it ) return;
		
		//------------------------------------------
		// Добавляем новый файл
		//------------------------------------------
		
		if( !@rename( $this->config['save_path']."/_tmp/".$file, $this->config['cron_path']."/".$file ) ) return;
		
		//-------------------------------------------------
		// Делаем файл исполняемым
		//-------------------------------------------------
		
		if( chmod( "{$this->config['cron_path']}/".$file, 0755 ) === FALSE )
		{
			@unlink( "{$this->config['cron_path']}/".$file );
			
			return;
		}
	}
	
	/**
    * Копирование директории и ее содержимого
    * 
    * Копирует указанную директорию вместе со
    * всеми вложенными файлами и директориями.
    * 
    * @author 	Anton Makarenko
    * 			makarenkoa@ukrpost.net
    *			webmaster@eufimb.edu.ua
    * @author 	DINI (Некоторые изменения и дополнения для ADOS)
    * 
    * @param 	string			Путь до исходной директории
    * @param 	string			Путь до конечной директории
    * @param 	octal	[opt]	CHMOD копируемых файлов
    * @param 	bool	[opt]	Создавать директории, если их не существует
    * @param 	array	[opt]	( 'files'	=> array( Шаблоны имен файлов, которые разрешается копировать ),
    * 							  'dirs'	=> array( Шаблоны имен директорий, которые разрешается копировать ),
    * 							  )
	* 
    * @return	bool			Результат выполнения операции
    */
	
	function copy_dir( $from, $to, $chmod=0777, $create_dirs=TRUE, $regex=array() )
	{
		//------------------------------------------
		// Создаем папку
		//------------------------------------------
		
		$path = "";
		
		$to = str_replace( "\\", "/", $to );
		$to = str_replace( "//", "/", $to );
		$to = preg_replace( "#/$#", "", $to );
		
		$dirs = explode( "/", $to );
		
		foreach( $dirs as $piece )
		{
			$path .= $piece."/";
			
			if( !is_dir( $path ) )
			{
				if( $create_dirs and !preg_match( "#^[a-zA-Z]:$#", $path ) and !@mkdir( $path ) ) return FALSE;
				else if( !$create_dirs ) return FALSE;
				else chmod( $path, $chmod );
			}
		}
		
		//------------------------------------------
		// Делаем проверку на ошибки
		//------------------------------------------

		if( !is_writable( $to ) )	return FALSE;
		if( !is_dir( $to ) ) 		return FALSE;
		if( !is_dir( $from ) )		return FALSE;
		
		$exceptions = array( '.' , '..' );

		//------------------------------------------
		// Начинаем...
		//------------------------------------------

		$handle = opendir( $from );

		while( FALSE !== ( $item = readdir( $handle ) ) )
		if( !in_array( $item, $exceptions ) )
        {
			$copy['from'] = str_replace( '//', '/', $from.'/'.$item );
			$copy['to'] = str_replace( '//', '/', $to.'/'.$item );
			
			if( is_file( $copy['from'] ) )
			{
				//------------------------------------------
				// Проверяем шаблоны названий файлов
				//------------------------------------------
				
				$can_copy = TRUE;
				
				if( is_array( $regex['files'] ) )
				{
					foreach( $regex['files'] as $pattern ) if( !preg_match( $pattern, $item ) )
					{
						$can_copy = FALSE;
							
						break;
					}
				}
				
				//------------------------------------------
				// Копируем файл
				//------------------------------------------
				
				if( $can_copy and @copy( $copy['from'], $copy['to'] ) )
				{
					chmod( $copy['to'], $chmod );
					touch( $copy['to'], filemtime( $copy['from'] ) );
				}
			}
	
			else if( is_dir( $copy['from'] ) )
			{
				//------------------------------------------
				// Проверяем шаблоны названий директорий
				//------------------------------------------
		
				if( is_dir( $copy['to'] ) and is_array( $regex['dirs'] ) )
				{
					$dir = str_replace( "\\", "/", $copy['to'] );
					$dir = preg_replace( "#/$#", "", $dir );
					$dir = substr( $dir, strrpos( $dir, "/" ) + 1 );
					
					foreach( $regex['dirs'] as $pattern ) if( !preg_match( $pattern, $dir ) )
					{
						return FALSE;
					}
				}
				
				//------------------------------------------
				// Пытаемся создать директорию
				//------------------------------------------
				
				if( $create_dirs and mkdir( $copy['to'] ) )
				{
					chmod( $copy['to'], $chmod );
				}
				
				//------------------------------------------
				// Копируем директорию
				//------------------------------------------
				
				if( is_dir( $copy['to'] ) )
				{
					$this->copy_dir( $copy['from'], $copy['to'], $chmod, $create_dirs, $regex );
				}
			}
		}
	
		closedir( $handle );
		
		return true;
	}
	
	/**
    * Удаление директории и ее содержимого
    * 
    * Удаляет указанную директорию вместе со
    * всеми вложенными файлами и директориями.
    * 
    * @author 	eli.hen@gmail.com
    * 
    * @param 	string			Путь до удаляемой директории
    * @param 	bool			Выполнить только очистку
	* 
    * @return	bool			Результат выполнения операции
    */
	
	function remove_dir( $dirname, $only_empty=FALSE )
	{
		if( !is_dir( $dirname ) ) return FALSE;

		$dscan = array( realpath( $dirname ) );
		$darr = array();

		while( !empty( $dscan ) )
		{
			$dcur = array_pop( $dscan );
        
			$darr[] = $dcur;
        
			if( $d = opendir( $dcur ) )
			{
				while( $f = readdir( $d ) )
				{
					if( $f == '.' or $f == '..' ) continue;
                
					$f = $dcur.'/'.$f;
                
					if( is_dir( $f ) ) $dscan[] = $f;
					else @unlink( $f );
				}

				closedir( $d );
			}
		}

		$i_until = $only_empty ? 1 : 0;

		for( $i = count( $darr )-1; $i >= $i_until; $i-- )
		{
			@rmdir( $darr[$i] );
		}
		
		return $only_empty ? count( scandir ) <= 2 : !is_dir( $dirname );
	}
	
	/**
    * Определение размера файла
    * 
    * Вычисляет размер указанного файла.
    * Возвращает размер файла в указанных
    * единицах.
    * Если файл не найден возвращает FALSE.
    * 
    * @param 	string			Относительный путь до файла
	* @param 	string	[opt]	Единица измерения размера
	* @param 	int		[opt]	Количество знаков после запятой
	* @param 	bool	[opt]	Возвращать строку с указанием единицы измерения
	* 
    * @return	string			Размер файла
    */
	
	function get_file_size( $path, $unit="Kb", $signs=2, $string=TRUE )
	{
		if( !is_file( $this->home_dir.$path ) )
		{
			return FALSE;
		}
		
		$size = filesize( $this->home_dir.$path );
		
		switch( strtolower( $unit ) )
		{
			case "b":
				return $string == TRUE ? $size." ".$this->lang['size_b'] : $size;
				break;
				
			case "mb":
				$size = round( ( $size / ( 1024 * 1024 ) ), $signs );
				return $string == TRUE ? $size ." ".$this->lang['size_mb'] : $size;
				
			case "gb":
				$size = round( ( $size / ( 1024 * 1024 * 1024 ) ), $signs );
				return $string == TRUE ? $size ." ".$this->lang['size_gb'] : $size;
				
			default:
				$size = round( ( $size / 1024 ), $signs );
				return $string == TRUE ? $size ." ".$this->lang['size_kb'] : $size;
		}
	}
	
	/**
    * Преобразование размера файла
    * 
    * Преобразует переданный размер файла до
    * указанной единицы измерения.
    * 
    * @param 	int				Размер файла в байтах
	* @param 	string	[opt]	Единица измерения размера
	* @param 	int		[opt]	Количество знаков после запятой
	* @param 	bool	[opt]	Возвращать строку с указанием единицы измерения
	* 
    * @return	string			Размер файла
    */
	
	function convert_file_size( $size, $unit="", $signs=2, $string=TRUE )
	{
		if( $size < 0 )
		{
			return FALSE;
		}
		
		if( !$unit )
		{
			if( $size < 524288 ) $unit = "kb";
			else if( $size < 894784853 ) $unit = "mb";
			else $unit = "gb";
		}
		
		switch( strtolower( $unit ) )
		{
			case "mb":
				$size = round( $size / pow( 1024, 2 ), $signs );
				return $string == TRUE ? $size ." ".$this->lang['size_mb'] : $size;
				
			case "gb":
				$size = round( $size / pow( 1024, 3 ), $signs );
				return $string == TRUE ? $size ." ".$this->lang['size_gb'] : $size;
				
			default:
				$size = round( $size / pow( 1024, 1 ), $signs );
				return $string == TRUE ? $size ." ".$this->lang['size_kb'] : $size;
		}
	}
}

?>
