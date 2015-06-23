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

/**
* Класс, содержащий базовые функции для работы с БД
*
* @author	DINI
* @version	1.3.9 (build 74)
*/

class db_universal
{
	/**
	* Идентификатор текущего соединения
	* 
	* @var int
	*/

	var $connection_id		= 0;

	/**
	* Количество выполненных запросов
	* 
	* @var int
	*/

	var $query_count		= 0;

	/**
	* Текст ошибки
	* 
	* @var bool
	*/

	var $error				= "";
	
	/**
	* Строка текущего запроса
	* 
	* @var string
	*/

	var $cur_query			= "";
	
	/**
	* Идентификатор текущего запроса
	* 
	* @var int
	* @var resource
	*/

	var $query_id			= NULL;

	/**
	* Массив с текстами выполненных запросов
	* (для отладки)
	* 
	* @var array
	*/

	var $cached_queries		= array();
	
	//-----------------------------------------------

	/**
	* UPDATE-запрос
	* 
	* @param	string			Имя таблицы
	* @param	array			Массив с параметрами запроса
	* @param	string	[opt]	WHERE-условие
	* 
	* @return	int				Идентификатор запроса
	*/

	function do_update( $table, $params, $where )
	{
		$set = $this->compile_update_string( &$params );

		$query = "UPDATE {$table} SET {$set}";

		if( $where )
		{
			$query .= " WHERE ".$where;
		}

		$id = $this->query_exec( $query );

		unset( $table, $params, $where, $set, $query );

		return $id;
	}

	/**
	* INSERT-запрос
	* 
	* @param	string			Имя таблицы
	* @param	array			Массив с параметрами запроса
	* 
	* @return	int				Идентификатор запроса
	*/

	function do_insert( $table, $params )
	{
		$fields = $this->compile_insert_string( &$params );

		$id = $this->query_exec( "INSERT INTO {$table} ({$fields['names']}) VALUES ({$fields['values']})" );

		unset( $table, $params, $fields );

		return $id;
	}
	
	/**
	* DELETE-запрос
	* 
	* @param	string			Имя таблицы
	* @param	string			WHERE-условие
	* 
	* @return	int				Идентификатор запроса
	*/

	function do_delete( $table, $where )
	{
		$id = $this->query_exec( "DELETE FROM {$table} WHERE {$where}" );

		unset( $table, $where );

		return $id;
	}

	//-----------------------------------------------

	/**
	* Конструктор запроса
	* 
	* @param	array			Массив с параметрами запроса
	* 
	* @return	void
	*/

	function simple_construct( $a )
	{
		if( $a['select'] )
		{
			$this->simple_select( $a['from'], $a['select'], $a['where'] );
		}

		if( $a['update'] )
		{
			$this->simple_update( $a['update'], $a['set'], $a['where'], $a['lowpro'] );
		}

		if( $a['delete'] )
		{
			$this->simple_delete( $a['delete'], $a['where'] );
		}

		if( $a['order'] )
		{
			$this->simple_order( $a['order'] );
		}

		if( is_array( $a['limit'] ) )
		{
			$this->simple_limit( $a['limit'][0], $a['limit'][1] );
		}
		else if( is_numeric( $a['limit'] ) )
		{
			$this->simple_limit( $a['limit'] );
		}
	}

	/**
	* Простой SELECT-запрос
	* 
	* @param	string			Имя таблицы
	* @param	string			Строка с параметрами запроса
	* @param	string	[opt]	WHERE-условие
	* 
	* @return	void
	*/

	function simple_select( $table, $get, $where="" )
	{
		$this->cur_query .= "SELECT {$get} FROM {$table}";

		if( $where )
		{
			$this->cur_query .= " WHERE ".$where;
		}

		unset( $table, $get, $where );
	}

	/**
	* Простой UPDATE-запрос
	* 
	* @param	string			Имя таблицы
	* @param	string			Строка с параметрами запроса
	* @param	string	[opt]	WHERE-условие
	* 
	* @return	void
	*/

