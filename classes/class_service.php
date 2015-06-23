<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			Сервисные функции
*/

/**
* Класс, содержащий функции, выполняемые
* при изменении настроек системы или настроек
* модуля.
*
* @author	DINI
* @version	1.3.9 (build 74)
*/

class service
{
	/**
	* Проверка значения свободного места
	* 
	* Проверяет, верно ли заполнено значения настройки
	* 'Зарезервированное место на диске'.
	* 
	* @param 	string	Новое значение
	*
	* @return	NULL или
	* 			float	Обработанное значение
	*/
	
	function parse_free_space_value( $new )
	{
		if( !is_numeric( $new ) or ( $float = floatval( $new ) ) <= 0 ) return NULL;
		else return $float;
	}
	
	/**
	* Проверка значения пути
	* 
	* Проверяет присутствие слеша в конце пути и
	* убирает его, если он найден.
	* 
	* @param 	string	Новое значение
	*
	* @return	string	Обработанное значение
	*/
	
	function parse_path_value( $new )
	{
		return preg_replace( "#/+$#", "", $new );
	}
	
	/**
	* Проверка численного значения
	* 
	* Проверяет, является ли новое значение неотрицаетльным
	* числом.
	* 
	* @param 	string	Старое значение
	* @param 	string	Новое значение
	*
	* @return	string	Обработанное значение
	*/
	
	function parse_max_value( $old, $new )
	{
		if( !is_numeric( $new ) or $new < 0 ) return $old;
		else return $new;
	}
	
	/**
	* Обновление путей (CRON)
	* 
	* Перемещает файл ados.sh из старой директории CRON в
	* новую.
	* Если в старой директории файла нет, то создает его в
	* новой директории.
	* 
	* @param 	string	Старый путь до CRON
	* @param 	string	Новый путь до CRON
	*
	* @return	void
	*/
	
	function change_cron_path( $old, $new )
	{
		if( !file_exists( $old."/ados.sh" ) or ( $rename = @rename( $old."/ados.sh", $new."/ados.sh" ) ) === FALSE )
		{
			if( $rename === FALSE )
			{
				// PUT TO LOG
			}
			
			if( ( $file = fopen( $new."/ados.sh", "w" ) === FALSE ) )
			{
				// PUT TO LOG
				
				return;
			}
			
			fputs( $file, "#!/bin/sh\n", 1024 );
			fputs( $file, "echo >> {$this->engine->home_dir}cron.lock", 1024 );
			fputs( $file, "{$this->engine->config['php_path']} {$this->engine->home_dir}classes/class_cron.php >> {$this->engine->config['save_path']}/_log/cron_schedule.log", 1024 );
			
			fclose( $file );
		}
	}
	
	/**
	* Обновление путей (PHP)
	* 
	* Изменяет путь до исполняемого файла PHP в файле
	* ados.sh (CRON).
	* 
	* @param 	string	Новый путь до PHP
	*
	* @return	void
	*/
	
	function change_php_path( $new )
	{
		if( !file_exists( $this->engine->config['cron_path']."/ados.sh" ) )
		{
			// PUT TO LOG
			
			return;
		}
		
		if( ( $file = fopen( $this->engine->config['cron_path']."/ados.sh", "w" ) === FALSE ) )
		{
			// PUT TO LOG
				
			return;
		}
			
		fputs( $file, "#!/bin/sh\n", 1024 );
		fputs( $file, "echo >> {$this->engine->home_dir}cron.lock", 1024 );
		fputs( $file, "{$new} {$this->engine->home_dir}classes/class_cron.php >> {$this->engine->config['save_path']}/_log/cron_schedule.log", 1024 );
			
		fclose( $file );
	}
	
	/**
	* Обновление путей (сохранение)
	* 
	* Изменяет пути каталогов пользователей в
	* соответствии с указанным новым значением
	* пути до корневого каталога.
	* 
	* @param 	string	Старый путь до корневого каталога
	* @param 	string	Новый путь до корневого каталога
	*
	* @return	void
	*/
	
