<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @link 		http://www.dini.su
* @copyright	2007—2008
*
* @name			Скрипт обновления для версии 1.3.0 beta 1
*/

class update_script_25
{
	/**
	* Функция для выполнения после обновления БД
	* 
	* Выполняет обновление форматов для временных
	* промежутков.
	*
	* @return 	void
	*/
	
	function after_db_update()
	{
		//-----------------------------------------------
		// Получаем информацию о событиях
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'time_id, time_start, time_end',
													'from'		=> 'schedule_time',
													'where'		=> "time_interlace='0'"
													)	);
		$this->engine->DB->simple_exec();
		
		while( $event = $this->engine->DB->fetch_row() )
		{
			$event['time_start'] = date( "m:d:H:i", $event['time_start'] );
			$event['time_end'] = date( "m:d:H:i", $event['time_end'] );
			
			$events[] = $event;
		}
		
		//-----------------------------------------------
		// Обновляем информацию о событиях
		//-----------------------------------------------
		
		if( is_array( $events ) ) foreach( $events as $event )
		{
			$this->engine->DB->do_update( "schedule_time", $event, "time_id='{$event['time_id']}'" );
		}
	}
}

?>