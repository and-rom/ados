<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			Обработка входящих переменных
*/

/**
* Класс, содержащий функции для очистки входящих
* внешних данных от потециально опасного кода.
*
* @author	DINI
* @version	1.3.9 (build 74)
*/

class input
{
	/**
	* Конструктор класса (не страндартный PHP)
	* 
	* Вызывает функцию обработки входящих данных и
	* определяет программное окружение пользователя.
	* Всегда возвращает TRUE.
	* 
	* @return	bool				Отметка об успешном выполнении
	*/
	
	function __class_construct()
	{
		$this->parse_incoming();

		//-----------------------------------------
		// Браузер пользователя
		//-----------------------------------------

		$this->engine->env['user_agent'] = $this->parse_clean_value( $_SERVER['HTTP_USER_AGENT'] );
		
		return TRUE;
	}
	
	/**
	* Обработка входящих переменных
	* 
	* Вызывает функции для удаления потенциально опасных
	* символов из названий и значений входящих переменных
	* и сохраняет результат в массив $this->engine->input
	* 
	* @return	void
	*/

	function parse_incoming()
	{
		if( !is_array( $_REQUEST ) ) return;
		
		//----------------------------------------
		// Обрабатываем переменные массива REQUEST
		//----------------------------------------
		
		foreach( $_REQUEST as $key => $value )
		{
			if( is_array( $_REQUEST[ $key ] ) ) foreach( $_REQUEST[ $key ] as $key2 => $value2 )
			{
				$this->engine->input[ $this->parse_clean_key( $key ) ][ $this->parse_clean_key( $key2 ) ] = $this->parse_clean_value( $value2, $key2 );
			}
			else 
			{
				$this->engine->input[ $this->parse_clean_key( $key ) ] = $this->parse_clean_value( $value, $key );
			}
		}
		
		unset( $_GET, $_POST, $_REQUEST );
	}

	/**
	* Обработка ключа (названия) переменной
	* 
	* Удаляет из названия потенциально опасные
	* символы.
	* Возвращает обработанное название.
	* 
	* @param	string				Ключ
	* 
	* @return	string				Обработанный ключ
	*/

	function parse_clean_key( $key )
	{
		if( $key == "" ) return "";

		$key = htmlspecialchars( urldecode( $key ) );
		
		$key = preg_replace( "#\W#", "_", $key );

		return $key;
	}
	
	/**
	* Обработка значения переменной
	* 
	* Удаляет из значения потенциально опасные
	* символы.
	* Возвращает обработанное значение.
	* 
	* @param	string				Значение
	* @param	string				Имя переменной
	* 
	* @return	string				Обработанное значение
	*/

	function parse_clean_value( $val )
	{
		if( $val == "" ) return "";
		
		$val = $this->engine->urludecode( $val );
		
		$val = str_replace( "%25", "%", $val );
		$val = preg_match( "#[file|link]#", $key ) ? $val : str_replace( "%20", " ", $val );
		
		$source = array( "%0A", "%0D", "%09", "%21", "%22", "%23", "%24", "%26", "%27", "%28", "%29", "%2C", "%3D", "%3A", "%3B", "%3F", "%5B", "%5D", "%5E", "%7C" );
		$replace = array( "\n", "\r" , "\t" , "!"  , "\"" , "#"  , "$"  , "&"  , "'"  , "("  , ")"  , ","  , "="  , ":"  , ";"  , "?"  , "["  , "]"  , "^"  , "|"   );
		
		$val = str_ireplace( $source, $replace, $val );
		
		if( in_array( "mbstring", $this->engine->php_ext ) )
		{
			$val = $this->engine->urledecode( $val );
		}
		
		$val = str_replace( "&#032;"	   , " "			 , $val );
		$val = str_replace( "<!--"         , "&#60;&#33;--"  , $val );
		$val = str_replace( "-->"          , "--&#62;"       , $val );
		$val = preg_replace( "/<script/i"  , "&#60;script"   , $val );
		$val = str_replace( ">"            , "&gt;"          , $val );
		$val = str_replace( "<"            , "&lt;"          , $val );
		$val = str_replace( "\""           , "&quot;"        , $val );
		$val = preg_replace( "/\\\$/"      , "&#036;"        , $val );
		$val = preg_replace( "/\r/"        , ""              , $val );
		$val = str_replace( "!"            , "&#33;"         , $val );
		$val = str_replace( "'"            , "&#39;"         , $val );

		$val = preg_replace("/&amp;#([0-9]+);/s", "&#\\1;", $val );

		if ( $this->engine->get_magic_quotes )
		{
			$val = stripslashes( $val );
		}

		$val = preg_replace( "/\\\(?!&amp;#|\?#)/", "&#092;", $val );

		return $val;
	}
	
	/**
	* Удаление потенциально опасных тэгов и JS слов
	* 
	* @param	string				Строка для обработки
	* 
	* @return	string				Обработанная строка
	*/

	function clean_evil_tags( $t )
	{
		$t = preg_replace( "/javascript/i" , "j&#097;v&#097;script", $t );
		$t = preg_replace( "/alert/i"      , "&#097;lert"          , $t );
		$t = preg_replace( "/about:/i"     , "&#097;bout:"         , $t );
		$t = preg_replace( "/onmouseover/i", "&#111;nmouseover"    , $t );
		$t = preg_replace( "/onclick/i"    , "&#111;nclick"        , $t );
		$t = preg_replace( "/onload/i"     , "&#111;nload"         , $t );
		$t = preg_replace( "/onsubmit/i"   , "&#111;nsubmit"       , $t );
		$t = preg_replace( "/<body/i"      , "&lt;body"            , $t );
		$t = preg_replace( "/<html/i"      , "&lt;html"            , $t );
		$t = preg_replace( "/document\./i" , "&#100;ocument."      , $t );

		return $t;
	}
	
	/**
	* Восстановление значения переменной
	* 
	* Восстанавливает потенциально опасные символы
	* в обработанной строке.
	* Внимание! Использовать только для проверки ссылок
	* и (или) для декодирования спецсимволов в паролях.
	* 
	* @param	string				Значение
	* 
	* @return	string				Обработанное значение
	*/

	function parse_unclean_value( $val )
	{
		if( $val == "" )
		{
			return "";
		}
		
		$val = str_replace( "&gt;"   , ">"  , $val );
		$val = str_replace( "&lt;"   , "<"  , $val );
		$val = str_replace( "&quot;" , "\"" , $val );
		$val = str_replace( "&#036;" , "$"  , $val );
		$val = str_replace( "&#33;"  , "!"  , $val );
		$val = str_replace( "&#39;"  , "'"  , $val );

		return $val;
	}
}

?>