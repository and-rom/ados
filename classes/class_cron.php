<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			Работа с CRON заданием
*/

/**
* Запуск системы
* 
* Проверяет наличие блокирующих файлов и
* запускает систему в режиме работы CRON.
*
* @return	void
*/

$lock_file = dirname( __FILE__ )."/../cron.lock";
$task_file = dirname( __FILE__ )."/../task.lock";
$update_file = dirname( __FILE__ )."/../update.lock";

if( !file_exists( $task_file ) and !file_exists( $update_file ) and file_exists( $lock_file ) and ( filemtime( $lock_file ) - time() ) <= 1 )
{
	unlink( $lock_file );
	
	define( "CRONTAB", TRUE );
	
	$file = fopen( $task_file, "w" );
	
	fclose( $file );
	
	sleep( 3 );
	
	require_once( dirname( __FILE__ )."/../index.php" );
}

/**
* Класс, содержащий функции для работы с CRON
* заданием: проверки параметров закачек и
* расписания
*
* @author   DINI
* @version	1.3.9 (build 74)
*/

class cron
{
	/**
	* Количество свободных слотов
	* 
	* @var 	int
	*/
	
	var $free_slots	= 0;
	
	/**
	* Ограничения скоростей пользователей
	* 
	* @var 	array
	*/
	
	var $speed_limits = array();
	
	/**
	* Файлы, которые должны быть докачаны
	* по расписанию
	* 
	* @var 	array
	*/
	
	var $scheduled_files = array();
	
	/**
	* Конструктор класса (не страндартный PHP)
	* 
	* Вызывает необходимые функции для проверки
	* закачек.
	* 
	* @return	bool	TRUE
	*/
	
	function __class_construct()
	{
		//-----------------------------------------------
		// Перемещаем CRON файлы
		//-----------------------------------------------
		
		$this->engine->move_cron_files();
		
		//-----------------------------------------------
		// Получаем список администраторов
		//-----------------------------------------------
		
		$this->_get_admin_users();
		
		//-----------------------------------------------
		// Очищаем кэш
		//-----------------------------------------------
		
		$this->_clean_system_cache();
		
		//-----------------------------------------------
		// Получаем информацию о скорости каналов
		//-----------------------------------------------
		
		$this->_get_users_bandwidth();
		
		//-----------------------------------------------
		// Проверяем, имеются ли уже закачаные файлы
		//-----------------------------------------------
		
		$this->_check_running_downloads();
		
		//-----------------------------------------------
		// Проверяем, имеются ли завершенные события
		//-----------------------------------------------
		
		$this->_check_running_events();
		
		//-----------------------------------------------
		// Обрабатываем файлы, которые требуется докачать
		//-----------------------------------------------
		
		if( count( $this->scheduled_files ) )
		{
			$this->_parse_scheduled_files();
		}
		
		//-----------------------------------------------
		// Вычисляем количество свободных слотов
		//-----------------------------------------------
		
		$where = $this->engine->config['reserve_paused_slots'] ? "file_state IN('running','paused')" : "file_state='running'";
		
		$downloading = $this->engine->DB->simple_exec_query( array(	'select'	=> 'COUNT(file_id) as count',
																	'from'		=> 'categories_files',
																	'where'		=> $where
																	)	);
																	
		$this->free_slots = $this->engine->config['download_max_amount'] - $downloading['count'];
		
		$this->engine->add_log_event( 4, "ICC_001", array( 'free_slots' => $this->free_slots ) );
		
		//-----------------------------------------------
		// Проверяем, нет ли файлов в очереди
		//-----------------------------------------------
		
		if( $this->free_slots > 0 ) $this->_check_query_downloads();
		
		//-----------------------------------------------
		// Проверяем, нет ли файлов в расписании
		//-----------------------------------------------
		
		$this->_check_scheduled_downloads();
		
		//-----------------------------------------------
		// Запускаем сохраненные в кэше закачки
		//-----------------------------------------------
		
		if( is_array( $this->engine->cache['download']['files'] ) )
		{
			$this->engine->classes['downloader']->download_start_cached();
		}
		
		//-----------------------------------------------
		// Удаляем блокирующий файл
		//-----------------------------------------------
		
		unlink( $this->engine->home_dir."task.lock" );
		
		$this->engine->add_log_event( 3, "ICC_002" );
		
		//-----------------------------------------
		// Записываем накопленные события в журнал
		//-----------------------------------------
		
		$this->engine->insert_db_log();
		
		return TRUE;
	}
	
