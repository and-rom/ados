<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			Страница управления пользователями
*/

/**
* Класс, содержащий функции для
* страницы управления пользователями.
*
* @author	DINI
* @version	1.3.9 (build 74)
*/

class users
{
	/**
	* HTML код для вывода на экран
	*
	* @var string
	*/

	var $html 			= "";
	
	/**
	* Информация о текущей странице
	*
	* @var array
	*/

	var $page_info		= array(	'title'	=> "",
									'desc'	=> "",
									);
									
	/**
	* Загруженные языковые файлы
	*
	* @var array
	*/

	var $lang_files		= array();
									
	/**
	* Конструктор класса (не страндартный PHP)
	* 
	* Загружает языковые строки и шаблон.
	* В зависимости от переданных параметров запускает
	* необходимую функцию.
	* 
	* @return	bool	TRUE
	*/
	
	function __class_construct()
	{
		$this->engine->load_lang( "users" );
		
		//-----------------------------------------------
		// AJAX запрос
		//-----------------------------------------------
			
		if( $this->engine->input['ajax'] == 'yes' ) switch( $this->engine->input['type'] )
		{
			case 'delete_user':
				$this->ajax_delete_user();
				break;
			
			case 'update_users_list':
				$this->ajax_update_users_list();
				break;
				
			case 'change_user_lang':
				$this->ajax_change_user_lang();
				break;
		}
			
		//-----------------------------------------------
		// Обычный запрос
		//-----------------------------------------------
			
		$this->engine->classes['output']->java_scripts['link'][] = "users";
			
		$this->show_users_list();
		
		return TRUE;
	}
	
	/**
    * Список пользователей
    * 
    * Выводит список пользователей системы.
    *
    * @return	void
    */
	
	function show_users_list()
	{
		$this->engine->classes['output']->java_scripts['embed'][] = "var lang_pass_click_to_edit = '{$this->engine->lang['pass_click_to_edit']}';
																	 var lang_user_delete = '{$this->engine->lang['user_delete']}';
																	 var lang_error_user_no_name = '{$this->engine->lang['error_user_no_name']}';";
		
		//-----------------------------------------------
		// Название страницы и системное сообщение
		//-----------------------------------------------
		
		$this->page_info['title']	= $this->engine->lang['page_title'];
		$this->page_info['desc']	= $this->engine->lang['page_desc'];
		
		$this->message = array(	'text'	=> "",
								'type'	=> "",
								);
		
		//-----------------------------------------------
		// Форма со списком пользователей
		//-----------------------------------------------
		
		$this->html .= $this->engine->classes['output']->form_start( array(	'tab'	=> 'users',
																			), "id='users_form' onsubmit='ajax_update_list(); return false;'" );
		
		$this->html .= "<div id='list'>\n";
																			
		$this->_get_users();
		
		$this->html .= "</div>\n";
		
		$this->html .= $this->engine->classes['output']->form_end();
	}
	
	/**
    * Обновление списка пользователей
    * 
    * Обновляет список пользователей в соответствии с
    * переданными значениями.
    *
    * @return	void
    */
	
