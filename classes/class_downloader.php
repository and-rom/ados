<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			Загрузчик модулей
*/

/**
* Класс, содержащий функции для вызова
* функций установленных модулей.
*
* @author	DINI
* @version	1.3.9 (build 74)
*/

class downloader
{
	/**
	* Текст ошибки
	*
	* @var string
	*/

	var $error 	= "";
	
	/**
	* Информация о модуле
	*
	* @var array
	*/

	var $module	= array(	'key'		=> "",
							'id'		=> 0,
							'path'		=> "",
							'version'	=> "",
							'class'		=> NULL,
							'settings'	=> array(),
							);
							
	/**
	* Информация о закачиваемом файле
	*
	* @var array
	*/
	
	var $file	= array(	'file_dl_speed'	=> 0,
							'file_dl_left'	=> 0,
							'file_dl_time'	=> 0,
							'file_dl_id'	=> "",
							);
							
	/**
	* Конструктор класса (не страндартный PHP)
	* 
	* Загружает языковые строки.
	* 
	* @return	bool	TRUE
	*/
	
	function __class_construct()
	{
		$this->engine->load_lang( "modules" );
		
		return TRUE;
	}
	
	/**
	* Проверка существования модуля
	* 
	* Ищет указанный в настройках исполняемый файл
	* модуля и проверяет, является ли он действительно
	* исполняемым.
	* 
	* @param 	string	[opt]	Ключ модуля
	* @param 	int		[opt]	ID модуля
	* 
	* @return	bool			Результат выполнения операции
	*/
	
	function module_exists( $key="", $id=0 )
	{
		//-----------------------------------------------
		// Получаем информацию о модуле
		//-----------------------------------------------
		
		if( $this->get_module_info( $key, $id ) === FALSE )
		{
			return FALSE;
		}
		
		//-----------------------------------------------
		// Проверяем наличие файла и его атрибуты
		//-----------------------------------------------
		
		if( !file_exists( $this->module['path'] ) or !is_executable( $this->module['path'] ) )
		{
			$this->error =& $this->engine->lang['error_module_file_error'];
			
			$this->engine->add_log_event( 1, "ECD_001", array( 'module_key' => $key, 'module_id' => $id, 'module_path' => $this->module['path'] ) );
			
			return FALSE;
		}
		
		$this->engine->add_log_event( 4, "ICD_001", array( 'module_key' => $key, 'module_id' => $id ) );
		
		return TRUE;
	}
	
	/**
	* Получение информации о модуле
	* 
	* Получает идентификатор модуля, его ключ и путь
	* до исполняемого файла программы по переданному
	* или заранее установленному идентификатору или
	* ключу.
	* 
	* @param 	string	[opt]	Ключ модуля
	* @param 	int		[opt]	ID модуля
	* 
	* @return	bool			Результат выполнения операции
	*/
	