	/**
	* Очистка кэша
	* 
	* Очищает системный кэш от давно необработанных
	* ссылок.
	* 
	* @return	void
	*/
	
	function _clean_system_cache()
	{
		$time = time() - 300;
		
		$this->engine->DB->do_delete( "system_cache", "cache_added < {$time}" );
		
		$this->engine->add_log_event( 4, "ICC_003" );
	}
	
	/**
	* Проверка каналов для пользователей
	* 
	* Проверяет, имееются ли у пользователей
	* свободные каналы для закачек.
	* 
	* @return	void
	*/
	
	function _get_users_bandwidth()
	{
		$this->engine->DB->simple_construct( array(	'select'	=> 'user_id, user_max_speed',
													'from'		=> 'users_list',
													)	);
		$this->engine->DB->simple_exec();
		
		while( $user = $this->engine->DB->fetch_row() )
		{
			$users[ $user['user_id'] ] = floor( $user['user_max_speed'] / 8 * 1024 );
		}
		
		foreach( $users as $uid => $max_speed )
		{
			$this->engine->cache['download']['speed'][ $uid ]['limit'] = $max_speed;
			
			if( $max_speed == -1 ) continue;
			
			$bandwidth = $this->engine->DB->simple_exec_query( array(	'select'	=> 'SUM( file_dl_bandwidth ) AS total',
																		'from'		=> 'categories_files',
																		'where'		=> "file_state='running' AND file_user='{$uid}'"
																		)	);
																		
			$this->engine->cache['download']['speed'][ $uid ]['free'] = $max_speed - ceil( $bandwidth['total'] );
			
			if( $this->engine->cache['download']['speed'][ $uid ]['free'] < 0 ) $this->engine->cache['download']['speed'][ $uid ]['free'] = 0;
		}
	}
	
	/**
	* Проверка скачиваемых файлов
	* 
	* Проверяет, нет ли среди выполняющихся закачек
	* завершившихся. Если есть, то меняет состояние
	* соответсвующих файлов на 'Готово'.
	* 
	* @return	void
	*/
	
