<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			Работа с файлами
*/

/**
* Класс, содержащий функции для работы
* с файлами.
*
* @author	DINI
* @version	1.3.9 (build 74)
*/

class files
{
	/**
	* Родительский класс
	*
	* @var object
	*/

	var $parent = NULL;
	
	/**
	* Сообщение об ошибке
	*
	* @var string
	*/

	var $error	= "";
	
	/**
	* Категории пользователя
	*
	* @var array
	*/

	var $cats	= array( 'unsorted'	=> array(),
						 'sorted'	=> array(),
						 );
						 
	/**
	* Шаблон ссылок для закачки и наборы
	* зарезервированных символов
	*
	* @var array
	*/
						 
	var $patterns	= array(	'links'		=> "",
								'illegal'	=> "><\?\*:|%\"\\\\",
								);
	
	/**
	* Конструктор класса (не страндартный PHP)
	* 
	* Подгружает языковые строки и шаблон.
	* Всегда возвращает TRUE.
	* 
	* @return	bool			Отметка об успешном выполнении
	*/
	
	function __class_construct()
	{
		$this->engine->load_lang( "files" );
		$this->engine->load_skin( "files" );
		
		return TRUE;
	}
	
	/**
    * Окно с параметрами файла
    * 
    * Получает информацию о параметрах файла
    * и его текущем состоянии и помещает их
    * в таблицу.
    *
    * @return	void
    */
	
	function properties_window()
	{		
		$type = $this->engine->input['type'] == "file_add" ? "add" : "edit";
		
		//-----------------------------------------------
		// Заменяем спецсимвол пробела
		//-----------------------------------------------
		
		$this->engine->input['link'] 	  = str_replace( "%20", " ", $this->engine->input['link'] );
		$this->engine->input['file_link'] = str_replace( "%20", " ", $this->engine->input['file_link'] );
		$this->engine->input['file_name'] = str_replace( "%20", " ", $this->engine->input['file_name'] );
		
		//-----------------------------------------------
		// Подключаем класс работы с закачками
		//-----------------------------------------------
			
		if( $this->engine->load_module( "class", "downloader" ) === FALSE )
		{
			$this->error = $this->engine->lang['error_cant_load_module']."downloader";
			return FALSE;
		}
			
		$this->engine->classes['downloader']->_get_auth_info();
		
		//-----------------------------------------------
		// Получаем параметры файла
		//-----------------------------------------------
		
		if( $type != 'add' )
		{
			$from = DB_ENGINE == "mysql"
				  ? "( categories_files f, users_list u ) LEFT JOIN categories_list c ON (c.cat_id=f.file_cat)"
				  : "categories_files f, users_list u LEFT JOIN categories_list c ON (c.cat_id=f.file_cat)";
			
			$file = $this->engine->DB->simple_exec_query( array(	'select'	=> 'f.*, u.user_name',
																	'from'		=> &$from,
																	'where'		=> "f.file_id='{$this->engine->input['id']}' AND u.user_id=f.file_user"
																	)	);
			
			if( !is_numeric( $file['file_id'] ) )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_file_id'], 'Log' => array( 'level' => 1, 'code' => "ECF_001" ) ) );
			}
			
			$file['file_blocked'] = in_array( $file['file_state'], array( "running", "paused" ) ) ? 1 : 0;
			
			if( $file['user_name'] )
			{
				$file['user_real'] = $file['user_name'];
				$file['user_name'] = preg_replace( "#\W#", "_", strtolower( $file['user_name'] ) );
			}
			else 
			{
				$file['user_real'] = $this->engine->lang['shared'];
				$file['user_name'] = $file['user_name'] = "_all";
			}
			
			//-----------------------------------------------
			// Обновляем информацию о состоянии файла
			//-----------------------------------------------
			
			if( $file['file_state'] == 'running' )
			{
				$this->engine->classes['downloader']->load_module( "", &$file['file_dl_module'] );
				
				foreach( $this->engine->classes['downloader']->file as $name => $value ) if( $value ) $file[ $name ] = $value;
				
				$this->engine->classes['downloader']->update_download_state( $file );
				
				if( $this->engine->config['reload_time'] )
				{
					$array['Function_1'] = "timer_id = setInterval( \"ajax_update_state( {$file['file_id']}, 'download' )\", ".( 1000 * $this->engine->config['reload_time'] )." );";
				}
			}
			
			//-----------------------------------------------
			// Обрабатваем информацию
			//-----------------------------------------------
			
			if( is_numeric( $file['file_size'] ) and is_numeric( $file['file_dl_left'] ) )
			{
				if( $file['file_state'] == 'done' )
				{
					$file['file_dl_left'] = 0;
					$file['file_dl_time'] = 0;
				}
				
				$file['file_dl_done']    = $file['file_size'] - $file['file_dl_left'];
				$file['file_dl_percent'] = round( $file['file_dl_done'] / $file['file_size'] * 100, 2 );
				$file['file_time_used']  = $file['file_dl_stop'] ? $file['file_dl_stop'] - $file['file_dl_start'] : time() - $file['file_dl_start'];
				
				$file['file_done'] = $this->engine->skin['files']->progress_info( $file['file_time_used'], &$file['file_dl_done'], $file['file_dl_percent'] );
				$file['file_left'] = $this->engine->skin['files']->progress_info( $file['file_dl_time'], $file['file_dl_left'], 100 - $file['file_dl_percent'] );
				
				$file['file_speed'] = $this->engine->convert_file_size( $file['file_speed'] ).$this->engine->lang['per_sec'];
			}
			else 
			{
				$file['file_done'] = $this->engine->skin['files']->progress_info();
				$file['file_left'] = $this->engine->skin['files']->progress_info();
				
				$file['file_speed'] = "--";
			}
			
			$file['cat_id'] = &$file['file_cat'];
			
			//-----------------------------------------------
			// Определяем права на действия с файлами
			//-----------------------------------------------
			
			$rights['view_link']	 = ( $file['file_cat'] != 0 or $this->engine->member['user_admin'] or $file['file_user'] == $this->engine->member['user_id'] or $this->engine->config['shared_view_link']   ) ? 1 : 0;
			$rights['view_owner']	 = ( $file['file_cat'] != 0 or $this->engine->member['user_admin'] or $file['file_user'] == $this->engine->member['user_id'] or $this->engine->config['shared_view_owner']  ) ? 1 : 0;
			$rights['can_control']	 = ( $file['file_cat'] != 0 or $this->engine->member['user_admin'] or $file['file_user'] == $this->engine->member['user_id'] or $this->engine->config['shared_can_control'] ) ? 1 : 0;
			$rights['change_path']	 = ( $file['file_cat'] != 0 or $this->engine->member['user_admin'] or $file['file_user'] == $this->engine->member['user_id'] or $this->engine->config['shared_can_control'] ) ? 1 : 0;
			$rights['change_desc']	 = ( $file['file_cat'] != 0 or $this->engine->member['user_admin'] or $file['file_user'] == $this->engine->member['user_id'] or $this->engine->config['shared_change_desc'] ) ? 1 : 0;
			$rights['change_link']	 = ( $this->engine->member['user_admin'] or $file['file_user'] == $this->engine->member['user_id'] ) ? 1 : 0;
			$rights['select_module'] = ( $this->engine->member['user_admin'] or $this->engine->config['can_select_module'] ) ? 1 : 0;
			$rights['set_priority']  = ( $this->engine->member['user_admin'] or $this->engine->config['can_set_priority'] ) ? 1 : 0;
		}
		else 
		{
			if( $this->engine->config['use_share_cats'] and ( !$this->engine->member['user_admin'] and $this->engine->input['id'] != 0 ) )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_shared_downloads_only'], 'Log' => array( 'level' => 2, 'code' => "WCF_001" ) ) );
			}
			else if( !$this->engine->member['user_admin'] and !in_array( $this->engine->input['id'], array( 0, $this->engine->member['user_id'] ) ) )
			{
				$this->engine->classes['output']->generate_xml_output( array(	'Message'	=> &$this->engine->lang['error_no_rights'],
																				'Log'		=> array(	'level'	=> 1,
																										'code'	=> "ECF_002",
																										'misc'	=> array( 'input_id' => $this->engine->input['id'], 'user_id' => $this->engine->member['user_id'] )
																										)	)	);
			}
			
			//-----------------------------------------------
			// Определяем, нет ли ссылок на файл в кэше
			//-----------------------------------------------
			
			if( $this->engine->input['cached'] and preg_match( "#^[a-zA-Z0-9]{32}$#", $this->engine->input['uid'] ) )
			{
				if( $this->engine->input['apply'] )
				{
					$cached['cache_id'] =& $this->engine->input['cid'];
					
					if( $this->engine->input['file_cache_start'] ) $this->engine->input['control'] = 'run';
				}
				else 
				{
					$cached = $this->engine->DB->simple_exec_query( array(	'select'	=> 'cache_id, cache_value, cache_priority',
																			'from'		=> 'system_cache',
																			'where'		=> "cache_name='links_list' AND cache_uid='{$this->engine->input['uid']}'",
																			'order'		=> 'cache_priority DESC, cache_id'
																			)	);
																			
					if( !$cached['cache_id'] ) $this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_cache_is_empty'], 'Log' => array( 'level' => 2, 'code' => "WCF_002" ) ) );
				
					$info = unserialize( stripslashes( $cached['cache_value'] ) );
					
					$this->engine->input['link'] =& $info['link'];
					
					$file['file_desc'] 		=& $info['desc'];
					$file['file_priority'] 	=& $cached['cache_priority'];
				}
			}
			
			//-----------------------------------------------
			// Обрабатываем параметры файла
			//-----------------------------------------------
			
			if( $this->engine->input['apply'] ) $this->engine->input['id'] =& $this->engine->input['auser'];
			
			$file['file_id']	= 0;
			$file['file_link']  = &$this->engine->input['link'];
			$file['file_user']  = is_numeric( $this->engine->input['id'] ) ? $this->engine->input['id'] : $this->engine->member['user_id'];
			$file['file_name']  = preg_replace( "#[".$this->patterns['illegal']."]#", "_", substr( $file['file_link'], strrpos( $file['file_link'], "/" ) + 1 ) );
			$file['file_ext']   = strtolower( substr( $file['file_name'], strrpos( $file['file_name'], "." ) + 1 ) );
			$file['file_done']  = $this->engine->skin['files']->progress_info();
			$file['file_left']  = $this->engine->skin['files']->progress_info();
			$file['file_speed'] = "--";
			
			$file['file_name']  = substr( $file['file_link'], strrpos( $file['file_link'], "/" ) + 1 );
			$file['file_name']  = ( strpos( $file['file_name'], "." === FALSE ) or preg_match( "#[?=&]#i", $file['file_name'] ) ) ? "" : preg_replace( "#[".$this->patterns['illegal']."]#", "_", $file['file_name'] );
			
			if( $this->engine->input['id'] )
			{
				$file['user_name'] = $file['user_real'] = $this->engine->member['user_name'];
				$file['user_name'] = preg_replace( "#\W#", "_", strtolower( $file['user_name'] ) );
			}
			else 
			{
				$file['user_real'] = $this->engine->lang['shared'];
				$file['user_name'] = $file['user_name'] = "_all";
			}
			
			//-----------------------------------------------
			// Определяем права на действия с файлами
			//-----------------------------------------------
			
			$rights['view_link']   = 1;
			$rights['can_control'] = 1;
			$rights['change_path'] = 1;
			$rights['change_desc'] = 1;
			$rights['change_link'] = 1;
			
