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
* версий 3.x
*
* @author	DINI
* @version	1.3.9 (build 74)
*/

class db_sqlite3 extends db_universal 
{
	/**
	* Объект PHP Data Object для работы с БД
	* SQLite 3.x
	* 
	* @var 	object
	*/
	
	var $pdo = NULL;
	
	/**
	* Информация о текущем запросе
	* 
	* @var 	array
	*/
	
	var $current_query = array(	'id'	=> NULL,
								'count'	=> 0,
								'rows'	=> 0,
								);
	
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
		$this->pdo = new PDO( "sqlite:".$this->engine->home_dir."database.s3db" );
		
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
		
		$this->connection_id = $this->pdo->query( $the_query );

		if( !$this->connection_id )
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

		return $this->connection_id;
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
			$query_id = $this->connection_id;
		}

		if( $this->current_query['id'] === $this->connection_id )
		{
			$row_id = ++$this->current_query['count'];
		}
		else 
		{
			$this->current_query['id'] = $this->connection_id;
			
			$row_id = $this->current_query['count'] = 0;
			
			$this->current_query['rows'] = $this->connection_id->fetchAll();
		}
		
		if( !( $current_row = $this->current_query['rows'][ $row_id ] ) ) return FALSE;
		
		if( is_array( $current_row ) ) foreach( $current_row as $rid => $cell )
		{
			if( strpos( $cell, "b64_" ) === 0 and $this->engine->config['check_non_latin_chars'] )
			{
				$current_row[ $rid ] = base64_decode( substr( $cell, 4 ) );
			}
			
			if( strpos( $rid, "." ) !== FALSE )
			{
				$rid_new = preg_replace( "#^\w+\.(\w+)$#", "\\1", $rid );
				
				$row_new[ $rid_new ] = $cell;
				
				unset( $current_row[ $rid ] );
			}
		}
		
		if( is_array( $row_new ) ) $current_row = array_merge( $current_row, $row_new );
		
		return $current_row;
	}

	/**
	* Количество возвращенных рядов для текущего запроса
	* 
	* @return	int				Количество рядов
	*/

	function get_num_rows()
	{
		$this->current_query['id'] 		= $this->connection_id;
		$this->current_query['count'] 	= -1;
		$this->current_query['rows'] 	= $this->connection_id->fetchAll();
		
		return count( $this->current_query['rows'] );
	}
	
	/**
	* Идентификатор вставленного ряда
	* 
	* @return	mixed			Идентификатор
	*/

	function get_insert_id()
	{
		return $this->pdo->lastInsertId();
	}

	/**
	* Закрытие текущего подключения
	*  
	* @return	bool			Отметка об успешном выполнении
	*/

	function close_db()
	{
		$this->pdo = NULL;
		
		return TRUE;
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
		$error_info = $this->pdo->errorInfo();
		
		$this->engine->fatal_error( "Database query execution error", array(	'query'		=> htmlspecialchars( $the_query ),
																				'answer'	=> htmlspecialchars( $error_info[2] )
																				)	);
	}
}

?>
