<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			Страница со списком событий
*/

/**
* Класс, содержащий функции для
* управления списком событий
*
* @author	DINI
* @version	1.3.9 (build 74)
*/

class log
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
	* Активное событие
	*
	* @var array
	*/

	var $active_group	= array(	'id'	=> 0,
									'sub'	=> 'all',
									);
	
	/**
	* Количество доступных страниц
	*
	* @var array
	*/

	var $pages_total	= 0;
	
	/**
	* Конструктор класса (не страндартный PHP)
	* 
	* Загружает языковые строки, шаблон и JavaScript.
	* В зависимости от переданных параметров запускает
	* необходимую функцию.
	* 
	* @return	bool	TRUE
	*/
	
	function __class_construct()
	{
		$this->engine->load_lang( "log" );
		$this->engine->load_skin( "log" );
		
		$this->engine->classes['output']->java_scripts['footer'][] = "setInterval( ajax_check_position, 100 )";
		
		//-----------------------------------------------
		// Подгружаем языковые файлы модулей
		//-----------------------------------------------
		
		$user_lang = array_key_exists( $this->engine->member['user_lang'], &$this->engine->languages['list'] ) ? $this->engine->member['user_lang'] : $this->engine->languages['default'];
		
		if( $dir = opendir( $this->engine->home_dir."languages/{$user_lang}/" ) )
		{
			while( false !== ( $file = readdir( $dir ) ) )
			{
				if( preg_match( "#^(module_\w+)\.lng$#", $file, $match ) ) $this->engine->load_lang( $match[1] );
			}
		}
		
		$this->engine->classes['output']->java_scripts['link'][] = "log";
		
		//-----------------------------------------------
		// Определение текущей страницы
		//-----------------------------------------------
		
		if( !is_numeric( $this->engine->input['st'] ) ) $this->engine->input['st'] = 0;
		
		$this->engine->input['page'] = ceil( $this->engine->input['st'] / 100 ) + 1;
		
		//-----------------------------------------------
		// AJAX запрос
		//-----------------------------------------------
		
		if( $this->engine->input['ajax'] == 'yes' ) switch( $this->engine->input['type'] )
		{
			case 'show_events':
			case 'set_page':
				$this->ajax_show_events();
				break;
				
			case 'show_info':
				$this->ajax_show_info();
				break;
				
			case 'event_delete':
				$this->ajax_event_delete();
				break;
				
			case 'event_clear':
				$this->ajax_event_clear();
				break;
		}
		
		//-----------------------------------------------
		// Обычный запрос
		//-----------------------------------------------
			
		$this->show_logs();
		
		return TRUE;
	}
	
	/**
    * Список событий
    * 
    * Выводит список событий для всех пользователей сразу
    * или только для определенного пользователя.
    *
    * @return	void
    */
	
	function show_logs()
	{
		if( $this->engine->member['user_admin'] )
		{
			$this->engine->classes['output']->java_scripts['embed'][] = "var lang_time_delete = '{$this->engine->lang['time_delete']}';
																		 var lang_time_allow = '{$this->engine->lang['time_allow']}';
																		 var lang_time_disallow = '{$this->engine->lang['time_disallow']}';";
		}
		
		$this->engine->add_log_event( 4, "ISL_001" );
		
		//-----------------------------------------------
		// Название страницы и системное сообщение
		//-----------------------------------------------
		
		$this->page_info['title']	= $this->engine->lang['page_title'];
		$this->page_info['desc']	= $this->engine->lang['page_desc'];
		
		$this->message = array(	'text'	=> "",
								'type'	=> "",
								);
		
		//-----------------------------------------------
		// Выводим меню
		//-----------------------------------------------
		
		$this->html  = $this->engine->skin['log']->page_top();
		
		$this->html .= $this->engine->skin['log']->groups_list_top();
		
		//-----------------------------------------------
		// Выводим список групп событий по типам
		//-----------------------------------------------
		
		$this->_get_groups_list();
		
		$this->html .= $this->engine->skin['log']->groups_list_bottom();
		
		//-----------------------------------------------
		// Выводим список событий указанной группы
		//-----------------------------------------------
		
		$this->html .= $this->engine->skin['log']->page_middle();
		
		$this->engine->input['id']  =& $this->active_group['id'];
		$this->engine->input['sub'] =& $this->active_group['sub'];
		
		$this->_get_events_list();
		
		$this->html .= $this->engine->skin['log']->page_bottom();
		
		$this->html  = str_replace( "<!--PAGE_MENU-->", $this->engine->skin['log']->page_menu(), $this->html );
	}
	
	/**
    * Вывод списка событий
    * 
    * Вызывает функцию для составления списка событий указанной
    * группы.
    *
    * @return	void
    */
	
	function ajax_show_events()
	{
		//-----------------------------------------------
		// Получаем информацию о событиях
		//-----------------------------------------------
		
		if( !is_numeric( $this->engine->input['id'] ) )
		{
			$this->engine->add_log_event( 1, "ESL_001" );
			
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_wrong_user_id'] ) );
		}
		
		$this->engine->add_log_event( 4, "ISL_002" );
		
		$this->_get_events_list();
		
		//-----------------------------------------------
		// Обновляем содержимое и возвращаем XML
		//-----------------------------------------------
		
		$array = array(	"List"			=> &$this->html,
						"Update_11"		=> $this->engine->skin['log']->page_menu(),
						"Function_0"	=> "update_pages_number({$this->pages_total},{$this->engine->input['st']})",
						"Function_1"	=> "ajax_reselect_events()",
						);
		
		$this->engine->classes['output']->generate_xml_output( &$array );
	}
	
	/**
    * Вывод описания события
    * 
    * Выводит описание события, возможные причины возникновения и
    * пути решения (для ошибок и предупреждений).
    *
    * @return	void
    */

	function ajax_show_info()
	{
		//-----------------------------------------------
		// Получаем параметры события
		//-----------------------------------------------
		
		$event = $this->engine->DB->simple_exec_query( array(	'select'	=> '*',
																'from'		=> 'system_log',
																'where'		=> "log_id='{$this->engine->input['id']}'",
																)	);
		
		if( !$event['log_id'] or ( !$this->engine->member['user_admin'] and !$event['log_visible'] ) )
		{
			$this->engine->add_log_event( 1, "ESL_002" );
			
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_event_id'] ) );
		}
		
		//-----------------------------------------------
		// Замена языковых строк
		//-----------------------------------------------
		
		if( preg_match( "#lang_(\w+)\['(\w+)'\]#", $this->engine->lang['log_'.$event['log_code'] ], $match ) )
		{
			if( $match[1] != "log" ) $this->engine->load_lang( $match[1] );
			
			$this->engine->lang['log_'.$event['log_code'] ] =& $this->engine->lang[ $match[2] ];
		}
		
		if( preg_match( "#lang_(\w+)\['(\w+)'\]#", $this->engine->lang['log_'.$event['log_code'].'_desc' ], $match ) )
		{
			if( $match[1] != "log" ) $this->engine->load_lang( $match[1] );
			
			$this->engine->lang['log_'.$event['log_code'].'_desc' ] =& $this->engine->lang[ $match[2] ];
		}
		
		if( preg_match_all( "#lang_(\w+)\['(\w+)'\]#", $this->engine->lang['log_'.$event['log_code'].'_reason' ], $match_all, PREG_SET_ORDER ) ) foreach( $match_all as $match )
		{
			if( $match[1] != "log" ) $this->engine->load_lang( $match[1] );
			
			$this->engine->lang['log_'.$event['log_code'].'_reason' ] = preg_replace( "#lang_".$match[1]."\['".$match[2]."'\]#", $this->engine->lang[ $match[2] ], $this->engine->lang['log_'.$event['log_code'].'_reason' ] );
		}
		
		if( preg_match_all( "#lang_(\w+)\['(\w+)'\]#", $this->engine->lang['log_'.$event['log_code'].'_solution' ], $match_all, PREG_SET_ORDER ) ) foreach( $match_all as $match )
		{
			if( $match[1] != "log" ) $this->engine->load_lang( $match[1] );
			
			$this->engine->lang['log_'.$event['log_code'].'_solution' ] = preg_replace( "#lang_".$match[1]."\['".$match[2]."'\]#", $this->engine->lang[ $match[2] ], $this->engine->lang['log_'.$event['log_code'].'_solution' ] );
		}
		
		$this->engine->lang['log_'.$event['log_code'].'_solution' ] = "\t\t\t\n<ul>".preg_replace( "#^\s*(.*)$#m", "\t\t\t<li>\\1</li>", $this->engine->lang['log_'.$event['log_code'].'_solution'] )."\n\t</ul>";
		
		//-----------------------------------------------
		// Создаем таблицу с описанием события
		//-----------------------------------------------
		
		$table  = $this->engine->classes['output']->form_start( array(	'tab'		=> 'modules',
																		), "onsubmit=\"my_getbyid('ajax_window').style.display='none'; ajax_window_loaded=null; return false;\"" );
		
		$this->engine->classes['output']->table_add_header( "", "30%" );
		$this->engine->classes['output']->table_add_header( "", "70%" );
		
		$table .= $this->engine->classes['output']->table_start( "", "100%", "", "", "style='border:0'" );
		
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['event_time']							, "row1" ),
								array(	$this->engine->get_date( &$event['log_time'], "LONG" )		, "row2" ),
								)	);
								
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['event_code']							, "row1" ),
								array(	$event['log_code']											, "row2" ),
								)	);
								
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['event_msg']							, "row1" ),
								array(	$this->engine->lang['log_'.$event['log_code'] ]				, "row2" ),
								)	);
								
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['event_desc']							, "row1" ),
								array(	$this->engine->lang['log_'.$event['log_code'].'_desc' ]		, "row2" ),
								)	);
								
		if( $event['log_type'] != 'info' )
		{
			$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['event_reason']							, "row1" ),
								array(	$this->engine->lang['log_'.$event['log_code'].'_reason' ]	, "row2" ),
								)	);
								
			$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['event_solution']						, "row1" ),
								array(	$this->engine->lang['log_'.$event['log_code'].'_solution' ]	, "row2" ),
								)	);
		}
		
		if( ( $this->engine->member['user_admin'] or !$this->engine->config['log_hide_misc'] ) and $event['log_misc'] and ( $misc = unserialize( stripslashes( $event['log_misc'] ) ) ) )
		{
			$table .= $this->engine->classes['output']->table_add_row_single_cell( &$this->engine->lang['event_misc'], "row4" );
				
			$table .= $this->_get_misc( &$misc );
		}
								
		$table .= $this->engine->classes['output']->table_end();
		
		$table .= $this->engine->classes['output']->form_end_submit( $this->engine->lang['ok'], "", "style='border:0;'" );
		
		//-----------------------------------------------
		// Делаем запись в журнал
		//-----------------------------------------------
		
		$this->engine->add_log_event( 4, "ISL_003" );
		
		if( $this->engine->config['log_detail'] == 4 )
		{
			$active = explode( ",", $this->engine->my_getcookie( "list_active" ) );
		
			foreach( $active as $item )
			{
				if( preg_match( "#log=(-?\d+):(\w+)#", $item, $match ) )
				{
					$this->engine->input['id']  = $match[1]; 
					$this->engine->input['sub'] = $match[2];
						
					break;
				}
			}
			
			$this->engine->input['id']  =& $this->engine->input['agroup'];
			$this->engine->input['sub'] =& $this->engine->input['asub'];
			
			$this->_get_events_list();
			
			$array['List'] = &$this->html;
			$array['Function_0'] = "ajax_reselect_events()";
		}
		
		//-----------------------------------------------
		// Возвращаем XML
		//-----------------------------------------------
		
		$array['HTML'] = $this->engine->skin['global']->ajax_window( $this->engine->lang['event_info'], &$table );
		
		$this->engine->classes['output']->generate_xml_output( &$array );
	}
	
	/**
    * Удаление событий
    * 
    * Делает события невидимыми для обычных пользователей
    * или удаляет их из БД, если текущий пользователь имеет
    * права администратора.
    *
    * @return	void
    */
	
	function ajax_event_delete()
	{
		$active = array(	'group'	=> &$this->engine->input['agroup'],
							'sub'	=> &$this->engine->input['asub'],
							);
							
		//-----------------------------------------------
		// Проверяем права на удаление
		//-----------------------------------------------
		
		if( !$this->engine->member['user_admin'] and ( ( $active['group'] and $this->engine->config['log_can_delete_own'] ) or ( !$active['group'] and !$this->engine->config['log_can_delete_share'] ) ) )
		{
			$this->engine->add_log_event( 2, "WSL_001" );
			
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_cant_delete_event'] ) );
		}
		
		//-----------------------------------------------
		// Получаем идентификаторы событий
		//-----------------------------------------------
		
		$ids = explode( ",", $this->engine->input['id'] );
		
		if( !count( $ids ) )
		{
			$this->engine->add_log_event( 2, "WSL_002" );
			
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_event_ids'] ) );
		}
		
		if( $ids[0] == "" ) unset( $ids[0] );
		
		//-----------------------------------------------
		// Получаем параметры событий
		//-----------------------------------------------
		
		$where = $this->engine->member['user_admin'] ? "" : " AND log_visible=1";
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'log_id, log_user',
													'from'		=> 'system_log',
													'where'		=> "log_id IN('".implode( "','", $ids )."') {$where}"
													)	);
		$this->engine->DB->simple_exec();
															
		if( !$this->engine->DB->get_num_rows() )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_events_found'] ) );
		}
		
		while( $event = $this->engine->DB->fetch_row() )
		{
			if( !$this->engine->member['user_admin'] and !in_array( $event['log_user'], array( 0, $this->engine->member['user_id'] ) ) )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_cant_delete_event'] ) );
			}

			$to_delete[] = $event['log_id'];
		}
		
		//-----------------------------------------------
		// Удаляем события
		//-----------------------------------------------
		
		if( count( $to_delete ) )
		{
			if( $this->engine->member['user_admin'] or !$this->engine->config['log_hide_only'] ) 
			{
				$this->engine->DB->do_delete( "system_log", "log_id IN('".implode( "','", $to_delete )."')" );
			}
			else 
			{
				$this->engine->DB->do_update( "system_log", array( 'log_visible' => 0 ), "log_id IN('".implode( "','", $to_delete )."')" );
			}
		}
		
		//-----------------------------------------------
		// Обновляем список событий и возвращаем XML
		//-----------------------------------------------
		
		$this->engine->input['id'] = &$active['group'];
		$this->engine->input['sub'] = &$active['sub'];
		
		$this->_get_events_list();
		
		$array['List'] = &$this->html;
		$array['Function_0'] = 'ajax_reselect_events()';
		
		$this->engine->classes['output']->generate_xml_output( &$array );
	}
	
	/**
    * Очистка списка событий
    * 
    * Делает события выведенного списка невидимыми для обычных
    * пользователей или удаляет их из БД, если текущий пользователь
    * имеет права администратора.
    *
    * @return	void
    */
	
	function ajax_event_clear()
	{
		$active = array(	'group'	=> &$this->engine->input['id'],
							'sub'	=> &$this->engine->input['asub'],
							);
							
		//-----------------------------------------------
		// Проверяем права на удаление
		//-----------------------------------------------
		
		if( !$this->engine->member['user_admin'] and ( ( $active['group'] and $this->engine->config['log_can_delete_own'] ) or ( !$active['group'] and !$this->engine->config['log_can_delete_share'] ) ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_cant_delete_event'] ) );
		}
		
		//-----------------------------------------------
		// Получаем параметры событий
		//-----------------------------------------------
		
		in_array( $active['sub'], array( 'error', 'warn', 'info' ) ) ? $where[] = "log_type='{$active['sub']}'" : $where = array();
		
		if( $this->engine->member['user_admin'] )
		{
			if( $active['group'] == 0 ) $where[] = "log_system=1";
			if( !is_array( $where ) ) $where[] = "1";
		}
		else 
		{
			if( $active['group'] == 0 ) $where[] = "log_system=1";
			else if( $active['group'] == -1 ) $where[] = "log_user='{$this->engine->member['user_id']}' OR log_system=1";
			
			$where[] = "log_visible=1";
		}
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'log_id, log_user',
													'from'		=> 'system_log',
													'where'		=> implode( " AND ", $where )
													)	);
		$this->engine->DB->simple_exec();
		
		if( !$this->engine->DB->get_num_rows() )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_events_found'] ) );
		}
		
		while( $event = $this->engine->DB->fetch_row() )
		{
			if( !$this->engine->member['user_admin'] and !in_array( $event['log_user'], array( 0, $this->engine->member['user_id'] ) ) )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_cant_delete_event'] ) );
			}

			$to_delete[] = $event['log_id'];
		}
		
		//-----------------------------------------------
		// Удаляем события
		//-----------------------------------------------
		
		if( count( $to_delete ) )
		{
			if( $this->engine->member['user_admin'] or !$this->engine->config['log_hide_only'] ) 
			{
				$this->engine->DB->do_delete( "system_log", "log_id IN('".implode( "','", $to_delete )."')" );
			}
			else 
			{
				$this->engine->DB->do_update( "system_log", array( 'log_visible' => 0 ), "log_id IN('".implode( "','", $to_delete )."')" );
			}
		}
		
		//-----------------------------------------------
		// Обновляем список событий и возвращаем XML
		//-----------------------------------------------
		
		$this->engine->input['id'] = &$active['group'];
		$this->engine->input['sub'] = &$active['sub'];
		
		$this->_get_events_list();
		
		$array['List'] = &$this->html;
		$array['Function_0'] = 'ajax_reselect_events()';
		
		$this->engine->classes['output']->generate_xml_output( &$array );
	}
	
	/**
    * Вывод списка групп событий по типам
    * 
    * Получает список пользователей, создает для каждого группы
    * событий и выводит все на экран.
    *
    * @return	void
    */
	
	function _get_groups_list()
	{
		//-----------------------------------------------
		// Определяем активный элемент
		//-----------------------------------------------
		
		$active = explode( ",", $this->engine->my_getcookie( "list_active" ) );
		
		foreach( $active as $item )
		{
			if( preg_match( "#log=(-?\d+):(\w+)#", $item, $match ) )
			{
				$this->active_group = array( "id" => $match[1], "sub" => $match[2] );
				
				break;
			}
		}
		
		//-----------------------------------------------
		// Определяем скрытые элементы
		//-----------------------------------------------
		
		$hidden_users = array();
		
		$hidden = explode( ":", $this->engine->my_getcookie( "list_hidden" ) );
		
		foreach( $hidden as $item )
		{
			if( preg_match( "#log=((\d+,?)*)#", $item, $match ) and $match[1] != "" )
			{
				$hidden_users = explode( ",", $match[1] );
				
				break;
			}
		}
		
		//-----------------------------------------------
		// Помещаем в начало списка дополнительные пункты
		//-----------------------------------------------
		
		$groups[] = array( 'user_name' => &$this->engine->lang['user_all']   , 'user_id' => -1 );
		$groups[] = array( 'user_name' => &$this->engine->lang['user_system'], 'user_id' => 0  );
		
		//-----------------------------------------------
		// Получаем список пользователей
		//-----------------------------------------------
		
		if( $this->engine->member['user_admin'] )
		{
			$this->engine->DB->simple_construct( array(	'select'	=> 'user_id, user_name',
														'from'		=> 'users_list'
														)	);
			$this->engine->DB->simple_exec();
		
			while( $user = $this->engine->DB->fetch_row() )
			{
				$groups[] = $user;
				
				if( $user['user_id'] == $this->active_group['id'] ) $got_active = TRUE;
			}
		}
		else 
		{
			$groups[] = &$this->engine->member;
			
			if( $this->engine->member['user_id'] == $this->active_group['id'] ) $got_active = TRUE;
		}
		
		//-----------------------------------------------
		// Проверяем наличие активной группы
		//-----------------------------------------------
		
		if( !$got_active and !is_numeric( $this->active_group['id'] ) )
		{
			$this->active_group = array( "id" => -1, "sub" => 'all' );
		}
		
		if( $this->active_group['sub'] != 'all' and in_array( $this->active_group['id'], $hidden_users ) )
		{
			$this->active_group['sub'] = 'all';
		}
		
		$this->engine->classes['output']->java_scripts['embed'][] = "var active_group = '{$this->active_group['id']}';
																	 var active_sub = '{$this->active_group['sub']}';";
		
		//-----------------------------------------------
		// Выводим список типов событий
		//-----------------------------------------------
		
		foreach( $groups as $group )
		{
			$icon = $group['user_id'] > 0 ? 'single' : ( $group['user_id'] == 0 ? 'system' : 'gear' );
			
			$active = $group['user_id'] == $this->active_group['id'] ? 1 : 0;
			$hidden = in_array( $group['user_id'], $hidden_users ) ? 1 : 0;
			
			$this->html .= $this->engine->skin['log']->groups_list_item( &$group, &$icon, &$active, &$hidden, $this->active_group['sub'] );
		}
	}
	
	/**
    * Вывод списка событий
    * 
    * Формирует и выводит список событий в соответствии
    * с указанной группой событий.
    *
    * @return	void
    */
	
	function _get_events_list()
	{
		//-----------------------------------------------
		// Определяем активный столбец и тип сортировки
		//-----------------------------------------------
		
		preg_match( "#tab_log_(user|time|type|msg|code)=(asc|desc)#", $this->engine->my_getcookie( "sort_params" ), $match );
		
		$match[1] = $match[1] ? $match[1] : "time";
		$match[2] = $match[2] ? ( $match[2] == "asc" ? "desc" : "asc" ) : "asc";
		
		$active['user']	= $match[1] == 'user' ? array( 'img' => $this->engine->skin['log']->events_list_sort( $match[2] ), 'sort' => $match[2] ) : array( 'sort' => 'desc' );
		$active['type'] = $match[1] == 'type' ? array( 'img' => $this->engine->skin['log']->events_list_sort( $match[2] ), 'sort' => $match[2] ) : array( 'sort' => 'desc' );
		$active['code'] = $match[1] == 'code' ? array( 'img' => $this->engine->skin['log']->events_list_sort( $match[2] ), 'sort' => $match[2] ) : array( 'sort' => 'desc' );
		$active['time']	= $match[1] == 'time' ? array( 'img' => $this->engine->skin['log']->events_list_sort( $match[2] ), 'sort' => $match[2] ) : array( 'sort' => 'desc' );
		$active['msg']  = $match[1] == 'msg'  ? array( 'img' => $this->engine->skin['log']->events_list_sort( $match[2] ), 'sort' => $match[2] ) : array( 'sort' => 'desc' );
		
		$this->html .= $this->engine->skin['log']->events_list_headers( &$active );
		
		//-----------------------------------------------
		// Условие выборки по идентификатору пользователя
		//-----------------------------------------------
		
		if( $this->engine->input['id'] == -1 )
		{
			if( !$this->engine->member['user_admin'] ) $where[] = "l.log_user='0' OR l.log_user='' OR l.log_user IS NULL OR l.log_user='{$this->engine->member['user_id']}'";
		}
		else if( $this->engine->input['id'] == 0 )
		{
			$where[] = "l.log_system=1";
			
			if( !$this->engine->member['user_admin'] ) $where[] = "l.log_user='0' OR l.log_user='' OR l.log_user IS NULL OR l.log_user='{$this->engine->member['user_id']}'";
		}
		else 
		{
			$where[] = $this->engine->member['user_admin'] ? "l.log_user='{$this->engine->input['id']}'" : "l.log_user='{$this->engine->member['user_id']}'";
			$where[] = "l.log_system=0";
		}
		
		//-----------------------------------------------
		// Условие выборки по типу события
		//-----------------------------------------------
		
		if( in_array( $this->engine->input['sub'], array( 'error', 'warn', 'info' ) ) ) $where[] = "l.log_type='{$this->engine->input['sub']}'";
		
		//-----------------------------------------------
		// Ограничения на видимые события
		//-----------------------------------------------
		
		if( !$this->engine->member['user_admin'] )
		{
			$where[] = "l.log_visible=1";
		}
		
		//-----------------------------------------------
		// Определяем количество страниц
		//-----------------------------------------------
		
		$pages = $this->engine->DB->simple_exec_query( array(	'select'	=> 'COUNT(log_id) as total',
																'from'		=> 'system_log AS l LEFT JOIN users_list AS u ON (u.user_id=l.log_user)',
																'where'		=> is_array( $where ) ? implode( " AND ", &$where ) : NULL,
																)	);
		
		$this->pages_total = intval( is_numeric( $pages['total'] ) ? ceil( $pages['total'] / 100 ) : 1 );
		
		$this->engine->classes['output']->java_scripts['embed'][] = "pages_total = {$this->pages_total};
																	 pages_st = {$this->engine->input['st']};";
		
		//-----------------------------------------------
		// Получаем список событий
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> '*',
													'from'		=> 'system_log AS l LEFT JOIN users_list AS u ON (u.user_id=l.log_user)',
													'where'		=> is_array( $where ) ? implode( " AND ", &$where ) : NULL,
													'order'		=> "l.log_{$match[1]} {$match[2]}",
													'limit'		=> array( $this->engine->input['st'], 100 ),
													)	);
		$this->engine->DB->simple_exec();
		
		if( !$this->engine->DB->get_num_rows() )
		{
			$this->html .= $this->engine->skin['log']->events_list_message( &$this->engine->lang['no_events'] );
		}
		else while( $event = $this->engine->DB->fetch_row() )
		{
			if( preg_match( "#lang_(\w+)\['(\w+)'\]#", $this->engine->lang['log_'.$event['log_code'] ], $match ) )
			{
				if( $match[1] != "log" ) $this->engine->load_lang( $match[1] );
				
				$this->engine->lang['log_'.$event['log_code'] ] =& $this->engine->lang[ $match[2] ];
			}
			
			$row = $row == 5 ? 6 : 5;
			
			$event['user_name'] = $event['user_name'] ? $event['user_name'] : $this->engine->lang['system_event'];
			$event['log_type']  = $this->engine->skin['log']->log_type( &$event['log_type'] );
			$event['log_time']  = $this->engine->get_date( &$event['log_time'], "LONG" );
			$event['log_msg']   = &$this->engine->lang['log_'.$event['log_code'] ];
			
			$this->html .= $this->engine->skin['log']->log_event_row( &$event, $row );
		}
		
		$this->html .= $this->engine->skin['log']->events_list_footer();
	}
	
	/**
    * Вывод дополнительной информации
    * 
    * Добавляет в таблицу с описанием события ряды
    * с дополнительной информацией об этом событии.
    * 
    * @param 	array			Массив с дополнительной информацией
    *
    * @return	string	HTML код таблицы
    */
	
	function _get_misc( $misc )
	{
		$table = "";
		
		foreach( $misc as $name => $value )
		{
			if( !$value ) continue;
					
			if( $name == 'file_link' and $event['log_user'] == 0 and !$this->engine->config['shared_view_link'] and $misc['file_user'] != $this->engine->member['user_id'] and !$this->engine->member['user_admin'] ) continue;
							
			//-----------------------------------------------
			// Обрабатываем значения дополнительных полей
			//-----------------------------------------------
			
			switch( $name )
			{
				case 'file_desc':
					$value = str_replace( "\n", "<br/>\n", $value );
					break;
				
				case 'time_start':
				case 'time_end':
					if( preg_match( "#(\d{1}):(\d{1,2}):(\d{1,2})#", $value, $match ) ) $value = $this->engine->lang['dfull_'.$match[1] ].", ".sprintf( "%02d", $match[2] ).":".sprintf( "%02d", $match[3] );
					else $value = $this->engine->get_date( $value, "FULL" );
					break;
					
				case 'event_start':
					$value = $this->engine->get_date( $value, "FULL" );
					break;
			}
			
			//-----------------------------------------------
			// Получаем название ряда и добавляем его
			//-----------------------------------------------
					
			$name = $this->engine->lang['event_misc_'.$name ] ? $this->engine->lang['event_misc_'.$name ] : $name;
					
			$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$name															, "row1" ),
								array(	$value															, "row2" ),
								)	);
		}
		
		return $table;
	}


}

?>