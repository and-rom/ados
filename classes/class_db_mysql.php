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
* Класс, содержащий функции для работы с БД MySQL
* версий 4.x и выше
*
* @author	DINI
* @version	1.3.9 (build 74)
*/

class db_mysql extends db_universal
{
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
		include_once( $this->engine->home_dir."database.conf.php" );
		
		$this->connection_id = @mysqli_connect( $params['host'], $params['user'], $params['pass'] );
			
		if( !$this->connection_id )
		{
			return FALSE;
		}
		
		if( !mysqli_select_db( $this->connection_id, $params['database'] ) )
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

		$this->query_id = mysqli_query( $this->connection_id, $the_query );

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
	* @param	int		[opt]	Идентификатор запроса
	* 
	* @return	array			Массив с полями возвращенного ряда
	*/

	function fetch_row( $query_id=0 )
	{
		if( !$query_id )
		{
			$query_id = $this->query_id;
		}

		$row = mysqli_fetch_array( $query_id, MYSQLI_ASSOC );
		
		return $row;
	}

	/**
	* Количество возвращенных рядов для текущего запроса
	* 
	* @return	int				Количество рядов
	*/

	function get_num_rows()
	{
		return mysqli_num_rows( $this->query_id );
	}
	
	/**
	* Идентификатор вставленного ряда
	* 
	* @return	mixed			Идентификатор
	*/

	function get_insert_id()
	{
		return mysqli_insert_id( $this->connection_id );
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
			return @mysqli_close( $this->connection_id );
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
																				'answer'	=> htmlspecialchars( mysqli_error( $this->connection_id ) )
																				)	);
	}
}

?>