	function change_save_path( $old, $new )
	{
		//-----------------------------------------------
		// Убираем слеш в конце пути
		//-----------------------------------------------
		
		if( preg_match( "#(.+)/$#", $new, $match ) )
		{
			$new = $match[1];
			
			$this->engine->DB->do_update( "settings_list", array( "setting_value" => $new ), "setting_key='save_path'" );
		}
		
		//-----------------------------------------------
		// Изменяем пути до категорий в БД
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'cat_id, cat_path',
													'from'		=> 'categories_list',
													)	);
		$this->engine->DB->simple_exec();
		
		while( $cat = $this->engine->DB->fetch_row() )
		{
			$categories[ $cat['cat_id'] ] = preg_replace( "#^".$old."/*#", $new."/", $cat['cat_path'] );
		}
		
		if( is_array( $categories ) ) foreach( $categories as $cid => $path )
		{
			$this->engine->DB->do_update( "categories_list", array( 'cat_path' => $path ), "cat_id='{$cid}'" );
		}
		
		//-----------------------------------------------
		// Изменяем пути до файлов в БД
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'file_id, file_path',
													'from'		=> 'categories_files',
													)	);
		$this->engine->DB->simple_exec();
		
		while( $file = $this->engine->DB->fetch_row() )
		{
			$files[ $file['file_id'] ] = preg_replace( "#^".$old."/*#", $new."/", $file['file_path'] );
		}
		
		if( is_array( $files ) ) foreach( $files as $fid => $path )
		{
			$this->engine->DB->do_update( "categories_files", array( 'file_path' => $path ), "file_id='{$fid}'" );
		}
		
		//-----------------------------------------------
		// Убеждаемся в наличии необходимых директорий
		//-----------------------------------------------
		
		$dirs = explode( "/", $new );
			
		$path = "";
			
		foreach( $dirs as $piece )
		{
			$path .= $piece."/";
				
			if( !preg_match( "#^[a-zA-Z]:$#", $path ) and !is_dir( $path ) and !mkdir( $path, 0777 ) ) return;
		}
		
		//-----------------------------------------------
		// Получаем имена пользователей
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'user_name',
													'from'		=> 'users_list',
													)	);
		$this->engine->DB->simple_exec();
			
		if( !$this->engine->DB->get_num_rows() )
		{
			return;
		}
		
		while( $user = $this->engine->DB->fetch_row() )
		{
			$users[] = strtolower( preg_replace( "#\W#", "_", $user['user_name'] ) );
		}
		
		$users[] = "_all";
		$users[] = "_tmp";
		$users[] = "_log";
		
		//-----------------------------------------------
		// Обновляем имена категорий на жестком диске
		//-----------------------------------------------
		
		if( $this->engine->config['path_change_save_path'] == 'rename' and is_dir( $old ) )
		{
			foreach( $users as $user )
			{
				$this->engine->copy_dir( $old."/".$user, $new."/".$user );
				$this->engine->remove_dir( $old."/".$user );
			}
			
			if( is_dir( $old ) and !$this->engine->dirsize( $old ) ) @rmdir( $old );
		}
		
		//-----------------------------------------------
		// Копируем файлы в новую категорию на жестком диске
		//-----------------------------------------------
		
		else if( $this->engine->config['path_change_save_path'] == 'copy' )
		{
			foreach( $users as $user )
			{
				$this->engine->copy_dir( $old."/".$user, $new."/".$user );
			}
		}
		
		//-----------------------------------------------
		// Создаем пользовательские категории в новой директории
		//-----------------------------------------------
		
		else if( $this->engine->config['path_change_save_path'] == 'make' )
		{			
			$this->engine->DB->simple_construct( array(	'select'	=> 'user_name',
														'from'		=> 'users_list',
														)	);
			$this->engine->DB->simple_exec();
			
			if( !$this->engine->DB->get_num_rows() )
			{
				return;
			}
			
			foreach( $users as $user ) @mkdir( $new."/".$user );
		}
	}
	
	/**
	* Создание или удаление категорий пользователей
	* 
	* Создает пользовательские категории при выключении
	* и удаляет категории при выключении режима
	* использования общих категорий.
	* 
	* @param 	bool	Режим включен
	*
	* @return	void
	*/
	
	function enable_share_cats( $enable )
	{
		if( $enable )
		{
			//-----------------------------------------------
			// Получаем список пользователей
			//-----------------------------------------------
			
			$this->engine->DB->simple_construct( array(	'select'	=> 'user_name, user_id',
														'from'		=> 'users_list',
														'where'		=> "user_admin=0"
														)	);
			$this->engine->DB->simple_exec();
			
			if( !$this->engine->DB->get_num_rows() )
			{
				return;
			}
			
			while( $user = $this->engine->DB->fetch_row() )
			{
				$users[ $user['user_id'] ] = $user['user_name'];
			}
			
			//-----------------------------------------------
			// Удаляем категории из БД
			//-----------------------------------------------
			
			if( in_array( $this->engine->config['path_toggle_shared_cats'], array( 'delete_delete', 'delete_save' ) ) )
			{
				$to_delete = implode( "','", array_keys( &$users ) );
				
				$this->engine->DB->do_delete( "categories_list", "cat_user IN('{$to_delete}')" );
				$this->engine->DB->do_delete( "categories_files", "file_user IN('{$to_delete}')" );
			}
			
			if( in_array( $this->engine->config['path_toggle_shared_cats'], array( 'delete_delete', 'save_delete' ) ) )
			{
				foreach( $users as $uname ) $this->engine->remove_dir( $this->engine->config['save_path']."/".strtolower( preg_replace( "#\W#", "_", $uname ) ) );
			}
			
			//-----------------------------------------------
			// Останавливаем все активные закачки
			//-----------------------------------------------
			
			$this->engine->DB->simple_construct( array(	'select'	=> 'file_id, file_dl_module',
														'from'		=> 'categories_files',
														'where'		=> "file_state='running' AND file_user='{$this->engine->input['id']}'"
														)	);
			$this->engine->DB->simple_exec();
			
			if( $this->engine->DB->get_num_rows() )
			{
				if( $this->engine->load_module( "class", "files" ) === FALSE )
				{
					// TO DO string to log
				}
				
				while( $file = $this->engine->DB->fetch_row() )
				{
					$stop_downloads[] = $file;
				}
			}
			
			if( is_array( $stop_downloads ) ) foreach( $stop_downloads as $file )
			{
				$this->engine->classes['file']->change_dowload_state( 'stop', &$file['file_id'], &$file['file_dl_module'] );
			}
			
			//-----------------------------------------------
			// Блокируем задания и закачки
			//-----------------------------------------------
			
			$this->engine->DB->do_update( "schedule_events", array( "event_state" => "blocked" ), "event_user<>0 AND event_state='query'" );
			
			$this->engine->DB->do_update( "categories_files", array( "file_state" => "blocked" ), "file_user<>0 AND file_state IN('query','paused')" );
		}
		else 
		{
			//-----------------------------------------------
			// Создаем пользовательские категории на жестком диске
			//-----------------------------------------------
			
			$this->engine->DB->simple_construct( array(	'select'	=> 'user_name',
														'from'		=> 'users_list',
														)	);
			$this->engine->DB->simple_exec();
			
			if( !$this->engine->DB->get_num_rows() )
			{
				return;
			}
			
			while( $user = $this->engine->DB->fetch_row() )
			{
				@mkdir( $this->engine->config['save_path']."/".strtolower( preg_replace( "#\W#", "_", $user['user_name'] ) ) );
			}
			
			@mkdir( $this->engine->config['save_path']."/_all" );
			
			//-----------------------------------------------
			// Снимаем блок с заданий и закачек
			//-----------------------------------------------
			
			if( $this->engine->load_module( "class", "files" ) !== FALSE ) $this->engine->classes['files']->_update_events_state( TRUE );
		}
	}
	
}

?>