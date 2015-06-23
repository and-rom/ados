<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			Работа с базой данных
*/

require_once( dirname( __FILE__ )."/class_db.php" );

/**
* Класс, содержащий функции для работы с БД SQLite
* версий 2.x
*
* @author	DINI
* @version	1.3.9 (build 74)
*/

class db_sqlite2 extends db_universal 
{
	/**
	* Нелатинские символы
	* 
	* Перед выполнением запроса и перед выдачей результата значения полей
	* проверяются на  наличие символов, имеющихся в этой строке и в случае,
	* если такие символы найдены, используется конвертация в Base-64 для
	* корректного хранения записей в БД SQLite 2.x
	* 
	* @var array
	*/
	
	var $non_latin_chars = "АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ";

	/**
	* Конструктор класса (не страндартный PHP)
	* 
	* Подключается к базе данных.
	* Возвращает TRUE в случае успешного соединения и
	* FALSE в случае ошибки
	* 
	* @return	bool			Отметка об успешном выполнении
	*/

	function __class_construct()
	{
		$this->connection_id = sqlite_open( $this->engine->home_dir."database.sqlite", 0666, $error );
			
		if( !$this->connection_id )
		{
			return FALSE;
		}
		
		return TRUE;
	}

	//-----------------------------------------------

	/**
	* Выполнение запроса в БД
	* 
	* @param	string			Строка запроса
	* 
	* @return	int				Идентификатор запроса
	*/

	function query_exec( $the_query, $bypass=0 )
	{
		if( IN_DEBUG )
		{
			$this->engine->start_timer( 'database' );
		}

		//--------------------------------------
		
		$this->query_id = sqlite_query( $this->connection_id, $the_query );

		if( !$this->query_id )
		{
			$this->fatal_error( $the_query );
		}

		//--------------------------------------

		if( IN_DEBUG )
		{
			$this->engine->stop_timer( 'database' );
		}

		$this->query_count++;

		if( IN_DEBUG )
		{
			$this->cached_queries[] = $the_query;
		}

		unset( $the_query );

		return $this->query_id;
	}

	/**
	* Возврат ряда для текущего запроса
	* 
	* @param	resource [opt]	Идентификатор запроса
	* 
	* @return	array			Массив с полями возвращенного ряда
	*/

	function fetch_row( $query_id=NULL )
	{
		if( !$query_id )
		{
			$query_id = $this->query_id;
		}

		$row = sqlite_fetch_array( $query_id, SQLITE_ASSOC );
		
		if( is_array( $row ) ) foreach( $row as $rid => $cell )
		{
			if( strpos( $cell, "b64_" ) === 0 and $this->engine->config['check_non_latin_chars'] )
			{
				$row[ $rid ] = base64_decode( substr( $cell, 4 ) );
			}
			
			if( strpos( $rid, "." ) !== FALSE )
			{
				$rid_new = preg_replace( "#^\w+\.(\w+)$#", "\\1", $rid );
				
				$row_new[ $rid_new ] = $row[ $rid ];
				
				unset( $row[ $rid ] );
			}
		}
		
		if( is_array( $row_new ) ) $row = array_merge( $row, $row_new );
		
		return $row;
	}

	/**
	* Количество возвращенных рядов для текущего запроса
	* 
	* @return	int				Количество рядов
	*/

	function get_num_rows()
	{
		return sqlite_num_rows( $this->query_id );
	}
	
	/**
	* Идентификатор вставленного ряда
	* 
	* @return	mixed			Идентификатор
	*/

	function get_insert_id()
	{
		return sqlite_last_insert_rowid( $this->connection_id );
	}

	/**
	* Закрытие текущего подключения
	*  
	* @return	bool			Отметка об успешном выполнении
	*/

	function close_db()
	{
		if( $this->connection_id )
		{
			return @sqlite_close( $this->connection_id );
		}
	}

	//-----------------------------------------------

	/**
	* Критическая ошибка
	* 
	* Если скрипт находится в режиме отладки, то выводит
	* текст запроса и текст ошибки на экран.
	* В противном случае посылает e-mail с данными об
	* ошибке администратору.
	* 
	* @param	string			Текст запроса
	* 
	* @return	void
	*/

	function fatal_error( $the_query="" )
	{
		$this->engine->fatal_error( "Database query execution error", array(	'query'		=> htmlspecialchars( $the_query ),
																				'answer'	=> htmlspecialchars( sqlite_error_string( sqlite_last_error( $this->connection_id ) ) )
																				)	);
	}
}

?>