	function _check_running_downloads()
	{
		//-----------------------------------------------
		// Проверяем, имеются ли уже закачаные файлы
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> '*',
													'from'		=> 'categories_files',
													'where'		=> "file_state='running'"
													)	);
		$this->engine->DB->simple_exec();
		
		if( !$this->engine->DB->get_num_rows() ) return;
		
		while( $file = $this->engine->DB->fetch_row() )
		{
			$modules[ $file['file_dl_module'] ][] = $file;
		}
		
		//-----------------------------------------------
		// Подгружаем классы для работы с модулями и
		// файлами
		//-----------------------------------------------
		
		$this->engine->load_module( "class", "downloader", FALSE );
		$this->engine->load_module( "class", "files", FALSE );
		
		//-----------------------------------------------
		// Для файлов каждого модуля выполняем проверку
		//-----------------------------------------------
		
		$array = array( 'file_dl_stop'	=> time(),
						'file_dl_time'	=> NULL,
						'file_dl_speed'	=> NULL,
						'file_dl_left'	=> NULL,
						);
		
		foreach( $modules as $mid => $files )
		{
			if( $this->engine->classes['downloader']->load_module( "", $mid ) === FALSE )
			{
				continue;
			}
			
			foreach( $files as $file )
			{
				if( $this->engine->classes['downloader']->download_is_running( $file ) === FALSE )
				{
					if( $this->engine->classes['downloader']->error )
					{
						$this->engine->cache['download']['speed'][ $file['file_user'] ]['free'] += $file['file_dl_bandwidth'];
						
						$this->engine->DB->do_update( "categories_files", array( 'file_state' => 'error', 'file_error' => $this->engine->classes['downloader']->error, 'file_dl_stop' => time() ), "file_id='{$file['file_id']}'" );
					}
					else 
					{
						//-----------------------------------------------
						// Обновляем информацию о скорости канала
						//-----------------------------------------------
						
						if( $this->engine->cache['download']['speed'][ $file['file_user'] ]['limit'] != -1 )
						{
							$this->engine->cache['download']['speed'][ $file['file_user'] ]['free'] += $file['file_bandwidth'];
							
							if( $this->engine->cache['download']['speed'][ $file['file_user'] ]['free'] > $this->engine->cache['download']['speed'][ $file['file_user'] ]['limit'] )
							{
								$this->engine->cache['download']['speed'][ $file['file_user'] ]['free'] = $this->engine->cache['download']['speed'][ $file['file_user'] ]['limit'];
							}
						}
						
						//-----------------------------------------------
						// Создаем отсутствующие директории
						//-----------------------------------------------
						
						if( $this->engine->config['create_missing_cats'] ) $this->_check_save_path( $file['file_path'] );
						
						//-----------------------------------------------
						// Перемещаем файл
						//-----------------------------------------------
						
						$from = $this->engine->config['save_path']."/_tmp/{$file['file_id']}_{$file['file_user']}_{$file['file_dl_id']}.ados";
						$to = $file['file_path']."/".$file['file_name'];
						
						if( !rename( $from, $to ) )
						{
							$array['file_state'] = 'error';
							$array['file_error'] = 3;
							
							$this->engine->add_log_event( 1, "ECC_001", array( 'rename_from' => $from, 'rename_to' => $to ) );
							
							$this->engine->DB->do_update( "categories_files", $array, "file_id='{$file['file_id']}'" );
							
							continue;
						}
						
						//-----------------------------------------------
						// Обновляем состояние файла
						//-----------------------------------------------
						
						$array['file_state'] = "done";
						
						$this->engine->DB->do_update( "categories_files", $array, "file_id='{$file['file_id']}'" );
						
						//-----------------------------------------------
						// Удаляем лог
						//-----------------------------------------------
						
						@unlink( $this->engine->config['save_path']."/_log/{$file['file_dl_id']}.log" );
						
						//-----------------------------------------------
						// Делаем запись в лог
						//-----------------------------------------------
						
						$this->engine->add_log_event( 3, "ICC_004", array( 'file_name' => $file['file_name'], 'file_id' => $file['file_id'], 'file_dl_id' => $file['file_dl_id'] ) );
					}
				}
				else if( $this->engine->config['scheduled_downloads'] and $this->engine->config['scheduled_downloads_pause'] != 'continue' and $this->engine->classes['files']->_check_limits( $file['file_user'] ) === FALSE )
				{
					//-----------------------------------------------
					// Приостанавливаем закачку
					//-----------------------------------------------
					
					if( in_array( $this->engine->config['scheduled_downloads_pause'], array( 'pause_cancel', 'pause_continue', 'continue_cancel', 'continue_continue' ) ) )
					{
						if( $this->engine->classes['files']->change_download_state( 'pause', $file['file_id'], $mid ) !== FALSE )
						{
							$paused = TRUE;
							
							$this->engine->add_log_event( 3, "ICC_006", array( 'file_name' => $file['file_name'], 'file_id' => $file['file_id'], 'file_dl_id' => $file['file_dl_id'] ) );
						}
						else 
						{
							$paused = FALSE;
							
							$this->engine->add_log_event( 1, "ECC_002", array( 'file_name' => $file['file_name'], 'file_id' => $file['file_id'], 'file_dl_id' => $file['file_dl_id'], 'desc' => $this->engine->classes['files']->error ) );
						}
					}
					
					//-----------------------------------------------
					// Отменяем закачку
					//-----------------------------------------------
					
					if( $this->engine->config['scheduled_downloads_pause'] == 'cancel' or ( in_array( $this->engine->config['scheduled_downloads_pause'], array( 'pause_cancel', 'continue_cancel' ) ) and $paused === FALSE ) )
					{
						if( $this->engine->classes['files']->change_download_state( 'stop', $file['file_id'], $mid ) !== FALSE )
						{
							$this->engine->add_log_event( 3, "ICC_007", array( 'file_name' => $file['file_name'], 'file_id' => $file['file_id'], 'file_dl_id' => $file['file_dl_id'] ) );
						}
						else 
						{
							$this->engine->add_log_event( 1, "ECC_003", array( 'file_name' => $file['file_name'], 'file_id' => $file['file_id'], 'file_dl_id' => $file['file_dl_id'], 'desc' => $this->engine->classes['files']->error ) );
						}
					}
					
					//-----------------------------------------------
					// Ставим закачку в очередь на возобновление
					//-----------------------------------------------
					
					if( in_array( $this->engine->config['scheduled_downloads_pause'], array( 'continue_cancel', 'continue_continue' ) ) and $paused === TRUE )
					{
						$this->scheduled_files[ $file['file_id'] ] = $file['file_user'];
					}
				}
			}
			
			$this->engine->classes['downloader']->unload_module();
		}
	}
	
	/**
	* Проверка выполняемых событий
	* 
	* Проверяет, нет ли среди выполняющихся событий
	* завершившихся. Если есть, то меняет состояние
	* соответсвующих событий на 'Завершено'.
	* Для завершенных чередующихся событий высчитывает
	* время следующего запуска.
	* 
	* @return	void
	*/
	
	function _check_running_events()
	{
		$files['running'] = $interlace = array();
		
		//-----------------------------------------------
		// Подгружаем класс для работы с файлами
		//-----------------------------------------------
		
		$this->engine->load_module( "class", "files", FALSE );
		
		//-----------------------------------------------
		// Проверяем, закачки каких файлов должны
		// происходить по расписанию
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'event_id, event_files, event_type, event_interlace, event_user',
													'from'		=> 'schedule_events',
													'where'		=> "event_state='running'"
													)	);
		$this->engine->DB->simple_exec();
		
		if( !$this->engine->DB->get_num_rows() ) return;
		
		while( $event = $this->engine->DB->fetch_row() )
		{
			$files['event'] = explode( ",", $event['event_files'] );
			
			$files['running'] = array_merge( $files['running'], array_diff( $files['event'], $files['running'] ) );
			
			$events[ $event['event_id'] ] = $files['event'][0] ? $files['event'] : NULL;
			
			if( $event['event_type'] ) $interlace[ $event['event_id'] ] = $event;
		}
		
		if( count( $files['running'] ) and $files['running'][0] )
		{
			//-----------------------------------------------
			// Подключаем модуль для работы с файлами
			//-----------------------------------------------
			
			$this->engine->load_module( "class", "files", FALSE );
			
			//-----------------------------------------------
			// Проверяем, какие файлы уже закачаны
			//-----------------------------------------------
			
			$this->engine->DB->simple_construct( array(	'select'	=> 'file_id',
														'from'		=> 'categories_files',
														'where'		=> "file_state != 'running' AND file_id IN('".implode( "','", $files['running'] )."')"
														)	);
			$this->engine->DB->simple_exec();
			
			if( !$this->engine->DB->get_num_rows() ) return;
			
			unset( $files['running'] );
			
			while( $file = $this->engine->DB->fetch_row() )
			{
				$files['done'][] = $file['file_id'];
			}
		}
		
		foreach( $events as $eid => $files['event'] )
		{
			$still_running = FALSE;
			
			if( is_array( $files['event'] ) and $files['event'][0] ) foreach( $files['event'] as $file )
			{
				if( !in_array( $file, $files['done'] ) )
				{
					$still_running = TRUE;
					
					break;
				}
			}
			
			//-----------------------------------------------
			// Если все файлы, ассоциированные с событием,
			// закачаны, то обновляем его состояние и
			// высчитываем следующее время запуска
			//-----------------------------------------------
			
			if( $still_running === FALSE )
			{
				$this->engine->DB->do_update( "schedule_events", array( "event_state" => 'done' ), "event_id='{$eid}'" );
				
				if( array_key_exists( $eid, $interlace ) )
				{
					$date = explode( ":", $interlace[ $eid ]['event_interlace'] );
					
					//-----------------------------------------------
					// Определяем день запуска
					//-----------------------------------------------
					
					switch( $date[0] )
					{
						case '0':
							$wday = 'Sunday';
							break;
							
						case '1':
							$wday = 'Monday';
							break;
							
						case '2':
							$wday = 'Tuesday';
							break;
							
						case '3':
							$wday = 'Wednesday';
							break;
							
						case '4':
							$wday = 'Thursday';
							break;
							
						case '5':
							$wday = 'Friday';
							break;
							
						case '6':
							$wday = 'Saturday';
							break;
					}
					
					//-----------------------------------------------
					// Определяем дату запуска
					//-----------------------------------------------
					
					$relative = "{$wday} {$date[1]}:{$date[2]}:00";
					
					$next_run = strtotime( "This {$relative}" );
					
					if( $next_run < time() ) $next_run = strtotime( "Next {$relative}" );
					
					//-----------------------------------------------
					// Добавляем новое событие
					//-----------------------------------------------
					
					$insert = array( 'event_interlace'	=> $interlace[ $eid ]['event_interlace'],
									 'event_user'		=> $interlace[ $eid ]['event_user'],
									 'event_time'		=> $next_run,
									 'event_type'		=> 1,
									 'event_state'		=> $this->engine->classes['files']->_check_limits( $interlace[ $eid ]['event_user'], $next_run ) === FALSE ? 'blocked' : 'query',
									 );
					
					$this->engine->DB->do_insert( "schedule_events", $insert );
					
					$this->engine->add_log_event( 3, "ICC_005", $insert );
				}
			}
		}
	}
	
	/**
	* Обработка файлов, требующих докачки
	* 
	* Проверяет, возможно ли соотнести файлы с
	* будущими событиями для соответствующих пользователей.
	* 
	* @return	void
	*/
	
	function _parse_scheduled_files()
	{
		//-----------------------------------------------
		// Ищем подходящие события
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'event_id, event_user, event_files',
													'from'		=> 'schedule_events',
													'where'		=> "event_state='query' AND event_user IN('".implode( "','", $this->scheduled_files )."')",
													'order'		=> 'event_time'
													)	);
		$this->engine->DB->simple_exec();
		
		if( !$this->engine->DB->get_num_rows() ) return FALSE;
		
		//-----------------------------------------------
		// Составляем массив событий
		//-----------------------------------------------
		
		$events = array();
		
		while( $event = $this->engine->DB->fetch_row() )
		{
			if( !array_key_exists( $event['event_user'], $events ) )
			{
				$event['event_files'] = $event['event_files'] ? explode( ",", $event['event_files'] ) : array();
				
				$events[ $event['event_user'] ] = $event;
			}
		}
		
		//-----------------------------------------------
		// Ассоциируем файлы с событиями
		//-----------------------------------------------
		
		foreach( $this->scheduled_files as $event_user => $file_id ) if( array_key_exists( $event_user, $events ) )
		{
			$events[ $event_user ]['event_files'][] = $file_id;
			
			$this->engine->add_log_event( 3, "ICC_008", array( 'file_id' => $file_id, 'event_id' => $events[ $event_user ]['event_id'] ) );
			
			$events_to_update[ $event_user ] = $events[ $event_user ];
		}
		
		//-----------------------------------------------
		// Обновляем события
		//-----------------------------------------------
		
		if( is_array( $events_to_update ) ) foreach( $events_to_update as $event )
		{
			$event['event_files'] = implode( ",", $event['event_files'] );
			
			$this->engine->DB->do_update( "schedule_events", array( 'event_files' => $event['event_files'] ), "event_id='{$event['event_id']}'" );
		}
	}
	
	/**
	* Проверка очереди файлов на закачку
	* 
	* Проверяет очередь файлов и запускает их на
	* закачку в соответствующем порядке.
	* 
	* @return	void
	*/
	
	function _check_query_downloads()
	{
		$time_now = time();
		
		//-----------------------------------------------
		// Получаем сведения о файлах из очереди
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> '*',
													'from'		=> 'categories_files',
													'where'		=> "file_state='query' AND file_dl_last_start IS NOT NULL",
													'limit'		=> $this->free_slots,
													'order'		=> 'file_priority DESC, file_dl_last_start, file_id'
													)	);
		$this->engine->DB->simple_exec();
		
		if( !$this->engine->DB->get_num_rows() ) return;
		
		while( $file = $this->engine->DB->fetch_row() )
		{
			$modules[ $file['file_dl_module'] ][] = $file;
		}
		
		//-----------------------------------------------
		// Подгружаем класс для работы с файлами
		//-----------------------------------------------
		
		$this->engine->load_module( "class", "files", FALSE );
		
		//-----------------------------------------------
		// Подгружаем класс для работы с модулями
		//-----------------------------------------------
		
		$this->engine->load_module( "class", "downloader", FALSE );
		
		//-----------------------------------------------
		// Для файлов каждого модуля начинаем закачку
		//-----------------------------------------------
		
		foreach( $modules as $mid => $files )
		{
			if( $this->engine->classes['downloader']->load_module( "", $mid ) === FALSE )
			{
				continue;
			}
			
			usort( $files, array( "cron", "_sort_by_id" ) );
			
			foreach( $files as $file )
			{
				if( $this->engine->classes['files']->_check_limits( $file['file_user'], $time_now ) === FALSE )
				{
					$this->engine->DB->do_update( "categories_files", array( "file_state" => "blocked" ), "file_id='{$file['file_id']}'" );
				}
				else 
				{
					$this->engine->member['user_admin'] = in_array( $file['file_user'], $this->engine->cache['cron']['admins'] ) ? 1 : 0;
					
					if( !$file['file_dl_left'] and $this->engine->classes['downloader']->download_start( $file ) !== FALSE )
					{
						--$this->free_slots;
					}
					else if( $file['file_dl_left'] and $this->engine->classes['downloader']->download_continue( $file ) !== FALSE )
					{
						--$this->free_slots;
					}
					else 
					{
						$this->engine->cache['download']['speed'][ $file['file_user'] ]['free'] += $file['file_dl_bandwidth'];
						
						$this->engine->DB->do_update( "categories_files", array( 'file_state' => "error" ), "file_id='{$file['file_id']}'" );
					}
				}
			}
			
			$this->engine->classes['downloader']->unload_module();
		}
		
	}
	
	/**
	* Проверка файлов в расписании
	* 
	* Проверяет, не пора ли запускать закачки по расписанию.
	* 
	* @return	void
	*/
	
	function _check_scheduled_downloads()
	{
		$time_now = time();
		
		$files = array();
		
		//-----------------------------------------------
		// Получаем сведения о необходимых для запуска событиях
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'event_id, event_files',
													'from'		=> 'schedule_events',
													'where'		=> "event_state='query' AND event_time <= ".$time_now
													)	);
		$this->engine->DB->simple_exec();
		
		if( !$this->engine->DB->get_num_rows() ) return;
		
		while( $event = $this->engine->DB->fetch_row() )
		{
			$events[] = $event['event_id'];
			
			if( $event['event_files'] ) $files = array_merge( $files, array_diff( explode( ",", $event['event_files'] ), $files ) );
		}
		
		//-----------------------------------------------
		// Обновляем состояние событий
		//-----------------------------------------------
		
		$this->engine->DB->do_update( "schedule_events", array( 'event_state' => 'running' ), "event_id IN('".implode( "','", $events )."')" );
		
		if( !count( $files ) or !$files[0] ) return;
		
		//-----------------------------------------------
		// Формируем список используемых модулей
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> '*',
													'from'		=> 'categories_files',
													'where'		=> "file_id IN('".implode( "','", $files )."') AND file_state IN('idle','paused')",
													'order'		=> 'file_priority DESC, file_dl_last_start'
													)	);
		$this->engine->DB->simple_exec();
		
		if( !$this->engine->DB->get_num_rows() ) return;
		
		$reserved_slots = 0;
		
		while( $file = $this->engine->DB->fetch_row() )
		{
			$modules[ $file['file_dl_module'] ][] = $file;
			
			if( $this->engine->config['reserve_paused_slots'] and $file['file_state'] == "paused" ) $reserved_slots++;
		}
		
		unset( $file, $files );
		
		//-----------------------------------------------
		// Обновляем количество свободных слотов
		//-----------------------------------------------
		
		if( $this->engine->config['reserve_paused_slots'] and $reserved_slots > 0 )
		{
			$this->free_slots = $reserved_slots > $this->engine->config['download_max_amount']
							  ? $this->engine->config['download_max_amount']
							  : $reserved_slots;
		}
		
		//-----------------------------------------------
		// Подгружаем класс для работы с файлами
		//-----------------------------------------------
		
		$this->engine->load_module( "class", "files", FALSE );
		
		//-----------------------------------------------
		// Подгружаем класс для работы с модулями
		//-----------------------------------------------
		
		$this->engine->load_module( "class", "downloader", FALSE );
		
		//-----------------------------------------------
		// Для файлов каждого модуля начинаем закачку
		//-----------------------------------------------
		
		foreach( $modules as $mid => $files )
		{
			if( $this->engine->classes['downloader']->load_module( "", $mid ) === FALSE )
			{
				continue;
			}
			
			usort( $files, array( "cron", "_sort_by_id" ) );
			
			foreach( $files as $file )
			{
				if( $this->free_slots <= 0 )
				{
					$this->engine->DB->do_update( "categories_files", array( 'file_state' => 'query', 'file_dl_last_start' => $time_now ), "file_id='{$file['file_id']}'" );
					
					continue;
				}
				
				$this->engine->member['user_admin'] = in_array( $file['file_user'], $this->engine->cache['cron']['admins'] ) ? 1 : 0;
				
				if( $file['file_state'] == 'idle' and $this->engine->classes['downloader']->download_start( $file ) !== FALSE )
				{
					--$this->free_slots;
				}
				else if( $file['file_state'] == 'paused' and $this->engine->classes['downloader']->download_continue( $file ) !== FALSE )
				{
					--$this->free_slots;
				}
				else 
				{
					$this->engine->cache['download']['speed'][ $file['file_user'] ]['free'] += $file['file_dl_bandwidth'];
					
					$this->engine->DB->do_update( "categories_files", array( 'file_state' => "error" ), "file_id='{$file['file_id']}'" );
				}
			}
			
			$this->engine->classes['downloader']->unload_module();
		}
	}
	
	//-----------------------------------------------
	
	/**
	* Получение списка администраторов
	* 
	* Получает список идентификаторв пользователей, имеющих
	* статус администратора, и сохраняет их в кэш.
	* 
	* @return	void
	*/
	
	function _get_admin_users()
	{
		$this->engine->cache['cron']['admins'] = array();
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'user_id',
													'from'		=> 'users_list',
													'where'		=> "user_admin=1"
													)	);
		$this->engine->DB->simple_exec();
		
		while( $user = $this->engine->DB->fetch_row() )
		{
			$this->engine->cache['cron']['admins'][] = $user['user_id'];
		}
	}
	
	/**
	* Проверка пути для сохранения файла.
	* 
	* Проверяет наличие директорий для указанного
	* пути сохранения файла. В случае, если директории
	* нет, пытается ее создать.
	* 
	* @return	bool			Результат выполнения операции
	*/
	
	function _check_save_path( $path )
	{
		if( !$path ) return FALSE;
		
		$dirs = explode( "/", $path );
		
		unset( $path );
		
		foreach( $dirs as $piece )
		{
			$path .= $piece."/";
			
			if( !is_dir( $path ) and !mkdir( $path ) ) return FALSE;
		}
		
		return TRUE;
	}
	
	/**
    * Сортировка закачек по идентификатору файла
    * 
    * Сортирует закачки в зависимости от значения
    * идентификатора закачиваемого файла.
    * 
    * @param 	array			Параметры первой закачки
    * @param 	array			Параметры второй закачки
    *
    * @return	int				Результат сравнения
    */
	
	function _sort_by_id( $a, $b )
	{
		if( $a['file_dl_last_start'] == $b['file_dl_last_start'] ) return strcmp( $a['file_id'], $b['file_id'] );
		
		return 0;
	}

}

?>
