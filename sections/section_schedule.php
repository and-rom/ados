<?php

/**
* @package		ADOS - Automatic Downloading System
* @version		1.3.9 (build 74)
*
* @author		DINI
* @copyright	2007—2008
*
* @name			Страница управления расписанием
*/

/**
* Класс, содержащий функции для
* управления расписанием закачек
*
* @author	DINI
* @version	1.3.9 (build 74)
*/

class schedule
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
	* Активный пользователь
	*
	* @var array
	*/

	var $active_user	= array(	'id'	=> 0,
									'sub'	=> 'today',
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
		$this->engine->load_lang( "schedule" );
		$this->engine->load_skin( "schedule" );
		
		$this->engine->classes['output']->java_scripts['link'][] = "schedule";
		
		if( $this->engine->member['user_admin'] )
		{
			$this->engine->classes['output']->java_scripts['link'][] = "schedule_admin";
		}
		
		$this->engine->classes['output']->java_scripts['footer'][] = "setInterval( ajax_check_position, 100 )";
		
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
				
			case 'show_limits':
				$this->ajax_show_limits();
				break;
				
			case 'show_params':
				$this->ajax_show_params();
				break;
				
			case 'apply_params':
				$this->ajax_apply_params();
				break;
				
			case 'delete_limit':
				$this->ajax_delete_limit();
				break;
				
			case 'delete_running':
				$this->ajax_delete_running();
				break;
			
			case 'event_add':	
			case 'event_edit':
				$this->ajax_event_add_edit();
				break;
				
			case 'event_delete':
				$this->ajax_event_delete();
				break;
		}
		
		//-----------------------------------------------
		// Обычный запрос
		//-----------------------------------------------
			
		$this->show_schedule();
		
		return TRUE;
	}
	
	/**
    * Расписание закачек
    * 
    * Выводит расписание закачек для всех пользователей
    * сразу или только для определенного пользователя.
    *
    * @return	void
    */
	
	function show_schedule()
	{
		if( $this->engine->member['user_admin'] )
		{
			$this->engine->classes['output']->java_scripts['embed'][] = "var lang_time_delete = '{$this->engine->lang['time_delete']}';
																		 var lang_time_allow = '{$this->engine->lang['time_allow']}';
																		 var lang_time_disallow = '{$this->engine->lang['time_disallow']}';";
		}
		
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
		
		$this->html  = $this->engine->skin['schedule']->page_top();
		
		$this->html .= $this->engine->skin['schedule']->users_list_top();
		
		//-----------------------------------------------
		// Выводим список пользователей
		//-----------------------------------------------
		
		$this->_get_users_list();
		
		$this->html .= $this->engine->skin['schedule']->users_list_bottom();
		
		//-----------------------------------------------
		// Выводим список файлов в активной категории
		//-----------------------------------------------
		
		$this->html .= $this->engine->skin['schedule']->page_middle();
		
		$this->engine->input['id']  =& $this->active_user['id'];
		$this->engine->input['sub'] =& $this->active_user['sub'];
		
		$this->_get_events_list();
		
		$this->html .= $this->engine->skin['schedule']->page_bottom();
		
		$this->html  = str_replace( "<!--PAGE_MENU-->", $this->engine->skin['schedule']->page_menu(), $this->html );
	}
	
	/**
    * Вывод списка событий
    * 
    * Вызывает функцию для составления списка
    * событий за указанный временной период.
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
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_wrong_user_id'] ) );
		}
		
		$this->_get_events_list();
		
		//-----------------------------------------------
		// Обновляем содержимое и возвращаем XML
		//-----------------------------------------------
		
		$array = array(	"List"			=> &$this->html,
						"Update_11"		=> $this->engine->skin['schedule']->page_menu(),
						"Function_0"	=> "update_pages_number({$this->pages_total},{$this->engine->input['st']})",
						"Function_1"	=> "ajax_reselect_events()",
						);
		
		$this->engine->classes['output']->generate_xml_output( &$array );
	}
	
	/**
    * Вывод списка временных ограничений
    * 
    * Формирует и выводит список временных ограничений для
    * пользователей системы.
    *
    * @return	void
    */
	
	function ajax_show_limits()
	{
		$year_now = date( "Y" );
		
		if( $this->engine->input['id'] == -1 )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['js_error_select_user'] ) );
		}
		
		if( !$this->engine->member['user_admin'] and !in_array( $this->engine->input['id'], array( 0, $this->engine->member['user_id'] ) ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_rights'] ) );
		}
		
		//-----------------------------------------------
		// Получаем информацию об ограничениях
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'time_id, time_allow, time_start, time_end, time_every, time_interlace',
													'from'		=> 'schedule_time',
													'where'		=> "time_users LIKE '%,{$this->engine->input['id']},%'",
													'order'		=> 'time_allow, time_interlace DESC, time_every, time_start'
													)	);
		$this->engine->DB->simple_exec();
		
		if( !$this->engine->DB->get_num_rows() )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['time_no_limits'] ) );
		}
		
		//-----------------------------------------------
		// Формируем таблицу со списком ограничений
		//-----------------------------------------------
		
		$table  = $this->engine->classes['output']->form_start( array(	'tab'		=> 'schedule',
																		), "onsubmit=\"my_getbyid('ajax_window').style.display='none'; ajax_window_loaded=null; return false;\"" );
																			
		$this->engine->classes['output']->table_add_header( ""	, "40%" );
		$this->engine->classes['output']->table_add_header( ""	, "60%" );
		
		$table .= $this->engine->classes['output']->table_start( "", "100%", "", "", "id='ajax_table' style='border:0'" );
		
		while( $time = $this->engine->DB->fetch_row() )
		{
			$string = array();
			
			if( $time['time_interlace'] )
			{
				$start = explode( ":", $time['time_start'] );
				$end = explode( ":", $time['time_end'] );
				
				$string[] = $this->engine->lang['time_every'];
				$string[] = $this->engine->lang['dafull_'.$time['time_every'] ];
				$string[] = $this->engine->lang['time_from'];
				$string[] = sprintf( "%02d", $start[0] ).":".sprintf( "%02d", $start[1] );
				$string[] = $this->engine->lang['time_to'];
				$string[] = sprintf( "%02d", $end[0] ).":".sprintf( "%02d", $end[1] );
			}
			else 
			{
				$date_limit['start'] = explode( ":", $time['time_start'] );
				$date_limit['end']   = explode( ":", $time['time_end']   );
					
				$time['time_start'] = strtotime( "{$year_now}-{$date_limit['start'][0]}-{$date_limit['start'][1]} {$date_limit['start'][2]}:{$date_limit['start'][3]}:00" );
				$time['time_end'] = strtotime( "{$year_now}-{$date_limit['end'][0]}-{$date_limit['end'][1]} {$date_limit['end'][2]}:{$date_limit['end'][3]}:00" );
				
				$string[] = $this->engine->lang['time_from'];
				$string[] = $this->engine->get_date( &$time['time_start'], "LONG" );
				$string[] = $this->engine->lang['time_to'];
				$string[] = $this->engine->get_date( &$time['time_end'], "LONG" );
			}
			
			$time['time_allow'] = $time['time_allow']
								? "<span style='color:green'>".$this->engine->lang['time_allowed']."</span>"
								: "<span style='color:red'>".$this->engine->lang['time_disallowed']."</span>";
			
			$table .= $this->engine->classes['output']->table_add_row( array( 
								array( $time['time_allow']		, "row1" ),
								array( implode( " ", $string )	, "row2" ),
								)	);
		}
								
		$table .= $this->engine->classes['output']->table_end();
								
		$table .= $this->engine->classes['output']->form_end_submit( $this->engine->lang['ok'], "", "style='border:0;'" );
		
		//-----------------------------------------------
		// Формируем и возвращаем XML
		//-----------------------------------------------
		
		$html = $this->engine->skin['global']->ajax_window( $this->engine->lang['time_limits'], &$table );
		
		$this->engine->classes['output']->generate_xml_output( array( 'HTML' => &$html ) );

	}
	
	/**
    * Вывод списка параметров расписания
    * 
    * Формирует форму для контроля временных промежутков,
    * в течение которых разрешается закачка пользователями
    * системы.
    *
    * @return	void
    */

	function ajax_show_params()
	{
		$year_now = date( "Y" );
		
		//-----------------------------------------------
		// Проверяем права
		//-----------------------------------------------
		
		if( !$this->engine->member['user_admin'] )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_rights'] ) );
		}
		
		//-----------------------------------------------
		// Получаем список параметров
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> '*',
													'from'		=> 'schedule_time',
													'order'		=> 'time_interlace, time_every, time_start',
													)	);
		$this->engine->DB->simple_exec();
		
		while( $time = $this->engine->DB->fetch_row() )
		{
			$time['time_interlace'] ? $times['interlaced'][] = $time : $times['standard'][] = $time;
			
			$last_id = $time['time_id'] > $last_id ? $time['time_id'] : $last_id;
		}
		
		//-----------------------------------------------
		// Получаем список пользователей
		//-----------------------------------------------
		
		$users[0] = &$this->engine->lang['user_shared'];
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'user_id, user_name',
													'from'		=> 'users_list',
													)	);
		$this->engine->DB->simple_exec();
		
		while( $user = $this->engine->DB->fetch_row() )
		{
			$users[ $user['user_id'] ] = $user['user_name'];
		}
		
		//-----------------------------------------------
		// Создаем списки дней месяца, месяцев,
		// дней недели, часов и минут
		//-----------------------------------------------
		
		for( $i = 1; $i < 32; $i++ )
		{
			$dropdown['days'][ $i ] = $i;
		}
		
		for( $i = 1; $i < 13; $i++ )
		{
			$dropdown['months'][ $i ] = &$this->engine->lang[ 'mfull_'.sprintf( "%02d", $i ) ];
		}
		
		for( $i = 1; $i < 7; $i++ )
		{
			$dropdown['wdays'][ $i ] = &$this->engine->lang[ 'dfull_'.$i ];
		}
		
		$dropdown['wdays'][0] = &$this->engine->lang[ 'dfull_0' ];
		
		for( $i = 0; $i < 24; $i++ )
		{
			$dropdown['hours'][ $i ] = sprintf( "%02d", $i );
		}
		
		for( $i = 0; $i < 60; $i++ )
		{
			$dropdown['minutes'][ $i ] = sprintf( "%02d", $i );
		}
		
		//-----------------------------------------------
		// Создаем форму со списком параметров ограничений
		//-----------------------------------------------
		
		$form = $this->engine->classes['output']->form_start( array(	'tab'	=> 'schedule',
																		), "id='ajax_form' onsubmit='ajax_apply_params(); return false;'" );
		
		//-----------------------------------------------
		// Единовременные ограничения
		//-----------------------------------------------
		
		$form .= "<div class='header'>".$this->engine->lang['time_standard']."</div>";
		
		$this->engine->classes['output']->table_add_header( $this->engine->lang['time_users']	, "20%" );
		$this->engine->classes['output']->table_add_header( $this->engine->lang['time_type']	, "20%" );
		$this->engine->classes['output']->table_add_header( $this->engine->lang['time_date']	, "53%" );
		$this->engine->classes['output']->table_add_header( ""									, "7%"  );
		
		$form .= $this->engine->classes['output']->table_start( "", "100%", "id='ajax_table_standard'", "", "style='border:0'" );
		
		if( is_array( $times['standard'] ) ) foreach( $times['standard'] as $time )
		{
			$time['time_disallow'] = $time['time_allow'] ? 0 : 1;
			
			$date_limit['start'] = explode( ":", $time['time_start'] );
			$date_limit['end']   = explode( ":", $time['time_end']   );
					
			$time['time_start'] = strtotime( "{$year_now}-{$date_limit['start'][0]}-{$date_limit['start'][1]} {$date_limit['start'][2]}:{$date_limit['start'][3]}:00" );
			$time['time_end'] = strtotime( "{$year_now}-{$date_limit['end'][0]}-{$date_limit['end'][1]} {$date_limit['end'][2]}:{$date_limit['end'][3]}:00" );
			
			$time['time_users'] = explode( ",", preg_replace( "#^,(.*),$#", "\\1", $time['time_users'] ) );
			$time['time_start'] = explode( ":", date( "d:m:H:i", $time['time_start'] ) );
			$time['time_end']   = explode( ":", date( "d:m:H:i", $time['time_end']   ) );
			
			$form .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->skin['global']->form_multiselect( "time_{$time['time_id']}_s_users", $users, $time['time_users'], "small", "style='width:112px;height:45px;'" ), "row1"   ),
								array(	$this->engine->skin['global']->form_radio( "time_{$time['time_id']}_s_allow", 1, $time['time_allow']	, &$this->engine->lang['time_allow'] )."<br/>".
										$this->engine->skin['global']->form_radio( "time_{$time['time_id']}_s_allow", 0, $time['time_disallow']	, &$this->engine->lang['time_disallow']	), "row1"   ),
								array(	"<div style='padding-bottom:7px;'><div style='float:right;'>".
										$this->engine->skin['global']->form_dropdown( "time_{$time['time_id']}_s_start_day"   , $dropdown['days']   , $time['time_start'][0], "tiny"  ).
										$this->engine->skin['global']->form_dropdown( "time_{$time['time_id']}_s_start_month" , $dropdown['months'] , $time['time_start'][1], "small" )."&nbsp;".
										$this->engine->skin['global']->form_dropdown( "time_{$time['time_id']}_s_start_hour"  , $dropdown['hours']  , $time['time_start'][2], "tiny"  ).
										$this->engine->skin['global']->form_dropdown( "time_{$time['time_id']}_s_start_minute", $dropdown['minutes'], $time['time_start'][3], "tiny"  ).
										"</div>".$this->engine->lang['time_from']."</div>".
										"<div><div style='float:right'>".
										$this->engine->skin['global']->form_dropdown( "time_{$time['time_id']}_s_end_day"   , $dropdown['days']   , $time['time_end'][0], "tiny"  ).
										$this->engine->skin['global']->form_dropdown( "time_{$time['time_id']}_s_end_month" , $dropdown['months'] , $time['time_end'][1], "small" )."&nbsp;".
										$this->engine->skin['global']->form_dropdown( "time_{$time['time_id']}_s_end_hour"  , $dropdown['hours']  , $time['time_end'][2], "tiny"  ).
										$this->engine->skin['global']->form_dropdown( "time_{$time['time_id']}_s_end_minute", $dropdown['minutes'], $time['time_end'][3], "tiny"  ).
										"</div>".$this->engine->lang['time_to']."</div>", "row1"   ),
								array(	$this->engine->skin['global']->element_button( $time['time_id'], "time", "delete" ), "row1", "style='text-align:center;'" ),
								)	);
		}
		
		$form .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->skin['global']->form_multiselect( "time_".( $last_id + 1 )."_s_users", $users, array(), "small", "style='width:112px;height:45px;' onblur='ajax_add_list_row(this,".( $last_id + 2 ).",1);'" ), "row1" ),
								array(	$this->engine->skin['global']->form_radio( "time_".( $last_id + 1 )."_s_allow", 1, 1, &$this->engine->lang['time_allow'] )."<br/>".
										$this->engine->skin['global']->form_radio( "time_".( $last_id + 1 )."_s_allow", 0, 0, &$this->engine->lang['time_disallow'] ), "row1"   ),
								array(	"<div style='padding-bottom:7px;'><div style='float:right;'>".
										$this->engine->skin['global']->form_dropdown( "time_".( $last_id + 1 )."_s_start_day"   , $dropdown['days']   , "", "tiny"  ).
										$this->engine->skin['global']->form_dropdown( "time_".( $last_id + 1 )."_s_start_month" , $dropdown['months'] , "", "small" )."&nbsp;".
										$this->engine->skin['global']->form_dropdown( "time_".( $last_id + 1 )."_s_start_hour"  , $dropdown['hours']  , "", "tiny"  ).
										$this->engine->skin['global']->form_dropdown( "time_".( $last_id + 1 )."_s_start_minute", $dropdown['minutes'], "", "tiny"  ).
										"</div>".$this->engine->lang['time_from']."</div>".
										"<div><div style='float:right'>".
										$this->engine->skin['global']->form_dropdown( "time_".( $last_id + 1 )."_s_end_day"   , $dropdown['days']   , "", "tiny"  ).
										$this->engine->skin['global']->form_dropdown( "time_".( $last_id + 1 )."_s_end_month" , $dropdown['months'] , "", "small" )."&nbsp;".
										$this->engine->skin['global']->form_dropdown( "time_".( $last_id + 1 )."_s_end_hour"  , $dropdown['hours']  , "", "tiny"  ).
										$this->engine->skin['global']->form_dropdown( "time_".( $last_id + 1 )."_s_end_minute", $dropdown['minutes'], "", "tiny"  ).
										"</div>".$this->engine->lang['time_to']."</div>", "row1"   ),
								array(	$this->engine->skin['global']->element_button( 0, "time", "delete" ), "row1", "style='text-align:center;'" ),
								)	);
								
		$form .= $this->engine->classes['output']->table_end();
								
		//-----------------------------------------------
		// Чередующиеся ограничения
		//-----------------------------------------------
		
		$form .= "<div class='header'>".$this->engine->lang['time_interlaced']."</div>";
		
		$this->engine->classes['output']->table_add_header( $this->engine->lang['time_users']	, "20%" );
		$this->engine->classes['output']->table_add_header( $this->engine->lang['time_type']	, "20%" );
		$this->engine->classes['output']->table_add_header( $this->engine->lang['time_date']	, "53%" );
		$this->engine->classes['output']->table_add_header( ""									, "7%"  );
		
		$form .= $this->engine->classes['output']->table_start( "", "100%", "id='ajax_table_interlaced'", "", "style='border:0'" );
		
		if( is_array( $times['interlaced'] ) ) foreach( $times['interlaced'] as $time )
		{
			$time['time_disallow'] = $time['time_allow'] ? 0 : 1;
			
			$time['time_users'] = explode( ",", preg_replace( "#^,(.*),$#", "\\1", $time['time_users'] ) );
			$time['time_start'] = explode( ":", $time['time_start'] );
			$time['time_end']   = explode( ":", $time['time_end'] 	);
			
			$form .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->skin['global']->form_multiselect( "time_{$time['time_id']}_i_users", $users, $time['time_users'], "small", "style='width:112px;height:45px;'" ), "row1"   ),
								array(	$this->engine->skin['global']->form_radio( "time_{$time['time_id']}_i_allow", 1, $time['time_allow']	, &$this->engine->lang['time_allow'] )."<br/>".
										$this->engine->skin['global']->form_radio( "time_{$time['time_id']}_i_allow", 0, $time['time_disallow']	, &$this->engine->lang['time_disallow']	), "row1"   ),
								array(	"<div style='padding-bottom:2px;'><div style='float:right;'>".
										$this->engine->lang['time_from']."&nbsp;".
										$this->engine->skin['global']->form_dropdown( "time_{$time['time_id']}_i_start_hour"   , $dropdown['hours']  , $time['time_start'][0], "tiny"  ).
										$this->engine->skin['global']->form_dropdown( "time_{$time['time_id']}_i_start_minute" , $dropdown['minutes'], $time['time_start'][1], "tiny"  ).
										"</div><div style='padding-left:39px;'>".
										$this->engine->skin['global']->form_dropdown( "time_{$time['time_id']}_i_wday"		   , $dropdown['wdays']  , $time['time_every']	 , "small" ).
										"</div></div>".
										"<div><div style='float:right;'>".
										$this->engine->lang['time_to']."&nbsp;".
										$this->engine->skin['global']->form_dropdown( "time_{$time['time_id']}_i_end_hour"  , $dropdown['hours']  , $time['time_end'][0], "tiny"  ).
										$this->engine->skin['global']->form_dropdown( "time_{$time['time_id']}_i_end_minute", $dropdown['minutes'], $time['time_end'][1], "tiny"  ).
										"</div>", "row1"   ),
								array(	$this->engine->skin['global']->element_button( $time['time_id'], "time", "delete" ), "row1", "style='text-align:center;'" ),
								)	);
		}
		
		$form .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->skin['global']->form_multiselect( "time_".( $last_id + 2 )."_i_users", $users, array(), "small", "style='width:112px;height:45px;' onblur='ajax_add_list_row(this,".( $last_id + 3 ).");'" ), "row1" ),
								array(	$this->engine->skin['global']->form_radio( "time_".( $last_id + 2 )."_i_allow", 1, 1, &$this->engine->lang['time_allow'] )."<br/>".
										$this->engine->skin['global']->form_radio( "time_".( $last_id + 2 )."_i_allow", 0, 0, &$this->engine->lang['time_disallow'] ), "row1"   ),
								array(	"<div style='padding-bottom:2px;'><div style='float:right;'>".
										$this->engine->lang['time_from']."&nbsp;".
										$this->engine->skin['global']->form_dropdown( "time_".( $last_id + 2 )."_i_start_hour"   , $dropdown['hours']  , "", "tiny"  ).
										$this->engine->skin['global']->form_dropdown( "time_".( $last_id + 2 )."_i_start_minute" , $dropdown['minutes'], "", "tiny"  ).
										"</div><div style='padding-left:39px;'>".
										$this->engine->skin['global']->form_dropdown( "time_".( $last_id + 2 )."_i_wday"		 , $dropdown['wdays']  , 1 , "small" ).
										"</div></div>".
										"<div><div style='float:right;'>".
										$this->engine->lang['time_to']."&nbsp;".
										$this->engine->skin['global']->form_dropdown( "time_".( $last_id + 2 )."_i_end_hour"  , $dropdown['hours']  , "", "tiny"  ).
										$this->engine->skin['global']->form_dropdown( "time_".( $last_id + 2 )."_i_end_minute", $dropdown['minutes'], "", "tiny"  ).
										"</div>", "row1"   ),
								array(	$this->engine->skin['global']->element_button( 0, "time", "delete" ), "row1", "style='text-align:center;'" ),
								)	);
								
		$form .= $this->engine->classes['output']->table_add_submit( $this->engine->lang['apply_settings'] );
		
		$form .= $this->engine->classes['output']->table_end();
		
		$form .= $this->engine->classes['output']->form_end();
		
		//-----------------------------------------------
		// Обновляем список настроек и возвращаем XML
		//-----------------------------------------------
		
		if( in_array( $this->engine->input['type'], array( 'apply_params', 'delete_limit' ) ) )
		{
			if( $this->engine->input['type'] == 'apply_params' and $this->_get_running_events() !== FALSE )
			{
				if( $this->_get_running_events() !== FALSE ) $array['Function_0'] = "ajax_delete_running()";
				
				$array['Message'] = &$this->engine->lang['time_params_updated'];
			}
			else if( $this->engine->input['type'] == 'apply_params' )
			{
				$array['Message'] = &$this->engine->lang['time_params_updated'];
			}
			
			$array['Function_1'] = 'ajax_reselect_events()';
			
			$this->engine->input['id'] = &$this->engine->input['auser'];
			$this->engine->input['sub'] = &$this->engine->input['asub'];
			
			$this->_get_events_list();
			
			$array['List'] = &$this->html;
		}
		
		$array['HTML'] = $this->engine->skin['global']->ajax_window( $this->engine->lang['time_params'], &$form );
		
		$this->engine->classes['output']->generate_xml_output( &$array );
	}
	
	/**
    * Применение параметров расписания
    * 
    * Вносит изменения в параметры расписания закачек в
    * соответствии с обработанными данными формы.
    *
    * @return	void
    */
	
	function ajax_apply_params()
	{
		//-----------------------------------------------
		// Проверяем права
		//-----------------------------------------------
		
		if( !$this->engine->member['user_admin'] )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_rights'] ) );
		}
		
		//-----------------------------------------------
		// Получаем список пользователей
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'user_id',
													'from'		=> 'users_list',
													)	);
		$this->engine->DB->simple_exec();
		
		while( $user = $this->engine->DB->fetch_row() )
		{
			$users[] = $user['user_id'];
		}
		
		//-----------------------------------------------
		// Получаем список параметров
		//-----------------------------------------------
		
		$times['old'] = array();
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'time_id',
													'from'		=> 'schedule_time',
													)	);
		$this->engine->DB->simple_exec();
		
		while( $time = $this->engine->DB->fetch_row() )
		{
			$times['old'][] = $time['time_id'];
		}
		
		//-----------------------------------------------
		// Проверяем переданные значения
		//-----------------------------------------------
		
		$year_now = date( "Y" );
		$time_now = time();
		
		foreach( $this->engine->input as $name => $value ) if( preg_match( "#time_(\d+)_(s|i)_(users|allow|start_day|start_month|start_hour|start_minute|end_day|end_month|end_hour|end_minute|wday)#", $name, $match ) )
		{
			if( is_array( $times['new'][ $match[2] ][ $match[1] ] ) )
			{
				$times['new'][ $match[2] ][ $match[1] ] = array_merge( $times['new'][ $match[2] ][ $match[1] ], array( $match[3] => $value ) );
			}
			else
			{
				
				$times['new'][ $match[2] ][ $match[1] ] = array( $match[3] => $value );
			}
		}
		
		if( !is_array( $times['new']['s'] ) or !is_array( $times['new']['i'] ) ) $this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_params'] ) );
		
		//-----------------------------------------------
		// Единовременные ограничения
		//-----------------------------------------------
		
		foreach( $times['new']['s'] as $tid => $time )
		{
			//-----------------------------------------------
			// Список пользователей
			//-----------------------------------------------
			
			if( !is_array( $time['users'] ) or !count( $time['users'] ) )
			{
				if( in_array( $tid, $times['old'] ) ) $this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_users_id'] ) );
				
				unset( $times['new']['s'][ $tid ] );
				
				continue;
			}
			
			foreach( $time['users'] as $uid )
			{
				if( $uid and !in_array( $uid, $users ) ) $this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_wrong_user_id'] ) );
			}
			
			//-----------------------------------------------
			// Начальная дата
			//-----------------------------------------------
			
			if( !$start = mktime( $time['start_hour'], $time['start_minute'], 0, $time['start_month'], $time['start_day'], $year_now ) )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_wrong_start_date'] ) );
			}
			
			//-----------------------------------------------
			// Конечная дата
			//-----------------------------------------------
			
			if( !$end = mktime( $time['end_hour'], $time['end_minute'], 0, $time['end_month'], $time['end_day'], $year_now ) )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_wrong_end_date'] ) );
			}
			
			//-----------------------------------------------
			// Сравнение дат
			//-----------------------------------------------
			
			if( $start >= $end ) $this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_wrong_dates'] ) );
			
			//-----------------------------------------------
			// Все нормально, сохраняем текущий параметр
			//-----------------------------------------------
			
			$array = array( 'time_users'		=> ",".implode( ",", $time['users'] ).",",
							'time_start'		=> $time['start_month'].":".$time['start_day'].":".$time['start_hour'].":".$time['start_minute'],
							'time_end'			=> $time['end_month'].":".$time['end_day'].":".$time['end_hour'].":".$time['end_minute'],
							'time_every'		=> NULL,
							'time_interlace'	=> 0,
							'time_allow'		=> $time['allow'] ? 1 : 0,
							);
			
			in_array( $tid, $times['old'] ) ? $times['parsed']['old'][ $tid ] = $array : $times['parsed']['new'][] = $array;
			
			unset( $times['new']['s'][ $tid ] );
		}
		
		//-----------------------------------------------
		// Чередующиеся ограничения
		//-----------------------------------------------
		
		foreach( $times['new']['i'] as $tid => $time )
		{
			//-----------------------------------------------
			// Список пользователей
			//-----------------------------------------------
			
			if( !is_array( $time['users'] ) or !count( $time['users'] ) )
			{
				if( in_array( $tid, $times['old'] ) ) $this->engine->classes['output']->generate_xml_output( array( 'Message' => $this->engine->lang['error_no_users_id'].$tid ) );
				
				unset( $times['new']['s'][ $tid ] );
				
				continue;
			}
			
			foreach( $time['users'] as $uid )
			{
				if( $uid and !in_array( $uid, $users ) ) $this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_wrong_user_id'] ) );
			}
			
			//-----------------------------------------------
			// День недели
			//-----------------------------------------------
			
			if( !is_numeric( $time['wday'] ) or $time['wday'] < 0 or $time['wday'] > 6 ) $this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_wrong_wday'] ) );
			
			//-----------------------------------------------
			// Начальное время
			//-----------------------------------------------
			
			if( !is_numeric( $time['start_hour'] ) or $time['start_hour'] < 0 or $time['start_hour'] > 23 or !is_numeric( $time['start_minute'] ) or $time['start_minute'] < 0 or $time['start_minute'] > 59 )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_wrong_start_time'] ) );
			}
			
			$start = intval( $time['start_hour'] ).":".intval( $time['start_minute'] );
			
			//-----------------------------------------------
			// Конечное время
			//-----------------------------------------------
			
			if( !is_numeric( $time['end_hour'] ) or $time['end_hour'] < 0 or $time['end_hour'] > 23 or !is_numeric( $time['end_minute'] ) or $time['end_minute'] < 0 or $time['end_minute'] > 59 )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_wrong_end_time'] ) );
			}
			
			$end = intval( $time['end_hour'] ).":".intval( $time['end_minute'] );
			
			//-----------------------------------------------
			// Сравнение времен
			//-----------------------------------------------
			
			if( $time['start_hour'] > $time['end_hour'] or ( $time['start_hour'] == $time['end_hour'] and $time['start_minute'] >= $time['end_minute'] ) )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_wrong_times'] ) );
			}
			
			//-----------------------------------------------
			// Все нормально, сохраняем текущий параметр
			//-----------------------------------------------
			
			$array = array( 'time_users'		=> ",".implode( ",", $time['users'] ).",",
							'time_start'		=> $start,
							'time_end'			=> $end,
							'time_every'		=> intval( $time['wday'] ),
							'time_interlace'	=> 1,
							'time_allow'		=> $time['allow'] ? 1 : 0,
							);
			
			in_array( $tid, $times['old'] ) ? $times['parsed']['old'][ $tid ] = $array : $times['parsed']['new'][] = $array;
			
			unset( $times['new']['i'][ $tid ] );
		}
		
		//-----------------------------------------------
		// Сохраняем параметры в БД
		//-----------------------------------------------
		
		if( is_array( $times['parsed']['old'] ) ) foreach( $times['parsed']['old'] as $tid => $time )
		{
			$this->engine->DB->do_update( "schedule_time", &$time, "time_id='{$tid}'" );
		}
		
		if( is_array( $times['parsed']['new'] ) ) foreach( $times['parsed']['new'] as $time )
		{
			$this->engine->DB->do_insert( "schedule_time", &$time );
		}
		
		//-----------------------------------------------
		// Снимаем (добавляем) блокировки и обновляем список
		//-----------------------------------------------
		
		$this->_update_events_state();
		
		$this->ajax_show_params();
	}
	
	/**
    * Удаление временного ограничения
    * 
    * Удаляет временное ограничение и обновляет информацию
    * о текущих временных ограничениях пользователей.
    *
    * @return	void
    */
	
	function ajax_delete_limit()
	{
		//-----------------------------------------------
		// Проверяем права
		//-----------------------------------------------
		
		if( !$this->engine->member['user_admin'] )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_rights'] ) );
		}
		
		//-----------------------------------------------
		// Получаем параметры ограничения
		//-----------------------------------------------
		
		$limit  = $this->engine->DB->simple_exec_query( array(	'select'	=> 'time_id',
																'from'		=> 'schedule_time',
																'where'		=> "time_id='{$this->engine->input['id']}'"
																)	);
																
		if( !$limit['time_id'] )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_limit_id'] ) );
		}
		
		//-----------------------------------------------
		// Удаляем ограничение и пересчитывам оставшиеся
		//-----------------------------------------------
		
		$this->engine->DB->do_delete( "schedule_time", "time_id='{$this->engine->input['id']}'" );
		
		$this->_update_events_state();
		
		//-----------------------------------------------
		// Обновляем список ограничений
		//-----------------------------------------------
		
		$this->ajax_show_params();
	}
	
	/**
    * Остановка и блокировка закачек и событий
    * 
    * Останавливает, а затем блокирует закачки, а также
    * блокирует события, попадающие в запрещенные
    * временные промежутки.
    *
    * @return	void
    */
	
	function ajax_delete_running()
	{
		//-----------------------------------------------
		// Получаем список запрещенных закачек и событий
		//-----------------------------------------------
		
		$list = $this->_get_running_events();
		
		if( $list === FALSE ) $this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_running_list_empty'] ) );
		
		//-----------------------------------------------
		// Останавливаем и блокируем запрещенные закачки
		//-----------------------------------------------
		
		if( is_array( $list['files'] ) ) 
		{
			if( $this->engine->load_module( "class", "files" ) === FALSE )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['err_module_load_failed'] ) );
			}
			
			foreach( $list['files'] as $id )
			{
				$this->engine->DB->do_update( "sections_files", array( 'file_state' => 'blocked' ), "file_id='{$id}'" );
				
				$this->engine->classes['files']->download_stop( &$fid );
			}
		}
		
		//-----------------------------------------------
		// Блокируем запрещенные события
		//-----------------------------------------------
		
		if( is_array( $list['events'] ) ) foreach( $list['events'] as $id )
		{
			$this->engine->DB->do_update( "schedule_events", array( 'event_state' => 'blocked' ), "event_id='{$id}'" );
		}
		
		//-----------------------------------------------
		// Обновляем список и возвращаем XML
		//-----------------------------------------------
		
		$this->_get_events_list();
		
		$array = array( "Message"		=> &$this->engine->lang['time_running_stoped'],
						"List"			=> &$this->html,
						"Function_0"	=> 'ajax_reselect_events()',
						);
		
		$this->engine->classes['output']->generate_xml_output( &$array );
	}
	
	/**
    * Добавление и редактирование события
    * 
    * Выводит параметры текущего события с возможностью
    * их редактирования.
    *
    * @return	void
    */
	
	function ajax_event_add_edit()
	{
		$type = $this->engine->input['type'] == "event_add" ? "add" : "edit";
		
		$active = array(	'user' => intval( $this->engine->input['auser'] ),
						 	'sub'  => $this->engine->input['asub'],
							);
							
		$time_now = time();
		
		//-----------------------------------------------
		// Проверяем права
		//-----------------------------------------------
		
		if( !$this->engine->member['user_admin'] and ( ( $active['user'] and ( $this->engine->config['use_share_cats'] or !$this->engine->config['schedule_use_own'] ) ) or ( !$active['user'] and !$this->engine->config['schedule_use_share'] ) ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_rights'] ) );
		}
		
		//-----------------------------------------------
		// Получаем параметры события
		//-----------------------------------------------
		
		if( $type == "edit" )
		{
			$event = $this->engine->DB->simple_exec_query( array(	'select'	=> '*',
																	'from'		=> 'schedule_events',
																	'where'		=> "event_id='{$this->engine->input['id']}'"
																	)	);
			
			if( !$event['event_id'] ) $this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_event_id'] ) );
			
			if( $event['event_state'] == 'running' ) $this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_cant_apply_running'] ) );
		}
		
		//-----------------------------------------------
		// Обновляем параметры категории
		//-----------------------------------------------
		
		if( $this->engine->input['apply'] )
		{
			if( $type == "edit" and !$this->engine->input['event_interlaced'] and !in_array( $event['event_state'], array( 'query', 'blocked' ) ) )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_event_wrong_state'] ) );
			}
			
			$this->engine->input['event_wday']	 = intval( $this->engine->input['event_wday'] 	);
			$this->engine->input['event_month']  = intval( $this->engine->input['event_month'] 	);
			$this->engine->input['event_day']	 = intval( $this->engine->input['event_day'] 	);
			$this->engine->input['event_hour']	 = intval( $this->engine->input['event_hour'] 	);
			$this->engine->input['event_minute'] = intval( $this->engine->input['event_minute'] );
			
			if( $this->engine->input['event_interlaced'] and ( $this->engine->input['event_wday'] < 0 or $this->engine->input['event_wday'] > 6 ) )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_wrong_event_wday'] ) );
			}
			
			if( !$this->engine->input['event_interlaced'] and ( $this->engine->input['event_month'] < 1 or $this->engine->input['event_month'] >12 or $this->engine->input['event_day'] < 1 or $this->engine->input['event_day'] > 31 ) )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_wrong_event_date'] ) );
			}
			
			if( $this->engine->input['event_hour'] < 0 or $this->engine->input['event_hour'] > 23 or $this->engine->input['event_minute'] < 0 or $this->engine->input['event_minute'] > 59 )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_wrong_event_time'] ) );
			}
			
			$interlace = $this->engine->input['event_interlaced'] ? intval( $this->engine->input['event_wday'] ).":".intval( $this->engine->input['event_hour'] ).":".intval( $this->engine->input['event_minute'] ) : NULL;
			
			if( $this->engine->input['event_interlaced'] )
			{
				switch( $this->engine->input['event_wday'] )
				{
					case 1:
						$wday = 'monday';
						break;
						
					case 2:
						$wday = 'tuesday';
						break;
						
					case 3:
						$wday = 'wednesday';
						break;
						
					case 4:
						$wday = 'thursday';
						break;
						
					case 5:
						$wday = 'friday';
						break;
						
					case 6:
						$wday = 'saturday';
						break;
						
					default:
						$wday = 'sunday';
						break;
				}
				
				$date = "{$wday} {$this->engine->input['event_hour']}:{$this->engine->input['event_minute']}:00";
				
				$clock = strtotime( "This {$date}" );
				
				if( $clock < $time_now ) $clock = strtotime( "Next {$date}" );
			}
			else 
			{
				$year_now = date( "Y" );
				
				$clock = mktime( $this->engine->input['event_hour'], $this->engine->input['event_minute'], 0, $this->engine->input['event_month'], $this->engine->input['event_day'], $year_now );
				
				if( $clock === FALSE or $clock == -1 )
				{
					$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_wrong_event_full'] ) );
				}
				
				if( $clock < $time_now ) $clock = mktime( $this->engine->input['event_hour'], $this->engine->input['event_minute'], 0, $this->engine->input['event_month'], $this->engine->input['event_day'], $year_now + 1 );
			}
			
			$params = array(	'event_interlace'	=> &$interlace,
								'event_user'		=> $event['event_user'] ? $event['event_user'] : $active['user'],
								'event_time'		=> &$clock,
								'event_type'		=> $this->engine->input['event_interlaced'] ? 1 : 0,
								'event_state'		=> 'query',
								);
								
			//-----------------------------------------------
			// Проверяем наличие событий с идентичной датой
			//-----------------------------------------------
				
			if( $params['event_type'] and ( $type == "add" or ( $event['event_type'] and $params['event_interlace'] != $event['event_interlace'] ) ) )
			{
				$where  = $params['event_type'] ? "OR event_interlace='{$params['event_interlace']}' )" : " )";
				$where .= $type == "add" ? "" : " AND event_id<>'{$event['event_id']}'";
				
				$same = $this->engine->DB->simple_exec_query( array(	'select'	=> 'event_id',
																		'from'		=> 'schedule_events',
																		'where'		=> "( event_time={$params['event_time']} {$where}",
																		)	);
																		
				if( $same['event_id'] ) $this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_same_event'] ) );
			}
			
			//-----------------------------------------------
			// Проверяем временные промежутки
			//-----------------------------------------------
			
			if( $this->engine->load_module( "class", "files" ) === FALSE )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['err_module_load_failed'] ) );
			}
			
			if( $this->engine->classes['files']->_check_limits( $event['event_user'] ? $event['event_user'] : $active['user'], &$params['event_time'] ) === FALSE )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_event_time_prohibited']) );
			}
			
			//-----------------------------------------------
								
			if( $type == "add" )
			{
				$this->engine->DB->do_insert( "schedule_events", &$params );
			}
			else
			{
				$this->engine->DB->do_update( "schedule_events", &$params, "event_id='{$event['event_id']}'" );
				
				//-----------------------------------------------
				// Обновляем даты будущих чередующихся событий
				//-----------------------------------------------
				
				if( $params['event_type'] and $event['event_type'] and $params['event_interlace'] != $event['event_interlace'] )
				{
					$this->engine->DB->simple_construct( array(	'select'	=> 'event_id, event_time',
																'from'		=> 'schedule_events',
																'where'		=> "event_interlace='{$event['event_interlace']}'",
																)	);
					$this->engine->DB->simple_exec();
					
					while( $interlace = $this->engine->DB->fetch_row() )
					{
						$interlaced[ $interlace['event_id'] ] = strtotime( "This {$date}", $interlace['event_time'] );
						
						if( $interlaced[ $interlace['event_id'] ] < $time_now ) $interlaced[ $interlace['event_id'] ] = strtotime( "Next {$date}", $interlace['event_time'] );
					}
					
					if( is_array( $interlaced ) ) foreach( $interlaced as $id => $interlace )
					{
						$this->engine->DB->do_update( "schedule_events", array( 'event_interlace' => &$interlace ), "event_id='{$id}'" );
					}
				}
				
				$array['Message'] =& $this->engine->lang['event_edited'];
			}
			
			//-----------------------------------------------
			// Обновляем список событий
			//-----------------------------------------------
			
			$this->engine->input['id'] = &$active['user'];
			$this->engine->input['sub'] = &$active['sub'];
			
			$this->_get_events_list();
			
			$array['List'] = &$this->html;
			$array['Function_0'] = 'ajax_reselect_events()';
			
			//-----------------------------------------------
			// Закрываем AJAX окно при добалении
			//-----------------------------------------------
			
			if( $type == "add" )
			{
				$array['CloseWindow'] = TRUE;
				
				$this->engine->classes['output']->generate_xml_output( &$array );
			}
			
			//-----------------------------------------------
			// Обновляем параметры события
			//-----------------------------------------------
			
			$event = array(	'event_id'			=> &$event['event_id'],
							'event_interlace'	=> &$params['event_interlace'],
							'event_user'		=> &$params['event_user'],
							'event_time'		=> &$params['event_time'],
							'event_type'		=> &$params['event_type'],
							'event_state'		=> &$params['event_state'],
							);
		}
		
		//-----------------------------------------------
		// Создаем списки дней месяца, месяцев,
		// дней недели, часов и минут
		//-----------------------------------------------
		
		for( $i = 1; $i < 32; $i++ )
		{
			$dropdown['days'][ $i ] = $i;
		}
		
		for( $i = 1; $i < 13; $i++ )
		{
			$dropdown['months'][ $i ] = &$this->engine->lang[ 'mfull_'.sprintf( "%02d", $i ) ];
		}
		
		for( $i = 1; $i < 7; $i++ )
		{
			$dropdown['wdays'][ $i ] = &$this->engine->lang[ 'dfull_'.$i ];
		}
		
		$dropdown['wdays'][0] = &$this->engine->lang[ 'dfull_0' ];
		
		for( $i = 0; $i < 24; $i++ )
		{
			$dropdown['hours'][ $i ] = sprintf( "%02d", $i );
		}
		
		for( $i = 0; $i < 60; $i++ )
		{
			$dropdown['minutes'][ $i ] = sprintf( "%02d", $i );
		}
		
		//-----------------------------------------------
		// Формируем таблицу с параметрами
		//-----------------------------------------------
		
		if( $event['event_type'] )
		{
			$select = array( 'i'	=> 1,
							 's'	=> 0,
							 'di'	=> "",
							 'ds'	=> "style='display:none;'",
							 );
		}
		else 
		{
			$select = array( 'i'	=> 0,
							 's'	=> 1,
							 'di'	=> "style='display:none;'",
							 'ds'	=> "",
							 );
		}
						 
		$event['event_time'] = explode( ":", date( "w:j:n:G:i", $event['event_time'] ? $event['event_time'] : $time_now ) );
		$event['event_time'][4] = intval( $event['event_time'][4] );
		
		$table  = $this->engine->classes['output']->form_start( array(	'tab'		=> 'schedule',
																		), "id='ajax_form' onsubmit='ajax_event_apply( \"{$event['event_id']}\", \"{$type}\" ); return false;'" );
																			
		$this->engine->classes['output']->table_add_header( ""	, "40%" );
		$this->engine->classes['output']->table_add_header( ""	, "60%" );
		
		$table .= $this->engine->classes['output']->table_start( "", "100%", "", "", "id='ajax_table' style='border:0'" );
		
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['events_sort_type']																															, "row1" ),
								array(	$this->engine->skin['global']->form_radio( "event_interlaced", 0, $select['s'], &$this->engine->lang['event_standard'], "onclick='ajax_toggle_params(0);'" )."<br/>".
										$this->engine->skin['global']->form_radio( "event_interlaced", 1, $select['i'], &$this->engine->lang['event_interlaced'], "onclick='ajax_toggle_params(1);'" )	, "row2" ),
								)	);
								
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['event_date']																																, "row1" ),
								array(	"<div id='date_standard' {$select['ds']}>".
										$this->engine->skin['global']->form_dropdown( "event_day"	, $dropdown['days']  , $event['event_time'][1], "tiny"  ).
										$this->engine->skin['global']->form_dropdown( "event_month" , $dropdown['months'], $event['event_time'][2], "small" ).
										"</div><div id='date_interlaced' {$select['di']}>".
										$this->engine->skin['global']->form_dropdown( "event_wday"	, $dropdown['wdays'] , $event['event_time'][0], "small" ).
										"</div>"																																						, "row2" ),
								)	);
								
		$table .= $this->engine->classes['output']->table_add_row( array( 
								array(	$this->engine->lang['event_time']																																, "row1" ),
								array(	$this->engine->skin['global']->form_dropdown( "event_hour"  , $dropdown['hours']  , $event['event_time'][3], "tiny"  ).
										$this->engine->skin['global']->form_dropdown( "event_minute", $dropdown['minutes'], $event['event_time'][4], "tiny"  )											, "row2" ),
								)	);
								
		$table .= $this->engine->classes['output']->table_end();
								
		$table .= $this->engine->classes['output']->form_end_submit( $this->engine->lang['apply_settings'], "", "style='border:0;'" );
		
		//-----------------------------------------------
		// Формируем и возвращаем XML
		//-----------------------------------------------
		
		$html = $this->engine->skin['global']->ajax_window( &$this->engine->lang['event_info'], &$table );
		
		$array['HTML'] =& $html;
		
		$this->engine->classes['output']->generate_xml_output( &$array );
	}
	
	/**
    * Удаление событий
    * 
    * Удаляет выбранные события и отменяет закачки, с
    * ними связанные.
    *
    * @return	void
    */
	
	function ajax_event_delete()
	{
		$active = array(	'user'	=> &$this->engine->input['auser'],
							'sub'	=> &$this->engine->input['asub'],
							);
							
		//-----------------------------------------------
		// Проверяем права на удаление
		//-----------------------------------------------
		
		if( !$this->engine->member['user_admin'] and ( ( $active['user'] and ( $this->engine->config['use_share_cats'] or !$this->engine->config['schedule_use_own'] ) ) or ( !$active['user'] and !$this->engine->config['schedule_use_share'] ) ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_cant_delete_event'] ) );
		}
		
		//-----------------------------------------------
		// Получаем идентификаторы событий
		//-----------------------------------------------
		
		$ids = explode( ",", $this->engine->input['id'] );
		
		if( !count( $ids ) )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_event_ids'] ) );
		}
		
		if( $ids[0] == "" ) unset( $ids[0] );
		
		//-----------------------------------------------
		// Получаем параметры событий
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> '*',
													'from'		=> 'schedule_events',
													'where'		=> "event_id IN('".implode( "','", $ids )."')"
													)	);
		$this->engine->DB->simple_exec();
															
		if( !$this->engine->DB->get_num_rows() )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_no_events_found'] ) );
		}
		
		$to_delete = array();
		$to_update = array();
		$to_check  = array();
		
		while( $event = $this->engine->DB->fetch_row() )
		{
			if( !$this->engine->member['user_admin'] and !in_array( $event['event_user'], array( 0, $this->engine->member['user_id'] ) ) )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_cant_delete_event'] ) );
			}

			if( $event['event_state'] == "running" )
			{
				$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_cant_delete_running'] ) );
			}
			
			$to_delete[] = $event['event_id'];
			
			if( $event['event_type'] ) $to_check[] = $event['event_interlace'];
			
			foreach( explode( ",", $event['event_files'] ) as $fid )
			{
				if( !in_array( $fid, $to_update ) ) $to_update[] = $fid;
			}
		}
		
		//-----------------------------------------------
		// Проверяем чередующиеся события
		//-----------------------------------------------
		
		if( count( $to_check ) )
		{
			$this->engine->DB->simple_construct( array(	'select'	=> 'event_state, event_files',
														'from'		=> 'schedule_events',
														'where'		=> "event_interlace IN('".implode( "','", $to_check )."')"
														)	);
			$this->engine->DB->simple_exec();
			
			while( $event = $this->engine->DB->fetch_row() )
			{
				if( $event['event_state'] == "running" )
				{
					$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['error_cant_delete_interlaced'] ) );
				}
				
				foreach( explode( ",", $event['event_files'] ) as $fid )
				{
					if( !in_array( $fid, $to_update ) ) $to_update[] = $fid;
				}
			}
		}
		
		//-----------------------------------------------
		// Удаляем события
		//-----------------------------------------------
		
		if( count( $to_delete ) )
		{
			$this->engine->DB->do_delete( "schedule_events", "event_id IN('".implode( "','", $to_delete )."')" );
		}
		
		//-----------------------------------------------
		// Отменяем закачки
		//-----------------------------------------------
		
		if( count( $to_update ) )
		{
			$this->engine->DB->do_update( "categories_files", array( "file_state" => 'stopped' ), "file_state='query' AND file_id IN('".implode( "','", $to_update )."')" );
		}
		
		//-----------------------------------------------
		// Обновляем список событий и возвращаем XML
		//-----------------------------------------------
		
		$this->engine->input['id'] = &$active['user'];
		$this->engine->input['sub'] = &$active['sub'];
		
		$this->_get_events_list();
		
		$array['List'] = &$this->html;
		$array['Function_0'] = 'ajax_reselect_events()';
		
		$this->engine->classes['output']->generate_xml_output( &$array );
	}
	
	/**
    * Вывод списка пользователей
    * 
    * Получает список пользователей, добавляет в начало
    * дополнительные параметры и выводит на экран.
    *
    * @return	void
    */
	
	function _get_users_list()
	{
		//-----------------------------------------------
		// Определяем активный элемент
		//-----------------------------------------------
		
		$active = explode( ",", $this->engine->my_getcookie( "list_active" ) );
		
		foreach( $active as $item )
		{
			if( preg_match( "#schedule=(-?\d+):(\w+)#", $item, $match ) )
			{
				$this->active_user = array( "id" => $match[1], "sub" => $match[2] );
				
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
			if( preg_match( "#schedule=((\d+,?)*)#", $item, $match ) and $match[1] != "" )
			{
				$hidden_users = explode( ",", $match[1] );
				
				break;
			}
		}
		
		//-----------------------------------------------
		// Помещаем в начало списка дополнительные пункты
		//-----------------------------------------------
		
		$users[] = array( 'user_name' => &$this->engine->lang['user_all']  , 'user_id' => -1 );
		$users[] = array( 'user_name' => &$this->engine->lang['user_share'], 'user_id' => 0  );
		
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
				$users[] = $user;
				
				if( $user['user_id'] == $this->active_user['id'] ) $got_active = TRUE;
			}
		}
		else 
		{
			$users[] = &$this->engine->member;
			
			if( $this->engine->member['user_id'] == $this->active_user['id'] ) $got_active = TRUE;
		}
		
		//-----------------------------------------------
		// Проверяем наличие активного пользователя
		//-----------------------------------------------
		
		if( !$got_active and !is_numeric( $this->active_user['id'] ) )
		{
			$this->active_user = array( "id" => -1, "sub" => 'today' );
		}
		
		if( $this->active_user['sub'] != 'all' and in_array( $this->active_user['id'], $hidden_users ) )
		{
			$this->active_user['sub'] = 'all';
		}
		
		$this->engine->classes['output']->java_scripts['embed'][] = "var active_user = '{$this->active_user['id']}';
																	 var active_sub = '{$this->active_user['sub']}';";
		
		//-----------------------------------------------
		// Выводим список пользователей
		//-----------------------------------------------
		
		foreach( $users as $user )
		{
			$active = $user['user_id'] == $this->active_user['id'] ? 1 : 0;
			$hidden = in_array( $user['user_id'], $hidden_users ) ? 1 : 0;
			
			$this->html .= $this->engine->skin['schedule']->users_list_item( &$user, $user['user_id'] > 0 ? 'single' : 'multi', &$active, &$hidden, $this->active_user['sub'] );
		}
	}
	
	/**
    * Вывод списка событий
    * 
    * Формирует и выводит список событий в соответствии
    * с заданным временным интервалом.
    *
    * @return	void
    */
	
	function _get_events_list()
	{
		//-----------------------------------------------
		// Определяем активный столбец и тип сортировки
		//-----------------------------------------------
		
		preg_match( "#tab_schedule_(user|time|type|state)=(asc|desc)#", $this->engine->my_getcookie( "sort_params" ), $match );
		
		$match[1] = $match[1] ? $match[1] : "time";
		$match[2] = $match[2] ? ( $match[2] == "asc" ? "desc" : "asc" ) : "asc";
		
		if( $this->engine->input['id'] < 0 )
		{
			$active['user']	= $match[1] == 'user' ? array( 'img' => $this->engine->skin['schedule']->events_list_sort( $match[2] ), 'sort' => $match[2] ) : array( 'sort' => 'desc' );
		}
		
		$active['time']	 = $match[1] == 'time'  ? array( 'img' => $this->engine->skin['schedule']->events_list_sort( $match[2] ), 'sort' => $match[2] ) : array( 'sort' => 'desc' );
		$active['type']  = $match[1] == 'type'  ? array( 'img' => $this->engine->skin['schedule']->events_list_sort( $match[2] ), 'sort' => $match[2] ) : array( 'sort' => 'desc' );
		$active['state'] = $match[1] == 'state' ? array( 'img' => $this->engine->skin['schedule']->events_list_sort( $match[2] ), 'sort' => $match[2] ) : array( 'sort' => 'desc' );
		
		$this->html .= $this->engine->skin['schedule']->events_list_headers( &$active );
		
		//-----------------------------------------------
		// Условие выборки по идентификатору пользователя
		//-----------------------------------------------
		
		if( $this->engine->input['id'] == -1 )
		{
			if( !$this->engine->member['user_admin'] ) $where[] = "e.event_user='0' OR e.event_user='{$this->engine->member['user_id']}'";
		}
		else if( $this->engine->input['id'] == 0 )
		{
			$where[] = "e.event_user='0'";
		}
		else 
		{
			$where[] = $this->engine->member['user_admin'] ? "e.event_user='{$this->engine->input['id']}'" : "e.event_user='{$this->engine->member['user_id']}'";
		}
		
		//-----------------------------------------------
		// Условие выборки по времени события
		//-----------------------------------------------
		
		$time_now = time();
		
		switch( $this->engine->input['sub'] )
		{
			case 'pall':
				$where[] = "e.event_time < {$time_now}";
				break;
				
			case 'pmonth':
				$where[] = "e.event_time < {$time_now} AND e.event_time > ".strtotime( "-1 month 00:00:00" );
				break;
				
			case 'pweek':
				$where[] = "e.event_time < {$time_now} AND e.event_time > ".strtotime( "-1 week 00:00:00" );
				break;
				
			case 'pday':
				$where[] = "e.event_time < ".strtotime( "yesterday 23:59:59" )." AND e.event_time > ".strtotime( "yesterday 00:00:00" );
				break;
				
			case 'today':
				$where[] = "e.event_time < ".strtotime( "today 23:59:59" )." AND e.event_time > ".strtotime( "today 00:00:00" );
				break;
				
			case 'nday':
				$where[] = "e.event_time > ".strtotime( "tomorrow 00:00:00" )." AND e.event_time < ".strtotime( "tomorrow 23:59:59" );
				break;
				
			case 'nweek':
				$where[] = "e.event_time > {$time_now} AND e.event_time < ".strtotime( "+1 week 23:59:59" );
				break;
				
			case 'nmonth':
				$where[] = "e.event_time > {$time_now} AND e.event_time < ".strtotime( "+1 month 23:59:59" );
				break;
				
			case 'nall':
				$where[] = "e.event_time > {$time_now}";
				break;
		}
		
		//-----------------------------------------------
		// Определяем количество страниц
		//-----------------------------------------------
		
		$pages = $this->engine->DB->simple_exec_query( array(	'select'	=> 'COUNT(event_id) as total',
																'from'		=> 'schedule_events AS e LEFT JOIN users_list AS u ON (u.user_id=e.event_user)',
																'where'		=> is_array( $where ) ? implode( " AND ", &$where ) : ( $this->engine->member['user_admin'] ? "1" : "e.event_user='{$this->engine->member['user_id']}'" ),
																)	);
		
		$this->pages_total = intval( is_numeric( $pages['total'] ) ? ceil( $pages['total'] / 100 ) : 1 );
		
		$this->engine->classes['output']->java_scripts['embed'][] = "pages_total = {$this->pages_total};
																	 pages_st = {$this->engine->input['st']};";
		
		//-----------------------------------------------
		// Получаем список событий
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> '*',
													'from'		=> 'schedule_events AS e LEFT JOIN users_list AS u ON (u.user_id=e.event_user)',
													'where'		=> is_array( $where ) ? implode( " AND ", &$where ) : ( $this->engine->member['user_admin'] ? "1" : "e.event_user='{$this->engine->member['user_id']}'" ),
													'order'		=> "e.event_{$match[1]} {$match[2]}",
													'limit'		=> array( $this->engine->input['st'], 100 ),
													)	);
		$this->engine->DB->simple_exec();
		
		if( !$this->engine->DB->get_num_rows() )
		{
			$this->html .= $this->engine->skin['schedule']->events_list_message( &$this->engine->lang['no_events'] );
		}
		else while( $event = $this->engine->DB->fetch_row() )
		{
			$row = $row == 5 ? 6 : 5;
			
			$event['user_name']	  = $event['user_name'] ? $event['user_name'] : $this->engine->lang['event_shared'];
			$event['event_type']  = &$this->engine->lang['event_'.( $event['event_type'] ? 'interlaced' : 'standard' ) ];
			$event['event_time']  = $this->engine->get_date( &$event['event_time'], "LONG" );
			$event['event_state'] = $this->engine->skin['schedule']->event_state( &$event['event_state'] );
			
			$this->html .= $this->engine->skin['schedule']->schedule_event_row( &$event, $row );
		}
		
		$this->html .= $this->engine->skin['schedule']->events_list_footer();
	}
	
	/**
    * Обновление состояний закачек и событий
    * 
    * Вызывает соответствующую функцию в классе работы
    * с файлами.
    *
    * @return	void
    */
	
	function _update_events_state()
	{
		if( $this->engine->load_module( "class", "files" ) === FALSE )
		{
			$this->engine->classes['output']->generate_xml_output( array( 'Message' => &$this->engine->lang['err_module_load_failed'] ) );
		}
		
		$this->engine->classes['files']->_update_events_state();
	}
	
	/**
    * Поиск текущих событий и закачек, попадающих под запрет
    * 
    * Производит поиск текщих событий и закачек, которые в
    * данный момент попадают в промежуток, в течение которого
    * закачки запрещены.
    * В случае, если события или закачки найдены, возвращает
    * массив с их идентификаторами. В противном случае
    * возвращает FALSE.
    *
    * @return	array	Идентификаторы событий и закачек или
    * 			bool	FALSE
    */
	
	function _get_running_events()
	{
		//-----------------------------------------------
		// Получаем список выполняемых закачек
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'file_id, file_user',
													'from'		=> 'categories_files',
													'where'		=> "file_state IN('running','paused')",
													)	);
		$this->engine->DB->simple_exec();
		
		while( $file = $this->engine->DB->fetch_row() )
		{
			$files[ $file['file_id'] ] = $file['file_user'];
		}
		
		//-----------------------------------------------
		// Получаем список выполняемых заданий
		//-----------------------------------------------
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'event_id, event_user',
													'from'		=> 'schedule_events',
													'where'		=> "event_state='running'",
													)	);
		$this->engine->DB->simple_exec();
		
		while( $event = $this->engine->DB->fetch_row() )
		{
			$events[ $event['event_id'] ] = $event['event_user'];
		}
		
		//-----------------------------------------------
		// Создаем общий список пользователей
		//-----------------------------------------------
		
		if( !is_array( $files ) and !is_array( $events ) ) return FALSE;
		
		$users = array();
		
		if( is_array( $files ) ) foreach( $files as $user )
		{
			if( !in_array( $user, $users ) ) $users[] = $user;
		}
		
		if( is_array( $events ) ) foreach( $events as $user )
		{
			if( !in_array( $user, $users ) ) $users[] = $user;
		}
		
		//-----------------------------------------------
		// Формируем условия выборки
		//-----------------------------------------------
		
		$date_now = explode( ":", date( "w:G:i:Y" ) );
		$time_now = time();
		
		$date_now[2] = intval( $date_now[2] );
		
		$where  = "( time_users LIKE '%,".implode( ",%' OR time_users LIKE '%,", $users ).",%' ) ";
		$where .= "AND ( ( time_allow=0 AND ( time_interlace=0 OR ( time_interlace=1 AND time_every={$date_now[0]} ) ) ) ";
		$where .= "OR ( time_allow=1 AND ( time_interlace=0 OR ( time_interlace=1 AND time_every={$date_now[0]} ) ) ) )";
		
		//-----------------------------------------------
		// Получаем список временных ограничений
		//-----------------------------------------------
		
		$blacklist = $unset = array();
		
		$this->engine->DB->simple_construct( array(	'select'	=> 'time_users, time_allow, time_start, time_end, time_interlace',
													'from'		=> 'schedule_time',
													'where'		=> &$where,
													)	);
		$this->engine->DB->simple_exec();
		
		while( $time = $this->engine->DB->fetch_row() )
		{
			$numbers['start'] = explode( ":", $time['time_start'] );
			$numbers['end']   = explode( ":", $time['time_end']   );
			
			if( $time['time_interlace'] )
			{
				if( $time['time_allow'] )
				{
					//-----------------------------------------------
					// Начало ограничения ранее текущего времени и
					// конец ограничения позднее текущего времени
					//-----------------------------------------------
					
					if( ( $numbers['start'][0] < $date_now[1] or ( $numbers['start'][0] == $date_now[1] and $numbers['start'][1] <= $date_now[2] ) ) and
						( $numbers['end'][0] > $date_now[1] or ( $numbers['end'][0] == $date_now[1] and $numbers['end'][1] > $date_now[2] ) ) )
						{
							if( !in_array( $user, $unset ) ) $unset[] = $user;
							
							continue;
						}
				}
				else 
				{
					//-----------------------------------------------
					// Начало ограничения позднее текущего времени или
					// конец ограничения ранее текущего времени
					//-----------------------------------------------
					
					if( ( $numbers['start'][0] > $date_now[1] or ( $numbers['start'][0] == $date_now[1] and $numbers['start'][1] > $date_now[2] ) ) or
						( $numbers['end'][0] < $date_now[1] or ( $numbers['end'][0] == $date_now[1] and $numbers['end'][1] <= $date_now[2] ) ) ) continue;
				}
			}
			else 
			{
				$time['time_start'] = strtotime( "{$date_now[3]}-{$numbers['start'][0]}-{$numbers['start'][1]} {$numbers['start'][2]}:{$numbers['start'][3]}:00" );
				$time['time_end'] = strtotime( "{$date_now[3]}-{$numbers['end'][0]}-{$numbers['end'][1]} {$numbers['end'][2]}:{$numbers['end'][3]}:00" );
				
				if( $time['time_allow'] )
				{
					//-----------------------------------------------
					// Начало ограничения ранее текущего времени и
					// конец ограничения позднее текущего времени
					//-----------------------------------------------
					
					if( $time['time_start'] <= $time_now and $time['time_end'] > $time_now )
					{
						if( !in_array( $user, $unset ) ) $unset[] = $user;
						
						continue;
					}
				}
				else 
				{
					//-----------------------------------------------
					// Начало ограничения позднее текущего времени или
					// конец ограничения ранее текущего времени
					//-----------------------------------------------
					
					if( $time['time_start'] > $time_now or $time['time_end'] <= $time_now ) continue;
				}
			}
			
			$users = explode( ",", preg_replace( "#^,(.*),$#", "\\1", $time['time_users'] ) );
			
			foreach( $users as $user ) if( !in_array( $user, $blacklist ) ) $blacklist[] = $user;
		}
		
		if( !count( $blacklist ) ) return FALSE;
		
		foreach( $blacklist as $bid => $user )
		{
			if( in_array( $user, $unset ) ) unset( $blacklist[ $bid ] );
		}
		
		//-----------------------------------------------
		// Формируем список запрещенных закачек
		//-----------------------------------------------
		
		if( is_array( $files ) ) foreach( $files as $fid => $user )
		{
			if( in_array( $user, $blacklist ) ) $stop['files'][] = $fid;
		}
		
		//-----------------------------------------------
		// Формируем список запрещенных событий
		//-----------------------------------------------
		
		if( is_array( $events ) ) foreach( $events as $eid => $user )
		{
			if( in_array( $user, $blacklist ) ) $stop['events'][] = $eid;
		}
		
		//-----------------------------------------------
		// Возвращаем список
		//-----------------------------------------------
		
		return is_array( $stop ) ? $stop : FALSE;
	}

}

?>