	function simple_update( $table, $set, $where="" )
	{
		$this->cur_query .= "UPDATE {$table} SET {$set}";

		if( $where )
		{
			$this->cur_query .= " WHERE ".$where;
		}

		unset( $table, $set, $where );
	}

	/**
	* Простой DELETE-запрос
	* 
	* @param	string			Имя таблицы
	* @param	string			WHERE-условие
	* 
	* @return	void
	*/

	function simple_delete( $table, $where )
	{
		$this->cur_query .= "DELETE FROM {$table}";

		$this->cur_query .= " WHERE ".$where;

		unset( $table, $where );
	}

	/**
	* Простой ORDER-запрос
	* 
	* @param	array			Массив с параметрами запроса
	* 
	* @return	void
	*/

	function simple_order( $a )
	{
		$this->cur_query .= " ORDER BY {$a}";

		unset( $a );
	}

	/**
	* Простой LIMIT-запрос
	* 
	* @param	int				Номер первого ряда (или количество рядов)
	* @param	int		[opt]	Количество рядов
	* 
	* @return	void
	*/

	function simple_limit( $offset, $limit=0 )
	{
		if( $limit )
		{
			$this->cur_query .= ' LIMIT '.intval( $offset ).','.intval( $limit );
		}
		else
		{
			$this->cur_query .= ' LIMIT '.intval( $offset );
		}

		unset( $offset, $limit );
	}

	/**
	* Выполнение простого запроса
	* 
	* @return	int				Идентификатор запроса
	*/

	function simple_exec()
	{
		if( $this->cur_query == "" ) return NULL;
		
		$id = $this->query_exec( $this->cur_query );

		$this->cur_query = "";

		return $id;
	}

	/**
	* Выполнение простого запроса с возвращением
	* одного ряда
	* 
	* @return	string			Полученный ряд
	*/

	function simple_exec_query( $a )
	{
		if( $a['select'] )
		{
			$a['limit'] = array( 1 );
		}

		$this->simple_construct( $a );

		$id = $this->simple_exec();

		if ( $a['select'] )
		{
			return $this->fetch_row( $id );
		}

		unset( $id );
	}

	//-----------------------------------------------

	/**
	* Формирование INSERT-массива
	* 
	* @param	array			Значения для вставки
	* 
	* @return	array			Сформированный массив значений
	*/

	function compile_insert_string( $data )
	{
		$field_names  = array();
		$field_values = array();

		foreach( $data as $key => $value )
		{
			$field_names[] = $key;
			
			$value = DB_ENGINE == 'mysql' ? mysqli_escape_string( $this->connection_id, $value ) : sqlite_escape_string( $value );
			
			if( DB_ENGINE == 'sqlite2' and !is_numeric( $value ) and $this->engine->config['check_non_latin_chars'] and preg_match( "#[".$this->non_latin_chars."]#i", $value ) )
			{
				$value = "b64_".base64_encode( $value );
			}

			$field_values[] = "'{$value}'";
		}

		return array(	'names'		=> implode( ",", &$field_names ),
						'values'	=> implode( ",", &$field_values ),
						);
	}

	/**
	* Формирование UPDATE-строки
	* 
	* @param	array			Значения для обновления
	* 
	* @return	string			Сформированный массив значений
	*/

	function compile_update_string( $data )
	{
		$return = "";

		foreach( $data as $key => $value )
		{
			if( DB_ENGINE == 'sqlite2' and !is_numeric( $value ) and $this->engine->config['check_non_latin_chars'] and preg_match( "#[".$this->non_latin_chars."]#i", $value ) )
			{
				$value = "b64_".base64_encode( $value );
			}
			
			$value = DB_ENGINE == 'mysql' ? mysqli_escape_string( $this->connection_id, $value ) : sqlite_escape_string( $value );
			
			$params[] = $key."='{$value}'";
		}
		
		return implode( ",", &$params );
	}
}

?>