	function ajax_update_users_list()
	{
		$users = array();
		
		$this->engine->config['save_path'] = preg_replace( "#/$#", "", $this->engine->config['save_path'] );
		
		//-----------------------------------------------
		// Получаем список пользователей
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'user_id, user_pass, user_name, user_admin, user_lang, user_max_speed',
													'from'		=> 'users_list',
													)	);
		$this->engine->DB->simple_exec();
		
		while( $user = $this->engine->DB->fetch_row() )
		{
			$users['passwords'][ $user['user_id'] ] = $user['user_pass'];
			$users['list'][ $user['user_id'] ] = $user;
		}
		
		//-----------------------------------------------
		// Проверяем переданные значения
		//-----------------------------------------------
		
		foreach( $this->engine->input as $name => $value ) if( preg_match( "#user_(\d+)#", $name, $match ) )
		{
			$user = explode( ",", $value, 6 );
			
			if( $user[5] ) $user[5] = ( $user[5] == "[********]" ) ? $users['passwords'][ $match[1] ] : $user[5];
			
			if( !$user[0] )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_user_no_name'] ) );
			}
			else if( !preg_match( "#^[a-zA-Z][a-zA-Z0-9_]{3,}$#", $user[0] ) )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_user_wrong_name'] ) );
			}
			
			if( $match[1] == 1 and !$user[1] )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_user_must_be_admin'].$user[0] ) );
			}
			
			if( $user[5] and !preg_match( "#^\w{6,}$#", $user[5] ) )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_user_pass_incorrect'].$user[0] ) );
			}
			
			if( $user[1] and !$user[5] )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_user_no_admin_pass'].$user[0] ) );
			}
			
			$info = array(	'user_id'			=> $match[1],
							'user_name'			=> $user[0],
							'user_pass'			=> $user[5] ? ( $user[5] == $users['passwords'][ $match[1] ] ? $user[5] : md5( sha1( $user[5] ) ) ) : NULL,
							'user_admin'		=> $user[1] ? 1 : 0,
							'user_lang'			=> array_key_exists( $user[4], &$this->engine->languages['list'] ) ? $user[4] : $this->engine->languages['default'],
							'user_max_speed'	=> intval( $user[2] ),
							'user_max_amount'	=> floatval( $user[3] ),
							);
			
			array_key_exists( $match[1], $users['passwords'] ) ? $users['updated'][] = $info : $users['new'][] = $info;
		}
		
		//-----------------------------------------------
		// Сохраняем список
		//-----------------------------------------------
		
		if( count( $users['updated'] ) ) foreach( $users['updated'] as $user )
		{
			$cats = array();
			$name = array( 'now' => "", 'was' => "" );
			
			$name['now'] = strtolower( preg_replace( "#\W#", "_", $user['user_name'] ) );
			
			if( !$this->engine->config['use_share_cats'] and !is_dir( $this->engine->config['save_path']."/".$name['now'] ) and !@mkdir( $this->engine->config['save_path']."/".$name['now'], 0755 ) )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_make_user_dir'].$user['user_name'] ) );
			}
			
			$this->engine->DB->do_update( "users_list", &$user, "user_id='{$user['user_id']}'" );
			
			if( strtolower( $user['user_name'] ) != strtolower( $users['list'][ $user['user_id'] ]['user_name'] ) and ( !$this->engine->config['use_share_cats'] or $user['user_admin'] ) )
			{
				$name['was'] = strtolower( preg_replace( "#\W#", "_", $users['list'][ $user['user_id'] ]['user_name'] ) );
				
				//-----------------------------------------------
				// Обновляем пути до категорий в БД
				//-----------------------------------------------
				
				$this->engine->DB->simple_construct( array(	'select'	=> 'cat_id, cat_path',
															'from'		=> 'categories_list',
															'where'		=> "cat_user='{$user['user_id']}'"
															)	);
				$this->engine->DB->simple_exec();
				
				while( $cat = $this->engine->DB->fetch_row() )
				{
					$cats[] = $cat;
				}
				
				if( count( $cats ) ) foreach( $cats as $cat )
				{
					$this->engine->DB->do_update( "categories_list", array( 'cat_path' => preg_replace( "#^(".$this->engine->config['save_path']."/)".$name['was']."(.*)$#i", "\\1".$name['now']."\\2", $cat['cat_path'] ) ), "cat_id='{$cat['cat_id']}'" );
				}
				
				//-----------------------------------------------
				// Обновляем пути до файлов в БД
				//-----------------------------------------------
				
				$this->engine->DB->simple_construct( array(	'select'	=> 'file_id, file_path',
															'from'		=> 'categories_files',
															'where'		=> "file_user='{$user['user_id']}'"
															)	);
				$this->engine->DB->simple_exec();
				
				while( $file = $this->engine->DB->fetch_row() )
				{
					$files[] = $file;
				}
				
				if( count( $files ) ) foreach( $files as $file )
				{
					$this->engine->DB->do_update( "categories_files", array( 'file_path' => preg_replace( "#^(".$this->engine->config['save_path']."/)".$name['was']."(.*)$#i", "\\1".$name['now']."\\2", $file['file_path'] ) ), "file_id='{$file['file_id']}'" );
				}
				
				if( $this->engine->config['path_change_user_name'] == "rename" and is_dir( $this->engine->config['save_path']."/".$name['was'] ) )
				{
					//-----------------------------------------------
					// Обновляем имена категорий на жестком диске
					//-----------------------------------------------
					
					rename( $this->engine->config['save_path']."/".$name['was'], $this->engine->config['save_path']."/".$name['now'] );
				}
				else if( $this->engine->config['path_change_user_name'] == "copy" and is_dir( $this->engine->config['save_path']."/".$name['was'] ) )
				{
					//-----------------------------------------------
					// Копируем файлы в новую категорию на жестком диске
					//-----------------------------------------------
					
					$this->engine->copy_dir( $this->engine->config['save_path']."/".$name['was'], $this->engine->config['save_path']."/".$name['now'] );
				}
				else if( $this->engine->config['path_change_user_name'] == "make" and is_dir( $this->engine->config['save_path']."/".$name['was'] ) )
				{
					//-----------------------------------------------
					// Создаем новую категорию на жестком диске
					//-----------------------------------------------
					
					@mkdir( $this->engine->config['save_path']."/".$name['now'], 0777 );
				}
			}
			else if( $this->engine->config['use_share_cats'] and !$user['user_admin'] )
			{
				if( in_array( $this->engine->config['path_toggle_shared_cats'], array( "delete_delete", "delete_save" ) ) )
				{
					//-----------------------------------------------
					// Удаляем категории и файлы из БД
					//-----------------------------------------------
					
					$this->engine->DB->do_delete( "categories_list", "cat_user='{$user['user_id']}'" );
					$this->engine->DB->do_delete( "categories_files", "file_user='{$user['user_id']}'" );
				}
				
				if( in_array( $this->engine->config['path_toggle_shared_cats'], array( "delete_delete", "save_delete" ) ) )
				{
					//-----------------------------------------------
					// Удаляем категории и файлы с жесткого диска
					//-----------------------------------------------
					
					$this->engine->remove_dir( $this->engine->config['save_path']."/".$name['was'] );
				}
			}
		}
		
		//-----------------------------------------------
		// Добавляем пользователей
		//-----------------------------------------------
		
		if( count( $users['new'] ) ) foreach( $users['new'] as $user )
		{
			if( !is_array( $this->lang_files[ $user['user_lang'] ] ) )
			{
				$this->lang_files[ $user['user_lang'] ] = $this->engine->load_lang( "users", $user['user_lang'], TRUE );
				
				if( !$this->lang_files[ $user['user_lang'] ] ) $this->lang_files[ $user['user_lang'] ] =& $this->engine->lang;
			}
			
			$this->engine->DB->do_insert( "users_list", &$user );
			
			$path = $this->engine->config['save_path']."/".strtolower( preg_replace( "#\W#", "_", $user['user_name'] ) )."/";
			
			if( ( !$this->engine->config['use_share_cats'] or $user['user_admin'] ) and $this->engine->config['create_standard_cats'] )
			{
				@mkdir( $path );
				
				//-----------------------------------------------
				// Подгружаем названия стандартных категорий
				//-----------------------------------------------
				
				$standard = array(	0 => array( 'cat_name'	=> &$this->lang_files[ $user['user_lang'] ]['cat_std_programs'],
												'cat_path'	=> $path."programs",
												'cat_icon'	=> "app1",
												'cat_types'	=> "exe msi",
												'cat_root'	=> 0,
												'cat_user'	=> &$user['user_id'] ),
												
									1 => array( 'cat_name'	=> &$this->lang_files[ $user['user_lang'] ]['cat_std_video'],
												'cat_path'	=> $path."video",
												'cat_icon'	=> "video1",
												'cat_types'	=> "mpg mpeg mp4 avi qt wmv mov",
												'cat_root'	=> 0,
												'cat_user'	=> &$user['user_id'] ),
												
									2 => array( 'cat_name'	=> &$this->lang_files[ $user['user_lang'] ]['cat_std_music'],
												'cat_path'	=> $path."music",
												'cat_icon'	=> "audio1",
												'cat_types'	=> "mp3 wma ogg",
												'cat_root'	=> 0,
												'cat_user'	=> &$user['user_id'] ),
												
									3 => array( 'cat_name'	=> &$this->lang_files[ $user['user_lang'] ]['cat_std_images'],
												'cat_path'	=> $path."images",
												'cat_icon'	=> "image1",
												'cat_types'	=> "jpg jpeg jp2 gif png bmp psd ai cdr tga tif tiff",
												'cat_root'	=> 0,
												'cat_user'	=> &$user['user_id'] ),
												
									4 => array( 'cat_name'	=> &$this->lang_files[ $user['user_lang'] ]['cat_std_archives'],
												'cat_path'	=> $path."archives",
												'cat_icon'	=> "archive1",
												'cat_types'	=> "zip rar 7z gz tar",
												'cat_root'	=> 0,
												'cat_user'	=> &$user['user_id'] ),
												);
				
				foreach( $standard as $std )
				{
					//-----------------------------------------------
					// Добавляем категорию в БД
					//-----------------------------------------------
					
					$this->engine->DB->do_insert( "categories_list", &$std );
					
					//-----------------------------------------------
					// Добавляем категорию на жесткий диск
					//-----------------------------------------------
					
					@mkdir( $std['cat_path'] );
				}
			}
		}
		
		//-----------------------------------------------
		// Обновляем список пользователей и возвращаем XML
		//-----------------------------------------------
		
		$this->_get_users();
		
		$array = array(	'Message'	=> &$this->engine->lang['users_list_updated'],
						'List'		=> &$this->html,
						);
		
		$this->engine->classes['output']->generate_xml_output( &$array );
	}
	
	/**
    * Удаление пользователя
    * 
    * Удаляет информацию о пользователе из БД,
    * все созданные им категории и скачанные
    * файлы.
    *
    * @return	void
    */
	
	function ajax_delete_user()
	{
		//-----------------------------------------------
		// Получаем информацию о пользователе
		//-----------------------------------------------
		
		$user = $this->engine->DB->simple_exec_query( array(	'select'	=> 'user_id, user_name',
																'from'		=> 'users_list',
																'where'		=> "user_id='{$this->engine->input['id']}'"
																)	);
		
		if( !$user['user_id'] )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_no_user_id'] ) );
		}
		
		if( $user['user_id'] == 1 )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['js_error_user_is_root'] ) );
		}
		
		//-----------------------------------------------
		// Останавливаем все активные закачки
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'file_id, file_dl_module',
													'from'		=> 'categories_files',
													'where'		=> "file_state IN('paused','running') AND file_user='{$this->engine->input['id']}'"
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
		// Удаляем информацию из БД
		//-----------------------------------------------
		
		$this->engine->DB->do_delete( "users_list", "user_id='{$this->engine->input['id']}'" );
		
		//-----------------------------------------------
		// Удаляем категории и файлы из БД
		//-----------------------------------------------
		
		if( in_array( $this->engine->config['path_delete_user'], array( "delete_delete", "delete_save" ) ) )
		{
			$this->engine->DB->do_delete( "categories_list", "cat_user='{$this->engine->input['id']}'" );
			
			$this->engine->DB->do_delete( "categories_files", "file_user='{$this->engine->input['id']}'" );
		}
		else 
		{
			$this->engine->DB->do_update( "categories_files", array( "file_state" => "stopped" ), "file_user='{$this->engine->input['id']}' AND file_state IN('query','paused')" );
		}
		
		//-----------------------------------------------
		// Удаляем категории и файлы с жесткого диска
		//-----------------------------------------------
		
		if( in_array( $this->engine->config['path_delete_user'], array( "delete_delete", "save_delete" ) ) )
		{	
			$this->engine->remove_dir( $this->engine->config['save_path']."/".strtolower( preg_replace( "\W", "_", $user['user_name'] ) ) );
		}
		
		//-----------------------------------------------
		// Удаляем настройки расписания
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'time_id, time_users',
													'from'		=> 'schedule_time',
													'where'		=> "time_users LIKE '%,{$this->engine->input['id']},%'"
													)	);
		$this->engine->DB->simple_exec();
		
		while( $time = $this->engine->DB->fetch_row() )
		{
			$users = explode( ",", preg_replace( "#^,(.*),$#", "\\1", $time['time_users'] ) );
			
			foreach( $users as $uid => $user ) if( $user == $this->engine->input['id'] )
			{
				if( count( $users ) == 1 ) $to_delete[] = $time['time_id'];
				else unset( $users[ $uid ] );
				
				break;
			}
			
			if( count( $users ) ) $to_update[ $time['time_id'] ] = ",".implode( ",", $users ).",";
		}
		
		if( is_array( $to_delete ) ) $this->engine->DB->do_delete( "schedule_time", "time_id IN('".implode( "','", $to_delete )."')" );
		
		if( is_array( $to_update ) ) foreach( $to_update as $tid => $users )
		{
			$this->engine->DB->do_update( "schedule_time", array( "time_users" => $users ), "time_id='{$tid}'" );
		}
		
		//-----------------------------------------------
		// Удаляем события из расписания
		//-----------------------------------------------
		
		$this->engine->DB->do_delete( "schedule_events", "event_user='{$this->engine->input['id']}'" );
		
		//-----------------------------------------------
		// Удаляем историю скачиваний
		//-----------------------------------------------
		
		// TO DO
		
		//-----------------------------------------------
		// Обновляем список пользователей и возвращаем XML
		//-----------------------------------------------
		
		$this->_get_users();
		
		$this->engine->classes['output']->generate_xml_output( array( 'List' => &$this->html, ) );
	}
	
	/**
    * Изменение языка системы для пользователя
    * 
    * Изменяет язык системы на выбранный
    * пользователем.
    *
    * @return	void
    */
	
	function ajax_change_user_lang()
	{
		if( !array_key_exists( $this->engine->input['id'], &$this->engine->languages['list'] ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_no_such_lang'] ) );
		}
		
		$this->engine->DB->do_update( "users_list", array( "user_lang" => $this->engine->input['id'] ), "user_id='{$this->engine->member['user_id']}'" );
		
		$this->engine->classes['output']->generate_xml_output( array( 'Function_0' => "window.location.reload()" ) );
	}
	
	/**
    * Загрузка списка пользователей
    * 
    * Загружает список пользователей и помещает
    * его в таблицу
    *
    * @return	void
    */
	
	function _get_users()
	{
		//-----------------------------------------------
		// Загружаем настройки
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> '*',
													'from'		=> 'users_list',
													'order'		=> 'user_id'
													)	);
		$this->engine->DB->simple_exec();
		
		//-----------------------------------------------
		// Помещаем информацию о пользователях в таблицу
		//-----------------------------------------------
		
		$this->engine->classes['output']->table_add_header( $this->engine->lang['user_name']		, "225px" );
		$this->engine->classes['output']->table_add_header( $this->engine->lang['user_pass']		, "225px" );
		$this->engine->classes['output']->table_add_header( $this->engine->lang['user_admin']		, "90px" );
		$this->engine->classes['output']->table_add_header( $this->engine->lang['user_lang']		, "110px" );
		$this->engine->classes['output']->table_add_header( $this->engine->lang['user_max_speed']	, "110px" );
		$this->engine->classes['output']->table_add_header( $this->engine->lang['user_max_amount']	, "110px" );
		$this->engine->classes['output']->table_add_header( ""										, ""  	  );
		
		$this->html .= $this->engine->classes['output']->table_start( "", "100%", "id='users_table'" );
		
		while( $user = $this->engine->DB->fetch_row() )
		{
			if( $user['user_pass'] )
			{
				$password = array(	'value' => $this->engine->lang['pass_click_to_edit'],
									'type'	=> 'text',
									'style'	=> "onfocus='ajax_change_type(this,true)' onblur='ajax_change_type(this,false)'",
									);
			}
			else 
			{
				$password = array(	'type' 		=> "password",
									'disabled'	=> "disabled='disabled'"
									);
			}
			
			if( $user['user_max_amount'] == -1 )
			{
				$max_amount = array(	'value'		=> "",
										'disabled'	=> "disabled='disabled'",
										);
			}
			else
			{
				$max_amount = array(	'value'		=> &$user['user_max_amount'],
										'checked'	=> 1,
										);
			}
			
			if( $user['user_max_speed'] == -1 )
			{
				$max_speed = array(	'value'		=> "",
									'disabled'	=> "disabled='disabled'",
									);
			}
			else
			{
				$max_speed = array(	'value'		=> &$user['user_max_speed'],
									'checked'	=> 1,
									);
			}
			
			$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->skin['global']->form_text( "user_{$user['user_id']}_name", $user['user_name'], "small", "text", "style='width:219px;'" ), "row2" ),
								array(	$this->engine->skin['global']->form_checkbox( "user_{$user['user_id']}_use_pass", $user['user_pass'] ? 1 : 0, "", "onclick=\"ajax_toggle_field_state('{$user['user_id']}',this,'pass')\"" ).
										$this->engine->skin['global']->form_text( "user_{$user['user_id']}_pass", $password['value'], "small", $password['type'], "style='width:195px;' id='user_{$user['user_id']}_pass' {$password['style']} {$password['disabled']}" ), "row2" ),
								array(	$this->engine->skin['global']->form_checkbox( "user_{$user['user_id']}_admin" , $user['user_admin'] ), "row1", "style='text-align:center'" ),
								array(	$this->engine->skin['global']->form_dropdown( "user_{$user['user_id']}_lang", &$this->engine->languages['list'], $user['user_lang'], "small", "style='width:105px;'" ), "row2" ),
								array(	$this->engine->skin['global']->form_checkbox( "user_{$user['user_id']}_use_speed", $max_speed['checked'], "", "onclick=\"ajax_toggle_field_state('{$user['user_id']}',this,'max_speed')\"" ).
										$this->engine->skin['global']->form_text( "user_{$user['user_id']}_max_speed", $max_speed['value'], "small", "text", "style='width:80px;' id='user_{$user['user_id']}_max_speed' {$max_speed['disabled']}" ), "row2" ),
								array(	$this->engine->skin['global']->form_checkbox( "user_{$user['user_id']}_use_amount", $max_amount['checked'], "", "onclick=\"ajax_toggle_field_state('{$user['user_id']}',this,'max_amount')\"" ).
										$this->engine->skin['global']->form_text( "user_{$user['user_id']}_max_amount", $max_amount['value'], "small", "text", "style='width:80px;' id='user_{$user['user_id']}_max_amount' {$max_amount['disabled']}" ), "row2" ),
								array(	$this->engine->skin['global']->element_button( $user['user_id'], "user", "delete" ), "row1", "style='text-align:center'" ),
								)	);
								
			$last_id = $user['user_id'];
		}
		
		$this->html .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->skin['global']->form_text( "user_".( $last_id + 1 )."_name", "", "small", "text", "style='width:219px;' onblur='ajax_add_list_row(this,".( $last_id + 2 ).");'" ), "row2" ),
								array(	$this->engine->skin['global']->form_checkbox( "user_".( $last_id + 1 )."_use_pass" , 0, "", "onclick=\"ajax_toggle_field_state('".( $last_id + 1 )."',this,'pass')\"" ).
										$this->engine->skin['global']->form_text( "user_".( $last_id + 1 )."_pass", "", "small", "password", "style='width:195px;' id='user_".( $last_id + 1 )."_pass' disabled='disabled'" ), "row2" ),
								array(	$this->engine->skin['global']->form_checkbox( "user_".( $last_id + 1 )."_admin" , 0 ), "row1", "style='text-align:center'" ),
								array(	$this->engine->skin['global']->form_dropdown( "user_".( $last_id + 1 )."_lang", &$this->engine->languages['list'], "", "small", "style='width:105px;' id='langs_list'" ), "row2" ),
								array(	$this->engine->skin['global']->form_checkbox( "user_".( $last_id + 1 )."_use_speed", 1, "", "onclick=\"ajax_toggle_field_state('".( $last_id + 1 )."',this,'max_speed')\"" ).
										$this->engine->skin['global']->form_text( "user_".( $last_id + 1 )."_max_speed", 128, "small", "text", "style='width:80px;' id='user_".( $last_id + 1 )."_max_speed'" ), "row2" ),
								array(	$this->engine->skin['global']->form_checkbox( "user_".( $last_id + 1 )."_use_amount", 1, "", "onclick=\"ajax_toggle_field_state('".( $last_id + 1 )."',this,'max_amount')\"" ).
										$this->engine->skin['global']->form_text( "user_".( $last_id + 1 )."_max_amount", 10, "small", "text", "style='width:80px;' id='user_".( $last_id + 1 )."_max_amount'" ), "row2" ),
								array(	$this->engine->skin['global']->element_button( 0, "user", "delete" ), "row1", "style='text-align:center'" ),
								)	);
		
		$this->html .= $this->engine->classes['output']->table_add_submit( $this->engine->lang['apply_settings'] );
		
		$this->html .= $this->engine->classes['output']->table_end();
	}

}

?>