			$rights['set_priority']  = ( $this->engine->member['user_admin'] or $this->engine->config['can_set_priority']  ) ? 1 : 0;
			$rights['select_module'] = ( $this->engine->member['user_admin'] or $this->engine->config['can_select_module'] ) ? 1 : 0;
		}
		
		//-----------------------------------------------
		// Получаем список модулей
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'm.module_id, m.module_name, m.module_default, module_version, v.version_min',
													'from'		=> 'modules_list m LEFT JOIN modules_versions v ON(v.version_module=m.module_key)',
													'where'		=> "m.module_enabled=1"
													)	);
		$this->engine->DB->simple_exec();

		while( $module = $this->engine->DB->fetch_row() )
		{
			$modules[ $module['module_id'] ] = $module['module_name'];
			
			if( $module['module_default'] ) $default_module = $module['module_id'];
		}
		
		if( !is_array( $modules ) or !count( $modules ) ) $this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_modules'], 'Log' => array( 'level' => 1, 'code' => "ECF_003" ) ) );
			
		$events[0] = &$this->engine->lang['file_no_event'];
		
		if( !array_key_exists( $file['file_dl_module'], $modules ) ) $file['file_dl_module'] = $default_module;
		
		//-----------------------------------------------
		// Получаем список событий
		//-----------------------------------------------
		
		$shared_events = array();
			
		$this->engine->DB->simple_construct( array(	'select'	=> 'event_id, event_time, event_user, event_files',
													'from'		=> 'schedule_events',
													'where'		=> "event_state='query' AND event_user IN(0,'{$file['file_user']}')",
													'order'		=> 'event_time'
													)	);
		$this->engine->DB->simple_exec();
		
		while( $event = $this->engine->DB->fetch_row() )
		{
			$events[ $event['event_id'] ] = $this->engine->get_date( $event['event_time'], "LONG" );
				
			if( !$event['event_user'] )
			{
				$events[ $event['event_id'] ] .= " ".$this->engine->lang['file_shared_event'];
				
				$shared_events[] = $event['event_id'];
			}
			
			$events_files[ $event['event_id'] ] = $event['event_files'] ? explode( ",", $event['event_files'] ) : array();
			$events_times[ $event['event_id'] ] = $event['event_time'];
				
			if( $file['file_id'] and in_array( $file['file_id'], explode( ",", $event['event_files'] ) ) ) $file['event_id'] = $event['event_id'];
		}
		
		//-----------------------------------------------
		// Составляем список категорий
		//-----------------------------------------------
		
		$dropdown['shown'][0] = &$this->engine->lang['file_no_cat'];
		
		$this->engine->DB->simple_construct( array(	'select'	=> '*',
													'from'		=> "categories_list c LEFT JOIN users_list u ON (u.user_id=c.cat_user)",
													'where'		=> "c.cat_user='{$this->engine->input['id']}'"
													)	);
		$this->engine->DB->simple_exec();
		
		while( $cat = $this->engine->DB->fetch_row() )
		{
			$this->cats['unsorted'][ $cat['cat_id'] ] = $cat;
		}
		
		if( count( $this->cats['unsorted'] ) )
		{
			if( $this->engine->config['check_non_latin_chars'] )
			{
				usort( $this->cats['unsorted'], array( "files", "_sort_cats_basic" ) );
			}
			
			$this->_sort_cats();
			
			foreach( $this->cats['sorted'] as $cid => $cat )
			{
				$cat['cat_children'] = $this->cats['sorted'][ $cid + 1 ]['cat_root'] == $cat['cat_id'] ? 1 : 0;
				$cat['cat_down']	 = $this->cats['sorted'][ $cid + 1 ] ? 1 : 0;
					
				$d_cid = $cid + 1;
					
				while( $this->cats['sorted'][ $d_cid ] )
				{
					if( $this->cats['sorted'][ $d_cid ]['cat_root'] == $cat['cat_root'] )
					{
						$cat['cat_relation'] = 1;
						break;
					}
						
					$d_cid++;
				}
					
				$cat['cat_relation'] = $cat['cat_relation'] ? 1 : 0;
				$cat['cat_cid'] = $cid;
					
				$this->cats['sorted'][ $cid ] = $cat;
				
				$dropdown['shown'][ $cat['cat_id'] ] = $this->_get_category_name( &$cat );
				
				//-----------------------------------------------
				// Определяем категорию и путь для сохранения
				// добавляемого файла
				//-----------------------------------------------
				
				$cat['user_name'] = $cat['user_name'] ? $cat['user_name'] : "_all";
				$cat['cat_path'] = preg_replace( "#^".$this->engine->config['save_path']."/*".$cat['user_name']."/*#i", "", $cat['cat_path'] );
				
				if( $type == 'add' and !$file['file_cat'] and in_array( $file['file_ext'], explode( " ", $cat['cat_types'] ) ) )
				{
					$file['cat_id'] = $cat['cat_id'];
					$file['file_path'] = $cat['cat_path'];
				}
				
				$dropdown['hidden'][ $cat['cat_id'] ] = $cat['cat_path'];
			}
		}
		
		$dropdown['hidden'][0] = "";
		
		//-----------------------------------------------
		// Составляем список приоритетов
		//-----------------------------------------------
		
		$priority = array(	0 => $this->engine->lang['file_priority_low'],
							1 => $this->engine->lang['file_priority_med'],
							2 => $this->engine->lang['file_priority_high'],
							);
		
		//-----------------------------------------------
		// Сохраняем измененные параметры
		//-----------------------------------------------
		
		if( $this->engine->input['apply'] )
		{
			if( $rights['can_control'] and $this->engine->input['file_name'] )
			{
				if( !preg_match( "#^[^".$this->patterns['illegal']."]+(\.\w+)?$#", $this->engine->input['file_name'] ) )
				{
					$this->engine->classes['output']->generate_xml_output( array(	'Message'	=> &$this->engine->lang['error_wrong_file_name'],
																					'Log'		=> array( 'level' => 2, 'code' => "WCF_003", 'misc'	=> array( 'file_name' => $this->engine->input['file_name'] ) )
																					)	);
				}
			}
			
			if( $rights['change_link'] )
			{
				$ip_num = '(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])';
				
				if( !$this->engine->input['file_link'] )
				{
					$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_file_link'], 'Log' => array( 'level' => 2, 'code' => "WCF_004" ) ) );
				}
				else if( !preg_match( "#^(http|ftp)://(([\w-]*\.)*[a-zA-Z]{2,5}|$ip_num\\.$ip_num\\.$ip_num\\.$ip_num)(:\d{1,5})?/(.+)?( \[desc\](.+?)\[/desc])?+$#i", $this->engine->input['file_link'], $match ) or 
						 $this->_parse_link( $match ) === FALSE )
				{
					$this->engine->classes['output']->generate_xml_output( array(	'Message'	=> &$this->engine->lang['error_wrong_file_link'],
																					'Log'		=> array( 'level' => 2, 'code' => "WCF_005", 'misc'	=> array( 'file_link' => $this->engine->input['file_link'] ) )
																					)	);
				}
			}
			
			if( $rights['change_path'] and $this->engine->input['file_path'] and !preg_match( "#^[\w|/| ]+$#", $this->engine->input['file_path'] ) )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_wrong_file_path'], 'Log' => array( 'level' => 2, 'code' => "WCF_006" ) ) );
			}
			
			if( $file['cat_path'] )
			{
				$file['cat_path'] = preg_replace( "#/$#", "", $file['cat_path'] );
				
				$file_path[] = $file['cat_path'];
			}
			else 
			{
				$file_path[] = $this->engine->config['save_path'];
				$file_path[] = $file['file_shared'] ? "_all" : $file['user_name'];
			}
			
			$file_path[] = preg_replace( "#/{2,}#", "/", $this->engine->input['file_path'] );
			
			if( $this->engine->config['schedule_force_share'] and !$this->engine->member['user_admin'] and !in_array( $this->engine->input['file_event'], $shared_events ) )
			{
				$this->engine->classes['output']->generate_xml_output( array(	'Message'	=> &$this->engine->lang['error_use_shared_event'],
																				'Log'		=> array( 'level' => 2, 'code' => "WCF_007", 'misc' => array( 'file_event' => $this->engine->input['file_event'] ) )
																				)	);
			}
			else if( $this->engine->input['file_event'] and !array_key_exists( $this->engine->input['file_event'], $events ) )
			{
				$this->engine->classes['output']->generate_xml_output( array(	'Message' => &$this->engine->lang['error_wrong_file_event'],
																				'Log'		=> array( 'level' => 2, 'code' => "WCF_008", 'misc' => array( 'file_event' => $this->engine->input['file_event'] ) )
																				)	);
			}
			
			if( $this->engine->input['file_event'] and $events_times[ $this->engine->input['file_event'] ] <= time() )
			{
				$this->engine->classes['output']->generate_xml_output( array(	'Message'	=> &$this->engine->lang['error_wrong_time_event'],
																				'Log'		=> array( 'level' => 2, 'code' => "WCF_009", 'misc' => array( 'event_start' => $events_times[ $this->engine->input['file_event'] ] ) )
																				)	);
			}
			
			//-----------------------------------------------
			// Формируем список параметров
			//-----------------------------------------------
			
			$params = array(	'file_name'		 => $rights['can_control'] 	 ? $this->engine->input['file_name'] : $file['file_name'],
								'file_link'		 => $rights['change_link'] 	 ? $this->engine->input['file_link'] : $file['file_link'],
								'file_desc' 	 => $rights['change_desc'] 	 ? $this->engine->input['file_desc'] : $file['file_desc'],
								'file_cat'		 => $rights['change_path']	 ? $this->engine->input['file_cat']  : $file['file_cat'],
								'file_path'		 => $rights['change_path']	 ? implode( "/", $file_path ) : $file['file_path'],
								'file_dl_module' => $rights['select_module'] ? $this->engine->input['file_module'] : $file['file_dl_module'],
								'file_priority'  => $rights['set_priority']  ? $this->engine->input['file_priority'] : $file['file_priority'],
								);
								
			$params['file_priority'] = is_numeric( $params['file_priority'] ) ? $params['file_priority'] : 1;
								
			if( !array_key_exists( $params['file_dl_module'], $modules ) ) $params['file_dl_module'] = $default_module;
								
			//-----------------------------------------------
			// Добавляем или обновляем файл
			//-----------------------------------------------
								
			if( $type == "add" )
			{
				$params['file_link']	  = str_replace( "%20", " ", $params['file_link'] );
				
				$params['file_size']	  = $this->engine->classes['downloader']->_check_link( $params['file_link'] );
				$params['file_user'] 	  = &$this->engine->member['user_id'];
				$params['file_dl_range']  = $this->engine->cache['download']['range'] ? 1 : 0;
				$params['file_dl_module'] = $rights['select_module'] ? $this->engine->input['file_module'] : $file['file_dl_module'];
				$params['file_shared']	  = $this->engine->input['auser'] ? 0 : 1;
				$params['file_added']	  = time();
				
				$params['file_name']	  = $params['file_name'] ? $params['file_name'] : $this->engine->cache['download']['filename'];
				$params['file_name']	  = $params['file_name'] ? $params['file_name'] : preg_replace( "#[".$this->patterns['illegal']."]#", "_", substr( $params['file_link'], strrpos( $params['file_link'], "/" ) + 1 ) );
				
				$this->engine->DB->do_insert( "categories_files", &$params );
				
				//-----------------------------------------------
				// Проверяем кэш ссылок
				//-----------------------------------------------
				
				if( $this->engine->input['cached'] and $this->engine->input['cached'] != -1 )
				{
					$cached_ids[0] = $this->engine->DB->get_insert_id();
					
					$this->engine->DB->do_delete( "system_cache", "cache_name='links_list' AND cache_uid='{$this->engine->input['uid']}' AND cache_id='{$cached['cache_id']}'" );
					
					if( $this->engine->input['file_cache_apply'] )
					{
						//-----------------------------------------------
						// Изменяем состояние файла
						//-----------------------------------------------
						
						if( $this->engine->input['file_cache_apply'] and !$this->engine->input['file_cache_start'] )
						{
							$params['file_state'] = "idle";
						}
						
						//-----------------------------------------------
						// Сохраняем параметры текущего файла и
						// резервируем слот закачки
						//-----------------------------------------------

						$params_recover = $params;
						$params_recover['file_id'] = $cached_ids[0];
						
						if( $this->engine->input['file_cache_start'] ) --$this->engine->config['download_max_amount'];
						
						//-----------------------------------------------
						// Получаем ссылки из кэша
						//-----------------------------------------------
						
						$this->engine->DB->simple_construct( array(	'select'	=> 'cache_id, cache_value, cache_priority',
																	'from'		=> 'system_cache',
																	'where'		=> "cache_name='links_list' AND cache_uid='{$this->engine->input['uid']}'",
																	'order'		=> 'cache_priority DESC, cache_id',
																	)	);
						$this->engine->DB->simple_exec();
						
						while( $cached = $this->engine->DB->fetch_row() )
						{
							$links[] = array_merge( unserialize( stripslashes( $cached['cache_value'] ) ), array( 'priority' => $cached['cache_priority'] ) );
						}
						
						$i = 102000; # Для того, чтобы при объединении массивов ниже не было одинаковых идентификаторов. Иначе некоторые элементы массива будут потеряны.
						
						//-----------------------------------------------
						// Обрабатываем каждую ссылку
						//-----------------------------------------------
						
						$time_now = time();

						if( is_array( $links ) ) foreach( $links as $cached )
						{
							$cached['link'] = str_replace( "%20", " ", $cached['link'] );
							
							$params['file_link']  	 = $cached['link'];
							$params['file_size']  	 = $this->engine->classes['downloader']->_check_link( $params['file_link'] );
							$params['file_desc']  	 = $cached['desc'];
							$params['file_added'] 	 = $time_now;
							$params['file_dl_range'] = $this->engine->cache['download']['range'] ? 1 : 0;
							$params['file_priority'] = $cached['priority'];
							
							$params['file_name']  	 = substr( $cached['link'], strrpos( $cached['link'], "/" ) + 1 );
							$params['file_name'] 	 = ( strpos( $params['file_name'], "." === FALSE ) or preg_match( "#[?=&]#i", $params['file_name'] ) ) ? "" : preg_replace( "#[^\w \-\.]#", "_", $params['file_name'] );
							$params['file_name']  	 = $params['file_name'] ? $params['file_name'] : $this->engine->cache['download']['filename'];
							$params['file_name']  	 = $params['file_name'] ? $params['file_name'] : preg_replace( "#[".$this->patterns['illegal']."]#", "_", substr( $cached['link'], strrpos( $cached['link'], "/" ) + 1 ) );
							
							$this->engine->DB->do_insert( "categories_files", &$params );
							
							$this->engine->add_log_event( 3, "ICF_001", array( 'cache_ids' => implode( ", ", $cached_ids ) ) );
							
							//-----------------------------------------------
							// Добавляем файл
							//-----------------------------------------------
							
							$file_id = $this->engine->DB->get_insert_id();
							
							$cached_ids[ $i ] = $file_id;
							
							//-----------------------------------------------
							// Запускаем закачку
							//-----------------------------------------------
							
							if( $this->engine->input['file_cache_start'] ) $this->change_download_state( 'run', &$file_id, &$params['file_dl_module'], FALSE, FALSE );
							
							$i++;
						}
						
						//-----------------------------------------------
						// Запускаем сохраненные в кэше закачки
						//-----------------------------------------------
						
						if( is_array( $this->engine->cache['download']['files'] ) )
						{
							$this->engine->classes['downloader']->download_start_cached();
						}
						
						//-----------------------------------------------
						// Проверяем очередь закачек и очищаем кэш файлов
						//-----------------------------------------------
						
						if( is_array( $this->engine->cache['files']['query'] ) )
						{
							$this->engine->DB->do_update( 'categories_files', array( "file_state" => 'query', "file_dl_last_start" => time() ), "file_id IN('".implode( "','", $this->engine->cache['files']['query'] )."')" );
							
							$this->engine->cache['files']['query'] = NULL;
						}
						
						$this->engine->DB->do_delete( "system_cache", "cache_name='links_list' AND cache_uid='{$this->engine->input['uid']}'" );
						
						$this->engine->add_log_event( 4, "ICF_002", array( 'cache_uid' => $this->engine->input['uid'] ) );
						
						//-----------------------------------------------
						// Восстанавливаем параметры текущего файла и
						// освобождаем зарезервированный слот
						//-----------------------------------------------

						$params = &$params_recover;
						
						if( $this->engine->input['file_cache_start'] ) ++$this->engine->config['download_max_amount'];
					}
				}
				else 
				{
					$params['file_id'] = $this->engine->DB->get_insert_id();
				}
				
				//-----------------------------------------------
				// Добавляем идентификатор файла в новое событие
				//-----------------------------------------------
				
				if( $this->engine->input['file_event'] )
				{
					$events_files[ $this->engine->input['file_event'] ][] = $params['file_id'];
					
					if( $cached and $this->engine->input['file_cache_apply'] and !$this->engine->input['file_cache_start'] )
					{
						$events_files[ $this->engine->input['file_event'] ] = array_merge( $events_files[ $this->engine->input['file_event'] ], $cached_ids );
					}
					
					$this->engine->DB->do_update( "schedule_events", array( "event_files" => implode( ",", $events_files[ $this->engine->input['file_event'] ] ) ), "event_id='{$this->engine->input['file_event']}'" );
				}
				
				$this->engine->add_log_event( 3, "ICF_003", &$params );
			}
			else
			{
				$params['file_size'] 	 = $this->engine->classes['downloader']->_check_link( $params['file_link'] );
				$params['file_dl_range'] = $this->engine->cache['download']['range'] ? 1 : 0;
				
				$params['file_name'] = $params['file_name'] ? $params['file_name'] : substr( $params['file_link'], strrpos( $params['file_link'], "/" ) + 1 );
				$params['file_name'] = ( strpos( $params['file_name'], "." === FALSE ) or preg_match( "#[?=&]#i", $params['file_name'] ) ) ? "" : preg_replace( "#[".$this->patterns['illegal']."]#", "_", $params['file_name'] );
				$params['file_name'] = $params['file_name'] ? $params['file_name'] : $this->engine->cache['download']['filename'];
				$params['file_name'] = $params['file_name'] ? $params['file_name'] : preg_replace( "#[".$this->patterns['illegal']."]#", "_", substr( $params['file_link'], strrpos( $params['file_link'], "/" ) + 1 ) );
				
				$this->engine->DB->do_update( "categories_files", &$params, "file_id='{$file['file_id']}'" );
				
				$array['Message'] =& $this->engine->lang['file_edited'];
				
				$params['file_id'] = &$file['file_id'];
				
				//-----------------------------------------------
				// Удаляем идентификатор файла из старого события
				// и добавляем в новое
				//-----------------------------------------------
				
				if( $this->engine->input['file_event'] and $file['event_id'] and $this->engine->input['file_event'] != $file['event_id'] )
				{
					foreach( $events_files[ $file['event_id'] ] as $id => $fid )
					{
						if( $fid == $file['file_id'] ) unset( $events_files[ $file['event_id'] ][ $id ] );
					}
					
					$this->engine->DB->do_update( "schedule_events", array( "event_files" => $events_files[ $file['event_id'] ] ), "event_id='{$file['event_id']}'" );
					
					$this->engine->add_log_event( 3, "ICF_004", array( "event_id" => $file['event_id'] ) );
					
					$events_files[ $this->engine->input['file_event'] ][] = $params['file_id'];
					
					$this->engine->DB->do_update( "schedule_events", array( "event_files" => implode( ",", $events_files[ $this->engine->input['file_event'] ] ) ), "event_id='{$this->engine->input['file_event']}'" );
					
					$this->engine->add_log_event( 3, "ICF_005", array( "event_id" => $this->engine->input['file_event'] ) );
					
					$file['event_id'] = $this->engine->input['file_event'];
					
					$params['file_state'] = $file['file_state'] == "paused" ? "paused" : "idle";
				}
				
				//-----------------------------------------------
				// Добавляем идентификатор файла в новое событие
				//-----------------------------------------------
				
				else if( $this->engine->input['file_event'] and !$file['event_id'] )
				{
					$events_files[ $this->engine->input['file_event'] ][] = $params['file_id'];
					
					$this->engine->DB->do_update( "schedule_events", array( "event_files" => implode( ",", $events_files[ $this->engine->input['file_event'] ] ) ), "event_id='{$this->engine->input['file_event']}'" );
					
					$this->engine->add_log_event( 3, "ICF_006", array( "event_id" => $this->engine->input['file_event'] ) );
					
					$file['event_id'] = $this->engine->input['file_event'];
					
					$params['file_state'] = $file['file_state'] == "paused" ? "paused" : "idle";
				}
				
				//-----------------------------------------------
				// Удаляем идентификатор файла из старого события
				//-----------------------------------------------
				
				else if( !$this->engine->input['file_event'] and $file['event_id'] )
				{
					foreach( $events_files[ $file['event_id'] ] as $id => $fid )
					{
						if( $fid == $file['file_id'] ) unset( $events_files[ $file['event_id'] ][ $id ] );
					}
					
					$this->engine->DB->do_update( "schedule_events", array( "event_files" => implode( ",", $events_files[ $file['event_id'] ] ) ), "event_id='{$file['event_id']}'" );
					
					$this->engine->add_log_event( 3, "ICF_007", array( "event_id" => $file['event_id'] ) );
					
					$file['event_id'] = 0;
					
					$params['file_state'] = $file['file_state'] == "paused" ? "paused" : "idle";
				}
				
				//-----------------------------------------------
				// Восстанавливаем режим ожидания для файла
				//-----------------------------------------------
				
				else if( $this->engine->input['file_event'] and $file['event_id'] and $this->engine->input['file_event'] == $file['event_id'] and in_array( $file['file_state'], array( "paused", "stopped" ) ) )
				{
					$this->engine->add_log_event( 3, "ICF_008", array( "event_id" => $file['event_id'] ) );
					
					$params['file_state'] = $file['file_state'] == "paused" ? "paused" : "idle";
				}
				
				$this->engine->add_log_event( 4, "ICF_009", &$params );
			}
			
			//-----------------------------------------------
			// Обновляем параметры файла
			//-----------------------------------------------
			
			if( $params['file_id'] ) $file['file_id'] = $params['file_id'];
			
			$file['file_name']		= &$params['file_name'];
			$file['file_link']		= &$params['file_link'];
			$file['file_size']		= &$params['file_size'];
			$file['file_priority']  = &$params['file_priority'];
			$file['cat_id']			= &$params['file_cat'];
			$file['file_path']		= &$this->engine->input['file_path'];
			$file['file_desc']		= &$params['file_desc'];
			$file['file_dl_module']	= &$params['file_dl_module'];
			$file['file_dl_range']	= &$params['file_dl_range'];
			
			$this->engine->add_log_event( 4, "ICF_010", &$file );
		}
		
		//-----------------------------------------------
		// Изменяем, если необходимо, состояние файла
		//-----------------------------------------------
		
		if( $type == 'add' and $this->engine->input['control'] == 'run' )
		{
			if( $this->change_download_state( 'run', &$file['file_id'], &$file['file_dl_module'] ) === FALSE )
			{
				$array['Message'] = $this->engine->lang['error_cant_run_download'].$this->error;
			}
		}
		else if( $type == 'edit' and $this->engine->input['apply'] )
		{
			if( !$rights['can_control'] )
			{
				$array['Message'] = $this->engine->lang['error_cant_change_state'];
				$array['Log'] = array( 'level' => 1, 'code' => "ECF_004" );
			}
			
			if( $this->engine->input['control'] ) switch( $this->engine->input['control'] )
			{
				case 'run':
					if( $file['file_state'] != 'running' and $this->change_download_state( 'run', &$file['file_id'], &$file['file_dl_module'] ) === FALSE )
					{
						$this->engine->DB->do_update( "categories_files", array( 'file_state' => "error" ), "file_id='{$file['file_id']}'" );
						
						$array['Message'] = $this->engine->lang['error_cant_run_download'].$this->error;
					}
					break;
					
				case 'pause':
					if( $file['file_state'] == 'running' and $this->change_download_state( 'pause', &$file['file_id'], &$file['file_dl_module'] ) === FALSE )
					{
						$array['Message'] = $this->engine->lang['error_cant_pause_download'].$this->error;
					}
					break;
					
				case 'stop':
					if( $file['file_state'] == 'running' and $this->change_download_state( 'stop', &$file['file_id'], &$file['file_dl_module'] ) === FALSE )
					{
						$array['Message'] = $this->engine->lang['error_cant_stop_download'].$this->error;
					}
					else 
					{
						$file['file_blocked'] = 0;
					}
					break;
					
				case 'delete':
					if( $this->change_download_state( 'delete', &$file['file_id'], &$file['file_dl_module'] ) === FALSE )
					{
						$array['Message'] = $this->engine->lang['error_cant_delete_download'].$this->error;
					}
					break;
			}
			else if( $params['file_state'] == "idle" )
			{
				$this->engine->DB->do_update( "categories_files", array( 'file_state' => "idle" ), "file_id='{$file['file_id']}'" );
			}
			
			$array['Message'] =& $this->engine->lang['file_edited'];
		}
		
		//-----------------------------------------------
		// Запускаем сохраненные в кэше закачки
		//-----------------------------------------------
		
		if( is_array( $this->engine->cache['download']['files'] ) )
		{
			$this->engine->classes['downloader']->download_start_cached();
		}
		
		//-----------------------------------------------
		// Обновляем список файлов
		//-----------------------------------------------
			
		if( $this->engine->input['apply'] )
		{
			if( method_exists( $this->parent, "_get_category_contents" ) )
			{
				$this->engine->input['id'] =& $this->engine->input['cat'];
				
				$this->parent->_get_category_contents();
				
				$array= array(	'List' 		 => &$this->parent->html,
								'Function_0' => "ajax_reselect_files()",
								);
			}
			else 
			{
				$this->engine->input['id'] =& $this->engine->input['auser'];
				$this->engine->input['sub'] =& $this->engine->input['asub'];
				
				$this->parent->_get_downloads_list();
				
				$array= array(	'List' 		 => &$this->parent->html,
								'Function_0' => "ajax_reselect_items()",
								);
			}
			
			$this->engine->add_log_event( 4, "ICF_011" );
		
			//-----------------------------------------------
			// Обновляем информацию о файле
			//-----------------------------------------------
		
			$refresh = $this->engine->DB->simple_exec_query( array(	'select'	=> 'file_dl_start, file_dl_stop, file_dl_left, file_dl_time, file_dl_speed, file_state, file_size',
																	'from'		=> 'categories_files',
																	'where'		=> "file_id='{$file['file_id']}'"
																	)	);
																	
			$file['file_dl_start'] 	= $refresh['file_dl_start'];
			$file['file_dl_stop']  	= $refresh['file_dl_stop'];
			$file['file_state']	 	= $refresh['file_state'];
			$file['file_size']	 	= $refresh['file_size'];
			$file['file_dl_left']	= $refresh['file_dl_left'];
			$file['file_dl_time']	= $refresh['file_dl_time'];
			$file['file_dl_speed'] 	= $refresh['file_dl_speed'];
			
			if( is_numeric( $file['file_size'] ) and is_numeric( $file['file_dl_left'] ) )
			{
				$file['file_time_used']  = time() - $file['file_dl_start'];
				$file['file_dl_done']    = floor( $file['file_size'] - $file['file_dl_left'] );
				$file['file_dl_percent'] = round( $file['file_dl_done'] / $file['file_size'] * 100, 2 );
				$file['file_dl_time']    = $file['file_dl_time'];
				
				$file['file_done']  = $this->engine->skin['files']->progress_info( $file['file_time_used'], $file['file_dl_done'], $file['file_dl_percent'] );
				$file['file_left']  = $this->engine->skin['files']->progress_info( $file['file_dl_time'], $file['file_dl_left'], 100 - $file['file_dl_percent'] );
				$file['file_speed'] = $this->engine->convert_file_size( $file['file_dl_speed'] ).$this->engine->lang['per_sec'];
			}
			else
			{
				$file['file_done']  = $this->engine->skin['files']->progress_info();
				$file['file_left']  = $this->engine->skin['files']->progress_info();
				$file['file_speed'] = "--";
			}
			
			$this->engine->add_log_event( 4, "ICF_012" );
		
			//-----------------------------------------------
			// Добавляем вызов нового окна для ссылки из списка
			//-----------------------------------------------
			
			if( $cached and !$this->engine->input['file_cache_apply'] and $this->engine->input['cached'] > 1 )
			{
				$array['Function_0'] = "ajax_parse_next_link('{$this->engine->input['uid']}','{$this->engine->input['cached']}')";
			}
			
			//-----------------------------------------------
			// Закрываем AJAX окно при добавлении
			//-----------------------------------------------
			
			if( ( $type == "add" or $this->engine->input['control'] == 'delete' ) )
			{
				$array['CloseWindow'] = TRUE;
					
				$this->engine->classes['output']->generate_xml_output( &$array );
			}
		}
		
		//-----------------------------------------------
		// Подгружаем информацию о событиях
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'event_files',
													'from'		=> 'schedule_events',
													'where'		=> "event_files LIKE '%{$file['file_id']}%' AND event_state IN('query','running')"
													)	);
		$this->engine->DB->simple_exec();
		
		while( $row = $this->engine->DB->fetch_row() ) if( in_array( $file['file_id'], explode( ",", $row['event_files'] ) ) )
		{
			$file['file_state'] = $file['file_state'] == "idle" ? "schedule" : "continue";
			
			break;
		}
		
		//-----------------------------------------------
		// Проверяем наличие библиотеки GD
		//-----------------------------------------------
		
		if( in_array( "gd", $this->engine->php_ext ) ) $progress_bar = TRUE;
		
		if( $file['file_state'] == "done" ) $file['file_dl_percent'] = 100;
		
		//-----------------------------------------------
		// Преобразуем путь до файла в относительный
		//-----------------------------------------------
		
		$subdir = $file['file_shared'] ? "_all" : $file['user_name'];
		
		$file['file_path'] = preg_replace( "#^".$this->engine->config['save_path']."/*{$subdir}/*#i", "", $file['file_path'] );
		
		//-----------------------------------------------
		// Формируем таблицу с параметрами
		//-----------------------------------------------
		
		$file['file_size'] = $file['file_size'] ? $this->engine->convert_file_size( $file['file_size'] ) : $this->engine->lang['file_size_unknown'];
		
		$misc['name'] = $rights['can_control']  ? "" : "disabled='disabled'";
		$misc['path'] = $rights['change_path']  ? "" : "disabled='disabled'";
		$misc['desc'] = $rights['change_desc']  ? "" : "disabled='disabled'";
		
		$file['file_priority'] = is_numeric( $file['file_priority'] ) ? $file['file_priority'] : 1;
		
		$misc['link'] = ( $file['file_blocked'] or !$rights['change_link'] ) ? "disabled='disabled'" : "";
		$misc['cat']  = "onchange='ajax_change_path_value(this);'";
		
		$table .= $this->engine->classes['output']->form_start( array(	'tab'		=> $this->engine->input['tab'],
																		'uid'		=> $this->engine->input['uid'],
																		'cid'		=> $cached['cache_id'],
																		'cached'	=> $this->engine->input['cached'] - 1,
																		), "id='ajax_form' onsubmit='ajax_apply_file( \"{$file['file_id']}\", \"{$type}\", \"{$this->engine->input['tab']}\" ); return false;'" );
																			
		$this->engine->classes['output']->table_add_header( ""	, "40%" );
		$this->engine->classes['output']->table_add_header( ""	, "60%" );
		
		$table .= $this->engine->classes['output']->table_start( "", "100%", "", "", "id='ajax_table' style='border:0'" );
		
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['file_name']																							, "row1" ),
								array(	$this->engine->skin['global']->form_text( "file_name", $file['file_name'], "medium", "text", &$misc['name'] )				, "row2" ),
								)	);
								
		if( $rights['view_link'] )
		{
			$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['file_link']																							, "row1" ),
								array(	$this->engine->skin['global']->form_text( "file_link", $file['file_link'], "medium", "text", &$misc['link'] )				, "row2" ),
								)	);
		}
		
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['file_category']																						, "row1" ),
								array(	$this->engine->skin['global']->form_dropdown( "file_cat", $dropdown['shown'], $file['cat_id'], "medium", &$misc['cat'] )	, "row2" ),
								)	);
								
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['file_path']																							, "row1" ),
								array(	$this->engine->skin['global']->form_text( "file_path", $file['file_path'], "medium", "text", &$misc['path'] )				, "row2" ),
								)	);
								
		if( $rights['view_owner'] )
		{
			$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['file_user']																							, "row1" ),
								array(	$file['user_real']																											, "row2" ),
								)	);
		}
								
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['file_size']																							, "row1" ),
								array(	"<div id='update_container_2'>".$file['file_size']."</div>"																	, "row2" ),
								)	);
								
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['file_done']																							, "row1" ),
								array(	"<div id='update_container_3'>".$file['file_done']."</div>"																	, "row2" ),
								)	);
								
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['file_left']																							, "row1" ),
								array(	"<div id='update_container_4' style='font-weight:bold'>".$file['file_left']."</div>"										, "row2" ),
								)	);
								
		if( $progress_bar )
		{
			$table .= $this->engine->classes['output']->table_add_row( array( 
									array(	$this->engine->lang['file_bar']																							, "row1" ),
									array(	"<div id='update_container_5' class='progress_bar'>".
											"<img src='{$this->engine->base_url}tab=download&progress={$file['file_dl_percent']}' alt='' /></div>"					, "row2" ),
									)	);
		}
								
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['file_speed']																							, "row1" ),
								array(	"<div id='update_container_6'>".$file['file_speed']."</div>"																, "row2" ),
								)	);
								
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['file_range']																							, "row1" ),
								array(	$this->engine->skin['files']->file_range( &$file['file_dl_range'] )															, "row2" ),
								)	);
								
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['file_desc']																							, "row1" ),
								array(	$this->engine->skin['global']->form_textarea( "file_desc", $file['file_desc'], "medium", "text", &$misc['desc'] )			, "row2" ),
								)	);
								
		if( $type != 'add' )
		{
			$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['file_state']																							, "row1" ),
								array(	"<div id='update_container_7'>".$this->engine->skin['files']->file_state( &$file['file_state'] )."</div>"					, "row2" ),
								)	);
		}
		
		//-----------------------------------------------
		// Добавляем элементы управления скачиванием
		//-----------------------------------------------
		
		$misc['control']  = $file['file_blocked'] ? "disabled='disabled'" : "";
		$misc['contiune'] = in_array( $file['file_state'], array( "paused", "schedule", "continue" ) ) ? "" : $misc['control'];
		
		if( $rights['can_control'] )
		{
			$table .= $this->engine->classes['output']->table_add_row_single_cell( &$this->engine->lang['file_control'], "row4" );
			
			if( $rights['select_module'] )
			{
				$table .= $this->engine->classes['output']->table_add_row( array( 
							array(	$this->engine->lang['file_module']																								, "row1" ),
							array(	$this->engine->skin['global']->form_dropdown( "file_module", $modules, $file['file_dl_module'], "medium", &$misc['control'] )	, "row2" ),
							)	);
			}
								
			$table .= $this->engine->classes['output']->table_add_row( array( 
							array(	$this->engine->lang['file_event']																								, "row1" ),
							array(	$this->engine->skin['global']->form_dropdown( "file_event", $events, $file['event_id'], "medium", &$misc['contiune'] )			, "row2" ),
							)	);

			if( $rights['set_priority'] )
			{		
				$table .= $this->engine->classes['output']->table_add_row( array( 
							array(	$this->engine->lang['file_priority']																							, "row1" ),
							array(	$this->engine->skin['global']->form_dropdown( "file_priority", $priority, $file['file_priority'], "medium", &$misc['contiune'] ), "row2" ),
							)	);
			}
								
			if( $this->engine->input['tab'] == 'download' and !$cached )
			{
				$table .= $this->engine->classes['output']->table_add_row( array( 
							array(	$this->engine->lang['file_controls']																							, "row1" ),
							array(	$this->engine->skin['files']->download_controls( &$file['file_id'], &$type )													, "row9" ),
							)	);
			}
		}
		
		//-----------------------------------------------
		// Добавляем элементы управления обработкой
		// списка ссылок
		//-----------------------------------------------
		
		if( $cached )
		{
			$table .= $this->engine->classes['output']->table_add_row_single_cell( &$this->engine->lang['file_cache'], "row4" );
			
			$table .= $this->engine->classes['output']->table_add_row( array( 
							array(	$this->engine->lang['file_cache_apply']																							, "row1" ),
							array(	$this->engine->skin['global']->form_yes_no( "file_cache_apply", 0 )																, "row2" ),
							)	);
							
			$table .= $this->engine->classes['output']->table_add_row( array( 
							array(	$this->engine->lang['file_cache_start']																							, "row1" ),
							array(	$this->engine->skin['global']->form_yes_no( "file_cache_start", 0 )																, "row2" ),
							)	);
		}
		
		if( $type == 'add' and count( $dropdown['shown'] ) > 1 )
		{
			$table .= $this->engine->skin['global']->form_dropdown( "hidden_paths", $dropdown['hidden'], "", "medium", "style='display:none;' disabled='disabled'" );
		}
								
		$table .= $this->engine->classes['output']->table_end();
								
		$table .= $this->engine->classes['output']->form_end_submit( $this->engine->lang['apply_settings'], "", "style='border:0;'" );
		
		//-----------------------------------------------
		// Если файл скачивается, то создаем блок с
		// блокирующем обновление идентификатором
		//-----------------------------------------------
		
		if( $file['file_state'] == 'running' )
		{
			$table .= "<div id='file_window'></div>\n";
		}
		
		//-----------------------------------------------
		// Формируем и возвращаем XML
		//-----------------------------------------------
		
		$this->engine->add_log_event( 4, "ICF_013", array( 'file_id' => $file['file_id'], 'file_name' => $file['file_name'] ) );
		
		$html = $this->engine->skin['global']->ajax_window( $this->engine->lang['file_properties'], &$table );
		
		$array['HTML'] =& $html;
		
		$this->engine->classes['output']->generate_xml_output( &$array );
	}
	
	/**
    * Изменение состояния закачки
    * 
    * Проверяет условия, необходимые для изменения состояния
    * закачки на указанное.
    * Если все условия соблюдены, то передается управление
    * модулю, ассоциированному с закачиваемым файлом.
    * 
    * @param 	string			Новое состояние
    * @param 	int				Идентификатор файла
    * @param 	int				Идентификатор модуля
    * @param 	bool	[opt]	Выводить сообщения о совпадении состояний
    * @param 	bool	[opt]	Автоматически добавлять файл в очередь
    *
    * @return	string			Системное сообщение
    */
	
	function change_download_state( $state, $id, $mid, $show_notice = TRUE, $add_to_query = TRUE )
	{
		//-----------------------------------------------
		// Проверяем тип нового состояния
		//-----------------------------------------------
		
		if( !in_array( $state, array( 'run', 'pause', 'stop', 'delete' ) ) )
		{
			$this->error =& $this->engine->lang['error_wrong_state'];
			
			$this->engine->add_log_event( 1, "ECF_005" );
			
			return FALSE;
		}
		
		//-----------------------------------------------
		// Получаем информацию о файле
		//-----------------------------------------------
		
		$file = $this->engine->DB->simple_exec_query( array(	'select'	=> '*',
																'from'		=> 'categories_files',
																'where'		=> "file_id='{$id}'"
																)	);
															
		if( !$file['file_id'] )
		{
			$this->error =& $this->engine->lang['error_wrong_file_id'];
			
			$this->engine->add_log_event( 1, "ECF_006" );
			
			return FALSE;
		}
		
		if( $state == 'run' and $file['file_state'] == 'running' )
		{
			$this->engine->add_log_event( 3, "ICF_014", array( 'file_id' => $file['file_id'], 'file_name' => $file['file_name'] ) );
			
			if( $show_notice )
			{
				$this->error =& $this->engine->lang['error_download_is_running'];
				return FALSE;
			}
			else 
			{
				return TRUE;
			}
		}
		else if( !in_array( $state, array( 'run', 'delete', 'stop' ) ) and $file['file_state'] != 'running' )
		{
			$this->engine->add_log_event( 3, "ICF_015", array( 'file_id' => $file['file_id'], 'file_name' => $file['file_name'] ) );
			
			if( $show_notice )
			{
				$this->error =& $this->engine->lang['error_download_is_not_running'];
				return FALSE;
			}
			else 
			{
				return TRUE;
			}
		}
		
		//-----------------------------------------------
		// Удаляем незакачиваемый файл
		//-----------------------------------------------
		
		if( $state == 'delete' and !in_array( $file['file_state'], array( 'running', 'paused' ) ) )
		{
			if( $this->delete_file_simple( &$file['file_id'] ) === FALSE )
			{
				return FALSE;
			}
			else 
			{
				return TRUE;
			}
		}
		
		//-----------------------------------------------
		// Останавливаем незакачиваемый файл
		//-----------------------------------------------
		
		if( $state == 'stop' and !in_array( $file['file_state'], array( 'running', 'paused' ) ) )
		{
			$this->engine->DB->do_update( "categories_files", array( 'file_state' => "stopped" ), "file_id='{$file['file_id']}'" );
			
			$this->engine->add_log_event( 3, "ICF_016", array( 'file_id' => $file['file_id'], 'file_name' => $file['file_name'] ) );
			
			return TRUE;
		}
		
		//-----------------------------------------------
		// Начинаем или возобновляем закачку
		//-----------------------------------------------
		
		if( $state == 'run' )
		{
			//-----------------------------------------------
			// Проверяем временные ограничения
			//-----------------------------------------------
			
			if( $this->_check_limits( &$file['file_user'] ) !== TRUE )
			{
				$this->error =& $this->engine->lang['error_time_restriction'];
				return FALSE;
			}
			
			//-----------------------------------------------
			// Проверяем количество текущих закачек
			//-----------------------------------------------
			
			if( !is_numeric( $this->engine->cache['files']['running_count'] ) )
			{
				$where = $this->engine->config['reserve_paused_slots'] ? "file_state IN ('running', 'paused')" : "file_state='running'";
				
				$amount = $this->engine->DB->simple_exec_query( array(	'select'	=> 'COUNT(file_id) as count',
																		'from'		=> 'categories_files',
																		'where'		=> $where
																		)	);
																		
				if( $file['file_state'] == "paused" and $this->engine->config['reserve_paused_slots'] ) $amount['count']--;
																		
				$this->engine->cache['files']['running_count'] = $amount['count'];
				
				$this->engine->add_log_event( 4, "ICF_017", array( 'running_count' => $amount['count'] ) );
			}
			
			if( $this->engine->cache['files']['running_count'] >= $this->engine->config['download_max_amount'] )
			{
				$this->engine->add_log_event( 3, "ICF_018", array( 'file_id' => $file['file_id'], 'file_name' => $file['file_name'] ) );
				
				if( $add_to_query )
				{
					$this->engine->DB->do_update( 'categories_files', array( "file_state" => 'query', "file_dl_last_start" => time() ), "file_id='{$id}'" );
				}
				else
				{
					$this->engine->cache['files']['query'][] = $id;
				}
				
				if( $show_notice )
				{
					$this->error =& $this->engine->lang['error_too_many_downloads'];
					return FALSE;
				}
				else 
				{
					return TRUE;
				}
			}
		}
		
		//-----------------------------------------------
		// Подключаем модуль
		//-----------------------------------------------
		
		if( $this->engine->load_module( "class", "downloader" ) === FALSE )
		{
			$this->error = $this->engine->lang['error_cant_load_module']."downloader";
			return FALSE;
		}
		
		if( $this->engine->classes['downloader']->load_module( "", $mid ) === FALSE )
		{
			$this->error =& $this->engine->lang['error_cant_load_file_module'];
			return FALSE;
		}
		
		if( !$this->engine->classes['downloader']->module['enabled'] )
		{
			$this->engine->add_log_event( 1, "ECF_007", array( 'module_id' => $mid ) );
			
			$this->error =& $this->engine->lang['error_module_disabled'];
			return FALSE;
		}
		
		switch( $state )
		{
			//-----------------------------------------------
			// Вызываем для модуля функцию начала закачки
			//-----------------------------------------------
			
			case 'run':
				
				if( $file['file_state'] == 'error' and $this->engine->classes['downloader']->download_can_restore( &$file ) )
				{
					if( $this->engine->classes['downloader']->download_continue( &$file ) === FALSE )
					{
						$this->error =& $this->engine->classes['downloader']->error;
						return FALSE;
					}
					else 
					{
						$file['file_state'] = 'paused';
					}
				}
				
				if( $file['file_state'] != 'paused' and $this->engine->classes['downloader']->download_start( &$file ) === FALSE )
				{
					$this->error =& $this->engine->classes['downloader']->error;
					return FALSE;
				}
				else if( $file['file_state'] == 'paused' and $this->engine->classes['downloader']->download_continue( &$file ) === FALSE )
				{
					$this->error =& $this->engine->classes['downloader']->error;
					return FALSE;
				}
				
				$save_state = 'running';
				
				break;
				
			//-----------------------------------------------
			// Вызываем для модуля функцию приостановки закачки
			//-----------------------------------------------
				
			case 'pause':
				
				if( $this->engine->classes['downloader']->download_pause( &$file ) === FALSE )
				{
					$this->error =& $this->engine->classes['downloader']->error;
					return FALSE;
				}
				
				$save_state = 'paused';
				
				break;
				
			//-----------------------------------------------
			// Вызываем для модуля функцию остановки закачки
			//-----------------------------------------------
				
			case 'stop':
			case 'delete':
				
				if( $this->engine->classes['downloader']->download_stop( &$file ) === FALSE )
				{
					$this->error =& $this->engine->classes['downloader']->error;
					return FALSE;
				}
				
				$save_state = $state == 'stop' ? 'stopped' : 'deleted';
				
				break;
		}
		
		//-----------------------------------------------
		// Удаляем незакачанный файл
		//-----------------------------------------------
		
		if( $state == 'delete' and in_array( $file['file_state'], array( 'running', 'paused' ) ) )
		{
			if( $this->delete_file_simple( &$file['file_id'], &$file['file_path'], &$file['file_name'] ) === FALSE )
			{
				return FALSE;
			}
			else 
			{
				return TRUE;
			}
		}
		
		//-----------------------------------------------
		// Обновляем информацию о файле
		//-----------------------------------------------
		
		$this->engine->classes['downloader']->update_download_state( &$file );
		
		//-----------------------------------------------
		// Если мы здесь, то состояние изменено
		//-----------------------------------------------
		
		$array['file_state'] = $save_state;
		
		if( $state == 'run' and $file['file_state'] != 'paused' ) $array['file_dl_id'] = $this->engine->classes['downloader']->file['file_dl_id'];
		
		if( $state == 'run' )
		{
			$time_now = time();
			
			if( $file['file_dl_stop'] )
			{
				$array['file_dl_stop'] = NULL;
			}
			
			$array['file_dl_last_start'] = $time_now;
			
			++$this->engine->cache['files']['running_count'];
		}
		
		$this->engine->DB->do_update( 'categories_files', &$array, "file_id='{$id}'" );
		
		$this->engine->add_log_event( 3, "ICF_019", &$array );
		
		return TRUE;		
	}
	
	/**
    * Удаление файлов
    * 
    * Удаляет файлы из списков категорий.
    * Если файлы скачиваются, то предварительно прекращает
    * скачивание.
    *
    * @return	void
    */
	
	function delete_file()
	{		
		//-----------------------------------------------
		// Получаем идентификаторы файлов
		//-----------------------------------------------
		
		$ids = explode( ",", $this->engine->input['id'] );
		
		if( !count( $ids ) )
		{
			$this->engine->add_log_event( 1, "ECF_008" );
			
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_file_ids'] ) );
		}
		
		if( $ids[0] == "" ) unset( $ids[0] );
		
		//-----------------------------------------------
		// Получаем параметры файлов
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'f.*',
													'from'		=> 'categories_files f LEFT JOIN categories_list c ON (c.cat_id=f.file_cat)',
													'where'		=> "f.file_id IN('".implode( "','", $ids )."')"
													)	);
		$this->engine->DB->simple_exec();
		
		if( !$this->engine->DB->get_num_rows() )
		{
			$this->engine->classes['output']->generate_xml_output( array(	'Message'	=> &$this->engine->lang['error_no_files_found'],
																			'Log'		=> array( 'level' => 1, 'code' => "ECF_009", 'misc' =>  array( 'input_ids' => implode( ", ", $ids ) ) )
																			)	);
		}
		
		while( $file = $this->engine->DB->fetch_row() )
		{
			$files[ $file['file_id'] ] = $file;
		}
		
		foreach( $files as $file )
		{			
			$file['file_blocked'] = in_array( $file['file_state'], array( "running", "paused" ) ) ? 1 : 0;
		
			//-----------------------------------------------
			// Определяем права на действия с файлами
			//-----------------------------------------------
			
			$rights['can_control'] = ( $file['file_cat'] != 0 or $this->engine->member['user_admin'] or $file['file_user'] == $this->engine->member['user_id'] or $this->engine->config['shared_can_control'] ) ? 1 : 0;
			$rights['can_delete']  = ( $file['file_cat'] != 0 or $this->engine->member['user_admin'] or $file['file_user'] == $this->engine->member['user_id'] or $this->engine->config['shared_can_delete' ] ) ? 1 : 0;
			
			if( !$rights['can_delete'] )
			{
				$this->engine->add_log_event( 2, "WCF_010", array( 'file_id' => $file['file_id'], 'file_name' => $file['file_name'] ) );
				
				continue;
			}
			
			//-----------------------------------------------
			// Подключаем модуль и отменяем закачку
			//-----------------------------------------------
			
			if( !$rights['can_control'] )
			{
				$this->engine->add_log_event( 2, "WCF_011", array( 'file_id' => $file['file_id'], 'file_name' => $file['file_name'] ) );
				
				continue;
			}
			else if( $this->change_download_state( 'delete', &$file['file_id'], &$file['file_module'] ) === FALSE ) 
			{
				continue;
			}
		}
			
		//-----------------------------------------------
		// Обновляем список файлов
		//-----------------------------------------------
			
		if( method_exists( $this->parent, "_get_category_contents" ) )
		{
			$this->engine->input['id'] =& $this->engine->input['cat'];
				
			$this->parent->_get_category_contents();
			
			$array= array(	'List' 		 => &$this->parent->html,
							'Function_0' => "ajax_reselect_files()",
							);
		}
		else 
		{
			$this->engine->input['id'] =& $this->engine->input['auser'];
			$this->engine->input['sub'] =& $this->engine->input['asub'];
				
			$this->parent->_get_downloads_list();
			
			$array= array(	'List' 		 => &$this->parent->html,
							'Function_0' => "ajax_reselect_items()",
							);
		}
			
		$this->engine->add_log_event( 4, "ICF_020", array( 'file_ids' => array_keys( $files ) ) );
		
		//-----------------------------------------------
		// Формируем и возвращаем XML
		//-----------------------------------------------
		
		$this->engine->classes['output']->generate_xml_output( &$array );
	}
	
	/**
    * Простое удаление файла
    * 
    * Предполагается, что все проверки на ошибки и
    * ограничения уже пройдены, и требуется только
    * непосредственное удаление файла.
    * Это удаление выполняется из БД, с диска (если
    * файл не был скачан полностью) и из списков
    * расписания (если он там присутствует).
    * 
    * @param 	int				Идентификатор файла
    * @param 	string	[opt]	Относительный путь до файла
    * @param 	string	[opt]	Имя файла
    *
    * @return	bool			Результат удаления
    */
	
	function delete_file_simple( $id, $path="", $name="" )
	{
		//-----------------------------------------------
		// Удаляем файл из списков в БД
		//-----------------------------------------------
		
		$this->engine->DB->do_delete( 'categories_files', "file_id='{$id}'" );
		
		$this->engine->add_log_event( 4, "ICF_021", array( 'file_id' => $id ) );
		
		//-----------------------------------------------
		// Проверяем, нет ли файла в списках расписания
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'event_id, event_files',
													'from'		=> 'schedule_events',
													'where'		=> "event_state='query'"
													)	);
		$this->engine->DB->simple_exec();
		
		while( $event = $this->engine->DB->fetch_row() )
		{
			foreach( explode( ",", $event['event_files'] ) as $fid => $file )
			{
				if( $file and $file != $id )
				{
					$event['new_files'][] = $id;
				}
				else if( $file == $id )
				{
					$got_it = TRUE;
				}
			}
			
			if( $got_it )
			{
				$to_update[ $event['event_id'] ] = ( is_array( $event['event_files'] ) and $event['event_files'][0] ) ? implode( ",", $event['event_files'] ) : "";
			}
		}
		
		//-----------------------------------------------
		// Если есть, то удаляем его оттуда и обновляем списки
		//-----------------------------------------------
		
		if( is_array( $to_update ) ) foreach( $to_update as $eid => $files )
		{
			$this->engine->DB->do_update( "schedule_events", array( "event_files" => $files ) );
			
			$this->engine->add_log_event( 4, "ICF_022", array( 'event_id' => $eid ) );
		}
		
		//-----------------------------------------------
		// Удаляем закачанный файл физически
		//-----------------------------------------------
		
		$unlink = $path."/".$name;
		
		if( $path and $name and ( !file_exists( $unlink ) or !@unlink( $unlink ) ) )
		{
			$this->engine->add_log_event( 1, "ECF_010", array( 'file_path' => $unlink ) );
			
			$this->error =& $this->engine->lang['error_cant_unlink_file'];
			return FALSE;
		}
		
		$this->engine->add_log_event( 3, "ICF_023", array( 'file_id' => $id ) );
		
		return TRUE;
	}
	
	//-----------------------------------------------
	
	/**
    * Обработка ссылки на скачивание
    * 
    * Проверяет правильность переданной ссылки на скачивание.
    * Возвращает FALSE, если ссылка содержит недопустимые
    * символы в недопустимых местах или обработанную ссылку,
    * если она в порядке.
    * 
    * @param 	array			Параметры ссылки
    *
    * @return 	string			Обработанная ссылка
    * @return	bool			FALSE
    */
	
	function _parse_link( $link )
	{
		$got_it = FALSE;
		
		//-----------------------------------------------
		// Разбиваем путь на составляющие
		//-----------------------------------------------
		
		if( ( $qs = strpos( $link[9], "?" ) ) !== FALSE )
		{
			$more_last = substr( $link[9], $qs );
			
			$link[9] = substr( $link[9], 0, $qs );
		}
		
		$paths = explode( "/", $link[9] );
		
		$count = count( $paths ) - 1;
		
		$last  = $this->engine->classes['input']->parse_unclean_value( $paths[ $count ] );
		$last .= $more_last ? $more_last : "";
		
		unset( $paths[ $count ] );
		
		//-----------------------------------------------
		// Обрабатывам название каждой директории
		//-----------------------------------------------
		
		foreach( $paths as $pid => $path )
		{
			$paths[ $pid ] = $this->engine->classes['input']->parse_unclean_value( urldecode( $path ) );
		}
		
		foreach( $paths as $path ) if( preg_match( "#[".$this->patterns['illegal']."]#", $path ) )
		{
			$got_it = TRUE;
			
			break;
		}
		
		if( $got_it === TRUE ) return FALSE;
		
		if( $link[1] == "http" )
		{
			//-----------------------------------------------
			// Обрабатываем ссылки с переменными для скриптов
			//-----------------------------------------------
			
			if( strpos( $last, "?" ) !== FALSE )
			{
				if( !preg_match( "#[^".$this->patterns['illegal']."]?\?[^&]+(&[\w]+=[^&]+)*#", $last ) ) return FALSE;
			}
			
			//-----------------------------------------------
			// Обрабатываем обычное имя файла
			//-----------------------------------------------
			
			else if( preg_match( "#[".$this->patterns['illegal']."]#", $last ) )
			{
				return FALSE;
			}
		}
		else 
		{
			//-----------------------------------------------
			// Обрабатываем обычное имя файла
			//-----------------------------------------------
			
			if( preg_match( "#[".$this->patterns['illegal']."]#", $last ) )
			{
				return FALSE;
			}
		}
		
		//-----------------------------------------------
		// Формируем конечный путь
		//-----------------------------------------------
		
		$paths[] = &$last;
			
		$url = implode( "/", $paths );
			
		//-----------------------------------------------
		// Возвращаем результат
		//-----------------------------------------------
		
		return $url;
	}
	
	/**
    * Проверка ограничений
    * 
    * Проверяет ограничения на закачку в указанное время для
    * указанного пользователя.
    * 
    * @param 	int				Идентификатор пользователя
    * @param 	int		[opt]	Время начала закачки
    *
    * @return	bool			Результат проверки
    */
	
	function _check_limits( $user, $date=NULL )
	{
		if( !$date ) $date = time();
		
		$year_now = date( "Y" );
		
		//-----------------------------------------------
		// Заполняем кэш
		//-----------------------------------------------
		
		if( !is_array( $this->engine->cache['files']['time_limits'][ $user ] ) )
		{
			$this->engine->DB->simple_construct( array(	'select'	=> 'time_id, time_allow, time_start, time_end, time_every, time_interlace',
														'from'		=> 'schedule_time',
														'where'		=> "time_users LIKE '%,{$user},%'",
														)	);
			$this->engine->DB->simple_exec();
			
			if( !$this->engine->DB->get_num_rows() )
			{
				$this->engine->cache['files']['time_limits'][ $user ] = array();
			}
			else while( $time = $this->engine->DB->fetch_row() )
			{
				$this->engine->cache['files']['time_limits'][ $user ][] = $time;
			}
			
			$this->engine->add_log_event( 4, "ICF_024", array( 'user_id' => $user ) );
		}
		
		//-----------------------------------------------
		// Проверяем ограничения
		//-----------------------------------------------
		
		$allowed = $not_allowed = FALSE;
		
		$wrong_day = FALSE;
		$right_day = FALSE;
			
		foreach( $this->engine->cache['files']['time_limits'][ $user ] as $time )
		{
			if( !$time['time_interlace'] )
			{
				$date_limit['start'] = explode( ":", $time['time_start'] );
				$date_limit['end']   = explode( ":", $time['time_end']   );
					
				$time['time_start'] = strtotime( "{$year_now}-{$date_limit['start'][0]}-{$date_limit['start'][1]} {$date_limit['start'][2]}:{$date_limit['start'][3]}:00" );
				$time['time_end'] = strtotime( "{$year_now}-{$date_limit['end'][0]}-{$date_limit['end'][1]} {$date_limit['end'][2]}:{$date_limit['end'][3]}:00" );
				
				if( $time['time_allow'] )
				{
					//-----------------------------------------------
					// Начало ограничения ранее указанного времени и
					// конец ограничения позднее указанного времени
					//-----------------------------------------------
						
					if( $time['time_start'] <= $date and $time['time_end'] > $date )
					{
						$time['event_start'] = $date;
						
						$this->engine->add_log_event( 4, "ICF_025", array( &$time ) );
						
						$allowed = TRUE;
					}
						
					//-----------------------------------------------
					// Событие не попало в разрешенный промежуток
					//-----------------------------------------------
					
					else
					{
						$time['event_start'] = $date;
						
						$this->engine->add_log_event( 4, "ICF_026", array( &$time ) );
						
						$not_allowed = TRUE;
					}
				}
				else
				{
					//-----------------------------------------------
					// Начало ограничения позднее указанного времени 
					// или конец ограничения ранее указанного времени
					//-----------------------------------------------
						
					if( $time['time_start'] > $date or $time['time_end'] <= $date )
					{
						$time['event_start'] = $date;
						
						$this->engine->add_log_event( 4, "ICF_027", array( &$time ) );
						
						continue;
					}
						
					//-----------------------------------------------
					// Событие попало в запрещенный промежуток
					//-----------------------------------------------
					
					$log_info = array(	'time_id'		=> $time['time_id'],
										'time_start'	=> $time['time_start'],
										'time_end'		=> $time['time_end'],
										'event_start'	=> $date,
										);
					
					$this->engine->add_log_event( 3, "WCF_012", &$log_info, 'warn' );
					
					return FALSE;
				}
			}
			else 
			{
				$date_limit['start'] = explode( ":", $time['time_start'] );
				$date_limit['end']   = explode( ":", $time['time_end']   );
				
				$time['wday'] = date( "w", $date );
				$time['hour'] = date( "H", $date );
				$time['minute'] = date( "i", $date );
				
				if( $time['time_allow'] )
				{
					//-----------------------------------------------
					// Неверный день недели
					//-----------------------------------------------
						
					if( $time['time_every'] != $time['wday'] )
					{
						$time['event_start'] = $date;
						
						$this->engine->add_log_event( 4, "ICF_028", &$time );
						
						$wrong_day = TRUE;
							
						continue;
					}
					
					//-----------------------------------------------
					// Начало ограничения ранее указанного времени и
					// конец ограничения позднее указанного времени
					//-----------------------------------------------
						
					if( ( $date_limit['start'][0] < $time['hour'] or ( $date_limit['start'][0] == $time['hour'] and $date_limit['start'][1] <= $time['minute'] ) ) and
						( $date_limit['end'][0] > $time['hour'] or ( $date_limit['end'][0] == $time['hour'] and $date_limit['end'][1] > $time['minute'] ) ) )
					{
						$time['event_start'] = $date;
						
						$this->engine->add_log_event( 4, "ICF_029", &$time );
						
						$right_day = TRUE;
						$allowed = TRUE;
					}
						
					//-----------------------------------------------
					// Событие не попало в разрешенный промежуток
					//-----------------------------------------------
					
					else
					{
						$time['event_start'] = $date;
						
						$this->engine->add_log_event( 4, "ICF_030", &$time );
						
						$not_allowed = TRUE;
					}
				}
				else 
				{
					//-----------------------------------------------
					// День недели не совпадает с указанным
					//-----------------------------------------------
						
					if( $time['time_every'] != $time['wday'] )
					{
						$time['event_start'] = $date;
						
						$this->engine->add_log_event( 4, "ICF_031", &$time );
						
						continue;
					}
					
					//-----------------------------------------------
					// Начало ограничения позднее указанного времени
					// или конец ограничения ранее указанного времени
					//-----------------------------------------------
						
					if( ( $date_limit['start'][0] > $time['hour'] or ( $date_limit['start'][0] == $time['hour'] and $date_limit['start'][1] > $time['minute'] ) ) or
						( $date_limit['end'][0] < $time['hour'] or ( $date_limit['end'][0] == $time['hour'] and $date_limit['end'][1] <= $time['minute'] ) ) )
					{
						$time['event_start'] = $date;
						
						$this->engine->add_log_event( 4, "ICF_032", &$time );
						
						continue;
					}
							
					//-----------------------------------------------
					// Событие попало в запрещенный промежуток
					//-----------------------------------------------
					
					$log_info = array(	'time_id'		=> $time['time_id'],
										'time_start'	=> $time['time_every'].":".$time['time_start'],
										'time_end'		=> $time['time_every'].":".$time['time_end'],
										'event_start'	=> $date,
										);
					
					$this->engine->add_log_event( 3, "WCF_013", &$log_info, 'warn' );
						
					return FALSE;
				}
			}
		}
		
		if( $wrong_day and !$right_day )
		{
			$this->engine->add_log_event( 4, "WCF_014", 'warn' );
					
			return FALSE;
		}
		
		if( $not_allowed and !$allowed )
		{
			$this->engine->add_log_event( 4, "WCF_015", 'warn' );
						
			return FALSE;
		}
		
		return TRUE;
	}
	
	/**
    * Обновление состояний закачек и событий
    * 
    * Отменяет будущие закачки и блокирует будущие события,
    * время начала которых попадает в запрещенный промежуток.
    * Снимает блокировку с будущих событий, время начала
    * которых не попадает в запрещенные промежутки.
    * 
    * Пошагово:
    * 
    * 1. Получает список всех будущих временных ограничений и
    * 	 на его основе формирует списки ограничений для каждого
    * 	 отдельного пользователя, а также формирует список
    * 	 идентификаторов пользователей, для которых есть
    * 	 ограничения.
    * 2. Получает список всех будущих заданий всех пользователей
    * 	 и для каждого задания проверяет ограничения того
    * 	 пользователя, который является владельцем задания.
    *	 Также формирует списки идентификаторов закачек и
    * 	 заданий, которые попадают в запрещенные временные
    * 	 промежутки.
    * 3. Отменяет все закачки и блокирует все задания из списков.
    * 4. Ставит в очередь все закачки и отменяет блокировку
    * 	 заданий, которые в списках не значатся.
    * 
    * @param 	bool	[opt]	Для всех закачек, кроме общих
    *
    * @return	void
    */
	
	function _update_events_state( $nonshared=FALSE )
	{
		$year_now = date( "Y" );
		$time_now = time();
		
		$blacklist = array();
		
		//-----------------------------------------------
		// Получаем список временных ограничений
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'time_users, time_allow, time_start, time_end, time_every, time_interlace',
													'from'		=> 'schedule_time',
													)	);
		$this->engine->DB->simple_exec();
		
		if( !$this->engine->DB->get_num_rows() )
		{
			$this->engine->add_log_event( 4, "ICF_033" );
			
			return;
		}
		
		$i = 0;
		
		while( $time = $this->engine->DB->fetch_row() )
		{
			if( $time['time_interlace'] )
			{
				$start = explode( ":", $time['time_start'] );
				$end = explode( ":", $time['time_end'] );
				
				$info[ $i ] = array(	'wday'		=> $time['time_every'],
										'hstart'	=> $start[0],
										'hend'		=> $end[0],
										'mstart'	=> $start[1],
										'mend'		=> $end[1],
										);
								
				$type = $time['time_allow'] ? 'allow_i' : 'disallow_i';
			}
			else 
			{
				$date_limit['start'] = explode( ":", $time['time_start'] );
				$date_limit['end']   = explode( ":", $time['time_end']   );
					
				$time['time_start'] = strtotime( "{$year_now}-{$date_limit['start'][0]}-{$date_limit['start'][1]} {$date_limit['start'][2]}:{$date_limit['start'][3]}:00" );
				$time['time_end'] = strtotime( "{$year_now}-{$date_limit['end'][0]}-{$date_limit['end'][1]} {$date_limit['end'][2]}:{$date_limit['end'][3]}:00" );
				
				$info[ $i ] = array(	'start'	=> $time['time_start'],
										'end'	=> $time['time_end'],
										);
										
				$type = $time['time_allow'] ? 'allow_s' : 'disallow_s';
			}
			
			$users = explode( ",", preg_replace( "#^,(.*),$#", "\\1", $time['time_users'] ) );
			
			//-----------------------------------------------
			// Формируем ограничения для каждого пользователя
			//-----------------------------------------------
			
			foreach( $users as $user )
			{
				if( $nonshared and $user == 0 ) continue;
				
				if( !in_array( $user, $blacklist ) ) $blacklist[] = $user;
				
				switch( $type )
				{
					case 'allow_i':
						$times[ $user ]['i']['allow'][] = &$info[ $i ];
						break;
						
					case 'disallow_i':
						$times[ $user ]['i']['disallow'][] = &$info[ $i ];
						break;
						
					case 'allow_s':
						$times[ $user ]['s']['allow'][] = &$info[ $i ];
						break;
						
					case 'disallow_s':
						$times[ $user ]['s']['disallow'][] = &$info[ $i ];
						break;
				}
				
				$this->engine->add_log_event( 4, "ICF_034", array( 'user_id' => $user ) );
			}
			
			$i++;
		}
		
		if( !count( $blacklist ) )
		{
			$this->engine->add_log_event( 4, "ICF_035" );
			
			//-----------------------------------------------
			// Разблокируем разрешенные закачки
			//-----------------------------------------------
			
			if( count( $whitelist['files'] ) )
			{
				$this->engine->DB->do_update( "categories_files", array( "file_state" => "idle" ), "file_id IN('".implode( "','", $whitelist['files'] )."')" );
				
				$this->engine->add_log_event( 3, "ICF_036", array( 'file_ids' => implode( ", ", $whitelist['files'] ) ) );
			}
			
			//-----------------------------------------------
			// Добавляем в очередь разрешенные события
			//-----------------------------------------------
			
			if( count( $whitelist['events'] ) )
			{
				$this->engine->DB->do_update( "schedule_events", array( "event_state" => "query" ), "event_id IN('".implode( "','", $whitelist['events'] )."')" );
				
				$this->engine->add_log_event( 3, "ICF_037", array( 'event_ids' => implode( ", ", $whitelist['events'] ) ) );
			}
			
			return;
		}
		
		//-----------------------------------------------
		// Получаем список будущих заданий пользователей
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> '*',
													'from'		=> 'schedule_events',
													'where'		=> "event_time > {$time_now} AND event_state != 'done' AND event_user IN('".implode( "','", $blacklist )."')",
													)	);
		$this->engine->DB->simple_exec();
		
		if( !$this->engine->DB->get_num_rows() )
		{
			$this->engine->add_log_event( 4, "ICF_038" );
			
			return;
		}
		
		$blacklist = array(	'events' => array(),
							'files'	 => array(),
							);
							
		$whitelist = array(	'events' => array(),
							'files'	 => array(),
							);
		
		while( $event = $this->engine->DB->fetch_row() )
		{
			$got_it = FALSE;
			
			$allowed = $not_allowed = FALSE;
			
			//-----------------------------------------------
			// Единовременные ограничения
			//-----------------------------------------------
			
			if( is_array( $times[ $event['event_user'] ]['s']['allow'] ) ) foreach( $times[ $event['event_user'] ]['s']['allow'] as $time )
			{
				//-----------------------------------------------
				// Начало ограничения ранее текущего времени и
				// конец ограничения позднее текущего времени
				//-----------------------------------------------
				
				if( $time['start'] <= $event['event_time'] and $time['end'] > $event['event_time'] )
				{
					$time['event_start'] = $date;
					$time['event_id']	 = $event['event_id'];
						
					$this->engine->add_log_event( 4, "ICF_039", &$time );
					
					$allowed = TRUE;
					
					continue;
				}
				
				$not_allowed = TRUE;
			}
			
			//-----------------------------------------------
			// Событие не попало в разрешенный промежуток
			//-----------------------------------------------
			
			if( $not_allowed and !$allowed )
			{
				foreach( explode( ",", $event['event_files'] ) as $file ) if( $file and !in_array( $file, $blacklist['files'] ) ) $blacklist['files'][] = $file;
				
				$blacklist['events'][] = $event['event_id'];
				
				$this->engine->add_log_event( 4, "WCF_016", array( 'event_id' => $event['event_id'], 'event_start' => $event['event_time'] ), 'warn' );
				
				continue;
			}
			
			if( is_array( $times[ $event['event_user'] ]['s']['disallow'] ) ) foreach( $times[ $event['event_user'] ]['s']['disallow'] as $time )
			{
				//-----------------------------------------------
				// Начало ограничения позднее текущего времени или
				// конец ограничения ранее текущего времени
				//-----------------------------------------------
				
				if( $time['start'] > $event['event_time'] or $time['end'] <= $event['event_time'] )
				{
					$time['event_start'] = $date;
					$time['event_id']	 = $event['event_id'];
						
					$this->engine->add_log_event( 4, "ICF_040", &$time );
					
					continue;
				}
				
				//-----------------------------------------------
				// Событие попало в запрещенный промежуток
				//-----------------------------------------------
				
				foreach( explode( ",", $event['event_files'] ) as $file ) if( $file and !in_array( $file, $blacklist['files'] ) ) $blacklist['files'][] = $file;
				
				$blacklist['events'][] = $event['event_id'];
				
				$log_info = array(	'time_start'	=> $time['start'],
									'time_end'		=> $time['end'],
									'event_id'		=> $event['event_id'],
									'event_start'	=> $event['event_time'],
									);
					
				$this->engine->add_log_event( 4, "WCF_017", &$log_info, 'warn' );
				
				$got_it = TRUE;
				
				break;
			}
			
			if( $got_it ) continue;
			
			//-----------------------------------------------
			// Чередующиеся ограничения
			//-----------------------------------------------
			
			$date = explode( ":", date( "w:G:i", $event['event_time'] ) );
			
			$wrong_day = FALSE;
			$right_day = FALSE;
			
			$allowed = $not_allowed = FALSE;
			
			if( is_array( $times[ $event['event_user'] ]['i']['allow'] ) ) foreach( $times[ $event['event_user'] ]['i']['allow'] as $time )
			{
				//-----------------------------------------------
				// День недели не совпадает с текущим для события
				//-----------------------------------------------
				
				if( $date[0] != $time['wday'] )
				{
					$time['event_start'] = $date;
					$time['event_id']	 = $event['event_id'];
						
					$this->engine->add_log_event( 4, "ICF_041", &$time );
					
					$wrong_day = TRUE;
					
					continue;
				}
				
				//-----------------------------------------------
				// Начало ограничения ранее текущего времени и
				// конец ограничения позднее текущего времени
				//-----------------------------------------------
				
				if( ( $time['hstart'] < $date[1] or ( $time['hstart'] == $date[1] and $time['mstart'] <= $date[2] ) ) and
					( $time['hend'] > $date[1] or ( $time['hend'] == $date[1] and $time['mend'] > $date[2] ) ) )
				{
					$time['event_start'] = $date;
					$time['event_id']	 = $event['event_id'];
						
					$this->engine->add_log_event( 4, "ICF_042", &$time );
					
					$right_day = TRUE;
					$allowed = TRUE;
					
					continue;
				}
				
				$not_allowed = TRUE;
			}
			
			//-----------------------------------------------
			// Событие не попало в разрешенный промежуток
			//-----------------------------------------------
			
			if( $not_allowed and !$allowed )
			{
				foreach( explode( ",", $event['event_files'] ) as $file ) if( $file and !in_array( $file, $blacklist['files'] ) ) $blacklist['files'][] = $file;
				
				$blacklist['events'][] = $event['event_id'];
				
				$this->engine->add_log_event( 4, "WCF_018", array( 'event_id' => $event['event_id'], 'event_start' => $event['event_time'] ), 'warn' );
				
				continue;
			}
			
			//-----------------------------------------------
			// Событие не попало в разрешенный день недели
			//-----------------------------------------------
			
			if( $wrong_day and !$right_day )
			{
				foreach( explode( ",", $event['event_files'] ) as $file ) if( $file and !in_array( $file, $blacklist['files'] ) ) $blacklist['files'][] = $file;
				
				$blacklist['events'][] = $event['event_id'];
				
				$this->engine->add_log_event( 4, "WCF_019", array( 'event_id' => $event['event_id'], 'event_start' => $event['event_time'] ), 'warn' );
				
				continue;
			}
			
			if( is_array( $times[ $event['event_user'] ]['s']['disallow'] ) ) foreach( $times[ $event['event_user'] ]['s']['disallow'] as $time )
			{
				//-----------------------------------------------
				// День недели не совпадает с текущим для события
				//-----------------------------------------------
				
				if( $date[0] != $time['wday'] )
				{
					$time['event_start'] = $date;
					$time['event_id']	 = $event['event_id'];
						
					$this->engine->add_log_event( 4, "ICF_043", &$time );
					
					continue;
				}
				
				//-----------------------------------------------
				// Начало ограничения позднее текущего времени или
				// конец ограничения ранее текущего времени
				//-----------------------------------------------
					
				if( ( $time['hstart'] > $date[1] or ( $time['hstart'] == $date[1] and $time['mstart'] > $date[2] ) ) or
					( $time['hend'] < $date[1] or ( $time['hend'] == $date[1] and $time['mend'] <= $date[2] ) ) )
				{
					$time['event_start'] = $date;
					$time['event_id']	 = $event['event_id'];
						
					$this->engine->add_log_event( 4, "ICF_044", &$time );
					
					continue;
				}
				
				//-----------------------------------------------
				// Событие попало в запрещенный промежуток
				//-----------------------------------------------
				
				foreach( explode( ",", $event['event_files'] ) as $file ) if( $file and !in_array( $file, $blacklist['files'] ) ) $blacklist['files'][] = $file;
				
				$blacklist['events'][] = $event['event_id'];
				
				$log_info = array(	'time_start'	=> $time['wday'].":".$time['hstart'],
									'time_end'		=> $time['wday'].":".$time['hend'],
									'event_id'		=> $event['event_id'],
									'event_start'	=> $event['event_time'],
									);
					
				$this->engine->add_log_event( 4, "WCF_020", &$log_info, 'warn' );
				
				$got_it = TRUE;
				
				break;
			}
			
			//-----------------------------------------------
			// Для события нет ограничений
			//-----------------------------------------------
			
			if( !$got_it and $event['event_state'] == 'blocked' )
			{
				foreach( explode( ",", $event['event_files'] ) as $file ) if( $file and !in_array( $file, $whitelist['files'] ) ) $whitelist['files'][] = $file;
				
				$whitelist['events'][] = $event['event_id'];
				
				$this->engine->add_log_event( 4, "ICF_045", array( 'event_id' => $event['event_id'], 'event_start' => $event['event_time'] ) );
			}
		}
		
		unset( $times );
		
		//-----------------------------------------------
		// Блокируем запрещенные закачки
		//-----------------------------------------------
		
		if( count( $blacklist['files'] ) )
		{
			$this->engine->DB->do_update( "categories_files", array( "file_state" => "blocked" ), "file_id IN('".implode( "','", $blacklist['files'] )."')" );
			
			$this->engine->add_log_event( 2, "WCF_021", array( 'file_ids' => implode( ", ", $blacklist['files'] ) ) );
		}
		
		//-----------------------------------------------
		// Блокируем запрещенные события
		//-----------------------------------------------
		
		if( count( $blacklist['events'] ) )
		{
			$this->engine->DB->do_update( "schedule_events", array( "event_state" => "blocked" ), "event_id IN('".implode( "','", $blacklist['events'] )."')" );
			
			$this->engine->add_log_event( 2, "WCF_022", array( 'event_ids' => implode( ", ", $blacklist['events'] ) ) );
		}
		
		//-----------------------------------------------
		// Разблокируем разрешенные закачки
		//-----------------------------------------------
		
		if( count( $whitelist['files'] ) )
		{
			$this->engine->DB->do_update( "categories_files", array( "file_state" => "idle" ), "file_id IN('".implode( "','", $whitelist['files'] )."')" );
			
			$this->engine->add_log_event( 3, "ICF_046", array( 'file_ids' => implode( ", ", $whitelist['files'] ) ) );
		}
		
		//-----------------------------------------------
		// Добавляем в очередь разрешенные события
		//-----------------------------------------------
		
		if( count( $whitelist['events'] ) )
		{
			$this->engine->DB->do_update( "schedule_events", array( "event_state" => "query" ), "event_id IN('".implode( "','", $whitelist['events'] )."')" );
			
			$this->engine->add_log_event( 3, "ICF_047", array( 'event_ids' => implode( ", ", $whitelist['events'] ) ) );
		}
	}
	
	/**
    * Первоначальная сортировка категорий
    * 
    * Сортирует категории по идентификатору родительской категории
    * и по названию.
    * Сортировка необходима, если используется кодирование строк в
    * БД для работы с нелатинскими символами, т.к. в данном случае
    * при выборке из БД сортировка кодированных строк может не
    * совпадать с сортировкой обработанных строк.
    * 
    * @param 	array			Параметры первой категории
    * @param 	array			Параметры второй категории
    *
    * @return	int				Результат сравнения
    */
	
	function _sort_cats_basic( $a, $b )
	{
		if( $a['cat_root'] != $b['cat_root'] ) return strcmp( $a['cat_root'], $b['cat_root'] );
		if( $a['cat_name'] != $b['cat_name'] ) return strcmp( $a['cat_name'], $b['cat_name'] );
		
		return 0;
	}
	
	/**
    * Сортировка категорий
    * 
    * Рекурсивная функция для создания правильной структуры категорий.
    * 
    * @param 	int		[opt]	Текущий идентификатор категории
    * @param 	int		[opt]	Текущий уровень
    * @param 	int		[opt]	Максимальный уровень
    *
    * @return	void
    */
	
	function _sort_cats( $id=0, $level=1, $max_level=5 )
	{
		if( $level > $max_level )
		{
			return;
		}
		
		foreach( $this->cats['unsorted'] as $cid => $cat ) if( $cat['cat_root'] == $id )
		{
			$cat['cat_level'] = $level;
			
			$this->cats['sorted'][] = $cat;
			
			unset( $this->cats['unsorted'][ $cid ] );
			
			$level_up = $level + 1;
			
			$this->_sort_cats( $cat['cat_id'], $level_up, $max_level );
		}
	}
	
	/**
    * Обработка названия категории
    * 
    * Определяет уровень вложенности категории и добавляет
    * соответствующую этому уровню псевдографику перед
    * названием категории.
    * 
    * @param 	array			Параметры категории
    *
    * @return	string			Обработанное название
    */
	
	function _get_category_name( $cat )
	{
		for( $i=2; $i <= $cat['cat_level']; $i++ )
		{
			//-----------------------------------------------
			// Получаем информацию о родительской категории
			//-----------------------------------------------
			
			$j = $i;
					
			while( true )
			{
				$cat['cat_up'] =& $this->cats['sorted'][ $cat['cat_cid'] - ( $cat['cat_level'] - $j ) ];
						
				if( !$cat['cat_up'] or $cat['cat_up']['cat_level'] == $i ) break;
						
				$j--;
			}
					
			$cat['cat_up_rel'] = ( $cat['cat_up']['cat_root'] != $cat['cat_root'] and $cat['cat_up']['cat_relation'] ) ? 1 : 0;
			
			//-----------------------------------------------
			// Выводим псевдографику для каждого уровня вложенности
			//-----------------------------------------------
			
			if( $cat['cat_down'] )
			{
				if( $cat['cat_level'] == $i )
				{
					if( $cat['cat_relation'] )
					{
						$name .= "&nbsp;|--";
					}
					else 
					{
						$name .= "&nbsp;`--";
					}
				}
				else 
				{
					if( $cat['cat_up_rel'] )
					{
						$name .= "&nbsp;|&nbsp;&nbsp;";
					}
					else 
					{
						$name .= "&nbsp;&nbsp;&nbsp;&nbsp;";
					}
					
					unset( $cat['cat_up'] );
				}
			}
			else 
			{
				if( $cat['cat_children'] )
				{
					$name .= "&nbsp;|--";
				}
				else if( $i == $cat['cat_level'] )
				{
					$name .= "&nbsp;`--";
				}
				else 
				{
					$name .= "&nbsp;&nbsp;&nbsp;&nbsp;";
				}
			}
		}
		
		//-----------------------------------------------
		// Возвращаем результат
		//-----------------------------------------------
		
		return $name."&nbsp;".$cat['cat_name'];
	}
	
}

?>