	function get_module_info( $key="", $id=0 )
	{
		//-----------------------------------------------
		// Проверяем указанный ключ или идентификатор
		//-----------------------------------------------
		
		$key = $key ? $key : ( $id ? "" : $this->module['key'] );
		$id = $id ? $id : ( $key ? 0 : $this->module['id'] );
		
		//-----------------------------------------------
		// Получаем данные из БД
		//-----------------------------------------------
		
		$where = ( $key == "" ) ? "module_id='{$id}'" : "module_key='{$key}'";
		
		$module = $this->engine->DB->simple_exec_query( array(	'select'	=> 'module_id, module_key, module_engine_version_support, module_enabled',
																'from'		=> 'modules_list',
																'where'		=> &$where,
																)	);
																
		if( !$module['module_id'] )
		{
			$this->error =& $this->engine->lang['error_wrong_module_id'];
			
			$this->engine->add_log_event( 1, "ECD_002", array( 'module_key' => $key, 'module_id' => $id ) );
			
			return FALSE;
		}
			
		$this->module['key']	 =& $module['module_key'];
		$this->module['id']		 =& $module['module_id'];
		$this->module['enabled'] =& $module['module_enabled'];
		$this->module['version'] =  str_replace( " [plus]", "", $module['module_engine_version_support'] );
		
		//-----------------------------------------------
		// Получаем настройки модуля
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'setting_key, setting_default, setting_value',
													'from'		=> 'modules_settings',
													'where'		=> "setting_module='{$this->module['id']}'"
													)	);
		$this->engine->DB->simple_exec();
		
		while( $setting = $this->engine->DB->fetch_row() )
		{
			$this->module['settings'][ $setting['setting_key'] ] = ( $setting['setting_value'] != "" and $setting['setting_value'] != $setting['setting_default'] ) ? $setting['setting_value'] : $setting['setting_default'];
		}
		
		//-----------------------------------------------
		// Проверяем путь до исполняемого файла
		//-----------------------------------------------
		
		if( !$this->module['settings']['engine_path'] or !preg_match( "#(/|\w+)+#", $this->module['settings']['engine_path'] ) )
		{
			$this->error =& $this->engine->lang['error_wrong_module_path'];
			
			$this->engine->add_log_event( 1, "ECD_003", array( 'module_path' => $this->module['settings']['engine_path'] ) );
			
			return FALSE;
		}
		
		$this->module['path'] =& $this->module['settings']['engine_path'];
		
		$this->engine->add_log_event( 4, "ICD_002", array( 'module_key' => $module['module_key'], 'module_id' => $module['module_id'] ) );
			
		return TRUE;
	}
	
	/**
	* Загрузка модуля
	* 
	* Инициализирует основной класс модуля и устанавливает
	* для него необходимые параметры.
	* 
	* @param 	string	[opt]	Ключ модуля
	* @param 	int		[opt]	ID модуля
	* @param	array	[opt]	Переменные, передаваемые конструктору класса
	* 
	* @return	bool			Результат выполнения операции
	*/
	
	function load_module( $key="", $id=0, $construct_params=array() )
	{
		if( is_object( $this->module['class'] ) and $this->module['id'] == $id or ( $this->module['key'] and $this->module['key'] == $key ) ) return TRUE;
		
		//-----------------------------------------------
		// Получаем идентификатор модуля
		//-----------------------------------------------
		
		if( $this->get_module_info( $key, $id ) === FALSE )
		{
			return FALSE;
		}
		
		//-----------------------------------------------
		// Проверяем наличие файла модуля
		//-----------------------------------------------
		
		$class = "module_{$this->module['key']}";
		$file  = $this->engine->home_dir."modules/{$class}.php";
		
		if( !file_exists( $file ) )
		{
			$this->error =& $this->engine->lang['error_cant_find_module_file'];
			
			$this->engine->add_log_event( 1, "ECD_004", array( 'module_file' => $file ) );
			
			return FALSE;
		}
		
		//-----------------------------------------------
		// Загружаем класс модуля
		//-----------------------------------------------
		
		require_once( $file );
		
		$this->module['class'] = new $class;
		$this->module['class']->engine =& $this->engine;
		$this->module['class']->path =& $this->module['path'];
		$this->module['class']->settings =& $this->module['settings'];
		
		if( method_exists( &$this->module['class'], "__class_construct" ) and call_user_func_array( array( &$this->module['class'], "__class_construct" ), $construct_params ) !== TRUE )
		{
			$this->error =& $this->engine->lang['error_cant_load_module_class'];
			
			return FALSE;
		}
		
		return TRUE;
	}
	
	/**
	* Выгрузка модуля
	* 
	* Выгружает текщий модуль, сбрасывая все настройки.
	* 
	* @return	void
	*/
	
	function unload_module()
	{
		unset( $this->module );
		
		$this->module = array(	'key'		=> "",
								'id'		=> 0,
								'path'		=> "",
								'version'	=> "",
								'class'		=> NULL,
								'settings'	=> array(),
								);
	}
	
	/**
	* Начало закачки
	* 
	* Передает управление функции начала закачки для
	* загруженного модуля.
	* Полученную строку для старта закачки сохраняет
	* в директорию для выполнения CRON'ом.
	* 
	* @param	array			Параметры закачиваемого файла
	* 
	* @return	bool			Результат выполнения операции
	*/
	
	function download_start( $file )
	{
		//-----------------------------------------------
		// Генерируем идентификатор файла
		//-----------------------------------------------
		
		$this->file['file_dl_id'] = $file['file_dl_id'] = md5( microtime().rand( 0, 100 ) );
		
		//-----------------------------------------------
		// Обрабатываем файл
		//-----------------------------------------------
		
		$return = $this->download_parse( &$file, 'start' );
		
		if( $return === TRUE )
		{
			$this->engine->add_log_event( 4, "ICD_003", array( 'file_dl_id' => $file['file_dl_id'], 'file_id' => $file['file_id'], 'file_name' => $file['file_name'] ) );
		}
		
		return $return;
	}
	
	/**
	* Возобновление закачки
	* 
	* Проверяет, поддерживает ли модуль функцию
	* возобновления закачки и, если да, выполняет ее.
	* 
	* @param	array			Параметры закачиваемого файла
	* 
	* @return	bool			Результат выполнения операции
	*/
	
	function download_continue( $file )
	{
		//-----------------------------------------------
		// Проверяем возможность возобновления закачки
		//-----------------------------------------------
		
		if( !method_exists( &$this->module['class'], "std_download_continue" ) )
		{
			$this->error =& $this->engine->lang['error_continue_unsupported'];
			
			$this->engine->add_log_event( 2, "WCD_001", array( 'module_key' => $this->module['key'], 'module_id' => $this->module['id'] ) );
			
			return FALSE;
		}
		
		//-----------------------------------------------
		// Обрабатываем файл
		//-----------------------------------------------
		
		$return = $this->download_parse( &$file, 'continue' );
		
		if( $return === TRUE )
		{
			$this->engine->add_log_event( 4, "ICD_004", array( 'file_dl_id' => $file['file_dl_id'], 'file_id' => $file['file_id'], 'file_name' => $file['file_name'] ) );
		}
		
		return $return;
	}
	
	/**
	* Обработка закачки
	* 
	* Проверяет параметры закачки и при отсутствии ошибок
	* добавляет информацию о закачке в кэш.
	* 
	* @param	array			Параметры закачиваемого файла
	* @param	array			Тип действия
	* 
	* @return	bool			Результат выполнения операции
	*/
	
	function download_parse( $file, $type )
	{
		//-----------------------------------------------
		// Обрабатываем авторизационную информацию
		//-----------------------------------------------
		
		if( !is_array( $this->engine->cache['downloader']['domains'] ) ) $this->_get_auth_info( $file['file_link'] );
		
		//-----------------------------------------------
		// Получаем размер файла
		//-----------------------------------------------
		
		if( ( $file['file_size'] = $this->_check_link( $file['file_link'] ) ) === FALSE )
		{
			$this->engine->add_log_event( 1, "ECD_005", array( 'file_id' => $file['file_id'], 'file_dl_id' => $file['file_dl_id'], 'file_name' => $file['file_name'] ) );
			
			return FALSE;
		}
		
		//-----------------------------------------------
		// Проверяем ограничение на скорость
		//-----------------------------------------------
		
		if( !is_array( $this->engine->cache['download']['speed'] ) or !in_array( $file['file_user'], $this->engine->cache['download']['speed'] ) )
		{
			$speed = $this->engine->DB->simple_exec_query( array(	'select'	=> 'user_id, user_max_speed',
																	'from'		=> 'users_list',
																	'where'		=> "user_id='{$file['file_user']}'"
																	)	);
																	
			if( $speed['user_max_speed'] == 0 )
			{
				$this->engine->DB->do_update( "categories_files", array( 'file_state' => 'error' ), "file_id='{$file['file_id']}'" );
				
				$this->error = &$this->engine->lang['error_null_download_speed'];
			
				$this->engine->add_log_event( 4, "ECD_006", array( 'file_id' => $file['file_id'], 'file_name' => $file['file_name'], 'file_dl_id' => $file['file_dl_id'], 'file_user' => $file['file_user'] ) );
			
				return FALSE;
			}
			
			if( $speed['user_max_speed'] == -1 )
			{
				$this->engine->cache['download']['speed'][ $speed['user_id'] ]['limit'] = -1;
			}
			else 
			{
				$speed['user_max_speed'] = floor( $speed['user_max_speed'] / 8 * 1024 );
			
				$bandwidth = $this->engine->DB->simple_exec_query( array(	'select'	=> 'SUM( file_dl_bandwidth ) AS total',
																			'from'		=> 'categories_files',
																			'where'		=> "file_state='running' AND file_user='{$file['file_user']}'"
																			)	);
				
				if( ( $free = ( $speed['user_max_speed'] - ceil( $bandwidth['total'] ) ) ) <= 0 )
				{
					$this->error = &$this->engine->lang['error_null_bandwidth'];
					
					if( $file['file_state'] != 'query' ) $this->engine->DB->do_update( "categories_files", array( 'file_state' => 'query' ), "file_id='{$file['file_id']}'" );
				
					$this->engine->add_log_event( 2, "WCD_002", array( 'file_id' => $file['file_id'], 'file_name' => $file['file_name'], 'file_dl_id' => $file['file_dl_id'], 'file_user' => $file['file_user'] ) );
				
					return FALSE;
				}
																		
				$this->engine->cache['download']['speed'][ $speed['user_id'] ]['limit'] = $speed['user_max_speed'];
				$this->engine->cache['download']['speed'][ $speed['user_id'] ]['free'] = $free;
			}
		}
		
		//-----------------------------------------------
		// Еще раз проверяем, если информация уже есть
		//-----------------------------------------------
		
		else if( $this->engine->cache['download']['speed'][ $file['file_user'] ]['limit'] != -1 )
		{
			if( $this->engine->cache['download']['speed'][ $file['file_user'] ]['free'] > $this->engine->cache['download']['speed'][ $file['file_user'] ]['limit'] )
			{
				$this->engine->cache['download']['speed'][ $speed['user_id'] ]['free'] = $this->engine->cache['download']['speed'][ $file['file_user'] ]['limit'];
			}
		}
		
		//-----------------------------------------------
		// Начинаем (возобновляем) закачку
		//-----------------------------------------------
		
		$params = $type == 'start'	? $this->module['class']->std_download_start( &$file )
									: $this->module['class']->std_download_continue( &$file );
		
		if( $params === FALSE )
		{
			$this->error =& $this->module['class']->error;
			
			return FALSE;
		}
		
		//-----------------------------------------------
		// Проверяем наличие свободного места на диске
		//-----------------------------------------------
		
		$size_left = $type == 'start' ? $params['size'] : $file['file_dl_left'];
		
		if( $size_left > ( $free_space = $this->engine->get_free_space( &$file['file_user'] ) ) )
		{
			$this->error =& $this->engine->lang['error_not_enough_space'];
			
			$this->engine->add_log_event( 1, "ECD_007", array( 'file_id' => $file['file_id'], 'file_name' => $file['file_name'], 'file_size' => $params['size'], 'free_space' => $free_space ) );
			
			return FALSE;
		}
		
		//-----------------------------------------------
		// Добавляем файл в кэш
		//-----------------------------------------------
		
		$this->engine->cache['download']['files'][ $file['file_user'] ][] = array( 'file' => &$file, 'params' => &$params );
		
		return TRUE;
	}
	
	/**
	* Приостановка закачки
	* 
	* Проверяет, поддерживает ли модуль функцию
	* приостановки закачки и, если да, выполняет ее.
	* Обновляет сведения о закачке в БД.
	* 
	* @param	array			Параметры закачиваемого файла
	* 
	* @return	bool			Результат выполнения операции
	*/
	
	function download_pause( $file )
	{
		if( !method_exists( &$this->module['class'], "std_download_pause" ) )
		{
			$this->error =& $this->engine->lang['error_pause_unsupported'];
			
			$this->engine->add_log_event( 2, "WCD_003", array( 'module_key' => $this->module['key'], 'module_id' => $this->module['id'] ) );
			
			return FALSE;
		}
		
		if( !$file['file_dl_range'] )
		{
			$this->error =& $this->engine->lang['error_file_unranged'];
			
			$this->engine->add_log_event( 2, "WCD_004", array( 'file_dl_id' => $file['file_dl_id'], 'file_id' => $file['file_id'], 'file_name' => $file['file_name'] ) );
			
			return FALSE;
		}
		
		if( $this->module['class']->std_download_pause( &$file ) === FALSE )
		{
			$this->error =& $this->module['class']->error;
			
			return FALSE;
		}
		
		if( !$this->engine->config['reserve_paused_slots'] )
		{
			$this->engine->cache['download']['speed'][ $file['file_user'] ]['free'] += $file['file_dl_bandwidth'];
		}
		
		$this->engine->add_log_event( 4, "ICD_005", array( 'file_dl_id' => $file['file_dl_id'], 'file_id' => $file['file_id'], 'file_name' => $file['file_name'] ) );
		
		return TRUE;
	}
	
	/**
	* Остановка закачки
	* 
	* Передает управление функции остановки закачки для
	* загруженного модуля.
	* 
	* @param	array			Параметры закачиваемого файла
	* 
	* @return	bool			Результат выполнения операции
	*/
	
	function download_stop( $file )
	{
		if( $this->module['class']->std_download_stop( &$file ) === FALSE )
		{
			$this->error =& $this->module['class']->error;
			
			return FALSE;
		}
		
		$this->engine->cache['download']['speed'][ $file['file_user'] ]['free'] += $file['file_dl_bandwidth'];
		
		$this->engine->add_log_event( 4, "ICD_006", array( 'file_dl_id' => $file['file_dl_id'], 'file_id' => $file['file_id'], 'file_name' => $file['file_name'] ) );
		
		return TRUE;
	}
	
	/**
	* Запуск закачек
	* 
	* Запускает ранее обработанные закачки, параметры
	* которых были сохранены в кэше.
	* 
	* @return	bool			Результат выполнения операции
	*/
	
	function download_start_cached()
	{
		//-----------------------------------------------
		// Проверяем, есть ли закачки для запуска
		//-----------------------------------------------
		
		if( !is_array( $this->engine->cache['download']['files'] ) )
		{
			$this->error =& $this->engine->lang['files_cache_is_empty'];
			
			$this->engine->add_log_event( 1, "ECD_008" );
			
			return FALSE;
		}
		
		$time_now = time();
		
		foreach( $this->engine->cache['download']['files'] as $uid => $files )
		{
			//-----------------------------------------------
			// Определяем среднюю скорость канала
			//-----------------------------------------------
			
			if( $this->engine->cache['download']['speed'][ $uid ]['limit'] != -1 )
			{
				$bandwidth = $real_bandwidth = sprintf( "%0.4f", ( $this->engine->cache['download']['speed'][ $uid ]['free'] / count( $files ) ) );
				$bandwidth = floor( $bandwidth );
			}
			else
			{
				$bandwidth = -1;
			}
			
			//-----------------------------------------------
			// Создаем CRON файлы и обновляем инфу о закачках
			//-----------------------------------------------
			
			foreach( $files as $file )
			{
				if( $bandwidth != -1 ) $file['params']['string'] .= " ".$file['params']['speed']." ".$bandwidth;
				
				if( $this->_create_cron_file( &$file['params']['string'], &$file['file']['file_dl_id'] ) === FALSE ) $array['file_state'] = "error";
				else $array['file_state'] = "running";
				
				$array['file_dl_id'] 	 	= &$file['file']['file_dl_id'];
				$array['file_size']	 	 	= &$file['params']['size'];
				$array['file_dl_bandwidth'] = $real_bandwidth;
				$array['file_dl_start'] 	= ( $file['file_dl_start'] or $file['file_state'] == 'paused' ) ? $file['file_dl_start'] : time();
				
				if( strpos( $file['file']['file_name'], "." ) === FALSE and $file['params']['name'] ) $array['file_name'] = &$file['params']['name'];
			
				$this->engine->DB->do_update( "categories_files", &$array, "file_id='{$file['file']['file_id']}'" );
				
				$this->engine->add_log_event( 4, "ICD_007", array( 'file_dl_id' => $file['file']['file_dl_id'], 'file_id' => $file['file']['file_id'], 'file_dl_bandwidth' => $bandwidth ) );
			}
			
			//-----------------------------------------------
			// Делаем запись о том, что канал забит
			//-----------------------------------------------
			
			if( $bandwidth == -1 ) continue;
			
			$this->engine->cache['download']['speed'][ $uid ]['free'] = 0;
			
			$this->engine->add_log_event( 4, "ICD_008", array( 'user_id' => $uid ) );
		}
		
		unset( $this->engine->cache['download']['files'] );
		
		return TRUE;
	}
	
	/**
	* Проверка состояния закачки
	* 
	* Проверяет, происходит ли закачка файла в данный
	* момент и если нет, то не была ли закачка прервана
	* из-за ошибки.
	* 
	* @param	array			Параметры закачиваемого файла
	* 
	* @return	bool			Результат выполнения операции
	*/
	
	function download_is_running( $file )
	{
		if( $this->module['class']->std_download_is_running( &$file ) === FALSE )
		{
			if( $this->module['class']->error )
			{
				$this->error =& $this->module['class']->error;
				
				$file_cron = $this->engine->config['cron_path']."/ados_{$file['file_dl_id']}.sh";
				$file_temp = $this->engine->config['save_path']."/_tmp/ados_{$file['file_dl_id']}.sh";
				$file_ados = $this->engine->config['save_path']."/_tmp/{$file['file_id']}_{$file['file_user']}_{$file['file_dl_id']}.ados";
				
				if( $this->error == 1 and ( file_exists( $file_cron ) or file_exists( $file_temp ) or ( !file_exists( $file_ados ) and ( time() - $file['file_dl_last_start'] ) < 180 ) ) )
				{
					$this->error = FALSE;
					
					return TRUE;
				}
			}
			
			return FALSE;
		}
		else 
		{
			$this->error = FALSE;
			
			return TRUE;
		}
	}
	
	/**
	* Проверка возможности восстановления
	* 
	* Проверяет, есть ли возможность восстановить закачку
	* после возникшей ошибки.
	* Для этого проверяет наличие уже закачанной информации
	* и поддержку ассоциированным модулем возможности докачки.
	* 
	* @param	array			Параметры закачиваемого файла
	* 
	* @return	bool			Результат выполнения операции
	*/
	
	function download_can_restore( $file )
	{
		$file_temp = $this->engine->config['save_path']."/_tmp/{$file['file_id']}_{$file['file_user']}_{$file['file_dl_id']}.ados";
		
		if( file_exists( $file_temp ) and method_exists( &$this->module['class'], "std_download_continue" ) ) return TRUE;
		
		$this->engine->add_log_event( 4, "ICD_009", array( 'file_dl_id' => $file['file_dl_id'], 'file_id' => $file['file_id'], 'file_name' => $file['file_name'] ) );
		
		return FALSE;
	}
	
	/**
	* Перезапуск закачки при простое
	* 
	* Проверяет, нет ли у закачки простоя.
	* Если есть, то выполняет ее перезапуск.
	* 
	* @param	array			Параметры закачиваемого файла
	* 
	* @return	bool			Результат выполнения операции
	*/
	
	function download_restart( $file )
	{
		$time_now = time();
			
		$file_name = $this->engine->config['save_path']."/_tmp/{$file['file_id']}_{$file['file_user']}_{$file['file_dl_id']}.ados";
			
		$current_check = array(	'file_size'		=> filesize( $file_name ),
								'file_time'		=> filemtime( $file_name ),
								'check_time'	=> $time_now,
								);
			
		$last_check = $this->engine->DB->simple_exec_query( array(	'select'	=> 'cache_value',
																	'from'		=> 'system_cache',
																	'where'		=> "cache_uid='{$file['file_dl_id']}'"
																	)	);
				
		if( $last_check )
		{
			$last_check = unserialize( stripslashes( $last_check['cache_value'] ) );
					
			if( $last_check['file_size'] == $current_check['file_size'] and $last_check['file_time'] == $current_check['file_time'] and ( $time_now - $last_check['file_time'] >= ( $this->engine->config['restart_after'] * 60 ) ) )
			{
				if( $this->download_pause( &$file ) === FALSE ) return TRUE;
				if( $this->download_continue( &$file ) === FALSE ) return FALSE;
					
				$this->engine->add_log_event( 3, "ICD_010", array( 'file_id' => $file['file_id'], 'file_name' => $file['file_name'], 'file_dl_id' => $file['file_dl_id'] ) );
			}
				
			$this->engine->DB->do_update( "system_cache", array( "cache_value" => serialize( $current_check ), 'cache_added' => $time_now ), "cache_uid='{$file['file_dl_id']}'" );
		}
		else 
		{
			$this->engine->DB->do_insert( "system_cache", array( "cache_value" => serialize( $current_check ), 'cache_added' => $time_now, 'cache_uid' => $file['file_dl_id'] ) );
		}
			
		return TRUE;
	}
	
	/**
	* Обновление параметров закачки
	* 
	* Обновляет текущие параметры закачки: время, размер и
	* скорость закачиваемого файла.
	* 
	* @param	array			Параметры закачиваемого файла
	* 
	* @return	bool			Результат выполнения операции
	*/
	
	function update_download_state( $file )
	{
		$this->_clear_file();
		
		//-----------------------------------------------
		// Получаем обновленные параметры
		//-----------------------------------------------
		
		if( $this->module['class']->std_get_download_info( &$file ) === FALSE )
		{
			$this->error =& $this->module['class']->error;
			
			return FALSE;
		}
		
		//-----------------------------------------------
		// Записываем их в БД и в переменную класса
		//-----------------------------------------------
		
		$this->engine->DB->do_update( "categories_files", &$this->module['class']->state, "file_id='{$file['file_id']}'" );
		
		foreach( $this->module['class']->state as $name => $value ) $this->file[ $name ] = $value;
		
		$this->file['file_dl_id'] &= $file['file_dl_id'];
		
		$this->engine->add_log_event( 4, "ICD_011", &$this->file );
	}
	
	//-----------------------------------------------
	
	/**
	* Очистка информации о файле
	* 
	* Очищает массив с информацией о текущем файле с тем,
	* чтобы в нем не осталась информация о предыдущем файле.
	* 
	* @return	void
	*/
	
	function _clear_file()
	{
		$this->file['file_dl_speed'] = 0;
		$this->file['file_dl_left']	 = 0;
		$this->file['file_dl_time']  = 0;
	}
	
	/**
	* Получение списка параметров авторизации
	* 
	* Загружает в кэш  список параметров авторизаци для
	* доменов.
	* 
	* @return 	void
	*/
	
	function _get_auth_info()
	{
		//-------------------------------------------------
		// Получаем параметры авторизации
		//-------------------------------------------------
			
		$this->engine->DB->simple_construct( array(	'select'	=> 'domain_name, domain_user, domain_pass, domain_share',
													'from'		=> 'domains_list',
													)	);
		$this->engine->DB->simple_exec();
			
		if( !$this->engine->DB->get_num_rows() )
		{
			$this->engine->cache['downloader']['domains'] = array();
				
			return;
		}
			
		while( $auth = $this->engine->DB->fetch_row() )
		{
			$auth['domain_user'] = $this->engine->classes['input']->parse_unclean_value( $auth['domain_user'] );
			$auth['domain_pass'] = $this->engine->classes['input']->parse_unclean_value( $auth['domain_pass'] );
			
			$this->engine->cache['downloader']['domains'][ $auth['domain_share'] ? 'admin' : 'shared' ][] = $auth;
		}
	}
	
	/**
	* Проверка ссылки
	* 
	* Проверяет переданную ссылку на ошибки и переадресацию.
	* В случае переадресации рекурсивно вызывает себя.
	* Для избежания вечной рекурсии, которую может вызвать,
	* например, переадресация двух страниц друг на друга,
	* количество выполняемых переадресаций ограничено тремя.
	* 
	* @param 	array			Ссылка на файл
	* @param 	int		[opt]	Количество переадресаций
	* 
	* @return	int				Размер файла
	* @return 	bool			FALSE
	*/
	
	function _check_link( $link, $depth=1 )
	{
		//-------------------------------------------------
		// Проверяем номер текущей переадресации
		//-------------------------------------------------
		
		if( $depth > 3 )
		{
			$this->error = &$this->engine->lang['error_too_many_redirects'];
			
			$this->engine->add_log_event( 1, "ECD_009", array( 'file_link' => preg_replace( "^.*:.*@(.*)$", "username:pass@\\1", $link ) ) );
			
			return FALSE;
		}
		
		//-------------------------------------------------
		// Проверяем протокол
		//-------------------------------------------------
		
		$protocol = preg_match( "#^http://#", $link ) ? "http://" : "ftp://";
		
		//-------------------------------------------------
		// Получаем имя домена
		//-------------------------------------------------
		
		$ip_num = '(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])';
		
		preg_match( "#^$protocol(([a-z0-9\-]+\.)*([a-z0-9\-]+\.)([a-zA-Z]{2,5})|$ip_num\\.$ip_num\\.$ip_num\\.$ip_num)(:\d{1,5})?/.+#", $link, $match );
		
		if( count( $match ) > 2 )
		{
			$domain = $match[3].$match[4];
			$subdomain = $match[1];
		}
		else 
		{
			$domain = $subdomain = $match[1];
		}
		
		$port = $match[9];
		
		$link = preg_replace( "#^".$protocol.$subdomain.$port."#" , "", $link );
		
		//-------------------------------------------------
		// Проверяем, требуется ли аутентификация для
		// админа
		//-------------------------------------------------
		
		if( $this->engine->member['user_admin'] and is_array( $this->engine->cache['downloader']['domains']['admin'] ) ) foreach( $this->engine->cache['downloader']['domains']['admin'] as $params )
		{
			$params['domain_name'] = preg_replace( "#\*(\.)?#", "([a-zA-Z0-9]+\\1)*", $params['domain_name'] );
			$params['domain_name'] = str_replace( ".", "\.", $params['domain_name'] );
			
			if( preg_match( "#^".$params['domain_name']."$#", $protocol.$subdomain ) )
			{
				$authorization = "Authorization: Basic ".base64_encode( $params['domain_user'].":".$params['domain_pass'] )."\r\n";
				
				$this->module['class']->auth = $params['domain_user'].":".$params['domain_pass']."@";
				
				break;
			}
			else 
			{
				$this->module['class']->auth = "";
			}
		}
		
		//-------------------------------------------------
		// Проверяем, требуется ли аутентификация для
		// любого пользователя
		//-------------------------------------------------
		
		if( is_array( $this->engine->cache['downloader']['domains']['shared'] ) ) foreach( $this->engine->cache['downloader']['domains']['shared'] as $params )
		{
			$params['domain_name'] = preg_replace( "#\*(\.)?#", "([a-zA-Z0-9]+\\1)*", $params['domain_name'] );
			$params['domain_name'] = str_replace( ".", "\.", $params['domain_name'] );
			
			if( preg_match( "#^".$params['domain_name']."$#", $protocol.$subdomain ) )
			{
				$authorization = "Authorization: Basic ".base64_encode( $params['domain_user'].":".$params['domain_pass'] )."\r\n";
				
				$this->module['class']->auth = $params['domain_user'].":".$params['domain_pass']."@";
				
				break;
			}
			else 
			{
				$this->module['class']->auth = "";
			}
		}
		
		$link = str_replace( " ", "%20", $link );
		
		if( $protocol == "ftp://" ) return $this->_check_link_ftp( &$subdomain, &$link );
		
		//-------------------------------------------------
		// Формируем запрос
		//-------------------------------------------------
		
		$query  = "GET {$link} HTTP/1.1\r\n";
		$query .= "Host: {$subdomain}\r\n";
		$query .= "User-Agent: ADOS/{$this->engine->config['__engine__']['version']} (File Downloading System)\r\n";
		$query .= "Accept: */*\r\n";
		$query .= "Accept-Language: ru-ru,ru;q=0.8,en-us;q=0.5,en;q=0.3\r\n";
		$query .= "Accept-Charset: windows-1251,utf-8;q=0.7,*;q=0.7\r\n";
		$query .= "Connection: Close\r\n";
		$query .= $authorization;
		$query .= "\r\n";
		
		//-------------------------------------------------
		// Открываем поток
		//-------------------------------------------------
		
		$port = $port ? intval( str_replace( ":", "", $port ) ) : 80;
		
		for( $i = 0; $i <= $this->engine->config['max_requests']; $i++ )
		{
			if( ( $fp = @fsockopen( $subdomain, $port, $errno, $errstr, $this->engine->config['max_wait_time'] ) ) !== FALSE ) break;
		}
		
		//-------------------------------------------------
		// Открыть поток не получилось
		//-------------------------------------------------
	
		if( $fp === FALSE )
		{
			$this->error = $this->engine->lang['error_cant_open_socket']." {$errno} ({$errstr})";
			
			$this->engine->add_log_event( 1, "ECD_010", array( 'file_link' => preg_replace( "#^.*:.*@(.*)$#", "username:pass@\\1", $link ), 'error_desc' => $errstr ) );
			
			return FALSE;
		}
		
		//-------------------------------------------------
		// От сервера не получен ответ
		//-------------------------------------------------
		
		if( stream_set_timeout( $fp, 30 ) === FALSE )
		{
			$this->error = $this->engine->lang['error_no_answer'];
			
			$this->engine->add_log_event( 1, "ECD_011", array( 'file_link' => preg_replace( "^.*:.*@(.*)$", "username:pass@\\1", $link ) ) );
			
			return FALSE;
		}
		
		//-------------------------------------------------
		// Отсылаем запрос
		//-------------------------------------------------
		
		fwrite( $fp, $query );
		
		//-------------------------------------------------
		// Читаем ответ и перехватываем заголовки
		//-------------------------------------------------
		
		$strnum = 1;
		
		while( !feof( $fp ) )
		{
			$str = fgets( $fp, 1024 );
			
			if( preg_match( "#^Location: (.+)$#i", $str, $match ) )
			{
				$headers['location'] = str_replace( "\r\n", "", $match[1] );
				$headers['location'] = str_replace( "\r"  , "", $headers['location'] );
				$headers['location'] = str_replace( "\n"  , "", $headers['location'] );
			}
			else if( preg_match( "#Accept-Ranges: bytes#i", $str, $match ) )
			{
				$headers['range'] = TRUE;
			}
			else if( preg_match( "#Content-Length: (\d+)#i", $str, $match ) )
			{
				$headers['content-length'] = $match[1];
			}
			else if( preg_match( "#Content-Disposition: attachment;\s?filename\*?=([a-z0-9\-]+'')?([\"']?)(.*)[\"']?#i", $str, $match ) )
			{
				$headers['name'] = $match[3];
				
				if( $match[2] == '"' ) $headers['name'] = trim( str_replace( '"', "", $headers['name'] ) );
				else if( $match[2] == "'" ) $headers['name'] = trim( str_replace( "'", "", $headers['name'] ) );
			}
			else if( $str == "\r\n" or $strnum > 15 )
			{
				break;
			}
			
			$strnum++;
		}
		
		fclose( $fp );
		
		//-------------------------------------------------
		// Возвращаем размер файла или следуем переадресации
		//-------------------------------------------------
		
		if( $headers['location'] )
		{
			$this->module['class']->redirect = $headers['location'];
			
			return $this->_check_link( $headers['location'], $new_depth = $depth + 1 );
		}
		else
		{
			if( $headers['name'] )
			{
				$this->engine->cache['download']['filename'] = $headers['name'];
				
				if( $this->module['class'] ) $this->module['class']->filename = $headers['name'];
			}
			else 
			{
				$this->engine->cache['download']['filename'] = "";
				
				if( $this->module['class'] ) $this->module['class']->filename = "";
			}
			
			if( $headers['range'] )
			{
				$this->engine->cache['download']['range'] = TRUE;
			}
			else 
			{
				$this->engine->cache['download']['range'] = FALSE;
			}
			
			return $headers['content-length'];
		}
	}
	
	/**
	* Проверка FTP ссылки
	* 
	* Пытается получить информацию о файле на FTP сервере.
	* 
	* @param 	string			Адрес хоста
	* @param 	string			Путь до файла
	* 
	* @return	int				Размер файла
	* @return 	bool			FALSE
	*/
	
	function _check_link_ftp( $host, $path )
	{
		$path = urldecode( $path );
		
		//-------------------------------------------------
		// Определяем адрес хоста и порт
		//-------------------------------------------------
		
		preg_match( "#(.*)(:\d{0,5})?#", $host, $server );
		
		//-------------------------------------------------
		// Определяем логин и пароль
		//-------------------------------------------------
		
		preg_match( "#^(.*):(.*)@$#", $this->module['class']->auth, $auth );
		
		$auth[1] = $auth[1] ? $auth[1] : "anonymous";
		
		//-------------------------------------------------
		// Создаем соединение с сервером и авторизуемся
		//-------------------------------------------------
		
		for( $i = 0; $i <= $this->engine->config['max_requests']; $i++ )
		{
			if( ( $fp = ftp_connect( $server[1], $server[2] ? $server[2] : NULL, $this->engine->config['max_wait_time'] ) ) !== FALSE ) break;
		}
		
		if( $fp === FALSE )
		{
			$this->error = $this->engine->lang['error_cant_connect_ftp'];
			
			$this->engine->add_log_event( 1, "ECD_012", array( 'file_host' => $host ) );
			
			return FALSE;
		}
		
		if( ftp_login( $fp, $auth[1], $auth[2] ) === FALSE )
		{
			$this->error = $this->engine->lang['error_cant_login_ftp'];
			
			$this->engine->add_log_event( 1, "ECD_013", array( 'file_host' => $host ) );
			
			return FALSE;
		}
		
		//-------------------------------------------------
		// Определяем размер запрошенного файла
		//-------------------------------------------------
		
		$file_name = substr( $path, ( strrpos( $path, "/" ) + 1 ) );
		$path = substr( $path, 0, strrpos( $path, "/" ) );
		
		$files = ftp_rawlist( $fp, $path );
		
		foreach( $files as $file )
		{
			$file = preg_split( "/[\s]+/", $file, 9 );
			
			if( $file[8] == $file_name )
			{
				$filesize = $file[4];
				
				break;
			}
		}
		
		if( !is_numeric( $filesize ) )
		{
			$this->error = $this->engine->lang['error_cant_get_size'];
			
			$this->engine->add_log_event( 1, "ECD_014", array( 'file_name' => $path ) );
			
			return FALSE;
		}
		
		//-------------------------------------------------
		// Закрываем соединение и возвращаем размер файла
		//-------------------------------------------------
		
		ftp_close( $fp );
		
		$this->engine->cache['download']['range'] = TRUE;
		
		return $filesize;
	}
	
	/**
	* Создание исполняемого файла для CRON
	* 
	* Создает файл для автоматического запуска закачки
	* в директории CRON и делает его исполняемым.
	* 
	* @param 	string			Строка с параметрами запуска закачки
	* @param 	string			Идентификатор закачки
	* 
	* @return 	bool			Результат создания файла
	*/
	
	function _create_cron_file( $string, $id )
	{
		//-------------------------------------------------
		// Пытаемся открыть файл в директории CRON
		//-------------------------------------------------
		
		$filename = $this->engine->config['save_path']."/_tmp/ados_{$id}.sh";
	
		if( ( $file = fopen( $filename, "w" ) ) === FALSE )
		{
			$this->error =& $this->engine->lang['error_cant_open_cron_file'];
			
			$this->engine->add_log_event( 1, "ECD_015", array( 'file_name' => $filename ) );
			
			return FALSE;
		}
		
		fputs( $file, "#!/bin/sh\n", 1024 );
		fputs( $file, "rm {$this->engine->config['cron_path']}/ados_{$id}.sh\n", 1024 );
		fputs( $file, "{$string} > {$this->engine->config['save_path']}/_log/{$id}.log 2>&1\n", 1024 );
		fputs( $file, "echo >> {$this->engine->home_dir}cron.lock\n", 1024 );
		fputs( $file, "{$this->engine->config['php_path']} {$this->engine->home_dir}classes/class_cron.php >> {$this->engine->config['save_path']}/_log/cron_end_download.log\n", 1024 );
		
		fclose( $file );
		
		$this->engine->add_log_event( 4, "ICD_012", array( 'file_name' => $filename ) );
		
		//-------------------------------------------------
		// Если возможно, перемещаем файл в директорию CRON
		//-------------------------------------------------
		
		$this->engine->move_cron_files();
	}
	
	/**
	* Удаление исполняемого файла для CRON
	* 
	* Удаляет файл CRON из временной директории или
	* непосредственно из CRON директории.
	* 
	* @param 	string			Идентификатор закачки
	* 
	* @return 	bool			Результат удаления файла
	*/
	
	function _delete_cron_file( $id )
	{
		$unlink_cron = $this->engine->config['cron_path']."/ados_{$id}.sh";
		$unlink_temp = $this->engine->config['save_path']."/_tmp/ados_{$id}.sh";
		
		if( file_exists( $unlink_cron ) and !@unlink( $unlink_cron ) )
		{
			$this->module['class']->error =& $this->engine->lang['error_cant_unlink_cron_file'];
			
			$this->engine->add_log_event( 1, "ECD_016", array( 'file_name' => $unlink_cron ) );
			
			return FALSE;
		}
		
		if( file_exists( $unlink_temp ) and !@unlink( $unlink_temp ) )
		{
			$this->module['class']->error =& $this->engine->lang['error_cant_unlink_temp_file'];
			
			$this->engine->add_log_event( 1, "ECD_017", array( 'file_name' => $unlink_temp ) );
			
			return FALSE;
		}
	}
}

?>